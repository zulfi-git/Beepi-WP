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
            <form id="vehicle-lookup-form" class="plate-form">
                <?php echo $this->render_plate_input($reg_number); ?>
            </form>
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
        ob_start();
        ?>
        <div class="owner-section">
            <div id="owner-info-container">
                <!-- Action Boxes -->
                <div class="action-boxes">
                    <div class="action-box" onclick="openActionPopup('eier')">
                        <div class="action-box-content">
                            <img src="https://beepi.no/wp-content/uploads/2025/09/Beepi-eier-600x600.png" alt="Se eier" class="action-box-icon">
                            <h4>Se eier</h4>
                        </div>
                    </div>
                    <div class="action-box" onclick="openActionPopup('skader')">
                        <div class="action-box-content">
                            <img src="https://beepi.no/wp-content/uploads/2025/09/Beepi-skade-600x600.png" alt="Se skader" class="action-box-icon">
                            <h4>Se skader</h4>
                        </div>
                    </div>
                    <div class="action-box" onclick="openActionPopup('pant')">
                        <div class="action-box-content">
                            <img src="https://beepi.no/wp-content/uploads/2025/09/Beepi-pant-600x600.png" alt="Se pant" class="action-box-icon">
                            <h4>Se pant</h4>
                        </div>
                    </div>
                </div>

                <!-- Popup Modals -->
                <div id="popup-eier" class="action-popup">
                    <div class="popup-content">
                        <span class="popup-close" onclick="closeActionPopup('eier')">&times;</span>
                        <h3>Se eier</h3>
                        <div class="popup-body">
                            <!-- Pricing, features & CTA will be added here -->
                        </div>
                    </div>
                </div>
                <div id="popup-skader" class="action-popup">
                    <div class="popup-content">
                        <span class="popup-close" onclick="closeActionPopup('skader')">&times;</span>
                        <h3>Se skader</h3>
                        <div class="popup-body">
                            <!-- Pricing, features & CTA will be added here -->
                        </div>
                    </div>
                </div>
                <div id="popup-pant" class="action-popup">
                    <div class="popup-content">
                        <span class="popup-close" onclick="closeActionPopup('pant')">&times;</span>
                        <h3>Se pant</h3>
                        <div class="popup-body">
                            <!-- Pricing, features & CTA will be added here -->
                        </div>
                    </div>
                </div>

                <div id="free-info-guide" class="free-info-guide">
                    <div class="guide-content">
                        <h4>💡 Se gratis informasjon</h4>
                        <p>Utforsk tekniske detaljer, EU-kontroll status og mer i boksene nedenfor - helt gratis!</p>
                        <button type="button" class="explore-free-btn" onclick="expandAllAccordions()">Utforsk gratis info</button>
                    </div>
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
            
            <div id="footer-action-boxes" class="action-boxes" style="margin-top: 2rem; display: none;">
                <div class="action-box" onclick="openActionPopup(\'eier\')">
                    <div class="action-box-content">
                        <img src="https://beepi.no/wp-content/uploads/2025/09/Beepi-eier-600x600.png" alt="Se eier" class="action-box-icon">
                        <h4>Se eier</h4>
                    </div>
                </div>
                <div class="action-box" onclick="openActionPopup(\'skader\')">
                    <div class="action-box-content">
                        <img src="https://beepi.no/wp-content/uploads/2025/09/Beepi-skade-600x600.png" alt="Se skader" class="action-box-icon">
                        <h4>Se skader</h4>
                    </div>
                </div>
                <div class="action-box" onclick="openActionPopup(\'pant\')">
                    <div class="action-box-content">
                        <img src="https://beepi.no/wp-content/uploads/2025/09/Beepi-pant-600x600.png" alt="Se pant" class="action-box-icon">
                        <h4>Se pant</h4>
                    </div>
                </div>
            </div>
            
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