jQuery(document).ready(function($) {
    // Cache DOM elements
    const $form = $('#vehicle-lookup-form');
    const $resultsDiv = $('#vehicle-lookup-results');
    const $errorDiv = $('#vehicle-lookup-error');
    const $quotaDisplay = $('#quota-display');
    const $vehicleTitle = $('.vehicle-title');
    const $vehicleSubtitle = $('.vehicle-subtitle');
    const $vehicleLogo = $('.vehicle-logo');
    const $vehicleInfo = $('.vehicle-info');
    const $submitButton = $form.find('button');

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }

    function checkEUAnchor() {
        if (window.location.hash === '#EU') {
            setTimeout(function() {
                const regAccordion = $('details summary span:contains("Reg. og EU-kontroll")').closest('details');
                if (regAccordion.length) {
                    regAccordion.attr('open', true);
                    $('html, body').animate({
                        scrollTop: regAccordion.offset().top - 100
                    }, 800);
                }
            }, 1000);
        }
    }

    // Make expandAllAccordions available globally
    window.expandAllAccordions = function() {
        $('.accordion details').each(function() {
            $(this).attr('open', true);
        });
        
        // Scroll to first accordion
        const firstAccordion = $('.accordion details').first();
        if (firstAccordion.length) {
            $('html, body').animate({
                scrollTop: firstAccordion.offset().top - 150
            }, 600);
        }
        
        // Hide the guide box after use
        $('.free-info-guide').slideUp(300);
    };

    function resetFormState() {
        $resultsDiv.hide();
        $errorDiv.hide().empty();
        $('.vehicle-tags').remove();
        $('.cache-notice').remove();
        $vehicleTitle.empty();
        $vehicleSubtitle.empty();
        $vehicleLogo.attr('src', '');
        $('.info-table').empty();
    }

    function displayCacheNotice(responseData) {
        // Remove any existing cache notice
        $('.cache-notice').remove();
        
        // Check if data includes cache information
        const cacheTime = responseData.cache_time;
        const isCached = responseData.is_cached || false;
        
        let noticeText = '';
        let noticeClass = 'fresh';
        
        if (isCached && cacheTime) {
            const cacheDate = new Date(cacheTime);
            const now = new Date();
            const diffMinutes = Math.round((now - cacheDate) / (1000 * 60));
            
            if (diffMinutes < 1) {
                noticeText = '📋 Cached (< 1 min)';
            } else if (diffMinutes < 60) {
                noticeText = `📋 Cached (${diffMinutes} min)`;
            } else {
                const diffHours = Math.round(diffMinutes / 60);
                noticeText = `📋 Cached (${diffHours}h)`;
            }
            noticeClass = 'cached';
        } else {
            noticeText = '🔄 Fresh data';
            noticeClass = 'fresh';
        }
        
        // Add cache notice to results div
        $resultsDiv.prepend(`<div class="cache-notice ${noticeClass}" title="Data retrieval status for this registration number">${noticeText}</div>`);
    }

    function validateRegistrationNumber(regNumber) {
        const validFormats = [
            /^[A-Z]{2}\d{4,5}$/,           // Standard vehicles and others
            /^E[KLVBCDE]\d{5}$/,           // Electric vehicles
            /^CD\d{5}$/,                   // Diplomatic vehicles
            /^\d{5}$/,                     // Temporary tourist plates
            /^[A-Z]\d{3}$/,               // Antique vehicles
            /^[A-Z]{2}\d{3}$/             // Provisional plates
        ];
        return validFormats.some(format => format.test(regNumber));
    }

    function setLoadingState(isLoading) {
        $submitButton.prop('disabled', isLoading).toggleClass('loading', isLoading);
    }

    function displayQuota(quota) {
        if (quota !== undefined) {
            $quotaDisplay.html(`Gjenværende kvote: ${quota}`).show();
        }
    }

    function displayVehicleHeader(vehicleData, regNumber) {
        // Set vehicle title
        const kjennemerke = vehicleData.kjoretoyId?.kjennemerke;
        $vehicleTitle.text(kjennemerke || regNumber);

        // Set manufacturer logo
        const defaultLogoUrl = vehicleLookupAjax.plugin_url + '/assets/images/car.png';
        const manufacturer = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt?.merke?.[0]?.merke;
        
        if (manufacturer) {
            const logoUrl = `https://www.carlogos.org/car-logos/${manufacturer.toLowerCase()}-logo.png`;
            $vehicleLogo
                .attr('src', logoUrl)
                .attr('alt', `${manufacturer} logo`)
                .on('error', function() {
                    $(this).attr('src', defaultLogoUrl).attr('alt', 'Generic car logo');
                });
        } else {
            $vehicleLogo.attr('src', defaultLogoUrl).attr('alt', 'Generic car logo');
        }

        // Set subtitle and tags
        const generalData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt;
        if (generalData) {
            let subtitle = '';
            if (generalData.merke?.[0]?.merke) subtitle += generalData.merke[0].merke + ' ';
            if (generalData.handelsbetegnelse?.[0]) subtitle += generalData.handelsbetegnelse[0];
            
            const regYear = vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato?.split('-')[0];
            if (regYear) subtitle += ` <strong>${regYear}</strong>`;
            
            $vehicleSubtitle.html(subtitle);
            addVehicleTags(vehicleData);
        }
    }

    function addVehicleTags(vehicleData) {
        const engineData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.motorOgDrivverk;
        const fuelType = engineData?.motor?.[0]?.arbeidsprinsipp?.kodeBeskrivelse;
        const transmission = engineData?.girkassetype?.kodeBeskrivelse;

        let tags = '';
        
        if (fuelType) {
            const fuelEmoji = {
                'Diesel': '⛽', 'Bensin': '⛽', 'Elektrisk': '⚡',
                'Hybrid': '🔋', 'Plugin-hybrid': '🔌', 'Hydrogen': '💨', 'Gass': '💨'
            }[fuelType] || '⛽';
            
            const fuelClass = fuelType.toLowerCase().replace('-', '');
            tags += `<span class="tag fuel ${fuelClass}">${fuelEmoji} ${fuelType}</span>`;
        }

        if (transmission) {
            const gearboxClass = transmission.toLowerCase() === 'manuell' ? 'manual' : 'automatic';
            tags += `<span class="tag gearbox ${gearboxClass}">⚙️ ${transmission}</span>`;
        }

        $vehicleInfo.append(`<div class="vehicle-tags">${tags}</div>`);
    }

    function displayStatusInfo(vehicleData) {
        const status = vehicleData.registrering?.registreringsstatus?.kodeVerdi || '';
        const statusText = vehicleData.registrering?.registreringsstatus?.kodeBeskrivelse || '';
        const euDeadline = vehicleData.periodiskKjoretoyKontroll?.kontrollfrist;

        $('.vehicle-status, .eu-status').remove();

        if (status) {
            const statusClass = status.toLowerCase();
            $vehicleSubtitle.after(`<p class="vehicle-status ${statusClass}"> ${statusText}</p>`);

            if (status === 'REGISTRERT' && euDeadline) {
                const today = new Date();
                const deadline = new Date(euDeadline);
                const daysUntilDeadline = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));

                let euStatusClass = '';
                let euMessage = '';

                if (daysUntilDeadline < 0) {
                    euStatusClass = 'overdue';
                    const monthsAgo = Math.abs(Math.floor(daysUntilDeadline / 30));
                    euMessage = `EU-kontroll (${monthsAgo} mnd siden)`;
                } else if (daysUntilDeadline <= 30) {
                    euStatusClass = 'warning';
                    euMessage = `EU-kontroll (${daysUntilDeadline} dager igjen)`;
                } else {
                    const monthsLeft = Math.floor(daysUntilDeadline / 30);
                    euMessage = `EU-kontroll (${monthsLeft} mnd igjen)`;
                }

                $('.vehicle-status').after(`<p class="eu-status ${euStatusClass}">${euMessage}</p>`);
            }
        }
    }

    function processVehicleData(response, regNumber) {
        const vehicleData = response.data.responser[0].kjoretoydata;
        
        setRegNumberCookie(regNumber);
        displayVehicleHeader(vehicleData, regNumber);
        displayStatusInfo(vehicleData);
        
        // Show cache status notice
        displayCacheNotice(response.data);
        
        // Always show basic info for free
        renderBasicInfo(vehicleData);
        renderRegistrationInfo(vehicleData);
        
        // Show preview of premium content
        renderPremiumPreview(vehicleData);
        
        // Only show full owner info if user has access
        renderOwnerInfo(vehicleData);
        renderTechnicalInfo(vehicleData);

        // Open basic info sections by default
        $('.accordion details[data-free="true"]').attr('open', true);
        $resultsDiv.show();

        $('html, body').animate({
            scrollTop: $('.vehicle-lookup-container').offset().top - 20
        }, 500);

        checkEUAnchor();
    }

    $form.on('submit', function(e) {
        e.preventDefault();

        const regNumber = $('#regNumber').val().trim().toUpperCase();

        resetFormState();

        if (!regNumber || !validateRegistrationNumber(regNumber)) {
            $errorDiv.html('Vennligst skriv inn et gyldig norsk registreringsnummer').show();
            return;
        }

        setLoadingState(true);

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
                    displayQuota(response.data.gjenstaendeKvote);

                    // Check if we have valid vehicle data structure
                    if (!response.data.responser || response.data.responser.length === 0) {
                        $errorDiv.html('Fant ingen kjøretøy med registreringsnummer ' + regNumber + '. Dette kan være en ugyldig registreringsnummer eller kjøretøyet er ikke registrert i Norge.').show();
                        return;
                    }

                    // Check if the first response has vehicle data
                    const firstResponse = response.data.responser[0];
                    if (!firstResponse || !firstResponse.kjoretoydata) {
                        $errorDiv.html('Fant ingen kjøretøy med registreringsnummer ' + regNumber + '. Dette kan være en ugyldig registreringsnummer eller kjøretøyet er ikke registrert i Norge.').show();
                        return;
                    }

                    $('.vehicle-info .vehicle-tags').remove();
                    processVehicleData(response, regNumber);
                } else {
                    // This handles cases where success is false - should show server error message
                    $errorDiv.html(response.data || 'Kunne ikke hente kjøretøyinformasjon').show();
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'En feil oppstod: ';
                if (status === 'timeout') {
                    errorMessage = 'Forespørsel tok for lang tid. Vennligst prøv igjen.';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                } else if (error) {
                    errorMessage += error;
                }
                $errorDiv.html(errorMessage).show();
            },
            complete: function() {
                setLoadingState(false);
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
            console.error('Ingen registreringsnummer funnet');
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
                .filter(([_, value]) => value && value !== '---')
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
                .filter(([_, value]) => value && value !== '---')
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

        const engineInfo = {};
        
        if (engineData?.motor?.[0]?.antallSylindre) {
            engineInfo['Motor'] = `${engineData.motor[0].antallSylindre} sylindre`;
        }
        
        if (engineData?.motor?.[0]?.arbeidsprinsipp?.kodeBeskrivelse) {
            engineInfo['Drivstoff'] = engineData.motor[0].arbeidsprinsipp.kodeBeskrivelse;
        }
        
        if (engineData?.motor?.[0]?.slagvolum) {
            engineInfo['Slagvolum'] = `${engineData.motor[0].slagvolum.toLocaleString()} cm³`;
        }
        
        if (engineData?.motor?.[0]?.drivstoff?.[0]?.maksNettoEffekt) {
            const kw = engineData.motor[0].drivstoff[0].maksNettoEffekt;
            const hp = Math.round(kw * 1.34102);
            engineInfo['Effekt'] = `${kw} kW (${hp} hk)`;
        }
        
        if (engineData?.girkassetype?.kodeBeskrivelse) {
            engineInfo['Girkasse'] = engineData.girkassetype.kodeBeskrivelse;
        }

        $('.engine-info-table').html(
            Object.entries(engineInfo)
                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                .join('')
        );
    }



    function renderPremiumPreview(vehicleData) {
        // Placeholder function to show preview of premium content
        console.log('Premium preview for vehicle:', vehicleData.kjoretoyId?.kjennemerke);
        
        // This function can be expanded to show preview cards or hints 
        // about additional premium data available after purchase
    }

    function renderRegistrationInfo(vehicleData) {
        const euDeadline = vehicleData.periodiskKjoretoyKontroll?.kontrollfrist;
        let euControlText = formatDate(euDeadline);
        let euControlClass = '';

        // Add dynamic text for EU control if deadline exists
        if (euDeadline) {
            const today = new Date();
            const deadline = new Date(euDeadline);
            const daysUntilDeadline = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));

            if (daysUntilDeadline < 0) {
                const monthsAgo = Math.abs(Math.floor(daysUntilDeadline / 30));
                euControlText += ` <span class="eu-overdue">(${monthsAgo} ${monthsAgo === 1 ? 'måned' : 'måneder'} siden)</span>`;
                euControlClass = 'eu-overdue';
            } else if (daysUntilDeadline <= 30) {
                euControlText += ` <span class="eu-warning">(${daysUntilDeadline} ${daysUntilDeadline === 1 ? 'dag' : 'dager'} igjen)</span>`;
                euControlClass = 'eu-warning';
            } else {
                const monthsLeft = Math.floor(daysUntilDeadline / 30);
                euControlText += ` <span class="eu-ok">(${monthsLeft} ${monthsLeft === 1 ? 'måned' : 'måneder'} igjen)</span>`;
                euControlClass = 'eu-ok';
            }
        }

        const regInfo = {
            'Reg.nr.': vehicleData.kjoretoyId?.kjennemerke,
            'EU-kontroll': euControlText,
            'Reg. første gang': formatDate(vehicleData.godkjenning?.forstegangsGodkjenning?.forstegangRegistrertDato),
            'Reg. i Norge': formatDate(vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato),
            'Reg. på eier': formatDate(vehicleData.registrering?.registrertForstegangPaEierskap),
            'Status': vehicleData.registrering?.registreringsstatus?.kodeBeskrivelse,
            'EU-kontroller': '',  // Spacer for visual grouping
            'Siste EU-kontroll': formatDate(vehicleData.periodiskKjoretoyKontroll?.sistGodkjent)
        };

        const tableHtml = Object.entries(regInfo)
            .filter(([_, value]) => value)
            .map(([label, value]) => {
                if (label === 'EU-kontroll' && euControlClass) {
                    return `<tr class="${euControlClass}"><th>${label}</th><td>${value}</td></tr>`;
                }
                return `<tr><th>${label}</th><td>${value}</td></tr>`;
            })
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