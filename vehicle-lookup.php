<?php
/*
Plugin Name: Beepi Vehicle Lookup
Description: A plugin to lookup Norwegian vehicle information using registration numbers
Version: 2.1.1
Author: Beepi
Author URI: https://beepi.no
*/

define('VEHICLE_LOOKUP_PLUGIN_DIR', dirname(__FILE__) . '/');
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://lookup.beepi.no');
define('VEHICLE_LOOKUP_VERSION', '2.1.1');

/**
 * Add rewrite rules for vehicle lookup URLs
 */
function vehicle_lookup_add_rewrite_rules() {
    add_rewrite_rule(
        'sok/([A-Za-z0-9]+)/?$',
        'index.php?pagename=sok&reg_number=$matches[1]',
        'top'
    );
}
add_action('init', 'vehicle_lookup_add_rewrite_rules');

/**
 * Add custom query vars to WordPress
 */
function vehicle_lookup_add_query_vars($vars) {
    $vars[] = 'reg_number';
    return $vars;
}
add_filter('query_vars', 'vehicle_lookup_add_query_vars');

/**
 * Flush rewrite rules on plugin activation
 */
function vehicle_lookup_activate() {
    vehicle_lookup_add_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'vehicle_lookup_activate');

/**
 * Clean up on plugin deactivation
 */
function vehicle_lookup_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'vehicle_lookup_deactivate');

require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-shortcode.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-order-confirmation-shortcode.php';

$order_confirmation = new Order_Confirmation_Shortcode();
$order_confirmation->init();

/**
 * Prevent redirect loops with registration number
 */
function fix_sok_redirect_loop() {
    // Check if we're on a /sok/ page with ?regNumber in the URL
    $uri = $_SERVER['REQUEST_URI'];
    if (strpos($uri, '/sok/') !== false && isset($_GET['regNumber'])) {
        // Check if the URL already contains the registration number in the path
        if (preg_match('#/sok/([A-Za-z0-9]+)/#i', $uri, $matches)) {
            $reg_in_path = $matches[1];
            $reg_in_query = $_GET['regNumber'];
            
            // If the registration number is duplicated, remove the query parameter
            if (strtoupper($reg_in_path) === strtoupper($reg_in_query)) {
                $clean_url = strtok($uri, '?');
                wp_safe_redirect($clean_url, 301);
                exit;
            }
        }
    }
}
add_action('template_redirect', 'fix_sok_redirect_loop');

$vehicle_lookup = new Vehicle_Lookup();
$vehicle_lookup->init();
