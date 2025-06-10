
<?php
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-search-base.php';

class Vehicle_Search_Tires_Shortcode extends Vehicle_Search_Base {
    
    protected function get_shortcode_name() {
        return 'vehicle_search_tires';
    }
    
    protected function get_fragment() {
        return 'tires';
    }
    
    protected function get_default_button_text() {
        return 'Sjekk dekk og felg';
    }
}
