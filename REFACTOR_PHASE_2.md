# Refactor Phase 2: Admin Class Split

**Status:** Ready for Implementation (After Phase 1)  
**Estimated Time:** 3-5 days  
**Risk Level:** Medium  
**Goal:** Break down monolithic Admin Class into focused, testable components

---

## Overview

The current `Vehicle_Lookup_Admin` class (1,197 lines) handles too many responsibilities. This phase splits it into 4 focused classes while maintaining all existing functionality.

**Key Principle:** This is a **refactoring**, not a rewrite. All functionality must remain identical from the user's perspective.

---

## Target Architecture

### New Class Structure

```
Vehicle_Lookup_Admin (Core Coordinator - ~150 lines)
├── Vehicle_Lookup_Admin_Settings (~250 lines)
│   ├── Settings registration
│   ├── Settings page rendering
│   └── Field callbacks
│
├── Vehicle_Lookup_Admin_Dashboard (~450 lines)
│   ├── Dashboard page rendering
│   ├── Business metrics calculation
│   ├── Technical metrics display
│   └── Health monitoring
│
├── Vehicle_Lookup_Admin_Analytics (~400 lines)
│   ├── Analytics page rendering
│   ├── Detailed statistics
│   ├── Popular searches
│   └── Historical data queries
│
└── Vehicle_Lookup_Admin_Ajax (~250 lines)
    ├── API connectivity tests
    ├── Health checks
    ├── Cache management
    └── Analytics reset
```

### Shared Dependencies

All classes will have access to:
- `Vehicle_Lookup_Database` - For data queries
- `VehicleLookupCache` - For cache operations
- WordPress settings API - For options
- AJAX nonce verification - For security

---

## Class 1: Vehicle_Lookup_Admin (Core)

**Purpose:** Coordinate sub-classes and manage WordPress admin integration

**File:** `includes/class-vehicle-lookup-admin.php`

**Responsibilities:**
1. Initialize sub-classes
2. Register admin menu pages
3. Enqueue scripts and styles
4. Ensure database table exists
5. Delegate to appropriate sub-class

**Public Interface:**
```php
class Vehicle_Lookup_Admin {
    private $settings;
    private $dashboard;
    private $analytics;
    private $ajax;
    
    public function init() {
        // Initialize sub-classes
        $this->settings = new Vehicle_Lookup_Admin_Settings();
        $this->dashboard = new Vehicle_Lookup_Admin_Dashboard();
        $this->analytics = new Vehicle_Lookup_Admin_Analytics();
        $this->ajax = new Vehicle_Lookup_Admin_Ajax();
        
        // Register WordPress hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this->settings, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialize AJAX handlers
        $this->ajax->register_handlers();
        
        // Ensure database
        $this->ensure_database_table();
    }
    
    public function add_admin_menu() {
        // Dashboard
        add_menu_page(
            'Vehicle Lookup',
            'Vehicle Lookup',
            'manage_options',
            'vehicle-lookup',
            array($this->dashboard, 'render'),
            'dashicons-car',
            30
        );
        
        // Settings
        add_submenu_page(
            'vehicle-lookup',
            'Settings',
            'Settings',
            'manage_options',
            'vehicle-lookup-settings',
            array($this->settings, 'render')
        );
        
        // Analytics
        add_submenu_page(
            'vehicle-lookup',
            'Analytics',
            'Analytics',
            'manage_options',
            'vehicle-lookup-analytics',
            array($this->analytics, 'render')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        // Load assets for Vehicle Lookup admin pages only
        if (strpos($hook, 'vehicle-lookup') === false) {
            return;
        }
        
        // Enqueue admin CSS and JS (unchanged from current)
        // ...
    }
    
    private function ensure_database_table() {
        $db_handler = new Vehicle_Lookup_Database();
        $db_handler->create_table();
    }
}
```

**Lines:** ~150 (down from 1,197)

---

## Class 2: Vehicle_Lookup_Admin_Settings

**Purpose:** Handle all settings-related functionality

