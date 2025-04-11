<?php
class Vehicle_Lookup_Shortcode {
    public function init() {
        add_shortcode('vehicle_lookup', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <form id="vehicle-lookup-form" class="plate-form">
                <div class="plate-input-wrapper">
                    <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                    <input type="text" id="regNumber" name="regNumber" required
                           class="plate-input"
                           placeholder="CU11262"
                           pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})">
                    <button type="submit" class="plate-search-button" aria-label="Search">
                        <div class="loading-spinner"></div>
                        <span class="search-icon">🔍</span>
                    </button>
                </div>
            </form>

            <div id="vehicle-lookup-results" style="display: none;">
                <div class="vehicle-header">
                    <div class="vehicle-info">
                        <h2 class="vehicle-title"></h2>
                        <img class="vehicle-logo" src="" alt="Car manufacturer logo">
                        <p class="vehicle-subtitle"></p>
                    </div>
                </div>
                
                <div class="owner-section">
                    <div id="owner-info-container">
                        <div id="owner-info-purchase">
                            <p>Hvem eier bilen?</p>
                            <div class="purchase-features">
                                <div>✨ Enkelt</div>
                                <div>🔒 100% Anonymt</div>
                                <div>⚡ Raskt</div>
                            </div>
                            <?php 
                            $product = wc_get_product(62);
                            $regular_price = $product ? $product->get_regular_price() : '39';
                            $sale_price = $product ? $product->get_sale_price() : null;
                            $final_price = $sale_price ? $sale_price : $regular_price;
                            ?>
                            <button class="purchase-button" data-product="62">
                                Kjøp med vipps! 
                                <div class="price-wrapper">
                                    <?php if ($sale_price): ?>
                                        <span class="regular-price"><?php echo esc_html($regular_price); ?> kr</span>
                                    <?php endif; ?>
                                    <span class="price"><?php echo esc_html($final_price); ?> kr</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <nav class="tabs">
                    <ul>
                        <li data-tab="general-info"><a href="#general-info">Generell</a></li>
                        <li data-tab="technical-info"><a href="#technical-info">Teknisk</a></li>
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
                                <summary>Registrering og kontroll</summary>
                                <div class="details-content">
                                    <table class="info-table registration-info-table"></table>
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
                            <details>
                                <summary>Størrelse og vekt</summary>
                                <div class="details-content">
                                    <table class="info-table size-weight-table"></table>
                                </div>
                            </details>
                            <details>
                                <summary>Dekk og felg</summary>
                                <div class="details-content">
                                    <table class="info-table tire-info-table"></table>
                                </div>
                            </details>
                        </div>
                    </section>
                </div>
            </div>

            <div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
            <div id="quota-display" class="quota-display" style="display: none;"></div>
            <div id="version-display" class="version-display">v<?php echo VEHICLE_LOOKUP_VERSION; ?></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
