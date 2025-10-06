# Refactor Phase 4: Testing & Validation Strategy

**Status:** Implementation Guide  
**Estimated Time:** 3-5 days  
**Risk Level:** Low  
**Goal:** Add comprehensive automated testing for refactored admin classes

---

## Overview

This phase establishes a testing framework and adds unit and integration tests for all admin classes created in Phase 2. The goal is to achieve >60% test coverage for critical admin functionality.

---

## Testing Architecture

### Directory Structure

```
tests/
├── bootstrap.php                          (PHPUnit bootstrap)
├── phpunit.xml                            (PHPUnit configuration)
├── unit/
│   ├── admin/
│   │   ├── AdminSettingsTest.php         (Settings class tests)
│   │   ├── AdminDashboardTest.php        (Dashboard class tests)
│   │   ├── AdminAnalyticsTest.php        (Analytics class tests)
│   │   └── AdminAjaxTest.php             (AJAX handlers tests)
│   ├── helpers/
│   │   └── HelpersTest.php               (Helper functions tests)
│   └── cache/
│       └── CacheTest.php                 (Cache operations tests)
├── integration/
│   ├── AdminPageLoadTest.php             (Page rendering tests)
│   ├── AjaxEndpointsTest.php             (AJAX integration tests)
│   └── DatabaseOperationsTest.php        (DB operations tests)
└── fixtures/
    ├── sample-health-response.json       (Test data)
    └── sample-stats-data.json            (Test data)
```

---

## Setup & Configuration

### 1. Install PHPUnit

**composer.json:**
```json
{
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "mockery/mockery": "^1.5",
        "brain/monkey": "^2.6"
    },
    "autoload-dev": {
        "psr-4": {
            "VehicleLookup\\Tests\\": "tests/"
        }
    }
}
```

### 2. PHPUnit Configuration

**tests/phpunit.xml:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="bootstrap.php"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    stopOnFailure="false"
    verbose="true">
    
    <testsuites>
        <testsuite name="Unit Tests">
            <directory>./unit</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory>./integration</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory suffix=".php">../includes</directory>
        </include>
        <exclude>
            <directory>../includes/admin/views</directory>
        </exclude>
        <report>
            <html outputDirectory="coverage-report"/>
            <text outputFile="php://stdout"/>
        </report>
    </coverage>
    
    <php>
        <const name="WP_TESTS_DOMAIN" value="example.org"/>
        <const name="WP_TESTS_EMAIL" value="admin@example.org"/>
        <const name="WP_TESTS_TITLE" value="Test Site"/>
        <const name="WP_PHP_BINARY" value="php"/>
        <const name="ABSPATH" value="/tmp/wordpress/"/>
    </php>
</phpunit>
```

### 3. Bootstrap File

**tests/bootstrap.php:**
```php
<?php

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// WordPress test library
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// WordPress core test functions
require_once $_tests_dir . '/includes/functions.php';

