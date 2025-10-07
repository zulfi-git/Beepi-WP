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
        
        // AI summary polling endpoint
        add_action('wp_ajax_vehicle_lookup_ai_poll', array($this, 'handle_ai_summary_poll'));
        add_action('wp_ajax_nopriv_vehicle_lookup_ai_poll', array($this, 'handle_ai_summary_poll'));

        // Add query vars filter (rewrite rules registered on activation)
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
        // Add Tailwind CSS via CDN
        wp_enqueue_style(
            'tailwindcss',
            'https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css',
            array(),
            '3.4.1'
        );
        
        // Enqueue modular CSS files in proper dependency order
        // 1. Variables first (defines CSS custom properties)
        wp_enqueue_style(
            'vehicle-lookup-variables',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/variables.css',
            array('tailwindcss'),
            VEHICLE_LOOKUP_VERSION . '.' . time()
        );
        
        // 2. Core component styles (depend on variables)
        wp_enqueue_style(
            'vehicle-lookup-forms',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/forms.css',
            array('vehicle-lookup-variables'),
            VEHICLE_LOOKUP_VERSION . '.' . time()
        );
        
        wp_enqueue_style(
            'vehicle-lookup-results',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/results.css',
            array('vehicle-lookup-variables'),
            VEHICLE_LOOKUP_VERSION . '.' . time()
        );
        
        // 3. Feature-specific styles
        wp_enqueue_style(
            'vehicle-lookup-ai-summary',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/ai-summary.css',
            array('vehicle-lookup-variables'),
            VEHICLE_LOOKUP_VERSION . '.' . time()
        );
        
        wp_enqueue_style(
            'vehicle-lookup-market',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/market.css',
            array('vehicle-lookup-variables'),
            VEHICLE_LOOKUP_VERSION . '.' . time()
        );
        
        // 4. Responsive and additional components (may override other styles)
        wp_enqueue_style(
            'vehicle-lookup-responsive',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/responsive.css',
            array('vehicle-lookup-variables', 'vehicle-lookup-forms', 'vehicle-lookup-results'),
            VEHICLE_LOOKUP_VERSION . '.' . time()
        );

        // Enqueue normalize-plate module first
        wp_enqueue_script(
            'normalize-plate',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/js/normalize-plate.js',
            array(),
            VEHICLE_LOOKUP_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'vehicle-lookup-script',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/js/vehicle-lookup.js',
            array('jquery', 'normalize-plate'),
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

        $regNumber = isset($_POST['regNumber']) ? Vehicle_Lookup_Helpers::normalize_plate(sanitize_text_field($_POST['regNumber'])) : '';
        $includeSummary = isset($_POST['includeSummary']) ? (bool)$_POST['includeSummary'] : false;
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

        // Check vehicle data cache (always separate from AI summaries in new system)
        $vehicle_cache_key = $regNumber;
        $cached_vehicle_data = $this->cache->get($vehicle_cache_key);
        if ($cached_vehicle_data !== false) {
            $response_time = round((microtime(true) - $start_time) * 1000);
            $this->db_handler->log_lookup($regNumber, $ip_address, true, null, $response_time, true);
            
            // Add cache metadata to response
            $cached_vehicle_data['is_cached'] = true;
            $cached_vehicle_data['cache_time'] = $this->cache->get_cache_time($vehicle_cache_key);
            
            // For the new system, always include AI status if summary was requested
            if ($includeSummary) {
                // Check if we have AI summary ready in separate cache
                $ai_cache_key = $regNumber . '_ai_summary';
                $cached_ai_summary = $this->cache->get($ai_cache_key);
                
                if ($cached_ai_summary !== false && isset($cached_ai_summary['status']) && $cached_ai_summary['status'] === 'complete') {
                    // AI summary is ready and cached
                    $cached_vehicle_data['aiSummary'] = $cached_ai_summary;
                } else {
                    // AI summary needs to be generated - trigger background generation
                    $this->trigger_ai_generation_async($regNumber);
                    
                    // Return generating status while it processes in background
                    $cached_vehicle_data['aiSummary'] = array(
                        'status' => 'generating',
                        'startedAt' => current_time('c'),
                        'progress' => null,
                        'pollUrl' => '/ai-summary/' . urlencode($regNumber)
                    );
                }
            }
            
            wp_send_json_success($cached_vehicle_data);
        }

        // Determine tier based on user's purchase status
        $tier = $this->access->determine_tier($regNumber);

        // Make API request (tier handled internally by WordPress)
        $api_result = $this->api->lookup($regNumber, $includeSummary);
        $response_time = $api_result['response_time'];
        $result = $this->api->process_response($api_result['response'], $regNumber);

        if (isset($result['error'])) {
            // This is an API error - log as failed lookup with enhanced error data
            $failure_type = isset($result['failure_type']) ? $result['failure_type'] : 'unknown';
            $error_code = isset($result['code']) ? $result['code'] : null;
            $correlation_id = isset($result['correlation_id']) ? $result['correlation_id'] : null;
            
            $this->db_handler->log_lookup(
                $regNumber, 
                $ip_address, 
                false, 
                $result['error'], 
                $response_time, 
                false, 
                $failure_type, 
                'free', 
                null, 
                $error_code, 
                $correlation_id
            );
            
            // Return structured error data to frontend
            wp_send_json_error(array(
                'message' => $result['error'],
                'code' => $error_code,
                'correlation_id' => $correlation_id,
                'retry_after' => isset($result['retry_after']) ? $result['retry_after'] : null
            ));
        }

        $data = $result['data'];

        // Cache vehicle data separately from AI summaries (new two-endpoint system)
        $vehicle_cache_key = $regNumber;
        
        // Extract and cache only vehicle data (without AI summary)
        $vehicle_data = $data;
        if (isset($vehicle_data['aiSummary'])) {
            unset($vehicle_data['aiSummary']); // Don't cache AI summary with vehicle data
        }
        $this->cache->set($vehicle_cache_key, $vehicle_data);
        
        // If response includes AI summary status, add it back for this response
        if ($includeSummary && isset($data['aiSummary'])) {
            $vehicle_data['aiSummary'] = $data['aiSummary'];
        }

        // Add cache metadata to response  
        $vehicle_data['is_cached'] = false;
        $vehicle_data['cache_time'] = current_time('c'); // ISO 8601 format

        // Log successful lookup (HTTP 200 with valid vehicle data)
        $this->db_handler->log_lookup($regNumber, $ip_address, true, null, $response_time, false, null, $tier);

        wp_send_json_success($vehicle_data);
    }

    /**
     * Handle AJAX AI summary and market listings polling requests
     */
    public function handle_ai_summary_poll() {
        check_ajax_referer('vehicle_lookup_nonce', 'nonce');

        $regNumber = isset($_POST['regNumber']) ? Vehicle_Lookup_Helpers::normalize_plate(sanitize_text_field($_POST['regNumber'])) : '';
        $ip_address = $this->access->get_client_ip();

        if (empty($regNumber)) {
            wp_send_json_error('Vennligst skriv inn et registreringsnummer');
        }

        if (!$this->api->validate_registration_number($regNumber)) {
            wp_send_json_error('Ugyldig registreringsnummer. Eksempel: AB12345');
        }

        // Check rate limiting for polling requests
        if (!$this->access->check_rate_limit()) {
            wp_send_json_error('For mange forespørsler. Vennligst vent litt før du prøver igjen.');
        }

        // Check cache for both AI summary and market listings
        $ai_cache_key = $regNumber . '_ai_summary';
        $market_cache_key = $regNumber . '_market_listings';
        
        $cached_ai_summary = $this->cache->get($ai_cache_key);
        $cached_market_listings = $this->cache->get($market_cache_key);
        
        // Prepare response data from cache only - don't trigger new API calls
        $response_data = array();
        
        // Return cached AI summary if available
        if ($cached_ai_summary !== false) {
            $response_data['aiSummary'] = $cached_ai_summary;
        } else {
            // If no cached AI summary, use the dedicated AI polling endpoint
            $api_result = $this->api->poll_ai_summary($regNumber);
            $ai_result = $this->api->process_ai_summary_response($api_result['response'], $regNumber);
            
            if (isset($ai_result['error'])) {
                // Return AI polling error but continue with market data
                $response_data['aiSummary'] = array(
                    'status' => 'error',
                    'message' => $ai_result['error']
                );
            } else {
                $response_data['aiSummary'] = $ai_result['data'];
                
                // Cache completed AI summary
                if (isset($ai_result['data']['status']) && $ai_result['data']['status'] === 'complete' && isset($ai_result['data']['summary'])) {
                    $this->cache->set($ai_cache_key, $ai_result['data'], 86400);
                }
            }
        }
        
        // Return cached market listings if available
        if ($cached_market_listings !== false) {
            $response_data['marketListings'] = $cached_market_listings;
        } else {
            // If no cached market data, check actual market listings status from API
            $market_api_result = $this->api->poll_market_listings($regNumber);
            $market_result = $this->api->process_market_listings_response($market_api_result['response'], $regNumber);
            
            if (isset($market_result['error'])) {
                // Return market polling error
                $response_data['marketListings'] = array(
                    'status' => 'error',
                    'message' => $market_result['error']
                );
            } else {
                $response_data['marketListings'] = $market_result['data'];
                
                // Cache completed market listings
                if (isset($market_result['data']['status']) && $market_result['data']['status'] === 'complete' && isset($market_result['data']['listings'])) {
                    $this->cache->set($market_cache_key, $market_result['data'], 86400);
                }
            }
        }

        wp_send_json_success($response_data);
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
     * Trigger AI generation asynchronously for cached vehicle data
     */
    private function trigger_ai_generation_async($regNumber) {
        // Check if AI generation is already in progress (avoid duplicate requests)
        $generation_key = $regNumber . '_ai_generating';
        $generation_lock = $this->cache->get($generation_key);
        
        if ($generation_lock !== false) {
            // AI generation already triggered within the last 5 minutes
            return;
        }
        
        // Set a short-term lock to prevent duplicate generation requests
        $this->cache->set($generation_key, current_time('c'), 300); // 5 minute lock
        
        // Make a non-blocking background request to trigger AI generation
        $api_result = $this->api->lookup($regNumber, true);
        
        // Process response to ensure AI generation is started
        if (!isset($api_result['error'])) {
            $result = $this->api->process_response($api_result['response'], $regNumber);
            
            // If successful and AI summary status returned, update our expectations
            if (isset($result['data']['aiSummary'])) {
                $ai_cache_key = $regNumber . '_ai_summary';
                
                // Store AI generation status in cache
                $ai_status = array(
                    'status' => $result['data']['aiSummary']['status'],
                    'startedAt' => current_time('c'),
                    'progress' => isset($result['data']['aiSummary']['progress']) ? $result['data']['aiSummary']['progress'] : null
                );
                
                // Cache the status (but don't cache incomplete summaries)
                if ($ai_status['status'] !== 'complete') {
                    $this->cache->set($ai_cache_key, $ai_status, 1800); // 30 minute temporary cache
                }
            }
        }
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