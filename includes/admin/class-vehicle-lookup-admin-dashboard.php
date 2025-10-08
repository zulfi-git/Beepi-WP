<?php

/**
 * Vehicle Lookup Admin Dashboard Class
 * 
 * Handles the dashboard page rendering and metrics calculation.
 */
class Vehicle_Lookup_Admin_Dashboard {

    private $db_handler;

    public function __construct() {
        $this->db_handler = new Vehicle_Lookup_Database();
    }

    /**
     * Render dashboard page
     */
    public function render() {
        $db_handler = new Vehicle_Lookup_Database();
        $today = date('Y-m-d');

        // Get real quota usage
        $quota_used = $db_handler->get_daily_quota($today);
        $quota_limit = get_option('vehicle_lookup_daily_quota', 5000);

        // Get real hourly rate limit stats
        $current_hour = date('Y-m-d-H');
        $rate_limit_total = $this->get_hourly_rate_limit_usage($current_hour);

        // Get real lookup stats
        $stats = $this->get_lookup_stats();

        // Calculate business metrics
        $quota_percentage = $quota_limit > 0 ? ($quota_used / $quota_limit) * 100 : 0;
        $trend_data = $this->get_usage_trend();
        $trend_direction = $trend_data['direction'];
        $trend_percentage = $trend_data['percentage'];
        
        ?>
        <div class="wrap vehicle-lookup-admin">
            <h1><span class="dashicons dashicons-car"></span> Vehicle Lookup Dashboard</h1>
            <div class="plugin-version" style="margin-bottom: 20px; color: #646970; font-size: 13px;">
                Plugin Version: <?php echo VEHICLE_LOOKUP_VERSION; ?>
            </div>

            <!-- Business Owner View -->
            <div class="business-overview">
                <h2><span class="dashicons dashicons-chart-line"></span> Business Overview</h2>
                
                <!-- Overall Service Status -->
                <div class="overall-status">
                    <div class="status-indicator" id="overall-status">
                        <span class="status-light checking"></span>
                        <span class="status-text">Checking Service Status...</span>
                    </div>
                </div>

                <!-- Key Business Metrics -->
                <div class="business-metrics">
                    <div class="metric-card primary">
                        <div class="metric-header">
                            <span class="dashicons dashicons-search"></span>
                            <h3>Today's Lookups</h3>
                        </div>
                        <div class="metric-content">
                            <div class="big-number"><?php echo number_format($stats['today_total']); ?></div>
                            <?php if ($trend_percentage !== null && $trend_percentage >= 0): ?>
                            <div class="metric-trend <?php echo $trend_direction; ?>">
                                <?php if ($trend_percentage > 0): ?>
                                    <span class="dashicons dashicons-arrow-<?php echo $trend_direction === 'up' ? 'up-alt' : 'down-alt'; ?>"></span>
                                    <?php echo $trend_direction === 'up' ? '+' : ''; ?><?php echo $trend_percentage; ?>% vs yesterday
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus"></span>
                                    No change vs yesterday
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="metric-trend">
                                <span class="dashicons dashicons-chart-line"></span>
                                No comparison data
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>


                    <div class="metric-card">
                        <div class="metric-header">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <h3>Success Rate</h3>
                        </div>
                        <div class="metric-content">
                            <div class="big-number"><?php echo $stats['success_rate']; ?>%</div>
                            <div class="metric-status <?php echo $stats['success_rate'] >= 95 ? 'good' : 'warning'; ?>">
                                <?php echo $stats['success_rate'] >= 95 ? 'Excellent' : 'Needs Attention'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-header">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <h3>API Costs</h3>
                        </div>
                        <div class="metric-content">
                            <div class="big-number"><?php echo number_format($quota_used); ?></div>
                            <div class="metric-status <?php echo $quota_percentage < 80 ? 'good' : 'warning'; ?>">
                                <?php echo round($quota_percentage, 1); ?>% of daily quota
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, $quota_percentage); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Status Overview -->
                <div class="service-overview">
                    <h3>Critical Services</h3>
                    <div class="service-status-grid">
                        <div class="service-item">
                            <div class="service-logo cloudflare-logo">
                                <img src="<?php echo VEHICLE_LOOKUP_PLUGIN_URL . 'assets/images/cloudflare-logo.png'; ?>" alt="Cloudflare" width="24" height="24">
                            </div>
                            <div class="service-info">
                                <div class="service-name">Cloudflare Worker</div>
                                <div class="service-status" id="cloudflare-status">
                                    <span class="status-light checking"></span>
                                    <span class="status-text">Checking...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-item">
                            <div class="service-logo vegvesen-logo">
                                <img src="<?php echo VEHICLE_LOOKUP_PLUGIN_URL . 'assets/images/vegvesen-logo.webp'; ?>" alt="Statens Vegvesen" width="24" height="24">
                            </div>
                            <div class="service-info">
                                <div class="service-name">Vegvesen API</div>
                                <div class="service-status" id="vegvesen-status">
                                    <span class="status-light unknown"></span>
                                    <span class="status-text">Pending...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-item">
                            <div class="service-logo openai-logo">
                                <img src="<?php echo VEHICLE_LOOKUP_PLUGIN_URL . 'assets/images/open-ai-logo.png'; ?>" alt="OpenAI" width="24" height="24">
                            </div>
                            <div class="service-info">
                                <div class="service-name">AI Summary Service</div>
                                <div class="service-status" id="ai-summary-status">
                                    <span class="status-light unknown"></span>
                                    <span class="status-text">Pending...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer View -->
            <div class="developer-section">
                <div class="section-header">
                    <h2><span class="dashicons dashicons-admin-tools"></span> Developer & Technical Details</h2>
                </div>
                