**File:** `includes/admin/class-vehicle-lookup-admin-settings.php`

**Responsibilities:**
1. Register WordPress settings
2. Render settings sections and fields
3. Handle field callbacks
4. Validate and sanitize settings

**Public Interface:**
```php
class Vehicle_Lookup_Admin_Settings {
    
    /**
     * Register all plugin settings
     * Called by WordPress admin_init hook
     */
    public function init_settings() {
        // Register settings
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_worker_url');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_timeout');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_rate_limit');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_cache_duration');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_daily_quota');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_log_retention');
        
        // Add settings sections
        $this->add_settings_sections();
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Render settings page
     */
    public function render() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('vehicle_lookup_messages', 'vehicle_lookup_message', 
                'Settings Saved', 'updated');
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
    
    // Field callbacks
    public function worker_url_field() { /* ... */ }
    public function timeout_field() { /* ... */ }
    public function rate_limit_field() { /* ... */ }
    public function cache_duration_field() { /* ... */ }
    public function daily_quota_field() { /* ... */ }
    public function log_retention_field() { /* ... */ }
    
    // Private helpers
    private function add_settings_sections() { /* ... */ }
    private function add_settings_fields() { /* ... */ }
}
```

**Methods Moved From Admin Class:**
- `init_settings()` (line 75)
- `settings_page()` → `render()` (line 496)
- `worker_url_field()` (line 633)
- `timeout_field()` (line 639)
- `rate_limit_field()` (line 645)
- `cache_duration_field()` (line 651)
- `daily_quota_field()` (line 658)
- `log_retention_field()` (line 666)

**Lines:** ~250

---

## Class 3: Vehicle_Lookup_Admin_Dashboard

**Purpose:** Business and technical dashboard with live metrics

**File:** `includes/admin/class-vehicle-lookup-admin-dashboard.php`

**Responsibilities:**
1. Render dashboard page with metrics
2. Calculate business metrics (quota, trends, usage)
3. Display technical health status
4. Show cache and rate limit statistics
5. Provide real-time monitoring data

