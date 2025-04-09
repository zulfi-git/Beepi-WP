
<?php
/**
 * Template Name: Vehicle Owner Information
 */

get_header();

global $wpdb;
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
$error_message = '';
$owner_data = null;

if (!$token) {
    $error_message = 'Invalid access token';
} else {
    $token_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vehicle_owner_tokens 
        WHERE token = %s AND expiration_time > NOW()",
        $token
    ));

    if (!$token_data) {
        $error_message = 'Access token is invalid or expired';
    } else {
        // Here we would make the API call to get owner information
        // Implementation depends on your API structure
        $owner_data = array(
            'registration' => $token_data->registration_number,
            'expiration' => $token_data->expiration_time,
        );
    }
}
?>

<div class="owner-info-container">
    <?php if ($error_message): ?>
        <div class="error-message">
            <?php echo esc_html($error_message); ?>
        </div>
    <?php elseif ($owner_data): ?>
        <div class="owner-info">
            <h2>Vehicle Owner Information</h2>
            <p class="access-note">This information is available for 24 hours from purchase.</p>
            <p class="expires-in">Access expires: <?php echo esc_html(date('Y-m-d H:i:s', strtotime($owner_data['expiration']))); ?></p>
            
            <!-- Owner information display here -->
            <div class="owner-details">
                <!-- API response will be displayed here -->
            </div>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