                <div class="developer-content" id="developer-content">
                    <!-- Technical Metrics -->
                    <div class="tech-metrics">
                        <div class="tech-card">
                            <h4><span class="dashicons dashicons-clock"></span> Rate Limiting</h4>
                            <div class="tech-stats">
                                <div class="tech-stat">
                                    <strong><?php echo $rate_limit_total; ?></strong>
                                    <span>Requests this hour</span>
                                </div>
                                <div class="tech-stat">
                                    <strong><?php echo number_format($quota_limit - $quota_used); ?></strong>
                                    <span>Quota remaining</span>
                                </div>
                            </div>
                        </div>

                        <div class="tech-card">
                            <h4><span class="dashicons dashicons-admin-comments"></span> AI Summary Service</h4>
                            <div class="tech-stats">
                                <div class="tech-stat" id="ai-model-info">
                                    <strong>-</strong>
                                    <span>Model in use</span>
                                </div>
                                <div class="tech-stat" id="ai-cache-entries">
                                    <strong>-</strong>
                                    <span>Cached summaries</span>
                                </div>
                                <div class="tech-stat" id="ai-timeout-setting">
                                    <strong>-</strong>
                                    <span>Timeout (ms)</span>
                                </div>
                                <div class="tech-stat" id="ai-active-generations">
                                    <strong>-</strong>
                                    <span>Active generations</span>
                                </div>
                                <div class="tech-stat" id="ai-success-rate">
                                    <strong>-</strong>
                                    <span>AI success rate</span>
                                </div>
                                <div class="tech-stat" id="ai-avg-generation-time">
                                    <strong>-</strong>
                                    <span>Avg generation time</span>
                                </div>
                            </div>
                        </div>

                        <div class="tech-card">
                            <h4><span class="dashicons dashicons-clock"></span> Performance Tracking</h4>
                            <div class="tech-stats">
                                <div class="tech-stat" id="vehicle-latency">
                                    <strong>-</strong>
                                    <span>Vehicle API latency</span>
                                </div>
                                <div class="tech-stat" id="ai-latency">
                                    <strong>-</strong>
                                    <span>AI API latency</span>
                                </div>
                            </div>
                        </div>

                        <div class="tech-card">
                            <h4><span class="dashicons dashicons-shield"></span> Circuit Breaker Status</h4>
                            <div class="tech-stats">
                                <div class="tech-stat" id="vehicle-circuit-status">
                                    <strong>-</strong>
                                    <span>Vehicle circuit</span>
                                </div>
                                <div class="tech-stat" id="ai-circuit-status">
                                    <strong>-</strong>
                                    <span>AI circuit</span>
                                </div>
                                <div class="tech-stat" id="circuit-success-rate">
                                    <strong>-</strong>
                                    <span>Success rate</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Activity Stats -->
                    <div class="detailed-activity">
                        <h4><span class="dashicons dashicons-chart-area"></span> Detailed Activity</h4>
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
                        </div>
                    </div>

                    <!-- Advanced API Details -->
                    <div class="api-monitoring">
                        <h4><span class="dashicons dashicons-admin-plugins"></span> API Monitoring</h4>
                        <div class="api-details" id="api-details" style="display: none;"></div>
                        <div class="monitoring-data" id="monitoring-data" style="display: none;"></div>
                    </div>

                    
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get hourly rate limit usage
     */
    private function get_hourly_rate_limit_usage($current_hour) {
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

    /**
     * Get lookup statistics
     */
    private function get_lookup_stats() {
        $today = date('Y-m-d');
        $start_date = $today . ' 00:00:00';
        $end_date = $today . ' 23:59:59';

        $stats = $this->db_handler->get_stats($start_date, $end_date);

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

    /**
     * Get usage trend
     */
    private function get_usage_trend() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        // Get today's count
        $today_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE DATE(lookup_time) = CURDATE()
        ");
        
        // Get yesterday's count
        $yesterday_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE DATE(lookup_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ");
        
        if ($yesterday_count == 0) {
            return array(
                'direction' => 'up',
                'percentage' => 0
            );
        }
        
        $percentage = round(abs(($today_count - $yesterday_count) / $yesterday_count) * 100, 1);
        $direction = ($today_count >= $yesterday_count) ? 'up' : 'down';
        
        return array(
            'direction' => $direction,
            'percentage' => $percentage
        );
    }
}
