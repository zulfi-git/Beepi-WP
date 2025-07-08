<?php
class Vehicle_Lookup {
    private $db_handler;

    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize database handler
        $this->db_handler = new Vehicle_Lookup_Database();
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Initialize shortcodes
        $shortcode = new Vehicle_Lookup_Shortcode();
        $shortcode->init();

        $search_shortcode = new Vehicle_Search_Shortcode();
        $search_shortcode->init();

        // Register AJAX handlers
        add_action('wp_ajax_vehicle_lookup', array($this, 'handle_lookup'));
        add_action('wp_ajax_nopriv_vehicle_lookup', array($this, 'handle_lookup'));

        // WooCommerce hooks
        add_action('woocommerce_checkout_create_order', array($this, 'save_registration_to_order'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'update_order_meta'));

        // Add rewrite rules for /sok/ URLs
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Database cleanup hook
        add_action('wp_scheduled_delete', array($this, 'cleanup_old_logs'));
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

        // Format and validate phone number when saving - try multiple sources
        $billing_phone = '';

        error_log("Vehicle Lookup: Starting phone extraction for order during creation");
        error_log("Vehicle Lookup: Available data keys: " . implode(', ', array_keys($data)));

        // First try the data array
        if (!empty($data['billing_phone'])) {
            $billing_phone = $data['billing_phone'];
            error_log("Vehicle Lookup: Phone found in data array: {$billing_phone}");
        }
        // Then try getting it from the order object (for Vipps Express)
        elseif (method_exists($order, 'get_billing_phone')) {
            $billing_phone = $order->get_billing_phone();
            if (!empty($billing_phone)) {
                error_log("Vehicle Lookup: Phone found via order->get_billing_phone(): {$billing_phone}");
            } else {
                error_log("Vehicle Lookup: order->get_billing_phone() returned empty");
            }
        }

