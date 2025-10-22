<?php
/**
 * SEO and Meta Tags Handler for Vehicle Lookup
 * 
 * Handles SEO optimization, meta tags, structured data (JSON-LD),
 * OpenGraph tags, and Twitter Cards for vehicle lookup pages.
 */
class Vehicle_Lookup_SEO {
    
    /**
     * Initialize SEO hooks
     */
    public function init() {
        // Add meta tags to wp_head
        add_action('wp_head', array($this, 'add_meta_tags'), 1);
        
        // Add structured data (JSON-LD)
        add_action('wp_head', array($this, 'add_structured_data'), 2);
        
        // Modify page title for vehicle pages
        add_filter('document_title_parts', array($this, 'modify_title'), 10, 1);
        
        // Add canonical URL
        add_action('wp_head', array($this, 'add_canonical_url'), 1);
    }
    
    /**
     * Check if current page is a vehicle lookup page
     */
    private function is_vehicle_page() {
        global $wp_query;
        
        // Check if we're on the sok page
        if (is_page('sok')) {
            return true;
        }
        
        // Check for reg_number query var
        $reg_number = get_query_var('reg_number');
        if (!empty($reg_number)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get registration number from URL
     */
    private function get_registration_number() {
        // Try query var first
        $reg_number = get_query_var('reg_number');
        
        if (empty($reg_number)) {
            // Try from URL path
            $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
            if (preg_match('/\/sok\/([^\/\?]+)/', $request_uri, $matches)) {
                $reg_number = $matches[1];
            }
        }
        
        return strtoupper(sanitize_text_field($reg_number));
    }
    
    /**
     * Get vehicle data from cache or database
     */
    private function get_vehicle_data($reg_number) {
        if (empty($reg_number)) {
            return null;
        }
        
        // Try WordPress transient cache first (12 hour cache)
        $cache_key = 'vehicle_data_' . $reg_number;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Try to get from database logs (most recent successful lookup)
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        $sql = 'SELECT response_data FROM ' . esc_sql($table_name) . ' WHERE reg_number = %s AND success = 1 AND response_data IS NOT NULL ORDER BY lookup_time DESC LIMIT 1';
        $result = $wpdb->get_row($wpdb->prepare($sql, $reg_number));
        
        if ($result && !empty($result->response_data)) {
            $data = json_decode($result->response_data, true);
            
            // Cache for 12 hours
            set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
            
            return $data;
        }
        
        return null;
    }
    
    /**
     * Modify page title for vehicle pages
     */
    public function modify_title($title_parts) {
        if (!$this->is_vehicle_page()) {
            return $title_parts;
        }
        
        $reg_number = $this->get_registration_number();
        if (empty($reg_number)) {
            $title_parts['title'] = 'Kjøretøyoppslag - Beepi';
            return $title_parts;
        }
        
        $vehicle_data = $this->get_vehicle_data($reg_number);
        
        if ($vehicle_data && isset($vehicle_data['vehicle'])) {
            $vehicle = $vehicle_data['vehicle'];
            $make = isset($vehicle['make']) ? $vehicle['make'] : '';
            $model = isset($vehicle['model']) ? $vehicle['model'] : '';
            $year = isset($vehicle['year']) ? $vehicle['year'] : '';
            
            if ($make && $model) {
                $title_parts['title'] = sprintf(
                    '%s %s %s (%s) - Eierinformasjon og Kjøretøydata',
                    $make,
                    $model,
                    $year,
                    $reg_number
                );
            } else {
                $title_parts['title'] = sprintf('%s - Kjøretøyoppslag | Beepi', $reg_number);
            }
        } else {
            $title_parts['title'] = sprintf('%s - Søk etter Kjøretøyinformasjon | Beepi', $reg_number);
        }
        
        return $title_parts;
    }
    
    /**
     * Add canonical URL
     */
    public function add_canonical_url() {
        if (!$this->is_vehicle_page()) {
            return;
        }
        
        $reg_number = $this->get_registration_number();
        if (!empty($reg_number)) {
            $canonical_url = home_url('/sok/' . $reg_number);
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        }
    }
    
    /**
     * Add meta tags for SEO
     */
    public function add_meta_tags() {
        if (!$this->is_vehicle_page()) {
            return;
        }
        
        $reg_number = $this->get_registration_number();
        
        // Base meta tags for search page without registration number
        if (empty($reg_number)) {
            echo '<meta name="description" content="Søk etter norsk kjøretøyinformasjon med Beepi. Finn eieropplysninger, tekniske detaljer og markedspriser for alle norske registreringsnummer." />' . "\n";
            echo '<meta name="keywords" content="kjøretøyoppslag, biloppslag, registreringsnummer, eieropplysninger, norge, beepi" />' . "\n";
            echo '<meta name="robots" content="index, follow" />' . "\n";
            return;
        }
        
        $vehicle_data = $this->get_vehicle_data($reg_number);
        
        if ($vehicle_data && isset($vehicle_data['vehicle'])) {
            $vehicle = $vehicle_data['vehicle'];
            $make = isset($vehicle['make']) ? $vehicle['make'] : '';
            $model = isset($vehicle['model']) ? $vehicle['model'] : '';
            $year = isset($vehicle['year']) ? $vehicle['year'] : '';
            $color = isset($vehicle['color']) ? $vehicle['color'] : '';
            
            // Description meta tag
            $description = sprintf(
                'Se detaljert informasjon om %s %s %s (%s). Finn eieropplysninger, tekniske spesifikasjoner, markedspris og historikk.',
                $make,
                $model,
                $year,
                $reg_number
            );
            echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
            
            // Keywords meta tag
            $keywords = sprintf(
                '%s, %s %s, %s, kjøretøyoppslag, eieropplysninger, bildata, registreringsnummer',
                $reg_number,
                $make,
                $model,
                $year
            );
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '" />' . "\n";
            
            // Robots meta tag
            echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />' . "\n";
            
            // OpenGraph tags for social media
            $this->add_opengraph_tags($reg_number, $vehicle_data);
            
            // Twitter Card tags
            $this->add_twitter_card_tags($reg_number, $vehicle_data);
            
        } else {
            // Vehicle not yet loaded - generic meta tags
            $description = sprintf(
                'Søk etter detaljert informasjon om kjøretøy med registreringsnummer %s. Finn eieropplysninger, tekniske detaljer og markedspriser hos Beepi.',
                $reg_number
            );
            echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
            echo '<meta name="keywords" content="' . esc_attr($reg_number) . ', kjøretøyoppslag, biloppslag, eieropplysninger, norge" />' . "\n";
            echo '<meta name="robots" content="index, follow" />' . "\n";
        }
    }
    
    /**
     * Add OpenGraph meta tags for social media sharing
     */
    private function add_opengraph_tags($reg_number, $vehicle_data) {
        $vehicle = $vehicle_data['vehicle'];
        $make = isset($vehicle['make']) ? $vehicle['make'] : '';
        $model = isset($vehicle['model']) ? $vehicle['model'] : '';
        $year = isset($vehicle['year']) ? $vehicle['year'] : '';
        
        $title = sprintf('%s %s %s (%s)', $make, $model, $year, $reg_number);
        $description = sprintf(
            'Se detaljert informasjon om %s %s %s. Finn eieropplysninger, tekniske spesifikasjoner og markedspris.',
            $make,
            $model,
            $year
        );
        $url = home_url('/sok/' . $reg_number);
        
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:site_name" content="Beepi" />' . "\n";
        echo '<meta property="og:locale" content="nb_NO" />' . "\n";
        
        // Add image if available
        $image_url = get_site_icon_url();
        if ($image_url) {
            echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
        }
    }
    
    /**
     * Add Twitter Card meta tags
     */
    private function add_twitter_card_tags($reg_number, $vehicle_data) {
        $vehicle = $vehicle_data['vehicle'];
        $make = isset($vehicle['make']) ? $vehicle['make'] : '';
        $model = isset($vehicle['model']) ? $vehicle['model'] : '';
        $year = isset($vehicle['year']) ? $vehicle['year'] : '';
        
        $title = sprintf('%s %s %s (%s)', $make, $model, $year, $reg_number);
        $description = sprintf(
            'Se detaljert informasjon om %s %s %s hos Beepi.',
            $make,
            $model,
            $year
        );
        
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
        
        // Add image if available
        $image_url = get_site_icon_url();
        if ($image_url) {
            echo '<meta name="twitter:image" content="' . esc_url($image_url) . '" />' . "\n";
        }
    }
    
    /**
     * Add structured data (JSON-LD) for search engines
     */
    public function add_structured_data() {
        if (!$this->is_vehicle_page()) {
            return;
        }
        
        $reg_number = $this->get_registration_number();
        if (empty($reg_number)) {
            // Add WebSite schema for search page
            $this->add_website_schema();
            return;
        }
        
        $vehicle_data = $this->get_vehicle_data($reg_number);
        
        if ($vehicle_data && isset($vehicle_data['vehicle'])) {
            // Add Vehicle schema
            $this->add_vehicle_schema($reg_number, $vehicle_data);
            
            // Add BreadcrumbList schema
            $this->add_breadcrumb_schema($reg_number, $vehicle_data);
            
            // Add Product schema for owner information
            $this->add_product_schema($reg_number, $vehicle_data);
        }
    }
    
    /**
     * Add WebSite structured data
     */
    private function add_website_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Beepi',
            'url' => home_url(),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/sok/{search_term_string}')
                ),
                'query-input' => 'required name=search_term_string'
            )
        );
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
    
    /**
     * Add Vehicle structured data
     */
    private function add_vehicle_schema($reg_number, $vehicle_data) {
        $vehicle = $vehicle_data['vehicle'];
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Vehicle',
            'name' => sprintf('%s %s', 
                isset($vehicle['make']) ? $vehicle['make'] : '',
                isset($vehicle['model']) ? $vehicle['model'] : ''
            ),
            'vehicleIdentificationNumber' => $reg_number
        );
        
        // Add optional properties if available
        if (isset($vehicle['make'])) {
            $schema['manufacturer'] = array(
                '@type' => 'Organization',
                'name' => $vehicle['make']
            );
        }
        
        if (isset($vehicle['model'])) {
            $schema['model'] = $vehicle['model'];
        }
        
        if (isset($vehicle['year'])) {
            $schema['productionDate'] = $vehicle['year'];
            $schema['vehicleModelDate'] = $vehicle['year'];
        }
        
        if (isset($vehicle['color'])) {
            $schema['color'] = $vehicle['color'];
        }
        
        if (isset($vehicle['fuelType'])) {
            $schema['fuelType'] = $vehicle['fuelType'];
        }
        
        if (isset($vehicle['enginePower'])) {
            $schema['vehicleEngine'] = array(
                '@type' => 'EngineSpecification',
                'enginePower' => array(
                    '@type' => 'QuantitativeValue',
                    'value' => $vehicle['enginePower'],
                    'unitCode' => 'KWT'
                )
            );
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
    
    /**
     * Add BreadcrumbList structured data
     */
    private function add_breadcrumb_schema($reg_number, $vehicle_data) {
        $vehicle = $vehicle_data['vehicle'];
        $make = isset($vehicle['make']) ? $vehicle['make'] : '';
        $model = isset($vehicle['model']) ? $vehicle['model'] : '';
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(
                array(
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Hjem',
                    'item' => home_url()
                ),
                array(
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Kjøretøyoppslag',
                    'item' => home_url('/sok')
                ),
                array(
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => sprintf('%s %s (%s)', $make, $model, $reg_number),
                    'item' => home_url('/sok/' . $reg_number)
                )
            )
        );
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
    
    /**
     * Add Product structured data for owner information service
     */
    private function add_product_schema($reg_number, $vehicle_data) {
        $vehicle = $vehicle_data['vehicle'];
        $make = isset($vehicle['make']) ? $vehicle['make'] : '';
        $model = isset($vehicle['model']) ? $vehicle['model'] : '';
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => sprintf('Eieropplysninger for %s %s (%s)', $make, $model, $reg_number),
            'description' => sprintf('Få tilgang til eieropplysninger for %s %s med registreringsnummer %s.', $make, $model, $reg_number),
            'offers' => array(
                '@type' => 'Offer',
                'price' => '69',
                'priceCurrency' => 'NOK',
                'availability' => 'https://schema.org/InStock',
                'url' => home_url('/sok/' . $reg_number),
                'seller' => array(
                    '@type' => 'Organization',
                    'name' => 'Beepi'
                )
            )
        );
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
}
