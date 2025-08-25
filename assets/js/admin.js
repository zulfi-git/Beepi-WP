
jQuery(document).ready(function($) {
    
    // Test API connectivity
    $('#test-api').on('click', function() {
        const button = $(this);
        const statusDiv = $('#api-status');
        
        button.prop('disabled', true).text('Testing...');
        statusDiv.html('<span class="status-indicator checking">●</span> Testing connection...');
        
        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup_test_api',
                nonce: vehicleLookupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.html(
                        '<span class="status-indicator ok">●</span> ' + 
                        response.data.message +
                        (response.data.response_time ? ' (' + response.data.response_time + ')' : '')
                    );
                } else {
                    statusDiv.html('<span class="status-indicator error">●</span> ' + response.data.message);
                }
            },
            error: function() {
                statusDiv.html('<span class="status-indicator error">●</span> Connection test failed');
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
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
    
    // Debug: Check if reset button exists and script is loading
    console.log('Admin script loaded');
    console.log('Reset button found:', $('#reset-analytics').length);
    console.log('Current page:', window.location.href);
    
    // Only bind reset handler if button exists
    if ($('#reset-analytics').length > 0) {
        console.log('Binding reset analytics handler');
        
        // Reset analytics data
        $('#reset-analytics').on('click', function() {
        console.log('Reset button clicked'); // Debug log
        
        if (!confirm('Are you sure you want to reset all analytics data? This action cannot be undone.')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.html();
        
        console.log('AJAX URL:', vehicleLookupAdmin.ajaxurl); // Debug log
        console.log('Nonce:', vehicleLookupAdmin.nonce); // Debug log
        
        button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Resetting...');
        
        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup_reset_analytics',
                nonce: vehicleLookupAdmin.nonce
            },
            beforeSend: function() {
                console.log('Sending AJAX request...'); // Debug log
            },
            success: function(response) {
                console.log('AJAX Response:', response); // Debug log
                if (response.success) {
                    alert('Analytics data has been successfully reset: ' + response.data.message);
                    location.reload(); // Refresh page to show updated stats
                } else {
                    alert('Error resetting analytics data: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr, status, error); // Debug log
                alert('Connection error while resetting analytics data: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    } else {
        console.log('Reset button not found on this page');
    }
});
