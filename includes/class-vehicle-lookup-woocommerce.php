<?php
class VehicleLookupWooCommerce {
    
    public function init() {
        // WooCommerce hooks
        add_action('woocommerce_checkout_create_order', array($this, 'save_registration_to_order'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'update_order_meta'));
    }

    /**
     * Get registration number from cookie
     */
    private function get_registration_number() {
        if (isset($_COOKIE['vehicle_reg_number'])) {
            return Vehicle_Lookup_Helpers::normalize_plate(sanitize_text_field($_COOKIE['vehicle_reg_number']));
        }
        return false;
    }

    /**
     * Save registration number to order during creation
     */
    public function save_registration_to_order($order, $data) {
        if ($reg_number = $this->get_registration_number()) {
            $order->update_meta_data('reg_number', $reg_number);
        }

        // Format and validate phone number when saving - try multiple sources
        $billing_phone = '';

        error_log("Vehicle Lookup: Starting phone extraction for order during creation");
        error_log("Vehicle Lookup: Available data keys: " . implode(', ', array_keys($data)));

        // First try the data array
        if (!empty($data['billing_phone'])) {
            $billing_phone = $data['billing_phone'];
            error_log("Vehicle Lookup: Phone found in data array: {$billing_phone}");
        }
        // Then try getting it from the order object (for Vipps Express)
        elseif (method_exists($order, 'get_billing_phone')) {
            $billing_phone = $order->get_billing_phone();
            if (!empty($billing_phone)) {
                error_log("Vehicle Lookup: Phone found via order->get_billing_phone(): {$billing_phone}");
            } else {
                error_log("Vehicle Lookup: order->get_billing_phone() returned empty");
            }
        }

        if (!empty($billing_phone)) {
            $formatted_phone = $this->format_phone_number($billing_phone);
            $order->update_meta_data('formatted_billing_phone', $formatted_phone);

            // Log for debugging
            error_log("Vehicle Lookup: INITIAL SUCCESS - Original phone: {$billing_phone}, Formatted: {$formatted_phone}");
        } else {
            error_log("Vehicle Lookup: INITIAL FAILED - No phone number found during order creation, fallback will be needed");
        }
    }

    /**
     * Format phone number to international Norwegian format (+47xxxxxxxx)
     */
    public function format_phone_number($phone) {
        // Handle array input (WooCommerce sometimes returns arrays)
        if (is_array($phone)) {
            $phone = reset($phone);
        }

        // Convert to string and remove spaces/special chars except +
        $phone = (string)$phone;
        $clean = preg_replace('/[^\d+]/', '', $phone);

        // If already in correct Norwegian format, return as-is
        if (preg_match('/^\+47\d{8}$/', $clean)) {
            return $clean;
        }

        // Handle different input formats
        $digits_only = $clean;

        // Remove +47 prefix if present
        if (strpos($digits_only, '+47') === 0) {
            $digits_only = substr($digits_only, 3);
        }
        // Remove + and any other country codes first
        elseif (strpos($digits_only, '+') === 0) {
            // Remove + and any non-Norwegian country codes
            $digits_only = preg_replace('/^\+(?!47)\d{1,3}/', '', $digits_only);
            $digits_only = ltrim($digits_only, '+');
        }

        // Remove leading zeros
        $digits_only = ltrim($digits_only, '0');

        // Check if we have exactly 8 digits and it's a valid Norwegian mobile number
        if (strlen($digits_only) === 8 && preg_match('/^[4-9]\d{7}$/', $digits_only)) {
            error_log('Vehicle Lookup: Formatting 8-digit number: ' . $digits_only . ' to +47' . $digits_only);
            return '+47' . $digits_only;
        }

        // Check if we have 10 digits starting with 47 (Norwegian country code + 8 digit mobile)
        if (strlen($digits_only) === 10 && strpos($digits_only, '47') === 0) {
            $mobile_part = substr($digits_only, 2);
            if (preg_match('/^[4-9]\d{7}$/', $mobile_part)) {
                error_log('Vehicle Lookup: Formatting 10-digit number with 47 prefix: ' . $digits_only . ' to +47' . $mobile_part);
                return '+47' . $mobile_part;
            }
        }

        // If not valid Norwegian mobile format, try to format as international
        if (strlen($digits_only) >= 8) {
            error_log('Vehicle Lookup: Formatting as international number: +' . $digits_only);
            return '+' . $digits_only;
        }

        // Last resort - return original
        error_log('Vehicle Lookup: Could not format phone number: ' . $phone . ' (extracted: ' . $digits_only . ', length: ' . strlen($digits_only) . ')');
        return $phone;
    }

    /**
     * Update order meta (legacy method kept for compatibility)
     */
    public function update_order_meta($order_id) {
        // This method is now redundant since we save during order creation
        return;
    }

    /**
     * Check if order contains vehicle lookup product
     */
    public function validate_order_has_lookup($order) {
        $lookup_product_ids = [62, 739]; // Basic and Premium product IDs
        foreach ($order->get_items() as $item) {
            if (in_array($item->get_product_id(), $lookup_product_ids)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get product tier from order
     */
    public function get_order_tier($order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id == 739) {
                return 'premium';
            } elseif ($product_id == 62) {
                return 'basic';
            }
        }
        return 'free';
    }
}
