jQuery(document).ready(function($) {
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }

    // Handle tab clicks - Removed as tabs are no longer used

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
                    // Display remaining quota
                    if (response.data.gjenstaendeKvote !== undefined) {
                        $('#quota-display')
                            .html(`Remaining quota: ${response.data.gjenstaendeKvote}`)
                            .show();
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
                    if (vehicleData.kjoretoyId?.kjennemerke) {
                        $('.vehicle-title').text(vehicleData.kjoretoyId.kjennemerke);
                    } else {
                        $('.vehicle-title').text(regNumber);
                    }

                    // Set manufacturer logo
                    const defaultLogoUrl = vehicleLookupAjax.plugin_url + '/assets/images/car.png';
                    if (vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt?.merke?.[0]?.merke) {
                        const manufacturer = vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt.merke[0].merke.toLowerCase();
                        const logoUrl = `https://www.carlogos.org/car-logos/${manufacturer}-logo.png`;

                        $('.vehicle-logo')
                            .attr('src', logoUrl)
                            .attr('alt', `${manufacturer} logo`)
                            .on('error', function() {
                                $(this)
                                    .attr('src', defaultLogoUrl)
                                    .attr('alt', 'Generic car logo');
                            });
                    } else {
                        $('.vehicle-logo')
                            .attr('src', defaultLogoUrl)
                            .attr('alt', 'Generic car logo');
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
                            let euMessage = '';

                            if (daysUntilDeadline < 0) {
                                const monthsAgo = Math.abs(Math.floor(daysUntilDeadline / 30));
                                euMessage = `EU-kontroll overskredet siden ${formattedDate} (${monthsAgo} ${monthsAgo === 1 ? 'måned' : 'måneder'} siden)`;
                            } else if (daysUntilDeadline <= 30) {
                                euMessage = `EU-kontroll haster: ${formattedDate} (${daysUntilDeadline} ${daysUntilDeadline === 1 ? 'dag' : 'dager'} igjen)`;
                            } else {
                                euMessage = `EU-kontroll nærmer seg: ${formattedDate} (${Math.floor(daysUntilDeadline / 30)} måneder igjen)`;
                            }

                            $('.vehicle-status').after(`<p class="eu-status ${euStatusClass}">${euMessage}</p>`);
                        }
                    }


                    // Parse and display data for each section
                    renderOwnerInfo(vehicleData);
                    renderBasicInfo(vehicleData);
                    renderTechnicalInfo(vehicleData);
                    renderRegistrationInfo(vehicleData);

                    // Keep all details elements open by default
                    $('details').attr('open', true);

                    // Initialize tabs - Removed as tabs are no longer used
                    $('#vehicle-lookup-results').show();

                    // Smooth scroll to results
                    $('html, body').animate({
                        scrollTop: $('.vehicle-lookup-container').offset().top - 20
                    }, 500);
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
        
        // Update URL without page reload
        if (regNumber && window.history && window.history.pushState) {
            const newUrl = '/sok/' + regNumber;
            window.history.pushState({ regNumber: regNumber }, '', newUrl);
        }sting submit handler
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

        // Registration number handling is now managed by WooCommerce Vipps plugin
        console.log('Registration number:', regNumber);
    });

    function renderBasicInfo(vehicleData) {
        const tekniskeData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData;
        const generelt = tekniskeData?.generelt;
        if (!generelt) return;

        const basicInfo = {
            'Merke': generelt.merke?.[0]?.merke || '---',
            'Modell': generelt.handelsbetegnelse?.[0] || '---',
            'Kjennemerke': vehicleData.kjoretoyId?.kjennemerke || '---',
            'Farge': vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.karosseriOgLasteplan?.rFarge?.[0]?.kodeNavn || '---',
            'Type': vehicleData.kjoretoyklassifisering?.tekniskKode?.kodeNavn || '---',
            'Antall seter': vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.persontall?.sitteplasserTotalt || '---'
        };

        $('.basic-info-table').html(
            Object.entries(basicInfo)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                .join('')
        );

        // Update notes section to be shown last
        const notes = {
            'Fabrikant': tekniskeData?.generelt?.fabrikant?.[0]?.fabrikant || '---',
            'Kjøring art': vehicleData.registrering?.kjoringensArt?.kodeBeskrivelse || '---',
            'Kjøretøymerknad': vehicleData.godkjenning?.kjoretoymerknad?.[0]?.merknad || '---',
            'Ombygget': vehicleData.godkjenning?.tekniskGodkjenning?.merknadListe?.find(m => m.type === 'OMBYGGET')?.merknadTekst || '---',
            'Oppbygget': vehicleData.godkjenning?.tekniskGodkjenning?.merknadListe?.find(m => m.type === 'OPPBYGGET')?.merknadTekst || '---',
            'Bruktimportert': vehicleData.godkjenning?.tekniskGodkjenning?.merknadListe?.find(m => m.type === 'BRUKTIMPORTERT')?.merknadTekst || '---',
            'Bevaringsverdig': vehicleData.godkjenning?.tekniskGodkjenning?.merknadListe?.find(m => m.type === 'BEVARINGSVERDIG')?.merknadTekst || '---'
        };

        $('.notes-info-table').html(
            Object.entries(notes)
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
                .map(([label, value]) => {
                    const tooltips = {
                        'Dekkdimensjon': 'Dekkstørrelse angitt som bredde/høydeprofil-felgdiameter',
                        'Hastighetsindeks': 'Bokstavkode som angir maksimal hastighet dekket er godkjent for',
                        'Lastindeks': 'Tallkode som angir maksimal belastning dekket tåler'
                    };
                    const tooltip = tooltips[label] ? ` title="${tooltips[label]}"` : '';
                    return `<tr><th${tooltip}>${label}</th><td${tooltip}>${value}</td></tr>`;
                })
                .join('')
        );


        // Get size and weight data
        const vekter = tekniskeData?.vekter;
        const dimensjoner = tekniskeData?.dimensjoner;
        
        const weightInfo = {
            'Lengde': tekniskeData?.dimensjoner?.lengde ? `${tekniskeData.dimensjoner.lengde} mm` : '---',
            'Bredde': tekniskeData?.dimensjoner?.bredde ? `${tekniskeData.dimensjoner.bredde} mm` : '---',
            'Høyde': tekniskeData?.dimensjoner?.hoyde ? `${tekniskeData.dimensjoner.hoyde} mm` : '---',
            'Egenvekt': tekniskeData?.vekter?.egenvekt ? `${tekniskeData.vekter.egenvekt} kg` : '---',
            'Nyttelast': tekniskeData?.vekter?.nyttelast ? `${tekniskeData.vekter.nyttelast} kg` : '---',
            'Tillatt totalvekt': tekniskeData?.vekter?.tillattTotalvekt ? `${tekniskeData.vekter.tillattTotalvekt} kg` : '---',
            'Tillatt tilhengervekt m/brems': tekniskeData?.vekter?.tillattTilhengervektMedBrems ? `${tekniskeData.vekter.tillattTilhengervektMedBrems} kg` : '---',
            'Tillatt tilhengervekt u/brems': tekniskeData?.vekter?.tillattTilhengervektUtenBrems ? `${tekniskeData.vekter.tillattTilhengervektUtenBrems} kg` : '---',
            'Tillatt vogntogvekt': tekniskeData?.vekter?.tillattVogntogvekt ? `${tekniskeData.vekter.tillattVogntogvekt} kg` : '---'
        };

        $('.size-weight-table').html(
            Object.entries(weightInfo)
                .filter(([_, value]) => value !== '---')
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
            'Reg. første gang': formatDate(vehicleData.godkjenning?.forstegangsGodkjenning?.forstegangRegistrertDato),
            'Reg. i Norge': formatDate(vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato),
            'Reg. på eier': formatDate(vehicleData.registrering?.registrertForstegangPaEierskap),
            'Status': vehicleData.registrering?.registreringsstatus?.kodeBeskrivelse,
            'EU-kontroller': '',  // Spacer for visual grouping
            'Siste EU-kontroll': formatDate(vehicleData.periodiskKjoretoyKontroll?.sistGodkjent),
            'Neste EU-kontroll': formatDate(vehicleData.periodiskKjoretoyKontroll?.kontrollfrist)
        };

        const tableHtml = Object.entries(regInfo)
            .filter(([_, value]) => value)
            .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
            .join('');

        $('.registration-info-table').html(tableHtml);
    }

    function extractBasicInfo(vehicleData) {
        const tekniskeData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData;
        const generelt = tekniskeData?.generelt;
        if (!generelt) return {}; // Return empty object if generelt is missing

        return {
            'Kjennemerke': vehicleData.kjoretoyId?.kjennemerke,
            'Understellsnummer': vehicleData.kjoretoyId?.understellsnummer,
            'Merke': generelt.merke?.[0]?.merke,
            'Modell': generelt.handelsbetegnelse?.[0],
            'Farge': generelt.farge?.[0]?.kodeBeskrivelse,
            'Type': generelt.type,
            'Antall seter': generelt.sitteplasserTotalt,
            'Ombygget': vehicleData.godkjenning?.tekniskGodkjenning?.merknadListe?.find(m => m.type === 'OMBYGGET')?.merknadTekst || '---',
            'Oppbygget': vehicleData.godkjenning?.tekniskGodkjenning?.merknadListe?.find(m => m.type === 'OPPBYGGET')?.merknadTekst || '---',
            'Bruktimportert': vehicleData.godkjenning?.tekniskGodkjenning?.merknadListe?.find(m => m.type === 'BRUKTIMPORTERT')?.merknadTekst || '---',
            'Bevaringsverdig': vehicleData.godkjenning?.tekniskGodkjenning?.merknadListe?.find(m => m.type === 'BEVARINGSVERDIG')?.merknadTekst || '---',
            'Fabrikant': tekniskeData?.generelt?.fabrikant?.[0]?.fabrikant || '---',
            'Kjøring art': tekniskeData?.generelt?.kjoeringArt?.kodeBeskrivelse || '---',
            'Kjøretøymerknad': tekniskeData?.generelt?.merknad || '---'
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
    // Add CSS for timeline margin
    $('.timeline').css('margin', '20px 0 50px 0');
});
// Handle browser back/forward
window.onpopstate = function(event) {
    const match = window.location.pathname.match(/\/sok\/([A-Za-z0-9]+)/);
    if (match && match[1]) {
        const regNumber = match[1].toUpperCase();
        $('#regNumber').val(regNumber);
        $('#vehicle-lookup-form').trigger('submit');
    }
};
