<?php
class SMS_Handler {
    public function init() {
        add_action('woocommerce_payment_complete', array($this, 'send_owner_notification'), 20);
        add_action('beepi_sms_sent_success', array($this, 'log_sms_success'), 10, 3);
        add_action('beepi_sms_sent_failed', array($this, 'log_sms_failure'), 10, 2);
    }

    public function send_owner_notification($order_id) {
        $order = wc_get_order($order_id);

        // Get registration number - standardized to use 'reg_number' field
        $reg_number = $order->get_meta('reg_number');

        if (empty($reg_number) || !$this->validate_order_has_lookup($order)) {
            error_log('SMS Handler: No registration number found for order ' . $order_id);
            return;
        }

        // Get owner details from vehicle API
        $owner_details = $this->get_owner_details($reg_number);
        if (empty($owner_details)) {
            return;
        }

        $customer_phone = $order->get_billing_phone();
        if (empty($customer_phone)) {
            return;
        }

        // Format message with new template
        $order_number = $order->get_order_number();
        $message = "Beepi.no - Takk for kjøpet!\n\nEier av {$reg_number}:\n{$owner_details['name']}\n{$owner_details['address']}\n\nOrdre #{$order_number}\nSe fullstendig rapport på: beepi.no";

        // Send SMS and track status
        $sms_result = $this->send_sms($customer_phone, $message);

        // Store SMS status in order meta and add order notes
        if ($sms_result) {
            $order->update_meta_data('_sms_notification_status', 'sent');
            $order->update_meta_data('_sms_sent_time', current_time('mysql'));
            
            // Add order note for successful SMS
            $order->add_order_note(
                sprintf(
                    'SMS notification sent successfully to %s. Vehicle owner information for %s delivered.',
                    $this->mask_phone_number($customer_phone),
                    $reg_number
                ),
                false // Set to true if you want customer to see this note
            );
            
            error_log('SMS Handler: SMS notification sent successfully for order ' . $order_id);
        } else {
            $order->update_meta_data('_sms_notification_status', 'failed');
            $order->update_meta_data('_sms_failure_reason', 'Twilio API failed');
            
            // Add order note for failed SMS
            $order->add_order_note(
                sprintf(
                    'SMS notification failed for phone %s. Vehicle lookup for %s completed but SMS delivery unsuccessful.',
                    $this->mask_phone_number($customer_phone),
                    $reg_number
                ),
                false // Set to true if you want customer to see this note
            );
            
            error_log('SMS Handler: SMS notification failed for order ' . $order_id);
        }
        $order->save();
    }

    private function get_owner_details($reg_number) {
        // Call the same API endpoint used by the frontend
        $response = wp_remote_post(VEHICLE_LOOKUP_WORKER_URL, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Origin' => get_site_url()
            ),
            'body' => json_encode(array(
                'registrationNumber' => $reg_number
            )),
            'timeout' => 15
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !isset($data['responser'][0]['kjoretoydata']['eierskap']['eier'])) {
            error_log('SMS Handler: Invalid API response structure');
            return false;
        }

        $eier = $data['responser'][0]['kjoretoydata']['eierskap']['eier'];
        $person = $eier['person'] ?? null;
        $adresse = $eier['adresse'] ?? null;

        if (!$person || !$adresse) {
            return false;
        }

        $name = trim($person['fornavn'] . ' ' . $person['etternavn']);
        $address_parts = array_filter([
            $adresse['adresselinje1'] ?? '',
            $adresse['postnummer'] ?? '',
            $adresse['poststed'] ?? ''
        ]);
        $address = implode(', ', $address_parts);

