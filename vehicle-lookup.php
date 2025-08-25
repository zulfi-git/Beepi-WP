<?php
/*
Plugin Name: Beepi Vehicle Lookup
Description: A plugin to lookup Norwegian vehicle information using registration numbers
Version: 3.1.0
Author: Beepi
Author URI: https://beepi.no
*/

define('VEHICLE_LOOKUP_PLUGIN_DIR', dirname(__FILE__) . '/');
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://lookup.beepi.no');
define('VEHICLE_LOOKUP_VERSION', '3.1.0');
define('VEHICLE_LOOKUP_RATE_LIMIT', 100); // per hour per IP
define('VEHICLE_LOOKUP_CACHE_DURATION', 43200); // 12 hours

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if required files exist before including
$required_files = [
    'includes/class-vehicle-lookup-helpers.php',
    'includes/class-vehicle-lookup-database.php',
    'includes/class-vehicle-lookup-api.php',
    'includes/class-vehicle-lookup-cache.php',
    'includes/class-vehicle-lookup-access.php',
    'includes/class-vehicle-lookup-woocommerce.php',
    'includes/class-vehicle-lookup.php',
    'includes/class-vehicle-lookup-shortcode.php',
    'includes/class-vehicle-search-shortcode.php',
    'includes/class-vehicle-eu-search-shortcode.php',
    'includes/class-order-confirmation-shortcode.php',
    'includes/class-sms-handler.php',
    'includes/class-vehicle-lookup-admin.php'
];

foreach ($required_files as $file) {
    $file_path = VEHICLE_LOOKUP_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log("Vehicle Lookup Plugin: Missing file - {$file_path}");
    }
}

// Initialize classes with error handling
try {
    if (class_exists('Vehicle_Lookup')) {
        $vehicle_lookup = new Vehicle_Lookup();
        $vehicle_lookup->init();
    }

    if (class_exists('Vehicle_Search_Shortcode')) {
        $vehicle_search = new Vehicle_Search_Shortcode();
        $vehicle_search->init();
    }

    if (class_exists('Vehicle_EU_Search_Shortcode')) {
        $eu_search = new Vehicle_EU_Search_Shortcode();
        $eu_search->init();
    }

    if (class_exists('Order_Confirmation_Shortcode')) {
        $order_confirmation = new Order_Confirmation_Shortcode();
        $order_confirmation->init();
    }

    if (class_exists('SMS_Handler')) {
        $sms_handler = new SMS_Handler();
        $sms_handler->init();
    }

    // Initialize admin interface
    if (is_admin() && class_exists('Vehicle_Lookup_Admin')) {
        $admin = new Vehicle_Lookup_Admin();
        $admin->init();
    }
} catch (Exception $e) {
    error_log("Vehicle Lookup Plugin Error: " . $e->getMessage());
}

// Plugin activation hook
register_activation_hook(__FILE__, 'vehicle_lookup_activate');

function vehicle_lookup_activate() {
    if (class_exists('Vehicle_Lookup_Database')) {
        $db_handler = new Vehicle_Lookup_Database();
        $db_handler->create_table();
    }
}
