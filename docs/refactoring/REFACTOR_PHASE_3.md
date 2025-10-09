# Refactor Phase 3: Live Metrics & Real-Time Monitoring

**Status:** Implementation Guide  
**Estimated Time:** 2-3 days  
**Risk Level:** Low  
**Goal:** Ensure all dashboard metrics use live data with no stale values

---

## Overview

This phase focuses on implementing truly live metrics in the dashboard, eliminating any stale or null status values, and adding proper monitoring capabilities for business, technical, and health data.

**Key Principle:** Users should always see current, accurate data with clear indicators of data freshness.

---

## Current State Analysis

### Metrics That Are Already Live ✅

1. **Daily Quota Usage**
   - Source: `Vehicle_Lookup_Database::get_daily_quota()`
   - Updates: Real-time on every lookup
   - Status: ✅ Already live

2. **Hourly Rate Limit**
   - Source: Direct database query for current hour
   - Updates: Real-time count of lookups in current hour
   - Status: ✅ Already live

3. **Today's Lookup Statistics**
   - Source: `Vehicle_Lookup_Database::get_stats()` for today
   - Updates: Real-time on every lookup
   - Status: ✅ Already live

4. **Cache Hit/Miss Statistics**
   - Source: Database query counting cached vs. non-cached lookups
   - Updates: Real-time on every lookup
   - Status: ✅ Already live

### Areas Needing Improvement ⚠️

1. **Upstream Health Check**
   - Current: 7-minute cache (420 seconds)
   - Issue: May show stale health data during incidents
   - Recommendation: Add manual refresh capability

2. **Service Status Indicators**
   - Current: JavaScript-based health check on page load
   - Issue: No auto-refresh for live monitoring
   - Recommendation: Add optional auto-refresh toggle

3. **Cache Age Display**
   - Current: No indication of how old cached data is
   - Issue: Users don't know if metrics are current
   - Recommendation: Add "Last Updated" timestamps

4. **Null/Missing Values**
   - Current: Some metrics may show 0 when data is missing
   - Issue: Unclear if 0 is real or missing data
   - Recommendation: Distinguish between zero and no-data

---

## Implementation Plan

### Enhancement 1: Manual Health Check Refresh

**Goal:** Allow users to force-refresh health check data without waiting for cache to expire.

**Implementation:**

#### Backend Changes (Vehicle_Lookup_Admin_Ajax)

Already implemented in Phase 2 design:
```php
public function check_upstream_health() {
    check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // NEW: Check for force refresh parameter
    $force_refresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] === 'true';
    
    $cache_key = 'vehicle_lookup_health_check';
    $cache_ttl = 420; // 7 minutes
    
    // Return cached unless forced
    if (!$force_refresh) {
        $cached_result = get_transient($cache_key);
        if ($cached_result !== false) {
            $cached_result['cached'] = true;
            $cached_result['cache_age'] = time() - (get_option('_transient_timeout_' . $cache_key) - $cache_ttl);
            wp_send_json_success($cached_result);
            return;
        }
    }
    
    // Perform fresh check
    $result = $this->perform_health_check();
    
    if ($result['success']) {
        set_transient($cache_key, $result['data'], $cache_ttl);
        $result['data']['cached'] = false;
        $result['data']['checked_at'] = current_time('mysql');
        wp_send_json_success($result['data']);
    } else {
        wp_send_json_error($result['error']);
    }
}
```

#### Frontend Changes (admin.js)

Add refresh button and update status display:

