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
        return sprintf(
            '<div class="plate-input-wrapper">
                <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                <input type="text" id="regNumber" name="regNumber" required
                       class="plate-input"
                       placeholder="CO11204"
                       value="%s"
                       pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})">
                <button type="submit" class="plate-search-button" aria-label="Search">
                    <div class="loading-spinner"></div>
                    <span class="search-icon">🔍</span>
                </button>
            </div>',
            esc_attr($reg_number)
        );
    }

    private function render_results_section($product_id) {
        ob_start();

            <div id="vehicle-lookup-results" style="display: none;">
            <?php echo $this->render_vehicle_header(); ?>
            <?php echo $this->render_owner_section($product_id); ?>
            <?php echo $this->render_accordion_section(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_vehicle_header() {
        return '<div class="vehicle-header">
            <div class="vehicle-info">
                <img class="vehicle-logo" src="" alt="Car manufacturer logo">
                <h2 class="vehicle-title"></h2>
                <p class="vehicle-subtitle"></p>
            </div>
        </div>';
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
        return '<div class="trust-indicators">
            <div>🔐 Data hentes fra Statens vegvesen</div>
            <div>⏱️ Svar på noen få sekunder</div>
        </div>';
    }

    private function render_accordion_section() {
        $accordion_sections = [
            ['Generell informasjon', '📋', 'basic-info-table'],
            ['Reg. og EU-kontroll', '🔍', 'registration-info-table'],
            ['Motor og drivverk', '🔧', 'engine-info-table'],
            ['Størrelse og vekt', '⚖️', 'size-weight-table'],
            ['Dekk og felg', '🛞', 'tire-info-table'],
            ['Merknader', '📝', 'notes-info-table']
        ];

        $html = '<div class="accordion">';
        foreach ($accordion_sections as $section) {
            $html .= sprintf(
                '<details>
                    <summary><span>%s</span><span>%s</span></summary>
                    <div class="details-content">
                        <table class="info-table %s"></table>
                    </div>
                </details>',
                $section[0], $section[1], $section[2]
            );
        }
        $html .= '</div>';
        
        return $html;
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
        // Check WordPress query var first (from rewrite rule)
        $wp_reg_number = get_query_var('reg_number');
        if (!empty($wp_reg_number)) {
            return strtoupper(sanitize_text_field($wp_reg_number));
        }
        
        // Check standard query parameter
        if (isset($_GET['reg']) && !empty($_GET['reg'])) {
            return strtoupper(sanitize_text_field($_GET['reg']));
        }
        
        // Check URL path for registration number as fallback
        $request_uri = $_SERVER['REQUEST_URI'];
        $path_parts = explode('/', trim($request_uri, '/'));
        
        // Look for registration number in the last part of the path
        if (!empty($path_parts)) {
            $last_part = end($path_parts);
            // Remove query string if present
            $last_part = explode('?', $last_part)[0];
            
            // Validate if it looks like a registration number
            $valid_patterns = array(
                '/^[A-Za-z]{2}\d{4,5}$/',         // Standard vehicles
                '/^[Ee][KkLlVvBbCcDdEe]\d{5}$/',  // Electric vehicles
                '/^[Cc][Dd]\d{5}$/',              // Diplomatic vehicles
                '/^\d{5}$/',                      // Temporary tourist plates
                '/^[A-Za-z]\d{3}$/',              // Antique vehicles
                '/^[A-Za-z]{2}\d{3}$/'            // Provisional plates
            );
            
            foreach ($valid_patterns as $pattern) {
                if (preg_match($pattern, $last_part)) {
                    return strtoupper(sanitize_text_field($last_part));
                }
            }
        }
        
        return '';
    }
}
