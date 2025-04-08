
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

define('VEHICLE_LOOKUP_PLUGIN_DIR', dirname(__FILE__) . '/');
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://vehicle-lookup.beepi.workers.dev');
define('VEHICLE_LOOKUP_VERSION', '1.0.0');

require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-shortcode.php';

// Initialize the plugin
$vehicle_lookup = new Vehicle_Lookup();
$vehicle_lookup->init();
