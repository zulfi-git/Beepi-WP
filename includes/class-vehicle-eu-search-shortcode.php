<?php
class Vehicle_EU_Search_Shortcode {
    public function init() {
        add_shortcode('eu_search', array($this, 'render_eu_search_shortcode'));
    }

    public function render_eu_search_shortcode($atts) {
        // Extract attributes with defaults
        $atts = shortcode_atts(array(
            'results_page' => '/sok', // Default results page path
            'button_text' => 'Søk', // Default button text
        ), $atts);

        $results_page = esc_url($atts['results_page']);
        
        ob_start();
        ?>
        <div class="vehicle-search-container">
            <form id="eu-search-form" class="plate-form" method="GET" action="<?php echo $results_page; ?>">
                <div class="plate-input-wrapper">
                    <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                    <input type="text" id="euSearchRegNumber" name="reg" required
                           class="plate-input"
                           placeholder="CO11204">
                    <button type="submit" class="plate-search-button" aria-label="EU Search">
                        <span class="button-text"><?php echo esc_html($atts['button_text']); ?></span>
                    </button>
                </div>
                <div id="eu-search-error" class="error-message" style="display: none;"></div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // normalizePlate is provided globally by normalize-plate.js

            // Minimal validation function (matches vehicle-lookup.js approach)
            function validateRegistrationNumber(regNumber) {
                // Check if empty
                if (!regNumber || regNumber.trim() === '') {
                    return {
                        valid: false,
                        error: 'Registreringsnummer kan ikke være tomt'
                    };
                }

                // Check for invalid characters (only A-Z, ÆØÅ and digits 0-9)
                // Personalized Norwegian plates can contain ÆØÅ (e.g., "LØØL")
                const invalidChars = /[^A-ZÆØÅ0-9]/;
                if (invalidChars.test(regNumber)) {
                    return {
                        valid: false,
                        error: 'Registreringsnummer kan kun inneholde norske bokstaver (A-Z, ÆØÅ) og tall (0-9)'
                    };
                }

                // Check max length (7 characters)
                if (regNumber.length > 7) {
                    return {
                        valid: false,
                        error: 'Registreringsnummer kan ikke være lengre enn 7 tegn'
                    };
                }

                // All basic checks passed
                return {
                    valid: true,
                    error: null
                };
            }

            $('#eu-search-form').on('submit', function(e) {
                const regNumber = normalizePlate($('#euSearchRegNumber').val());
                const errorDiv = $('#eu-search-error');
                
                // Reset error state
                errorDiv.hide().empty();
                
                // Validate using minimal client-side validation
                const validation = validateRegistrationNumber(regNumber);
                if (!validation.valid) {
                    e.preventDefault();
                    errorDiv.html(validation.error).show();
                    return false;
                }
                
                // Update the form action to include the registration number in the path with EU anchor
                const baseUrl = '<?php echo $results_page; ?>';
                this.action = baseUrl + '/' + regNumber + '#EU';
                
                // Remove the reg parameter since we're using path-based routing
                $('#euSearchRegNumber').prop('name', '');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
