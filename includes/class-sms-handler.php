<?php
class SMS_Handler {
    public function init() {
        add_action('woocommerce_payment_complete', array($this, 'send_owner_notification'), 20);
    }

    public function send_owner_notification($order_id) {
        $order = wc_get_order($order_id);
        
        // Get registration number using the same logic as order confirmation
        $reg_number = '';
        $reg_fields = ['custom_reg', 'reg_number', '_custom_reg', '_reg_number', 'regNumber'];
        
        foreach ($reg_fields as $field) {
            $reg_number = $order->get_meta($field);
            if (!empty($reg_number)) {
                break;
            }
        }
        
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

        // Format message with owner name and address
        $message = "Eierinformasjon for {$reg_number}: {$owner_details['name']}, {$owner_details['address']}";
        
        // Send SMS and track status
        $sms_result = $this->send_sms($customer_phone, $message);
        
        // Store SMS status in order meta
        if ($sms_result !== false) {
            $order->update_meta_data('_sms_notification_status', 'sent');
            $order->update_meta_data('_sms_sent_time', current_time('mysql'));
            error_log('SMS Handler: SMS notification marked as sent for order ' . $order_id);
        } else {
            $order->update_meta_data('_sms_notification_status', 'failed');
            $order->update_meta_data('_sms_failure_reason', 'SMS service unavailable or returned false');
            error_log('SMS Handler: SMS notification marked as failed for order ' . $order_id . ' - SMS service unavailable or returned false');
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
        // Integration with WP SMS plugin or your SMS service
        if (function_exists('wp_sms_send')) {
            error_log('SMS Handler: Attempting to send SMS to ' . $phone);
            $result = wp_sms_send($phone, $message);
            
            if ($result) {
                error_log('SMS Handler: SMS sent successfully to ' . $phone);
                return $result;
            } else {
                error_log('SMS Handler: wp_sms_send returned false for ' . $phone);
                return false;
            }
        } else {
            error_log('SMS Handler: wp_sms_send function not available - WP SMS plugin may not be installed or active');
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
}