```javascript
// Add to checkCloudflareStatus function
function checkCloudflareStatus(forceRefresh = false) {
    $('#health-check-spinner').show();
    $('#health-refresh-btn').prop('disabled', true);
    
    $.ajax({
        url: vehicleLookupAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'vehicle_lookup_check_upstream',
            nonce: vehicleLookupAdmin.nonce,
            force_refresh: forceRefresh ? 'true' : 'false'
        },
        success: function(response) {
            if (response.success) {
                updateCloudflareStatus(response.data);
                updateVegvesenStatus(response.data);
                updateAiSummaryStatus(response.data);
                updateEnhancedDashboardMetrics(response.data);
                
                // Show cache indicator
                if (response.data.cached) {
                    const cacheAge = response.data.cache_age || 0;
                    $('#cache-indicator').html(
                        '<span style="color: #666;">📋 Cached (' + 
                        Math.floor(cacheAge / 60) + 'm ago)</span>'
                    );
                } else {
                    $('#cache-indicator').html(
                        '<span style="color: #46b450;">🔄 Live Data</span>'
                    );
                }
                
                // Update last checked time
                const now = new Date();
                $('#last-checked').text('Last checked: ' + now.toLocaleTimeString());
            }
        },
        complete: function() {
            $('#health-check-spinner').hide();
            $('#health-refresh-btn').prop('disabled', false);
        }
    });
}

// Add refresh button handler
$('#health-refresh-btn').on('click', function() {
    checkCloudflareStatus(true); // Force refresh
});
```

#### Dashboard HTML Update

Add refresh button and indicators:
```php
<div class="overall-status">
    <div class="status-indicator" id="overall-status">
        <span class="status-light checking"></span>
        <span class="status-text">Checking Service Status...</span>
    </div>
    <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
        <button type="button" id="health-refresh-btn" class="button button-secondary">
            <span class="dashicons dashicons-update-alt"></span> Refresh Status
        </button>
        <span id="cache-indicator"></span>
        <span id="last-checked" style="color: #666; font-size: 12px;"></span>
        <span id="health-check-spinner" class="spinner" style="display: none;"></span>
    </div>
</div>
```

---

### Enhancement 2: Auto-Refresh Toggle

**Goal:** Allow users to enable automatic metric updates without manual page refresh.

**Implementation:**

#### Dashboard HTML

```php
<div class="dashboard-controls" style="margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 15px;">
        <label>
            <input type="checkbox" id="auto-refresh-toggle" />
            <span>Auto-refresh metrics (every 30 seconds)</span>
        </label>
        <span id="next-refresh" style="color: #666; font-size: 12px;"></span>
    </div>
</div>
```

#### Frontend JavaScript

```javascript
let autoRefreshInterval = null;
let autoRefreshEnabled = false;
let nextRefreshTime = 0;
const AUTO_REFRESH_INTERVAL = 30000; // 30 seconds

// Load saved preference
autoRefreshEnabled = localStorage.getItem('vehicleLookup_autoRefresh') === 'true';
$('#auto-refresh-toggle').prop('checked', autoRefreshEnabled);

// Toggle handler
$('#auto-refresh-toggle').on('change', function() {
    autoRefreshEnabled = $(this).is(':checked');
    localStorage.setItem('vehicleLookup_autoRefresh', autoRefreshEnabled);
    
    if (autoRefreshEnabled) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
});

function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    // Update countdown every second
    autoRefreshInterval = setInterval(function() {
        // Refresh all metrics
        refreshAllMetrics();
        
        // Reset countdown
        nextRefreshTime = Date.now() + AUTO_REFRESH_INTERVAL;
    }, AUTO_REFRESH_INTERVAL);
    
    // Start countdown display
    updateCountdown();
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
    $('#next-refresh').text('');
}

function updateCountdown() {
    if (!autoRefreshEnabled) return;
    
    const remaining = Math.max(0, Math.ceil((nextRefreshTime - Date.now()) / 1000));
    $('#next-refresh').text('Next refresh in ' + remaining + 's');
    
    setTimeout(updateCountdown, 1000);
}

function refreshAllMetrics() {
    // Don't force-refresh health check (use cache)
    checkCloudflareStatus(false);
    
    // Reload page metrics via AJAX
    $.ajax({
        url: vehicleLookupAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'vehicle_lookup_get_live_metrics',
            nonce: vehicleLookupAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                updateDashboardMetrics(response.data);
            }
        }
    });
}

// Initialize on page load
if (autoRefreshEnabled) {
    startAutoRefresh();
}
```

#### New AJAX Endpoint

Add to `Vehicle_Lookup_Admin_Ajax::register_handlers()`:

```php
add_action('wp_ajax_vehicle_lookup_get_live_metrics', array($this, 'get_live_metrics'));
```

