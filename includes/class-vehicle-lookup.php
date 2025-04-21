<?php
class Vehicle_Lookup {
    /**
     * Initialize the plugin
     */
    public function init() {
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Initialize shortcode
        $shortcode = new Vehicle_Lookup_Shortcode();
        $shortcode->init();
        
        // Register AJAX handlers
        add_action('wp_ajax_vehicle_lookup', array($this, 'handle_lookup'));
        add_action('wp_ajax_nopriv_vehicle_lookup', array($this, 'handle_lookup'));
        
        // WooCommerce hooks
        add_action('woocommerce_checkout_create_order', array($this, 'save_registration_to_order'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'update_order_meta'));
    }

    private function get_registration_number() {
        if (isset($_COOKIE['vehicle_reg_number'])) {
            return sanitize_text_field($_COOKIE['vehicle_reg_number']);
        }
        return false;
    }

    public function save_registration_to_order($order, $data) {
        if ($reg_number = $this->get_registration_number()) {
            $order->update_meta_data('reg_number', $reg_number);
        }
    }

    public function update_order_meta($order_id) {
        // This method is now redundant since we save during order creation
        return;
    }

    /**
     * Enqueue required scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'vehicle-lookup-style',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/vehicle-lookup.css',
            array(),
            VEHICLE_LOOKUP_VERSION . '.' . time()
        );

        wp_enqueue_script(
            'vehicle-lookup-script',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/js/vehicle-lookup.js',
            array('jquery'),
            VEHICLE_LOOKUP_VERSION . '.' . time(),
            true
        );

        wp_localize_script(
            'vehicle-lookup-script',
            'vehicleLookupAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vehicle_lookup_nonce'),
                'plugin_url' => plugins_url('', dirname(__FILE__))
            )
        );
    }

    /**
     * Handle AJAX lookup requests
     */
    public function handle_lookup() {
        check_ajax_referer('vehicle_lookup_nonce', 'nonce');

        $regNumber = isset($_POST['regNumber']) ? sanitize_text_field($_POST['regNumber']) : '';
        
        if (empty($regNumber)) {
            wp_send_json_error('Registration number is required');
        }

        $valid_patterns = array(
            '/^[A-Za-z]{2}\d{4,5}$/',         // Standard vehicles and others
            '/^[Ee][KkLlVvBbCcDdEe]\d{5}$/',  // Electric vehicles
            '/^[Cc][Dd]\d{5}$/',              // Diplomatic vehicles
            '/^\d{5}$/',                      // Temporary tourist plates
            '/^[A-Za-z]\d{3}$/',              // Antique vehicles
            '/^[A-Za-z]{2}\d{3}$/'            // Provisional plates
        );
        
        $is_valid = false;
        foreach ($valid_patterns as $pattern) {
            if (preg_match($pattern, $regNumber)) {
                $is_valid = true;
                break;
            }
        }
        
        if (!$is_valid) {
            wp_send_json_error('Invalid registration number format');
        }

        $response = wp_remote_post(VEHICLE_LOOKUP_WORKER_URL, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Origin' => get_site_url()
            ),
            'body' => json_encode(array(
                'registrationNumber' => $regNumber
            )),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            wp_send_json_error('Connection error: ' . $error_message);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            wp_send_json_error('Server returned error code: ' . $status_code);
        }

        $body = wp_remote_retrieve_body($response);
        
        // Enhanced debug logging for vehicle lookup
        error_log("\n=== Vehicle Lookup Debug Info ===");
        error_log('Request Details:');
        error_log('- Registration Number: ' . $regNumber);
        error_log('- Request Time: ' . date('Y-m-d H:i:s'));
        error_log('- Origin URL: ' . get_site_url());
        
        error_log('\nResponse Details:');
        error_log('- Status Code: ' . $status_code);
        error_log('- Response Time: ' . round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 3) . 's');
        error_log('- Headers: ' . print_r(wp_remote_retrieve_headers($response), true));
        
        $decoded_body = json_decode($body, true);
        error_log('\nResponse Data:');
        error_log('- Valid JSON: ' . (json_last_error() === JSON_ERROR_NONE ? 'Yes' : 'No'));
        error_log('- Data Present: ' . (!empty($decoded_body) ? 'Yes' : 'No'));
        error_log('- Full Response: ' . print_r($decoded_body, true));
        error_log("\n==============================\n");
        
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON Decode Error: ' . json_last_error_msg());
            wp_send_json_error('Invalid JSON response from server');
        }

        if (empty($data)) {
            error_log('Empty Data Response for: ' . $regNumber);
            wp_send_json_error('No vehicle information found for this registration number');
        }

        wp_send_json_success($data);
    }
}
