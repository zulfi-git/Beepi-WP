<?php

/**
 * Vehicle Lookup Admin Settings Class
 * 
 * Handles all settings-related functionality for the Vehicle Lookup plugin.
 * This class manages:
 * - WordPress settings registration
 * - Settings page rendering
 * - Field callbacks and validation
 */
class Vehicle_Lookup_Admin_Settings {

    /**
     * Register all plugin settings
     * Called by WordPress admin_init hook
     */
    public function init_settings() {
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_worker_url');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_timeout');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_rate_limit');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_cache_duration');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_cache_enabled', array(
            'sanitize_callback' => array($this, 'sanitize_cache_enabled')
        ));
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_daily_quota');
        register_setting('vehicle_lookup_settings', 'vehicle_lookup_log_retention');

        add_settings_section(
            'vehicle_lookup_api_section',
            'API Configuration',
            null,
            'vehicle_lookup_settings'
        );

        add_settings_section(
            'vehicle_lookup_limits_section',
            'Rate Limiting & Cache',
            null,
            'vehicle_lookup_settings'
        );

        add_settings_field(
            'worker_url',
            'Worker URL',
            array($this, 'worker_url_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_api_section'
        );

        add_settings_field(
            'timeout',
            'API Timeout (seconds)',
            array($this, 'timeout_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_api_section'
        );

        add_settings_field(
            'rate_limit',
            'Rate Limit (requests per hour per IP)',
            array($this, 'rate_limit_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );

        add_settings_field(
            'cache_duration',
            'Cache Duration (seconds)',
            array($this, 'cache_duration_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );

        add_settings_field(
            'cache_enabled',
            'Enable Cache',
            array($this, 'cache_enabled_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );

        add_settings_field(
            'daily_quota',
            'Daily Quota Limit',
            array($this, 'daily_quota_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );

        add_settings_field(
            'log_retention',
            'Log Retention (days)',
            array($this, 'log_retention_field'),
            'vehicle_lookup_settings',
            'vehicle_lookup_limits_section'
        );
    }

    /**
     * Render settings page
     */
    public function render() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('vehicle_lookup_messages', 'vehicle_lookup_message', 'Settings Saved', 'updated');
        }

        settings_errors('vehicle_lookup_messages');
        ?>
        <div class="wrap vehicle-lookup-admin">
            <h1><span class="dashicons dashicons-admin-settings"></span> Vehicle Lookup Settings</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('vehicle_lookup_settings');
                do_settings_sections('vehicle_lookup_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Worker URL field callback
     */
    public function worker_url_field() {
        $value = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        echo '<input type="url" name="vehicle_lookup_worker_url" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL for the vehicle lookup API worker</p>';
    }

    /**
     * Timeout field callback
     */
    public function timeout_field() {
        $value = get_option('vehicle_lookup_timeout', 15);
        echo '<input type="number" name="vehicle_lookup_timeout" value="' . esc_attr($value) . '" min="5" max="30" />';
        echo '<p class="description">API request timeout in seconds (5-30)</p>';
    }

    /**
     * Rate limit field callback
     */
    public function rate_limit_field() {
        $value = get_option('vehicle_lookup_rate_limit', VEHICLE_LOOKUP_RATE_LIMIT);
        echo '<input type="number" name="vehicle_lookup_rate_limit" value="' . esc_attr($value) . '" min="1" max="100" />';
        echo '<p class="description">Maximum requests allowed per hour per IP address</p>';
    }

    /**
     * Cache duration field callback
     */
    public function cache_duration_field() {
        $value = get_option('vehicle_lookup_cache_duration', VEHICLE_LOOKUP_CACHE_DURATION);
        $hours = $value / 3600;
        echo '<input type="number" name="vehicle_lookup_cache_duration" value="' . esc_attr($value) . '" min="3600" max="86400" />';
        echo '<p class="description">Cache duration in seconds (currently ' . $hours . ' hours)</p>';
    }

    /**
     * Cache enabled field callback
     */
    public function cache_enabled_field() {
        $value = get_option('vehicle_lookup_cache_enabled', '1');
        $checked = ($value === '1') ? 'checked' : '';
        echo '<label><input type="checkbox" name="vehicle_lookup_cache_enabled" value="1" ' . esc_attr($checked) . ' />';
        echo ' Enable caching</label>';
        echo '<p class="description">Disable cache for debugging purposes. <strong>Warning:</strong> Disabling cache will increase API usage and may impact performance.</p>';
    }

    /**
     * Sanitize cache enabled checkbox
     * WordPress checkboxes only send data when checked, so we need this callback
     * to handle the unchecked state
     */
    public function sanitize_cache_enabled($value) {
        // If checkbox is checked, $value will be '1'
        // If checkbox is unchecked, $value will be null (field not in POST)
        return ($value === '1') ? '1' : '0';
    }

    /**
     * Daily quota field callback
     */
    public function daily_quota_field() {
        $value = get_option('vehicle_lookup_daily_quota', 5000);
        echo '<input type="number" name="vehicle_lookup_daily_quota" value="' . esc_attr($value) . '" min="100" max="10000" />';
        echo '<p class="description">Maximum API calls allowed per day</p>';
    }

    /**
     * Log retention field callback
     */
    public function log_retention_field() {
        $value = get_option('vehicle_lookup_log_retention', 90);
        echo '<input type="number" name="vehicle_lookup_log_retention" value="' . esc_attr($value) . '" min="30" max="365" />';
        echo '<p class="description">Number of days to keep lookup logs (30-365)</p>';
    }
}