Add method:
```php
public function get_live_metrics() {
    check_ajax_referer('vehicle_lookup_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $db_handler = new Vehicle_Lookup_Database();
    $today = date('Y-m-d');
    
    // Get all live metrics
    $data = array(
        'quota' => array(
            'used' => $db_handler->get_daily_quota($today),
            'limit' => get_option('vehicle_lookup_daily_quota', 5000),
            'updated_at' => current_time('mysql')
        ),
        'rate_limit' => array(
            'current' => $this->get_current_hour_count($db_handler),
            'limit' => get_option('vehicle_lookup_rate_limit', VEHICLE_LOOKUP_RATE_LIMIT),
            'updated_at' => current_time('mysql')
        ),
        'lookups_today' => array(
            'total' => $db_handler->get_daily_count($today),
            'success' => $db_handler->get_daily_success_count($today),
            'updated_at' => current_time('mysql')
        ),
        'cache' => array(
            'hits' => $db_handler->get_cache_hits($today),
            'misses' => $db_handler->get_cache_misses($today),
            'updated_at' => current_time('mysql')
        )
    );
    
    wp_send_json_success($data);
}

private function get_current_hour_count($db_handler) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
    
    $current_hour = date('Y-m-d H');
    $hour_start = $current_hour . ':00:00';
    $hour_end = $current_hour . ':59:59';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} 
         WHERE lookup_time BETWEEN %s AND %s",
        $hour_start, $hour_end
    ));
}
```

---

### Enhancement 3: Timestamp Display

**Goal:** Show when each metric was last updated.

**Implementation:**

#### Dashboard HTML Updates

Add timestamp to each metric card:

```php
<div class="metric-card primary">
    <div class="metric-header">
        <span class="dashicons dashicons-search"></span>
        <h3>Today's Lookups</h3>
    </div>
    <div class="metric-content">
        <div class="big-number"><?php echo number_format($stats['today_total']); ?></div>
        <div class="metric-meta">
            <span class="metric-timestamp">
                Updated: <?php echo date('H:i:s'); ?>
            </span>
        </div>
    </div>
</div>
```

#### CSS Styling

```css
.metric-timestamp {
    display: block;
    font-size: 11px;
    color: #666;
    margin-top: 5px;
    font-style: italic;
}

.metric-meta {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e0e0e0;
}
```

---

### Enhancement 4: Null Value Handling

**Goal:** Clearly distinguish between zero values and missing/unavailable data.

**Implementation:**

#### Helper Function

```php
/**
 * Format metric value with null handling
 * 
 * @param mixed $value The metric value
 * @param string $type The type of metric (number, percentage, etc.)
 * @return string Formatted display value
 */
private function format_metric_value($value, $type = 'number') {
    if ($value === null || $value === false) {
        return '<span class="metric-unavailable">N/A</span>';
    }
    
    if ($value === 0) {
        return '<span class="metric-zero">0</span>';
    }
    
    switch ($type) {
        case 'percentage':
            return number_format($value, 1) . '%';
        case 'number':
            return number_format($value);
        case 'time':
            return $value . 'ms';
        default:
            return esc_html($value);
    }
}
```

#### Dashboard Usage

```php
<div class="big-number">
    <?php echo $this->format_metric_value($stats['today_total'], 'number'); ?>
</div>

<div class="metric-detail">
    Success Rate: <?php echo $this->format_metric_value($stats['success_rate'], 'percentage'); ?>
</div>
```

#### CSS Styling

```css
.metric-unavailable {
    color: #999;
    font-style: italic;
    font-weight: normal;
}

.metric-zero {
    color: #666;
}
```

---

### Enhancement 5: Error State Display

**Goal:** Handle and display API/database errors gracefully.

**Implementation:**

#### Dashboard Error Handling

