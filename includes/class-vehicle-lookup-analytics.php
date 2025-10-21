<?php
/**
 * Analytics Integration for Vehicle Lookup
 * 
 * Handles Google Analytics 4, Search Console verification, and tracking
 * for vehicle lookup performance monitoring.
 */
class Vehicle_Lookup_Analytics {
    
    /**
     * Initialize analytics integration
     */
    public function init() {
        // Add Google Analytics tracking hooks
        add_action('wp_head', array($this, 'add_analytics_tracking'), 999);
        
        // Add Search Console verification meta tag
        add_action('wp_head', array($this, 'add_search_console_verification'), 1);
        
        // Track vehicle lookups in admin (custom event logging)
        add_action('vehicle_lookup_success', array($this, 'track_lookup_event'), 10, 2);
        
        // Add admin dashboard widgets
        if (is_admin()) {
            add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        }
        
        // Add admin settings page for analytics configuration
        add_action('admin_menu', array($this, 'add_analytics_settings_page'), 99);
    }
    
    /**
     * Get Google Analytics ID from options
     */
    private function get_ga_tracking_id() {
        return get_option('vehicle_lookup_ga_tracking_id', '');
    }
    
    /**
     * Get Google Search Console verification code from options
     */
    private function get_gsc_verification_code() {
        return get_option('vehicle_lookup_gsc_verification_code', '');
    }
    
    /**
     * Add Google Analytics tracking code
     */
    public function add_analytics_tracking() {
        $ga_id = $this->get_ga_tracking_id();
        
        if (empty($ga_id)) {
            return;
        }
        
        // Check if we're on a vehicle page for enhanced tracking
        $is_vehicle_page = false;
        $reg_number = '';
        
        if (is_page('sok') || get_query_var('reg_number')) {
            $is_vehicle_page = true;
            $reg_number = get_query_var('reg_number');
            if (empty($reg_number)) {
                $request_uri = $_SERVER['REQUEST_URI'];
                if (preg_match('/\/sok\/([^\/\?]+)/', $request_uri, $matches)) {
                    $reg_number = $matches[1];
                }
            }
        }
        
        ?>
        <!-- Google Analytics 4 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga_id); ?>"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        
        gtag('config', '<?php echo esc_js($ga_id); ?>', {
            'anonymize_ip': true,
            'cookie_flags': 'SameSite=None;Secure'
        });
        
        <?php if ($is_vehicle_page && !empty($reg_number)): ?>
        // Track vehicle page view as custom event
        gtag('event', 'view_vehicle', {
            'registration_number': '<?php echo esc_js(strtoupper(sanitize_text_field($reg_number))); ?>',
            'page_path': '<?php echo esc_js($_SERVER['REQUEST_URI']); ?>'
        });
        <?php endif; ?>
        
        // Track vehicle lookup success (triggered by AJAX)
        document.addEventListener('vehicleLookupSuccess', function(event) {
            if (typeof gtag !== 'undefined' && event.detail) {
                gtag('event', 'vehicle_lookup', {
                    'registration_number': event.detail.regNumber,
                    'make': event.detail.make || '',
                    'model': event.detail.model || '',
                    'year': event.detail.year || ''
                });
            }
        });
        