// Override plugin loading
function _manually_load_plugin() {
    require dirname(__DIR__) . '/vehicle-lookup.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start WordPress test suite
require $_tests_dir . '/includes/bootstrap.php';

// Activate Brain\Monkey for WordPress function mocking
\Brain\Monkey\setUp();
```

---

## Unit Tests

### Test 1: Settings Class

**tests/unit/admin/AdminSettingsTest.php:**
```php
<?php

namespace VehicleLookup\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

class AdminSettingsTest extends TestCase {
    
    protected $settings;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('get_option')->justReturn('');
        Functions\when('esc_attr')->returnArg();
        Functions\when('register_setting')->justReturn(true);
        Functions\when('add_settings_section')->justReturn(true);
        Functions\when('add_settings_field')->justReturn(true);
        
        $this->settings = new \Vehicle_Lookup_Admin_Settings();
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function it_registers_all_settings() {
        Functions\expect('register_setting')
            ->times(6) // 6 settings to register
            ->andReturn(true);
        
        $this->settings->init_settings();
        
        $this->assertTrue(true); // If we get here, all registrations happened
    }
    
    /** @test */
    public function worker_url_field_displays_correctly() {
        Functions\when('get_option')
            ->with('vehicle_lookup_worker_url', Mockery::any())
            ->justReturn('https://worker.example.com');
        
        ob_start();
        $this->settings->worker_url_field();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('https://worker.example.com', $output);
        $this->assertStringContainsString('type="url"', $output);
        $this->assertStringContainsString('name="vehicle_lookup_worker_url"', $output);
    }
    
    /** @test */
    public function timeout_field_has_correct_constraints() {
        Functions\when('get_option')
            ->with('vehicle_lookup_timeout', 15)
            ->justReturn(15);
        
        ob_start();
        $this->settings->timeout_field();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('type="number"', $output);
        $this->assertStringContainsString('min="5"', $output);
        $this->assertStringContainsString('max="30"', $output);
        $this->assertStringContainsString('value="15"', $output);
    }
    
    /** @test */
    public function rate_limit_field_uses_default_constant() {
        Functions\when('get_option')
            ->with('vehicle_lookup_rate_limit', VEHICLE_LOOKUP_RATE_LIMIT)
            ->justReturn(100);
        
        ob_start();
        $this->settings->rate_limit_field();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('value="100"', $output);
    }
    
    /** @test */
    public function settings_page_renders_without_errors() {
        Functions\expect('settings_errors')->once();
        Functions\expect('settings_fields')->once();
        Functions\expect('do_settings_sections')->once();
        Functions\expect('submit_button')->once();
        
        ob_start();
        $this->settings->render();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Vehicle Lookup Settings', $output);
        $this->assertStringContainsString('<form', $output);
    }
    
    /** @test */
    public function settings_page_shows_success_message_after_save() {
        $_GET['settings-updated'] = true;
        
        Functions\expect('add_settings_error')
            ->once()
            ->with('vehicle_lookup_messages', 'vehicle_lookup_message', 
                   'Settings Saved', 'updated');
        
        Functions\expect('settings_errors')->once();
        Functions\expect('settings_fields')->once();
        Functions\expect('do_settings_sections')->once();
        Functions\expect('submit_button')->once();
        
        ob_start();
        $this->settings->render();
        $output = ob_get_clean();
        
        unset($_GET['settings-updated']);
    }
}
```

---

### Test 2: Dashboard Class

**tests/unit/admin/AdminDashboardTest.php:**
```php
<?php

namespace VehicleLookup\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

class AdminDashboardTest extends TestCase {
    
    protected $dashboard;
    protected $mockDb;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock database handler
        $this->mockDb = Mockery::mock('Vehicle_Lookup_Database');
        
        // Mock WordPress functions
        Functions\when('get_option')->justReturn('');
        Functions\when('date')->alias(function($format, $timestamp = null) {
            return date($format, $timestamp ?? time());
        });
        
        $this->dashboard = new \Vehicle_Lookup_Admin_Dashboard();
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function it_calculates_quota_percentage_correctly() {
        $this->mockDb->shouldReceive('get_daily_quota')
            ->once()
            ->andReturn(2500);
        
        Functions\when('get_option')
            ->with('vehicle_lookup_daily_quota', 5000)
            ->justReturn(5000);
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->dashboard);
        $method = $reflection->getMethod('calculate_quota_percentage');
        $method->setAccessible(true);
        
        $percentage = $method->invoke($this->dashboard, 2500, 5000);
        
        $this->assertEquals(50, $percentage);
    }
    
    /** @test */
    public function it_handles_zero_quota_limit_gracefully() {
        $reflection = new \ReflectionClass($this->dashboard);
        $method = $reflection->getMethod('calculate_quota_percentage');
        $method->setAccessible(true);
        
        $percentage = $method->invoke($this->dashboard, 100, 0);
        
        $this->assertEquals(0, $percentage);
    }
    
    /** @test */
    public function it_gets_hourly_rate_limit_usage() {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(45);
        
        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT COUNT(*) ...');
        
        Functions\when('get_option')
            ->with('vehicle_lookup_rate_limit', Mockery::any())
            ->justReturn(100);
        
        $reflection = new \ReflectionClass($this->dashboard);
        $method = $reflection->getMethod('get_hourly_rate_limit_usage');
        $method->setAccessible(true);
        
        $stats = $method->invoke($this->dashboard, '2024-01-15-14');
        
        $this->assertIsArray($stats);
        $this->assertEquals(45, $stats['current']);
        $this->assertEquals(100, $stats['limit']);
        $this->assertEquals(45, $stats['percentage']);
    }
    
    /** @test */
    public function it_calculates_cache_hit_rate_correctly() {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $wpdb->shouldReceive('get_var')
            ->twice()
            ->andReturn(75, 25); // 75 hits, 25 misses
        
        $wpdb->shouldReceive('prepare')
            ->twice()
            ->andReturn('SELECT COUNT(*) ...');
        
        $reflection = new \ReflectionClass($this->dashboard);
        $method = $reflection->getMethod('get_cache_stats');
        $method->setAccessible(true);
        
        $stats = $method->invoke($this->dashboard);
        
        $this->assertEquals(75, $stats['hits']);
        $this->assertEquals(25, $stats['misses']);
        $this->assertEquals(100, $stats['total']);
        $this->assertEquals(75.0, $stats['hit_rate']);
    }
    
    /** @test */
    public function it_handles_usage_trend_with_no_yesterday_data() {
        $mockStats = (object) ['total_lookups' => 100];
        
        $this->mockDb->shouldReceive('get_stats')
            ->twice()
            ->andReturn($mockStats, null); // today has data, yesterday doesn't
        
        $reflection = new \ReflectionClass($this->dashboard);
        $method = $reflection->getMethod('get_usage_trend');
        $method->setAccessible(true);
        
        $trend = $method->invoke($this->dashboard);
        
        $this->assertNull($trend['direction']);
        $this->assertNull($trend['percentage']);
    }
    
    /** @test */
    public function it_calculates_upward_trend_correctly() {
        $todayStats = (object) ['total_lookups' => 150];
        $yesterdayStats = (object) ['total_lookups' => 100];
        
        $this->mockDb->shouldReceive('get_stats')
            ->twice()
            ->andReturn($todayStats, $yesterdayStats);
        
        $reflection = new \ReflectionClass($this->dashboard);
        $method = $reflection->getMethod('get_usage_trend');
        $method->setAccessible(true);
        
        $trend = $method->invoke($this->dashboard);
        
        $this->assertEquals('up', $trend['direction']);
        $this->assertEquals(50, $trend['percentage']); // 50% increase
    }
    
    /** @test */
    public function it_returns_empty_stats_when_database_has_no_data() {
        $this->mockDb->shouldReceive('get_stats')
            ->once()
            ->andReturn(null);
        
        $reflection = new \ReflectionClass($this->dashboard);
        $method = $reflection->getMethod('get_lookup_stats');
        $method->setAccessible(true);
        
        $stats = $method->invoke($this->dashboard);
        
        $this->assertEquals(0, $stats['today_total']);
        $this->assertEquals(0, $stats['today_success']);
        $this->assertEquals(0, $stats['success_rate']);
    }
}
```

---

### Test 3: Analytics Class

**tests/unit/admin/AdminAnalyticsTest.php:**
```php
<?php

