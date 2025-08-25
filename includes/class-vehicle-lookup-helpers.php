<?php
class Vehicle_Lookup_Helpers {
    
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
            return strtoupper(sanitize_text_field($wp_reg_number));
        }
        
        // Check standard query parameter
        if (isset($_GET['reg']) && !empty($_GET['reg'])) {
            return strtoupper(sanitize_text_field($_GET['reg']));
        }
        
        // Check URL path for registration number as fallback
        $request_uri = $_SERVER['REQUEST_URI'];
        $path_parts = explode('/', trim($request_uri, '/'));
        
        // Look for registration number in the last part of the path
        if (!empty($path_parts)) {
            $last_part = end($path_parts);
            // Remove query string if present
            $last_part = explode('?', $last_part)[0];
            
            foreach (self::get_valid_patterns() as $pattern) {
                if (preg_match($pattern, $last_part)) {
                    return strtoupper(sanitize_text_field($last_part));
                }
            }
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
                    '<details>
                        <summary><span>%s</span><span>%s</span></summary>
                        <div class="details-content">
                            <div class="owner-history-container">
                                <div id="%s"></div>
                            </div>
                        </div>
                    </details>',
                    $section[0], $section[1], $section[2]
                );
            } else {
                $html .= sprintf(
                    '<details>
                        <summary><span>%s</span><span>%s</span></summary>
                        <div class="details-content">
                            <table class="info-table %s"></table>
                        </div>
                    </details>',
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
}
