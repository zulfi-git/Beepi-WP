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
        ?>
        <div class="vehicle-lookup-container">
            <form id="vehicle-lookup-form" class="plate-form">
                <div class="plate-input-wrapper">
                    <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                    <input type="text" id="regNumber" name="regNumber" required
                           class="plate-input"
                           placeholder="CO11204"
                           value="<?php echo esc_attr($reg_number); ?>"
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
                        <img class="vehicle-logo" src="" alt="Car manufacturer logo">
                        <h2 class="vehicle-title"></h2>
                        <p class="vehicle-subtitle"></p>
                    </div>
                </div>

                <div class="owner-section">
                    <div id="owner-info-container">
                        <div id="owner-info-purchase">
                            <p>Hvem eier bilen?</p>
                            <?php 
                            $product = wc_get_product($product_id);
                            $regular_price = $product ? $product->get_regular_price() : '39';
                            $sale_price = $product ? $product->get_sale_price() : null;
                            $final_price = $sale_price ? $sale_price : $regular_price;
                            ?>
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
                            <div class="trust-indicators">
                                <div>🔐 Data hentes fra Statens vegvesen</div>
                                <div>⏱️ Svar på noen få sekunder</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion">
                    <details>
                        <summary><span>Generell informasjon</span><span>📋</span></summary>
                        <div class="details-content">
                            <table class="info-table basic-info-table"></table>
                        </div>
                    </details>
                    <details>
                        <summary><span>Reg. og EU-kontroll</span><span>🔍</span></summary>
                        <div class="details-content">
                            <table class="info-table registration-info-table"></table>
                        </div>
                    </details>
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
                    <details>
                        <summary><span>Merknader</span><span>📝</span></summary>
                        <div class="details-content">
                            <table class="info-table notes-info-table"></table>
                        </div>
                    </details>
                </div>
            </div>

            <div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
            <div id="quota-display" class="quota-display" style="display: none;"></div>
            <div id="version-display" class="version-display">v<?php echo VEHICLE_LOOKUP_VERSION; ?></div>
            <div class="powered-by">Levert av <a href="https://beepi.no" target="_blank">Beepi.no</a></div>
        </div>
        
        <?php if ($reg_number): ?>
        <script>
        jQuery(document).ready(function($) {
            // Auto-trigger lookup if registration number is in URL
            setTimeout(function() {
                $('#vehicle-lookup-form').trigger('submit');
            }, 500);
        });
        </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
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