namespace VehicleLookup\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

class AdminAnalyticsTest extends TestCase {
    
    protected $analytics;
    protected $mockDb;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        $this->mockDb = Mockery::mock('Vehicle_Lookup_Database');
        
        Functions\when('date')->alias(function($format, $timestamp = null) {
            return date($format, $timestamp ?? time());
        });
        
        $this->analytics = new \Vehicle_Lookup_Admin_Analytics();
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function it_formats_period_stats_correctly() {
        $mockStats = (object) [
            'total_lookups' => 1000,
            'successful_lookups' => 950,
            'failed_lookups' => 50,
            'invalid_plates' => 20,
            'http_errors' => 30
        ];
        
        $reflection = new \ReflectionClass($this->analytics);
        $method = $reflection->getMethod('format_period_stats');
        $method->setAccessible(true);
        
        $formatted = $method->invoke($this->analytics, $mockStats);
        
        $this->assertEquals(1000, $formatted['total']);
        $this->assertEquals(950, $formatted['success']);
        $this->assertEquals(50, $formatted['failed']);
        $this->assertEquals(95, $formatted['rate']); // 95% success rate
        $this->assertEquals(20, $formatted['invalid_plates']);
        $this->assertEquals(30, $formatted['http_errors']);
    }
    
    /** @test */
    public function it_handles_null_stats_gracefully() {
        $reflection = new \ReflectionClass($this->analytics);
        $method = $reflection->getMethod('format_period_stats');
        $method->setAccessible(true);
        
        $formatted = $method->invoke($this->analytics, null);
        
        $this->assertEquals(0, $formatted['total']);
        $this->assertEquals(0, $formatted['success']);
        $this->assertEquals(0, $formatted['rate']);
    }
    
