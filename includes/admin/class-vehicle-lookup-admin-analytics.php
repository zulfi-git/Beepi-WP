<?php

/**
 * Vehicle Lookup Admin Analytics Class
 * 
 * Handles the analytics page rendering and detailed statistics.
 */
class Vehicle_Lookup_Admin_Analytics {

    private $db_handler;

    public function __construct() {
        $this->db_handler = new Vehicle_Lookup_Database();
    }

    /**
     * Render analytics page
     */
    public function render() {
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

    /**
     * Get detailed statistics
     */
    private function get_detailed_stats() {

        // Today's stats
        $today = date('Y-m-d');
        $today_stats = $this->db_handler->get_stats($today . ' 00:00:00', $today . ' 23:59:59');

        // Week's stats
        $week_start = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00';
        $week_end = date('Y-m-d') . ' 23:59:59';
        $week_stats = $this->db_handler->get_stats($week_start, $week_end);

        // Month's stats
        $month_start = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
        $month_end = date('Y-m-d') . ' 23:59:59';
        $month_stats = $this->db_handler->get_stats($month_start, $month_end);

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

    /**
     * Get most searched numbers
     */
    private function get_most_searched_numbers($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                reg_number as registration_number,
                COUNT(*) as search_count,
                MAX(success) as has_valid_result,
                MAX(lookup_time) as last_searched,
                (SELECT failure_type FROM `{$table_name}` l2 
                 WHERE l2.reg_number = l1.reg_number 
                 AND l2.success = 0 
                 AND l2.failure_type IS NOT NULL 
                 ORDER BY l2.lookup_time DESC 
                 LIMIT 1) as last_failure_type
            FROM `{$table_name}` l1
            WHERE reg_number IS NOT NULL 
            AND reg_number != ''
            AND lookup_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY reg_number
            ORDER BY search_count DESC
            LIMIT %d
        ", $limit));
    }
}
