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
                'nonce' => wp_create_nonce('vehicle_lookup_nonce')
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
        
        // Get logging preference from request
        $enable_logging = isset($_POST['enable_logging']) ? filter_var($_POST['enable_logging'], FILTER_VALIDATE_BOOLEAN) : false;
        
        if ($enable_logging) {
            error_log('Vehicle Lookup Request for: ' . $regNumber);
            error_log('Response Status Code: ' . $status_code);
            error_log('Response Headers: ' . print_r(wp_remote_retrieve_headers($response), true));
            error_log('Response Body: ' . $body);
        }
        
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($enable_logging) {
                error_log('JSON Decode Error: ' . json_last_error_msg());
            }
            wp_send_json_error('Invalid JSON response from server');
        }

        if (empty($data)) {
            if ($enable_logging) {
                error_log('Empty Data Response for: ' . $regNumber);
            }
            wp_send_json_error('No vehicle information found for this registration number');
        }

        wp_send_json_success($data);
    }
}
