<?php
/**
 * Plugin Name: Beepi Vehicle Lookup
 * Plugin URI: https://beepi.no
 * Description: A WordPress plugin for vehicle information lookup using Cloudflare Worker integration
 * Version: 1.0.0
 * Author: Beepi
 * Author URI: https://beepi.no
 * Text Domain: beepi-vehicle-lookup
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('BEEPI_VEHICLE_LOOKUP_VERSION', '1.0.0');
define('BEEPI_VEHICLE_LOOKUP_PATH', plugin_dir_path(__FILE__));
define('BEEPI_VEHICLE_LOOKUP_URL', plugin_dir_url(__FILE__));

// Include required files
require_once BEEPI_VEHICLE_LOOKUP_PATH . 'includes/frontend-rendering.php';
require_once BEEPI_VEHICLE_LOOKUP_PATH . 'includes/ajax-handler.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'beepi_vehicle_lookup_activate');

function beepi_vehicle_lookup_activate() {
    // Future activation tasks like creating tables can go here
    flush_rewrite_rules();
}

// Enqueue scripts and styles
function beepi_vehicle_lookup_enqueue_scripts() {
    // Bootstrap CSS from CDN
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
        array(),
        '5.1.3'
    );

    // Plugin CSS
    wp_enqueue_style(
        'beepi-vehicle-lookup',
        BEEPI_VEHICLE_LOOKUP_URL . 'css/vehicle-lookup.css',
        array('bootstrap'),
        BEEPI_VEHICLE_LOOKUP_VERSION
    );

    // jQuery is already included with WordPress
    wp_enqueue_script(
        'beepi-vehicle-lookup',
        BEEPI_VEHICLE_LOOKUP_URL . 'js/vehicle-lookup.js',
        array('jquery'),
        BEEPI_VEHICLE_LOOKUP_VERSION,
        true
    );

    // Pass variables to JavaScript
    wp_localize_script('beepi-vehicle-lookup', 'beepiConfig', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'cloudflareWorkerEndpoint' => 'https://beepi.zhaiden.workers.dev',
        'nonce' => wp_create_nonce('beepi_vehicle_lookup_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'beepi_vehicle_lookup_enqueue_scripts');

// Register shortcode
add_shortcode('vehicle_search', 'beepi_vehicle_search_shortcode');
?>