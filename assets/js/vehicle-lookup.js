
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
                    if (!response.data.responser || response.data.responser.length === 0) {
                        errorDiv.html('No data found for this registration number').show();
                        return;
                    }
                    
                    const vehicleData = response.data.responser[0].kjoretoydata;
                    
                    // Set vehicle title and subtitle
                    $('.vehicle-title').text(vehicleData.kjoretoyId.kjennemerke);
                    if (vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt) {
                        const generalData = vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt;
                        let subtitle = '';
                        
                        if (generalData.merke?.[0]?.merke) {
                            subtitle += generalData.merke[0].merke + ' ';
                        }
                        
                        if (generalData.handelsbetegnelse?.[0]) {
                            subtitle += generalData.handelsbetegnelse[0];
                        }
                        
                        $('.vehicle-subtitle').text(subtitle);
                    }
                    
                    // Parse and display data for each section
                    renderBasicInfo(vehicleData);
                    renderTechnicalInfo(vehicleData);
                    renderRegistrationInfo(vehicleData);
                    
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

    function renderBasicInfo(vehicleData) {
        const basicInfo = extractBasicInfo(vehicleData);
        $('#basic-info .info-content').html(
            Object.entries(basicInfo)
                .map(([label, value]) => createInfoItem(label, value))
                .join('')
        );
    }

    function renderTechnicalInfo(vehicleData) {
        const technicalInfo = extractTechnicalInfo(vehicleData);
        $('#technical-info .info-content').html(
            Object.entries(technicalInfo)
                .map(([label, value]) => createInfoItem(label, value))
                .join('')
        );
    }

    function renderRegistrationInfo(vehicleData) {
        const registrationInfo = extractRegistrationInfo(vehicleData);
        $('#registration-info .info-content').html(
            Object.entries(registrationInfo)
                .map(([label, value]) => createInfoItem(label, value))
                .join('')
        );
    }

    function extractBasicInfo(vehicleData) {
        const tekniskeData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData;
        return {
            'Kjennemerke': vehicleData.kjoretoyId?.kjennemerke,
            'Understellsnummer': vehicleData.kjoretoyId?.understellsnummer,
            'Merke': tekniskeData?.generelt?.merke?.[0]?.merke,
            'Modell': tekniskeData?.generelt?.handelsbetegnelse?.[0],
            'Farge': tekniskeData?.karosseriOgLasteplan?.rFarge?.[0]?.kodeBeskrivelse
        };
    }

    function extractTechnicalInfo(vehicleData) {
        const tekniskeData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData;
        return {
            'Drivstoff': tekniskeData?.motorOgDrivverk?.motor?.[0]?.arbeidsprinsipp?.kodeBeskrivelse,
            'Girkasse': tekniskeData?.motorOgDrivverk?.girkassetype?.kodeBeskrivelse,
            'Antall seter': tekniskeData?.persontall?.sitteplasserTotalt,
            'Egenvekt': tekniskeData?.vekter?.egenvekt ? `${tekniskeData.vekter.egenvekt} kg` : null,
            'Totalvekt': tekniskeData?.vekter?.totalvekt ? `${tekniskeData.vekter.totalvekt} kg` : null
        };
    }

    function extractRegistrationInfo(vehicleData) {
        return {
            'Første registrering': vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato,
            'Neste kontroll': vehicleData.periodiskKjoretoyKontroll?.kontrollfrist,
            'Status': vehicleData.registrering?.registreringsstatus?.kodeBeskrivelse
        };
    }

    function createInfoItem(label, value) {
        return `<div class="info-item">
            <strong>${label}:</strong>
            <span>${value || '-'}</span>
        </div>`;
    }
});
