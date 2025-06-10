<?php
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-search-base.php';

class Vehicle_Search_EU_Shortcode extends Vehicle_Search_Base {
    
    protected function get_shortcode_name() {
        return 'vehicle_search_eu';
    }
    
    protected function get_fragment() {
        return 'eu';
    }
    
    protected function get_default_button_text() {
        return 'Sjekk EU-kontroll';
    }
}
