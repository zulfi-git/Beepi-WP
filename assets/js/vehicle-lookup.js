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

    // Handle mobile number input
    $('.vipps-mobile-form').on('submit', function(e) {
        e.preventDefault();
        const mobile = $('#mobileNumber').val().replace(/[^0-9]/g, '');
        if (mobile.length === 8) {
            document.cookie = `vehicle_lookup_mobile=${mobile};path=/`;
            // Trigger Vipps button click after setting cookie
            $('.woo-vipps-checkout-button').click();
        }
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

        // Registration number handling is now managed by WooCommerce Vipps plugin
        console.log('Registration number:', regNumber);
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

        const tooltips = {
            'Lengde': 'Total lengde av kjøretøyet fra front til hekk',
            'Bredde': 'Total bredde av kjøretøyet inkludert speil',
            'Høyde': 'Total høyde av kjøretøyet fra bakken',
            'Egenvekt': 'Vekt av kjøretøyet uten last eller passasjerer',
            'Nyttelast': 'Maksimal last kjøretøyet kan bære'
        };

        $('.size-weight-table').html(
            Object.entries(weightInfo)
                .filter(([_, value]) => value)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                .join('')
        );

        // Add click handler for tooltips
        $('.tooltip-icon').on('click', function(e) {
            e.stopPropagation();
            const tooltip = $(this).find('.tooltip-text');

            // Hide all other tooltips
            $('.tooltip-text').not(tooltip).removeClass('active');

            // Position and show this tooltip
            const icon = $(this);
            const iconPos = icon.offset();
            const tooltipWidth = tooltip.outerWidth();
            const windowWidth = $(window).width();

            tooltip.toggleClass('active');

            if (tooltip.hasClass('active')) {
                let left = iconPos.left;

                // Ensure tooltip doesn't go off-screen
                if (left + tooltipWidth > windowWidth) {
                    left = windowWidth - tooltipWidth - 10;
                }

                tooltip.css({
                    top: iconPos.top - tooltip.outerHeight() - 10,
                    left: left
                });
            }
        });

        // Close tooltips when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.tooltip-icon').length) {
                $('.tooltip-text').removeClass('active');
            }
        });
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

    function renderTimeline(vehicleData) {
        function getTimeContext(date) {
            const now = new Date();
            const eventDate = new Date(date);
            const diffDays = Math.floor((now - eventDate) / (1000 * 60 * 60 * 24));
            const diffMonths = Math.floor(diffDays / 30);
            const diffYears = Math.floor(diffDays / 365);

            if (diffDays < 0) {
                // Future date
                const remainingDays = Math.abs(diffDays);
                if (remainingDays > 365) {
                    return `Om ${Math.floor(remainingDays / 365)} år`;
                } else if (remainingDays > 30) {
                    return `Om ${Math.floor(remainingDays / 30)} måneder`;
                }
                return `Om ${remainingDays} dager`;
            } else {
                // Past date
                if (diffYears > 0) {
                    return `${diffYears} ${diffYears === 1 ? 'år' : 'år'} siden`;
                } else if (diffMonths > 0) {
                    return `${diffMonths} ${diffMonths === 1 ? 'måned' : 'måneder'} siden`;
                }
                return `${diffDays} ${diffDays === 1 ? 'dag' : 'dager'} siden`;
            }
        }

        const timelineEvents = [];

        // First registration
        const firstRegDate = vehicleData.godkjenning?.forstegangsGodkjenning?.forstegangRegistrertDato;
        if (firstRegDate) {
            timelineEvents.push({
                date: firstRegDate,
                label: 'Første registrering',
                context: getTimeContext(firstRegDate),
                isFuture: false
            });
        }

        // Import to Norway
        const importDate = vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato;
        if (importDate && importDate !== firstRegDate) {
            timelineEvents.push({
                date: importDate,
                label: 'Registrert i Norge',
                context: getTimeContext(importDate),
                isFuture: false
            });
        }

        // Current owner
        const ownerDate = vehicleData.registrering?.registrertForstegangPaEierskap;
        if (ownerDate) {
            timelineEvents.push({
                date: ownerDate,
                label: 'Nåværende eier',
                context: getTimeContext(ownerDate),
                isFuture: false
            });
        }

        // Last EU control
        const lastEUDate = vehicleData.periodiskKjoretoyKontroll?.sistGodkjent;
        if (lastEUDate) {
            timelineEvents.push({
                date: lastEUDate,
                label: 'Siste EU-kontroll',
                context: getTimeContext(lastEUDate),
                isFuture: false
            });
        }

        // Next EU control
        const nextEUDate = vehicleData.periodiskKjoretoyKontroll?.kontrollfrist;
        if (nextEUDate) {
            timelineEvents.push({
                date: nextEUDate,
                label: 'Neste EU-kontroll',
                context: getTimeContext(nextEUDate),
                isFuture: true
            });
        }

        // Sort events by date
        timelineEvents.sort((a, b) => new Date(a.date) - new Date(b.date));

        // Create timeline HTML
        const timelineHtml = `
            <div class="timeline">
                <div class="timeline-events">
                    ${timelineEvents.map(event => `
                        <div class="timeline-event ${event.isFuture ? 'future' : ''}">
                            <div class="timeline-event-date">${formatDate(event.date)}</div>
                            <div class="timeline-event-label">${event.label}</div>
                            <div class="timeline-event-context">${event.context}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        return timelineHtml;
    }

    function renderRegistrationInfo(vehicleData) {
        // Add timeline to the owner section
        const timelineHtml = renderTimeline(vehicleData);
        $('.timeline').remove(); // Remove any existing timeline
        $('.owner-section').append(timelineHtml);

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
        return {
            'Kjennemerke': vehicleData.kjoretoyId?.kjennemerke,
            'Understellsnummer': vehicleData.kjoretoyId?.understellsnummer,
            'Merke': tekniskeData?.merke?.[0]?.merke,
            'Modell': tekniskeData?.handelsbetegnelse?.[0],
            'Farge': tekniskeData?.karosseriOgLasteplan?.rFarge?.[0]?.kodeBeskrivelse,
            'Type': tekniskeData?.generelt?.tekniskKode?.kodeBeskrivelse,
            'Antall seter': tekniskeData?.persontall?.sitteplasserTotalt,
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