**Public Interface:**
```php
class Vehicle_Lookup_Admin_Dashboard {
    
    private $db_handler;
    
    public function __construct() {
        $this->db_handler = new Vehicle_Lookup_Database();
    }
    
    /**
     * Render dashboard page
     */
    public function render() {
        $today = date('Y-m-d');
        
        // Get live metrics (no stale data)
        $quota_used = $this->db_handler->get_daily_quota($today);
        $quota_limit = get_option('vehicle_lookup_daily_quota', 5000);
        
        $current_hour = date('Y-m-d-H');
        $rate_limit_stats = $this->get_hourly_rate_limit_usage($current_hour);
        
        $cache_stats = $this->get_cache_stats();
        $lookup_stats = $this->get_lookup_stats();
        
        // Calculate business metrics
        $quota_percentage = $quota_limit > 0 ? ($quota_used / $quota_limit) * 100 : 0;
        $trend_data = $this->get_usage_trend();
        
        // Render dashboard HTML
        $this->render_dashboard_html($quota_used, $quota_limit, $quota_percentage, 
            $rate_limit_stats, $cache_stats, $lookup_stats, $trend_data);
    }
    
    /**
     * Get real-time hourly rate limit usage
     * NO cached data - always current
     */
    private function get_hourly_rate_limit_usage($current_hour) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        $rate_limit = get_option('vehicle_lookup_rate_limit', VEHICLE_LOOKUP_RATE_LIMIT);
        
        // Count lookups in current hour
        $hour_start = str_replace('-', '-', $current_hour) . ':00:00';
        $hour_end = str_replace('-', '-', $current_hour) . ':59:59';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE lookup_time BETWEEN %s AND %s",
            $hour_start, $hour_end
        ));
        
        return array(
            'current' => (int) $total,
            'limit' => (int) $rate_limit,
            'percentage' => $rate_limit > 0 ? ($total / $rate_limit) * 100 : 0
        );
    }
    
    /**
     * Get real-time cache statistics
     */
    private function get_cache_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        $today = date('Y-m-d');
        
        // Cache hits/misses from today
        $cache_hits = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE DATE(lookup_time) = %s AND cached = 1",
            $today
        ));
        
        $cache_misses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE DATE(lookup_time) = %s AND cached = 0",
            $today
        ));
        
        $total = $cache_hits + $cache_misses;
        $hit_rate = $total > 0 ? ($cache_hits / $total) * 100 : 0;
        
        return array(
            'hits' => (int) $cache_hits,
            'misses' => (int) $cache_misses,
            'total' => (int) $total,
            'hit_rate' => round($hit_rate, 1)
        );
    }
    
    /**
     * Get today's lookup statistics
     * Real-time data, no caching
     */
    private function get_lookup_stats() {
        $today = date('Y-m-d');
        $start_date = $today . ' 00:00:00';
        $end_date = $today . ' 23:59:59';
        
        $stats = $this->db_handler->get_stats($start_date, $end_date);
        
        if (!$stats) {
            return $this->get_empty_stats();
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
     * Calculate usage trend vs yesterday
     */
    private function get_usage_trend() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $today_stats = $this->db_handler->get_stats(
            $today . ' 00:00:00', 
            $today . ' 23:59:59'
        );
        
        $yesterday_stats = $this->db_handler->get_stats(
            $yesterday . ' 00:00:00', 
            $yesterday . ' 23:59:59'
        );
        
        $today_count = $today_stats ? $today_stats->total_lookups : 0;
        $yesterday_count = $yesterday_stats ? $yesterday_stats->total_lookups : 0;
        
        if ($yesterday_count == 0) {
            return array('direction' => null, 'percentage' => null);
        }
        
        $percentage_change = (($today_count - $yesterday_count) / $yesterday_count) * 100;
        
        return array(
            'direction' => $percentage_change >= 0 ? 'up' : 'down',
            'percentage' => round(abs($percentage_change))
        );
    }
    
    /**
     * Render dashboard HTML
     * Contains all the HTML from current admin_page() method
     */
    private function render_dashboard_html($quota_used, $quota_limit, $quota_percentage,
                                          $rate_limit_stats, $cache_stats, $lookup_stats, 
                                          $trend_data) {
        // Full HTML rendering from current admin_page() method (lines 205-495)
        ?>
        <div class="wrap vehicle-lookup-admin">
            <!-- Dashboard HTML -->
        </div>
        <?php
    }
    
    private function get_empty_stats() {
        return array(
            'today_total' => 0,
            'today_success' => 0,
            'today_failed' => 0,
            'today_invalid_plates' => 0,
            'today_http_errors' => 0,
            'success_rate' => 0
        );
    }
}
```

**Methods Moved From Admin Class:**
- `admin_page()` → `render()` (line 181)
- `get_hourly_rate_limit_usage()` (line 673)
- `get_cache_stats()` (line 693)
- `get_lookup_stats()` (line 726)
- `get_usage_trend()` (line 1165)
- `calculate_avg_response_time()` (line 1146) - if used

**Lines:** ~450

**Key Features:**
- ✅ All metrics are real-time (no stale data)
- ✅ Clear separation between business and technical views
- ✅ Performance-optimized queries
- ✅ Proper null handling

---

## Class 4: Vehicle_Lookup_Admin_Analytics

**Purpose:** Detailed analytics and historical data

**File:** `includes/admin/class-vehicle-lookup-admin-analytics.php`

**Responsibilities:**
1. Render analytics page
2. Calculate detailed statistics for multiple time periods
3. Display popular searches
4. Provide data export capabilities (future)

