jQuery(document).ready(function($) {
    // Initialize tabs
    function initializeTabs() {
        $('.tabs li:first-child a').addClass('active');
        $('.tab-panel:first-child').show().siblings('.tab-panel').hide();
    }

    // Handle tab clicks
    $(document).on('click', '.tabs a', function(e) {
        e.preventDefault();
        const tabId = $(this).parent().data('tab');

        // Update active tab
        $('.tabs a').removeClass('active');
        $(this).addClass('active');

        // Show selected tab panel
        $('.tab-panel').hide();
        $('#' + tabId).show();
    });

    $('#vehicle-lookup-form').on('submit', function(e) {
        e.preventDefault();

        const regNumber = $('#regNumber').val().trim();
        const resultsDiv = $('#vehicle-lookup-results');
        const errorDiv = $('#vehicle-lookup-error');

        // Reset all states
        resultsDiv.hide();
        errorDiv.hide().empty();
        $('.vehicle-tags').remove();
        $('.vehicle-title').empty();
        $('.vehicle-subtitle').empty();
        $('.vehicle-logo').attr('src', '');
        $('.info-table').empty();

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
        $(this).find('button').prop('disabled', true).addClass('loading');

        // Make AJAX request
        $.ajax({
            url: vehicleLookupAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'vehicle_lookup',
                nonce: vehicleLookupAjax.nonce,
                regNumber: regNumber,
                enable_logging: $('#enable-logging').is(':checked')
            },
            dataType: 'json',
            contentType: 'application/x-www-form-urlencoded',
            timeout: 15000,
            success: function(response) {
                if (response.success && response.data) {
                    // Display remaining quota
                    if (response.data.gjenstaendeKvote !== undefined) {
                        $('#quota-display')
                            .html(`Remaining quota: ${response.data.gjenstaendeKvote}`)
                            .show();
                    }
                    
                    // Log response if logging is enabled
                    if ($('#enable-logging').is(':checked')) {
                        console.log("API Response:", response.data);
                    }

                    if (!response.data.responser || response.data.responser.length === 0 || !response.data.responser[0]?.kjoretoydata) {
                        errorDiv.html('No valid vehicle data found for this registration number').show();
                        return;
                    }

                    // Clear existing vehicle tags before adding new ones
                    $('.vehicle-info .vehicle-tags').remove();

                    const vehicleData = response.data.responser[0].kjoretoydata;

                    // Set vehicle title and subtitle with safe access
                    if (vehicleData.kjoretoyId?.kjennemerke) {
                        $('.vehicle-title').text(vehicleData.kjoretoyId.kjennemerke);
                    } else {
                        $('.vehicle-title').text(regNumber);
                    }

                    // Set manufacturer logo
                    if (vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt?.merke?.[0]?.merke) {
                        const manufacturer = vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt.merke[0].merke.toLowerCase();
                        const logoUrl = `https://www.carlogos.org/car-logos/${manufacturer}-logo.png`;
                        $('.vehicle-logo').attr('src', logoUrl).attr('alt', `${manufacturer} logo`);
                    } else {
                        $('.vehicle-logo').attr('src', '').attr('alt', 'No logo available');
                    }
                    if (vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt) {
                        const generalData = vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt;
                        let subtitle = '';

                        if (generalData.merke?.[0]?.merke) {
                            subtitle += generalData.merke[0].merke + ' ';
                        }

                        if (generalData.handelsbetegnelse?.[0]) {
                            subtitle += generalData.handelsbetegnelse[0];
                        }

                        // Add registration year if available
                        const regYear = vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato?.split('-')[0];
                        if (regYear) {
                            subtitle += ` <strong>${regYear}</strong>`;
                        }

                        $('.vehicle-subtitle').html(subtitle);

                        // Add vehicle tags
                        const engineData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.motorOgDrivverk;
                        const fuelType = engineData?.motor?.[0]?.arbeidsprinsipp?.kodeBeskrivelse;
                        const transmission = engineData?.girkassetype?.kodeBeskrivelse;

                        let tags = '';

                        // Fuel type tags
                        if (fuelType) {
                            const fuelEmoji = {
                                'Diesel': '⛽',
                                'Bensin': '⛽',
                                'Elektrisk': '⚡',
                                'Hybrid': '🔋',
                                'Plugin-hybrid': '🔌',
                                'Hydrogen': '💨',
                                'Gass': '💨'
                            }[fuelType] || '⛽';

                            const fuelClass = fuelType.toLowerCase().replace('-', '');
                            tags += `<span class="tag fuel ${fuelClass}">${fuelEmoji} ${fuelType}</span>`;
                        }

                        // Transmission tag
                        if (transmission) {
                            const gearboxClass = transmission.toLowerCase() === 'manuell' ? 'manual' : 'automatic';
                            tags += `<span class="tag gearbox ${gearboxClass}">⚙️ ${transmission}</span>`;
                        }

                        $('.vehicle-info').append(`<div class="vehicle-tags">${tags}</div>`);
                    }

                    // Add status display
                    const status = vehicleData.registrering?.registreringsstatus?.kodeVerdi || '';
                    const statusText = vehicleData.registrering?.registreringsstatus?.kodeBeskrivelse || '';
                    const euDeadline = vehicleData.periodiskKjoretoyKontroll?.kontrollfrist;

                    // Remove any existing status displays
                    $('.vehicle-status, .eu-status').remove();

                    // Add status badge for all statuses
                    if (status) {
                        const statusClass = status.toLowerCase();
                        $('.vehicle-subtitle').after(`<p class="vehicle-status ${statusClass}"> ${statusText}</p>`);
                        
                        // Only show EU control status for registered vehicles
                        if (status === 'REGISTRERT' && euDeadline) {
                            const today = new Date();
                            const deadline = new Date(euDeadline);
                            const daysUntilDeadline = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));
                            
                            let euStatusClass = '';
                            if (daysUntilDeadline < 0) {
                                euStatusClass = 'overdue';
                            } else if (daysUntilDeadline <= 30) {
                                euStatusClass = 'warning';
                            }
                            
                            // Format date as DD-MM-YYYY with bold year
                            const day = deadline.getDate().toString().padStart(2, '0');
                            const month = (deadline.getMonth() + 1).toString().padStart(2, '0');
                            const year = deadline.getFullYear();
                            const formattedDate = `${day}-${month}-<strong>${year}</strong>`;
                            
                            $('.vehicle-status').after(`<p class="eu-status ${euStatusClass}">Frist EU-kontroll: ${formattedDate}</p>`);
                        }
                    }


                    // Parse and display data for each section
                    renderOwnerInfo(vehicleData);
                    renderBasicInfo(vehicleData);
                    renderTechnicalInfo(vehicleData);
                    renderRegistrationInfo(vehicleData);

                    // Keep all details elements open by default
                    $('details').attr('open', true);

                    // Initialize tabs
                    initializeTabs();
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
                    .removeClass('loading');
            }
        });
    });

    function renderOwnerInfo(vehicleData) {
        if (!vehicleData.eierskap?.eier) return;
        
        const eier = vehicleData.eierskap.eier;
        const person = eier.person;
        const adresse = eier.adresse;
        
        const ownerInfo = {
            'Eier': person ? `${person.fornavn} ${person.etternavn}` : '',
            'Adresse': adresse?.adresselinje1 || '',
            'Postnummer': adresse?.postnummer || '',
            'Poststed': adresse?.poststed || ''
        };

        $('.owner-info-table').html(
            Object.entries(ownerInfo)
                .filter(([_, value]) => value)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                .join('')
        );
    }

    function renderBasicInfo(vehicleData) {
        if (!vehicleData) return;

        const basicInfo = extractBasicInfo(vehicleData);
        $('.general-info-table').html(
            Object.entries(basicInfo)
                .filter(([_, value]) => value)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                .join('')
        );

        // Size and weight info with null checking
        const dimensions = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.dimensjoner;
        const vekter = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.vekter;

        const weightInfo = {
            'Lengde': dimensions?.lengde ? dimensions.lengde + ' mm' : '',
            'Bredde': dimensions?.bredde ? dimensions.bredde + ' mm' : '',
            'Høyde': dimensions?.hoyde ? dimensions.hoyde + ' mm' : '',
            'Egenvekt': vekter?.egenvekt ? vekter.egenvekt + ' kg' : '',
            'Nyttelast': vekter?.nyttelast ? vekter.nyttelast + ' kg' : ''
        };

        $('.size-weight-table').html(
            Object.entries(weightInfo)
                .filter(([_, value]) => value)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                .join('')
        );
    }

    function renderTechnicalInfo(vehicleData) {
        const tekniskeData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData;
        const engineData = tekniskeData?.motorOgDrivverk;
        const dekkOgFelg = tekniskeData?.dekkOgFelg?.akselDekkOgFelgKombinasjon?.[0]?.akselDekkOgFelg;

        // Render tire info for front axle
        const frontTire = dekkOgFelg?.find(axle => axle.akselId === 1);
        const tireInfo = {
            'Dekkdimensjon foran': frontTire?.dekkdimensjon,
            'Felgdimensjon foran': frontTire?.felgdimensjon,
            'Innpress foran': frontTire?.innpress ? frontTire.innpress + ' mm' : null,
            'Belastningskode foran': frontTire?.belastningskodeDekk,
            'Hastighetskode foran': frontTire?.hastighetskodeDekk
        };

        // Add rear axle info if available
        const rearTire = dekkOgFelg?.find(axle => axle.akselId === 2);
        if (rearTire) {
            Object.assign(tireInfo, {
                'Dekkdimensjon bak': rearTire.dekkdimensjon,
                'Felgdimensjon bak': rearTire.felgdimensjon,
                'Innpress bak': rearTire.innpress ? rearTire.innpress + ' mm' : null,
                'Belastningskode bak': rearTire.belastningskodeDekk,
                'Hastighetskode bak': rearTire.hastighetskodeDekk
            });
        }

        $('.tire-info-table').html(
            Object.entries(tireInfo)
                .filter(([_, value]) => value)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                .join('')
        );


        const engineInfo = {
            'Motor': engineData?.motor?.[0]?.antallSylindre + ' sylindre',
            'Drivstoff': engineData?.motor?.[0]?.arbeidsprinsipp?.kodeBeskrivelse,
            'Slagvolum': engineData?.motor?.[0]?.slagvolum + ' ccm',
            'Effekt': engineData?.motor?.[0]?.drivstoff?.[0]?.maksNettoEffekt + ' kW',
            'Girkasse': engineData?.girkassetype?.kodeBeskrivelse
        };

        $('.engine-info-table').html(
            Object.entries(engineInfo)
                .filter(([_, value]) => value)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                .join('')
        );
    }

    function renderRegistrationInfo(vehicleData) {
        const regInfo = {
            'Registreringsnummer': vehicleData.kjoretoyId?.kjennemerke,
            'Første registrering': vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato,
            'Status': vehicleData.registrering?.registreringsstatus?.kodeBeskrivelse,
            'Neste EU-kontroll': vehicleData.periodiskKjoretoyKontroll?.kontrollfrist
        };

        $('.registration-info-table').html(
            Object.entries(regInfo)
                .filter(([_, value]) => value)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
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
            'Farge': tekniskeData?.karosseriOgLasteplan?.rFarge?.[0]?.kodeBeskrivelse,
            'Type': tekniskeData?.generelt?.tekniskKode?.kodeBeskrivelse,
            'Antall seter': tekniskeData?.persontall?.sitteplasserTotalt
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