<?php
/*
Plugin Name: Beepi Vehicle Lookup
Description: A plugin to lookup Norwegian vehicle information using registration numbers
Version: 3.0.0
Author: Beepi
Author URI: https://beepi.no
*/

define('VEHICLE_LOOKUP_PLUGIN_DIR', dirname(__FILE__) . '/');
define('VEHICLE_LOOKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VEHICLE_LOOKUP_WORKER_URL', 'https://lookup.beepi.no');
define('VEHICLE_LOOKUP_VERSION', '2.1.0');
define('VEHICLE_LOOKUP_RATE_LIMIT', 20); // per hour per IP
define('VEHICLE_LOOKUP_CACHE_DURATION', 43200); // 12 hours

require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-lookup-shortcode.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-search-base.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-search-shortcode.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-search-eu-shortcode.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-vehicle-search-tires-shortcode.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-order-confirmation-shortcode.php';
require_once VEHICLE_LOOKUP_PLUGIN_DIR . 'includes/class-sms-handler.php';

$order_confirmation = new Order_Confirmation_Shortcode();
$order_confirmation->init();

$vehicle_lookup = new Vehicle_Lookup();
$vehicle_lookup->init();

$vehicle_search_eu = new Vehicle_Search_EU_Shortcode();
$vehicle_search_eu->init();

$vehicle_search_tires = new Vehicle_Search_Tires_Shortcode();
$vehicle_search_tires->init();

$sms_handler = new SMS_Handler();
$sms_handler->init();
