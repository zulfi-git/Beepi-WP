<?php

/**
 * Vehicle Lookup Admin Class
 * 
 * Core coordinator class that manages WordPress admin integration
 * and delegates to specialized sub-classes.
 * 
 * Phase 2 Refactoring: This class now delegates to:
 * - Vehicle_Lookup_Admin_Settings - Settings management
 * - Vehicle_Lookup_Admin_Dashboard - Dashboard rendering and metrics
 * - Vehicle_Lookup_Admin_Analytics - Analytics page
 * - Vehicle_Lookup_Admin_Ajax - AJAX handlers
 */
class Vehicle_Lookup_Admin {

    private $settings;
    private $dashboard;
    private $analytics;
    private $ajax;

    public function init() {
        // Initialize sub-classes
        $this->settings = new Vehicle_Lookup_Admin_Settings();
        $this->dashboard = new Vehicle_Lookup_Admin_Dashboard();
        $this->analytics = new Vehicle_Lookup_Admin_Analytics();
        $this->ajax = new Vehicle_Lookup_Admin_Ajax();

        // Register WordPress hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this->settings, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Initialize AJAX handlers
        $this->ajax->register_handlers();

        // Ensure database table exists
        $this->ensure_database_table();
    }

    private function ensure_database_table() {
        // create_table() is idempotent and handles both new installations and schema upgrades
        $db_handler = new Vehicle_Lookup_Database();
        $db_handler->create_table();
    }

    /**
     * Register admin menu pages and delegate to sub-classes
     */
    public function add_admin_menu() {
        add_menu_page(
            'Vehicle Lookup',
            'Vehicle Lookup',
            'manage_options',
            'vehicle-lookup',
            array($this->dashboard, 'render'),
            'dashicons-car',
            30
        );

        add_submenu_page(
            'vehicle-lookup',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'vehicle-lookup',
            array($this->dashboard, 'render')
        );

        add_submenu_page(
            'vehicle-lookup',
            'Settings',
            'Settings',
            'manage_options',
            'vehicle-lookup-settings',
            array($this->settings, 'render')
        );

        add_submenu_page(
            'vehicle-lookup',
            'Analytics',
            'Analytics',
            'manage_options',
            'vehicle-lookup-analytics',
            array($this->analytics, 'render')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Load on all vehicle lookup admin pages
        if (strpos($hook, 'vehicle-lookup') === false) {
            return;
        }

        // Dynamic cache busting using file modification time
        $css_file = VEHICLE_LOOKUP_PLUGIN_DIR . 'assets/css/admin.css';
        $js_file = VEHICLE_LOOKUP_PLUGIN_DIR . 'assets/js/admin.js';
        
        $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
        $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

        wp_enqueue_style(
            'vehicle-lookup-admin-style',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $css_version
        );

        wp_enqueue_script(
            'vehicle-lookup-admin-script',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $js_version,
            true
        );

        wp_localize_script('vehicle-lookup-admin-script', 'vehicleLookupAdmin', array(
            'nonce' => wp_create_nonce('vehicle_lookup_admin_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }
}