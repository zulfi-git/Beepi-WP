<?php
/*
Plugin Name: Beepi Vehicle Lookup
Description: A plugin to lookup Norwegian vehicle information using registration numbers
Version: 2.1.0
Author: Beepi
Author URI: https://beepi.no
*/

define('VEHICLE_LOOKUP_PLUGIN_DIR', dirname(__FILE__) . '/');
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://lookup.beepi.no');
define('VEHICLE_LOOKUP_VERSION', '2.1.0');

// Enable error logging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

try {
    require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup.php';
    require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-shortcode.php';
    require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-order-confirmation-shortcode.php';
} catch (Exception $e) {
    error_log('Vehicle Lookup Plugin Error: ' . $e->getMessage());
    if (current_user_can('administrator')) {
        echo 'Plugin Error: ' . esc_html($e->getMessage());
    }
    return;
}

$order_confirmation = new Order_Confirmation_Shortcode();
$order_confirmation->init();

$vehicle_lookup = new Vehicle_Lookup();
$vehicle_lookup->init();
