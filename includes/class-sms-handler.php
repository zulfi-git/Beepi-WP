
<?php
class SMS_Handler {
    public function init() {
        add_action('woocommerce_payment_complete', array($this, 'send_owner_notification'), 20);
    }

    public function send_owner_notification($order_id) {
        $order = wc_get_order($order_id);
        $reg_number = $order->get_meta('reg_number');
        
        if (empty($reg_number) || !$this->validate_order_has_lookup($order)) {
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
        
        $this->send_sms($customer_phone, $message);
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

        if (empty($data) || !isset($data['eierskap']['eier'])) {
            return false;
        }

        $eier = $data['eierskap']['eier'];
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
            wp_sms_send($phone, $message);
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
?>
