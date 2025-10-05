jQuery(document).ready(function($) {

    function formatPercentValue(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        if (typeof value === 'number') {
            if (!Number.isFinite(value)) {
                return null;
            }
            return `${Number.isInteger(value) ? value : Number(value.toFixed(1))}%`;
        }

        const stringValue = value.toString().trim();
        if (stringValue === '') {
            return null;
        }

        if (stringValue.includes('%')) {
            return stringValue;
        }

        const numeric = Number(stringValue);
        if (!Number.isNaN(numeric)) {
            return `${Number.isInteger(numeric) ? numeric : Number(numeric.toFixed(1))}%`;
        }

        return stringValue;
    }

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
                    
                    // Handle AI Summary Status
                    updateAiSummaryStatus(healthData);
                    
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

        // Check circuit breaker state for vehicle endpoint
        if (healthData.monitoring_data && healthData.monitoring_data.circuit_breaker) {
            const cbState = healthData.monitoring_data.circuit_breaker.vehicle_circuit_state || 
                           healthData.monitoring_data.circuit_breaker.state; // fallback to legacy
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
        
        // Update enhanced dashboard metrics after status update
        updateEnhancedDashboardMetrics(healthData);
    }

    function updateAiSummaryStatus(healthData) {
        const aiSummaryDiv = $('#ai-summary-status');
        
        // Check for enhanced AI summary monitoring data from two-endpoint system
        const aiData = healthData.monitoring_data?.ai_summary || healthData.health_data?.aiSummaries;
        
        let statusClass = 'unknown';
        let statusText = 'Unknown';
        
        if (!aiData) {
            statusClass = 'unknown';
            statusText = 'No Data';
        } else if (healthData.monitoring_data?.ai_summary) {
            // New enhanced AI summary monitoring
            const status = aiData.status;
            
            if (status === 'operational') {
                statusClass = 'ok';
                statusText = 'Operational';
                if (aiData.active_generations > 0) {
                    statusText += ` (${aiData.active_generations} generating)`;
                }
            } else if (status === 'degraded') {
                statusClass = 'warning';
                statusText = 'Degraded Performance';
            } else if (status === 'down') {
                statusClass = 'error';
                statusText = 'Service Down';
            } else if (status === 'limited') {
                statusClass = 'warning';
                statusText = 'Rate Limited';
            }
            
            // Update enhanced AI metrics for two-endpoint system
            $('#ai-model-info strong').text(aiData.model || '-');
            $('#ai-timeout-setting strong').text((aiData.timeout || 25000) + 'ms');
            
            // Use proper AI cache entries from enhanced monitoring
            const aiCacheEntries = healthData.monitoring_data?.cache?.ai_cache_entries;
            if (aiCacheEntries !== undefined) {
                $('#ai-cache-entries strong').text(aiCacheEntries + ' cached');
            } else {
                $('#ai-cache-entries strong').text((aiData.completed_today || 0) + ' today');
            }
            
            const successRateElement = $('#ai-success-rate strong');
            if (successRateElement.length) {
                const successRateValue = aiData.success_rate ?? aiData.generation_success_rate ?? aiData.successRate;
                const formattedSuccessRate = formatPercentValue(successRateValue);
                if (formattedSuccessRate) {
                    successRateElement.text(formattedSuccessRate);
                }
            }

            // Update enhanced AI metrics for new dashboard elements
            if (aiData.active_generations !== undefined) {
                $('#ai-active-generations strong').text(aiData.active_generations);
            }
            if (aiData.avg_generation_time !== undefined) {
                $('#ai-avg-generation-time strong').text(aiData.avg_generation_time + 'ms');
            }
            
            // Check AI circuit breaker state
            if (healthData.monitoring_data?.circuit_breaker?.ai_circuit_state) {
                const aiCbState = healthData.monitoring_data.circuit_breaker.ai_circuit_state;
                if (aiCbState === 'OPEN') {
                    statusClass = 'error';
                    statusText = 'AI Circuit Breaker OPEN';
                } else if (aiCbState === 'HALF_OPEN') {
                    statusClass = 'warning';
                    statusText = 'AI Circuit Testing';
                }
            }
            
        } else if (!aiData.enabled) {
            // Legacy AI summary data
            statusClass = 'warning';
            statusText = 'API Key Missing';
        } else {
            // Legacy AI summary data  
            statusClass = 'ok';
            statusText = `Active (${aiData.model})`;
            
            // Update developer section with AI details (legacy format)
            $('#ai-model-info strong').text(aiData.model || '-');
            $('#ai-cache-entries strong').text(aiData.entries || '0');
            $('#ai-timeout-setting strong').text(aiData.timeoutMs || '10000');
        }
        
        if (healthData.cached) {
            statusText += ' (cached)';
        }
        
        aiSummaryDiv.find('.status-light').removeClass('checking ok warning error unknown').addClass(statusClass);
        aiSummaryDiv.find('.status-text').text(statusText);
        
        // Update enhanced dashboard metrics after status update
        updateEnhancedDashboardMetrics(healthData);
    }
    
    // Function to update all enhanced dashboard metrics from two-endpoint monitoring
    function updateEnhancedDashboardMetrics(healthData) {
        const monitoringData = healthData.monitoring_data || {};
        
        // Enhanced Cache Performance metrics
        if (monitoringData.cache) {
            const cache = monitoringData.cache;
            
            if (cache.vehicle_cache_entries !== undefined) {
                $('#vehicle-cache-entries strong').text(cache.vehicle_cache_entries);
            }
            
            const aiHitRateValue = cache.ai_cache_hit_rate ?? cache.ai_hit_rate;
            const formattedAiHitRate = formatPercentValue(aiHitRateValue);
            if (formattedAiHitRate) {
                $('#ai-cache-hit-rate strong').text(formattedAiHitRate);
            }
        }
        
        // Circuit Breaker Status
        if (monitoringData.circuit_breaker) {
            const cb = monitoringData.circuit_breaker;
            
            if (cb.vehicle_circuit_state) {
                $('#vehicle-circuit-status strong').text(cb.vehicle_circuit_state);
            }
            
            if (cb.ai_circuit_state) {
                $('#ai-circuit-status strong').text(cb.ai_circuit_state);
            }
            
            if (cb.success_rate) {
                $('#circuit-success-rate strong').text(cb.success_rate);
            }
        }
        
        // Performance Tracking
        if (monitoringData.performance) {
            const perf = monitoringData.performance;

            if (perf.vehicle_latency !== undefined) {
                $('#vehicle-latency strong').text(perf.vehicle_latency + 'ms');
            }

            if (perf.ai_latency !== undefined) {
                $('#ai-latency strong').text(perf.ai_latency + 'ms');
            }

            if (perf.cache_hit_improvement) {
                $('#cache-performance-improvement strong').text(perf.cache_hit_improvement);
            }
        }

        // Rate limiting details
        const rateLimiting = monitoringData.rate_limiting;
        if (rateLimiting && rateLimiting.daily_remaining !== undefined && rateLimiting.daily_remaining !== null) {
            const quotaRemainingElement = $('#quota-remaining-value');
            if (quotaRemainingElement.length) {
                const formattedValue = Number(rateLimiting.daily_remaining).toLocaleString();
                quotaRemainingElement.text(formattedValue);
            }
        }
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
        const aiSummaryStatusDiv = $('#ai-summary-status');
        const detailsDiv = $('#api-details');

        cloudflareStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
        cloudflareStatusDiv.find('.status-text').text('API Error');
        
        vegvesenStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('unknown');
        vegvesenStatusDiv.find('.status-text').text('Unknown');
        
        aiSummaryStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('unknown');
        aiSummaryStatusDiv.find('.status-text').text('Unknown');

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
        const aiSummaryStatusDiv = $('#ai-summary-status');
        const detailsDiv = $('#api-details');

        cloudflareStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
        vegvesenStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('unknown');
        aiSummaryStatusDiv.find('.status-light').removeClass('checking ok warning').addClass('unknown');

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
        aiSummaryStatusDiv.find('.status-text').text('Unknown');

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

        // Enhanced cache information for two-endpoint system
        if (monitoringData.cache) {
            const cache = monitoringData.cache;
            html += '<div class="monitoring-item">';
            html += '<strong>Total Cache:</strong> ' + cache.entries + '/' + cache.max_size + ' entries (' + cache.utilization + '%)';
            html += '</div>';
            
            // Separate cache metrics if available
            if (cache.vehicle_cache_entries !== undefined || cache.ai_cache_entries !== undefined) {
                html += '<div class="monitoring-item">';
                html += '<strong>Vehicle Cache:</strong> ' + (cache.vehicle_cache_entries || 0) + ' entries (' + (cache.vehicle_hit_rate || '0%') + ' hit rate)';
                html += '</div>';
                const aiHitRateDisplay = formatPercentValue(cache.ai_cache_hit_rate ?? cache.ai_hit_rate) || '0%';
                html += '<div class="monitoring-item">';
                html += '<strong>AI Cache:</strong> ' + (cache.ai_cache_entries || 0) + ' entries (' + aiHitRateDisplay + ' hit rate)';
                html += '</div>';
                if (cache.ai_cache_ttl) {
                    html += '<div class="monitoring-item">';
                    html += '<strong>AI Cache TTL:</strong> ' + Math.floor(cache.ai_cache_ttl / 3600) + ' hours';
                    html += '</div>';
                }
            }
        }

        // AI Summary Service Performance
        if (monitoringData.ai_summary) {
            const ai = monitoringData.ai_summary;
            html += '<div class="monitoring-item">';
            html += '<strong>AI Summaries:</strong> ' + (ai.completed_today || 0) + ' completed, ' + (ai.failed_today || 0) + ' failed';
            html += '</div>';
            if (ai.avg_generation_time) {
                html += '<div class="monitoring-item">';
                html += '<strong>AI Generation Time:</strong> ' + ai.avg_generation_time + 'ms avg';
                html += '</div>';
            }
        }

        // Performance Metrics for Two-Endpoint System
        if (monitoringData.performance) {
            const perf = monitoringData.performance;
            html += '<div class="monitoring-item">';
            html += '<strong>Vehicle Latency:</strong> ' + perf.vehicle_avg_latency + 'ms avg';
            html += '</div>';
            html += '<div class="monitoring-item">';
            html += '<strong>AI Latency:</strong> ' + perf.ai_avg_latency + 'ms avg';
            html += '</div>';
            if (perf.cache_hit_improvement) {
                html += '<div class="monitoring-item">';
                html += '<strong>Cache Performance:</strong> ' + perf.cache_hit_improvement + ' improvement';
                html += '</div>';
            }
        }

        // Enhanced circuit breaker status for two-endpoint system
        if (monitoringData.circuit_breaker) {
            const cb = monitoringData.circuit_breaker;
            
            // Overall circuit breaker state
            if (cb.state) {
                html += '<div class="monitoring-item">';
                html += '<strong>Circuit Breaker:</strong> ' + cb.state + ' (' + (cb.success_rate || '100%') + ' success)';
                html += '</div>';
            }
            
            // Separate circuit breaker states for two-endpoint system
            if (cb.vehicle_circuit_state || cb.ai_circuit_state) {
                html += '<div class="monitoring-item">';
                html += '<strong>Vehicle Circuit:</strong> ' + (cb.vehicle_circuit_state || 'CLOSED');
                html += '</div>';
                html += '<div class="monitoring-item">';
                html += '<strong>AI Circuit:</strong> ' + (cb.ai_circuit_state || 'CLOSED');
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

    // Dual-Audience Dashboard Functions
    function initializeDualAudienceDashboard() {
        // Initialize overall status indicator
        updateOverallStatus('checking', 'Checking Service Status...');
    }

    function updateOverallStatus(status, message) {
        const overallStatusDiv = $('#overall-status');
        const statusLight = overallStatusDiv.find('.status-light');
        const statusText = overallStatusDiv.find('.status-text');
        
        if (statusLight.length && statusText.length) {
            // Remove all status classes and add the new one
            statusLight.removeClass('ok error warning unknown checking').addClass(status);
            statusText.text(message);
            
            // Update the overall status indicator styling
            overallStatusDiv.removeClass('status-ok status-error status-warning status-unknown status-checking')
                            .addClass('status-' + status);
        }
    }

    // Enhanced health check integration
    function enhanceHealthCheckForBusinessView() {
        // Hook into existing health check success
        $(document).ajaxSuccess(function(event, xhr, settings) {
            if (settings.data && settings.data.includes('vehicle_lookup_check_upstream')) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const healthData = response.data;
                        
                        // Determine overall system status
                        setTimeout(function() {
                            const cloudflareOk = $('#cloudflare-status .status-light').hasClass('ok');
                            const vegvesenOk = $('#vegvesen-status .status-light').hasClass('ok');
                            const aiSummaryOk = $('#ai-summary-status .status-light').hasClass('ok');
                            const aiSummaryWarning = $('#ai-summary-status .status-light').hasClass('warning');
                            
                            if (cloudflareOk && vegvesenOk && (aiSummaryOk || aiSummaryWarning)) {
                                if (aiSummaryWarning) {
                                    updateOverallStatus('warning', 'AI Features Limited');
                                } else {
                                    updateOverallStatus('ok', 'All Systems Operational');
                                }
                            } else if (cloudflareOk && vegvesenOk) {
                                updateOverallStatus('warning', 'Core Services OK, AI Unknown');
                            } else if (cloudflareOk || vegvesenOk) {
                                updateOverallStatus('warning', 'Some Services Degraded');
                            } else {
                                updateOverallStatus('error', 'Service Issues Detected');
                            }
                            
                            // Check circuit breaker state
                            if (healthData.monitoring_data && healthData.monitoring_data.circuit_breaker) {
                                const cbState = healthData.monitoring_data.circuit_breaker.state;
                                if (cbState === 'OPEN' || cbState === 'HALF_OPEN') {
                                    updateOverallStatus('warning', 'Service Temporarily Degraded');
                                }
                            }
                        }, 100);
                    }
                } catch (e) {
                    // Silent fail for non-health check responses
                }
            }
        });

        // Hook into health check failures
        $(document).ajaxError(function(event, xhr, settings) {
            if (settings.data && settings.data.includes('vehicle_lookup_check_upstream')) {
                updateOverallStatus('error', 'Connection Failed');
            }
        });
    }

    // Initialize dual-audience dashboard
    initializeDualAudienceDashboard();
    enhanceHealthCheckForBusinessView();

});