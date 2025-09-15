jQuery(document).ready(function($) {

    // Test API connectivity with health check
    $('#test-api').on('click', function() {
        const button = $(this);
        const statusDiv = $('#api-status');
        const detailsDiv = $('#api-details');

        button.prop('disabled', true).text('Checking...');
        statusDiv.html('<span class="status-indicator checking">●</span> Checking health...');
        detailsDiv.hide();

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
                    
                    if (data.health_data && data.health_data.status === 'degraded') {
                        statusClass = 'warning';
                    }
                    
                    statusDiv.html(
                        '<span class="status-indicator ' + statusClass + '">●</span> ' + data.message
                    );
                    
                    if (data.details) {
                        detailsDiv.html('<small>' + data.details + '</small>').show();
                    }
                    
                    if (data.health_data) {
                        displayHealthData(data.health_data);
                    }
                } else {
                    statusDiv.html('<span class="status-indicator error">●</span> ' + response.data.message);
                    detailsDiv.hide();
                }
            },
            error: function() {
                statusDiv.html('<span class="status-indicator error">●</span> Connection test failed');
                detailsDiv.hide();
            },
            complete: function() {
                button.prop('disabled', false).text('Health Check');
            }
        });
    });

    // Auto-check API status on page load
    if ($('#api-status').length) {
        setTimeout(function() {
            $('#test-api').trigger('click');
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

        button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Resetting...ng...');

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup_reset_analytics',
                nonce: vehicleLookupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Analytics data has been successfully reset: ' + response.data.message);
                    location.reload();
                } else {
                    alert('Error resetting analytics data: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Connection error while resetting analytics data: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Check upstream health
    $('#check-upstream').on('click', function() {
        const button = $(this);
        const statusDiv = $('#api-status');
        const detailsDiv = $('#api-details');

        button.prop('disabled', true).text('Checking...');
        statusDiv.html('<span class="status-indicator checking">●</span> Checking upstream...');

        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup_check_upstream',
                nonce: vehicleLookupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const healthData = response.data.health_data;
                    let statusClass = 'ok';
                    
                    if (healthData.status === 'degraded') {
                        statusClass = 'warning';
                    }
                    
                    statusDiv.html('<span class="status-indicator ' + statusClass + '">●</span> Upstream: ' + healthData.status);
                    
                    if (healthData.upstream) {
                        const upstream = healthData.upstream;
                        let upstreamMsg = 'Vegvesen API: ' + upstream.status;
                        if (upstream.responseTime) {
                            upstreamMsg += ' (' + upstream.responseTime + 'ms)';
                        }
                        detailsDiv.html('<small>' + upstreamMsg + '</small>').show();
                    }
                    
                    displayHealthData(healthData);
                } else {
                    statusDiv.html('<span class="status-indicator error">●</span> ' + response.data.message);
                    detailsDiv.hide();
                }
            },
            error: function() {
                statusDiv.html('<span class="status-indicator error">●</span> Upstream check failed');
                detailsDiv.hide();
            },
            complete: function() {
                button.prop('disabled', false).text('Check Upstream');
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