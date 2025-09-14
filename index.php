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
    <h1 style="text-align: center; color: #1e293b; margin-bottom: 2rem;">Enhanced Vehicle Cards Demo</h1>
    
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
    </div>
</div>

<script>
function showDemo() {
    document.getElementById('demo-vehicle').style.display = 'block';
}
</script>

</body>
</html>