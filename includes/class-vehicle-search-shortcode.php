<?php
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-search-base.php';

class Vehicle_Search_Shortcode extends Vehicle_Search_Base {

    protected function get_shortcode_name() {
        return 'vehicle_search';
    }

    protected function get_fragment() {
        return ''; // No fragment for basic search
    }

    protected function get_default_button_text() {
        return 'Søk';
    }
}