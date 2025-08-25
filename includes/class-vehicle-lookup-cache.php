<?php
class VehicleLookupCache {
    
    /**
     * Get cached response for registration number
     */
    public function get($regNumber) {
        $cache_key = $this->get_cache_key($regNumber);
        return get_transient($cache_key);
    }

    /**
     * Cache API response
     */
    public function set($regNumber, $data) {
        $cache_key = $this->get_cache_key($regNumber);
        set_transient($cache_key, $data, VEHICLE_LOOKUP_CACHE_DURATION);
    }

    /**
     * Generate cache key for registration number
     */
    private function get_cache_key($regNumber) {
        return 'vehicle_cache_' . md5(strtoupper($regNumber));
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
    }
}
