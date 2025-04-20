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

        const regNumber = $('#regNumber').val().trim().toUpperCase();
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
                regNumber: regNumber
            },
            dataType: 'json',
            contentType: 'application/x-www-form-urlencoded',
            timeout: 15000,
            success: function(response) {
                if (response.success && response.data) {
                    // Debug logging
                    console.log('Response received:', response);
                    
                    // Display remaining quota
                    if (response.data.gjenstaendeKvote !== undefined) {
                        $('#quota-display')
                            .html(`Remaining quota: ${response.data.gjenstaendeKvote}`)
                            .show();
                    }

                    // Check if we have valid vehicle data
                    if (!response.data.responser) {
                        console.log('No responser array in data');
                        errorDiv.html('Ingen kjøretøydata funnet').show();
                        return;
                    }

                    // Always log response for debugging
                    console.log("=== Vehicle Lookup Response ===");
                    console.log("Registration Number:", regNumber);
                    console.log("Full Response:", response);
                    console.log("Response Data:", response.data);
                    console.log("=============================");

                    if (!response.data.responser || response.data.responser.length === 0 || !response.data.responser[0]?.kjoretoydata) {
                        errorDiv.html('No valid vehicle data found for this registration number').show();
                        return;
                    }

                    // Clear existing vehicle tags before adding new ones
                    $('.vehicle-info .vehicle-tags').remove();

                    const vehicleData = response.data.responser[0].kjoretoydata;

                    // Set vehicle title and subtitle with safe access

                    // Set manufacturer logo with fallback
                    if (vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt?.merke?.[0]?.merke) {
                        const manufacturer = vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt.merke[0].merke.toLowerCase();
                        const logoUrl = `https://www.carlogos.org/car-logos/${manufacturer}-logo.png`;
                        const fallbackUrl = 'https://beepi.no/wp-content/uploads/2024/01/car-placeholder.png';

                        $('.vehicle-logo')
                            .attr('src', logoUrl)
                            .attr('alt', `${manufacturer} logo`)
                            .on('error', function() {
                                $(this)
                                    .attr('src', fallbackUrl)
                                    .attr('alt', 'Generic car icon')
                                    .addClass('fallback-logo');
                            });
                    } else {
                        $('.vehicle-logo')
                            .attr('src', 'https://beepi.no/wp-content/uploads/2024/01/car-placeholder.png')
                            .attr('alt', 'Generic car icon')
                            .addClass('fallback-logo');
                    }
                    if (vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt) {
                        const generalData = vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt;

                        // Set brand, model and year
                        if (generalData) {
                            if (generalData.merke?.[0]?.merke) {
                                $('.brand-name').text(generalData.merke[0].merke);
                            }
                            if (generalData.handelsbetegnelse?.[0]) {
                                $('.model-name').text(generalData.handelsbetegnelse[0]);
                            }
                        }

                        // Set registration year
                        if (vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato) {
                            const regYear = vehicleData.forstegangsregistrering.registrertForstegangNorgeDato.split('-')[0];
                            $('.reg-year').text(regYear);
                        }

                        // Set classification and registration status
                        if (vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt?.tekniskKode?.kodeBeskrivelse) {
                            $('.classification-desc').text(vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt.tekniskKode.kodeBeskrivelse);
                        }

                        if (vehicleData.registrering?.registreringsstatus?.kodeBeskrivelse) {
                            $('.reg-status').text(vehicleData.registrering.registreringsstatus.kodeBeskrivelse);
                        }

                        // Registration dates
                        const regDates = {
                            'Registrert første gang': vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato,
                            'Neste frist for EU-kontroll': vehicleData.periodiskKjoretoyKontroll?.kontrollfrist,
                            'Sist EU-godkjent': vehicleData.periodiskKjoretoyKontroll?.sistGodkjent
                        };

                        let datesHtml = '<div class="registration-dates">';
                        Object.entries(regDates).forEach(([label, date]) => {
                            if (date) {
                                const [year, month, day] = date.split('T')[0].split('-');
                                const formattedDate = `${day}.${month}.<strong>${year}</strong>`;
                                datesHtml += `<div class="date-item"><span class="date-label">${label}:</span><span class="date-value">${formattedDate}</span></div>`;
                            }
                        });
                        datesHtml += '</div>';
                        
                        $('.registration-info').append(datesHtml);

                        // Add vehicle tags after reg-date
                        const engineData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.motorOgDrivverk;
                        const fuelType = engineData?.motor?.[0]?.arbeidsprinsipp?.kodeBeskrivelse;
                        const transmission = engineData?.girkassetype?.kodeBeskrivelse;

                        let tags = '';

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

                        if (transmission) {
                            const gearboxClass = transmission.toLowerCase() === 'manuell' ? 'manual' : 'automatic';
                            tags += `<span class="tag gearbox ${gearboxClass}">⚙️ ${transmission}</span>`;
                        }

                        $('.vehicle-info-right .registration-info').append(`<div class="vehicle-tags">${tags}</div>`);
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

        const hasAccess = checkOwnerAccessToken(vehicleData.kjoretoyId?.kjennemerke);
        const $ownerTable = $('.owner-info-table');
        const $purchaseDiv = $('#owner-info-purchase');
        const isConfirmationPage = $('.order-confirmation-container').length > 0;

        if (hasAccess || isConfirmationPage) {
            const eier = vehicleData.eierskap.eier;
            const person = eier.person;
            const adresse = eier.adresse;

            const ownerInfo = {
                'Eier': person ? `${person.fornavn} ${person.etternavn}` : '',
                'Adresse': adresse?.adresselinje1 || '',
                'Postnummer': adresse?.postnummer || '',
                'Poststed': adresse?.poststed || ''
            };

            $ownerTable.html(
                Object.entries(ownerInfo)
                    .filter(([_, value]) => value)
                    .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                    .join('')
            );
            $purchaseDiv.hide();
        } else {
            $ownerTable.html('');
            $purchaseDiv.show();
        }
    }

    function checkOwnerAccessToken(regNumber) {
        const token = localStorage.getItem(`owner_access_${regNumber}`);
        if (!token) return false;

        const tokenData = JSON.parse(token);
        return tokenData.expiry > Date.now();
    }

    function setRegNumberCookie(regNumber) {
        document.cookie = `vehicle_reg_number=${regNumber};path=/;max-age=3600`;
    }

    // Update form submit handler to save cookie
    $('#vehicle-lookup-form').on('submit', function(e) {
        const regNumber = $('#regNumber').val().trim().toUpperCase();
        setRegNumberCookie(regNumber);
        // ... rest of the existing submit handler
    });

    // Check URL parameters for successful payment
    const urlParams = new URLSearchParams(window.location.search);
    const orderKey = urlParams.get('key');
    const orderId = window.location.pathname.match(/order-received\/(\d+)/)?.[1];

    if (orderId && orderKey) {
        // Redirect to page ID 588
        window.location.href = `/?page_id=588&order=${orderId}&key=${orderKey}`;
    }

    // Add purchase button handler
    $(document).on('click', '.purchase-button', function() {
        const productId = $(this).data('product');
        const displayedReg = $('.vehicle-title').text().trim();
        const inputReg = $('#regNumber').val().trim().toUpperCase();
        const regNumber = displayedReg || inputReg;

        if (!regNumber) {
            console.error('No registration number found');
            return;
        }

        // Redirect to Vipps product purchase URL
        window.location.href = `https://beepi.no/vipps-buy-product/?pr=fd8b7cd7&reg_number=${encodeURIComponent(regNumber)}&custom_reg=${encodeURIComponent(regNumber)}`;
    });

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
            'Dekk dim. front': frontTire?.dekkdimensjon,
            'Felg dim. front': frontTire?.felgdimensjon,
            'Innp. front': frontTire?.innpress ? frontTire.innpress + ' mm' : null,
            'Last. kode front': frontTire?.belastningskodeDekk,
            'Hast. kode front': frontTire?.hastighetskodeDekk
        };

        // Add rear axle info if available
        const rearTire = dekkOgFelg?.find(axle => axle.akselId === 2);
        if (rearTire) {
            Object.assign(tireInfo, {
                'Dekk dim. bak': rearTire.dekkdimensjon,
                'Felg dim. bak': rearTire.felgdimensjon,
                'Innp. bak': rearTire.innpress ? rearTire.innpress + ' mm' : null,
                'Last. kode bak': rearTire.belastningskodeDekk,
                'Hast. kode bak': rearTire.hastighetskodeDekk
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
            'Reg.nr.': vehicleData.kjoretoyId?.kjennemerke,
            'Første reg.': vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato,
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
            'Reg.nr': vehicleData.kjoretoyId?.kjennemerke,
            'Chassis nr': vehicleData.kjoretoyId?.understellsnummer,
            'Merke': tekniskeData?.generelt?.merke?.[0]?.merke,
            'Modell': tekniskeData?.generelt?.handelsbetegnelse?.[0],
            'Farge': tekniskeData?.karosseriOgLasteplan?.rFarge?.[0]?.kodeBeskrivelse,
            'Type': tekniskeData?.generelt?.tekniskKode?.kodeBeskrivelse,
            'Seter': tekniskeData?.persontall?.sitteplasserTotalt
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