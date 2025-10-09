<?php
class Vehicle_Lookup_Shortcode {
    public function init() {
        add_shortcode('vehicle_lookup', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        // Extract and sanitize product IDs from shortcode attributes
        $atts = shortcode_atts(array(
            'product_id' => '', // Legacy attribute (basic product)
            'basic_product_id' => '',
            'premium_product_id' => '',
        ), $atts);

        $basic_attr = $atts['basic_product_id'] !== '' ? $atts['basic_product_id'] : $atts['product_id'];
        $premium_attr = $atts['premium_product_id'];

        $basic_product_id = absint($basic_attr);
        if ($basic_product_id <= 0) {
            $basic_product_id = Vehicle_Lookup_Helpers::get_basic_product_id();
        }

        $premium_product_id = absint($premium_attr);
        if ($premium_product_id <= 0) {
            $premium_product_id = Vehicle_Lookup_Helpers::get_premium_product_id();
        }

        // Check for registration number in URL path or query parameter
        $reg_number = $this->get_reg_from_url();

        ob_start();
        echo $this->render_form_section($reg_number);
        echo $this->render_results_section($basic_product_id, $premium_product_id);
        echo $this->render_footer_section();
        echo $this->render_premium_data_script($basic_product_id, $premium_product_id);
        echo $this->render_auto_submit_script($reg_number);

        return ob_get_clean();
    }

    private function render_form_section($reg_number) {
        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <form id="vehicle-lookup-form" class="plate-form">
                <?php echo $this->render_plate_input($reg_number); ?>
            </form>
        <?php
        return ob_get_clean();
    }

    private function render_plate_input($reg_number) {
        return Vehicle_Lookup_Helpers::render_plate_input($reg_number);
    }

    private function render_results_section($basic_product_id, $premium_product_id) {
        ob_start();
        ?>
        <div id="vehicle-lookup-results" style="display: none;">
            <?php echo $this->render_vehicle_header(); ?>
            <?php echo $this->render_owner_section($basic_product_id, $premium_product_id); ?>
            <?php echo $this->render_accordion_section(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_vehicle_header() {
        return Vehicle_Lookup_Helpers::render_vehicle_header();
    }

    private function render_owner_section($basic_product_id, $premium_product_id) {
        $basic_product = function_exists('wc_get_product') ? wc_get_product($basic_product_id) : false;
        $premium_product = function_exists('wc_get_product') ? wc_get_product($premium_product_id) : false;

        $basic_name = 'Basic rapport';
        $basic_price = '39';
        $basic_sale = null;

        if ($basic_product) {
            $basic_name = $basic_product->get_name() ?: $basic_name;
            $basic_price = $basic_product->get_regular_price() ?: $basic_price;
            $basic_sale = $basic_product->get_sale_price() ?: null;
        }

        $premium_name = 'Premium rapport';
        $premium_price = '89';
        $premium_sale = null;

        if ($premium_product) {
            $premium_name = $premium_product->get_name() ?: $premium_name;
            $premium_price = $premium_product->get_regular_price() ?: $premium_price;
            $premium_sale = $premium_product->get_sale_price() ?: null;
        }

        ob_start();
        ?>
        <div class="owner-section">
            <div id="owner-info-container">
                <div id="free-info-guide" class="free-info-guide">
                    <div class="guide-content">
                        <h4>💡 Se gratis informasjon</h4>
                        <p>Utforsk tekniske detaljer, EU-kontroll status og mer i boksene nedenfor - helt gratis!</p>
                        <button type="button" class="explore-free-btn" onclick="expandAllAccordions()">Utforsk gratis info</button>
                    </div>
                </div>

                <div id="tier-selection">
                    <h3>Velg rapporttype</h3>
                    <div class="tier-comparison">
                        <!-- Basic Tier -->
                        <div class="tier-card basic-tier">
                            <div class="tier-header">
                                <h4><?php echo esc_html($basic_name); ?></h4>
                                <div class="tier-price">
                                    <?php if (!empty($basic_sale)): ?>
                                        <span class="regular-price"><?php echo esc_html($basic_price); ?> kr</span>
                                        <span class="sale-price"><?php echo esc_html($basic_sale); ?> kr</span>
                                    <?php else: ?>
                                        <span class="price"><?php echo esc_html($basic_price); ?> kr</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tier-features">
                                <div class="feature-item">✓ Nåværende eier</div>
                                <div class="feature-item">✓ Alle tekniske detaljer</div>
                                <div class="feature-item">✓ EU-kontroll status</div>
                            </div>
                            <div class="tier-purchase">
                                <?php if ($basic_product_id > 0): ?>
                                    <?php echo do_shortcode('[woo_vipps_buy_now id=' . absint($basic_product_id) . ' /]'); ?>
                                <?php else: ?>
                                    <p class="vipps-button-missing">Produkt-ID for basic rapport er ikke konfigurert.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Premium Tier -->
                        <div class="tier-card premium-tier recommended">
                            <div class="tier-badge">Mest populær</div>
                            <div class="tier-header">
                                <h4><?php echo esc_html($premium_name); ?></h4>
                                <?php
                                // Calculate percentage discount if there's a sale price
                                if (!empty($premium_sale) && $premium_sale < $premium_price):
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
                            </div>
                            <div class="tier-features">
                                <div class="feature-item">✓ Alt fra Basic rapport</div>
                                <div class="feature-item">✓ Komplett eierhistorikk</div>
                                <div class="feature-item">✓ Skadehistorikk</div>
                                <div class="feature-item">✓ Detaljert kjøretøyrapport</div>
                                <div class="feature-item">✓ Import</div>
                            </div>
                            <div class="tier-purchase">
                                <?php if ($premium_product_id > 0): ?>
                                    <?php echo do_shortcode('[woo_vipps_buy_now id=' . absint($premium_product_id) . ' /]'); ?>
                                <?php else: ?>
                                    <p class="vipps-button-missing">Produkt-ID for premium rapport er ikke konfigurert.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php echo $this->render_trust_indicators(); ?>
                </div>
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

    private function render_premium_data_script($basic_product_id, $premium_product_id) {
        $premium_product = function_exists('wc_get_product') ? wc_get_product($premium_product_id) : false;
        $basic_product = function_exists('wc_get_product') ? wc_get_product($basic_product_id) : false;

        $premium_data = array(
            'id' => $premium_product_id,
            'name' => $premium_product && $premium_product->get_name() ? $premium_product->get_name() : 'Premium rapport',
            'regular_price' => $premium_product && $premium_product->get_regular_price() ? $premium_product->get_regular_price() : '89',
            'sale_price' => $premium_product && $premium_product->get_sale_price() ? $premium_product->get_sale_price() : null,
        );

        $basic_data = array(
            'id' => $basic_product_id,
            'name' => $basic_product && $basic_product->get_name() ? $basic_product->get_name() : 'Basic rapport',
            'regular_price' => $basic_product && $basic_product->get_regular_price() ? $basic_product->get_regular_price() : '39',
            'sale_price' => $basic_product && $basic_product->get_sale_price() ? $basic_product->get_sale_price() : null,
        );

        $vipps_button = $premium_product_id > 0 ? do_shortcode('[woo_vipps_buy_now id=' . absint($premium_product_id) . ' /]') : '';

        return '<script>
        window.vehicleLookupData = window.vehicleLookupData || {};
        window.vehicleLookupData.basicProduct = ' . wp_json_encode($basic_data) . ';
        window.vehicleLookupData.premiumProduct = ' . wp_json_encode($premium_data) . ';
        window.premiumVippsBuyButton = ' . wp_json_encode($vipps_button) . ';
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