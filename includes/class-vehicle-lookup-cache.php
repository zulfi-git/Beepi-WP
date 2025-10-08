<?php
class VehicleLookupCache {
    
    /**
     * Get cached response for registration number
     */
    public function get($regNumber) {
        // Check if cache is enabled
        $cache_enabled = get_option('vehicle_lookup_cache_enabled', '1');
        if ($cache_enabled !== '1') {
            return false; // Cache disabled, return false to trigger fresh lookup
        }
        
        $cache_key = $this->get_cache_key($regNumber);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data && is_array($cached_data) && isset($cached_data['data'])) {
            return $cached_data['data'];
        }
        
        return $cached_data;
    }

    /**
     * Get cache time for registration number
     */
    public function get_cache_time($regNumber) {
        $cache_key = $this->get_cache_key($regNumber);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data && is_array($cached_data) && isset($cached_data['cache_time'])) {
            return $cached_data['cache_time'];
        }
        
        return null;
    }

    /**
     * Cache API response
     */
    public function set($regNumber, $data) {
        // Check if cache is enabled
        $cache_enabled = get_option('vehicle_lookup_cache_enabled', '1');
        if ($cache_enabled !== '1') {
            return; // Cache disabled, don't store anything
        }
        
        $cache_key = $this->get_cache_key($regNumber);
        
        // Store cache time with the data
        $cache_data = array(
            'data' => $data,
            'cache_time' => current_time('c')
        );
        
        // Use admin setting for cache duration, fallback to constant if not set
        $cache_duration = get_option('vehicle_lookup_cache_duration', VEHICLE_LOOKUP_CACHE_DURATION);
        
        set_transient($cache_key, $cache_data, $cache_duration);
    }

    /**
     * Generate cache key for registration number
     */
    private function get_cache_key($regNumber) {
        // Normalize the plate before generating cache key
        $normalized = Vehicle_Lookup_Helpers::normalize_plate($regNumber);
        return 'vehicle_cache_' . md5($normalized);
    }

    /**
     * Clear cache for specific registration number
     */
    public function delete($regNumber) {
        $cache_key = $this->get_cache_key($regNumber);
        delete_transient($cache_key);
    }

    /**
     * Clear all vehicle lookup cache
     */
    public function clear_all() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vehicle_cache_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vehicle_cache_%'");
        
        // Also clear worker cache
        $this->clear_worker_cache();
    }

    /**
     * Clear cache on the worker
     */
    public function clear_worker_cache() {
        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        
        $response = wp_remote_post($worker_url . '/cache/clear', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Origin' => get_site_url()
            ),
            'body' => json_encode(array(
                'clearAll' => true
            )),
            'timeout' => 10
        ));

        if (is_wp_error($response)) {
            error_log('Failed to clear worker cache: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            error_log('Worker cache cleared successfully');
            return true;
        } else {
            error_log('Failed to clear worker cache. Status code: ' . $status_code);
            return false;
        }
    }
}
