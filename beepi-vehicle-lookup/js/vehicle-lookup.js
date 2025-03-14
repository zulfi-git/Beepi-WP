jQuery(document).ready(function($) {
    const form = $('#vehicle-search-form');
    const resultsContainer = $('#vehicle-results');
    const alertContainer = $('#alert-container');
    const spinner = form.find('.spinner-border');
    
    function showLoading() {
        spinner.removeClass('d-none');
        form.find('button[type="submit"]').prop('disabled', true);
    }
    
    function hideLoading() {
        spinner.addClass('d-none');
        form.find('button[type="submit"]').prop('disabled', false);
    }
    
    function showAlert(message, type = 'danger') {
        alertContainer.html(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
    }
    
    function displayVehicleInfo(data) {
        const vehicleInfo = `
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Vehicle Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th>Registration</th>
                                <td>${data.registration || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Make</th>
                                <td>${data.make || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Model</th>
                                <td>${data.model || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Year</th>
                                <td>${data.year || 'N/A'}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        resultsContainer.html(vehicleInfo);
    }
    
    form.on('submit', function(e) {
        e.preventDefault();
        
        const registrationNumber = $('#registration-number').val().trim();
        alertContainer.empty();
        resultsContainer.empty();
        
        if (!registrationNumber) {
            showAlert('Please enter a registration number');
            return;
        }
        
        showLoading();
        
        $.ajax({
            url: beepiConfig.ajaxurl,
            method: 'POST',
            data: {
                action: 'beepi_vehicle_lookup',
                nonce: beepiConfig.nonce,
                registration: registrationNumber
            },
            success: function(response) {
                if (response.success) {
                    displayVehicleInfo(response.data);
                } else {
                    showAlert(response.data.message || 'Failed to fetch vehicle information');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while fetching vehicle information';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                showAlert(errorMessage);
            },
            complete: hideLoading
        });
    });
});
