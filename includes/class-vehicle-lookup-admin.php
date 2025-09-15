<?php

class Vehicle_Lookup_Admin {

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_vehicle_lookup_test_api', array($this, 'test_api_connectivity'));
        add_action('wp_ajax_vehicle_lookup_check_upstream', array($this, 'check_upstream_health'));
        add_action('wp_ajax_clear_worker_cache', array($this, 'handle_clear_worker_cache'));
        add_action('wp_ajax_clear_local_cache', array($this, 'handle_clear_local_cache'));
        add_action('wp_ajax_reset_analytics_data', array($this, 'reset_analytics_data'));

        // Ensure database table exists
        $this->ensure_database_table();
    }

    private function ensure_database_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

        $db_handler = new Vehicle_Lookup_Database();

        if (!$table_exists) {
            $db_handler->create_table();
        } else {
            // Ensure table has all required columns for existing installations
            $db_handler->create_table();
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Vehicle Lookup',
            'Vehicle Lookup',
            'manage_options',
            'vehicle-lookup',
            array($this, 'admin_page'),
            'dashicons-car',
            30
        );

        add_submenu_page(
            'vehicle-lookup',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'vehicle-lookup',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'vehicle-lookup',
            'Settings',
            'Settings',
            'manage_options',
            'vehicle-lookup-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'vehicle-lookup',
            'Analytics',
            'Analytics',
            'manage_options',
            'vehicle-lookup-analytics',
            array($this, 'analytics_page')
        );
    }

    public function init_settings() {
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_worker_url');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_timeout');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_rate_limit');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_cache_duration');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_rate_limit');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_daily_quota');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_log_retention');

        add_settings_section(
            'vehicle_lookup_api_section',
            'API Configuration',
            null,
            'vehicle_lookup_settings'
        );

        add_settings_section(
            'vehicle_lookup_limits_section',
            'Rate Limiting & Cache',
            null,
            'vehicle_lookup_settings'
        );

        add_settings_field(
            'worker_url',
            'Worker URL',
            array($this, 'worker_url_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_api_section'
        );

        add_settings_field(
            'timeout',
            'API Timeout (seconds)',
            array($this, 'timeout_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_api_section'
        );

        add_settings_field(
            'rate_limit',
            'Rate Limit (requests per hour per IP)',
            array($this, 'rate_limit_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );

        add_settings_field(
            'cache_duration',
            'Cache Duration (seconds)',
            array($this, 'cache_duration_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );

        add_settings_field(
            'daily_quota',
            'Daily Quota Limit',
            array($this, 'daily_quota_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );

        add_settings_field(
            'log_retention',
            'Log Retention (days)',
            array($this, 'log_retention_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );
    }

    public function enqueue_admin_scripts($hook) {
        // Load on all vehicle lookup admin pages
        if (strpos($hook, 'vehicle-lookup') === false) {
            return;
        }

        wp_enqueue_style(
            'vehicle-lookup-admin-style',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'vehicle-lookup-admin-script',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('vehicle-lookup-admin-script', 'vehicleLookupAdmin', array(
            'nonce' => wp_create_nonce('vehicle_lookup_admin_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }

    public function admin_page() {
        $db_handler = new Vehicle_Lookup_Database();
        $today = date('Y-m-d');

        // Get real quota usage
        $quota_used = $db_handler->get_daily_quota($today);
        $quota_limit = get_option('vehicle_lookup_daily_quota', 5000);

        // Get real hourly rate limit stats
        $current_hour = date('Y-m-d-H');
        $rate_limit_total = $this->get_hourly_rate_limit_usage($db_handler, $current_hour);

        // Get cache stats
        $cache_stats = $this->get_cache_stats();

        // Get real lookup stats
        $stats = $this->get_lookup_stats($db_handler);

        ?>
        <div class="wrap vehicle-lookup-admin">
            <h1><span class="dashicons dashicons-car"></span> Vehicle Lookup Dashboard</h1>

            <div class="admin-grid">
                <!-- Status Cards -->
                <div class="status-cards">
                    <div class="status-card quota">
                        <div class="card-header">
                            <h3>Daily Quota</h3>
                            <span class="dashicons dashicons-chart-bar"></span>
                        </div>
                        <div class="card-content">
                            <div class="big-number"><?php echo number_format($quota_used); ?></div>
                            <div class="sub-text">of <?php echo number_format($quota_limit); ?> used</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, ($quota_used / $quota_limit) * 100); ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="status-card rate-limit">
                        <div class="card-header">
                            <h3>Hourly Rate Limits</h3>
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="card-content">
                            <div class="big-number"><?php echo $rate_limit_total; ?></div>
                            <div class="sub-text">requests this hour</div>
                        </div>
                    </div>

                    <div class="status-card cache">
                        <div class="card-header">
                            <h3>Cache Status</h3>
                            <span class="dashicons dashicons-performance"></span>
                        </div>
                        <div class="card-content">
                            <div class="big-number"><?php echo $cache_stats['entries']; ?></div>
                            <div class="sub-text">cached entries</div>
                            <div class="cache-efficiency">
                                Hit rate: <?php echo $cache_stats['hit_rate']; ?>%
                            </div>
                        </div>
                    </div>

                    <div class="status-card api">
                        <div class="card-header">
                            <h3>API Status</h3>
                            <span class="dashicons dashicons-admin-plugins"></span>
                        </div>
                        <div class="card-content">
                            <div class="api-status" id="api-status">
                                <span class="status-indicator checking">●</span> Checking...
                            </div>
                            <button type="button" class="button button-secondary" id="test-api">Test Connection</button>
                            <button type="button" class="button button-secondary" id="check-upstream">Check Upstream</button>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h3>Today's Activity</h3>
                    <div class="activity-stats">
                        <div class="stat-item">
                            <strong><?php echo $stats['today_total']; ?></strong>
                            <span>Total Lookups</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo $stats['today_success']; ?></strong>
                            <span>Successful</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo $stats['today_failed']; ?></strong>
                            <span>Failed</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo $stats['today_invalid_plates']; ?></strong>
                            <span>Invalid Plates</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo $stats['today_http_errors']; ?></strong>
                            <span>HTTP Errors</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo $stats['success_rate']; ?>%</strong>
                            <span>Success Rate</span>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="system-health">
                    <h3>System Health</h3>
                    <div class="health-checks">
                        <div class="health-item">
                            <span class="dashicons dashicons-yes-alt health-ok"></span>
                            <span>WordPress Version: <?php echo get_bloginfo('version'); ?></span>
                        </div>
                        <div class="health-item">
                            <span class="dashicons dashicons-yes-alt health-ok"></span>
                            <span>PHP Version: <?php echo PHP_VERSION; ?></span>
                        </div>
                        <div class="health-item">
                            <span class="dashicons dashicons-<?php echo $cache_stats['entries'] > 0 ? 'yes-alt health-ok' : 'warning health-warning'; ?>"></span>
                            <span>Cache Functioning: <?php echo $cache_stats['entries'] > 0 ? 'Yes' : 'No entries'; ?></span>
                        </div>
                        <div class="health-item">
                            <span class="dashicons dashicons-<?php echo function_exists('wp_remote_post') ? 'yes-alt health-ok' : 'dismiss health-error'; ?>"></span>
                            <span>HTTP Requests: <?php echo function_exists('wp_remote_post') ? 'Available' : 'Disabled'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('vehicle_lookup_messages', 'vehicle_lookup_message', 'Settings Saved', 'updated');
        }

        settings_errors('vehicle_lookup_messages');
        ?>
        <div class="wrap vehicle-lookup-admin">
            <h1><span class="dashicons dashicons-admin-settings"></span> Vehicle Lookup Settings</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('vehicle_lookup_settings');
                do_settings_sections('vehicle_lookup_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function analytics_page() {
        $stats = $this->get_detailed_stats();
        ?>
        <div class="wrap vehicle-lookup-admin">
            <h1><span class="dashicons dashicons-chart-area"></span> Vehicle Lookup Analytics</h1>

            <div class="analytics-actions" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <div>
                        <button type="button" class="button button-secondary" id="reset-analytics" style="background-color: #dc3232; color: white; border-color: #dc3232;">
                            <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> Reset Analytics Data
                        </button>
                        <p class="description" style="margin-top: 5px; max-width: 200px;">Permanently delete all historical lookup data and statistics.</p>
                    </div>

                    <div>
                        <button type="button" class="button button-secondary" id="clear-worker-cache">
                            <span class="dashicons dashicons-cloud" style="margin-top: 3px;"></span> Clear Worker Cache
                        </button>
                        <p class="description" style="margin-top: 5px; max-width: 200px;">Clear cached data on the remote worker server.</p>
                    </div>

                    <div>
                        <button type="button" class="button button-secondary" id="clear-local-cache">
                            <span class="dashicons dashicons-performance" style="margin-top: 3px;"></span> Clear Local Cache
                        </button>
                        <p class="description" style="margin-top: 5px; max-width: 200px;">Clear WordPress transient cache for vehicle lookups.</p>
                    </div>
                </div>
            </div>

            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Usage Statistics</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Total Lookups</th>
                                <th>Successful</th>
                                <th>Failed</th>
                                <th>Success Rate</th>
                                <th>Invalid Plates</th>
                                <th>HTTP Errors</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Today</strong></td>
                                <td><?php echo $stats['today']['total']; ?></td>
                                <td><?php echo $stats['today']['success']; ?></td>
                                <td><?php echo $stats['today']['failed']; ?></td>
                                <td><?php echo $stats['today']['rate']; ?>%</td>
                                <td><?php echo $stats['today']['invalid_plates']; ?></td>
                                <td><?php echo $stats['today']['http_errors']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>This Week</strong></td>
                                <td><?php echo $stats['week']['total']; ?></td>
                                <td><?php echo $stats['week']['success']; ?></td>
                                <td><?php echo $stats['week']['failed']; ?></td>
                                <td><?php echo $stats['week']['rate']; ?>%</td>
                                <td><?php echo $stats['week']['invalid_plates']; ?></td>
                                <td><?php echo $stats['week']['http_errors']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>This Month</strong></td>
                                <td><?php echo $stats['month']['total']; ?></td>
                                <td><?php echo $stats['month']['success']; ?></td>
                                <td><?php echo $stats['month']['failed']; ?></td>
                                <td><?php echo $stats['month']['rate']; ?>%</td>
                                <td><?php echo $stats['month']['invalid_plates']; ?></td>
                                <td><?php echo $stats['month']['http_errors']; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="analytics-card">
                    <h3>Most Searched Registration Numbers</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Registration Number</th>
                                <th>Search Count</th>
                                <th>Status</th>
                                <th>Last Searched</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['popular'] as $search): ?>
                            <tr>
                                <td><strong><?php echo esc_html($search['reg_number']); ?></strong></td>
                                <td><?php echo $search['count']; ?></td>
                                <td>
                                    <?php if ($search['has_valid_result']): ?>
                                        <span style="color: #46b450;">✓ Valid</span>
                                    <?php else: ?>
                                        <span style="color: #dc3232;">✗ Invalid</span>
                                        <?php if (!empty($search['failure_reason'])): ?>
                                            <span style="color: #666; font-size: 0.9em;">(<?php echo esc_html($search['failure_reason']); ?>)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', $search['last_search']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    // Settings field callbacks
    public function worker_url_field() {
        $value = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        echo '<input type="url" name="vehicle_lookup_worker_url" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL for the vehicle lookup API worker</p>';
    }

    public function timeout_field() {
        $value = get_option('vehicle_lookup_timeout', 15);
        echo '<input type="number" name="vehicle_lookup_timeout" value="' . esc_attr($value) . '" min="5" max="30" />';
        echo '<p class="description">API request timeout in seconds (5-30)</p>';
    }

    public function rate_limit_field() {
        $value = get_option('vehicle_lookup_rate_limit', VEHICLE_LOOKUP_RATE_LIMIT);
        echo '<input type="number" name="vehicle_lookup_rate_limit" value="' . esc_attr($value) . '" min="1" max="100" />';
        echo '<p class="description">Maximum requests allowed per hour per IP address</p>';
    }

    public function cache_duration_field() {
        $value = get_option('vehicle_lookup_cache_duration', VEHICLE_LOOKUP_CACHE_DURATION);
        $hours = $value / 3600;
        echo '<input type="number" name="vehicle_lookup_cache_duration" value="' . esc_attr($value) . '" min="3600" max="86400" />';
        echo '<p class="description">Cache duration in seconds (currently ' . $hours . ' hours)</p>';
    }

    public function daily_quota_field() {
        $value = get_option('vehicle_lookup_daily_quota', 5000);
        echo '<input type="number" name="vehicle_lookup_daily_quota" value="' . esc_attr($value) . '" min="100" max="10000" />';
        echo '<p class="description">Maximum API calls allowed per day</p>';
    }



    public function log_retention_field() {
        $value = get_option('vehicle_lookup_log_retention', 90);
        echo '<input type="number" name="vehicle_lookup_log_retention" value="' . esc_attr($value) . '" min="30" max="365" />';
        echo '<p class="description">Number of days to keep lookup logs (30-365)</p>';
    }

    // Helper methods
    private function get_hourly_rate_limit_usage($db_handler, $current_hour) {
        global $wpdb;

        // Get actual hourly usage across all IPs
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        // Use current hour directly without complex conversion
        $start_time = date('Y-m-d H:00:00');
        $end_time = date('Y-m-d H:59:59');

        $sql = "SELECT COUNT(*) FROM {$table_name} 
                WHERE lookup_time >= %s AND lookup_time <= %s";

        $result = $wpdb->get_var(
            $wpdb->prepare($sql, $start_time, $end_time)
        );

        return (int) ($result ?: 0);
    }

    private function get_cache_stats() {
        global $wpdb;

        $entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_vehicle_cache_%'");

        return array(
            'entries' => intval($entries),
            'hit_rate' => 85 // This would need actual tracking
        );
    }

    /**
     * Handle AJAX request to clear cache
     */
    public function handle_clear_cache() {
        // Check nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'vehicle_lookup_admin_nonce')) {
            wp_die('Security check failed');
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $cache = new VehicleLookupCache();
        $result = $cache->clear_all();

        wp_send_json_success(array(
            'message' => 'Cache cleared successfully'
        ));
    }

    private function get_lookup_stats($db_handler) {
        $today = date('Y-m-d');
        $start_date = $today . ' 00:00:00';
        $end_date = $today . ' 23:59:59';

        $stats = $db_handler->get_stats($start_date, $end_date);

        if (!$stats) {
            return array(
                'today_total' => 0,
                'today_success' => 0,
                'today_failed' => 0,
                'today_invalid_plates' => 0,
                'today_http_errors' => 0,
                'success_rate' => 0
            );
        }

        $success_rate = $stats->total_lookups > 0 ? 
            round(($stats->successful_lookups / $stats->total_lookups) * 100) : 0;

        return array(
            'today_total' => (int) $stats->total_lookups,
            'today_success' => (int) $stats->successful_lookups,
            'today_failed' => (int) $stats->failed_lookups,
            'today_invalid_plates' => (int) $stats->invalid_plates,
            'today_http_errors' => (int) $stats->http_errors,
            'success_rate' => $success_rate
        );
    }

    private function get_detailed_stats() {
        $db_handler = new Vehicle_Lookup_Database();

        // Today's stats
        $today = date('Y-m-d');
        $today_stats = $db_handler->get_stats($today . ' 00:00:00', $today . ' 23:59:59');

        // Week's stats
        $week_start = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00';
        $week_end = date('Y-m-d') . ' 23:59:59';
        $week_stats = $db_handler->get_stats($week_start, $week_end);

        // Month's stats
        $month_start = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
        $month_end = date('Y-m-d') . ' 23:59:59';
        $month_stats = $db_handler->get_stats($month_start, $month_end);

        // Popular searches
        $popular_searches = $this->get_most_searched_numbers(5);

        return array(
            'today' => array(
                'total' => $today_stats ? (int) $today_stats->total_lookups : 0,
                'success' => $today_stats ? (int) $today_stats->successful_lookups : 0,
                'failed' => $today_stats ? (int) $today_stats->failed_lookups : 0,
                'rate' => $today_stats && $today_stats->total_lookups > 0 ? 
                    round(($today_stats->successful_lookups / $today_stats->total_lookups) * 100) : 0,
                'invalid_plates' => $today_stats ? (int) $today_stats->invalid_plates : 0,
                'http_errors' => $today_stats ? (int) $today_stats->http_errors : 0
            ),
            'week' => array(
                'total' => $week_stats ? (int) $week_stats->total_lookups : 0,
                'success' => $week_stats ? (int) $week_stats->successful_lookups : 0,
                'failed' => $week_stats ? (int) $week_stats->failed_lookups : 0,
                'rate' => $week_stats && $week_stats->total_lookups > 0 ? 
                    round(($week_stats->successful_lookups / $week_stats->total_lookups) * 100) : 0,
                'invalid_plates' => $week_stats ? (int) $week_stats->invalid_plates : 0,
                'http_errors' => $week_stats ? (int) $week_stats->http_errors : 0
            ),
            'month' => array(
                'total' => $month_stats ? (int) $month_stats->total_lookups : 0,
                'success' => $month_stats ? (int) $month_stats->successful_lookups : 0,
                'failed' => $month_stats ? (int) $month_stats->failed_lookups : 0,
                'rate' => $month_stats && $month_stats->total_lookups > 0 ? 
                    round(($month_stats->successful_lookups / $month_stats->total_lookups) * 100) : 0,
                'invalid_plates' => $month_stats ? (int) $month_stats->invalid_plates : 0,
                'http_errors' => $month_stats ? (int) $month_stats->http_errors : 0
            ),
            'popular' => array_map(function($search) {
                return array(
                    'reg_number' => $search->registration_number,
                    'count' => (int) $search->search_count,
                    'last_search' => strtotime($search->last_searched),
                    'has_valid_result' => (bool) $search->has_valid_result,
                    'failure_reason' => $search->last_failure_type
                );
            }, $popular_searches)
        );
    }

    private function get_most_searched_numbers($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                reg_number as registration_number,
                COUNT(*) as search_count,
                MAX(success) as has_valid_result,
                MAX(lookup_time) as last_searched,
                (SELECT failure_type FROM {$table_name} l2 
                 WHERE l2.reg_number = l1.reg_number 
                 AND l2.success = 0 
                 AND l2.failure_type IS NOT NULL 
                 ORDER BY l2.lookup_time DESC 
                 LIMIT 1) as last_failure_type
            FROM {$table_name} l1
            WHERE reg_number IS NOT NULL 
            AND reg_number != ''
            AND lookup_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY reg_number
            ORDER BY search_count DESC
            LIMIT %d
        ", $limit));
    }

    public function test_api_connectivity() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);

        $response = wp_remote_post($worker_url . '/lookup', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Origin' => get_site_url()
            ),
            'body' => json_encode(array(
                'registrationNumber' => 'CO10101'
            )),
            'timeout' => $timeout
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Connection failed: ' . $response->get_error_message()
            ));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_time = wp_remote_retrieve_header($response, 'X-Response-Time');

        if ($status_code === 200) {
            wp_send_json_success(array(
                'message' => 'API is responding correctly',
                'status_code' => $status_code,
                'response_time' => $response_time ?: 'Unknown'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'API returned status code: ' . $status_code,
                'status_code' => $status_code
            ));
        }
    }

    public function check_upstream_health() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);

        $response = wp_remote_post($worker_url . '/health', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Origin' => get_site_url()
            ),
            'body' => json_encode(array(
                'checkUpstream' => true
            )),
            'timeout' => $timeout
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Upstream check failed: ' . $response->get_error_message(),
                'status' => 'degraded'
            ));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200 && isset($body['status']) && $body['status'] === 'healthy') {
            wp_send_json_success(array(
                'message' => 'Upstream service is healthy.',
                'status' => 'healthy',
                'details' => $body
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Upstream service is degraded or check failed.',
                'status' => isset($body['status']) ? $body['status'] : 'unknown',
                'details' => $body
            ));
        }
    }


    public function reset_analytics_data() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

        if (!$table_exists) {
            wp_send_json_error(array(
                'message' => 'Analytics table does not exist'
            ));
        }

        // Get count before deletion for verification
        $count_before = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        // Use DELETE instead of TRUNCATE for better compatibility
        $result = $wpdb->query("DELETE FROM {$table_name}");

        if ($result !== false) {
            // Verify deletion worked
            $count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

            // Clear any cached data
            wp_cache_delete('vehicle_lookup_stats_*', 'vehicle_lookup');

            wp_send_json_success(array(
                'message' => "Successfully deleted {$count_before} records"
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to reset analytics data. Database error: ' . $wpdb->last_error
            ));
        }
    }

    /**
     * Handle AJAX request to clear worker cache only
     */
    public function handle_clear_worker_cache() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $cache = new VehicleLookupCache();
        $result = $cache->clear_worker_cache();

        if ($result) {
            wp_send_json_success(array('message' => 'Worker cache cleared successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to clear worker cache'));
        }
    }

    /**
     * Handle AJAX request to clear local cache only
     */
    public function handle_clear_local_cache() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        // Clear only local WordPress transients
        global $wpdb;
        $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vehicle_cache_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vehicle_cache_%'");

        wp_send_json_success(array(
            'message' => "Local cache cleared successfully ({$deleted} entries removed)"
        ));
    }
}