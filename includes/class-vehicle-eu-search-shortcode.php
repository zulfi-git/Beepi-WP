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
        <div class="max-w-full mx-auto px-4">
            <form id="eu-search-form" class="flex justify-center mx-auto my-4" method="GET" action="<?php echo $results_page; ?>">
                <div class="flex items-center bg-white overflow-hidden shadow-md border border-slate-200 rounded-lg">
                    <div class="bg-sky-500 text-white text-3xl px-3 py-2 flex flex-col items-center justify-center h-full rounded-l-lg">
                        <span>🇳🇴</span>
                        <span class="text-xs -mt-1">N</span>
                    </div>
                    <input type="text" id="euSearchRegNumber" name="reg" required
                           class="border-0 text-3xl px-3 py-2 uppercase tracking-wider w-36 text-center outline-none font-semibold text-slate-900"
                           placeholder="CO11204"
                           pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 border-0 text-white px-6 py-3 text-base font-semibold cursor-pointer flex items-center justify-center transition-all duration-200 ease-in-out h-full rounded-r-lg hover:-translate-y-px relative" aria-label="EU Search">
                        <span class="transition-opacity duration-200"><?php echo esc_html($atts['button_text']); ?></span>
                    </button>
                </div>
            </form>
            <div id="eu-search-error" class="text-red-600 text-center mt-2 font-medium" style="display: none;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // normalizePlate is provided globally by normalize-plate.js

            $('#eu-search-form').on('submit', function(e) {
                const regNumber = normalizePlate($('#euSearchRegNumber').val());
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
