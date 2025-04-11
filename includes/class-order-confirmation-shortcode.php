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
        $lookup_product_id = 62; // Hardcoded product ID from vehicle-lookup.js

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

        // Get registration number from WooCommerce order meta
        $reg_number = '';
        $reg_fields = ['custom_reg', 'reg_number', '_custom_reg', '_reg_number', 'regNumber'];
        
        // First try direct meta access
        foreach ($reg_fields as $field) {
            $reg_number = get_post_meta($order_id, $field, true);
            if (!empty($reg_number)) {
                error_log("Found registration number in post meta {$field}: {$reg_number}");
                break;
            }
        }
        
        // If not found, try WC meta
        if (empty($reg_number)) {
            foreach ($reg_fields as $field) {
                $reg_number = $order->get_meta($field);
                if (!empty($reg_number)) {
                    error_log("Found registration number in WC meta {$field}: {$reg_number}");
                    break;
                }
            }
        }

        // Enhanced debug logging with all possible data sources
        error_log("\n\n=== DEBUG: COMPLETE ORDER DATA ===");
        error_log("Basic Order Info:");
        error_log("- Order ID: " . $order_id);
        error_log("- Order Key: " . $order_key);
        error_log("- Order Status: " . $order->get_status());
        error_log("- Payment Method: " . $order->get_payment_method());
        
        error_log("\nOrder Items:");
        foreach ($order->get_items() as $item) {
            error_log("- Product ID: " . $item->get_product_id());
            error_log("  Name: " . $item->get_name());
            error_log("  Quantity: " . $item->get_quantity());
        }
        
        error_log("\nOrder Meta Data:");
        foreach ($order->get_meta_data() as $meta) {
            error_log("- Key: '" . $meta->key . "'");
            error_log("  Value: '" . print_r($meta->value, true) . "'");
        }
        
        error_log("\nDirectly Checking Registration Fields:");
        foreach ($reg_fields as $field) {
            error_log("- Checking '" . $field . "': '" . $order->get_meta($field) . "'");
        }
        error_log("Direct Meta Access:");
        foreach ($reg_fields as $field) {
            error_log("- Field '{$field}' direct get_meta(): " . print_r($order->get_meta($field), true));
        }
        error_log("Request Data:");
        error_log("- POST: " . print_r($_POST, true));
        error_log("- GET: " . print_r($_GET, true));
        error_log("=== DEBUG: DETAILED ORDER DATA END ===\n\n");

        foreach ($reg_fields as $field) {
            $value = $order->get_meta($field);
            error_log('Order ' . $order_id . ' - Checking field ' . $field . ': ' . var_export($value, true));
            if (!empty($value)) {
                $reg_number = $value;
                break;
            }
        }

        if (empty($reg_number)) {
            error_log('Order ' . $order_id . ': No registration number found in meta fields: ' . implode(', ', $reg_fields));
            return '<p>Ingen registreringsnummer funnet for denne ordren. Vennligst kontakt support.</p>';
        }

        // Verify order exists and is valid
        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            return '<p>Invalid order information.</p>';
        }

        // Check if transient already exists before creating
        $transient_key = 'owner_access_' . $reg_number;
        if (false === get_transient($transient_key)) {
            $expiry = 24 * HOUR_IN_SECONDS; // 24 hours
            set_transient($transient_key, true, $expiry);
        }

        ob_start();
        ?>
        <div class="vehicle-lookup-container order-confirmation-container">
            <h2>✅ Bestilling bekreftet</h2>
            <p>Betalingen er gjennomført</p>
            <div class="plate">
                <div class="blue-strip">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/d/d9/Flag_of_Norway.svg" alt="Norwegian Flag">
                    <div class="country">N</div>
                </div>
                <div class="reg-number"><?php echo esc_html($reg_number); ?></div>
            </div>
            <div id="vehicle-lookup-results" class="results-wrapper">
                <div class="vehicle-header">
                    <div class="vehicle-info">
                        <h2 class="vehicle-title"></h2>
                        <img class="vehicle-logo" src="" alt="Car manufacturer logo">
                        <p class="vehicle-subtitle"></p>
                    </div>
                </div>
                
                <nav class="tabs">
                    <ul>
                        <li data-tab="general-info"><a href="#general-info">Generell</a></li>
                        <li data-tab="technical-info"><a href="#technical-info">Teknisk</a></li>
                    </ul>
                </nav>
                
                <div class="tab-content">
                    <section id="general-info" class="tab-panel">
                        <div class="accordion">
                            <details open>
                                <summary>Eierinformasjon</summary>
                                <div class="details-content">
                                    <div id="owner-info-container">
                                        <table class="info-table owner-info-table"></table>
                                    </div>
                                </div>
                            </details>
                            <details open>
                                <summary>Generell informasjon</summary>
                                <div class="details-content">
                                    <table class="info-table general-info-table"></table>
                                </div>
                            </details>
                            <details open>
                                <summary>Registrering og kontroll</summary>
                                <div class="details-content">
                                    <table class="info-table registration-info-table"></table>
                                </div>
                            </details>
                        </div>
                    </section>
                    
                    <section id="technical-info" class="tab-panel">
                        <div class="accordion">
                            <details open>
                                <summary>Motor og drivverk</summary>
                                <div class="details-content">
                                    <table class="info-table engine-info-table"></table>
                                </div>
                            </details>
                            <details open>
                                <summary>Størrelse og vekt</summary>
                                <div class="details-content">
                                    <table class="info-table size-weight-table"></table>
                                </div>
                            </details>
                            <details open>
                                <summary>Dekk og felg</summary>
                                <div class="details-content">
                                    <table class="info-table tire-info-table"></table>
                                </div>
                            </details>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            const regNumber = '<?php echo esc_js($reg_number); ?>';
            
            // Initialize tabs
            function initializeTabs() {
                $('.tabs li:first-child a').addClass('active');
                $('.tab-panel:first-child').show().siblings('.tab-panel').hide();
            }

            // Handle tab clicks
            $(document).on('click', '.tabs a', function(e) {
                e.preventDefault();
                const tabId = $(this).parent().data('tab');
                $('.tabs a').removeClass('active');
                $(this).addClass('active');
                $('.tab-panel').hide();
                $('#' + tabId).show();
            });

            $.ajax({
                url: vehicleLookupAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vehicle_lookup',
                    nonce: vehicleLookupAjax.nonce,
                    regNumber: regNumber
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const vehicleData = response.data.responser[0].kjoretoydata;
                        
                        // Set vehicle title and subtitle
                        if (vehicleData.kjoretoyId?.kjennemerke) {
                            $('.vehicle-title').text(vehicleData.kjoretoyId.kjennemerke);
                        }

                        // Set manufacturer logo
                        if (vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData?.generelt?.merke?.[0]?.merke) {
                            const manufacturer = vehicleData.godkjenning.tekniskGodkjenning.tekniskeData.generelt.merke[0].merke.toLowerCase();
                            const logoUrl = `https://www.carlogos.org/car-logos/${manufacturer}-logo.png`;
                            $('.vehicle-logo').attr('src', logoUrl);
                        }

                        // Display vehicle data
                        renderOwnerInfo(vehicleData);
                        renderBasicInfo(vehicleData);
                        renderTechnicalInfo(vehicleData);
                        renderRegistrationInfo(vehicleData);

                        // Initialize tabs
                        initializeTabs();
                        $('#vehicle-lookup-results').show();
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