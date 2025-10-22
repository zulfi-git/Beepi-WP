<?php

/**
 * Vehicle Lookup Admin AJAX Class
 * 
 * Handles all AJAX requests for admin functionality.
 * This class manages:
 * - API connectivity testing
 * - Upstream health checks
 * - Analytics data reset
 * - Security validation for all requests
 */
class Vehicle_Lookup_Admin_Ajax {

    /**
     * Register all AJAX handlers
     * Called by Vehicle_Lookup_Admin::init()
     */
    public function register_handlers() {
        add_action('wp_ajax_vehicle_lookup_test_api', array($this, 'test_api_connectivity'));
        add_action('wp_ajax_vehicle_lookup_check_upstream', array($this, 'check_upstream_health'));
        add_action('wp_ajax_vehicle_lookup_check_chatkit', array($this, 'check_chatkit_health'));
        add_action('wp_ajax_reset_analytics_data', array($this, 'reset_analytics_data'));
    }

    /**
     * Test API connectivity to worker
     */
    public function test_api_connectivity() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);

        $response = wp_remote_get($worker_url . '/health', array(
            'headers' => array(
                'Origin' => get_site_url()
            ),
            'timeout' => $timeout
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);

        if ($status_code === 200) {
            wp_send_json_success(json_decode($raw_body, true));
        } else {
            wp_send_json_error('Health check failed with status: ' . $status_code);
        }
    }

    /**
     * Check upstream health with intelligent caching
     */
    public function check_upstream_health() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        // Cache key for health check results
        $cache_key = 'vehicle_lookup_health_check';
        $cache_ttl = 420; // 7 minutes (420 seconds) - middle of recommended 5-10 minutes

        // Check for cached results first
        $cached_result = get_transient($cache_key);
        if ($cached_result !== false) {
            // Return cached results with indicator
            $cached_result['message'] = 'Health check completed (cached).';
            $cached_result['cached'] = true;
            $cached_result['cache_expires_in'] = get_option('_transient_timeout_' . $cache_key) - time();
            wp_send_json_success($cached_result);
            return;
        }

        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);

        // Use GET method as documented by Cloudflare team
        $response = wp_remote_get($worker_url . '/health', array(
            'headers' => array(
                'Origin' => get_site_url()
            ),
            'timeout' => $timeout
        ));

        if (is_wp_error($response)) {
            // Don't cache error responses
            wp_send_json_error(array(
                'message' => 'Health check failed: ' . $response->get_error_message(),
                'status' => 'unknown',
                'cached' => false
            ));
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200 && isset($body['status'])) {
            // Extract comprehensive monitoring data from new structure
            $monitoring_data = array();
            
            // Rate limiting information
            if (isset($body['rateLimiting'])) {
                $rl = $body['rateLimiting'];
                $monitoring_data['rate_limiting'] = array(
                    'daily_usage' => $rl['globalDailyUsage'] ?? 0,
                    'daily_limit' => $rl['globalDailyLimit'] ?? 4500,
                    'daily_remaining' => $rl['globalDailyRemaining'] ?? 0,
                    'vegvesen_quota' => $rl['vegvesenQuotaUsage'] ?? '0/5000',
                    'quota_utilization' => $rl['quotaUtilization'] ?? '0%',
                    'active_ips_hourly' => $rl['activeIPsTracked']['hourly'] ?? 0,
                    'active_ips_burst' => $rl['activeIPsTracked']['burst'] ?? 0,
                    // Enhanced tracking for two-endpoint system
                    'vehicle_endpoint_usage' => $rl['vehicleEndpointUsage'] ?? 0,
                    'ai_endpoint_usage' => $rl['aiEndpointUsage'] ?? 0,
                    'ai_generation_rate' => $rl['aiGenerationRate'] ?? 0
                );
            }
            
            // Cache information with enhanced AI summary tracking
            if (isset($body['cache'])) {
                $cache = $body['cache'];
                $monitoring_data['cache'] = array(
                    'entries' => $cache['entries'] ?? 0,
                    'max_size' => $cache['maxSize'] ?? 1000,
                    'ttl' => $cache['ttl'] ?? 3600,
                    'utilization' => isset($cache['entries'], $cache['maxSize']) ? 
                        round(($cache['entries'] / $cache['maxSize']) * 100, 1) : 0,
                    // Separate cache metrics for two-endpoint system
                    'vehicle_cache_entries' => $cache['vehicleCacheEntries'] ?? 0,
                    'ai_cache_entries' => $cache['aiCacheEntries'] ?? 0,
                    'vehicle_hit_rate' => $cache['vehicleHitRate'] ?? '0%',
                    'ai_hit_rate' => $cache['aiHitRate'] ?? '0%',
                    'ai_cache_ttl' => $cache['aiCacheTtl'] ?? 86400  // 24 hours
                );
            }
            
            // AI Summary Service Status
            if (isset($body['aiSummary'])) {
                $ai = $body['aiSummary'];
                $monitoring_data['ai_summary'] = array(
                    'status' => $ai['status'] ?? 'unknown',
                    'model' => $ai['model'] ?? 'gpt-4o-mini',
                    'timeout' => $ai['timeout'] ?? 25000,
                    'active_generations' => $ai['activeGenerations'] ?? 0,
                    'completed_today' => $ai['completedToday'] ?? 0,
                    'failed_today' => $ai['failedToday'] ?? 0,
                    'avg_generation_time' => $ai['avgGenerationTime'] ?? 0,
                    'generation_success_rate' => $ai['generationSuccessRate'] ?? '100%',
                    'cache_utilization' => $ai['cacheUtilization'] ?? '0%'
                );
            }
            
            // Circuit breaker status
            if (isset($body['circuitBreaker'])) {
                $cb = $body['circuitBreaker'];
                $monitoring_data['circuit_breaker'] = array(
                    'state' => $cb['state'] ?? 'CLOSED',
                    'failure_count' => $cb['failureCount'] ?? 0,
                    'success_rate' => $cb['successRate'] ?? '100%',
                    'total_requests' => $cb['totalRequests'] ?? 0,
                    'last_failure' => $cb['lastFailure'],
                    // Enhanced circuit breaker for two-endpoint system
                    'vehicle_circuit_state' => $cb['vehicleCircuitState'] ?? 'CLOSED',
                    'ai_circuit_state' => $cb['aiCircuitState'] ?? 'CLOSED'
                );
            }
            
            // Two-endpoint system performance metrics
            if (isset($body['performance'])) {
                $perf = $body['performance'];
                $monitoring_data['performance'] = array(
                    'vehicle_avg_latency' => $perf['vehicleAvgLatency'] ?? 0,
                    'ai_avg_latency' => $perf['aiAvgLatency'] ?? 0,
                    'cache_hit_improvement' => $perf['cacheHitImprovement'] ?? '0%',
                    'timeout_elimination' => $perf['timeoutElimination'] ?? true
                );
            }

            $result_data = array(
                'message' => 'Health check completed.',
                'health_data' => $body,
                'monitoring_data' => $monitoring_data,
                'status' => $body['status'],
                'correlation_id' => $body['correlationId'] ?? null,
                'service_version' => $body['version'] ?? 'unknown',
                'cached' => false,
                'cache_ttl' => $cache_ttl
            );

            // Cache the successful result
            set_transient($cache_key, $result_data, $cache_ttl);

            wp_send_json_success($result_data);
        } else {
            // Don't cache failed health checks
            wp_send_json_error(array(
                'message' => 'Health check failed with status code: ' . $status_code,
                'health_data' => $body ?: array('status' => 'unknown'),
                'status' => isset($body['status']) ? $body['status'] : 'unknown',
                'cached' => false
            ));
        }
    }

    /**
     * Check chatkit health with intelligent caching
     */
    public function check_chatkit_health() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        // Cache key for chatkit health check results
        $cache_key = 'vehicle_lookup_chatkit_health_check';
        $cache_ttl = 420; // 7 minutes (420 seconds) - same as main health check

        // Check for cached results first
        $cached_result = get_transient($cache_key);
        if ($cached_result !== false) {
            // Return cached results with indicator
            $cached_result['message'] = 'Chatkit health check completed (cached).';
            $cached_result['cached'] = true;
            $cached_result['cache_expires_in'] = get_option('_transient_timeout_' . $cache_key) - time();
            wp_send_json_success($cached_result);
            return;
        }

        $chatkit_url = 'https://chatkit.beepi.no';
        $timeout = get_option('vehicle_lookup_timeout', 15);

        // Use GET method as per API specification
        $response = wp_remote_get($chatkit_url . '/api/health', array(
            'headers' => array(
                'Origin' => get_site_url()
            ),
            'timeout' => $timeout
        ));

        if (is_wp_error($response)) {
            // Don't cache error responses
            wp_send_json_error(array(
                'message' => 'Chatkit health check failed: ' . $response->get_error_message(),
                'status' => 'unknown',
                'cached' => false
            ));
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200 && isset($body['status'])) {
            $result_data = array(
                'message' => 'Chatkit health check completed.',
                'status' => $body['status'],
                'version' => $body['version'] ?? 'unknown',
                'cached' => false,
                'cache_ttl' => $cache_ttl
            );

            // Cache the successful result
            set_transient($cache_key, $result_data, $cache_ttl);

            wp_send_json_success($result_data);
        } else {
            // Don't cache failed health checks
            wp_send_json_error(array(
                'message' => 'Chatkit health check failed with status code: ' . $status_code,
                'status' => isset($body['status']) ? $body['status'] : 'unknown',
                'cached' => false
            ));
        }
    }

    /**
     * Reset all analytics data
     */
    public function reset_analytics_data() {
        // Log the incoming request for debugging
        error_log('Reset analytics request received. POST data: ' . print_r($_POST, true));

        // Check if user has permissions first
        if (!current_user_can('manage_options')) {
            error_log('Reset analytics failed: Insufficient permissions');
            wp_send_json_error(array(
                'message' => 'Insufficient permissions'
            ));
            return;
        }

        // Check nonce with better error handling
        if (!isset($_POST['nonce'])) {
            error_log('Reset analytics failed: No nonce provided');
            wp_send_json_error(array(
                'message' => 'Security token missing'
            ));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'vehicle_lookup_admin_nonce')) {
            error_log('Reset analytics failed: Invalid nonce. Expected: vehicle_lookup_admin_nonce, Received nonce: ' . $_POST['nonce']);
            wp_send_json_error(array(
                'message' => 'Security check failed'
            ));
            return;
        }

        global $wpdb;

        if (!$wpdb) {
            error_log('Reset analytics failed: Database connection not available');
            wp_send_json_error(array(
                'message' => 'Database connection not available'
            ));
            return;
        }

        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        // Check if table exists first
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;

        if (!$table_exists) {
            error_log('Reset analytics failed: Analytics table does not exist');
            wp_send_json_error(array(
                'message' => 'Analytics table does not exist'
            ));
            return;
        }

        // Get count before deletion for verification
        $count_before = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");

        // Use DELETE instead of TRUNCATE for better compatibility
        $result = $wpdb->query("DELETE FROM `{$table_name}`");

        if ($result !== false) {
            // Verify deletion worked
            $count_after = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");

            // Clear any cached data
            wp_cache_delete('vehicle_lookup_stats_*', 'vehicle_lookup');

            // Log success
            error_log("Successfully reset analytics data. Deleted {$count_before} records.");

            wp_send_json_success(array(
                'message' => "Successfully deleted {$count_before} records"
            ));
        } else {
            // Log the database error
            error_log('Reset analytics failed: Failed to delete data. Database error: ' . $wpdb->last_error);
            wp_send_json_error(array(
                'message' => 'Failed to reset analytics data. Database error: ' . $wpdb->last_error
            ));
        }
    }
}
