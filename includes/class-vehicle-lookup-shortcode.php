
<?php
class Vehicle_Lookup_Shortcode {
    public function init() {
        add_shortcode('vehicle_lookup', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <form id="vehicle-lookup-form" class="vehicle-lookup-form">
                <div class="form-group">
                    <label for="regNumber">Enter Registration Number:</label>
                    <input type="text" id="regNumber" name="regNumber" required 
                           placeholder="Registration Number"
                           class="reg-input">
                </div>
                <button type="submit" class="lookup-button">Look Up Vehicle</button>
            </form>
            
            <div id="vehicle-lookup-results" class="vehicle-results" style="display: none;">
                <div class="vehicle-header">
                    <h2 class="vehicle-title"></h2>
                    <p class="vehicle-subtitle"></p>
                </div>

                <div class="info-sections">
                    <section class="info-section" id="basic-info">
                        <h3>Vehicle Information</h3>
                        <div class="info-content"></div>
                    </section>

                    <section class="info-section" id="technical-info">
                        <h3>Technical Details</h3>
                        <div class="info-content"></div>
                    </section>

                    <section class="info-section" id="registration-info">
                        <h3>Registration Details</h3>
                        <div class="info-content"></div>
                    </section>
                </div>
            </div>
            
            <div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