**Public Interface:**
```php
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
        
        // Render analytics HTML
        $this->render_analytics_html($stats);
    }
    
    /**
     * Get comprehensive statistics for all time periods
     */
    private function get_detailed_stats() {
        // Today's stats
        $today = date('Y-m-d');
        $today_stats = $this->db_handler->get_stats(
            $today . ' 00:00:00', 
            $today . ' 23:59:59'
        );
        
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
            'today' => $this->format_period_stats($today_stats),
            'week' => $this->format_period_stats($week_stats),
            'month' => $this->format_period_stats($month_stats),
            'popular' => $this->format_popular_searches($popular_searches)
        );
    }
    
    /**
     * Get most searched registration numbers
     */
    private function get_most_searched_numbers($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                reg_number as registration_number,
                COUNT(*) as search_count,
                MAX(lookup_time) as last_searched,
                MAX(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as has_valid_result,
                MAX(CASE WHEN status != 'success' THEN error_type ELSE NULL END) as last_failure_type
            FROM {$table_name}
            GROUP BY reg_number
            ORDER BY search_count DESC
            LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Format statistics for a time period
     */
    private function format_period_stats($stats) {
        if (!$stats) {
            return array(
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'rate' => 0,
                'invalid_plates' => 0,
                'http_errors' => 0
            );
        }
        
        return array(
            'total' => (int) $stats->total_lookups,
            'success' => (int) $stats->successful_lookups,
            'failed' => (int) $stats->failed_lookups,
            'rate' => $stats->total_lookups > 0 ? 
                round(($stats->successful_lookups / $stats->total_lookups) * 100) : 0,
            'invalid_plates' => (int) $stats->invalid_plates,
            'http_errors' => (int) $stats->http_errors
        );
    }
    
    /**
     * Format popular search results
     */
    private function format_popular_searches($searches) {
        return array_map(function($search) {
            return array(
                'reg_number' => $search->registration_number,
                'count' => (int) $search->search_count,
                'last_search' => strtotime($search->last_searched),
                'has_valid_result' => (bool) $search->has_valid_result,
                'failure_reason' => $search->last_failure_type
            );
        }, $searches);
    }
    
    /**
     * Render analytics HTML
     */
    private function render_analytics_html($stats) {
        // Full HTML from current analytics_page() method (lines 520-630)
        ?>
        <div class="wrap vehicle-lookup-admin">
            <!-- Analytics HTML with action buttons and tables -->
        </div>
        <?php
    }
}
```

**Methods Moved From Admin Class:**
- `analytics_page()` → `render()` (line 517)
- `get_detailed_stats()` (line 757)
- `get_most_searched_numbers()` (line 817)

**Lines:** ~400

---

## Class 5: Vehicle_Lookup_Admin_Ajax

**Purpose:** Handle all AJAX requests for admin functionality

**File:** `includes/admin/class-vehicle-lookup-admin-ajax.php`

**Responsibilities:**
1. API connectivity testing
2. Upstream health checks
3. Cache management (worker and local)
4. Analytics data reset
5. Security validation for all requests

