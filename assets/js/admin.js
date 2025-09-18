jQuery(document).ready(function($) {

    // Auto-check service status on page load
    function checkServiceStatus() {
        checkCloudflareStatus();
    }

    function checkCloudflareStatus() {
        const cloudflareStatusDiv = $('#cloudflare-status');
        const vegvesenStatusDiv = $('#vegvesen-status');
        const detailsDiv = $('#api-details');

        console.log('Starting health check request...');

        // Set both to checking state
        cloudflareStatusDiv.find('.status-light').removeClass('ok error warning unknown').addClass('checking');
        cloudflareStatusDiv.find('.status-text').text('Checking...');
        vegvesenStatusDiv.find('.status-light').removeClass('ok error warning unknown').addClass('checking');
        vegvesenStatusDiv.find('.status-text').text('Pending...');

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            timeout: 15000, // 15 second timeout
            data: {
                action: 'vehicle_lookup_check_upstream',
                nonce: vehicleLookupAdmin.nonce
            },
            beforeSend: function() {
                console.log('Health check AJAX request sent...');
            },
            success: function(response) {
                if (response.success) {
                    const healthData = response.data;
                    
                    // Update cache expiry tracking for smart intervals
                    updateCacheExpiryTracking(healthData);
                    
                    // Handle Cloudflare Worker Status
                    updateCloudflareStatus(healthData);
                    
                    // Handle Vegvesen API Status
                    updateVegvesenStatus(healthData);
                    
                    // Display monitoring data if available
                    if (healthData.monitoring_data) {
                        displayMonitoringData(healthData.monitoring_data);
                    }
                    
                    // Show cache status if response is cached
                    if (healthData.cached) {
                        showCacheInfo(healthData);
                    }
                    
                } else {
                    handleHealthCheckError(response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Health check failed:', status, error, xhr);
                handleHealthCheckNetworkError(xhr, status, error);
            }
        });
    }

    // Enhanced status handling functions
    function updateCloudflareStatus(healthData) {
        const cloudflareStatusDiv = $('#cloudflare-status');
        
        // Worker is online if we got a successful response
        cloudflareStatusDiv.find('.status-light').removeClass('checking ok error warning').addClass('ok');
        
        let statusText = 'Online';
        if (healthData.cached) {
            statusText += ' (cached)';
        }
        if (healthData.service_version && healthData.service_version !== 'unknown') {
            statusText += ' v' + healthData.service_version;
        }
        
        cloudflareStatusDiv.find('.status-text').text(statusText);
    }

    function updateVegvesenStatus(healthData) {
        const vegvesenStatusDiv = $('#vegvesen-status');
        
        if (!healthData.health_data || !healthData.health_data.status) {
            vegvesenStatusDiv.find('.status-light').removeClass('checking ok warning error').addClass('unknown');
            vegvesenStatusDiv.find('.status-text').text('Unknown');
            return;
        }

        const status = healthData.health_data.status;
        let statusClass = 'unknown';
        let statusText = 'Unknown';

        // Handle different health states
        switch(status.toLowerCase()) {
            case 'healthy':
                statusClass = 'ok';
                statusText = 'Healthy';
                break;
            case 'degraded':
                statusClass = 'warning';
                statusText = 'Degraded';
                break;
            case 'unhealthy':
                statusClass = 'error';
                statusText = 'Unhealthy';
                break;
            default:
                statusClass = 'warning';
                statusText = status;
        }

        // Check circuit breaker state
        if (healthData.monitoring_data && healthData.monitoring_data.circuit_breaker) {
            const cbState = healthData.monitoring_data.circuit_breaker.state;
            if (cbState === 'OPEN') {
                statusClass = 'error';
                statusText = 'Circuit Breaker OPEN';
            } else if (cbState === 'HALF_OPEN') {
                statusClass = 'warning';
                statusText = 'Circuit Breaker Testing';
            }
        }

        if (healthData.cached) {
            statusText += ' (cached)';
        }

        vegvesenStatusDiv.find('.status-light').removeClass('checking ok warning error unknown').addClass(statusClass);
        vegvesenStatusDiv.find('.status-text').text(statusText);
    }

    function showCacheInfo(healthData) {
        const detailsDiv = $('#api-details');
        let cacheInfo = '<div style="padding: 10px; background: #f0f9ff; border-left: 3px solid #0ea5e9; margin: 10px 0;">';
        cacheInfo += '<strong>Using Cached Data</strong><br>';
        if (healthData.cache_expires_in) {
            const minutes = Math.floor(healthData.cache_expires_in / 60);
            const seconds = healthData.cache_expires_in % 60;
            cacheInfo += 'Expires in: ' + minutes + 'm ' + seconds + 's<br>';
        }
        cacheInfo += 'Cache TTL: ' + (healthData.cache_ttl ? Math.floor(healthData.cache_ttl / 60) + ' minutes' : 'Unknown');
        cacheInfo += '</div>';
        detailsDiv.html(cacheInfo).show();
    }

    function handleHealthCheckError(response) {
        const cloudflareStatusDiv = $('#cloudflare-status');
        const vegvesenStatusDiv = $('#vegvesen-status');
        const detailsDiv = $('#api-details');

        cloudflareStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
        cloudflareStatusDiv.find('.status-text').text('API Error');
        
        vegvesenStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('unknown');
        vegvesenStatusDiv.find('.status-text').text('Unknown');

        let errorMessage = 'Health check failed';
        let helpText = '';

        if (response.data && response.data.message) {
            errorMessage = response.data.message;
            
            // Provide specific help for common error scenarios
            if (errorMessage.includes('Health check failed:')) {
                helpText = '<br><strong>Possible causes:</strong><br>• Worker endpoint is down<br>• Network connectivity issues<br>• Authentication problems';
            } else if (errorMessage.includes('status code:')) {
                helpText = '<br><strong>Action needed:</strong><br>• Check worker configuration<br>• Verify endpoint URL is correct';
            }
        }

        detailsDiv.html('<div style="color: #dc3232; padding: 10px; border-left: 3px solid #dc3232; background: #fef2f2;">' + 
                       '<strong>Error:</strong> ' + errorMessage + helpText + 
                       '</div>').show();
    }

    function handleHealthCheckNetworkError(xhr, status, error) {
        const cloudflareStatusDiv = $('#cloudflare-status');
        const vegvesenStatusDiv = $('#vegvesen-status');
        const detailsDiv = $('#api-details');

        cloudflareStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
        vegvesenStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('unknown');

        let errorMessage = 'Connection failed';
        let statusText = 'Connection Failed';
        let helpText = '';

        // Enhanced error messaging based on error type
        switch(status) {
            case 'timeout':
                errorMessage = 'Request timed out after 15 seconds';
                statusText = 'Timeout';
                helpText = '• Server may be overloaded<br>• Check network connectivity<br>• Try again in a few minutes';
                break;
            case 'error':
                if (xhr.status === 0) {
                    errorMessage = 'Network error - Unable to connect';
                    statusText = 'No Connection';
                    helpText = '• Check internet connection<br>• Server may be down<br>• Firewall blocking request';
                } else if (xhr.status >= 500) {
                    errorMessage = 'Server error (HTTP ' + xhr.status + ')';
                    statusText = 'Server Error';
                    helpText = '• Internal server error<br>• Worker may be experiencing issues<br>• Try again later';
                } else if (xhr.status >= 400) {
                    errorMessage = 'Client error (HTTP ' + xhr.status + ')';
                    statusText = 'Client Error';
                    helpText = '• Authentication may be required<br>• Check configuration settings<br>• Verify endpoint URL';
                } else {
                    errorMessage = 'Network error: ' + error;
                    statusText = 'Network Error';
                    helpText = '• Check network connectivity<br>• Server may be unreachable';
                }
                break;
            case 'abort':
                errorMessage = 'Request was cancelled';
                statusText = 'Cancelled';
                helpText = '• Request was interrupted<br>• Try refreshing the page';
                break;
            default:
                errorMessage = 'Unknown error occurred';
                statusText = 'Error';
                helpText = '• Unexpected error<br>• Try refreshing the page<br>• Check browser console for details';
        }

        cloudflareStatusDiv.find('.status-text').text(statusText);
        vegvesenStatusDiv.find('.status-text').text('Unknown');

        detailsDiv.html('<div style="color: #dc3232; padding: 10px; border-left: 3px solid #dc3232; background: #fef2f2;">' + 
                       '<strong>Connection Error:</strong> ' + errorMessage + '<br>' +
                       '<strong>Troubleshooting:</strong><br>' + helpText + 
                       '</div>').show();
    }

    function displayCachedHealthData(cachedData) {
        const statusDiv = $('#vegvesen-status');
        const healthData = cachedData.health_data;
        const monitoringData = cachedData.monitoring_data;

        // Display status based on cached data
        let statusClass = 'unknown';
        let statusText = 'Unknown';

        if (healthData && healthData.status) {
            if (healthData.status === 'healthy') {
                statusClass = 'ok';
                statusText = 'Healthy';
            } else if (healthData.status === 'degraded') {
                statusClass = 'warning';
                statusText = 'Degraded';
            }
        }

        statusDiv.find('.status-light').removeClass('checking ok warning error').addClass(statusClass);
        statusDiv.find('.status-text').text(statusText + ' (cached)');

        // Display cached monitoring data
        displayMonitoringData(monitoringData);

        console.log('Using cached health data (expires in ' + 
                   Math.round((HEALTH_CACHE_DURATION - (Date.now() - healthCheckCacheTime)) / 1000) + 's)');
    }

    function extractMonitoringData(healthData) {
        const monitoringData = {};

        // Rate limiting information
        if (healthData.rateLimiting) {
            const rl = healthData.rateLimiting;
            monitoringData.rate_limiting = {
                daily_usage: rl.globalDailyUsage || 0,
                daily_limit: rl.globalDailyLimit || 4500,
                daily_remaining: rl.globalDailyRemaining || 0,
                vegvesen_quota: rl.vegvesenQuotaUsage || '0/5000',
                quota_utilization: rl.quotaUtilization || '0%',
                active_ips_hourly: (rl.activeIPsTracked && rl.activeIPsTracked.hourly) || 0,
                active_ips_burst: (rl.activeIPsTracked && rl.activeIPsTracked.burst) || 0
            };
        }

        // Cache information
        if (healthData.cache) {
            const cache = healthData.cache;
            monitoringData.cache = {
                entries: cache.entries || 0,
                max_size: cache.maxSize || 1000,
                ttl: cache.ttl || 3600,
                utilization: (cache.entries && cache.maxSize) ? 
                    Math.round((cache.entries / cache.maxSize) * 100) : 0
            };
        }

        // Circuit breaker status
        if (healthData.circuitBreaker) {
            const cb = healthData.circuitBreaker;
            monitoringData.circuit_breaker = {
                state: cb.state || 'CLOSED',
                failure_count: cb.failureCount || 0,
                success_rate: cb.successRate || '100%',
                total_requests: cb.totalRequests || 0,
                last_failure: cb.lastFailure
            };
        }

        return monitoringData;
    }

    function displayMonitoringData(monitoringData) {
        const monitoringDiv = $('#monitoring-data');

        if (!monitoringData || Object.keys(monitoringData).length === 0) {
            monitoringDiv.hide();
            return;
        }

        let html = '<div class="monitoring-details">';
        html += '<h4 style="margin: 0 0 10px 0; color: #374151;">Live Metrics</h4>';

        // Rate limiting information
        if (monitoringData.rate_limiting) {
            const rl = monitoringData.rate_limiting;
            html += '<div class="monitoring-item">';
            html += '<strong>Daily Quota:</strong> ' + rl.daily_usage + '/' + rl.daily_limit + ' (' + rl.quota_utilization + ')';
            html += '</div>';
            html += '<div class="monitoring-item">';
            html += '<strong>Vegvesen Quota:</strong> ' + rl.vegvesen_quota;
            html += '</div>';
            html += '<div class="monitoring-item">';
            html += '<strong>Active IPs:</strong> ' + rl.active_ips_hourly + ' hourly, ' + rl.active_ips_burst + ' burst';
            html += '</div>';
        }

        // Cache information
        if (monitoringData.cache) {
            const cache = monitoringData.cache;
            html += '<div class="monitoring-item">';
            html += '<strong>Cache:</strong> ' + cache.entries + '/' + cache.max_size + ' entries (' + cache.utilization + '%)';
            html += '</div>';
        }

        // Circuit breaker status
        if (monitoringData.circuit_breaker) {
            const cb = monitoringData.circuit_breaker;
            const stateColor = cb.state === 'CLOSED' ? '#00a32a' : (cb.state === 'OPEN' ? '#d63638' : '#dba617');
            html += '<div class="monitoring-item">';
            html += '<strong>Circuit Breaker:</strong> <span style="color: ' + stateColor + '">' + cb.state + '</span>';
            if (cb.success_rate) {
                html += ' (Success: ' + cb.success_rate + ')';
            }
            html += '</div>';
            if (cb.total_requests > 0) {
                html += '<div class="monitoring-item">';
                html += '<strong>Requests:</strong> ' + cb.total_requests + ' total, ' + cb.failure_count + ' failures';
                html += '</div>';
            }
        }

        html += '</div>';

        monitoringDiv.html(html).show();
    }

    // Smart cache-aware health checking
    let healthCheckInterval;
    let lastCacheExpiryTime = 0;

    function startSmartHealthMonitoring() {
        if ($('#cloudflare-status').length) {
            console.log('Starting smart health monitoring...');
            
            // Initial check after 500ms
            setTimeout(function() {
                checkServiceStatus();
            }, 500);

            // Set up smart interval checking
            healthCheckInterval = setInterval(function() {
                if ($('#cloudflare-status').length) {
                    const now = Date.now() / 1000; // Current time in seconds
                    
                    // If we know when cache expires, check 30 seconds before expiry
                    if (lastCacheExpiryTime > 0 && now < (lastCacheExpiryTime - 30)) {
                        console.log('Cache still valid, skipping health check. Next check in ' + Math.round((lastCacheExpiryTime - 30) - now) + ' seconds');
                        return;
                    }
                    
                    // Make a fresh health check
                    checkServiceStatus();
                }
            }, 30000); // Check every 30 seconds, but respect cache
        }
    }

    function updateCacheExpiryTracking(healthData) {
        if (healthData.cached && healthData.cache_expires_in) {
            lastCacheExpiryTime = (Date.now() / 1000) + healthData.cache_expires_in;
            console.log('Cache expires at: ' + new Date(lastCacheExpiryTime * 1000).toLocaleString());
        } else if (!healthData.cached && healthData.cache_ttl) {
            // Fresh data, cache will expire after TTL
            lastCacheExpiryTime = (Date.now() / 1000) + healthData.cache_ttl;
            console.log('Fresh data cached, expires at: ' + new Date(lastCacheExpiryTime * 1000).toLocaleString());
        }
    }

    // Start smart monitoring
    startSmartHealthMonitoring();

    // Add tooltips to help text
    $('.description').each(function() {
        $(this).closest('tr').find('th').attr('title', $(this).text());
    });

    // Reset analytics data
    $('#reset-analytics').on('click', function() {
        if (!confirm('Are you sure you want to reset all analytics data? This action cannot be undone.')) {
            return;
        }

        const button = $(this);
        const originalText = button.html();

        // Check if we have the necessary variables
        if (typeof vehicleLookupAdmin === 'undefined') {
            alert('Error: WordPress AJAX not properly configured. Please check if you are in a WordPress environment.');
            button.prop('disabled', false).html(originalText);
            return;
        }

        // Debug logging
        console.log('Starting analytics reset...');
        console.log('AJAX URL:', vehicleLookupAdmin.ajaxurl);
        console.log('Nonce:', vehicleLookupAdmin.nonce);

        button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Resetting...');

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_analytics_data',
                nonce: vehicleLookupAdmin.nonce
            },
            beforeSend: function(xhr) {
                console.log('Sending AJAX request...');
            },
            success: function(response) {
                console.log('AJAX response received:', response);
                if (response.success) {
                    alert('Analytics data has been successfully reset: ' + response.data.message);
                    location.reload();
                } else {
                    alert('Error resetting analytics data: ' + (response.data ? response.data.message : 'Unknown error'));
                    console.error('Error details:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('Connection error while resetting analytics data: ' + error);
            },
            complete: function() {
                console.log('AJAX request complete.');
                button.prop('disabled', false).html(originalText);
            }
        });
    });



    // Helper function to display health data
    function displayHealthData(healthData) {
        if (healthData.correlationId) {
            console.log('Health Check Correlation ID:', healthData.correlationId);
        }

        // You can add more detailed health data display here if needed
        if (healthData.circuitBreaker && healthData.circuitBreaker.state !== 'CLOSED') {
            console.warn('Circuit Breaker State:', healthData.circuitBreaker.state);
        }
    }

    // Clear worker cache
    $('#clear-worker-cache').on('click', function() {
        if (!confirm('Are you sure you want to clear the worker cache?')) {
            return;
        }

        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Clearing...');

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_worker_cache',
                nonce: vehicleLookupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Worker cache cleared successfully: ' + response.data.message);
                } else {
                    alert('Error clearing worker cache: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Connection error while clearing worker cache: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Clear local cache
    $('#clear-local-cache').on('click', function() {
        if (!confirm('Are you sure you want to clear the local vehicle cache?')) {
            return;
        }

        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Clearing...');

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_local_cache',
                nonce: vehicleLookupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Local cache cleared successfully: ' + response.data.message);
                    location.reload();
                } else {
                    alert('Error clearing local cache: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Connection error while clearing local cache: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Clear cache functionality
    $('#clear-cache-btn').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.text();

        $btn.text('Clearing...').prop('disabled', true);

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_vehicle_cache',
                nonce: vehicleLookupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $btn.text('Cleared!').removeClass('button-secondary').addClass('button-primary');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Failed to clear cache');
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('Error clearing cache');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });
});