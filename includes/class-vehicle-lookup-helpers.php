<?php
class Vehicle_Lookup_Helpers {
    
    /**
     * Normalize Norwegian registration plate
     * - Convert to uppercase
     * - Remove all whitespace characters (including Unicode whitespace)
     * 
     * @param string $plate Registration plate number
     * @return string Normalized plate number
     */
    public static function normalize_plate($plate) {
        if (empty($plate)) {
            return '';
        }
        
        // Convert to string if needed
        $plate = (string) $plate;
        
        // Remove all whitespace characters (including Unicode whitespace) and convert to uppercase
        // \p{Z} = all Unicode separator characters (spaces)
        // \p{C} = all Unicode control characters (including zero-width spaces)
        // \s = ASCII whitespace for backwards compatibility
        return strtoupper(preg_replace('/[\p{Z}\p{C}\s]+/u', '', $plate));
    }
    
    /**
     * Validate Norwegian registration number format
     */
    public static function validate_registration_number($regNumber) {
        $valid_patterns = array(
            '/^[A-Za-z]{2}\d{4,5}$/',         // Standard vehicles and others
            '/^[Ee][KkLlVvBbCcDdEe]\d{5}$/',  // Electric vehicles
            '/^[Cc][Dd]\d{5}$/',              // Diplomatic vehicles
            '/^\d{5}$/',                      // Temporary tourist plates
            '/^[A-Za-z]\d{3}$/',              // Antique vehicles
            '/^[A-Za-z]{2}\d{3}$/'            // Provisional plates
        );

        foreach ($valid_patterns as $pattern) {
            if (preg_match($pattern, $regNumber)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract registration number from URL path or query parameters
     */
    public static function get_reg_from_url() {
        // Check WordPress query var first (from rewrite rule)
        $wp_reg_number = get_query_var('reg_number');
        if (!empty($wp_reg_number)) {
            return self::normalize_plate(sanitize_text_field($wp_reg_number));
        }
        
        // Check standard query parameter
        if (isset($_GET['reg']) && !empty($_GET['reg'])) {
            return self::normalize_plate(sanitize_text_field($_GET['reg']));
        }
        
        return '';
    }

    /**
     * Get valid registration number patterns
     */
    public static function get_valid_patterns() {
        return array(
            '/^[A-Za-z]{2}\d{4,5}$/',         // Standard vehicles
            '/^[Ee][KkLlVvBbCcDdEe]\d{5}$/',  // Electric vehicles
            '/^[Cc][Dd]\d{5}$/',              // Diplomatic vehicles
            '/^\d{5}$/',                      // Temporary tourist plates
            '/^[A-Za-z]\d{3}$/',              // Antique vehicles
            '/^[A-Za-z]{2}\d{3}$/'            // Provisional plates
        );
    }

    /**
     * Render common plate input HTML
     */
    public static function render_plate_input($reg_number = '', $input_id = 'regNumber', $input_name = 'regNumber') {
        return sprintf(
            '<div class="flex items-center bg-white overflow-hidden shadow-md border border-slate-200 rounded-lg">
                <div class="bg-sky-500 text-white text-3xl px-3 py-2 flex flex-col items-center justify-center h-full rounded-l-lg">
                    <span>🇳🇴</span>
                    <span class="text-xs -mt-1">N</span>
                </div>
                <input type="text" id="%s" name="%s" required
                       class="border-0 text-3xl px-3 py-2 uppercase tracking-wider w-36 text-center outline-none font-semibold text-slate-900"
                       placeholder="CO11204"
                       value="%s"
                       pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 border-0 text-white px-6 py-3 text-base font-semibold cursor-pointer flex items-center justify-center transition-all duration-200 ease-in-out h-full rounded-r-lg hover:-translate-y-px relative" aria-label="Search">
                    <div class="opacity-0 absolute w-4 h-4 border-2 border-white rounded-full border-t-transparent animate-spin"></div>
                    <span class="transition-opacity duration-200">🔍</span>
                </button>
            </div>',
            esc_attr($input_id),
            esc_attr($input_name),
            esc_attr($reg_number)
        );
    }

    /**
     * Render vehicle header section
     */
    public static function render_vehicle_header() {
        return '<div class="mx-0 my-4 p-6 bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl shadow-sm">
            <div class="text-center">
                <img class="w-20 h-20 rounded-full object-contain mx-auto mb-3 block bg-white p-2.5 shadow-sm" src="" alt="Car manufacturer logo">
                <h2 class="text-2xl font-bold text-slate-900 mb-2"></h2>
                <p class="text-base text-slate-600"></p>
            </div>
        </div>';
    }

    /**
     * Render accordion section
     */
    public static function render_accordion_section() {
        $accordion_sections = [
            ['Generell informasjon', '📋', 'basic-info-table'],
            ['Reg. og EU-kontroll', '🔍', 'registration-info-table'],
            ['Eierhistorikk', '👥', 'eierhistorikk-content'],
            ['Motor og drivverk', '🔧', 'engine-info-table'],
            ['Størrelse og vekt', '⚖️', 'size-weight-table'],
            ['Dekk og felg', '🛞', 'tire-info-table'],
            ['Merknader', '📝', 'notes-info-table']
        ];

        $html = '<div class="mt-2 flex flex-col gap-2">';
        foreach ($accordion_sections as $section) {
            if ($section[2] === 'eierhistorikk-content') {
                $html .= sprintf(
                    '<div class="bg-white rounded-2xl overflow-hidden shadow-lg border border-slate-200">
                        <div class="p-3 font-bold bg-gradient-to-br from-slate-50 to-slate-100 relative flex justify-between items-center gap-3 text-sm text-slate-900 border-b border-slate-200">
                            <span class="flex-1 font-bold">%s</span><span class="text-base">%s</span>
                        </div>
                        <div class="p-0">
                            <div class="p-0">
                                <div id="%s"></div>
                            </div>
                        </div>
                    </div>',
                    $section[0], $section[1], $section[2]
                );
            } else {
                $html .= sprintf(
                    '<div class="bg-white rounded-2xl overflow-hidden shadow-lg border border-slate-200">
                        <div class="p-3 font-bold bg-gradient-to-br from-slate-50 to-slate-100 relative flex justify-between items-center gap-3 text-sm text-slate-900 border-b border-slate-200">
                            <span class="flex-1 font-bold">%s</span><span class="text-base">%s</span>
                        </div>
                        <div class="p-0">
                            <table class="w-full border-collapse text-[0.95rem] %s"></table>
                        </div>
                    </div>',
                    $section[0], $section[1], $section[2]
                );
            }
        }
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render trust indicators
     */
    public static function render_trust_indicators() {
        return '<div class="flex flex-col sm:flex-row gap-4 justify-center items-center mt-6 text-sm text-slate-600">
            <div class="flex items-center gap-2">🔐 <span>Data hentes fra Statens vegvesen</span></div>
            <div class="flex items-center gap-2">⏱️ <span>Svar på noen få sekunder</span></div>
        </div>';
    }

    /**
     * Validate correlation ID format from Cloudflare Worker
     * Expected format: req-{timestamp}-{random}
     */
    public static function is_valid_correlation_id($correlationId) {
        if (empty($correlationId) || !is_string($correlationId)) {
            return false;
        }
        return preg_match('/^req-\d+-[a-z0-9]{9}$/', $correlationId) === 1;
    }
}
