<?php
abstract class Vehicle_Search_Base {
    
    protected $fragment = '';
    protected $default_button_text = 'Søk';

    public function init() {
        add_shortcode($this->get_shortcode_name(), array($this, 'render_search_shortcode'));
    }

    abstract protected function get_shortcode_name();
    abstract protected function get_fragment();
    abstract protected function get_default_button_text();

    public function render_search_shortcode($atts) {
        // Extract attributes with defaults
        $atts = shortcode_atts(array(
            'results_page' => '/sok',
            'button_text' => $this->get_default_button_text(),
            'placeholder' => 'CO11204',
        ), $atts);

        $results_page = esc_url($atts['results_page']);
        $button_text = esc_html($atts['button_text']);
        $placeholder = esc_attr($atts['placeholder']);
        $fragment = $this->get_fragment();

        ob_start();
        ?>
        <div class="vehicle-search-container">
            <form class="vehicle-search-form" onsubmit="return handleSearchSubmit(event, '<?php echo $results_page; ?>', '<?php echo $fragment; ?>')">
                <div class="plate-input-wrapper">
                    <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                    <input type="text" name="regNumber" required
                           class="plate-input"
                           placeholder="<?php echo $placeholder; ?>"
                           pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})">
                    <button type="submit" class="plate-search-button" aria-label="Search">
                        <span class="search-icon">🔍</span>
                        <span class="button-text"><?php echo $button_text; ?></span>
                    </button>
                </div>
            </form>
        </div>

        <script>
        function handleSearchSubmit(event, resultsPage, fragment) {
            event.preventDefault();
            const regNumber = event.target.regNumber.value.trim().toUpperCase();

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
                alert('Vennligst skriv inn et gyldig norsk registreringsnummer');
                return false;
            }

            if (regNumber) {
                const url = fragment ? 
                    `${resultsPage}/${regNumber}#${fragment}` : 
                    `${resultsPage}/${regNumber}`;
                window.location.href = url;
            }
            return false;
        }
        </script>

        <?php
        return ob_get_clean();
    }
}