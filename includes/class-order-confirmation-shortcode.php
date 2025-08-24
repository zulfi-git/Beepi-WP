<?php
class Order_Confirmation_Shortcode {
    public function init() {
        add_shortcode('order_confirmation', array($this, 'render_shortcode'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_complete'));
    }

    public function handle_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        $reg_number = $order->get_meta('reg_number');

        if (empty($reg_number)) {
            error_log('Payment complete but no registration number found for order: ' . $order_id);
            return;
        }

        if ($reg_number && $this->validate_order_has_lookup($order)) {
            $tier = $this->get_order_tier($order);
            
            if ($tier === 'premium') {
                $transient_key = 'premium_access_' . $reg_number;
                set_transient($transient_key, true, 24 * HOUR_IN_SECONDS);
            } elseif ($tier === 'basic') {
                $transient_key = 'owner_access_' . $reg_number;
                set_transient($transient_key, true, 24 * HOUR_IN_SECONDS);
            }
        }
    }

    public function handle_order_complete($order_id) {
        $this->handle_payment_complete($order_id);
    }

    private function validate_order_has_lookup($order) {
        $lookup_product_ids = [62, 739]; // Basic and Premium product IDs

        foreach ($order->get_items() as $item) {
            if (in_array($item->get_product_id(), $lookup_product_ids)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get product tier from order
     */
    private function get_order_tier($order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id == 739) {
                return 'premium';
            } elseif ($product_id == 62) {
                return 'basic';
            }
        }
        return 'free';
    }

    private function format_norwegian_phone($phone) {
        if (empty($phone)) {
            return $phone;
        }

        // Remove all non-digits and +
        $clean = preg_replace('/[^\d+]/', '', $phone);
        
        // Remove leading +47 if present
        if (strpos($clean, '+47') === 0) {
            $clean = substr($clean, 3);
        } elseif (strpos($clean, '47') === 0 && strlen($clean) === 10) {
            $clean = substr($clean, 2);
        }

        // Format based on length
        if (strlen($clean) === 8) {
            // Mobile number: xxx xx xxx
            if (preg_match('/^[4-9]/', $clean)) {
                return '+47 ' . substr($clean, 0, 3) . ' ' . substr($clean, 3, 2) . ' ' . substr($clean, 5, 3);
            }
            // Landline: xx xx xx xx
            else {
                return '+47 ' . substr($clean, 0, 2) . ' ' . substr($clean, 2, 2) . ' ' . substr($clean, 4, 2) . ' ' . substr($clean, 6, 2);
            }
        }

        // Return original if can't format
        return $phone;
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
        $reg_number = $order->get_meta('reg_number');

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

        error_log("\nRegistration Number Check:");
        error_log("- reg_number: '" . $order->get_meta('reg_number') . "'");
        error_log("Request Data:");
        error_log("- POST: " . print_r($_POST, true));
        error_log("- GET: " . print_r($_GET, true));
        
        // Log customer phone number for debugging
        $customer_phone = $order->get_billing_phone();
        error_log("Customer Phone Number: " . $customer_phone);
        error_log("=== DEBUG: DETAILED ORDER DATA END ===\n\n");

        if (empty($reg_number)) {
            error_log('Order ' . $order_id . ': No registration number found in reg_number field');
            return '<p>Ingen registreringsnummer funnet for denne ordren. Vennligst kontakt support.</p>';
        }

        // Verify order exists and is valid
        $order = wc_get_order($order_id);
        if (!$order || $order->get_order_key() !== $order_key) {
            return '<p>Invalid order information.</p>';
        }

        // Set appropriate transient based on purchased tier
        $tier = $this->get_order_tier($order);
        
        if ($tier === 'premium') {
            $transient_key = 'premium_access_' . $reg_number;
            if (false === get_transient($transient_key)) {
                set_transient($transient_key, true, 24 * HOUR_IN_SECONDS);
            }
        } elseif ($tier === 'basic') {
            $transient_key = 'owner_access_' . $reg_number;
            if (false === get_transient($transient_key)) {
                set_transient($transient_key, true, 24 * HOUR_IN_SECONDS);
            }
        }

        ob_start();
        ?>
        <div class="vehicle-lookup-container order-confirmation-container">
            <div id="vehicle-lookup-results" class="results-wrapper">
                <div class="vehicle-header">
                    <div class="vehicle-info">
                        <h2 class="vehicle-title"></h2>
                        <img class="vehicle-logo" src="" alt="Car manufacturer logo">
                        <p class="vehicle-subtitle"></p>
                    </div>
                </div>

                <div class="accordion">
                    <details open>
                        <summary><span>Eierinformasjon</span><span>👤</span></summary>
                        <div class="details-content">
                            <table class="info-table owner-info-table"></table>
                        </div>
                    </details>
                    
                    <details open>
                        <summary><span>Kjøperinformasjon</span><span>📋</span></summary>
                        <div class="details-content">
                            <table class="info-table">
                                <tr><th>Telefonnummer</th><td><?php echo esc_html($this->format_norwegian_phone($order->get_billing_phone())); ?></td></tr>
                                <tr><th>E-post</th><td><?php echo esc_html($order->get_billing_email()); ?></td></tr>
                                <tr><th>Ordre ID</th><td><?php echo esc_html($order_id); ?></td></tr>
                            </table>
                        </div>
                    </details>
                </div>

                <?php 
                // Check if SMS was sent by looking for SMS status in order meta
                $sms_status = $order->get_meta('_sms_notification_status');
                $sms_sent_time = $order->get_meta('_sms_sent_time');
                ?>
                <div class="sms-status-card <?php echo $sms_status === 'sent' ? 'sent' : ($sms_status === 'failed' ? 'failed' : 'pending'); ?>">
                    <h4>📱 SMS Varsel</h4>
                    <?php if ($sms_status === 'sent'): ?>
                        <p>
                            ✅ Eierinformasjon er sendt til ditt telefonnummer
                            <?php if ($sms_sent_time): ?>
                                <br><small style="color: #666;">Sendt: <?php echo date('d.m.Y H:i', strtotime($sms_sent_time)); ?></small>
                            <?php endif; ?>
                        </p>
                    <?php elseif ($sms_status === 'failed'): ?>
                        <p>
                            ❌ SMS kunne ikke sendes. Eierinformasjonen er tilgjengelig på denne siden.
                        </p>
                    <?php else: ?>
                        <p>
                            ⏳ SMS varsel behandles...
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            const regNumber = '<?php echo esc_js(trim($reg_number)); ?>';
            console.log('Registration number:', regNumber); // Debug log

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
                    console.log('API Response:', response); // Debug log
                    if (response.success && response.data && response.data.responser && response.data.responser[0]) {
                        const vehicleData = response.data.responser[0].kjoretoydata;
                        const tekniskeData = vehicleData.godkjenning?.tekniskGodkjenning?.tekniskeData;
                        const generalData = tekniskeData?.generelt;

                        // Set vehicle title and subtitle
                        if (vehicleData.kjoretoyId?.kjennemerke) {
                            $('.vehicle-title').text(vehicleData.kjoretoyId.kjennemerke);
                        }

                        // Set vehicle subtitle
                        if (generalData) {
                            let subtitle = '';
                            if (generalData.merke?.[0]?.merke) {
                                subtitle += generalData.merke[0].merke + ' ';
                            }
                            if (generalData.handelsbetegnelse?.[0]) {
                                subtitle += generalData.handelsbetegnelse[0];
                            }
                            $('.vehicle-subtitle').html(subtitle);
                        }

                        // Set manufacturer logo
                        if (generalData?.merke?.[0]?.merke) {
                            const manufacturer = generalData.merke[0].merke.toLowerCase();
                            const logoUrl = `https://www.carlogos.org/car-logos/${manufacturer}-logo.png`;
                            $('.vehicle-logo').attr('src', logoUrl);
                        }

                        // Display vehicle data
                        const engineData = tekniskeData?.motorOgDrivverk;
                        const dekkOgFelg = tekniskeData?.dekkOgFelg?.akselDekkOgFelgKombinasjon?.[0]?.akselDekkOgFelg;
                        const frontTire = dekkOgFelg?.find(axle => axle.akselId === 1);
                        const rearTire = dekkOgFelg?.find(axle => axle.akselId === 2);
                        const dimensions = tekniskeData?.dimensjoner;
                        const vekter = tekniskeData?.vekter;

                        // Prepare tire info
                        const tireInfo = {
                            'Dekkdimensjon foran': frontTire?.dekkdimensjon,
                            'Felgdimensjon foran': frontTire?.felgdimensjon,
                            'Innpress foran': frontTire?.innpress ? frontTire.innpress + ' mm' : null,
                            'Belastningskode foran': frontTire?.belastningskodeDekk,
                            'Hastighetskode foran': frontTire?.hastighetskodeDekk
                        };

                        if (rearTire) {
                            Object.assign(tireInfo, {
                                'Dekkdimensjon bak': rearTire.dekkdimensjon,
                                'Felgdimensjon bak': rearTire.felgdimensjon,
                                'Innpress bak': rearTire.innpress ? rearTire.innpress + ' mm' : null,
                                'Belastningskode bak': rearTire.belastningskodeDekk,
                                'Hastighetskode bak': rearTire.hastighetskodeDekk
                            });
                        }

                        $('.tire-info-table').html(
                            Object.entries(tireInfo)
                                .filter(([_, value]) => value)
                                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                                .join('')
                        );

                        // Owner info
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

                        // Basic info
                        const basicInfo = {
                            'Kjennemerke': vehicleData.kjoretoyId?.kjennemerke,
                            'Understellsnummer': vehicleData.kjoretoyId?.understellsnummer,
                            'Merke': tekniskeData?.merke?.[0]?.merke,
                            'Modell': tekniskeData?.handelsbetegnelse?.[0],
                            'Farge': tekniskeData?.karosseriOgLasteplan?.rFarge?.[0]?.kodeBeskrivelse,
                            'Type': tekniskeData?.generelt?.tekniskKode?.kodeBeskrivelse,
                            'Antall seter': tekniskeData?.persontall?.sitteplasserTotalt
                        };

                        $('.general-info-table').html(
                            Object.entries(basicInfo)
                                .filter(([_, value]) => value)
                                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                                .join('')
                        );

                        // Registration info
                        const regInfo = {
                            'Registreringsnummer': vehicleData.kjoretoyId?.kjennemerke,
                            'Første registrering': vehicleData.forstegangsregistrering?.registrertForstegangNorgeDato,
                            'Status': vehicleData.registrering?.registreringsstatus?.kodeBeskrivelse,
                            'Neste EU-kontroll': vehicleData.periodiskKjoretoyKontroll?.kontrollfrist
                        };

                        $('.registration-info-table').html(
                            Object.entries(regInfo)
                                .filter(([_, value]) => value)
                                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                                .join('')
                        );

                        // Engine info
                        const engineInfo = {
                            'Motor': engineData?.motor?.[0]?.antallSylindre + ' sylindre',
                            'Drivstoff': engineData?.motor?.[0]?.arbeidsprinsipp?.kodeBeskrivelse,
                            'Slagvolum': engineData?.motor?.[0]?.slagvolum + ' ccm',
                            'Effekt': engineData?.motor?.[0]?.drivstoff?.[0]?.maksNettoEffekt + ' kW',
                            'Girkasse': engineData?.girkassetype?.kodeBeskrivelse
                        };

                        $('.engine-info-table').html(
                            Object.entries(engineInfo)
                                .filter(([_, value]) => value)
                                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                                .join('')
                        );

                        // Size and weight info
                        const weightInfo = {
                            'Lengde': dimensions?.lengde ? dimensions.lengde + ' mm' : '',
                            'Bredde': dimensions?.bredde ? dimensions.bredde + ' mm' : '',
                            'Høyde': dimensions?.hoyde ? dimensions.hoyde + ' mm' : '',
                            'Egenvekt': vekter?.egenvekt ? vekter.egenvekt + ' kg' : '',
                            'Nyttelast': vekter?.nyttelast ? vekter.nyttelast + ' kg' : ''
                        };

                        $('.size-weight-table').html(
                            Object.entries(weightInfo)
                                .filter(([_, value]) => value)
                                .map(([label, value]) => `<tr><th>${label}</th><td>${value}</td></tr>`)
                                .join('')
                        );

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