**Public Interface:**
```php
class Vehicle_Lookup_Admin_Ajax {
    
    /**
     * Register all AJAX handlers
     * Called by Vehicle_Lookup_Admin::init()
     */
    public function register_handlers() {
        add_action('wp_ajax_vehicle_lookup_test_api', array($this, 'test_api_connectivity'));
        add_action('wp_ajax_vehicle_lookup_check_upstream', array($this, 'check_upstream_health'));
        add_action('wp_ajax_clear_worker_cache', array($this, 'handle_clear_worker_cache'));
        add_action('wp_ajax_clear_local_cache', array($this, 'handle_clear_local_cache'));
        add_action('wp_ajax_reset_analytics_data', array($this, 'reset_analytics_data'));
    }
    
    /**
     * Test API connectivity to worker
     */
    public function test_api_connectivity() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);
        
        $response = wp_remote_get($worker_url . '/health', array(
            'headers' => array('Origin' => get_site_url()),
            'timeout' => $timeout
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            wp_send_json_success(json_decode($raw_body, true));
        } else {
            wp_send_json_error('Health check failed with status: ' . $status_code);
        }
    }
    
    /**
     * Check upstream health with intelligent caching
     */
    public function check_upstream_health() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check for manual refresh parameter
        $force_refresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] === 'true';
        
        // Cache configuration
        $cache_key = 'vehicle_lookup_health_check';
        $cache_ttl = 420; // 7 minutes
        
        // Return cached result unless force refresh
        if (!$force_refresh) {
            $cached_result = get_transient($cache_key);
            if ($cached_result !== false) {
                $cached_result['message'] = 'Health check completed (cached).';
                $cached_result['cached'] = true;
                $cached_result['cache_expires_in'] = 
                    get_option('_transient_timeout_' . $cache_key) - time();
                wp_send_json_success($cached_result);
                return;
            }
        }
        
        // Perform fresh health check
        $result = $this->perform_health_check();
        
        if ($result['success']) {
            // Cache successful results
            set_transient($cache_key, $result['data'], $cache_ttl);
            $result['data']['cached'] = false;
            $result['data']['message'] = 'Health check completed (live).';
            wp_send_json_success($result['data']);
        } else {
            // Don't cache errors
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Perform actual health check (extracted for testability)
     */
    private function perform_health_check() {
        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);
        
        $response = wp_remote_get($worker_url . '/health', array(
            'headers' => array('Origin' => get_site_url()),
            'timeout' => $timeout
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => array(
                    'message' => 'Health check failed: ' . $response->get_error_message(),
                    'status' => 'unknown'
                )
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 200 && isset($body['status'])) {
            return array(
                'success' => true,
                'data' => $body
            );
        }
        
        return array(
            'success' => false,
            'error' => array(
                'message' => 'Health check returned status: ' . $status_code,
                'status' => 'error'
            )
        );
    }
    
    /**
     * Clear worker (remote) cache
     */
    public function handle_clear_worker_cache() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
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
     * Clear local (WordPress transient) cache
     */
    public function handle_clear_local_cache() {
        check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        global $wpdb;
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_vehicle_cache_%'"
        );
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_vehicle_cache_%'"
        );
        
        wp_send_json_success(array(
            'message' => "Local cache cleared successfully ({$deleted} entries removed)"
        ));
    }
    
    /**
     * Reset all analytics data
     */
    public function reset_analytics_data() {
        // Comprehensive permission and nonce checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(array('message' => 'Security token missing'));
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'vehicle_lookup_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        // Verify table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            wp_send_json_error(array('message' => 'Analytics table does not exist'));
            return;
        }
        
        // Count before deletion
        $count_before = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        
        // Delete all records
        $result = $wpdb->query("DELETE FROM {$table_name}");
        
        if ($result !== false) {
            // Clear cached data
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
}
```

**Methods Moved From Admin Class:**
- `test_api_connectivity()` (line 843)
- `check_upstream_health()` (line 871)
- `handle_clear_worker_cache()` (line 1106)
- `handle_clear_local_cache()` (line 1126)
- `reset_analytics_data()` (line 1022)

**Lines:** ~250

**New Features:**
- ✅ Added `force_refresh` parameter for health checks
- ✅ Better error handling and logging
- ✅ Extracted testable helper methods
- ✅ Consistent permission checking

---

## Directory Structure

```
includes/
├── class-vehicle-lookup-admin.php           (Core coordinator - 150 lines)
└── admin/
    ├── class-vehicle-lookup-admin-settings.php    (250 lines)
    ├── class-vehicle-lookup-admin-dashboard.php   (450 lines)
    ├── class-vehicle-lookup-admin-analytics.php   (400 lines)
    └── class-vehicle-lookup-admin-ajax.php        (250 lines)
```

---

## Migration Strategy

### Step 1: Create New Classes (No Breaking Changes)
1. Create `includes/admin/` directory
2. Create 4 new classes with all moved methods
3. Keep original `Vehicle_Lookup_Admin` unchanged
4. No WordPress integration yet

### Step 2: Update Core Admin Class
1. Add private properties for sub-classes
2. Initialize sub-classes in `init()`
3. Update `add_admin_menu()` to delegate
4. Remove moved methods from original class

### Step 3: Test Thoroughly
1. Load each admin page (Dashboard, Settings, Analytics)
2. Test all AJAX endpoints
3. Verify no JavaScript errors
4. Check all metrics display correctly
5. Test cache clearing
6. Test analytics reset

