
<?php
/*
Plugin Name: Vehicle Lookup
Description: A plugin to lookup vehicle information using registration numbers
Version: 1.0.0
Author: Your Name
*/

define('VEHICLE_LOOKUP_PLUGIN_DIR', dirname(__FILE__) . '/');
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://vehicle-lookup.beepi.workers.dev');
define('VEHICLE_LOOKUP_VERSION', '1.0.0');

require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-shortcode.php';

$vehicle_lookup = new Vehicle_Lookup();
$vehicle_lookup->init();
