<?php
class Order_Confirmation_Shortcode {
    public function init() {
        add_shortcode('order_confirmation', array($this, 'render_shortcode'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_complete'));
    }

    public function handle_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        $reg_number = $order->get_meta('custom_reg') ?: $order->get_meta('reg_number');

        if (empty($reg_number)) {
            error_log('Payment complete but no registration number found for order: ' . $order_id);
            return;
        }

        if ($reg_number && $this->validate_order_has_lookup($order)) {
            $transient_key = 'owner_access_' . $reg_number;
            set_transient($transient_key, true, 24 * HOUR_IN_SECONDS);
        }
    }

    public function handle_order_complete($order_id) {
        $this->handle_payment_complete($order_id);
    }

    private function validate_order_has_lookup($order) {
        $lookup_product_id = 62; // Hardcoded product ID from vehicle-lookup.js

        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $lookup_product_id) {
                return true;
            }
        }
        return false;
    }

    public function render_shortcode($atts) {
        $order_id = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : '';
        $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

        if (empty($order_id) || empty($order_key)) {
            return '<p>Invalid order information.</p>';
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            return '<p>Invalid order information.</p>';
        }

        if (!in_array($order->get_status(), ['completed', 'processing'])) {
            return '<p>Order payment not yet confirmed.</p>';
        }

        if (!$this->validate_order_has_lookup($order)) {
            return '<p>Order does not contain vehicle lookup product.</p>';
        }

        // Get registration number from WooCommerce order meta
        $reg_number = '';
        $reg_fields = ['custom_reg', 'reg_number', '_custom_reg', '_reg_number', 'regNumber'];
        
        // First try direct meta access
        foreach ($reg_fields as $field) {
            $reg_number = get_post_meta($order_id, $field, true);
            if (!empty($reg_number)) {
                error_log("Found registration number in post meta {$field}: {$reg_number}");
                break;
            }
        }
        
        // If not found, try WC meta
        if (empty($reg_number)) {
            foreach ($reg_fields as $field) {
                $reg_number = $order->get_meta($field);
                if (!empty($reg_number)) {
                    error_log("Found registration number in WC meta {$field}: {$reg_number}");
                    break;
                }
            }
        }

        // Enhanced debug logging with all possible data sources
        error_log("\n\n=== DEBUG: COMPLETE ORDER DATA ===");
        error_log("Basic Order Info:");
        error_log("- Order ID: " . $order_id);
        error_log("- Order Key: " . $order_key);
        error_log("- Order Status: " . $order->get_status());
        error_log("- Payment Method: " . $order->get_payment_method());
        
        error_log("\nOrder Items:");
        foreach ($order->get_items() as $item) {
            error_log("- Product ID: " . $item->get_product_id());
            error_log("  Name: " . $item->get_name());
            error_log("  Quantity: " . $item->get_quantity());
        }
        
        error_log("\nOrder Meta Data:");
        foreach ($order->get_meta_data() as $meta) {
            error_log("- Key: '" . $meta->key . "'");
            error_log("  Value: '" . print_r($meta->value, true) . "'");
        }
        
        error_log("\nDirectly Checking Registration Fields:");
        foreach ($reg_fields as $field) {
            error_log("- Checking '" . $field . "': '" . $order->get_meta($field) . "'");
        }
        error_log("Direct Meta Access:");
        foreach ($reg_fields as $field) {
            error_log("- Field '{$field}' direct get_meta(): " . print_r($order->get_meta($field), true));
        }
        error_log("Request Data:");
        error_log("- POST: " . print_r($_POST, true));
        error_log("- GET: " . print_r($_GET, true));
        error_log("=== DEBUG: DETAILED ORDER DATA END ===\n\n");

        foreach ($reg_fields as $field) {
            $value = $order->get_meta($field);
            error_log('Order ' . $order_id . ' - Checking field ' . $field . ': ' . var_export($value, true));
            if (!empty($value)) {
                $reg_number = $value;
                break;
            }
        }

        if (empty($reg_number)) {
            error_log('Order ' . $order_id . ': No registration number found in meta fields: ' . implode(', ', $reg_fields));
            return '<p>Ingen registreringsnummer funnet for denne ordren. Vennligst kontakt support.</p>';
        }

        // Verify order exists and is valid
        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            return '<p>Invalid order information.</p>';
        }

        // Check if transient already exists before creating
        $transient_key = 'owner_access_' . $reg_number;
        if (false === get_transient($transient_key)) {
            $expiry = 24 * HOUR_IN_SECONDS; // 24 hours
            set_transient($transient_key, true, $expiry);
        }

        ob_start();
        ?>
        <div class="vehicle-lookup-container order-confirmation-container">
            <h2>✅ Bestilling bekreftet</h2>
            <p>Betalingen er gjennomført</p>
            <div class="plate-display">
                <div class="plate-flag">
                    <span>N</span>
                    <span class="plate-country">NORGE</span>
                </div>
                <strong class="plate-number"><?php echo esc_html($reg_number); ?></strong>
            </div>
            <div id="vehicle-lookup-results" class="results-wrapper"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            const regNumber = '<?php echo esc_js($reg_number); ?>';
            $.ajax({
                url: vehicleLookupAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vehicle_lookup',
                    nonce: vehicleLookupAjax.nonce,
                    regNumber: regNumber
                },
                success: function(response) {
                    if (response.success) {
                        $('#vehicle-lookup-results').show();
                        // Trigger the existing vehicle lookup display logic
                        $(document).trigger('vehicleLookupComplete', [response.data]);
                    }
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
?>