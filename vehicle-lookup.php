<?php
/*
Plugin Name: Beepi Vehicle Lookup
Description: A plugin to lookup Norwegian vehicle information using registration numbers
Version: 7.0.9
Author: Beepi
Author URI: https://beepi.no
License: Proprietary
License URI: https://github.com/zulfi-git/Beepi-WP/blob/main/LICENSE

Copyright (c) 2025 Beepi.no - All Rights Reserved
Unauthorized copying of this file, via any medium is strictly prohibited.
This software is proprietary and confidential.
*/

define('VEHICLE_LOOKUP_PLUGIN_DIR', dirname(__FILE__) . '/');
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://lookup.beepi.no');
define('VEHICLE_LOOKUP_VERSION', '7.0.9');
define('VEHICLE_LOOKUP_RATE_LIMIT', 100); // per hour per IP

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if required files exist before including
$required_files = [
    'includes/class-vehicle-lookup-helpers.php',
    'includes/class-vehicle-lookup-database.php',
    'includes/class-vehicle-lookup-api.php',
    'includes/class-vehicle-lookup-access.php',
    'includes/class-vehicle-lookup-woocommerce.php',
    'includes/class-vehicle-lookup.php',
    'includes/class-vehicle-lookup-shortcode.php',
    'includes/class-vehicle-search-shortcode.php',
    'includes/class-vehicle-eu-search-shortcode.php',
    'includes/class-popular-vehicles-shortcode.php',
    'includes/class-order-confirmation-shortcode.php',
    'includes/class-sms-handler.php',
    // Admin classes (Phase 2 refactoring)
    'includes/admin/class-vehicle-lookup-admin-settings.php',
    'includes/admin/class-vehicle-lookup-admin-dashboard.php',
    'includes/admin/class-vehicle-lookup-admin-analytics.php',
    'includes/admin/class-vehicle-lookup-admin-ajax.php',
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

    if (class_exists('Popular_Vehicles_Shortcode')) {
        $popular_vehicles = new Popular_Vehicles_Shortcode();
        $popular_vehicles->init();
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

    // Register custom rewrite rules before flushing
    add_rewrite_rule(
        '^sok/([^/]+)/?$',
        'index.php?pagename=sok&reg_number=$matches[1]',
        'top'
    );

    // Flush rewrite rules to register custom routes
    flush_rewrite_rules();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'vehicle_lookup_deactivate');

function vehicle_lookup_deactivate() {
    // Flush rewrite rules to remove custom routes
    flush_rewrite_rules();
}

// Enqueue plugin assets with cache busting
function vehicle_lookup_enqueue_scripts() {
    // CSS files with dependencies and cache busting
    $css_files = array(
        'variables' => array('deps' => array()),
        'forms' => array('deps' => array('vehicle-lookup-variables')),
        'results' => array('deps' => array('vehicle-lookup-variables')),
        'ai-summary' => array('deps' => array('vehicle-lookup-variables')),
        'market' => array('deps' => array('vehicle-lookup-variables')),
        'buttons' => array('deps' => array('vehicle-lookup-variables')),
        'responsive' => array('deps' => array('vehicle-lookup-variables', 'vehicle-lookup-forms', 'vehicle-lookup-results'))
    );

    foreach ($css_files as $name => $config) {
        $file_path = VEHICLE_LOOKUP_PLUGIN_DIR . 'assets/css/' . $name . '.css';
        $version = file_exists($file_path) ? filemtime($file_path) : VEHICLE_LOOKUP_VERSION;
        wp_enqueue_style(
            'vehicle-lookup-' . $name,
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/' . $name . '.css',
            $config['deps'],
            $version
        );
    }

    // JavaScript files with cache busting
    $js_files = array(
        'normalize-plate' => array('deps' => array()),
        'vehicle-lookup' => array('deps' => array('jquery', 'vehicle-lookup-normalize-plate'))
    );

    foreach ($js_files as $name => $config) {
        $file_path = VEHICLE_LOOKUP_PLUGIN_DIR . 'assets/js/' . $name . '.js';
        $version = file_exists($file_path) ? filemtime($file_path) : VEHICLE_LOOKUP_VERSION;
        $handle = ($name === 'vehicle-lookup') ? 'vehicle-lookup' : 'vehicle-lookup-' . $name;
        wp_enqueue_script(
            $handle,
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/js/' . $name . '.js',
            $config['deps'],
            $version,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'vehicle_lookup_enqueue_scripts');