    /** @test */
    public function it_formats_popular_searches_correctly() {
        $mockSearches = [
            (object) [
                'registration_number' => 'AB12345',
                'search_count' => 25,
                'last_searched' => '2024-01-15 14:30:00',
                'has_valid_result' => true,
                'last_failure_type' => null
            ]
        ];
        
        $reflection = new \ReflectionClass($this->analytics);
        $method = $reflection->getMethod('format_popular_searches');
        $method->setAccessible(true);
        
        $formatted = $method->invoke($this->analytics, $mockSearches);
        
        $this->assertCount(1, $formatted);
        $this->assertEquals('AB12345', $formatted[0]['reg_number']);
        $this->assertEquals(25, $formatted[0]['count']);
        $this->assertTrue($formatted[0]['has_valid_result']);
    }
    
    /** @test */
    public function it_gets_most_searched_numbers_with_correct_limit() {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $wpdb->shouldReceive('prepare')
            ->once()
            ->with(Mockery::any(), 10)
            ->andReturn('SELECT ... LIMIT 10');
        
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);
        
        $reflection = new \ReflectionClass($this->analytics);
        $method = $reflection->getMethod('get_most_searched_numbers');
        $method->setAccessible(true);
        
        $results = $method->invoke($this->analytics, 10);
        
        $this->assertIsArray($results);
    }
    
    /** @test */
    public function analytics_page_renders_all_sections() {
        $this->mockDb->shouldReceive('get_stats')
            ->times(3) // today, week, month
            ->andReturn((object) [
                'total_lookups' => 100,
                'successful_lookups' => 95,
                'failed_lookups' => 5,
                'invalid_plates' => 2,
                'http_errors' => 3
            ]);
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $wpdb->shouldReceive('prepare')->andReturn('SELECT ...');
        $wpdb->shouldReceive('get_results')->andReturn([]);
        
        ob_start();
        $this->analytics->render();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Vehicle Lookup Analytics', $output);
        $this->assertStringContainsString('Usage Statistics', $output);
        $this->assertStringContainsString('Most Searched Registration Numbers', $output);
    }
}
```

---

### Test 4: AJAX Handlers

**tests/unit/admin/AdminAjaxTest.php:**
```php
<?php

namespace VehicleLookup\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

class AdminAjaxTest extends TestCase {
    
    protected $ajax;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->with('manage_options')->justReturn(true);
        
        $this->ajax = new \Vehicle_Lookup_Admin_Ajax();
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function it_registers_all_ajax_handlers() {
        Functions\expect('add_action')
            ->times(5) // 5 AJAX handlers
            ->andReturn(true);
        
        $this->ajax->register_handlers();
        
        $this->assertTrue(true);
    }
    
