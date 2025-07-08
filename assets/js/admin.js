
jQuery(document).ready(function($) {
    
    // Test API connectivity
    $('#test-api').on('click', function() {
        const button = $(this);
        
        button.prop('disabled', true).text('Testing...');
        testApiStatus();
        
        setTimeout(function() {
            button.prop('disabled', false).text('Test Connection');
        }, 2000);
    });
    
    // Auto-check API status on page load
    if ($('#api-status').length) {
        setTimeout(function() {
            testApiStatus();
        }, 500);
    }
    
    function testApiStatus() {
        const statusDiv = $('#api-status');
        
        statusDiv.html('<span class="status-indicator checking">●</span> Checking connection...');
        
        $.ajax({
            url: vehicleLookupAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup_test_api',
                nonce: vehicleLookupAdmin.nonce
            },
            timeout: 10000, // 10 second timeout
            success: function(response) {
                console.log('API Test Response:', response);
                if (response.success) {
                    statusDiv.html(
                        '<span class="status-indicator ok">●</span> ' + 
                        response.data.message +
                        (response.data.response_time ? ' (' + response.data.response_time + ')' : '')
                    );
                } else {
                    statusDiv.html('<span class="status-indicator error">●</span> ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('API Test Error:', status, error, xhr.responseText);
                statusDiv.html('<span class="status-indicator error">●</span> Connection test failed: ' + error);
            }
        });
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
