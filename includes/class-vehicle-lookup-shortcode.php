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

        // Check for registration number in URL path or query parameter
        $reg_number = $this->get_reg_from_url();

        ob_start();
        echo $this->render_form_section($reg_number);
        echo $this->render_results_section($product_id);
        echo $this->render_footer_section();
        echo $this->render_premium_data_script();
        echo $this->render_auto_submit_script($reg_number);

        return ob_get_clean();
    }

    private function render_form_section($reg_number) {
        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <section class="lookup-hero">
                <span class="hero-badge">Oppslag på sekunder</span>
                <h2 class="hero-heading">Få full innsikt i bilen før du bestemmer deg</h2>
                <p class="hero-subheading">Skriv inn registreringsnummeret for å hente gratis teknisk informasjon og lås opp komplett eierskaps- og skaderapporter ved behov.</p>
                <ul class="hero-benefits">
                    <li>🔍 Gratis tekniske data og EU-status uten innlogging</li>
                    <li>⚡ Premium rapport levert umiddelbart etter betaling</li>
                    <li>🛡️ Data direkte fra Statens vegvesen og andre kilder</li>
                </ul>
            </section>
            <form id="vehicle-lookup-form" class="plate-form">
                <?php echo $this->render_plate_input($reg_number); ?>
            </form>
            <p class="form-footnote">Vi lagrer ikke registreringsnummeret ditt. Rapporten leveres digitalt med Vipps-betaling.</p>
        <?php
        return ob_get_clean();
    }

    private function render_plate_input($reg_number) {
        return Vehicle_Lookup_Helpers::render_plate_input($reg_number);
    }

    private function render_results_section($product_id) {
        ob_start();
        ?>
        <div id="vehicle-lookup-results" style="display: none;">
            <?php echo $this->render_vehicle_header(); ?>
            <?php echo $this->render_owner_section($product_id); ?>
            <?php echo $this->render_accordion_section(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_vehicle_header() {
        return Vehicle_Lookup_Helpers::render_vehicle_header();
    }

    private function render_owner_section($product_id) {
        // Get products for both tiers
        $basic_product = wc_get_product(62);
        $premium_product = wc_get_product(739);

        $basic_price = $basic_product ? $basic_product->get_regular_price() : '39';
        $basic_sale = $basic_product ? $basic_product->get_sale_price() : null;

        $premium_price = $premium_product ? $premium_product->get_regular_price() : '89';
        $premium_sale = $premium_product ? $premium_product->get_sale_price() : null;

        ob_start();
        ?>
        <div class="owner-section">
            <div id="owner-info-container" class="owner-info-container">
                <aside id="free-info-guide" class="free-info-guide">
                    <div class="guide-content">
                        <span class="guide-badge">Gratis innsikt</span>
                        <h3>Se gratis data før du kjøper rapport</h3>
                        <p>Åpne seksjonene under for å lese teknisk info, EU-status og historikk uten kostnad. Perfekt for raske vurderinger.</p>
                        <ul class="guide-benefits">
                            <li>✅ Tekniske spesifikasjoner og dimensjoner</li>
                            <li>✅ EU-kontroll og registreringsstatus</li>
                            <li>✅ Dekkinformasjon og merknader</li>
                        </ul>
                        <button type="button" class="explore-free-btn" onclick="expandAllAccordions()">Vis gratis informasjon</button>
                    </div>
                </aside>

                <section id="tier-selection" class="tier-selection">
                    <div class="tier-selection-heading">
                        <h3>Velg rapporttype</h3>
                        <p>Lås opp mer dokumentasjon for tryggere kjøp. Rapportene leveres digitalt på sekunder.</p>
                    </div>
                    <div class="tier-comparison">
                        <article class="tier-card basic-tier">
                            <header class="tier-header">
                                <h4><?php echo $basic_product ? esc_html($basic_product->get_name()) : 'Basic rapport'; ?></h4>
                                <p class="tier-tagline">Rask statusrapport med de viktigste fakta</p>
                                <div class="tier-price">
                                    <?php if ($basic_sale): ?>
                                        <span class="regular-price"><?php echo esc_html($basic_price); ?> kr</span>
                                        <span class="sale-price"><?php echo esc_html($basic_sale); ?> kr</span>
                                    <?php else: ?>
                                        <span class="price"><?php echo esc_html($basic_price); ?> kr</span>
                                    <?php endif; ?>
                                </div>
                            </header>
                            <ul class="tier-features">
                                <li class="feature-item">✓ Nåværende eier</li>
                                <li class="feature-item">✓ Alle tekniske detaljer</li>
                                <li class="feature-item">✓ EU-kontroll status</li>
                            </ul>
                            <div class="tier-meta">
                                <p>Leveres umiddelbart etter betaling.</p>
                            </div>
                            <div class="tier-purchase">
                                <?php echo do_shortcode("[woo_vipps_buy_now id=62 /]"); ?>
                            </div>
                        </article>

                        <article class="tier-card premium-tier recommended">
                            <span class="tier-badge">Mest populær</span>
                            <header class="tier-header">
                                <h4><?php echo $premium_product ? esc_html($premium_product->get_name()) : 'Premium rapport'; ?></h4>
                                <p class="tier-tagline">Komplett trygghet med hele historikken</p>
                                <?php
                                if ($premium_sale && $premium_sale < $premium_price):
                                    // Calculate percentage discount if there's a sale price
                                    $discount_percentage = round((($premium_price - $premium_sale) / $premium_price) * 100);
                                    ?>
                                    <div class="savings-display">
                                        Spar <?php echo esc_html($discount_percentage); ?>% ved å kjøpe denne!
                                    </div>
                                <?php endif; ?>
                                <div class="tier-price">
                                    <?php if ($premium_sale): ?>
                                        <span class="regular-price"><?php echo esc_html($premium_price); ?> kr</span>
                                        <span class="sale-price"><?php echo esc_html($premium_sale); ?> kr</span>
                                    <?php else: ?>
                                        <span class="price"><?php echo esc_html($premium_price); ?> kr</span>
                                    <?php endif; ?>
                                </div>
                            </header>
                            <ul class="tier-features">
                                <li class="feature-item">✓ Alt fra Basic rapport</li>
                                <li class="feature-item">✓ Komplett eierhistorikk</li>
                                <li class="feature-item">✓ Skadehistorikk og kilometerstand</li>
                                <li class="feature-item">✓ Detaljert kjøretøyrapport</li>
                                <li class="feature-item">✓ Import- og avgiftsinformasjon</li>
                            </ul>
                            <div class="tier-meta">
                                <p>Vipps-betaling og rapport sendt til e-post og SMS.</p>
                            </div>
                            <div class="tier-purchase">
                                <?php
                                $vipps_button = do_shortcode("[woo_vipps_buy_now id=739 /]");
                                echo $vipps_button;
                                ?>
                                <script>
                                window.premiumVippsBuyButton = <?php echo json_encode($vipps_button); ?>;
                                </script>
                            </div>
                        </article>
                    </div>
                    <div class="tier-checkout-safety">
                        <div class="benefit-item">🔒 Kryptert betaling med Vipps</div>
                        <div class="benefit-item">📄 Kvittering og rapport levert digitalt</div>
                        <div class="benefit-item">💬 Support fra bileksperter ved spørsmål</div>
                    </div>
                    <?php echo $this->render_trust_indicators(); ?>
                </section>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_trust_indicators() {
        return Vehicle_Lookup_Helpers::render_trust_indicators();
    }

    private function render_accordion_section() {
        return Vehicle_Lookup_Helpers::render_accordion_section();
    }

    private function render_footer_section() {
        // Get usage statistics
        $db_handler = new Vehicle_Lookup_Database();
        $today = date('Y-m-d');
        $stats = $db_handler->get_stats($today . ' 00:00:00', $today . ' 23:59:59');
        $today_searches = $stats ? $stats->total_lookups : 0;

        return sprintf(
            '<div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
            <div id="quota-display" class="quota-display" style="display: none;"></div>
            <div class="usage-stats">
                <div class="stat-item">
                    <span class="stat-number">%d</span>
                    <span class="stat-label">oppslag i dag</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">15.000+</span>
                    <span class="stat-label">fornøyde kunder</span>
                </div>
            </div>
            <div id="version-display" class="version-display">v%s</div>
            <div class="powered-by">Levert av <a href="https://beepi.no" target="_blank">Beepi.no</a></div>
            </div>',
            $today_searches,
            VEHICLE_LOOKUP_VERSION
        );
    }

    private function render_premium_data_script() {
        $premium_product = wc_get_product(739);
        $premium_data = array(
            'name' => $premium_product ? $premium_product->get_name() : 'Premium Kjøretøyrapport',
            'regular_price' => $premium_product ? $premium_product->get_regular_price() : '89',
            'sale_price' => $premium_product ? $premium_product->get_sale_price() : null
        );

        $vipps_button = do_shortcode("[woo_vipps_buy_now id=739 /]");

        return '<script>
        window.vehicleLookupData = window.vehicleLookupData || {};
        window.vehicleLookupData.premiumProduct = ' . json_encode($premium_data) . ';
        window.premiumVippsBuyButton = ' . json_encode($vipps_button) . ';
        </script>';
    }

    private function render_auto_submit_script($reg_number) {
        if (!$reg_number) return '';

        return '<script>
        jQuery(document).ready(function($) {
            setTimeout(function() {
                $("#vehicle-lookup-form").trigger("submit");
            }, 500);
        });
        </script>';
    }



    /**
     * Extract registration number from URL path or query parameters
     */
    private function get_reg_from_url() {
        return Vehicle_Lookup_Helpers::get_reg_from_url();
    }
}