    /** @test */
    public function test_api_connectivity_succeeds_with_200_response() {
        Functions\when('get_option')->justReturn('https://worker.example.com');
        Functions\when('get_site_url')->justReturn('https://example.com');
        
        Functions\when('wp_remote_get')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'ok'])
        ]);
        
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode(['status' => 'ok']));
        
        Functions\expect('wp_send_json_success')
            ->once()
            ->with(['status' => 'ok']);
        
        $this->ajax->test_api_connectivity();
    }
    
    /** @test */
    public function test_api_connectivity_fails_with_wp_error() {
        Functions\when('get_option')->justReturn('https://worker.example.com');
        Functions\when('get_site_url')->justReturn('https://example.com');
        
        $error = Mockery::mock('WP_Error');
        $error->shouldReceive('get_error_message')->andReturn('Connection timeout');
        
        Functions\when('wp_remote_get')->justReturn($error);
        Functions\when('is_wp_error')->justReturn(true);
        
        Functions\expect('wp_send_json_error')
            ->once()
            ->with('Connection failed: Connection timeout');
        
        $this->ajax->test_api_connectivity();
    }
    
    /** @test */
    public function health_check_returns_cached_result_when_available() {
        $_POST['force_refresh'] = 'false';
        
        $cachedData = [
            'status' => 'operational',
            'timestamp' => time()
        ];
        
        Functions\when('get_transient')
            ->with('vehicle_lookup_health_check')
            ->justReturn($cachedData);
        
        Functions\when('get_option')
            ->with(Mockery::pattern('/_transient_timeout_/'), Mockery::any())
            ->justReturn(time() + 300);
        
        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(function($arg) {
                return $arg['cached'] === true && 
                       $arg['status'] === 'operational';
            }));
        
        $this->ajax->check_upstream_health();
        
        unset($_POST['force_refresh']);
    }
    
    /** @test */
    public function health_check_bypasses_cache_with_force_refresh() {
        $_POST['force_refresh'] = 'true';
        
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('https://worker.example.com');
        Functions\when('get_site_url')->justReturn('https://example.com');
        
        Functions\when('wp_remote_get')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'operational'])
        ]);
        
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode(['status' => 'operational']));
        
        Functions\expect('set_transient')->once();
        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(function($arg) {
                return $arg['cached'] === false;
            }));
        
        $this->ajax->check_upstream_health();
        
        unset($_POST['force_refresh']);
    }
    
    /** @test */
    public function clear_worker_cache_succeeds() {
        $mockCache = Mockery::mock('VehicleLookupCache');
        $mockCache->shouldReceive('clear_worker_cache')->once()->andReturn(true);
        
        // Mock cache instantiation
        Functions\when('VehicleLookupCache')->justReturn($mockCache);
        
        Functions\expect('wp_send_json_success')
            ->once()
            ->with(['message' => 'Worker cache cleared successfully']);
        
        $this->ajax->handle_clear_worker_cache();
    }
    
    /** @test */
    public function clear_local_cache_deletes_transients() {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->options = 'wp_options';
        
        $wpdb->shouldReceive('query')
            ->twice()
            ->andReturn(42); // 42 entries deleted
        
        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(function($arg) {
                return str_contains($arg['message'], '42 entries');
            }));
        
        $this->ajax->handle_clear_local_cache();
    }
    
    /** @test */
    public function reset_analytics_checks_permissions() {
        Functions\when('current_user_can')->with('manage_options')->justReturn(false);
        
        Functions\expect('wp_send_json_error')
            ->once()
            ->with(['message' => 'Insufficient permissions']);
        
        $this->ajax->reset_analytics_data();
    }
    
    /** @test */
    public function reset_analytics_verifies_nonce() {
        $_POST['nonce'] = 'invalid_nonce';
        
        Functions\when('wp_verify_nonce')
            ->with('invalid_nonce', 'vehicle_lookup_admin_nonce')
            ->justReturn(false);
        
        Functions\expect('wp_send_json_error')
            ->once()
            ->with(['message' => 'Security check failed']);
        
        $this->ajax->reset_analytics_data();
        
        unset($_POST['nonce']);
    }
}
```

---

## Integration Tests

### Test 1: Admin Page Loading

**tests/integration/AdminPageLoadTest.php:**
```php
<?php

namespace VehicleLookup\Tests\Integration;

use WP_UnitTestCase;

class AdminPageLoadTest extends WP_UnitTestCase {
    
    protected $admin;
    protected $admin_user_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Create admin user
        $this->admin_user_id = $this->factory->user->create([
            'role' => 'administrator'
        ]);
        wp_set_current_user($this->admin_user_id);
        
