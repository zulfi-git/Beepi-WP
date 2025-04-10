<?php
/*
Plugin Name: Beepi Vehicle Lookup
Description: A plugin to lookup Norwegian vehicle information using registration numbers
Version: 1.1.0
Author: Beepi
Author URI: https://beepi.no
*/

define('VEHICLE_LOOKUP_PLUGIN_DIR', dirname(__FILE__) . '/');
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://lookup.beepi.no');
define('VEHICLE_LOOKUP_VERSION', '1.1.0');

require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-shortcode.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-woocommerce.php';

$vehicle_lookup = new Vehicle_Lookup();
$vehicle_lookup->init();

if (class_exists('WooCommerce')) {
    $vehicle_lookup_wc = new Vehicle_Lookup_WooCommerce();
    $vehicle_lookup_wc->init();
}
