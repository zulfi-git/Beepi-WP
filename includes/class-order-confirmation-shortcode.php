<?php
class Order_Confirmation_Shortcode {
    public function init() {
        add_shortcode('order_confirmation', array($this, 'render_shortcode'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_complete'));
    }

    public function handle_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        $reg_number = $order->get_meta('custom_reg') ?: $order->get_meta('reg_number');

        if (empty($reg_number)) {
            error_log('Payment complete but no registration number found for order: ' . $order_id);
            return;
        }

        if ($reg_number && $this->validate_order_has_lookup($order)) {
            $transient_key = 'owner_access_' . $reg_number;
            set_transient($transient_key, true, 24 * HOUR_IN_SECONDS);
        }
    }

    public function handle_order_complete($order_id) {
        $this->handle_payment_complete($order_id);
    }

    private function validate_order_has_lookup($order) {
        $lookup_product_id = 62;

        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $lookup_product_id) {
                return true;
            }
        }
        return false;
    }

    public function render_shortcode($atts) {
        $order_id = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : '';
        $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

        if (empty($order_id) || empty($order_key)) {
            return '<p>Invalid order information.</p>';
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            return '<p>Invalid order information.</p>';
        }

        if (!in_array($order->get_status(), ['completed', 'processing'])) {
            return '<p>Order payment not yet confirmed.</p>';
        }

        if (!$this->validate_order_has_lookup($order)) {
            return '<p>Order does not contain vehicle lookup product.</p>';
        }

        $reg_number = '';
        $reg_fields = ['custom_reg', 'reg_number', '_custom_reg', '_reg_number', 'regNumber'];

        foreach ($reg_fields as $field) {
            $reg_number = $order->get_meta($field);
            if (!empty($reg_number)) {
                break;
            }
        }

        if (empty($reg_number)) {
            return '<p>Ingen registreringsnummer funnet for denne ordren. Vennligst kontakt support.</p>';
        }

        $transient_key = 'owner_access_' . $reg_number;
        if (false === get_transient($transient_key)) {
            set_transient($transient_key, true, 24 * HOUR_IN_SECONDS);
        }

        ob_start();
        ?>
        <div class="vehicle-lookup-container order-confirmation-container">
            <div id="vehicle-lookup-results" class="results-wrapper">
                <div class="accordion">
                    <details open>
                        <summary>Eierinformasjon</summary>
                        <div class="details-content">
                            <table class="info-table owner-info-table"></table>
                        </div>
                    </details>
                </div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            const regNumber = '<?php echo esc_js(trim($reg_number)); ?>';

            $.ajax({
                url: vehicleLookupAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vehicle_lookup',
                    nonce: vehicleLookupAjax.nonce,
                    regNumber: regNumber
                },
                success: function(response) {
                    if (response.success && response.data && response.data.responser && response.data.responser[0]) {
                        const vehicleData = response.data.responser[0].kjoretoydata;
                        if (vehicleData.eierskap?.eier) {
                            const eier = vehicleData.eierskap.eier;
                            const person = eier.person;
                            const adresse = eier.adresse;

                            const ownerInfo = {
                                'Eier': person ? `${person.fornavn} ${person.etternavn}` : '',
                                'Adresse': adresse?.adresselinje1 || '',
                                'Postnummer': adresse?.postnummer || '',
                                'Poststed': adresse?.poststed || ''
                            };

                            $('.owner-info-table').html(
                                Object.entries(ownerInfo)
                                    .filter(([_, value]) => value)
                                    .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                                    .join('')
                            );
                        }
                    }
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
?>