
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
});

function resetAnalytics() {
    // Show confirmation dialog
    if (!confirm('Are you sure you want to delete ALL analytics data? This action cannot be undone.')) {
        return;
    }
    
    // Disable button and show loading
    const $button = $('#reset-analytics');
    const originalText = $button.text();
    $button.prop('disabled', true).text('Deleting...');
    
    $.post(vehicleLookupAdmin.ajaxurl, {
        action: 'vehicle_lookup_reset_analytics',
        nonce: vehicleLookupAdmin.nonce
    }, function(response) {
        if (response.success) {
            alert('Success: ' + response.data.message);
            location.reload();
        } else {
            alert('Error: ' + (response.data ? response.data.message : 'Unknown error occurred'));
            $button.prop('disabled', false).text(originalText);
        }
    }).fail(function(xhr, status, error) {
        alert('Network error: ' + error);
        $button.prop('disabled', false).text(originalText);
    });
});