        if (!empty($billing_phone)) {
            $formatted_phone = $this->format_phone_number($billing_phone);
            $order->update_meta_data('formatted_billing_phone', $formatted_phone);

            // Log for debugging
            error_log("Vehicle Lookup: INITIAL SUCCESS - Original phone: {$billing_phone}, Formatted: {$formatted_phone}");
        } else {
            error_log("Vehicle Lookup: INITIAL FAILED - No phone number found during order creation, fallback will be needed");
        }
    }

    /**
     * Format phone number to international Norwegian format (+47xxxxxxxx)
     */
    private function format_phone_number($phone) {
        // Handle array input (WooCommerce sometimes returns arrays)
        if (is_array($phone)) {
            $phone = reset($phone);
        }

        // Convert to string and remove spaces/special chars except +
        $phone = (string)$phone;
        $clean = preg_replace('/[^\d+]/', '', $phone);

        // If already in correct Norwegian format, return as-is
        if (preg_match('/^\+47\d{8}$/', $clean)) {
            return $clean;
        }

        // Handle different input formats
        $digits_only = $clean;

        // Remove +47 prefix if present
        if (strpos($digits_only, '+47') === 0) {
            $digits_only = substr($digits_only, 3);
        }
        // Remove + and any other country codes first
        elseif (strpos($digits_only, '+') === 0) {
            // Remove + and any non-Norwegian country codes
            $digits_only = preg_replace('/^\+(?!47)\d{1,3}/', '', $digits_only);
            $digits_only = ltrim($digits_only, '+');
        }

        // Remove leading zeros
        $digits_only = ltrim($digits_only, '0');

        // Check if we have exactly 8 digits and it's a valid Norwegian mobile number
        if (strlen($digits_only) === 8 && preg_match('/^[4-9]\d{7}$/', $digits_only)) {
            error_log('Vehicle Lookup: Formatting 8-digit number: ' . $digits_only . ' to +47' . $digits_only);
            return '+47' . $digits_only;
        }

        // Check if we have 10 digits starting with 47 (Norwegian country code + 8 digit mobile)
        if (strlen($digits_only) === 10 && strpos($digits_only, '47') === 0) {
            $mobile_part = substr($digits_only, 2);
            if (preg_match('/^[4-9]\d{7}$/', $mobile_part)) {
                error_log('Vehicle Lookup: Formatting 10-digit number with 47 prefix: ' . $digits_only . ' to +47' . $mobile_part);
                return '+47' . $mobile_part;
            }
        }

        // If not valid Norwegian mobile format, try to format as international
        if (strlen($digits_only) >= 8) {
            error_log('Vehicle Lookup: Formatting as international number: +' . $digits_only);
            return '+' . $digits_only;
        }

        // Last resort - return original
        error_log('Vehicle Lookup: Could not format phone number: ' . $phone . ' (extracted: ' . $digits_only . ', length: ' . strlen($digits_only) . ')');
        return $phone;
    }

    public function update_order_meta($order_id) {
        // This method is now redundant since we save during order creation
        return;
    }

    /**
     * Add custom rewrite rules for vehicle lookup URLs
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^sok/([^/]+)/?$',
            'index.php?pagename=sok&reg_number=$matches[1]',
            'top'
        );

        // Always flush rewrite rules when this plugin is activated
        // This ensures the rules are properly registered
        flush_rewrite_rules();
    }

    /**
     * Add custom query variables
     */
    public function add_query_vars($vars) {
        $vars[] = 'reg_number';
        return $vars;
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
        $ip_address = $this->get_client_ip();
        $start_time = microtime(true);

        if (empty($regNumber)) {
            $this->db_handler->log_lookup($regNumber, $ip_address, false, 'Empty registration number');
            wp_send_json_error('Du må skrive inn et registreringsnummer. F.eks: AB12345 eller EL12345');
        }

        if (!$this->validate_registration_number($regNumber)) {
            $this->db_handler->log_lookup($regNumber, $ip_address, false, 'Invalid registration number format', null, false, 'validation_error');
            wp_send_json_error('Registreringsnummeret "' . strtoupper($regNumber) . '" har feil format. Norske bilskilt følger mønster som AB12345, EL12345 eller CD12345. Prøv uten mellomrom og bindestrek.');
        }

        // Check rate limiting (before quota check)
        if (!$this->check_rate_limit()) {
            $rate_status = $this->get_rate_limit_status();
            $this->db_handler->log_lookup($regNumber, $ip_address, false, 'Rate limit exceeded', null, false, 'rate_limit');
            wp_send_json_error('Du har brukt opp dine ' . $rate_status['limit'] . ' oppslag denne timen. Prøv igjen etter kl. ' . date('H:59', strtotime('+1 hour')) . '.');
        }

        // Check daily quota
        if (!$this->check_quota_available()) {
            $quota_status = $this->get_quota_status();
            $this->db_handler->log_lookup($regNumber, $ip_address, false, 'Daily quota exceeded', null, false, 'quota_exceeded');
            wp_send_json_error('Daglig grense på ' . number_format($quota_status['limit']) . ' oppslag er nådd. Tjenesten tilbakestilles i morgen kl. 00:00.');
        }

        // Check cache first
        $cached_data = $this->get_cached_response($regNumber);
        if ($cached_data !== false) {
            $response_time = round((microtime(true) - $start_time) * 1000);
            $this->db_handler->log_lookup($regNumber, $ip_address, true, null, $response_time, true);
            wp_send_json_success($cached_data);
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

        $response_time = round((microtime(true) - $start_time) * 1000);
        $result = $this->process_api_response($response, $regNumber);
        
        if (isset($result['error'])) {
            // This is an API error (like KJENNEMERKE_UKJENT) - log as failed lookup
            $failure_type = isset($result['failure_type']) ? $result['failure_type'] : 'unknown';
            $this->db_handler->log_lookup($regNumber, $ip_address, false, $result['error'], $response_time, false, $failure_type);
            wp_send_json_error($result['error']);
        }

        $data = $result['data'];

        // Cache successful response
        $this->cache_response($regNumber, $data);

        // Log successful lookup (HTTP 200 with valid vehicle data)
        $this->db_handler->log_lookup($regNumber, $ip_address, true, null, $response_time);

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
        $current_hour = date('Y-m-d H');
        $current_count = $this->db_handler->get_hourly_rate_limit($ip_address, $current_hour);

        return $current_count < VEHICLE_LOOKUP_RATE_LIMIT;
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
        $current_hour = date('Y-m-d H');
        $current_count = $this->db_handler->get_hourly_rate_limit($ip_address, $current_hour);
        $rate_limit = get_option('vehicle_lookup_rate_limit', VEHICLE_LOOKUP_RATE_LIMIT);

        return array(
            'used' => $current_count,
            'limit' => $rate_limit,
            'remaining' => $rate_limit - $current_count,
            'resets_at' => date('Y-m-d H:59:59')
        );
    }

    /**
     * Cleanup old logs
     */
    public function cleanup_old_logs() {
        $retention_days = get_option('vehicle_lookup_log_retention', 90);
        $this->db_handler->cleanup_old_logs($retention_days);
    }

    private function check_quota_available() {
        $today = date('Y-m-d');
        $current_count = $this->db_handler->get_daily_quota($today);
        $quota_limit = get_option('vehicle_lookup_daily_quota', 5000);

        return $current_count < $quota_limit;
    }

    public function get_quota_status() {
        $today = date('Y-m-d');
        $current_count = $this->db_handler->get_daily_quota($today);
        $quota_limit = get_option('vehicle_lookup_daily_quota', 5000);

        return array(
            'used' => $current_count,
            'limit' => $quota_limit,
            'remaining' => $quota_limit - $current_count
        );
    }

    

    /**
     * Validate Norwegian registration number format
     */
    private function validate_registration_number($regNumber) {
        return Vehicle_Lookup_Helpers::validate_registration_number($regNumber);
    }

    /**
     * Handle API response with proper error checking
     */
    private function process_api_response($response, $regNumber) {
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if (strpos($error_message, 'timeout') !== false) {
                return array('error' => 'Forespørselen tok for lang tid. Sjekk internetttilkoblingen og prøv igjen.', 'failure_type' => 'connection_error');
            }
            return array('error' => 'Kunne ikke koble til kjøretøyregisteret. Sjekk internetttilkoblingen og prøv igjen om et øyeblikk.', 'failure_type' => 'connection_error');
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 429) {
            return array('error' => 'For mange forespørsler. Vent 1-2 minutter før du prøver igjen.', 'failure_type' => 'rate_limit');
        } elseif ($status_code === 503) {
            return array('error' => 'Kjøretøyregisteret er midlertidig utilgjengelig. Prøv igjen om 5-10 minutter.', 'failure_type' => 'http_error');
        } elseif ($status_code !== 200) {
            return array('error' => 'Kjøretøyregisteret svarer ikke som forventet (feilkode: ' . $status_code . '). Prøv igjen senere.', 'failure_type' => 'http_error');
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON Decode Error: ' . json_last_error_msg());
            return array('error' => 'Mottok ugyldig data fra kjøretøyregisteret. Dette er vanligvis midlertidig - prøv igjen om litt.', 'failure_type' => 'http_error');
        }

        if (empty($data)) {
            error_log('Empty Data Response for: ' . $regNumber);
            return array('error' => 'Kjøretøyregisteret returnerte tomt svar. Prøv igjen eller kontakt oss hvis problemet vedvarer.', 'failure_type' => 'http_error');
        }

        // Check if API returned an error in the response data
        if (isset($data['responser']) && is_array($data['responser'])) {
            foreach ($data['responser'] as $respons) {
                if (isset($respons['feilmelding'])) {
                    $error_code = $respons['feilmelding'];
                    
                    // Map API error codes to user-friendly messages
                    switch ($error_code) {
                        case 'KJENNEMERKE_UKJENT':
                            return array('error' => 'Finner ikke kjøretøy med registreringsnummer "' . strtoupper($regNumber) . '". Sjekk at du har skrevet det riktig (f.eks. AB12345).', 'failure_type' => 'invalid_plate');
                        case 'KJENNEMERKE_UGYLDIG':
                            return array('error' => 'Registreringsnummeret "' . strtoupper($regNumber) . '" har ugyldig format. Norske skiltnummer følger formatet AB12345.', 'failure_type' => 'invalid_plate');
                        case 'TJENESTE_IKKE_TILGJENGELIG':
                            return array('error' => 'Kjøretøyregisteret er midlertidig utilgjengelig for vedlikehold. Prøv igjen om 10-15 minutter.', 'failure_type' => 'http_error');
                        case 'INGEN_TILGANG':
                            return array('error' => 'Mangler tilgang til kjøretøydata. Dette kan skyldes at kjøretøyet er sperret for oppslag.', 'failure_type' => 'access_denied');
                        default:
                            return array('error' => 'Kunne ikke hente kjøretøydata: ' . $error_code . '. Kontakt oss hvis problemet vedvarer.', 'failure_type' => 'invalid_plate');
                    }
                }
                
                // Check if we have valid vehicle data
                if (!isset($respons['kjoretoydata']) || empty($respons['kjoretoydata'])) {
                    return array('error' => 'Ingen kjøretøydata tilgjengelig for "' . strtoupper($regNumber) . '". Dette kan skyldes at kjøretøyet ikke er registrert i Norge eller er sperret for oppslag.', 'failure_type' => 'invalid_plate');
                }
            }
        }

        return array('success' => true, 'data' => $data);
    }

    /**
     * Check if order contains vehicle lookup product
     */
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