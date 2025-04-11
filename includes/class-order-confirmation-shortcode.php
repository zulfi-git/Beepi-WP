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
