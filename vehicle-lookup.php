
<?php
/*
Plugin Name: Beepi Vehicle Lookup
Description: A plugin to lookup Norwegian vehicle information using registration numbers
Version: 1.1.0
Author: Beepi
Author URI: https://beepi.no
*/

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

define('VEHICLE_LOOKUP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://lookup.beepi.no');
define('VEHICLE_LOOKUP_VERSION', '1.1.0');

// Load core files
function vehicle_lookup_load_files() {
    require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup.php';
    require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-shortcode.php';
    require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-woocommerce.php';
}

// Initialize plugin
function vehicle_lookup_init() {
    try {
        vehicle_lookup_load_files();
        
        $vehicle_lookup = new Vehicle_Lookup();
        $vehicle_lookup->init();

        if (class_exists('WooCommerce')) {
            $vehicle_lookup_wc = new Vehicle_Lookup_WooCommerce();
            $vehicle_lookup_wc->init();
        }
    } catch (Exception $e) {
        error_log('Vehicle Lookup Plugin Error: ' . $e->getMessage());
        add_action('admin_notices', 'vehicle_lookup_admin_error_notice');
    }
}

// Admin error notice
function vehicle_lookup_admin_error_notice() {
    ?>
    <div class="notice notice-error">
        <p>Vehicle Lookup Plugin Error: Please check the error logs for details.</p>
    </div>
    <?php
}

// Hook into WordPress init
add_action('plugins_loaded', 'vehicle_lookup_init');
