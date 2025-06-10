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
            wp_send_json_error('Vennligst skriv inn et registreringsnummer');
        }

        // Check rate limiting (before quota check)
        if (!$this->check_rate_limit()) {
            wp_send_json_error('For mange forespørsler. Vennligst vent litt før du prøver igjen.');
        }

        // Check daily quota
        if (!$this->check_quota_available()) {
            wp_send_json_error('Daglig grense nådd. Prøv igjen i morgen.');
        }

        // Check cache first
        $cached_data = $this->get_cached_response($regNumber);
        if ($cached_data !== false) {
            error_log('Vehicle Lookup: Cache hit for ' . $regNumber);
            wp_send_json_success($cached_data);
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
            wp_send_json_error('Ugyldig registreringsnummer. Eksempel: AB12345');
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
            wp_send_json_error('Tilkoblingsfeil. Prøv igjen om litt.');
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            wp_send_json_error('Tjenesten er ikke tilgjengelig for øyeblikket. Prøv igjen senere.');
        }

        $body = wp_remote_retrieve_body($response);
        
        // Basic error logging
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Vehicle Lookup Error: Invalid JSON response for ' . $regNumber);
        }
        
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON Decode Error: ' . json_last_error_msg());
            wp_send_json_error('Ugyldig svar fra server. Prøv igjen.');
        }

        if (empty($data)) {
            error_log('Empty Data Response for: ' . $regNumber);
            wp_send_json_error('Fant ingen kjøretøyinformasjon for dette registreringsnummeret');
        }

        // Cache successful response
        $this->cache_response($regNumber, $data);
        
        // Increment quota counter on successful lookup
        $this->increment_quota_counter();
        
        // Increment rate limit counter
        $this->increment_rate_limit_counter();
        
        error_log('Vehicle Lookup: API call made for ' . $regNumber);
        wp_send_json_success($data);
    }


    /**
     * Check if rate limit allows request
     */
    private function check_rate_limit() {
        // Allow administrators to bypass rate limits
        if (current_user_can('administrator')) {
            return true;
        }

        $ip_address = $this->get_client_ip();
        $rate_limit_key = 'vehicle_rate_limit_' . md5($ip_address) . '_' . date('Y-m-d-H');
        $current_count = get_transient($rate_limit_key) ?: 0;
        
        return $current_count < VEHICLE_LOOKUP_RATE_LIMIT;
    }

    /**
     * Increment rate limit counter
     */
    private function increment_rate_limit_counter() {
        $ip_address = $this->get_client_ip();
        $rate_limit_key = 'vehicle_rate_limit_' . md5($ip_address) . '_' . date('Y-m-d-H');
        $current_count = get_transient($rate_limit_key) ?: 0;
        
        set_transient($rate_limit_key, $current_count + 1, HOUR_IN_SECONDS);
        
        // Log rate limit violations
        if ($current_count >= VEHICLE_LOOKUP_RATE_LIMIT) {
            error_log('Vehicle Lookup: Rate limit exceeded for IP ' . $ip_address);
        }
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get cached response for registration number
     */
    private function get_cached_response($regNumber) {
        $cache_key = 'vehicle_cache_' . md5(strtoupper($regNumber));
        return get_transient($cache_key);
    }

    /**
     * Cache API response
     */
    private function cache_response($regNumber, $data) {
        $cache_key = 'vehicle_cache_' . md5(strtoupper($regNumber));
        set_transient($cache_key, $data, VEHICLE_LOOKUP_CACHE_DURATION);
    }

    /**
     * Get rate limit status for current IP
     */
    public function get_rate_limit_status() {
        $ip_address = $this->get_client_ip();
        $rate_limit_key = 'vehicle_rate_limit_' . md5($ip_address) . '_' . date('Y-m-d-H');
        $current_count = get_transient($rate_limit_key) ?: 0;
        
        return array(
            'used' => $current_count,
            'limit' => VEHICLE_LOOKUP_RATE_LIMIT,
            'remaining' => VEHICLE_LOOKUP_RATE_LIMIT - $current_count,
            'resets_at' => date('Y-m-d H:59:59')
        );
    }

        }

        // Cache successful response
        $this->cache_response($regNumber, $data);
        
        // Increment quota counter on successful lookup
        $this->increment_quota_counter();
        
        // Increment rate limit counter
        $this->increment_rate_limit_counter();
        
        error_log('Vehicle Lookup: API call made for ' . $regNumber);
        wp_send_json_success($data);
    }

    private function check_quota_available() {
        $today = date('Y-m-d');
        $quota_key = 'vegvesen_quota_' . $today;
        $current_count = get_transient($quota_key) ?: 0;
        
        return $current_count < 5000;
    }

    private function increment_quota_counter() {
        $today = date('Y-m-d');
        $quota_key = 'vegvesen_quota_' . $today;
        $current_count = get_transient($quota_key) ?: 0;
        
        set_transient($quota_key, $current_count + 1, DAY_IN_SECONDS);
    }

    public function get_quota_status() {
        $today = date('Y-m-d');
        $quota_key = 'vegvesen_quota_' . $today;
        $current_count = get_transient($quota_key) ?: 0;
        
        return array(
            'used' => $current_count,
            'limit' => 5000,
            'remaining' => 5000 - $current_count
        );
    }
}
