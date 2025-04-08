<?php
class Vehicle_Lookup_Shortcode {
    /**
     * Initialize the shortcode
     */
    public function init() {
        add_shortcode('vehicle_lookup', array($this, 'render_shortcode'));
    }

    /**
     * Render the vehicle lookup form
     */
    public function render_shortcode($atts) {
        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <form id="vehicle-lookup-form" class="vehicle-lookup-form">
                <div class="form-group">
                    <label for="regNumber">Enter Registration Number:</label>
                    <input type="text" id="regNumber" name="regNumber" required 
                           minlength="6" maxlength="7" 
                           pattern="([A-Z]{2}\d{4,5}|E[KLVBCDE]\d{5}|CD\d{5}|\d{5}|[A-Z]\d{3}|[A-Z]{2}\d{3})"
                           placeholder="Norwegian Registration Number (e.g., AB12345)">
                </div>
                <button type="submit" class="lookup-button">
                    Look Up Vehicle
                </button>
            </form>
            
            <div id="vehicle-lookup-results" class="vehicle-lookup-results" style="display: none;">
                <h3>Vehicle Information</h3>
                <div class="results-content"></div>
            </div>
            
            <div id="vehicle-lookup-error" class="vehicle-lookup-error" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
