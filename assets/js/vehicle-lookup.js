jQuery(document).ready(function($) {
    $('#vehicle-lookup-form').on('submit', function(e) {
        e.preventDefault();
        
        const regNumber = $('#regNumber').val().trim();
        const resultsDiv = $('#vehicle-lookup-results');
        const errorDiv = $('#vehicle-lookup-error');
        
        // Hide previous results/errors
        resultsDiv.hide();
        errorDiv.hide();
        
        // Validate Norwegian registration number
        const validFormats = [
            /^[A-Z]{2}\d{4,5}$/,           // Standard vehicles and others
            /^E[KLVBCDE]\d{5}$/,           // Electric vehicles
            /^CD\d{5}$/,                   // Diplomatic vehicles
            /^\d{5}$/,                     // Temporary tourist plates
            /^[A-Z]\d{3}$/,               // Antique vehicles
            /^[A-Z]{2}\d{3}$/             // Provisional plates
        ];
        
        const isValid = validFormats.some(format => format.test(regNumber));
        if (!regNumber || !isValid) {
            errorDiv.html('Please enter a valid Norwegian registration number').show();
            return;
        }
        
        // Show loading state
        $(this).find('button').prop('disabled', true).text('Looking up...');
        
        // Make AJAX request
        $.ajax({
            url: vehicleLookupAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup',
                nonce: vehicleLookupAjax.nonce,
                regNumber: regNumber
            },
            dataType: 'json',
            contentType: 'application/x-www-form-urlencoded',
            timeout: 15000,
            success: function(response) {
                if (response.success && response.data) {
                    if (!response.data || !response.data.responser || response.data.responser.length === 0) {
                        errorDiv.html('No data found for this registration number').show();
                        return;
                    }
                    
                    const vehicleData = response.data.responser[0].kjoretoydata;
                    let html = '<table class="vehicle-info-table">';
                    
                    // Basic vehicle info
                    const basicInfo = {
                        'Kjennemerke': vehicleData.kjoretoyId.kjennemerke,
                        'Merke': vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt.merke[0].merke,
                        'Model': vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt.handelsbetegnelse[0],
                        'Første registrering': vehicleData.forstegangsregistrering.registrertForstegangNorgeDato,
                        'Farge': vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.karosseriOgLasteplan.rFarge[0].kodeBeskrivelse,
                        'Drivstoff': vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.motorOgDrivverk.motor[0].arbeidsprinsipp.kodeBeskrivelse,
                        'Girkasse': vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.motorOgDrivverk.girkassetype.kodeBeskrivelse,
                        'Antall seter': vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.persontall.sitteplasserTotalt,
                        'Egenvekt': vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.vekter.egenvekt + ' kg',
                        'Neste kontroll': vehicleData.periodiskKjoretoyKontroll.kontrollfrist
                    };
                    
                    for (const [key, value] of Object.entries(basicInfo)) {
                        html += `<tr>
                            <th>${key}</th>
                            <td>${value}</td>
                        </tr>`;
                    }
                    
                    html += '</table>';
                    resultsDiv.find('.results-content').html(html);
                    resultsDiv.show();
                } else {
                    errorDiv.html('Failed to retrieve vehicle information').show();
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'An error occurred: ';
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                } else if (error) {
                    errorMessage += error;
                }
                errorDiv.html(errorMessage).show();
            },
            complete: function() {
                // Reset button state
                $('#vehicle-lookup-form button')
                    .prop('disabled', false)
                    .text('Look Up Vehicle');
            }
        });
    });
});
