<?php
class Vehicle_Search_Shortcode {
    public function init() {
        add_shortcode('vehicle_search', array($this, 'render_search_shortcode'));
    }

    public function render_search_shortcode($atts) {
        // Extract attributes with defaults
        $atts = shortcode_atts(array(
            'results_page' => '/sok', // Default results page path
            'button_text' => 'Søk', // Default button text
        ), $atts);

        $results_page = esc_url($atts['results_page']);
        
        ob_start();
        ?>
        <div class="vehicle-search-container">
            <form id="vehicle-search-form" class="plate-form" method="GET" action="<?php echo $results_page; ?>">
                <div class="plate-input-wrapper">
                    <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                    <input type="text" id="searchRegNumber" name="reg" required
                           class="plate-input"
                           placeholder="CO11204">
                    <button type="submit" class="plate-search-button" aria-label="Search">
                        <span class="button-text"><?php echo esc_html($atts['button_text']); ?></span>
                    </button>
                </div>
                <div id="search-error" class="error-message" style="display: none;"></div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // normalizePlate and validateRegistrationNumber are provided globally

            $('#vehicle-search-form').on('submit', function(e) {
                const regNumber = normalizePlate($('#searchRegNumber').val());
                const errorDiv = $('#search-error');
                
                // Reset error state
                errorDiv.hide().empty();
                
                // Validate using minimal client-side validation
                const validation = validateRegistrationNumber(regNumber);
                if (!validation.valid) {
                    e.preventDefault();
                    errorDiv.html(validation.error).show();
                    return false;
                }
                
                // Update the form action to include the registration number in the path
                const baseUrl = '<?php echo $results_page; ?>';
                this.action = baseUrl + '/' + regNumber;
                
                // Remove the reg parameter since we're using path-based routing
                $('#searchRegNumber').prop('name', '');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