        // Track owner info purchase clicks
        document.addEventListener('ownerInfoClick', function(event) {
            if (typeof gtag !== 'undefined' && event.detail) {
                gtag('event', 'click_owner_info', {
                    'registration_number': event.detail.regNumber
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Add Google Search Console verification meta tag
     */
    public function add_search_console_verification() {
        $verification_code = $this->get_gsc_verification_code();
        
        if (!empty($verification_code)) {
            echo '<meta name="google-site-verification" content="' . esc_attr($verification_code) . '" />' . "\n";
        }
    }
    
    /**
     * Track vehicle lookup event (called after successful lookup)
     */
    public function track_lookup_event($reg_number, $vehicle_data) {
        // This is for server-side tracking if needed
        // Can be extended to send events to third-party services
        
        // Store in custom table for analytics
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_analytics';
        
        // Check if analytics table exists, if not create it
        $this->maybe_create_analytics_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'reg_number' => $reg_number,
                'event_type' => 'lookup',
                'event_time' => current_time('mysql'),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create analytics table if it doesn't exist
     */
    private function maybe_create_analytics_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_analytics';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reg_number varchar(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_time datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            KEY idx_reg_number (reg_number),
            KEY idx_event_type (event_type),
            KEY idx_event_time (event_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Add dashboard widgets for analytics
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'vehicle_lookup_analytics_widget',
            'Vehicle Lookup Analytics',
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render dashboard analytics widget
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        // Get today's lookups
        $today = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE DATE(lookup_time) = CURDATE()"
        );
        
        // Get this week's lookups
        $this_week = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE YEARWEEK(lookup_time) = YEARWEEK(NOW())"
        );
        
        // Get this month's lookups
        $this_month = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE YEAR(lookup_time) = YEAR(NOW()) 
            AND MONTH(lookup_time) = MONTH(NOW())"
        );
        
        // Get top 5 searched vehicles this month
        $top_vehicles = $wpdb->get_results(
            "SELECT reg_number, COUNT(*) as lookup_count
            FROM {$table_name}
            WHERE YEAR(lookup_time) = YEAR(NOW()) 
            AND MONTH(lookup_time) = MONTH(NOW())
            AND success = 1
            GROUP BY reg_number
            ORDER BY lookup_count DESC
            LIMIT 5"
        );
        
        ?>
        <div class="vehicle-lookup-analytics-widget">
            <div class="analytics-stats">
                <div class="stat-box">
                    <h4>I dag</h4>
                    <p class="stat-number"><?php echo number_format($today); ?></p>
                </div>
                <div class="stat-box">
                    <h4>Denne uken</h4>
                    <p class="stat-number"><?php echo number_format($this_week); ?></p>
                </div>
                <div class="stat-box">
                    <h4>Denne måneden</h4>
                    <p class="stat-number"><?php echo number_format($this_month); ?></p>
                </div>
            </div>
            
            <?php if (!empty($top_vehicles)): ?>
                <div class="top-vehicles">
                    <h4>Mest søkte denne måneden</h4>
                    <ol>
                        <?php foreach ($top_vehicles as $vehicle): ?>
                            <li>
                                <strong><?php echo esc_html($vehicle->reg_number); ?></strong>
                                (<?php echo number_format($vehicle->lookup_count); ?> søk)
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=vehicle-lookup-admin'); ?>" class="button button-primary">
                    Se full analyse
                </a>
            </p>
        </div>
        
        <style>
        .vehicle-lookup-analytics-widget .analytics-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .vehicle-lookup-analytics-widget .stat-box {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .vehicle-lookup-analytics-widget .stat-box h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .vehicle-lookup-analytics-widget .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #3498db;
            margin: 0;
        }
        
        .vehicle-lookup-analytics-widget .top-vehicles h4 {
            margin: 15px 0 10px 0;
        }
        
        .vehicle-lookup-analytics-widget .top-vehicles ol {
            margin: 0;
            padding-left: 25px;
        }
        
        .vehicle-lookup-analytics-widget .top-vehicles li {
            margin: 5px 0;
        }
        </style>
        <?php
    }
    
    /**
     * Add analytics settings page
     */
    public function add_analytics_settings_page() {
        add_submenu_page(
            'vehicle-lookup-admin',
            'Analytics Settings',
            'Analytics',
            'manage_options',
            'vehicle-lookup-analytics',
            array($this, 'render_analytics_settings_page')
        );
    }
    
    /**
     * Render analytics settings page
     */
    public function render_analytics_settings_page() {
        // Handle form submission
        if (isset($_POST['vehicle_lookup_analytics_submit']) && 
            check_admin_referer('vehicle_lookup_analytics_settings')) {
            
            update_option('vehicle_lookup_ga_tracking_id', 
                sanitize_text_field($_POST['ga_tracking_id']));
            update_option('vehicle_lookup_gsc_verification_code', 
                sanitize_text_field($_POST['gsc_verification_code']));
            
            echo '<div class="notice notice-success"><p>Analytics settings saved!</p></div>';
        }
        
        $ga_id = $this->get_ga_tracking_id();
        $gsc_code = $this->get_gsc_verification_code();
        
        ?>
        <div class="wrap">
            <h1>Vehicle Lookup Analytics Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('vehicle_lookup_analytics_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ga_tracking_id">Google Analytics 4 Measurement ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="ga_tracking_id" 
                                   name="ga_tracking_id" 
                                   value="<?php echo esc_attr($ga_id); ?>" 
                                   class="regular-text"
                                   placeholder="G-XXXXXXXXXX">
                            <p class="description">
                                Enter your Google Analytics 4 Measurement ID (starts with G-).
                                <a href="https://support.google.com/analytics/answer/9539598" target="_blank">How to find it</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gsc_verification_code">Google Search Console Verification</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="gsc_verification_code" 
                                   name="gsc_verification_code" 
                                   value="<?php echo esc_attr($gsc_code); ?>" 
                                   class="regular-text"
                                   placeholder="abc123def456...">
                            <p class="description">
                                Enter the verification code from Google Search Console (content attribute value only).
                                <a href="https://support.google.com/webmasters/answer/9008080" target="_blank">How to verify</a>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="vehicle_lookup_analytics_submit" 
                           class="button button-primary" 
                           value="Save Settings">
                </p>
            </form>
            
            <hr>
            
            <h2>Setup Instructions</h2>
            
            <div class="card">
                <h3>Google Analytics 4 Setup</h3>
                <ol>
                    <li>Go to <a href="https://analytics.google.com" target="_blank">Google Analytics</a></li>
                    <li>Create a GA4 property if you haven't already</li>
                    <li>Copy your Measurement ID (starts with G-)</li>
                    <li>Paste it in the field above and save</li>
                </ol>
            </div>
            
            <div class="card">
                <h3>Google Search Console Setup</h3>
                <ol>
                    <li>Go to <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                    <li>Add your property (website URL)</li>
                    <li>Choose "HTML tag" verification method</li>
                    <li>Copy the content attribute value from the meta tag</li>
                    <li>Paste it in the field above and save</li>
                    <li>Return to Search Console and click "Verify"</li>
                </ol>
            </div>
            
            <div class="card">
                <h3>Submit Sitemap</h3>
                <p>After verifying your site in Search Console:</p>
                <ol>
                    <li>Go to Sitemaps in the left menu</li>
                    <li>Add new sitemap URL: <code><?php echo home_url('/vehicle-sitemap.xml'); ?></code></li>
                    <li>Click "Submit"</li>
                </ol>
            </div>
        </div>
        <?php
    }
}
