<?php
/**
 * Content Enhancement for Vehicle Lookup
 * 
 * Handles internal linking, related vehicle suggestions, and content
 * improvements for better SEO and user engagement.
 */
class Vehicle_Lookup_Content {
    
    /**
     * Initialize content enhancements
     */
    public function init() {
        // Add related vehicles section to vehicle pages
        add_action('wp_footer', array($this, 'add_related_vehicles_section'), 20);
        
        // Add internal links shortcode
        add_shortcode('related_vehicles', array($this, 'render_related_vehicles_shortcode'));
    }
    
    /**
     * Check if current page is a vehicle lookup page
     */
    private function is_vehicle_page() {
        if (is_page('sok')) {
            return true;
        }
        
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
        $reg_number = get_query_var('reg_number');
        
        if (empty($reg_number)) {
            $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
            if (preg_match('/\/sok\/([^\/\?]+)/', $request_uri, $matches)) {
                $reg_number = $matches[1];
            }
        }
        
        return strtoupper(sanitize_text_field($reg_number));
    }
    
    /**
     * Get vehicle data from cache
     */
    private function get_vehicle_data($reg_number) {
        if (empty($reg_number)) {
            return null;
        }
        
        $cache_key = 'vehicle_data_' . $reg_number;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT response_data FROM `{$table_name}` 
            WHERE reg_number = %s AND success = 1 AND response_data IS NOT NULL 
            ORDER BY lookup_time DESC LIMIT 1",
            $reg_number
        ));
        
        if ($result && !empty($result->response_data)) {
            $data = json_decode($result->response_data, true);
            set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
            return $data;
        }
        
        return null;
    }
    
    /**
     * Get related vehicles based on make/model or recent searches
     */
    private function get_related_vehicles($current_reg_number, $vehicle_data = null, $limit = 5) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        $related = array();
        
        // Strategy 1: If we have vehicle data, find similar vehicles
        if ($vehicle_data && isset($vehicle_data['vehicle'])) {
            $vehicle = $vehicle_data['vehicle'];
            $make = isset($vehicle['make']) ? $vehicle['make'] : null;
            
            if ($make) {
                // Find other vehicles with the same make that were searched recently
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT DISTINCT reg_number, MAX(lookup_time) as last_lookup
                    FROM `{$table_name}`
                    WHERE success = 1 
                    AND reg_number != %s
                    AND response_data LIKE %s
                    AND lookup_time > DATE_SUB(NOW(), INTERVAL 90 DAY)
                    GROUP BY reg_number
                    ORDER BY last_lookup DESC
                    LIMIT %d",
                    $current_reg_number,
                    '%' . $wpdb->esc_like($make) . '%',
                    $limit
                ));
                
                if (!empty($results)) {
                    foreach ($results as $row) {
                        $related[] = array(
                            'reg_number' => $row->reg_number,
                            'url' => home_url('/sok/' . urlencode($row->reg_number)),
                            'type' => 'similar_make'
                        );
                    }
                }
            }
        }
        
        // Strategy 2: If we don't have enough related vehicles, get popular recent searches
        if (count($related) < $limit) {
            $remaining = $limit - count($related);
            
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT reg_number, COUNT(*) as lookup_count
                FROM `{$table_name}`
                WHERE success = 1 
                AND reg_number != %s
                AND lookup_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY reg_number
                ORDER BY lookup_count DESC
                LIMIT %d",
                $current_reg_number,
                $remaining
            ));
            
            if (!empty($results)) {
                foreach ($results as $row) {
                    // Check if not already added
                    $already_added = false;
                    foreach ($related as $existing) {
                        if ($existing['reg_number'] === $row->reg_number) {
                            $already_added = true;
                            break;
                        }
                    }
                    
                    if (!$already_added) {
                        $related[] = array(
                            'reg_number' => $row->reg_number,
                            'url' => home_url('/sok/' . urlencode($row->reg_number)),
                            'type' => 'popular'
                        );
                    }
                }
            }
        }
        
        return $related;
    }
    
    /**
     * Add related vehicles section to the footer of vehicle pages
     */
    public function add_related_vehicles_section() {
        if (!$this->is_vehicle_page()) {
            return;
        }
        
        $reg_number = $this->get_registration_number();
        if (empty($reg_number)) {
            return;
        }
        
        $vehicle_data = $this->get_vehicle_data($reg_number);
        $related_vehicles = $this->get_related_vehicles($reg_number, $vehicle_data, 5);
        
        if (empty($related_vehicles)) {
            return;
        }
        
        // Only add on vehicle lookup pages (not admin)
        if (is_admin()) {
            return;
        }
        
        ?>
        <style>
        .related-vehicles-section {
            max-width: 1200px;
            margin: 40px auto;
            padding: 30px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .related-vehicles-section h3 {
            font-size: 24px;
            margin: 0 0 20px 0;
            color: #2c3e50;
            text-align: center;
        }
        
        .related-vehicles-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .related-vehicle-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .related-vehicle-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #3498db;
        }
        
        .related-vehicle-item a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            font-size: 18px;
            display: block;
        }
        
        .related-vehicle-item a:hover {
            color: #3498db;
        }
        
        .related-vehicle-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            background: #e8f4fd;
            color: #3498db;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .related-vehicles-list {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }
            
            .related-vehicle-item {
                padding: 15px;
            }
            
            .related-vehicle-item a {
                font-size: 16px;
            }
        }
        </style>
        
        <div class="related-vehicles-section">
            <h3>Andre søkte også på</h3>
            <ul class="related-vehicles-list">
                <?php foreach ($related_vehicles as $related): ?>
                    <li class="related-vehicle-item">
                        <a href="<?php echo esc_url($related['url']); ?>" 
                           title="Se informasjon om <?php echo esc_attr($related['reg_number']); ?>">
                            <span class="plate-flag" style="font-size: 24px;">🇳🇴</span>
                            <br>
                            <?php echo esc_html($related['reg_number']); ?>
                        </a>
                        <?php if ($related['type'] === 'similar_make'): ?>
                            <span class="related-vehicle-badge">Lignende</span>
                        <?php elseif ($related['type'] === 'popular'): ?>
                            <span class="related-vehicle-badge">Populær</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Render related vehicles shortcode
     * Usage: [related_vehicles count="5"]
     */
    public function render_related_vehicles_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'title' => 'Populære søk',
        ), $atts);
        
        $count = absint($atts['count']);
        $title = sanitize_text_field($atts['title']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        // Get popular vehicles
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT reg_number, COUNT(*) as lookup_count
            FROM `{$table_name}`
            WHERE success = 1 
            AND lookup_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY reg_number
            ORDER BY lookup_count DESC
            LIMIT %d",
            $count
        ));
        
        if (empty($results)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="related-vehicles-widget">
            <h3><?php echo esc_html($title); ?></h3>
            <ul class="popular-vehicles-list">
                <?php foreach ($results as $vehicle): ?>
                    <li>
                        <a href="<?php echo esc_url(home_url('/sok/' . urlencode($vehicle->reg_number))); ?>">
                            🇳🇴 <?php echo esc_html($vehicle->reg_number); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <style>
        .related-vehicles-widget {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .related-vehicles-widget h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
            color: #2c3e50;
        }
        
        .popular-vehicles-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .popular-vehicles-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .popular-vehicles-list li:last-child {
            border-bottom: none;
        }
        
        .popular-vehicles-list a {
            text-decoration: none;
            color: #3498db;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .popular-vehicles-list a:hover {
            color: #2980b9;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
