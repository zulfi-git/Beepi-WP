
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
        $response = wp_remote_post(VEHICLE_LOOKUP_WORKER_URL . '/owner', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'registrationNumber' => $token_data->registration_number
            )),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            error_log('Owner lookup error: ' . $response->get_error_message());
            $error_message = 'Failed to fetch owner information';
        } else {
            $body = wp_remote_retrieve_body($response);
            $owner_data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Owner lookup error: Invalid JSON response');
                $error_message = 'Invalid response from server';
            }
        }
    }
}

$expiration_time = isset($token_data->expiration_time) ? strtotime($token_data->expiration_time) : 0;
$time_remaining = $expiration_time - time();
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
            
            <?php if ($time_remaining > 0): ?>
                <p class="expires-in">Access expires in: 
                    <span class="countdown" data-expires="<?php echo esc_attr($expiration_time); ?>">
                        <?php echo esc_html(human_time_diff(time(), $expiration_time)); ?>
                    </span>
                </p>
            <?php endif; ?>
            
            <div class="owner-details">
                <?php foreach ($owner_data as $key => $value): ?>
                    <div class="owner-detail-item">
                        <strong><?php echo esc_html(ucfirst($key)); ?>:</strong>
                        <span><?php echo esc_html($value); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateCountdown() {
    const countdownElement = document.querySelector('.countdown');
    if (!countdownElement) return;
    
    const expirationTime = parseInt(countdownElement.dataset.expires, 10) * 1000;
    const now = new Date().getTime();
    const timeLeft = expirationTime - now;
    
    if (timeLeft <= 0) {
        location.reload();
        return;
    }
    
    const hours = Math.floor(timeLeft / (1000 * 60 * 60));
    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
    
    countdownElement.textContent = `${hours}h ${minutes}m ${seconds}s`;
}

if (document.querySelector('.countdown')) {
    updateCountdown();
    setInterval(updateCountdown, 1000);
}
</script>

<?php get_footer(); ?>
