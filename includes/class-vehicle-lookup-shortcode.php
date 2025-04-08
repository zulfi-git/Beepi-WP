
<?php
class Vehicle_Lookup_Shortcode {
    public function init() {
        add_shortcode('vehicle_lookup', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <form id="vehicle-lookup-form" class="vehicle-lookup-form">
                <div class="form-group">
                    <label for="regNumber">Registreringsnummer:</label>
                    <input type="text" id="regNumber" name="regNumber" required 
                           class="reg-input"
                           placeholder="Tast inn registreringsnummer"
                           pattern="([A-Z]{2}\d{4,5}|E[KLVBCDE]\d{5}|CD\d{5}|\d{5}|[A-Z]\d{3}|[A-Z]{2}\d{3})">
                </div>
                <button type="submit" class="lookup-button">Søk</button>
            </form>

            <div id="vehicle-lookup-results" style="display: none;">
                <div class="vehicle-header">
                    <h2 class="vehicle-title"></h2>
                    <p class="vehicle-subtitle"></p>
                </div>
                
                <nav class="tabs">
                    <ul>
                        <li data-tab="general-info"><a href="#general-info">Generell info</a></li>
                        <li data-tab="technical-info"><a href="#technical-info">Tekniske detaljer</a></li>
                        <li data-tab="registration-info"><a href="#registration-info">EU kontroll</a></li>
                    </ul>
                </nav>
                
                <div class="tab-content">
                    <section id="general-info" class="tab-panel">
                        <div class="accordion">
                            <details>
                                <summary>Generell informasjon</summary>
                                <div class="details-content">
                                    <table class="info-table general-info-table"></table>
                                </div>
                            </details>
                            
                            <details>
                                <summary>Størrelse og vekt</summary>
                                <div class="details-content">
                                    <table class="info-table size-weight-table"></table>
                                </div>
                            </details>
                        </div>
                    </section>
                    
                    <section id="technical-info" class="tab-panel">
                        <div class="accordion">
                            <details>
                                <summary>Motor og drivverk</summary>
                                <div class="details-content">
                                    <table class="info-table engine-info-table"></table>
                                </div>
                            </details>
                        </div>
                    </section>

                    <section id="registration-info" class="tab-panel">
                        <div class="accordion">
                            <details>
                                <summary>Registrering og kontroll</summary>
                                <div class="details-content">
                                    <table class="info-table registration-info-table"></table>
                                </div>
                            </details>
                        </div>
                    </section>
                </div>
            </div>

            <div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
