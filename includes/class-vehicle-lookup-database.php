<?php
class Vehicle_Lookup_Database {
    private $table_name;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'vehicle_lookup_logs';
    }

    /**
     * Create database table for lookup tracking
     */
    public function create_table() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reg_number varchar(20) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            lookup_time datetime DEFAULT CURRENT_TIMESTAMP,
            success tinyint(1) NOT NULL,
            error_message text,
            failure_type varchar(20) DEFAULT NULL,
            response_time_ms int,
            cached tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_reg_number (reg_number),
            KEY idx_lookup_time (lookup_time),
            KEY idx_success (success),
            KEY idx_ip_address (ip_address)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log a lookup attempt
     */
    public function log_lookup($reg_number, $ip_address, $success, $error_message = null, $response_time_ms = null, $cached = false, $failure_type = null) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return $this->wpdb->insert(
            $this->table_name,
            array(
                'reg_number' => strtoupper($reg_number),
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'lookup_time' => current_time('mysql'),
                'success' => $success ? 1 : 0,
                'error_message' => $error_message,
                'failure_type' => $failure_type,
                'response_time_ms' => $response_time_ms,
                'cached' => $cached ? 1 : 0
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d')
        );
    }

    /**
     * Get lookup statistics for a date range
     */
    public function get_stats($start_date, $end_date) {
        $sql = "SELECT 
            COUNT(*) as total_lookups,
            SUM(success) as successful_lookups,
            COUNT(*) - SUM(success) as failed_lookups,
            AVG(response_time_ms) as avg_response_time,
            SUM(cached) as cache_hits,
            SUM(CASE WHEN success = 0 AND failure_type = 'http_error' THEN 1 ELSE 0 END) as http_errors,
            SUM(CASE WHEN success = 0 AND failure_type = 'invalid_plate' THEN 1 ELSE 0 END) as invalid_plates,
            SUM(CASE WHEN success = 0 AND failure_type = 'connection_error' THEN 1 ELSE 0 END) as connection_errors
        FROM {$this->table_name} 
        WHERE lookup_time >= %s AND lookup_time <= %s";

        return $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $start_date, $end_date)
        );
    }

    /**
     * Get most searched registration numbers
     */
    public function get_popular_searches($limit = 10, $days = 30) {
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $sql = "SELECT 
            reg_number,
            COUNT(*) as search_count,
            MAX(lookup_time) as last_searched
        FROM {$this->table_name} 
        WHERE lookup_time >= %s AND success = 1
        GROUP BY reg_number 
        ORDER BY search_count DESC 
        LIMIT %d";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $start_date, $limit)
        );
    }

    /**
     * Get hourly rate limit usage for IP
     */
    public function get_hourly_rate_limit($ip_address, $hour) {
        $start_time = $hour . ':00:00';
        $end_time = $hour . ':59:59';
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} 
        WHERE ip_address = %s AND lookup_time >= %s AND lookup_time <= %s";

        return $this->wpdb->get_var(
            $this->wpdb->prepare($sql, $ip_address, $start_time, $end_time)
        );
    }

    /**
     * Get daily quota usage
     */
    public function get_daily_quota($date) {
        $start_date = $date . ' 00:00:00';
        $end_date = $date . ' 23:59:59';
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} 
        WHERE lookup_time >= %s AND lookup_time <= %s AND success = 1";

        return $this->wpdb->get_var(
            $this->wpdb->prepare($sql, $start_date, $end_date)
        );
    }

    /**
     * Get failed lookup attempts with details
     */
    public function get_failed_lookups($limit = 50) {
        $sql = "SELECT reg_number, ip_address, error_message, lookup_time 
        FROM {$this->table_name} 
        WHERE success = 0 
        ORDER BY lookup_time DESC 
        LIMIT %d";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $limit)
        );
    }

    /**
     * Clean up old logs (older than specified days)
     */
    public function cleanup_old_logs($days = 90) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE lookup_time < %s",
                $cutoff_date
            )
        );
    }
}
