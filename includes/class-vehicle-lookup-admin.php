<?php

class Vehicle_Lookup_Admin {

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_vehicle_lookup_test_api', array($this, 'test_api_connectivity'));

        // Ensure database table exists
        $this->ensure_database_table();
    }

    private function ensure_database_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

        if (!$table_exists) {
            $db_handler = new Vehicle_Lookup_Database();
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
        if (strpos($hook, 'vehicle-lookup') !== false) {
            wp_enqueue_style(
                'vehicle-lookup-admin',
                VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                VEHICLE_LOOKUP_VERSION
            );

            wp_enqueue_script(
                'vehicle-lookup-admin',
                VEHICLE_LOOKUP_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                VEHICLE_LOOKUP_VERSION,
                true
            );

            wp_localize_script(
                'vehicle-lookup-admin',
                'vehicleLookupAdmin',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('vehicle_lookup_admin_nonce')
                )
            );
        }
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
                            </tr>
                            <tr>
                                <td><strong>This Month</strong></td>
                                <td><?php echo $stats['month']['total']; ?></td>
                                <td><?php echo $stats['month']['success']; ?></td>
                                <td><?php echo $stats['month']['failed']; ?></td>
                                <td><?php echo $stats['month']['rate']; ?>%</td>
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
                                <th>Last Searched</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['popular'] as $search): ?>
                            <tr>
                                <td><strong><?php echo esc_html($search['reg_number']); ?></strong></td>
                                <td><?php echo $search['count']; ?></td>
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

        // Convert Y-m-d-H format to proper datetime range
        $hour_parts = explode('-', $current_hour);
        if (count($hour_parts) === 4) {
            $start_time = $hour_parts[0] . '-' . $hour_parts[1] . '-' . $hour_parts[2] . ' ' . $hour_parts[3] . ':00:00';
            $end_time = $hour_parts[0] . '-' . $hour_parts[1] . '-' . $hour_parts[2] . ' ' . $hour_parts[3] . ':59:59';
        } else {
            // Fallback to current hour if format is unexpected
            $start_time = date('Y-m-d H:00:00');
            $end_time = date('Y-m-d H:59:59');
        }

        $sql = "SELECT COUNT(*) FROM {$table_name} 
                WHERE lookup_time >= %s AND lookup_time <= %s";

        return $wpdb->get_var(
            $wpdb->prepare($sql, $start_time, $end_time)
        ) ?: 0;
    }

    private function get_cache_stats() {
        global $wpdb;

        // Count cache entries by checking transients
        $cache_entries = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_vehicle_cache_%'"
        );

        // Calculate real hit rate from database
        $db_handler = new Vehicle_Lookup_Database();
        $today = date('Y-m-d');
        $start_date = $today . ' 00:00:00';
        $end_date = $today . ' 23:59:59';

        $stats = $db_handler->get_stats($start_date, $end_date);
        $hit_rate = $stats && $stats->total_lookups > 0 ? 
            round(($stats->cache_hits / $stats->total_lookups) * 100) : 0;

        return array(
            'entries' => (int) $cache_entries,
            'hit_rate' => $hit_rate
        );
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
                'success_rate' => 0
            );
        }

        $success_rate = $stats->total_lookups > 0 ? 
            round(($stats->successful_lookups / $stats->total_lookups) * 100) : 0;

        return array(
            'today_total' => (int) $stats->total_lookups,
            'today_success' => (int) $stats->successful_lookups,
            'today_failed' => (int) $stats->failed_lookups,
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
        $popular_searches = $db_handler->get_popular_searches(5, 30);

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
                    round(($week_stats->successful_lookups / $week_stats->total_lookups) * 100) : 0
            ),
            'month' => array(
                'total' => $month_stats ? (int) $month_stats->total_lookups : 0,
                'success' => $month_stats ? (int) $month_stats->successful_lookups : 0,
                'failed' => $month_stats ? (int) $month_stats->failed_lookups : 0,
                'rate' => $month_stats && $month_stats->total_lookups > 0 ? 
                    round(($month_stats->successful_lookups / $month_stats->total_lookups) * 100) : 0
            ),
            'popular' => array_map(function($search) {
                return array(
                    'reg_number' => $search->reg_number,
                    'count' => (int) $search->search_count,
                    'last_search' => strtotime($search->last_searched)
                );
            }, $popular_searches)
        );
    }

    public function test_api_connectivity() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');

        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);

        $response = wp_remote_post($worker_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Origin' => get_site_url()
            ),
            'body' => json_encode(array(
                'registrationNumber' => 'AB12345'
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
}