        return array(
            'name' => $name,
            'address' => $address
        );
    }

    private function send_sms($phone, $message) {
        // Get Twilio credentials from wp-config
        $twilio_sid = defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : null;
        $twilio_token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : null;
        $twilio_from = defined('TWILIO_FROM_NUMBER') ? TWILIO_FROM_NUMBER : null;

        if (empty($twilio_sid) || empty($twilio_token) || empty($twilio_from)) {
            error_log('SMS Handler: Twilio credentials not configured in wp-config.php');
            do_action('beepi_sms_sent_failed', $phone, $message);
            return false;
        }

        // Format phone number to international format
        $formatted_phone = $this->format_phone_number($phone);
        error_log('SMS Handler: Sending SMS to ' . $formatted_phone);

        // Prepare Twilio API request
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilio_sid}/Messages.json";

        $data = array(
            'From' => $twilio_from,
            'To' => $formatted_phone,
            'Body' => $message
        );

        $headers = array(
            'Authorization' => 'Basic ' . base64_encode($twilio_sid . ':' . $twilio_token),
            'Content-Type' => 'application/x-www-form-urlencoded'
        );

        // Send request to Twilio
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => http_build_query($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('SMS Handler: Twilio API error - ' . $response->get_error_message());
            do_action('beepi_sms_sent_failed', $formatted_phone, $message);
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code >= 200 && $response_code < 300) {
            $result = json_decode($response_body, true);
            error_log('SMS Handler: SMS sent successfully - SID: ' . ($result['sid'] ?? 'unknown'));
            do_action('beepi_sms_sent_success', $formatted_phone, $message, $result);
            return true;
        } else {
            error_log('SMS Handler: Twilio API failed - Code: ' . $response_code . ', Body: ' . $response_body);
            do_action('beepi_sms_sent_failed', $formatted_phone, $message);
            return false;
        }
    }

    private function validate_order_has_lookup($order) {
        $lookup_product_id = 62;
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $lookup_product_id) {
                return true;
            }
        }
        return false;
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

        error_log('SMS Handler: Original phone: ' . $phone . ', Cleaned: ' . $clean);

        // If already in correct Norwegian format, return as-is
        if (preg_match('/^\+47\d{8}$/', $clean)) {
            error_log('SMS Handler: Phone already in correct format: ' . $clean);
            return $clean;
        }

        // Handle different input formats
        $digits_only = $clean;
        
        // Remove +47 prefix if present
        if (strpos($digits_only, '+47') === 0) {
            $digits_only = substr($digits_only, 3);
        }
        // Remove 47 prefix if present and total length suggests it's Norwegian
        elseif (strpos($digits_only, '47') === 0 && strlen($digits_only) === 10) {
            $digits_only = substr($digits_only, 2);
        }
        // Remove + and any other country codes, but be more specific
        elseif (strpos($digits_only, '+') === 0) {
            // Remove + and any non-Norwegian country codes
            $digits_only = preg_replace('/^\+(?!47)\d{1,3}/', '', $digits_only);
            $digits_only = ltrim($digits_only, '+');
        }

        // Remove leading zeros
        $digits_only = ltrim($digits_only, '0');

        error_log('SMS Handler: Extracted digits: ' . $digits_only);

        // Ensure we have exactly 8 digits for Norwegian mobile
        if (strlen($digits_only) === 8 && preg_match('/^[4-9]\d{7}$/', $digits_only)) {
            $formatted = '+47' . $digits_only;
            error_log('SMS Handler: Final formatted number: ' . $formatted);
            return $formatted;
        }

        // If not valid Norwegian mobile format, log error and return original
        error_log('SMS Handler: Invalid Norwegian phone number format: ' . $phone . ' (extracted: ' . $digits_only . ', length: ' . strlen($digits_only) . ')');
        return $phone;
    }

    /**
     * Log successful SMS sends
     */
    public function log_sms_success($phone, $message, $result) {
        error_log("Beepi SMS Success: Sent to {$phone} - SID: " . ($result['sid'] ?? 'unknown'));
        
        // You could add additional order note here if needed
        // This hook is called after the main SMS handling
    }

    /**
     * Log failed SMS sends
     */
    public function log_sms_failure($phone, $message) {
        error_log("Beepi SMS Failure: Failed to send to {$phone}");
        
        // You could add additional order note here if needed
        // This hook is called after the main SMS handling
    }

    /**
     * Mask phone number for privacy in order notes (show last 4 digits)
     */
    private function mask_phone_number($phone) {
        if (strlen($phone) <= 4) {
            return $phone;
        }
        return str_repeat('*', strlen($phone) - 4) . substr($phone, -4);
    }
}