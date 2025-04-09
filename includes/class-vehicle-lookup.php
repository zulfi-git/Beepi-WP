<?php
class Vehicle_Lookup {
    public function init() {
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_thankyou', array($this, 'handle_successful_payment'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_registration_to_order'));
        add_action('init', array($this, 'install'));
        add_action('wp_ajax_get_product_price', array($this, 'get_product_price'));
        add_action('wp_ajax_nopriv_get_product_price', array($this, 'get_product_price'));

        // Initialize shortcode
        $shortcode = new Vehicle_Lookup_Shortcode();
        $shortcode->init();

        // Register AJAX handlers
        add_action('wp_ajax_vehicle_lookup', array($this, 'handle_lookup'));
        add_action('wp_ajax_nopriv_vehicle_lookup', array($this, 'handle_lookup'));

        // Owner info page template
        add_filter('template_include', array($this, 'owner_info_template'));
    }

    public function get_product_price() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Product not found');
        }

        wp_send_json_success(array(
            'price' => strip_tags(wc_price($product->get_price()))
        ));
    }

    public function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_owner_tokens';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            registration_number varchar(10) NOT NULL,
            token varchar(64) NOT NULL,
            order_id bigint(20) NOT NULL,
            expiration_time datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY registration_number (registration_number),
            KEY expiration_time (expiration_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function save_registration_to_order($order_id) {
        if (!isset($_POST['registration'])) {
            return;
        }

        $registration = sanitize_text_field($_POST['registration']);
        update_post_meta($order_id, 'vehicle_registration', $registration);
    }

    public function owner_info_template($template) {
        if (is_page('owner-info')) {
            $new_template = VEHICLE_LOOKUP_PLUGIN_DIR . 'templates/owner-info.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    private function generate_secure_token($length = 32) {
        return bin2hex(random_bytes($length));
    }

    public function handle_successful_payment($order_id) {
        $order = wc_get_order($order_id);
        $registration = $order->get_meta('vehicle_registration');

        if (!$registration) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_owner_tokens';

        $token = $this->generate_secure_token();
        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $wpdb->insert($table_name, [
            'registration_number' => $registration,
            'token' => $token,
            'order_id' => $order_id,
            'expiration_time' => $expiration
        ]);

        wp_redirect(add_query_arg('token', $token, get_permalink(586)));
        exit;
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'vehicle-lookup-style',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/css/vehicle-lookup.css',
            array(),
            VEHICLE_LOOKUP_VERSION
        );

        wp_enqueue_script(
            'vehicle-lookup-script',
            VEHICLE_LOOKUP_PLUGIN_URL . 'assets/js/vehicle-lookup.js',
            array('jquery'),
            VEHICLE_LOOKUP_VERSION,
            true
        );

        wp_localize_script(
            'vehicle-lookup-script',
            'vehicleLookupAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vehicle_lookup_nonce')
            )
        );
    }

    public function handle_lookup() {
        check_ajax_referer('vehicle_lookup_nonce', 'nonce');

        $regNumber = isset($_POST['regNumber']) ? sanitize_text_field($_POST['regNumber']) : '';
        if (empty($regNumber)) {
            wp_send_json_error('Registration number is required');
        }

        // Add API call here
        $response = wp_remote_post(VEHICLE_LOOKUP_WORKER_URL, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array('registrationNumber' => $regNumber)),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            error_log('Vehicle lookup error: ' . $response->get_error_message());
            wp_send_json_error('Connection error occurred');
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('Vehicle lookup error: Server returned ' . $status_code);
            wp_send_json_error('Server error occurred');
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Vehicle lookup error: Invalid JSON response');
            wp_send_json_error('Invalid response from server');
        }

        wp_send_json_success($data);
    }
    public function validate_token($token) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_owner_tokens';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE token = %s AND expiration_time > NOW()",
            $token
        ));
        
        return $result;
    }
}