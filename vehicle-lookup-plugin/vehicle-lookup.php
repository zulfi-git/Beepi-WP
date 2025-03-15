<?php
/**
 * Plugin Name: Beepi CF Connector
 * Plugin URI: https://beepi.no
 * Description: A WordPress plugin that connects to BeepiWorker Cloudflare Worker for vehicle information lookup.
 * Version: 1.0.0
 * Author: Zulfiqar Haidari
 * Author URI: https://beepi.no
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vehicle-lookup
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VEHICLE_LOOKUP_VERSION', '1.0.0');
define('VEHICLE_LOOKUP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://beepi.zhaiden.workers.dev');

// Include required files
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-shortcode.php';

// Initialize the plugin
function vehicle_lookup_init() {
    $plugin = new Vehicle_Lookup();
    $plugin->init();
}
add_action('plugins_loaded', 'vehicle_lookup_init');