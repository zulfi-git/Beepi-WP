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
                           placeholder="CO11204"
                           pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})">
                    <button type="submit" class="plate-search-button" aria-label="EU Search">
                        <span class="button-text"><?php echo esc_html($atts['button_text']); ?></span>
                    </button>
                </div>
                <div id="eu-search-error" class="error-message" style="display: none;"></div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#eu-search-form').on('submit', function(e) {
                const regNumber = $('#euSearchRegNumber').val().trim().toUpperCase();
                const errorDiv = $('#eu-search-error');
                
                // Reset error state
                errorDiv.hide().empty();
                
                // Validate Norwegian registration number
                const validFormats = [
                    /^[A-Z]{2}\d{4,5}$/,           // Standard vehicles
                    /^E[KLVBCDE]\d{5}$/,           // Electric vehicles
                    /^CD\d{5}$/,                   // Diplomatic vehicles
                    /^\d{5}$/,                     // Temporary tourist plates
                    /^[A-Z]\d{3}$/,               // Antique vehicles
                    /^[A-Z]{2}\d{3}$/             // Provisional plates
                ];
                
                const isValid = validFormats.some(format => format.test(regNumber));
                if (!regNumber || !isValid) {
                    e.preventDefault();
                    errorDiv.html('Vennligst skriv inn et gyldig norsk registreringsnummer').show();
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
