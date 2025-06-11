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
                <div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
            </form>

            <div id="vehicle-lookup-results" style="display: none;">
                <div class="vehicle-header">
                    <div class="vehicle-info">
                        <img class="vehicle-logo" src="" alt="Car manufacturer logo">
                        <h2 class="vehicle-title"></h2>
                        <p class="vehicle-subtitle"></p>
                    </div>
                    <div class="add-to-cart-section">
                        <button id="add-to-cart-btn" class="add-to-cart-button" data-product-id="<?php echo $product_id; ?>">
                            Legg til i handlekurv
                        </button>
                    </div>
                </div>

                <div class="vehicle-content">
                    <!-- Basic Information Tab -->
                    <div class="accordion-section" data-section="basic">
                        <div class="accordion-header">
                            <h3>Grunnleggende informasjon</h3>
                            <span class="accordion-toggle">▼</span>
                        </div>
                        <div class="accordion-content">
                            <table class="basic-info-table"></table>
                        </div>
                    </div>

                    <!-- Registration and Control Tab -->
                    <div class="accordion-section" data-section="eu">
                        <div class="accordion-header">
                            <h3>Reg. og kontroll</h3>
                            <span class="accordion-toggle">▼</span>
                        </div>
                        <div class="accordion-content">
                            <table class="reg-control-table"></table>
                        </div>
                    </div>

                    <!-- Engine and Drivetrain Tab -->
                    <div class="accordion-section" data-section="engine">
                        <div class="accordion-header">
                            <h3>Motor og drivverk</h3>
                            <span class="accordion-toggle">▼</span>
                        </div>
                        <div class="accordion-content">
                            <table class="engine-drivetrain-table"></table>
                        </div>
                    </div>

                    <!-- Tires and Wheels Tab -->
                    <div class="accordion-section" data-section="tires">
                        <div class="accordion-header">
                            <h3>Dekk og felger</h3>
                            <span class="accordion-toggle">▼</span>
                        </div>
                        <div class="accordion-content">
                            <table class="tires-wheels-table"></table>
                        </div>
                    </div>

                    <!-- Size and Weight Tab -->
                    <div class="accordion-section" data-section="weight">
                        <div class="accordion-header">
                            <h3>Mål og vekt</h3>
                            <span class="accordion-toggle">▼</span>
                        </div>
                        <div class="accordion-content">
                            <table class="size-weight-table"></table>
                        </div>
                    </div>

                    <!-- Additional Notes Tab -->
                    <div class="accordion-section" data-section="notes">
                        <div class="accordion-header">
                            <h3>Tilleggsopplysninger</h3>
                            <span class="accordion-toggle">▼</span>
                        </div>
                        <div class="accordion-content">
                            <div class="additional-notes"></div>
                        </div>
                    </div>
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
        if (preg_match('/\/sok\/([A-Za-z0-9]+)/', $request_uri, $matches)) {
            return strtoupper(sanitize_text_field($matches[1]));
        }
        
        return '';
    }
}
