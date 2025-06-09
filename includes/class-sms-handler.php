
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

        // Get owner phone from vehicle data
        $owner_phone = $this->get_owner_phone($reg_number);
        if (empty($owner_phone)) {
            return;
        }

        $customer_phone = $order->get_billing_phone();
        $message = "Din bil ({$reg_number}) er søkt opp av tlf: {$customer_phone}. Mer info: " . get_site_url();
        
        $this->send_sms($owner_phone, $message);
    }

    private function get_owner_phone($reg_number) {
        // This would need to be implemented based on how you store/retrieve owner data
        // For now, return empty to prevent errors
        return '';
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
