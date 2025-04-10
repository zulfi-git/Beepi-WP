
<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vehicle_Lookup_WooCommerce {
    private $debug = true;

    private function log($message) {
        if ($this->debug) {
            error_log('[Vehicle Lookup WC] ' . print_r($message, true));
        }
    }

    public function init() {
        try {
            $this->log('Initializing WooCommerce integration');
            
            // Verify WooCommerce is active
            if (!class_exists('WC_Order')) {
                throw new Exception('WooCommerce core classes not found');
            }

            // Register hooks with error handling
            $this->register_hooks();
            
            $this->log('WooCommerce integration initialized successfully');
            
        } catch (Exception $e) {
            $this->log('Initialization error: ' . $e->getMessage());
            return false;
        }
    }

    private function register_hooks() {
        add_action('woocommerce_checkout_create_order', array($this, 'save_reg_number_to_order'), 10, 2);
        add_action('woocommerce_thankyou', array($this, 'display_reg_number_on_thankyou'), 10, 1);
        add_action('woocommerce_payment_complete', array($this, 'generate_owner_access_token'), 10, 1);
    }

    public function save_reg_number_to_order($order, $data) {
        try {
            if (!($order instanceof WC_Order)) {
                throw new Exception('Invalid order object');
            }

            if (isset($_GET['reg_number'])) {
                $reg_number = sanitize_text_field($_GET['reg_number']);
                $order->update_meta_data('_vehicle_reg_number', $reg_number);
                $this->log("Saved registration number: $reg_number to order: " . $order->get_id());
            }
        } catch (Exception $e) {
            $this->log('Error saving reg number: ' . $e->getMessage());
        }
    }

    public function display_reg_number_on_thankyou($order_id) {
        try {
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new Exception('Order not found');
            }

            $reg_number = $order->get_meta('_vehicle_reg_number');
            if ($reg_number) {
                echo '<p class="woocommerce-notice">Registration number: ' . esc_html($reg_number) . '</p>';
            }
        } catch (Exception $e) {
            $this->log('Error displaying reg number: ' . $e->getMessage());
        }
    }

    public function generate_owner_access_token($order_id) {
        try {
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new Exception('Order not found');
            }

            $reg_number = $order->get_meta('_vehicle_reg_number');
            if ($reg_number) {
                $token = array(
                    'reg_number' => $reg_number,
                    'expiry' => time() + (24 * 60 * 60),
                    'order_id' => $order_id
                );
                
                set_transient('vehicle_owner_access_' . $reg_number, $token, 24 * HOUR_IN_SECONDS);
                $this->log("Generated access token for registration: $reg_number");
            }
        } catch (Exception $e) {
            $this->log('Error generating access token: ' . $e->getMessage());
        }
    }
}
?>
