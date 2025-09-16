jQuery(document).ready(function($) {

    // Auto-check service status on page load
    function checkServiceStatus() {
        checkCloudflareStatus();
    }

    function checkCloudflareStatus() {
        const cloudflareStatusDiv = $('#cloudflare-status');
        const vegvesenStatusDiv = $('#vegvesen-status');
        const detailsDiv = $('#api-details');

        // Set both to checking state
        cloudflareStatusDiv.find('.status-light').removeClass('ok error warning unknown').addClass('checking');
        cloudflareStatusDiv.find('.status-text').text('Checking...');
        vegvesenStatusDiv.find('.status-light').removeClass('ok error warning unknown').addClass('checking');
        vegvesenStatusDiv.find('.status-text').text('Checking...');

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup_test_api',
                nonce: vehicleLookupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const healthData = data.health_data;
                    
                    // Update Cloudflare status
                    let cloudflareStatusClass = 'ok';
                    let cloudflareStatusText = 'Online';

                    if (healthData && healthData.status === 'degraded') {
                        cloudflareStatusClass = 'warning';
                        cloudflareStatusText = 'Degraded';
                    }

                    cloudflareStatusDiv.find('.status-light').removeClass('checking ok error warning').addClass(cloudflareStatusClass);
                    cloudflareStatusDiv.find('.status-text').text(cloudflareStatusText);

                    // Update Vegvesen status based on circuit breaker
                    let vegvesenStatusClass = 'ok';
                    let vegvesenStatusText = 'Online';
                    
                    if (healthData && healthData.circuitBreaker) {
                        const cbState = healthData.circuitBreaker.state;
                        if (cbState === 'OPEN') {
                            vegvesenStatusClass = 'error';
                            vegvesenStatusText = 'Unavailable';
                        } else if (cbState === 'HALF_OPEN') {
                            vegvesenStatusClass = 'warning';
                            vegvesenStatusText = 'Recovering';
                        }
                    }

                    vegvesenStatusDiv.find('.status-light').removeClass('checking ok error warning').addClass(vegvesenStatusClass);
                    vegvesenStatusDiv.find('.status-text').text(vegvesenStatusText);

                    // Display monitoring data
                    if (healthData) {
                        const monitoringData = extractMonitoringData(healthData);
                        displayMonitoringData(monitoringData);
                    }
                } else {
                    cloudflareStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
                    cloudflareStatusDiv.find('.status-text').text('Error');
                    vegvesenStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('unknown');
                    vegvesenStatusDiv.find('.status-text').text('Unknown');
                    detailsDiv.html('<small>Health check failed: ' + response.data.message + '</small>').show();
                }
            },
            error: function() {
                cloudflareStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
                cloudflareStatusDiv.find('.status-text').text('Offline');
                vegvesenStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('unknown');
                vegvesenStatusDiv.find('.status-text').text('Unknown');
                detailsDiv.html('<small>Connection failed to health endpoint</small>').show();
            }
        });
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

        let html = '<div class="monitoring-details">';
        
        // Quota Usage
        if (monitoringData.quota_usage) {
            const quota = monitoringData.quota_usage;
            const percentage = quota.limit > 0 ? Math.round((quota.used / quota.limit) * 100) : 0;
            html += '<div class="monitoring-item">';
            html += '<strong>Vegvesen Quota:</strong> ' + quota.used + '/' + quota.limit + ' (' + percentage + '%)';
            html += '</div>';
        }

        // Vegvesen Utilization
        if (monitoringData.vegvesen_utilization) {
            html += '<div class="monitoring-item">';
            html += '<strong>Utilization:</strong> ' + monitoringData.vegvesen_utilization + '%';
            html += '</div>';
        }

        // Active IPs
        if (monitoringData.active_ips) {
            html += '<div class="monitoring-item">';
            html += '<strong>Active IPs:</strong> ' + monitoringData.active_ips;
            html += '</div>';
        }

        // Rate Limit Config
        if (monitoringData.rate_limit_config) {
            const config = monitoringData.rate_limit_config;
            html += '<div class="monitoring-item">';
            html += '<strong>Rate Limit:</strong> ' + (config.perMinute || 'N/A') + '/min, ' + (config.perHour || 'N/A') + '/hour';
            html += '</div>';
        }

        html += '</div>';
        
        monitoringDiv.html(html).show();
    }

    // Auto-check service status on page load
    if ($('#cloudflare-status').length) {
        setTimeout(function() {
            checkServiceStatus();
        }, 1000);
    }

    // Refresh stats every 30 seconds
    setInterval(function() {
        if ($('.vehicle-lookup-admin').length && window.location.href.indexOf('vehicle-lookup') !== -1) {
            location.reload();
        }
    }, 30000);

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