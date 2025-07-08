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
        $product = wc_get_product($product_id);
        $regular_price = $product ? $product->get_regular_price() : '39';
        $sale_price = $product ? $product->get_sale_price() : null;
        
        ob_start();
        ?>
        <div class="owner-section">
            <div id="owner-info-container">
                <div id="owner-info-purchase">
                    <p>Hvem eier bilen?</p>
                    <div class="price-display">
                        <?php if ($sale_price): ?>
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
        return sprintf(
            '<div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
            <div id="quota-display" class="quota-display" style="display: none;"></div>
            <div id="version-display" class="version-display">v%s</div>
            <div class="powered-by">Levert av <a href="https://beepi.no" target="_blank">Beepi.no</a></div>
            </div>',
            VEHICLE_LOOKUP_VERSION
        );
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
