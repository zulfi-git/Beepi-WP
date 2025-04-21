<?php
class Vehicle_Lookup_Shortcode {
    public function init() {
        add_shortcode('vehicle_lookup', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        // Extract and sanitize product_id from shortcode attributes
        $atts = shortcode_atts(array(
            'product_id' => '62' // Default product ID
        ), $atts);
        
        $product_id = absint($atts['product_id']);
        
        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <h1 class="lookup-title">Søk på kjøretøy</h1>
            <p class="lookup-description">Finn informasjon om norske kjøretøy ved å søke på registreringsnummer</p>
            
            <form id="vehicle-lookup-form" class="plate-form">
                <div class="plate-input-wrapper">
                    <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                    <input type="text" id="regNumber" name="regNumber" required
                           class="plate-input"
                           placeholder="AB12345"
                           pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})">
                    <button type="submit" class="plate-search-button" aria-label="Search">
                        <div class="loading-spinner"></div>
                        <span class="search-icon">🔍</span>
                    </button>
                </div>
            </form>
            
            <div class="lookup-features">
                <div class="feature-item">✨ Direkte tilgang til kjøretøydata</div>
                <div class="feature-item">🔐 Data fra Statens vegvesen</div>
                <div class="feature-item">⚡ Live oppdateringer</div>
            </div>

            <div id="vehicle-lookup-results" style="display: none;">
                <div class="vehicle-header">
                    <div class="vehicle-info">
                        <img class="vehicle-logo" src="" alt="Car manufacturer logo">
                        <h2 class="vehicle-title"></h2>
                        <p class="vehicle-subtitle"></p>
                    </div>
                </div>
                
                <div class="owner-section">
                    <div id="owner-info-container">
                        <div id="owner-info-purchase">
                            <p>Hvem eier bilen?</p>
                            <div class="purchase-features">
                                <div>✨ Direkte tilgang</div>
                                <div>🎯 Enkelt</div>
                                <div>⚡ Live data</div>
                            </div>
                            <?php 
                            $product = wc_get_product($product_id);
                            $regular_price = $product ? $product->get_regular_price() : '39';
                            $sale_price = $product ? $product->get_sale_price() : null;
                            $final_price = $sale_price ? $sale_price : $regular_price;
                            ?>
                            <div class="price-display">
                                <?php if ($sale_price): ?>
                                    <div class="discount-badge">Tilbud!</div>
                                    <div class="price-wrapper">
                                        <span class="regular-price"><?php echo esc_html($regular_price); ?> kr</span>
                                        <span class="sale-price"><?php echo esc_html($sale_price); ?> kr</span>
                                    </div>
                                <?php else: ?>
                                    <div class="price-wrapper">
                                        <span class="regular-price-only"><?php echo esc_html($regular_price); ?> kr</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php echo do_shortcode("[woo_vipps_buy_now id={$product_id} /]"); ?>
                            </button>
                            <div class="trust-indicators">
                                <div>🔐 Data hentes fra Statens vegvesen</div>
                                <div>⏱️ Svar på noen få sekunder</div>
                            </div>
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
                                <summary><span>Generell informasjon</span><span>📋</span></summary>
                                <div class="details-content">
                                    <table class="info-table general-info-table"></table>
                                </div>
                            </details>
                            <details>
                                <summary><span>Reg. og kontroll</span><span>🔍</span></summary>
                                <div class="details-content">
                                    <table class="info-table registration-info-table"></table>
                                </div>
                            </details>
                        </div>
                    </section>
                    
                    <section id="technical-info" class="tab-panel">
                        <div class="accordion">
                            <details>
                                <summary><span>Motor og drivverk</span><span>🔧</span></summary>
                                <div class="details-content">
                                    <table class="info-table engine-info-table"></table>
                                </div>
                            </details>
                            <details>
                                <summary><span>Størrelse og vekt</span><span>⚖️</span></summary>
                                <div class="details-content">
                                    <table class="info-table size-weight-table"></table>
                                </div>
                            </details>
                            <details>
                                <summary><span>Dekk og felg</span><span>🛞</span></summary>
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
            <div class="powered-by">Levert av <a href="https://beepi.no" target="_blank">Beepi.no</a></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