### Step 4: Update Autoloader
Add new classes to main plugin file:
```php
require_once VEHICLE_LOOKUP_DIR . 'includes/admin/class-vehicle-lookup-admin-settings.php';
require_once VEHICLE_LOOKUP_DIR . 'includes/admin/class-vehicle-lookup-admin-dashboard.php';
require_once VEHICLE_LOOKUP_DIR . 'includes/admin/class-vehicle-lookup-admin-analytics.php';
require_once VEHICLE_LOOKUP_DIR . 'includes/admin/class-vehicle-lookup-admin-ajax.php';
```

---

## Testing Checklist

### Dashboard Page
- [ ] Page loads without errors
- [ ] Quota displays correctly
- [ ] Rate limit shows current hour usage
- [ ] Cache stats are accurate
- [ ] Today's stats display
- [ ] Trend direction is correct
- [ ] Service status check works
- [ ] No stale/cached data issues

### Settings Page
- [ ] Page loads without errors
- [ ] All fields display current values
- [ ] Can save worker URL
- [ ] Can save timeout
- [ ] Can save rate limit
- [ ] Can save cache duration
- [ ] Can save daily quota
- [ ] Can save log retention
- [ ] Success message appears after save

### Analytics Page
- [ ] Page loads without errors
- [ ] Today's stats display
- [ ] Week stats display
- [ ] Month stats display
- [ ] Popular searches table displays
- [ ] Clear worker cache button works
- [ ] Clear local cache button works
- [ ] Reset analytics button works (with confirmation)

### AJAX Endpoints
- [ ] Test API connectivity works
- [ ] Health check returns data
- [ ] Health check caches properly
- [ ] Force refresh bypasses cache
- [ ] Worker cache clear succeeds
- [ ] Local cache clear succeeds
- [ ] Analytics reset works
- [ ] All endpoints check permissions
- [ ] All endpoints verify nonce

---

## Live Metrics Improvements

### Added in Phase 2

**Dashboard Enhancements:**
1. Added "Refresh Now" button for health checks (bypasses cache)
2. Added last-updated timestamp to metric cards
3. Added cache indicator (live vs. cached)
4. All metrics query database directly (no stale data)

**Health Check Improvements:**
1. Support for `force_refresh` parameter
2. Clear indication of cached vs. live data
3. Cache expiration countdown
4. Better error handling

**Cache Statistics:**
1. Real-time cache hit/miss calculation
2. Today's cache performance
3. No reliance on stale transients

---

## Success Criteria

1. ✅ All 4 new classes created and working
2. ✅ Core admin class reduced to ~150 lines
3. ✅ No functionality lost or changed
4. ✅ All admin pages load correctly
5. ✅ All AJAX endpoints function properly
6. ✅ No JavaScript errors
7. ✅ Performance same or better
8. ✅ Code is more maintainable
9. ✅ Live metrics (no stale data)
10. ✅ Ready for unit tests (Phase 3)

---

## Risks & Mitigation

### Risk: Breaking Admin Functionality
**Likelihood:** Medium  
**Impact:** High  
**Mitigation:** 
- Thorough testing of all pages
- Test in staging environment first
- Keep git history for easy rollback

### Risk: AJAX Endpoints Fail
**Likelihood:** Low  
**Impact:** High  
**Mitigation:**
- Test each endpoint individually
- Verify nonce and permission checks
- Monitor browser console for errors

### Risk: Performance Regression
**Likelihood:** Low  
**Impact:** Medium  
**Mitigation:**
- Measure page load times before/after
- Optimize database queries
- Use proper caching where appropriate

---

## Next Steps

After Phase 2 completion:
1. **Phase 3:** Add unit tests for all new classes
2. **Phase 4:** Consider frontend JavaScript modularization
3. **Phase 5:** Add integration tests
4. **Phase 6:** Performance optimization

**See [REFACTOR_PHASE_3.md](./REFACTOR_PHASE_3.md) for testing strategy.**
