<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beepi Vehicle Lookup - Enhanced Cards</title>
    <link rel="stylesheet" href="assets/css/vehicle-lookup.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f8fafc;">

<div class="vehicle-lookup-container">
    <h1 style="text-align: center; color: #1e293b; margin-bottom: 2rem;">Vehicle Lookup with Enhanced Status Cards</h1>
    
    <!-- Vehicle Lookup Form -->
    <form id="vehicle-lookup-form" class="plate-form">
        <div class="plate-input-wrapper">
            <div class="plate-flag">
                <div>🇳🇴</div>
                <div class="plate-country">NOR</div>
            </div>
            <input type="text" id="regNumber" class="plate-input" placeholder="AB12345" maxlength="7" required>
            <button type="submit" class="plate-search-button">
                <span class="search-icon">🔍</span>
                <span class="button-text">Søk</span>
                <div class="loading-spinner"></div>
            </button>
        </div>
    </form>
    
    <!-- Error Display -->
    <div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
    
    <!-- Results Container -->
    <div id="vehicle-lookup-results" style="display: none;">
        <div class="vehicle-header">
            <img src="assets/images/car.png" alt="Vehicle Logo" class="vehicle-logo">
            <div class="vehicle-info">
                <h2 class="vehicle-title"></h2>
                <p class="vehicle-subtitle"></p>
            </div>
        </div>
        
        <!-- Status cards and metrics will be inserted here by JavaScript -->
        
        <div class="accordion">
            <details data-free="true">
                <summary><span>Grunnleggende informasjon</span></summary>
                <div class="details-content">
                    <table class="info-table basic-info-table"></table>
                </div>
            </details>
        </div>
    </div>
    
    <!-- Sample Vehicle Data for Demo -->
    <div id="demo-vehicle" style="display: none;">
        <div class="vehicle-header">
            <img src="assets/images/car.png" alt="Vehicle Logo" class="vehicle-logo">
            <div class="vehicle-info">
                <h2 class="vehicle-title">AB12345</h2>
                <p class="vehicle-subtitle">BMW 3 Series <strong>2020</strong></p>
                <div class="vehicle-tags">
                    <span class="tag fuel diesel">⛽ Diesel</span>
                    <span class="tag gearbox automatic">⚙️ Automatisk</span>
                </div>
            </div>
        </div>

        <!-- Status Cards -->
        <div class="status-cards-grid">
            <div class="status-card registration-card">
                <div class="status-icon">✓</div>
                <div class="status-content">
                    <h3>Registrering</h3>
                    <p>Registrert</p>
                </div>
            </div>
            <div class="status-card eu-card warning">
                <div class="status-icon">⚠️</div>
                <div class="status-content">
                    <h3>EU-kontroll</h3>
                    <p>15 dager igjen</p>
                </div>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">📅</div>
                <div class="metric-content">
                    <span class="metric-value">4 år</span>
                    <span class="metric-label">Alder</span>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">⚡</div>
                <div class="metric-content">
                    <span class="metric-value">190 hk</span>
                    <span class="metric-label">Effekt</span>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">📏</div>
                <div class="metric-content">
                    <span class="metric-value">4.7m</span>
                    <span class="metric-label">Lengde</span>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">⚖️</div>
                <div class="metric-content">
                    <span class="metric-value">1.8t</span>
                    <span class="metric-label">Vekt</span>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin: 2rem 0;">
        <button onclick="showDemo()" style="background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 16px; cursor: pointer;">
            Show Enhanced Cards Demo
        </button>
        <button onclick="testStatusCards()" style="background: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 16px; cursor: pointer; margin-left: 10px;">
            Test Status Cards
        </button>
    </div>
</div>

<script src="assets/js/vehicle-lookup.js"></script>
<script>
// Global configuration for the vehicle lookup
const vehicleLookupAjax = {
    ajaxurl: '/test-api.php', // Mock API endpoint for testing
    nonce: 'test-nonce'
};

function showDemo() {
    document.getElementById('demo-vehicle').style.display = 'block';
}

function testStatusCards() {
    // Simulate vehicle data for testing status cards
    const mockVehicleData = {
        registrering: {
            registreringsstatus: {
                kodeVerdi: 'REGISTRERT',
                kodeBeskrivelse: 'Registrert'
            }
        },
        periodiskKjoretoyKontroll: {
            kontrollfrist: '2025-10-15'
        },
        forstegangsregistrering: {
            registrertForstegangNorgeDato: '2020-01-15'
        },
        godkjenning: {
            tekniskGodkjenning: {
                tekniskeData: {
                    generelt: {
                        merke: [{merke: 'BMW'}],
                        handelsbetegnelse: ['3 Series']
                    },
                    motorOgDrivverk: {
                        motor: [{
                            arbeidsprinsipp: {kodeBeskrivelse: 'Diesel'},
                            nettoEffekt: 140
                        }],
                        girkassetype: {kodeBeskrivelse: 'Automatisk'}
                    },
                    dimensjoner: {
                        lengde: 4700
                    },
                    vekter: {
                        egenvekt: 1800
                    }
                }
            }
        },
        kjoretoyId: {
            kjennemerke: 'AB12345'
        }
    };

    // Simulate the vehicle lookup process
    $('#vehicle-lookup-results').show();
    
    // Test displayVehicleHeader
    if (typeof displayVehicleHeader === 'function') {
        displayVehicleHeader(mockVehicleData, 'AB12345');
    } else {
        // Fallback: manually update header
        $('.vehicle-title').text('AB12345');
        $('.vehicle-subtitle').html('BMW 3 Series <strong>2020</strong>');
    }
    
    // Test renderStatusCards
    if (typeof renderStatusCards === 'function') {
        renderStatusCards(mockVehicleData);
    } else {
        console.error('renderStatusCards function not found');
    }
    
    // Test renderMetricsGrid
    if (typeof renderMetricsGrid === 'function') {
        renderMetricsGrid(mockVehicleData);
    } else {
        console.error('renderMetricsGrid function not found');
    }
    
    // Scroll to results
    $('html, body').animate({
        scrollTop: $('.vehicle-lookup-container').offset().top - 20
    }, 500);
}
</script>

</body>
</html>