<?php
class Vehicle_Lookup_Helpers {
    
    /**
     * Normalize Norwegian registration plate
     * - Convert to uppercase
     * - Remove all whitespace characters
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
        
        // Remove all whitespace characters and convert to uppercase
        return strtoupper(preg_replace('/\s+/', '', $plate));
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
            '<div class="plate-input-wrapper">
                <div class="plate-flag">🇳🇴<span class="plate-country">N</span></div>
                <input type="text" id="%s" name="%s" required
                       class="plate-input"
                       placeholder="CO11204"
                       value="%s"
                       pattern="([A-Za-z]{2}\d{4,5}|[Ee][KkLlVvBbCcDdEe]\d{5}|[Cc][Dd]\d{5}|\d{5}|[A-Za-z]\d{3}|[A-Za-z]{2}\d{3})">
                <button type="submit" class="plate-search-button" aria-label="Search">
                    <div class="loading-spinner"></div>
                    <span class="search-icon">🔍</span>
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
        return '<div class="vehicle-header">
            <div class="vehicle-info">
                <img class="vehicle-logo" src="" alt="Car manufacturer logo">
                <h2 class="vehicle-title"></h2>
                <p class="vehicle-subtitle"></p>
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

        $html = '<div class="accordion">';
        foreach ($accordion_sections as $section) {
            if ($section[2] === 'eierhistorikk-content') {
                $html .= sprintf(
                    '<div class="section">
                        <div class="section-header"><span class="section-title">%s</span><span class="section-icon">%s</span></div>
                        <div class="section-content">
                            <div class="owner-history-container">
                                <div id="%s"></div>
                            </div>
                        </div>
                    </div>',
                    $section[0], $section[1], $section[2]
                );
            } else {
                $html .= sprintf(
                    '<div class="section">
                        <div class="section-header"><span class="section-title">%s</span><span class="section-icon">%s</span></div>
                        <div class="section-content">
                            <table class="info-table %s"></table>
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
        return '<div class="trust-indicators">
            <div>🔐 Data hentes fra Statens vegvesen</div>
            <div>⏱️ Svar på noen få sekunder</div>
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
