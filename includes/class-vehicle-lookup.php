<?php
/**
 * The core plugin class
 */
class Vehicle_Lookup {
    private $db_handler;
    private $api;
    private $cache;
    private $access;
    private $woocommerce;

    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize database handler
        $this->db_handler = new Vehicle_Lookup_Database();

        // Initialize specialized classes
        $this->api = new VehicleLookupAPI();
        $this->cache = new VehicleLookupCache();
        $this->access = new VehicleLookupAccess();
        $this->woocommerce = new VehicleLookupWooCommerce();

        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Initialize shortcodes
        $shortcode = new Vehicle_Lookup_Shortcode();
        $shortcode->init();

        $search_shortcode = new Vehicle_Search_Shortcode();
        $search_shortcode->init();

        // Initialize WooCommerce integration
        $this->woocommerce->init();

        // Register AJAX handlers
        add_action('wp_ajax_vehicle_lookup', array($this, 'handle_lookup'));
        add_action('wp_ajax_nopriv_vehicle_lookup', array($this, 'handle_lookup'));

        // Add rewrite rules for /sok/ URLs
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Database cleanup hook
        add_action('wp_scheduled_delete', array($this, 'cleanup_old_logs'));
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
        $ip_address = $this->access->get_client_ip();
        $start_time = microtime(true);

        if (empty($regNumber)) {
            $this->db_handler->log_lookup($regNumber, $ip_address, false, 'Empty registration number');
            wp_send_json_error('Vennligst skriv inn et registreringsnummer');
        }

        if (!$this->api->validate_registration_number($regNumber)) {
            $this->db_handler->log_lookup($regNumber, $ip_address, false, 'Invalid registration number format', null, false, 'validation_error');
            wp_send_json_error('Ugyldig registreringsnummer. Eksempel: AB12345');
        }

        // Check rate limiting (before quota check)
        if (!$this->access->check_rate_limit()) {
            $this->db_handler->log_lookup($regNumber, $ip_address, false, 'Rate limit exceeded', null, false, 'rate_limit');
            wp_send_json_error('For mange forespørsler. Vennligst vent litt før du prøver igjen.');
        }

        // Check daily quota
        if (!$this->access->check_quota_available()) {
            $this->db_handler->log_lookup($regNumber, $ip_address, false, 'Daily quota exceeded', null, false, 'quota_exceeded');
            wp_send_json_error('Daglig grense nådd. Prøv igjen i morgen.');
        }

        // Check cache first
        $cached_data = $this->cache->get($regNumber);
        if ($cached_data !== false) {
            $response_time = round((microtime(true) - $start_time) * 1000);
            $this->db_handler->log_lookup($regNumber, $ip_address, true, null, $response_time, true);
            
            // Add cache metadata to response
            $cached_data['is_cached'] = true;
            $cached_data['cache_time'] = $this->cache->get_cache_time($regNumber);
            
            wp_send_json_success($cached_data);
        }

        // Determine tier based on user's purchase status
        $tier = $this->access->determine_tier($regNumber);

        // Make API request
        $api_result = $this->api->lookup($regNumber, $tier);
        $response_time = $api_result['response_time'];
        $result = $this->api->process_response($api_result['response'], $regNumber);

        if (isset($result['error'])) {
            // This is an API error (like KJENNEMERKE_UKJENT) - log as failed lookup
            $failure_type = isset($result['failure_type']) ? $result['failure_type'] : 'unknown';
            $this->db_handler->log_lookup($regNumber, $ip_address, false, $result['error'], $response_time, false, $failure_type);
            wp_send_json_error($result['error']);
        }

        $data = $result['data'];

        // Cache successful response
        $this->cache->set($regNumber, $data);

        // Add cache metadata to response
        $data['is_cached'] = false;
        $data['cache_time'] = current_time('c'); // ISO 8601 format

        // Log successful lookup (HTTP 200 with valid vehicle data)
        $this->db_handler->log_lookup($regNumber, $ip_address, true, null, $response_time, false, null, $tier);

        wp_send_json_success($data);
    }

    /**
     * Get rate limit status for current IP
     */
    public function get_rate_limit_status() {
        return $this->access->get_rate_limit_status();
    }

    /**
     * Get quota status
     */
    public function get_quota_status() {
        return $this->access->get_quota_status();
    }

    /**
     * Cleanup old logs
     */
    public function cleanup_old_logs() {
        $retention_days = get_option('vehicle_lookup_log_retention', 90);
        $this->db_handler->cleanup_old_logs($retention_days);
    }

    /**
     * Validate Norwegian registration number format
     */
    private function validate_registration_number($regNumber) {
        return $this->api->validate_registration_number($regNumber);
    }

    /**
     * Format phone number to international Norwegian format (+47xxxxxxxx)
     */
    private function format_phone_number($phone) {
        return $this->woocommerce->format_phone_number($phone);
    }

    /**
     * Save registration to order during checkout
     */
    public function save_registration_to_order($order, $data) {
        $this->woocommerce->save_registration_to_order($order, $data);
    }

    /**
     * Update order meta (now handled within save_registration_to_order)
     */
    public function update_order_meta($order_id) {
        // This method is now redundant since we save during order creation
        return;
    }
}