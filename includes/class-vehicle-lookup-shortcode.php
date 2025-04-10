<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vehicle_Lookup_Shortcode {
    public function init() {
        add_shortcode('vehicle-lookup', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        ob_start();
        ?>
        <div class="vehicle-lookup-container">
            <form class="plate-form" id="vehicle-lookup-form">
                <div class="plate-input-wrapper">
                    <input type="text" class="plate-input" placeholder="AB12345" maxlength="7" />
                    <button type="submit" class="plate-search-button">
                        <span class="search-icon">🔍</span>
                        <div class="loading-spinner"></div>
                    </button>
                </div>
            </form>
            <div id="vehicle-results"></div>
            <div id="vehicle-lookup-error" class="error-message" style="display: none;"></div>
            <div id="quota-display" class="quota-display" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

?>