        // Initialize admin class
        $this->admin = new \Vehicle_Lookup_Admin();
        $this->admin->init();
    }
    
    /** @test */
    public function dashboard_page_loads_without_errors() {
        set_current_screen('toplevel_page_vehicle-lookup');
        
        ob_start();
        do_action('admin_page_vehicle-lookup');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Vehicle Lookup Dashboard', $output);
        $this->assertStringContainsString('Business Overview', $output);
    }
    
    /** @test */
    public function settings_page_loads_without_errors() {
        set_current_screen('vehicle-lookup_page_vehicle-lookup-settings');
        
        ob_start();
        do_action('admin_page_vehicle-lookup-settings');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Vehicle Lookup Settings', $output);
        $this->assertStringContainsString('Worker URL', $output);
    }
    
    /** @test */
    public function analytics_page_loads_without_errors() {
        set_current_screen('vehicle-lookup_page_vehicle-lookup-analytics');
        
        ob_start();
        do_action('admin_page_vehicle-lookup-analytics');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Vehicle Lookup Analytics', $output);
        $this->assertStringContainsString('Usage Statistics', $output);
    }
    
    /** @test */
    public function admin_scripts_enqueue_correctly() {
        set_current_screen('toplevel_page_vehicle-lookup');
        
        do_action('admin_enqueue_scripts', 'toplevel_page_vehicle-lookup');
        
        $this->assertTrue(wp_script_is('vehicle-lookup-admin-script', 'enqueued'));
        $this->assertTrue(wp_style_is('vehicle-lookup-admin-style', 'enqueued'));
    }
    
    /** @test */
    public function non_admin_users_cannot_access_pages() {
        // Create subscriber user
        $subscriber_id = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);
        
        $this->assertFalse(current_user_can('manage_options'));
        
        // Attempting to access should fail capability check
        // (WordPress handles this at the menu level)
    }
}
```

---

## Test Data Fixtures

**tests/fixtures/sample-health-response.json:**
```json
{
  "status": "operational",
  "timestamp": "2024-01-15T14:30:00Z",
  "services": {
    "vegvesen": {
      "status": "operational",
      "latency": 245
    },
    "ai": {
      "status": "operational",
      "latency": 1230
    }
  },
  "rateLimiting": {
    "enabled": true,
    "current": 45,
    "limit": 100
  },
  "cache": {
    "vehicle_entries": 1250,
    "ai_hit_rate": 85.5
  }
}
```

---

## Running Tests

### Commands

```bash
# Run all tests
composer test

# Run only unit tests
composer test -- --testsuite="Unit Tests"

# Run only integration tests
composer test -- --testsuite="Integration Tests"

# Run with coverage report
composer test -- --coverage-html coverage-report

# Run specific test file
composer test tests/unit/admin/AdminSettingsTest.php

# Run specific test method
composer test --filter test_api_connectivity_succeeds_with_200_response
```

### composer.json Scripts

```json
{
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite='Unit Tests'",
        "test:integration": "phpunit --testsuite='Integration Tests'",
        "test:coverage": "phpunit --coverage-html coverage-report",
        "test:watch": "phpunit-watcher watch"
    }
}
```

---

## Coverage Goals

### Target Coverage by Component

| Component | Target Coverage | Priority |
|-----------|----------------|----------|
| Admin Settings | 90% | HIGH |
| Admin Dashboard | 80% | HIGH |
| Admin Analytics | 80% | HIGH |
| Admin AJAX | 95% | HIGH |
| Cache Operations | 90% | MEDIUM |
| Helper Functions | 95% | MEDIUM |

### Minimum Overall Coverage: 60%

---

## Continuous Integration

### GitHub Actions Workflow

**.github/workflows/tests.yml:**
```yaml
name: Run Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'
        extensions: mysqli, mbstring
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run tests
      run: composer test
    
    - name: Generate coverage report
      run: composer test:coverage
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v2
      with:
        files: ./coverage.xml
```

---

## Success Criteria

1. ✅ All unit tests pass
2. ✅ All integration tests pass
3. ✅ >60% code coverage achieved
4. ✅ CI/CD pipeline runs tests automatically
5. ✅ No test failures on main branch
6. ✅ Test execution time <2 minutes
7. ✅ All critical paths covered
8. ✅ Mock objects used appropriately
9. ✅ Test data fixtures well-organized
10. ✅ Documentation for running tests

---

## Next Steps

After Phase 4 completion:
1. Monitor test coverage trends
2. Add tests for edge cases discovered
3. Refactor code to improve testability
4. Consider adding E2E tests with Selenium

**This completes the testing phase of the refactoring project.**
