<?php
class VehicleLookupAccess {
    private $db_handler;

    public function __construct() {
        $this->db_handler = new Vehicle_Lookup_Database();
    }

    /**
     * Determine user's tier based on purchase status
     */
    public function determine_tier($regNumber) {
        // Check for premium access first (Product ID 739)
        $premium_key = 'premium_access_' . $regNumber;
        if (get_transient($premium_key)) {
            return 'premium';
        }

        // Check for basic access (Product ID 62)
        $basic_key = 'owner_access_' . $regNumber;
        if (get_transient($basic_key)) {
            return 'basic';
        }

        // Default to free tier
        return 'free';
    }

    /**
     * Check if rate limit allows request
     */
    public function check_rate_limit() {
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
     * Check daily quota availability
     */
    public function check_quota_available() {
        $today = date('Y-m-d');
        $current_count = $this->db_handler->get_daily_quota($today);
        $quota_limit = get_option('vehicle_lookup_daily_quota', 5000);

        return $current_count < $quota_limit;
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
     * Get quota status
     */
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
     * Get client IP address
     */
    public function get_client_ip() {
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
}
