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
        $test_reg_number = Vehicle_Lookup_Helpers::get_test_reg_number();
        
        ob_start();
        ?>
        <div class="vehicle-search-container">
            <form id="eu-search-form" class="plate-form" method="GET" action="<?php echo $results_page; ?>">
                <div class="plate-input-wrapper">
                    <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                    <input type="text" id="euSearchRegNumber" name="reg" required
                           class="plate-input"
                           placeholder="REGNR">
                    <button type="submit" class="plate-search-button" aria-label="EU Search">
                        <span class="button-text"><?php echo esc_html($atts['button_text']); ?></span>
                    </button>
                </div>
                <div id="eu-search-error" class="error-message" style="display: none;"></div>
            </form>
            <div class="try-with-container">
                <span class="try-with-text">Prøv med:</span>
                <button type="button" class="try-with-button" id="try-with-btn-eu" data-reg-number="<?php echo esc_attr($test_reg_number); ?>"><?php echo esc_html($test_reg_number); ?></button>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // normalizePlate and validateRegistrationNumber are provided globally

            // Handle try-with button click - scoped to this form's button
            $('#try-with-btn-eu').on('click', function() {
                const regNumber = $(this).data('reg-number');
                $('#euSearchRegNumber').val(regNumber);
                
                // Submit the form immediately
                const baseUrl = '<?php echo $results_page; ?>';
                window.location.href = baseUrl + '/' + regNumber + '#EU';
            });

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
