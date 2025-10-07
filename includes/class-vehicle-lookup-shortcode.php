<?php
class Vehicle_Lookup_Shortcode {
    public function init() {
        add_shortcode('vehicle_lookup', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        // Extract and sanitize product_id from shortcode attributes
        $atts = shortcode_atts(array(
            'product_id' => '62' // Default product ID
        ), $atts);

        $product_id = absint($atts['product_id']);

        // Check for registration number in URL path or query parameter
        $reg_number = $this->get_reg_from_url();

        ob_start();
        echo $this->render_form_section($reg_number);
        echo $this->render_results_section($product_id);
        echo $this->render_footer_section();
        echo $this->render_premium_data_script();
        echo $this->render_auto_submit_script($reg_number);

        return ob_get_clean();
    }

    private function render_form_section($reg_number) {
        ob_start();
        ?>
        <div class="max-w-full mx-auto px-4">
            <form id="vehicle-lookup-form" class="flex justify-center mx-auto my-4">
                <?php echo $this->render_plate_input($reg_number); ?>
            </form>
        <?php
        return ob_get_clean();
    }

    private function render_plate_input($reg_number) {
        return Vehicle_Lookup_Helpers::render_plate_input($reg_number);
    }

    private function render_results_section($product_id) {
        ob_start();
        ?>
        <div id="vehicle-lookup-results" style="display: none;">
            <?php echo $this->render_vehicle_header(); ?>
            <?php echo $this->render_owner_section($product_id); ?>
            <?php echo $this->render_accordion_section(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_vehicle_header() {
        return Vehicle_Lookup_Helpers::render_vehicle_header();
    }

    private function render_owner_section($product_id) {
        // Get products for both tiers
        $basic_product = wc_get_product(62);
        $premium_product = wc_get_product(739);

        $basic_price = $basic_product ? $basic_product->get_regular_price() : '39';
        $basic_sale = $basic_product ? $basic_product->get_sale_price() : null;

        $premium_price = $premium_product ? $premium_product->get_regular_price() : '89';
        $premium_sale = $premium_product ? $premium_product->get_sale_price() : null;

        ob_start();
        ?>
        <div class="my-0">
            <div id="owner-info-container">
                <div id="free-info-guide" class="bg-gradient-to-br from-sky-50 to-sky-100 rounded-2xl shadow-lg p-6 my-6 border border-sky-200">
                    <div class="text-center">
                        <h4 class="text-xl font-bold text-slate-900 mb-3 flex items-center justify-center gap-2">
                            <span>💡</span>
                            <span>Se gratis informasjon</span>
                        </h4>
                        <p class="text-base text-slate-700 mb-4 leading-relaxed">Utforsk tekniske detaljer, EU-kontroll status og mer i boksene nedenfor - helt gratis!</p>
                        <button type="button" class="bg-sky-600 hover:bg-sky-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 ease-in-out shadow-md hover:shadow-lg hover:-translate-y-0.5" onclick="expandAllAccordions()">Utforsk gratis info</button>
                    </div>
                </div>

                <div id="tier-selection" class="my-6">
                    <h3 class="text-2xl font-bold text-center text-slate-900 mb-6">Velg rapporttype</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                        <!-- Basic Tier -->
                        <div class="bg-white rounded-2xl shadow-lg border-2 border-slate-200 p-6 transition-all duration-200 hover:shadow-xl hover:border-sky-400">
                            <div class="mb-6">
                                <h4 class="text-xl font-bold text-slate-900 mb-4"><?php echo $basic_product ? esc_html($basic_product->get_name()) : 'Basic rapport'; ?></h4>
                                <div class="text-center">
                                    <?php if ($basic_sale): ?>
                                        <span class="text-slate-400 line-through text-xl font-medium"><?php echo esc_html($basic_price); ?> kr</span>
                                        <span class="block text-3xl font-bold text-sky-600 mt-1"><?php echo esc_html($basic_sale); ?> kr</span>
                                    <?php else: ?>
                                        <span class="block text-3xl font-bold text-sky-600"><?php echo esc_html($basic_price); ?> kr</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-6 space-y-3">
                                <div class="text-sm text-emerald-700 font-medium">✓ Nåværende eier</div>
                                <div class="text-sm text-emerald-700 font-medium">✓ Alle tekniske detaljer</div>
                                <div class="text-sm text-emerald-700 font-medium">✓ EU-kontroll status</div>
                            </div>
                            <div>
                                <?php echo do_shortcode("[woo_vipps_buy_now id=62 /]"); ?>
                            </div>
                        </div>

                        <!-- Premium Tier -->
                        <div class="bg-white rounded-2xl shadow-xl border-2 border-sky-500 p-6 relative md:scale-105 transition-all duration-200 hover:shadow-2xl">
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-sky-500 to-sky-600 text-white px-5 py-2 rounded-full text-xs font-semibold uppercase shadow-md">Mest populær</div>
                            <div class="mb-6">
                                <h4 class="text-xl font-bold text-slate-900 mb-2"><?php echo $premium_product ? esc_html($premium_product->get_name()) : 'Premium rapport'; ?></h4>
                                <?php
                                // Calculate percentage discount if there's a sale price
                                if ($premium_sale && $premium_sale < $premium_price): 
                                    $discount_percentage = round((($premium_price - $premium_sale) / $premium_price) * 100);
                                    ?>
                                    <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-semibold text-center my-3 shadow-md">
                                        Spar <?php echo esc_html($discount_percentage); ?>% ved å kjøpe denne!
                                    </div>
                                <?php endif; ?>
                                <div class="text-center">
                                    <?php if ($premium_sale): ?>
                                        <span class="text-slate-400 line-through text-xl font-medium"><?php echo esc_html($premium_price); ?> kr</span>
                                        <span class="block text-3xl font-bold text-sky-600 mt-1"><?php echo esc_html($premium_sale); ?> kr</span>
                                    <?php else: ?>
                                        <span class="block text-3xl font-bold text-sky-600"><?php echo esc_html($premium_price); ?> kr</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-6 space-y-3">
                                <div class="text-sm text-emerald-700 font-medium">✓ Alt fra Basic rapport</div>
                                <div class="text-sm text-emerald-700 font-medium">✓ Komplett eierhistorikk</div>
                                <div class="text-sm text-emerald-700 font-medium">✓ Skadehistorikk</div>
                                <div class="text-sm text-emerald-700 font-medium">✓ Detaljert kjøretøyrapport</div>
                                <div class="text-sm text-emerald-700 font-medium">✓ Import</div>
                            </div>
                            <div>
                                <?php 
                                $vipps_button = do_shortcode("[woo_vipps_buy_now id=739 /]");
                                echo $vipps_button;
                                ?>
                                <script>
                                window.premiumVippsBuyButton = <?php echo json_encode($vipps_button); ?>;
                                </script>
                            </div>
                        </div>
                    </div>
                    <?php echo $this->render_trust_indicators(); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_trust_indicators() {
        return Vehicle_Lookup_Helpers::render_trust_indicators();
    }

    private function render_accordion_section() {
        return Vehicle_Lookup_Helpers::render_accordion_section();
    }

    private function render_footer_section() {
        // Get usage statistics
        $db_handler = new Vehicle_Lookup_Database();
        $today = date('Y-m-d');
        $stats = $db_handler->get_stats($today . ' 00:00:00', $today . ' 23:59:59');
        $today_searches = $stats ? $stats->total_lookups : 0;

        return sprintf(
            '<div id="vehicle-lookup-error" class="bg-red-50 text-red-600 p-4 rounded border border-red-200 mt-5 hidden"></div>
            <div id="quota-display" class="mt-2.5 p-2 bg-slate-100 rounded text-sm text-slate-600 text-right hidden"></div>
            <div class="flex justify-center gap-8 my-6 p-4 bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl border border-slate-200">
                <div class="text-center">
                    <span class="block text-3xl font-bold text-sky-600 leading-none">%d</span>
                    <span class="text-sm text-slate-600 font-medium">oppslag i dag</span>
                </div>
                <div class="text-center">
                    <span class="block text-3xl font-bold text-sky-600 leading-none">15.000+</span>
                    <span class="text-sm text-slate-600 font-medium">fornøyde kunder</span>
                </div>
            </div>
            <div id="version-display" class="mt-1 text-xs text-slate-400 text-right">v%s</div>
            <div class="text-center text-sm text-slate-600 mt-8 p-4 font-medium">
                Levert av <a href="https://beepi.no" target="_blank" class="text-sky-600 no-underline font-semibold hover:text-sky-700 hover:underline">Beepi.no</a>
            </div>
            </div>',
            $today_searches,
            VEHICLE_LOOKUP_VERSION
        );
    }

    private function render_premium_data_script() {
        $premium_product = wc_get_product(739);
        $premium_data = array(
            'name' => $premium_product ? $premium_product->get_name() : 'Premium Kjøretøyrapport',
            'regular_price' => $premium_product ? $premium_product->get_regular_price() : '89',
            'sale_price' => $premium_product ? $premium_product->get_sale_price() : null
        );

        $vipps_button = do_shortcode("[woo_vipps_buy_now id=739 /]");

        return '<script>
        window.vehicleLookupData = window.vehicleLookupData || {};
        window.vehicleLookupData.premiumProduct = ' . json_encode($premium_data) . ';
        window.premiumVippsBuyButton = ' . json_encode($vipps_button) . ';
        </script>';
    }

    private function render_auto_submit_script($reg_number) {
        if (!$reg_number) return '';

        return '<script>
        jQuery(document).ready(function($) {
            setTimeout(function() {
                $("#vehicle-lookup-form").trigger("submit");
            }, 500);
        });
        </script>';
    }



    /**
     * Extract registration number from URL path or query parameters
     */
    private function get_reg_from_url() {
        return Vehicle_Lookup_Helpers::get_reg_from_url();
    }
}