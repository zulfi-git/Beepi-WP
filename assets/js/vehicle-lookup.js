// Action Box Popup Functions
function openActionPopup(type) {
    const popup = document.getElementById('popup-' + type);
    if (popup) {
        popup.classList.add('active');
    }
}

function closeActionPopup(type) {
    const popup = document.getElementById('popup-' + type);
    if (popup) {
        popup.classList.remove('active');
    }
}

// Close popup when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('action-popup')) {
        event.target.classList.remove('active');
    }
});


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

    // Track active polling state to prevent conflicts on subsequent lookups
    let activePollingTimeoutId = null;
    let currentLookupRegNumber = null;

    // normalizePlate is now provided by normalize-plate.js
    // It's available globally as window.normalizePlate

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }

    function formatDateTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';

        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const seconds = date.getSeconds().toString().padStart(2, '0');

        return `${day}.${month}.${year}, ${hours}:${minutes}:${seconds}`;
    }

    function checkEUAnchor() {
        if (window.location.hash === '#EU') {
            setTimeout(function() {
                const regSection = $('.section-title:contains("Reg. og EU-kontroll")').closest('.section');
                if (regSection.length) {
                    $('html, body').animate({
                        scrollTop: regSection.offset().top - 100
                    }, 800);
                }
            }, 1000);
        }
    }

    // All sections are now always visible - no expand/collapse needed
    window.expandAllAccordions = function() {
        // Scroll to first section (all are already visible)
        const firstSection = $('.accordion .section').first();
        if (firstSection.length) {
            $('html, body').animate({
                scrollTop: firstSection.offset().top - 150
            }, 600);
        }

        // Hide the guide box after use
        $('.free-info-guide').slideUp(300);
    };

    function resetFormState() {
        console.log('🧹 Clearing previous vehicle data...');

        // Cancel any active polling to prevent conflicts with new lookup
        if (activePollingTimeoutId) {
            clearTimeout(activePollingTimeoutId);
            activePollingTimeoutId = null;
            console.log('🛑 Cancelled active polling from previous lookup');
        }

        $resultsDiv.hide();
        $errorDiv.hide().empty();
        $('.vehicle-tags').remove();
        $('.cache-notice').remove();
        // Clear AI summary sections to prevent stacking
        $('.ai-summary-section').remove();
        $('.ai-summary-error').remove();
        // Clear market listings sections to prevent stacking
        $('.market-listings-section').remove();
        $('.market-listings-error').remove();
        $vehicleTitle.empty();
        $vehicleSubtitle.empty();
        $vehicleLogo.attr('src', '');
        $('.info-table').empty();
        // Clear owner history content to prevent stacking - unlike other sections that use .info-table class, this div has a different structure and needs explicit clearing
        $('#eierhistorikk-content').empty();
        console.log('✅ Previous vehicle data cleared');
    }

    function displayCacheNotice(responseData) {
        console.log('💾 Checking cache status...');
        // Remove any existing cache notice
        $('.cache-notice').remove();

        // Check if data includes cache information
        const cacheTime = responseData.cache_time;
        const isCached = responseData.is_cached || false;

        console.log('Cache info - isCached:', isCached, 'cacheTime:', cacheTime);

        let noticeText = '';
        let noticeClass = 'fresh';

        if (isCached && cacheTime) {
            const cacheDate = new Date(cacheTime);
            const now = new Date();
            const diffMinutes = Math.round((now - cacheDate) / (1000 * 60));

            if (diffMinutes < 1) {
                noticeText = 'Bufret (< 1 min)';
            } else if (diffMinutes < 60) {
                noticeText = `Bufret (${diffMinutes} min)`;
            } else {
                const diffHours = Math.round(diffMinutes / 60);
                noticeText = `Bufret (${diffHours}t)`;
            }
            noticeClass = 'cached';
        } else {
            noticeText = 'Ferske data';
            noticeClass = 'fresh';
        }

        // Add cache notice above vehicle-lookup-results
        $('#vehicle-lookup-results').before(`<div class="cache-notice ${noticeClass}" title="Datahentingsstatus for dette registreringsnummeret">${noticeText}</div>`);
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
        console.log('📊 Processing vehicle data for:', regNumber);
        console.log('Data cached:', response.data.is_cached || false);
        console.log('Cache time:', response.data.cache_time || 'N/A');

        const vehicleData = response.data.responser[0].kjoretoydata;

        setRegNumberCookie(regNumber);
        displayVehicleHeader(vehicleData, regNumber);
        displayStatusInfo(vehicleData);

        // Show cache status notice
        displayCacheNotice(response.data);
        console.log('✅ Cache notice displayed');

        // Render AI summary if available (always requested for all users)
        if (response.data.aiSummary) {
            if (typeof renderAiSummary === 'function') {
                renderAiSummary(response.data.aiSummary);
            } else {
                console.warn('AI Summary: renderAiSummary function not available');
            }
        }

        // Render market listings if available
        if (response.data.marketListings) {
            renderMarketListings(response.data.marketListings);
        }

        // Always show basic info for free
        console.log('📝 Rendering basic info...');
        renderBasicInfo(vehicleData);
        console.log('📝 Rendering registration info...');
        renderRegistrationInfo(vehicleData);

        // Only show full owner info if user has access
        console.log('👤 Rendering owner info...');
        renderOwnerInfo(vehicleData);
        console.log('🔧 Rendering technical info...');
        renderTechnicalInfo(vehicleData);

        // Populate owner history section
        console.log('📜 Populating owner history table...');
        populateOwnerHistoryTable();

        // No need to manage accordion open/close - all sections are always visible
        $resultsDiv.show();
        console.log('✅ Results displayed');

        $('html, body').animate({
            scrollTop: $('.vehicle-lookup-container').offset().top - 20
        }, 500);

        checkEUAnchor();
        console.log('🎉 Vehicle lookup complete for:', regNumber);
    }

    $form.on('submit', function(e) {
        e.preventDefault();

        const regNumber = normalizePlate($('#regNumber').val());
        console.log('🔍 Vehicle lookup initiated for:', regNumber);

        // Track the current lookup to prevent interference from old polling
        currentLookupRegNumber = regNumber;

        resetFormState();

        if (!regNumber || !validateRegistrationNumber(regNumber)) {
            $errorDiv.html('Vennligst skriv inn et gyldig norsk registreringsnummer').show();
            return;
        }

        setLoadingState(true);

        // Phase 1: Always request AI summary generation for all users
        const requestData = {
            action: 'vehicle_lookup',
            nonce: vehicleLookupAjax.nonce,
            regNumber: regNumber,
            includeSummary: true  // Triggers AI generation in background
        };

        // Make AJAX request
        $.ajax({
            url: vehicleLookupAjax.ajaxurl,
            type: 'POST',
            data: requestData,
            dataType: 'json',
            contentType: 'application/x-www-form-urlencoded',
            timeout: 15000,
            success: function(response) {
                console.log('📡 AJAX response received');
                console.log('Success:', response.success);
                if (response.success && response.data) {
                    console.log('✅ Valid vehicle data received');
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

                    // Clear retry counters on successful lookup
                    clearRetryCounters(regNumber);
                    console.log('✅ Retry counters cleared');

                    // Phase 1: Process vehicle data immediately
                    console.log('🚀 Phase 1: Processing vehicle data immediately');
                    processVehicleData(response, regNumber);

                    // Phase 2: Check for AI summary status and start polling if needed
                    console.log('🤖 Phase 2: Checking AI summary status');
                    checkAndStartAiSummaryPolling(response.data, regNumber);

                    // Phase 3: Check for market listings status and start polling if needed
                    console.log('🏪 Phase 3: Checking market listings status');
                    console.log('Response data keys:', Object.keys(response.data));
                    console.log('Market listings in response:', response.data.marketListings);
                    checkAndStartMarketListingsPolling(response.data, regNumber);
                } else {
                    // This handles cases where success is false - check for structured error data
                    let errorMessage = 'Kunne ikke hente kjøretøyinformasjon';
                    let errorCode = null;
                    let correlationId = null;

                    if (response.data) {
                        if (typeof response.data === 'object' && response.data.message) {
                            // Structured error response
                            errorMessage = response.data.message;
                            errorCode = response.data.code;
                            correlationId = response.data.correlation_id;
                        } else {
                            // Simple error message
                            errorMessage = response.data;
                        }
                    }

                    // Track error analytics for failed responses
                    trackVehicleLookupError(errorCode, correlationId, normalizePlate($('#regNumber').val()), 'success_false');

                    // Attempt smart retry if applicable
                    if (errorCode && !attemptSmartRetry(errorCode, correlationId, regNumber)) {
                        $errorDiv.html(errorMessage).show();
                    }
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'En feil oppstod: ';
                let errorCode = null;
                let correlationId = null;
                let retryAfter = null;

                if (status === 'timeout') {
                    errorMessage = 'Forespørsel tok for lang tid. Vennligst prøv igjen.';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    // Check if it's structured error data
                    if (typeof xhr.responseJSON.data === 'object' && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                        errorCode = xhr.responseJSON.data.code;
                        correlationId = xhr.responseJSON.data.correlation_id;
                        retryAfter = xhr.responseJSON.data.retry_after;
                    } else {
                        // Backward compatibility for simple error messages
                        errorMessage = xhr.responseJSON.data;
                    }
                } else if (error) {
                    errorMessage += error;
                }

                // Log structured error data for debugging
                if (errorCode || correlationId) {
                    console.group('🔴 Vehicle Lookup Error');
                    console.log('Message:', errorMessage);
                    if (errorCode) console.log('Code:', errorCode);
                    if (correlationId) console.log('Correlation ID:', correlationId);
                    if (retryAfter) console.log('Retry After:', retryAfter + 'ms');
                    console.groupEnd();
                }

                // Handle specific error codes with enhanced UX
                if (errorCode === 'RATE_LIMIT_EXCEEDED' && retryAfter) {
                    const retrySeconds = Math.ceil(retryAfter / 1000);
                    errorMessage += ` Prøv igjen om ${retrySeconds} sekunder.`;

                    // Auto-retry after the specified time
                    setTimeout(function() {
                        $submitButton.removeClass('loading').prop('disabled', false);
                        if (correlationId) {
                            console.log('🔄 Auto-retrying after rate limit (Correlation ID: ' + correlationId + ')');
                        }
                    }, retryAfter);
                } else if (errorCode) {
                    // Attempt smart retry for other error codes
                    if (!attemptSmartRetry(errorCode, correlationId, regNumber)) {
                        // No retry strategy available, show error normally
                        let displayMessage = errorMessage;
                        if (correlationId && window.location.hostname.includes('debug')) {
                            displayMessage += ` (Ref: ${correlationId.substring(0, 8)})`;
                        }
                        $errorDiv.html(displayMessage).show();
                    }
                    return; // Exit early since retry will handle the rest
                }

                // Display user-friendly error message with optional correlation ID for support
                let displayMessage = errorMessage;
                if (correlationId && window.location.hostname.includes('debug')) {
                    displayMessage += ` (Ref: ${correlationId.substring(0, 8)})`;
                }

                $errorDiv.html(displayMessage).show();

                // Track error analytics
                trackVehicleLookupError(errorCode, correlationId, normalizePlate($('#regNumber').val()), 'ajax_error');
            },
            complete: function() {
                setLoadingState(false);
            }
        });
    });

    /**
     * Track vehicle lookup errors for analytics and monitoring
     */
    function trackVehicleLookupError(errorCode, correlationId, registrationNumber, context) {
        // Console logging for debugging
        if (errorCode || correlationId) {
            console.group('📊 Error Analytics');
            console.log('Context:', context);
            console.log('Error Code:', errorCode || 'N/A');
            console.log('Correlation ID:', correlationId || 'N/A');
            console.log('Registration Number:', registrationNumber || 'N/A');
            console.groupEnd();
        }

        // Google Analytics 4 tracking (if available)
        if (typeof gtag === 'function' && errorCode) {
            gtag('event', 'vehicle_lookup_error', {
                'error_code': errorCode,
                'correlation_id': correlationId,
                'registration_number': registrationNumber,
                'context': context,
                'custom_map': {
                    'metric1': errorCode
                }
            });
        }

        // Custom analytics tracking (if available)
        if (typeof window.analyticsTracker === 'function') {
            window.analyticsTracker('error', {
                type: 'vehicle_lookup_error',
                code: errorCode,
                correlation_id: correlationId,
                registration_number: registrationNumber,
                context: context,
                timestamp: new Date().toISOString()
            });
        }

        // Store error in session storage for support purposes
        try {
            const errorLog = JSON.parse(sessionStorage.getItem('vehicle_lookup_errors') || '[]');
            errorLog.push({
                timestamp: new Date().toISOString(),
                code: errorCode,
                correlation_id: correlationId,
                registration_number: registrationNumber,
                context: context
            });

            // Keep only last 10 errors
            if (errorLog.length > 10) {
                errorLog.splice(0, errorLog.length - 10);
            }

            sessionStorage.setItem('vehicle_lookup_errors', JSON.stringify(errorLog));
        } catch (e) {
            console.warn('Could not store error log in session storage:', e);
        }
    }

    /**
     * Implement smart retry logic for specific error codes
     */
    function attemptSmartRetry(errorCode, correlationId, originalRegNumber) {
        const retryStrategies = {
            'TIMEOUT': {
                delay: 2000,
                maxAttempts: 2,
                message: '⏱️ Forsøker på nytt på grunn av timeout...'
            },
            'NETWORK_ERROR': {
                delay: 1500,
                maxAttempts: 3,
                message: '🌐 Tilkoblingsproblem - forsøker på nytt...'
            },
            'SERVICE_UNAVAILABLE': {
                delay: 5000,
                maxAttempts: 1,
                message: '🔧 Tjenesten er midlertidig utilgjengelig - forsøker på nytt...'
            }
        };

        const strategy = retryStrategies[errorCode];
        if (!strategy) return false;

        // Check if we've already exceeded retry attempts for this session
        const retryKey = `retry_${errorCode}_${originalRegNumber}`;
        const currentAttempts = parseInt(sessionStorage.getItem(retryKey) || '0');

        if (currentAttempts >= strategy.maxAttempts) {
            console.log(`❌ Max retry attempts (${strategy.maxAttempts}) exceeded for ${errorCode}`);
            return false;
        }

        // Show retry message
        $errorDiv.html(strategy.message).show().addClass('retrying');

        // Increment retry counter
        sessionStorage.setItem(retryKey, (currentAttempts + 1).toString());

        // Perform retry after delay
        setTimeout(() => {
            console.log(`🔄 Smart retry attempt ${currentAttempts + 1}/${strategy.maxAttempts} for ${errorCode} (Correlation: ${correlationId})`);

            // Reset form state and retry
            resetFormState();
            $errorDiv.removeClass('retrying');
            $('#regNumber').val(originalRegNumber);
            $form.trigger('submit');
        }, strategy.delay);

        return true;
    }

    /**
     * Clear retry counters when successful lookup occurs
     */
    function clearRetryCounters(regNumber) {
        const keysToRemove = [];
        for (let i = 0; i < sessionStorage.length; i++) {
            const key = sessionStorage.key(i);
            if (key && key.startsWith('retry_') && key.endsWith(`_${regNumber}`)) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => sessionStorage.removeItem(key));
    }

    function renderOwnerInfo(vehicleData) {
        console.log('  → renderOwnerInfo: Starting...');
        if (!vehicleData.eierskap?.eier) {
            console.log('  → renderOwnerInfo: No owner data available');
            return;
        }

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
            console.log('  → renderOwnerInfo: Owner data displayed (access granted)');
        } else {
            $ownerTable.html('');
            $purchaseDiv.show();
            console.log('  → renderOwnerInfo: Purchase prompt displayed (no access)');
        }
        console.log('  → renderOwnerInfo: Complete');
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

    // Add CSS for the new owner history section and error states
    const ownerHistoryCss = `
        /* Error retry states */
        .vehicle-lookup-error.retrying {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #ffeaa7;
        }

        .vehicle-lookup-error.retrying::before {
            content: "🔄 ";
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .owner-history-container .content-wrapper {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
        }
        .owner-history-container .blurred-content {
            filter: blur(10px);
            height: 150px; /* Adjust height as needed */
            background-color: rgba(200, 200, 200, 0.5); /* Light grey background for blur effect */
            padding: 15px;
            box-sizing: border-box;
            color: #555; /* Darker text for readability behind blur */
            font-size: 14px;
            line-height: 1.5;
        }
        .owner-history-container .premium-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-align: center;
            z-index: 10;
            width: 80%; /* Adjust overlay width */
        }
        .owner-history-container .premium-overlay h3 {
            margin-top: 0;
            color: #007bff; /* Premium color */
        }
        .owner-history-container .premium-overlay .price {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        .owner-history-container .premium-overlay .buy-button {
            display: inline-block;
            background-color: #28a745; /* Buy button color */
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .owner-history-container .premium-overlay .buy-button:hover {
            background-color: #218838;
        }
        .owner-history-container .premium-overlay .product-title {
            font-size: 1.1em;
            color: #6c757d; /* Secondary text color */
            margin-bottom: 5px;
        }
    `;
    const $style = $('<style type="text/css"></style>').text(ownerHistoryCss);
    $('head').append($style);

    // Function to populate the owner history table
    function populateOwnerHistoryTable() {
        console.log('  → populateOwnerHistoryTable: Starting...');
        const regNumber = normalizePlate($('#regNumber').val());
        const $ownerHistoryDiv = $('#eierhistorikk-content');

        if (!regNumber || !$ownerHistoryDiv.length) {
            return;
        }

        // Mock Norwegian owner history data (will be heavily blurred)
        const mockOwnerHistory = [
            { period: '2020-2023', owner: 'Kari Nordmann', address: 'Storgata 15, 0101 Oslo' },
            { period: '2018-2020', owner: 'Lars Hansen', address: 'Bjørnstjerne Bjørnsons gate 45, 4611 Kristiansand' },
            { period: '2015-2018', owner: 'Inger Solberg', address: 'Kongens gate 23, 7011 Trondheim' }
        ];

        let html = '<div class="owner-history-content">';

        // Blurred content
        html += '<div class="blurred-owner-data">';

        mockOwnerHistory.forEach(item => {
            html += `<div style="margin-bottom: 0.5rem; padding: 0.5rem; background: rgba(255,255,255,0.5); border-radius: 4px;">
                <strong>${item.period}:</strong> ${item.owner}<br>
                <small style="color: #6b7280;">${item.address}</small>
            </div>`;
        });

        html += '</div>';

        // Premium overlay with dynamic pricing - use same payment as premium tier
        const premiumProduct = window.vehicleLookupData?.premiumProduct;
        const regularPrice = premiumProduct?.regular_price || '89';
        const salePrice = premiumProduct?.sale_price;
        const productName = premiumProduct?.name || 'Premium Kjøretøyrapport';

        html += `<div class="owner-history-overlay">
            <h4>🔐 ${productName}</h4>
            <div class="tier-price">
                <span class="regular-price">kr ${regularPrice},-</span>
                <span class="sale-price">kr ${salePrice || regularPrice},-</span>
            </div>
            <div class="tier-purchase">
                ${window.premiumVippsBuyButton || ''}
            </div>
        </div>`;

        html += '</div>';

        $ownerHistoryDiv.html(html);
        console.log('  → populateOwnerHistoryTable: Complete');
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
        const displayedReg = normalizePlate($('.vehicle-title').text());
        const inputReg = normalizePlate($('#regNumber').val());
        const regNumber = displayedReg || inputReg;

        if (!regNumber) {
            console.error('Ingen registreringsnummer funnet');
            return;
        }

        // Registration number handling is now managed by WooCommerce Vipps plugin
        console.log('Registration number:', regNumber);
    });

    function renderBasicInfo(vehicleData) {
        console.log('  → renderBasicInfo: Starting...');
        const tekniskeData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData;
        const generelt = tekniskeData?.generelt;
        if (!generelt) {
            console.log('  → renderBasicInfo: No data available');
            return;
        }

        const basicInfo = {
            'Merke': generelt.merke?.[0]?.merke || '---',
            'Modell': generelt.handelsbetegnelse?.[0] || '---',
            'Kjennemerke': vehicleData.kjoretoyId?.kjennemerke || '---',
            'Farge': generelt.farge?.[0]?.kodeBeskrivelse || '---',
            'Type': generelt.type || '---',
            'Antall seter': generelt.sitteplasserTotalt || '---'
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
        console.log('  → renderBasicInfo: Complete');
    }

    function renderTechnicalInfo(vehicleData) {
        console.log('  → renderTechnicalInfo: Starting...');
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
        console.log('  → renderTechnicalInfo: Complete');
    }


    function renderRegistrationInfo(vehicleData) {
        console.log('  → renderRegistrationInfo: Starting...');
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
                euControlText += ` <span class="eu-overdue">(${monthsAgo} ${monthsAgo === 1 ? 'måned' : 'måneder'} igjen)</span>`;
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
        console.log('  → renderRegistrationInfo: Complete');
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

    function renderAiSummary(aiSummary) {
        if (!aiSummary) return;

        // Remove any existing AI summary sections to prevent duplicates
        $('.ai-summary-section').remove();
        $('.ai-summary-error').remove();

        try {
            // Create DOM elements safely to prevent XSS (no accordion, always visible)
            const $aiSection = $('<div class="ai-summary-section section">');
            const $sectionHeader = $('<div class="section-header">').append(
                $('<span class="section-title">').text('AI Kjøretøyanalyse'),
                $('<img>').attr({
                    'src': vehicleLookupAjax.plugin_url + '/assets/images/open-ai-logo.png',
                    'alt': 'OpenAI',
                    'class': 'section-icon-logo'
                }).css({
                    'height': '20px',
                    'width': 'auto',
                    'opacity': '0.85'
                })
            );
            const $sectionContent = $('<div class="section-content">');
            const $aiContent = $('<div class="ai-summary-content">');

            // Main summary section
            if (aiSummary.summary) {
                const $summarySection = $('<div class="ai-section">');
                $summarySection.append(
                    $('<p class="ai-summary-text">').text(aiSummary.summary)
                );
                $aiContent.append($summarySection);
            }

            // Safely add highlights
            if (aiSummary.highlights && Array.isArray(aiSummary.highlights) && aiSummary.highlights.length > 0) {
                const $highlightsSection = $('<div class="ai-section">');
                const $highlightsList = $('<ul class="ai-highlights">');

                aiSummary.highlights.forEach(highlight => {
                    if (typeof highlight === 'string') {
                        $highlightsList.append(
                            $('<li class="ai-highlight-item">').text(highlight)
                        );
                    }
                });

                $highlightsSection.append(
                    $('<h4 class="ai-section-title">').text('Høydepunkter'),
                    $highlightsList
                );
                $aiContent.append($highlightsSection);
            }

            // Safely add recommendation
            if (aiSummary.recommendation) {
                const $recommendationSection = $('<div class="ai-section">');
                $recommendationSection.append(
                    $('<h4 class="ai-section-title">').text('Anbefaling'),
                    $('<p class="ai-recommendation">').text(aiSummary.recommendation)
                );
                $aiContent.append($recommendationSection);
            }

            // Safely add market insights
            if (aiSummary.marketInsights) {
                const $marketSection = $('<div class="ai-section">');
                $marketSection.append(
                    $('<h4 class="ai-section-title">').text('Markedsanalyse'),
                    $('<p class="ai-market-insights">').text(aiSummary.marketInsights)
                );
                $aiContent.append($marketSection);
            }

            // Safely add red flags
            if (aiSummary.redFlags && Array.isArray(aiSummary.redFlags) && aiSummary.redFlags.length > 0) {
                const $redFlagsSection = $('<div class="ai-section">');
                const $redFlagsList = $('<ul class="ai-red-flags">');

                aiSummary.redFlags.forEach(flag => {
                    if (typeof flag === 'string') {
                        $redFlagsList.append(
                            $('<li class="ai-red-flag-item">').text(flag)
                        );
                    }
                });

                $redFlagsSection.append(
                    $('<h4 class="ai-section-title">').text('Ting å vurdere'),
                    $redFlagsList
                );
                $aiContent.append($redFlagsSection);
            }

            // Safely add attribution
            const $attribution = $('<div class="ai-attribution">');
            const $meta = $('<small class="ai-meta">');

            let metaText = aiSummary.aiGenerated ?
                `AI-generert med ${aiSummary.model || 'gpt-4o-mini'}` :
                'Fallback-sammendrag';

            if (aiSummary.generatedAt) {
                const formattedDateTime = formatDateTime(aiSummary.generatedAt);
                if (formattedDateTime) {
                    metaText += ` • ${formattedDateTime}`;
                }
            }

            if (aiSummary.correlationId) {
                metaText += ` • ${aiSummary.correlationId}`;
            }

            $meta.text(metaText);
            $attribution.append($meta);
            $aiContent.append($attribution);

            // Assemble the complete structure
            $sectionContent.append($aiContent);
            $aiSection.append($sectionHeader, $sectionContent);

            // Insert AI summary at the beginning of the accordion
            $('.accordion').prepend($aiSection);

        } catch (error) {
            console.error('Error rendering AI summary:', error);
            // Fallback: show a simple error message
            const $errorSection = $('<div class="ai-summary-error" style="padding: 10px; margin: 10px 0; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">');
            $errorSection.text('AI-sammendrag kunne ikke vises');
            $('.accordion').prepend($errorSection);
        }
    }

    function createInfoItem(label, value) {
        return `<div class="info-item">
            <strong>${label}:</strong>
            <span>${value || '-'}</span>
        </div>`;
    }

    /**
     * Phase 2: Check AI summary status and start polling if needed
     */
    function checkAndStartAiSummaryPolling(responseData, regNumber) {
        // Check if response has AI summary data
        if (responseData.aiSummary) {
            // If AI summary is already complete, render it
            if (responseData.aiSummary.status === 'complete' && responseData.aiSummary.summary) {
                renderAiSummary(responseData.aiSummary.summary);
                return;
            }

            // If AI summary is generating, start polling
            if (responseData.aiSummary.status === 'generating') {
                showAiGenerationStatus('AI sammendrag genereres...', responseData.aiSummary.progress);
                startAiSummaryPolling(regNumber);
                return;
            }

            // If there was an error with AI generation
            if (responseData.aiSummary.status === 'error') {
                console.warn('AI summary generation failed:', responseData.aiSummary.error);
                return;
            }
        }
    }

    /**
     * Show AI generation status to user
     */
    function showAiGenerationStatus(message, progress) {
        // Remove any existing AI summary sections and create loading placeholder
        $('.ai-summary-section').remove();

        // Create AI section with loading status inside (no accordion, always visible)
        const $aiSection = $('<div class="ai-summary-section section">');
        const $sectionHeader = $('<div class="section-header">').append(
            $('<span class="section-title">').text('AI Kjøretøyanalyse'),
            $('<img>').attr({
                'src': vehicleLookupAjax.plugin_url + '/assets/images/open-ai-logo.png',
                'alt': 'OpenAI',
                'class': 'section-icon-logo'
            }).css({
                'height': '20px',
                'width': 'auto',
                'opacity': '0.85'
            })
        );
        const $sectionContent = $('<div class="section-content">');
        const $aiContent = $('<div class="ai-summary-content ai-generation-status">');

        // Single centered loading message
        const $loadingContainer = $('<div style="padding: 1.5rem; text-align: center;">');
        const $spinner = $('<div class="loading-spinner" style="width: 24px; height: 24px; border: 3px solid #e2e8f0; border-top: 3px solid #0ea5e9; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 0.75rem auto;">');
        const $statusText = $('<div style="color: #64748b; font-size: 0.875rem; font-weight: 500;">').text('Genererer analyse...');

        $loadingContainer.append($spinner, $statusText);
        $aiContent.append($loadingContainer);

        // Add CSS animation if not already present
        if (!$('head style[data-spinner-animation]').length) {
            $('<style data-spinner-animation="true">')
                .prop('type', 'text/css')
                .html('@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }')
                .appendTo('head');
        }

        // Assemble AI section and add to beginning of accordion (no collapsible behavior)
        $sectionContent.append($aiContent);
        $aiSection.append($sectionHeader, $sectionContent);
        $('.accordion').prepend($aiSection);
    }

    /**
     * Start polling for AI summary completion
     */
    function startAiSummaryPolling(regNumber, attempt = 1, maxAttempts = 15) {
        // Check if this polling is for the current active lookup
        if (normalizePlate(regNumber) !== currentLookupRegNumber) {
            console.log('🛑 Stopping polling for', regNumber, '- new lookup in progress for', currentLookupRegNumber);
            return;
        }

        // Don't poll more than maxAttempts times (15 attempts = ~30 seconds with 2s intervals)
        if (attempt > maxAttempts) {
            $('.ai-summary-section .ai-summary-content').html(
                '<div style="padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.875rem;">Analysen tar lenger tid enn forventet. Prøv å oppdatere siden.</div>'
            );
            console.warn('AI summary polling timeout after', maxAttempts, 'attempts');
            return;
        }

        const pollDelay = attempt === 1 ? 1000 : 2000; // First poll after 1s, then every 2s

        activePollingTimeoutId = setTimeout(() => {
            // Double-check if this polling is still relevant
            if (normalizePlate(regNumber) !== currentLookupRegNumber) {
                console.log('🛑 Polling cancelled for', regNumber, '- lookup changed to', currentLookupRegNumber);
                return;
            }

            $.ajax({
                url: vehicleLookupAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vehicle_lookup_ai_poll',
                    nonce: vehicleLookupAjax.nonce,
                    regNumber: normalizePlate(regNumber)
                },
                dataType: 'json',
                contentType: 'application/x-www-form-urlencoded',
                timeout: 10000,
                success: function(response) {
                    console.log('Polling response received:', response);

                    // Check if this response is still relevant
                    if (normalizePlate(regNumber) !== currentLookupRegNumber) {
                        console.log('🛑 Ignoring polling response for', regNumber, '- current lookup is', currentLookupRegNumber);
                        return;
                    }

                    if (response.success && response.data) {
                        const pollingData = response.data;
                        console.log('Polling data structure:', pollingData);

                        // Track completion status
                        let aiComplete = false;
                        let marketComplete = false;

                        // Handle AI summary data
                        if (pollingData.aiSummary) {
                            console.log('AI Summary data:', pollingData.aiSummary);
                            const aiData = pollingData.aiSummary;

                            if (aiData.status === 'complete' && aiData.summary) {
                                // AI summary is ready! Only render if not already rendered
                                if (!$('.ai-summary-section .ai-summary-content .ai-section').length) {
                                    $('.ai-summary-section').remove();
                                    renderAiSummary(aiData.summary);
                                    console.log('✅ AI summary generated successfully');
                                }
                                aiComplete = true;
                            } else if (aiData.status === 'generating') {
                                // Still generating, update progress only if not already complete
                                if (!$('.ai-summary-section .ai-summary-content .ai-section').length) {
                                    showAiGenerationStatus('AI sammendrag genereres...', aiData.progress);
                                }
                            } else if (aiData.status === 'error') {
                                // Generation failed - show error in AI section
                                $('.ai-summary-section .ai-summary-content').html(
                                    '<div style="padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.875rem;">Kunne ikke generere analyse. Prøv igjen senere.</div>'
                                );
                                console.warn('AI summary generation failed:', aiData.error);
                                aiComplete = true; // Stop polling for AI
                            }
                        }

                        // Handle market listings data
                        if (pollingData.marketListings) {
                            console.log('Market listings data:', pollingData.marketListings);
                            const marketData = pollingData.marketListings;

                            if (marketData.status === 'complete') {
                                // Market listings are ready! Only render if not already rendered
                                if (!$('.market-listings-section .market-listings-content .market-listing-item').length) {
                                    renderMarketListings(marketData);
                                    console.log('✅ Market listings generated successfully');
                                }
                                marketComplete = true;
                            } else if (marketData.status === 'generating') {
                                // Still generating, show loading state if not already showing content
                                if (!$('.market-listings-section .market-listings-content .market-listing-item').length) {
                                    showMarketListingsGenerationStatus('Markedsdata hentes...');
                                }
                            } else if (marketData.status === 'error') {
                                // Generation failed - show error
                                showMarketListingsTimeout();
                                console.warn('Market listings generation failed:', marketData.error);
                                marketComplete = true; // Stop polling for market data
                            }
                        }

                        // Determine if we should continue polling
                        const aiStillGenerating = pollingData.aiSummary && pollingData.aiSummary.status === 'generating';
                        const marketStillGenerating = pollingData.marketListings && pollingData.marketListings.status === 'generating';

                        // Only continue polling if either section is still generating
                        if (aiStillGenerating || marketStillGenerating) {
                            console.log('Continuing polling - AI:', aiStillGenerating ? 'generating' : 'done', 'Market:', marketStillGenerating ? 'generating' : 'done');
                            startAiSummaryPolling(regNumber, attempt + 1, maxAttempts);
                        } else {
                            console.log('✅ Polling complete - both AI and market data finished');
                        }
                    } else {
                        console.log('Polling failed - no success or data:', response);

                        // Check if this polling is still relevant before retrying
                        if (normalizePlate(regNumber) !== currentLookupRegNumber) {
                            console.log('🛑 Not retrying polling for', regNumber, '- lookup changed');
                            return;
                        }

                        // API error, retry with exponential backoff
                        const retryDelay = Math.min(pollDelay * Math.pow(1.5, attempt - 1), 10000);
                        activePollingTimeoutId = setTimeout(() => {
                            startAiSummaryPolling(regNumber, attempt + 1, maxAttempts);
                        }, retryDelay);
                    }
                },
                error: function(xhr, status, error) {
                    // Check if this polling is still relevant before retrying
                    if (normalizePlate(regNumber) !== currentLookupRegNumber) {
                        console.log('🛑 Not retrying polling after error for', regNumber, '- lookup changed');
                        return;
                    }

                    // Handle polling errors gracefully
                    if (attempt < maxAttempts) {
                        const retryDelay = Math.min(pollDelay * Math.pow(1.5, attempt), 10000);
                        activePollingTimeoutId = setTimeout(() => {
                            startAiSummaryPolling(regNumber, attempt + 1, maxAttempts);
                        }, retryDelay);
                    } else {
                        $('.ai-summary-section .ai-summary-content').html(
                            '<div class="ai-summary-error">AI sammendrag ikke tilgjengelig for øyeblikket.</div>'
                        );
                        console.warn('AI summary polling failed after', maxAttempts, 'attempts');
                    }
                }
            });
        }, pollDelay);
    }

    // Function to render market listings
    function renderMarketListings(marketData) {
        console.log('🎨 Rendering market listings with status:', marketData?.status, 'listings count:', marketData?.listings?.length);

        // Remove any existing market listings sections and create new one
        $('.market-listings-section').remove();
        $('.market-listings-error').remove();

        if (!marketData) {
            console.log('No market listings data provided');
            return;
        }

        try {
            // Create market listings section with status-based content
            const $marketSection = $('<div class="market-listings-section section">');
            const $sectionHeader = $('<div class="section-header">').append(
                $('<span class="section-title">').text('Siste annonser på finn.no'),
                $('<img>').attr({
                    'src': vehicleLookupAjax.plugin_url + '/assets/images/finnno-logo.png',
                    'alt': 'Finn.no',
                    'class': 'section-icon-logo'
                }).css({
                    'height': '20px',
                    'width': 'auto',
                    'opacity': '0.85'
                })
            );
            const $sectionContent = $('<div class="section-content">');
            const $marketContent = $('<div class="market-listings-content">');

            if (marketData.status === 'generating') {
                // Show loading state for market listings
                const $statusHeader = $('<div style="display: flex; align-items: center; gap: 10px; justify-content: center; padding: 0.75rem; background: linear-gradient(135deg, #f8faff 0%, #f0f4ff 100%); border: 1px solid #cbd5e1; border-radius: 8px;">');
                const $spinner = $('<div class="loading-spinner" style="width: 20px; height: 20px; border: 2px solid #e2e8f0; border-top: 2px solid #0ea5e9; border-radius: 50%; animation: spin 1s linear infinite;">');
                const $statusText = $('<span style="color: #475569; font-size: 0.9rem; font-weight: 500;">').text('Henter markedsdata...');

                $statusHeader.append($spinner, $statusText);
                $marketContent.append($statusHeader);
            } else if (marketData.status === 'complete') {
                // Show completed market listings
                console.log('Market listings complete - listings present:', !!marketData.listings, 'is array:', Array.isArray(marketData.listings), 'length:', marketData.listings?.length);

                if (marketData.listings && Array.isArray(marketData.listings) && marketData.listings.length > 0) {
                    // Display listings directly without wrapper sections
                    const $listingsList = $('<div class="market-listings">');

                    marketData.listings.slice(0, 3).forEach(listing => { // Show max 3 listings for mobile optimization
                        const $listingItem = $('<div class="market-listing-item">');

                        const title = listing.title || 'Ukjent kjøretøy';
                        const price = listing.price ? `${parseInt(listing.price).toLocaleString('no-NO')} kr` : 'Pris ikke oppgitt';
                        const year = listing.year || '';
                        const mileage = listing.mileage ? `${parseInt(listing.mileage).toLocaleString('no-NO')} km` : '';
                        const location = listing.location || '';
                        const image = listing.image || '';

                        // Create mobile-first card layout with image
                        let listingHtml = '<div class="listing-card">';

                        // Image section (left/top on mobile)
                        if (image) {
                            const imageErrorHandler = "this.parentElement.parentElement.style.display='none'";
                            if (listing.url) {
                                listingHtml += `<a href="${listing.url}" target="_blank" class="listing-image-link" rel="noopener">
                                    <div class="listing-image">
                                        <img src="${image}" alt="${title}" loading="lazy" onerror="${imageErrorHandler}">
                                    </div>
                                </a>`;
                            } else {
                                listingHtml += `<div class="listing-image">
                                    <img src="${image}" alt="${title}" loading="lazy" onerror="${imageErrorHandler}">
                                </div>`;
                            }
                        }

                        // Content section
                        listingHtml += '<div class="listing-content">';

                        // Header with title and year
                        listingHtml += '<div class="listing-header">';
                        listingHtml += `<div class="listing-title">${title}</div>`;
                        if (year) {
                            listingHtml += `<div class="listing-year">${year}</div>`;
                        }
                        listingHtml += '</div>';

                        // Price prominently displayed
                        listingHtml += `<div class="listing-price">${price}</div>`;

                        // Key details in mobile-friendly format
                        if (mileage || location) {
                            listingHtml += '<div class="listing-details">';
                            if (mileage) listingHtml += `<span class="listing-mileage">${mileage}</span>`;
                            if (location) listingHtml += `<span class="listing-location">${location}</span>`;
                            listingHtml += '</div>';
                        }

                        // Action link
                        if (listing.url) {
                            listingHtml += `<a href="${listing.url}" target="_blank" class="listing-link" rel="noopener">Se annonse →</a>`;
                        }

                        listingHtml += '</div>'; // Close listing-content
                        listingHtml += '</div>'; // Close listing-card

                        $listingItem.html(listingHtml);
                        $listingsList.append($listingItem);
                    });

                    $marketContent.append($listingsList);

                    // Add "Vis flere annonser på Finn.no" button if searchUrl is available
                    if (marketData.searchUrl) {
                        const $viewMoreButton = $('<a>')
                            .attr({
                                'href': marketData.searchUrl,
                                'target': '_blank',
                                'rel': 'noopener noreferrer',
                                'class': 'finn-view-more-btn'
                            })
                            .text('Vis flere annonser på Finn.no →');

                        const $buttonWrapper = $('<div class="finn-view-more-wrapper">').append($viewMoreButton);
                        $marketContent.append($buttonWrapper);
                    }

                    // Add attribution with completedAt and correlationId
                    const $attribution = $('<div class="ai-attribution">');
                    const $meta = $('<small class="ai-meta">');

                    let metaText = 'Fullført markedsanalyse';

                    if (marketData.completedAt) {
                        const formattedDateTime = formatDateTime(marketData.completedAt);
                        if (formattedDateTime) {
                            metaText += ` • ${formattedDateTime}`;
                        }
                    }

                    if (marketData.correlationId) {
                        metaText += ` • ${marketData.correlationId}`;
                    }

                    $meta.text(metaText);
                    $attribution.append($meta);
                    $marketContent.append($attribution);
                } else {
                    // No listings found
                    const $noDataText = $('<p class="market-no-data">').text('Ingen lignende kjøretøy funnet i markedet for øyeblikket.');
                    $marketContent.append($noDataText);
                }
            } else {
                // Handle error status or unknown status
                console.log('Market listings unexpected status:', marketData.status);
                const $errorText = $('<p class="market-no-data">').text('Kunne ikke hente markedsdata for øyeblikket.');
                $marketContent.append($errorText);
            }

            $sectionContent.append($marketContent);
            $marketSection.append($sectionHeader, $sectionContent);

            // Insert market listings section after AI summary or basic info
            const $aiSection = $('.ai-summary-section');
            if ($aiSection.length) {
                $aiSection.after($marketSection);
            } else {
                $('.basic-info-section').after($marketSection);
            }

        } catch (error) {
            console.error('Error rendering market listings:', error);
            const $errorSection = $('<div class="market-listings-error">');
            $errorSection.html('<p style="color: #dc2626; padding: 1rem; text-align: center;">Kunne ikke vise markedsdata. Prøv igjen senere.</p>');
            $('.basic-info-section').after($errorSection);
        }
    }

    // Function to check and start market listings polling
    function checkAndStartMarketListingsPolling(data, regNumber) {
        console.log('🏪 Checking market listings data:', data.marketListings ? 'Present' : 'Missing');

        if (!data.marketListings) {
            console.log('⚠️ No market listings data in response');
            return; // No market listings data
        }

        console.log('Market listings status:', data.marketListings.status);

        // If market listings are already complete, render them immediately (even if empty)
        if (data.marketListings.status === 'complete') {
            console.log('✅ Market listings complete, rendering immediately');
            renderMarketListings(data.marketListings);
            return;
        }

        // If market listings are generating, render the section with loading state and start polling
        if (data.marketListings.status === 'generating') {
            console.log('Market listings generating, starting polling for:', regNumber);
            renderMarketListings(data.marketListings); // Create section with loading state
            startMarketListingsPolling(regNumber);
        }
    }

    // Function to start market listings polling
    // NOTE: Market listings now use the unified AI polling endpoint
    function startMarketListingsPolling(regNumber, attemptCount = 0) {
        // Market listings polling is now handled by the unified AI polling system
        // This function is kept for backward compatibility but redirects to AI polling
        console.log('Market listings polling redirected to unified AI polling system');
        startAiSummaryPolling(regNumber, 1, 15); // Use AI polling system
    }

    // Function to show market listings generation status
    function showMarketListingsGenerationStatus(message) {
        // Only update if market listings section doesn't already have content
        if (!$('.market-listings-section .market-listings-content .market-listing-item').length) {
            const $loadingContainer = $('<div style="padding: 1.5rem; text-align: center;">');
            const $spinner = $('<div class="loading-spinner" style="width: 24px; height: 24px; border: 3px solid #e2e8f0; border-top: 3px solid #0ea5e9; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 0.75rem auto;">');
            const $statusText = $('<div style="color: #64748b; font-size: 0.875rem; font-weight: 500;">').text('Henter markedsdata...');

            $loadingContainer.append($spinner, $statusText);
            $('.market-listings-section .market-listings-content').html($loadingContainer);
        }
    }

    // Function to show market listings timeout
    function showMarketListingsTimeout() {
        $('.market-listings-section .market-listings-content').html(
            '<div style="padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.875rem;">Markedsdata tar lenger tid enn forventet. Prøv å oppdatere siden.</div>'
        );
    }

    // Add CSS for timeline margin
    $('.timeline').css('margin', '20px 0 50px 0');

    // Call populateOwnerHistoryTable after processing vehicle data
    // Ensure this is called at the right time, e.g., after vehicle data is loaded
    // For now, we'll attach it to the submit success handler for demonstration
    $form.on('submit', function(e) {
        // ... existing submit handler code ...
        // Inside the success callback, after processVehicleData:
        // success: function(response) {
        //     // ... other success logic ...
        //     processVehicleData(response, regNumber);
        //     populateOwnerHistoryTable(); // Call it here after data is processed
        //     // ... rest of success logic ...
        // },
    });
});