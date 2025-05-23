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

        // Get registration number from query variable (set by rewrite rule)
        $regNumber = get_query_var('reg_number', '');
        
        // If not found in query var, check URL directly as fallback
        if (empty($regNumber)) {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            if (preg_match('#/sok/([A-Za-z0-9]+)#', $request_uri, $matches)) {
                $regNumber = sanitize_text_field($matches[1]);
            }
        }
        $is_valid = false;

        if (!empty($regNumber)) {
            // Basic validation of the registration number format (customize as needed)
            $pattern = '/([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})/';
            $is_valid = preg_match($pattern, $regNumber);
        }

        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <form id="vehicle-lookup-form" class="plate-form">
                <div class="plate-input-wrapper">
                    <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                    <input type="text" id="regNumber" name="regNumber" required
                           class="plate-input"
                           placeholder="CU11262"
                           pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})"
                           value="<?php echo esc_attr($regNumber); ?>">
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
                        <summary><span>Reg. og kontroll</span><span>🔍</span></summary>
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
        <?php if ($is_valid): ?>
        <script>
            jQuery(document).ready(function($) {
                $('#vehicle-lookup-form').submit();
            });
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
}
?>