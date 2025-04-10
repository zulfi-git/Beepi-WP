
<?php
class Vehicle_Lookup_WooCommerce {
    public function init() {
        // Save registration number as order meta
        add_action('woocommerce_checkout_create_order', array($this, 'save_reg_number_to_order'), 10, 2);
        
        // Add registration number to thank you page
        add_action('woocommerce_thankyou', array($this, 'display_reg_number_on_thankyou'), 10, 1);
        
        // Generate access token after successful payment
        add_action('woocommerce_payment_complete', array($this, 'generate_owner_access_token'), 10, 1);
    }

    public function save_reg_number_to_order($order, $data) {
        if (isset($_GET['reg_number'])) {
            $order->update_meta_data('_vehicle_reg_number', sanitize_text_field($_GET['reg_number']));
        }
    }

    public function display_reg_number_on_thankyou($order_id) {
        $order = wc_get_order($order_id);
        $reg_number = $order->get_meta('_vehicle_reg_number');
        
        if ($reg_number) {
            echo '<p class="woocommerce-notice">Registration number: ' . esc_html($reg_number) . '</p>';
        }
    }

    public function generate_owner_access_token($order_id) {
        $order = wc_get_order($order_id);
        $reg_number = $order->get_meta('_vehicle_reg_number');
        
        if ($reg_number) {
            $token = array(
                'reg_number' => $reg_number,
                'expiry' => time() + (24 * 60 * 60), // 24 hours
                'order_id' => $order_id
            );
            
            // Store token in WordPress transient
            set_transient('vehicle_owner_access_' . $reg_number, $token, 24 * HOUR_IN_SECONDS);
        }
    }
}
?>