```php
public function render() {
    try {
        $today = date('Y-m-d');
        
        // Attempt to get metrics
        $quota_used = $this->db_handler->get_daily_quota($today);
        $quota_limit = get_option('vehicle_lookup_daily_quota', 5000);
        
        // ... get other metrics ...
        
        // Render dashboard
        $this->render_dashboard_html($metrics);
        
    } catch (Exception $e) {
        // Log error
        error_log('Dashboard render error: ' . $e->getMessage());
        
        // Show error message
        $this->render_error_state($e->getMessage());
    }
}

private function render_error_state($message) {
    ?>
    <div class="wrap vehicle-lookup-admin">
        <h1>Vehicle Lookup Dashboard</h1>
        <div class="notice notice-error">
            <p>
                <strong>Error loading dashboard:</strong> <?php echo esc_html($message); ?>
            </p>
            <p>
                Please check your database connection and try again. 
                If the problem persists, contact support.
            </p>
        </div>
        <button type="button" class="button button-primary" onclick="location.reload()">
            Retry
        </button>
    </div>
    <?php
}
```

---

## Monitoring Enhancements

### Real-Time Status Indicators

Add visual indicators for service health:

```php
<div class="status-grid">
    <!-- API Status -->
    <div class="status-item">
        <div class="status-icon" id="api-status-icon">
            <span class="dashicons dashicons-cloud"></span>
        </div>
        <div class="status-details">
            <h4>Worker API</h4>
            <span class="status-value" id="api-status-text">Checking...</span>
            <span class="status-timestamp" id="api-status-time"></span>
        </div>
    </div>
    
    <!-- Database Status -->
    <div class="status-item">
        <div class="status-icon" id="db-status-icon">
            <span class="dashicons dashicons-database"></span>
        </div>
        <div class="status-details">
            <h4>Database</h4>
            <span class="status-value" id="db-status-text">Checking...</span>
            <span class="status-timestamp" id="db-status-time"></span>
        </div>
    </div>
    
    <!-- Cache Status -->
    <div class="status-item">
        <div class="status-icon" id="cache-status-icon">
            <span class="dashicons dashicons-performance"></span>
        </div>
        <div class="status-details">
            <h4>Cache</h4>
            <span class="status-value" id="cache-status-text">Checking...</span>
            <span class="status-timestamp" id="cache-status-time"></span>
        </div>
    </div>
</div>
```

#### Status Update JavaScript

```javascript
function updateServiceStatus(service, status, message, timestamp) {
    const statusColors = {
        'operational': '#46b450',
        'degraded': '#f56e28',
        'down': '#dc3232',
        'unknown': '#999'
    };
    
    $(`#${service}-status-icon`).css('color', statusColors[status]);
    $(`#${service}-status-text`).text(message).css('color', statusColors[status]);
    
    if (timestamp) {
        const time = new Date(timestamp);
        $(`#${service}-status-time`).text('Last checked: ' + time.toLocaleTimeString());
    }
}

// Example usage
updateServiceStatus('api', 'operational', 'All systems operational', Date.now());
updateServiceStatus('db', 'operational', 'Connected', Date.now());
updateServiceStatus('cache', 'degraded', 'High memory usage', Date.now());
```

---

## Business vs Technical vs Health Views

### View Segregation

Create tabbed interface for different audiences:

```php
<div class="dashboard-tabs">
    <button class="tab-button active" data-tab="business">Business Overview</button>
    <button class="tab-button" data-tab="technical">Technical Details</button>
    <button class="tab-button" data-tab="health">System Health</button>
    <button class="tab-button" data-tab="debug">Debug Info</button>
</div>

<div class="tab-content" id="business-tab">
    <!-- Business metrics: Quota, Usage, Trends -->
    <div class="business-metrics">
        <div class="metric-card">Today's Usage</div>
        <div class="metric-card">Quota Status</div>
        <div class="metric-card">Trends</div>
    </div>
</div>

<div class="tab-content" id="technical-tab" style="display: none;">
    <!-- Technical metrics: Cache, Response Times, Error Rates -->
    <div class="technical-metrics">
        <div class="metric-card">Cache Performance</div>
        <div class="metric-card">API Response Times</div>
        <div class="metric-card">Error Breakdown</div>
    </div>
</div>

<div class="tab-content" id="health-tab" style="display: none;">
    <!-- Health monitoring: Service Status, Circuit Breakers -->
    <div class="health-metrics">
        <div class="metric-card">Service Status</div>
        <div class="metric-card">Circuit Breakers</div>
        <div class="metric-card">Dependencies</div>
    </div>
</div>

