<?php
if (!defined('ABSPATH')) {
    exit;
}

// AJAX handler for vehicle lookup
function beepi_handle_vehicle_lookup() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'beepi_vehicle_lookup_nonce')) {
        wp_send_json_error(array(
            'message' => 'Security check failed'
        ), 403);
    }

    // Validate registration number
    $registration = isset($_POST['registration']) ? sanitize_text_field($_POST['registration']) : '';
    
    if (empty($registration) || !preg_match('/^[A-Za-z0-9]{1,8}$/', $registration)) {
        wp_send_json_error(array(
            'message' => 'Invalid registration number format'
        ), 400);
    }

    // Forward request to Cloudflare Worker
    $worker_url = 'https://beepi.zhaiden.workers.dev';

    $response = wp_remote_post($worker_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'registrationNumber' => $registration
        )),
        'timeout' => 15
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => 'Failed to connect to vehicle information service'
        ), 500);
    }

    $body = wp_remote_retrieve_body($response);
    $status = wp_remote_retrieve_response_code($response);

    if ($status !== 200) {
        wp_send_json_error(array(
            'message' => 'Vehicle information service error'
        ), $status);
    }

    wp_send_json_success(json_decode($body));
}

add_action('wp_ajax_beepi_vehicle_lookup', 'beepi_handle_vehicle_lookup');
add_action('wp_ajax_nopriv_beepi_vehicle_lookup', 'beepi_handle_vehicle_lookup');