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
            tier varchar(10) DEFAULT 'free',
            response_time_ms int,
            cached tinyint(1) DEFAULT 0,
            response_data longtext DEFAULT NULL,
            error_code varchar(50) DEFAULT NULL,
            correlation_id varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_reg_number (reg_number),
            KEY idx_lookup_time (lookup_time),
            KEY idx_success (success),
            KEY idx_ip_address (ip_address),
            KEY idx_tier (tier),
            KEY idx_error_code (error_code),
            KEY idx_correlation_id (correlation_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Ensure failure_type column exists for existing installations
        $this->add_failure_type_column();
        
        // Ensure tier column exists for existing installations
        $this->add_tier_column();
        
        // Ensure response_data column exists for existing installations
        $this->add_response_data_column();
        
        // Ensure error tracking columns exist for existing installations
        $this->add_error_tracking_columns();
    }

    /**
     * Add failure_type column to existing table if it doesn't exist
     */
    private function add_failure_type_column() {
        $column_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM {$this->table_name} LIKE %s",
                'failure_type'
            )
        );

        if (empty($column_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->table_name} 
                ADD COLUMN failure_type varchar(20) DEFAULT NULL AFTER error_message"
            );
        }
    }

    /**
     * Add tier column to existing table if it doesn't exist
     */
    private function add_tier_column() {
        $column_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM {$this->table_name} LIKE %s",
                'tier'
            )
        );

        if (empty($column_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->table_name} 
                ADD COLUMN tier varchar(10) DEFAULT 'free' AFTER failure_type"
            );
        }
    }

    /**
     * Add response_data column to existing table if it doesn't exist
     */
    private function add_response_data_column() {
        $column_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM {$this->table_name} LIKE %s",
                'response_data'
            )
        );

        if (empty($column_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->table_name} 
                ADD COLUMN response_data longtext DEFAULT NULL AFTER cached"
            );
        }
    }

    /**
     * Add error tracking columns to existing table if they don't exist
     */
    private function add_error_tracking_columns() {
        // Check and add error_code column
        $error_code_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM {$this->table_name} LIKE %s",
                'error_code'
            )
        );

        if (empty($error_code_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->table_name} 
                ADD COLUMN error_code varchar(50) DEFAULT NULL AFTER response_data,
                ADD INDEX idx_error_code (error_code)"
            );
        }

        // Check and add correlation_id column
        $correlation_id_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM {$this->table_name} LIKE %s",
                'correlation_id'
            )
        );

        if (empty($correlation_id_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->table_name} 
                ADD COLUMN correlation_id varchar(100) DEFAULT NULL AFTER error_code,
                ADD INDEX idx_correlation_id (correlation_id)"
            );
        }
    }

    /**
     * Log a lookup attempt with enhanced error tracking
     */
    public function log_lookup($reg_number, $ip_address, $success, $error_message = null, $response_time_ms = null, $cached = false, $failure_type = null, $tier = 'free', $response_data = null, $error_code = null, $correlation_id = null) {
        // Safely sanitize user agent to prevent injection
        $user_agent = '';
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            // Use WordPress function if available, otherwise basic sanitization
            if (function_exists('sanitize_text_field')) {
                $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
            } else {
                // Basic sanitization fallback
                $user_agent = strip_tags($_SERVER['HTTP_USER_AGENT']);
                $user_agent = preg_replace('/[^\w\s\-\.\(\)\/]/', '', $user_agent);
            }
            // Additional length limiting for security
            $user_agent = substr($user_agent, 0, 500);
        }
        
        // Validate and sanitize all inputs
        $reg_number = function_exists('sanitize_text_field') ? sanitize_text_field($reg_number) : strip_tags($reg_number);
        // Normalize the registration number (uppercase and remove spaces)
        $reg_number = Vehicle_Lookup_Helpers::normalize_plate($reg_number);
        $ip_address = filter_var($ip_address, FILTER_VALIDATE_IP) ? $ip_address : '';
        $tier = in_array($tier, ['free', 'premium', 'business']) ? $tier : 'free';
        $failure_type = $failure_type ? (function_exists('sanitize_text_field') ? sanitize_text_field($failure_type) : strip_tags($failure_type)) : null;
        $response_time_ms = is_numeric($response_time_ms) ? absint($response_time_ms) : null;
        
        // Validate and sanitize response_data if provided
        if ($response_data !== null) {
            $response_data = is_string($response_data) ? $response_data : wp_json_encode($response_data);
        }
        
        // Sanitize error_code and correlation_id
        $error_code = $error_code ? (function_exists('sanitize_text_field') ? sanitize_text_field($error_code) : strip_tags($error_code)) : null;
        $correlation_id = $correlation_id ? (function_exists('sanitize_text_field') ? sanitize_text_field($correlation_id) : strip_tags($correlation_id)) : null;
        
        return $this->wpdb->insert(
            $this->table_name,
            array(
                'reg_number' => $reg_number,  // Already normalized above
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'lookup_time' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
                'success' => $success ? 1 : 0,
                'error_message' => $error_message ? (function_exists('sanitize_text_field') ? sanitize_text_field($error_message) : strip_tags($error_message)) : null,
                'failure_type' => $failure_type,
                'tier' => $tier,
                'response_time_ms' => $response_time_ms,
                'cached' => $cached ? 1 : 0,
                'response_data' => $response_data,
                'error_code' => $error_code,
                'correlation_id' => $correlation_id
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
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
        // Validate IP address
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return 0;
        }
        
        // Validate hour format (should be HH format like "14" for 2 PM)
        if (!preg_match('/^\d{1,2}$/', $hour) || intval($hour) < 0 || intval($hour) > 23) {
            return 0;
        }
        
        // Use current date with validated hour
        $current_date = function_exists('current_time') ? current_time('Y-m-d') : date('Y-m-d');
        $hour = str_pad(intval($hour), 2, '0', STR_PAD_LEFT);
        $start_time = $current_date . ' ' . $hour . ':00:00';
        $end_time = $current_date . ' ' . $hour . ':59:59';
        
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
        // Validate date format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
            $date = function_exists('current_time') ? current_time('Y-m-d') : date('Y-m-d'); // Use current date if invalid
        }
        
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
        // Validate days parameter to prevent injection
        $days = absint($days);
        if ($days < 1 || $days > 3650) { // Max 10 years
            $days = 90; // Safe default
        }
        
        // Use safer date calculation
        $cutoff_date = function_exists('current_time') ? current_time('mysql', true) : gmdate('Y-m-d H:i:s');
        $cutoff_timestamp = strtotime($cutoff_date) - ($days * 24 * 60 * 60);
        $cutoff_date = date('Y-m-d H:i:s', $cutoff_timestamp);
        
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE lookup_time < %s",
                $cutoff_date
            )
        );
    }
}