<div class="tab-content" id="debug-tab" style="display: none;">
    <!-- Debug info: Raw health data, Logs, Configurations -->
    <div class="debug-info">
        <pre id="raw-health-data"></pre>
        <pre id="recent-errors"></pre>
    </div>
</div>
```

#### Tab Switching JavaScript

```javascript
$('.tab-button').on('click', function() {
    const tab = $(this).data('tab');
    
    // Update buttons
    $('.tab-button').removeClass('active');
    $(this).addClass('active');
    
    // Update content
    $('.tab-content').hide();
    $(`#${tab}-tab`).show();
    
    // Save preference
    localStorage.setItem('vehicleLookup_activeTab', tab);
});

// Restore last viewed tab
const lastTab = localStorage.getItem('vehicleLookup_activeTab') || 'business';
$(`.tab-button[data-tab="${lastTab}"]`).click();
```

---

## Testing Checklist

### Live Metrics Testing

- [ ] Dashboard shows current quota usage (not cached)
- [ ] Rate limit reflects current hour accurately
- [ ] Today's stats update immediately after new lookup
- [ ] Cache hit rate recalculates on new lookups
- [ ] All timestamps display correctly
- [ ] "Last Updated" times are accurate

### Health Check Testing

- [ ] Initial health check runs on page load
- [ ] Cached health check displays cache age
- [ ] "Refresh Now" button forces fresh check
- [ ] Cache indicator shows "Live" vs "Cached"
- [ ] Health check failures display error message
- [ ] Network errors handled gracefully

### Auto-Refresh Testing

- [ ] Toggle saves preference to localStorage
- [ ] Auto-refresh updates metrics every 30 seconds
- [ ] Countdown displays correctly
- [ ] Turning off stops refresh
- [ ] Turning on resumes refresh
- [ ] Page performance remains good with auto-refresh

### Null Value Testing

- [ ] Zero values display as "0"
- [ ] Null values display as "N/A"
- [ ] Missing data doesn't break layout
- [ ] Tooltips explain what N/A means
- [ ] Error states show helpful messages

### Tab Interface Testing

- [ ] All tabs switch correctly
- [ ] Last viewed tab is remembered
- [ ] Business view shows business metrics only
- [ ] Technical view shows technical details
- [ ] Health view shows monitoring data
- [ ] Debug view shows raw data

---

## Performance Considerations

### Query Optimization

1. **Use Indexed Columns**
   ```sql
   CREATE INDEX idx_lookup_time ON vehicle_lookup_logs(lookup_time);
   CREATE INDEX idx_cached ON vehicle_lookup_logs(cached);
   CREATE INDEX idx_status ON vehicle_lookup_logs(status);
   ```

2. **Limit Query Scope**
   - Only query today's data for dashboard
   - Use BETWEEN for date ranges
   - Add LIMIT to popular searches

3. **Cache Expensive Calculations**
   - Cache trend calculations (5-minute TTL)
   - Cache popular searches (10-minute TTL)
   - Don't cache real-time metrics

### AJAX Request Throttling

```javascript
let lastRefreshTime = 0;
const MIN_REFRESH_INTERVAL = 5000; // 5 seconds

function refreshAllMetrics() {
    const now = Date.now();
    
    // Throttle requests
    if (now - lastRefreshTime < MIN_REFRESH_INTERVAL) {
        console.log('Refresh throttled, too frequent');
        return;
    }
    
    lastRefreshTime = now;
    
    // Perform refresh...
}
```

---

## Success Criteria

1. ✅ All metrics show live data (no stale values)
2. ✅ Manual health check refresh works
3. ✅ Auto-refresh toggle implemented
4. ✅ Timestamps on all metrics
5. ✅ Null values handled gracefully
6. ✅ Error states display properly
7. ✅ Tab interface separates concerns
8. ✅ Performance remains acceptable
9. ✅ No JavaScript errors
10. ✅ Mobile-responsive design

---

## Next Steps

After Phase 3:
- **Phase 4:** Add comprehensive testing (unit & integration)
- **Phase 5:** Performance monitoring and optimization
- **Phase 6:** Consider frontend JavaScript modularization

**See [REFACTOR_PHASE_4.md](./REFACTOR_PHASE_4.md) for testing strategy.**
