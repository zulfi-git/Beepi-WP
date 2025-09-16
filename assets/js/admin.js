jQuery(document).ready(function($) {

    // Auto-check service status on page load
    function checkServiceStatus() {
        checkCloudflareStatus();
        // Vegvesen status will be updated when Cloudflare check completes
    }

    function checkCloudflareStatus() {
        const statusDiv = $('#cloudflare-status');
        const detailsDiv = $('#api-details');

        statusDiv.find('.status-light').removeClass('ok error warning unknown').addClass('checking');
        statusDiv.find('.status-text').text('Checking...');

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
                    let statusClass = 'ok';
                    let statusText = 'Online';

                    if (data.health_data && data.health_data.status === 'degraded') {
                        statusClass = 'warning';
                        statusText = 'Degraded';
                    }

                    statusDiv.find('.status-light').removeClass('checking ok error warning').addClass(statusClass);
                    statusDiv.find('.status-text').text(statusText);

                    if (data.details || (data.response_time && data.response_time !== 'Unknown')) {
                        let details = 'Cloudflare Worker: ' + statusText;
                        if (data.response_time && data.response_time !== 'Unknown') {
                            details += ' (' + data.response_time + ')';
                        }
                        detailsDiv.html('<small>' + details + '</small>').show();
                    }

                    // Now check upstream
                    if (data.health_data) {
                        displayHealthData(data.health_data);
                        checkUpstreamStatus();
                    }
                } else {
                    statusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
                    statusDiv.find('.status-text').text('Error');
                    detailsDiv.html('<small>Cloudflare Worker: ' + response.data.message + '</small>').show();
                }
            },
            error: function() {
                statusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
                statusDiv.find('.status-text').text('Offline');
                detailsDiv.html('<small>Cloudflare Worker: Connection failed</small>').show();
            }
        });
    }

    function checkUpstreamStatus() {
        const statusDiv = $('#vegvesen-status');

        statusDiv.find('.status-light').removeClass('ok error warning unknown').addClass('checking');
        statusDiv.find('.status-text').text('Checking...');

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup_check_upstream',
                nonce: vehicleLookupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const healthData = response.data.health_data || response.data.details || response.data;
                    const monitoringData = response.data.monitoring_data || {};
                    let statusClass = 'ok';
                    let statusText = 'Online';

                    if (healthData && healthData.status) {
                        if (healthData.status === 'degraded') {
                            statusClass = 'warning';
                            statusText = 'Degraded';
                        } else if (healthData.status === 'healthy') {
                            statusClass = 'ok';
                            statusText = 'Online';
                        }
                    }

                    statusDiv.find('.status-light').removeClass('checking ok error warning').addClass(statusClass);
                    statusDiv.find('.status-text').text(statusText);

                    // Display API details
                    if (healthData && healthData.upstream && healthData.upstream.responseTime) {
                        const detailsDiv = $('#api-details');
                        const currentDetails = detailsDiv.html();
                        const vegvesenDetails = 'Vegvesen API: ' + statusText + ' (' + healthData.upstream.responseTime + 'ms)';
                        detailsDiv.html(currentDetails + '<br><small>' + vegvesenDetails + '</small>').show();
                    }

                    // Display enhanced monitoring data
                    displayMonitoringData(monitoringData);
                } else {
                    statusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
                    statusDiv.find('.status-text').text('Error');
                }
            },
            error: function() {
                statusDiv.find('.status-light').removeClass('checking ok warning').addClass('error');
                statusDiv.find('.status-text').text('Unknown');
            }
        });
    }

    function displayMonitoringData(monitoringData) {
        const monitoringDiv = $('#monitoring-data');
        
        if (!monitoringData || Object.keys(monitoringData).length === 0) {
            monitoringDiv.hide();
            return;
        }

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