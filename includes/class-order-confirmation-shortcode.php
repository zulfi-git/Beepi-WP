<?php
class Order_Confirmation_Shortcode {
    public function init() {
        add_shortcode('order_confirmation', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $order_id = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : '';
        $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
        $reg_number = isset($_GET['custom_reg']) ? sanitize_text_field($_GET['custom_reg']) : '';

        if (empty($order_id) || empty($order_key)) {
            return '<p>Invalid order information.</p>';
        }

        // Verify order exists and is valid
        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            return '<p>Invalid order information.</p>';
        }

        // Create transient for owner access
        $transient_key = 'owner_access_' . $reg_number;
        $expiry = 24 * HOUR_IN_SECONDS; // 24 hours
        set_transient($transient_key, true, $expiry);

        ob_start();
        ?>
        <div class="order-confirmation-container">
            <h2>Order Confirmation</h2>
            <p>Your payment has been processed successfully.</p>
            <p>Registration number: <strong><?php echo esc_html($reg_number); ?></strong></p>
            <p>Click below to view the owner information:</p>
            <a href="/#" class="view-info-button" onclick="window.location.reload()">View Owner Information</a>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
