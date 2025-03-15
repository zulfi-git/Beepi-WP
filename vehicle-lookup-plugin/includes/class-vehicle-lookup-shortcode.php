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
                           minlength="17" maxlength="17" 
                           pattern="[A-HJ-NPR-Z0-9]{17}"
                           placeholder="17-character Registration Number">
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
