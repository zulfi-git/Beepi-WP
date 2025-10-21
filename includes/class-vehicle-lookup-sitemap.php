<?php
/**
 * Sitemap Generator for Vehicle Lookup Pages
 * 
 * Generates XML sitemaps for popular and recently searched vehicle pages
 * to improve search engine indexing.
 */
class Vehicle_Lookup_Sitemap {
    
    /**
     * Initialize sitemap functionality
     */
    public function init() {
        // Add custom endpoint for vehicle sitemap
        add_action('init', array($this, 'add_sitemap_rewrite_rules'));
        
        // Handle sitemap requests
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
        
        // Integrate with WordPress core sitemaps (WP 5.5+)
        if (function_exists('wp_sitemaps_get_server')) {
            add_filter('wp_sitemaps_add_provider', array($this, 'add_vehicle_sitemap_provider'), 10, 2);
        }
    }
    
    /**
     * Add rewrite rules for sitemap
     */
    public function add_sitemap_rewrite_rules() {
        add_rewrite_rule(
            '^vehicle-sitemap\.xml$',
            'index.php?vehicle_sitemap=1',
            'top'
        );
        
        add_rewrite_tag('%vehicle_sitemap%', '([0-9]+)');
    }
    
    /**
     * Handle sitemap requests
     */
    public function handle_sitemap_request() {
        if (get_query_var('vehicle_sitemap')) {
            $this->generate_sitemap();
            exit;
        }
    }
    
    /**
     * Get popular/recent vehicle registration numbers for sitemap
     */
    private function get_sitemap_vehicles($limit = 1000) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        // Get most frequently searched vehicles from the last 30 days
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT reg_number, MAX(lookup_time) as last_lookup, COUNT(*) as lookup_count
            FROM {$table_name}
            WHERE success = 1 
            AND lookup_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY reg_number
            ORDER BY lookup_count DESC, last_lookup DESC
            LIMIT %d",
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Generate XML sitemap
     */
    public function generate_sitemap() {
        header('Content-Type: application/xml; charset=utf-8');
        
        $vehicles = $this->get_sitemap_vehicles();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add main search page
        echo '  <url>' . "\n";
        echo '    <loc>' . esc_url(home_url('/sok')) . '</loc>' . "\n";
        echo '    <changefreq>daily</changefreq>' . "\n";
        echo '    <priority>1.0</priority>' . "\n";
        echo '  </url>' . "\n";
        
        // Add individual vehicle pages
        foreach ($vehicles as $vehicle) {
            $url = home_url('/sok/' . urlencode($vehicle->reg_number));
            $last_mod = date('c', strtotime($vehicle->last_lookup));
            
            // Calculate priority based on lookup count
            $priority = min(0.8, 0.5 + ($vehicle->lookup_count / 100));
            
            echo '  <url>' . "\n";
            echo '    <loc>' . esc_url($url) . '</loc>' . "\n";
            echo '    <lastmod>' . esc_html($last_mod) . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>' . number_format($priority, 1) . '</priority>' . "\n";
            echo '  </url>' . "\n";
        }
        
        echo '</urlset>';
    }
    
    /**
     * Add vehicle sitemap provider to WordPress core sitemaps
     */
    public function add_vehicle_sitemap_provider($provider, $name) {
        if ('vehicles' === $name) {
            $provider = new Vehicle_Lookup_Sitemap_Provider();
        }
        
        return $provider;
    }
}

/**
 * Sitemap Provider for WordPress Core Sitemaps (WP 5.5+)
 */
class Vehicle_Lookup_Sitemap_Provider extends WP_Sitemaps_Provider {
    
    /**
     * Get the name of the sitemap provider
     */
    public function get_name() {
        return 'vehicles';
    }
    
    /**
     * Get the URL list for the sitemap
     */
    public function get_url_list($page_num, $post_type = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        $per_page = 2000;
        $offset = ($page_num - 1) * $per_page;
        
        // Get vehicles for this page
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT reg_number, MAX(lookup_time) as last_lookup, COUNT(*) as lookup_count
            FROM {$table_name}
            WHERE success = 1 
            AND lookup_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY reg_number
            ORDER BY lookup_count DESC, last_lookup DESC
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $url_list = array();
        
        foreach ($results as $vehicle) {
            $url_list[] = array(
                'loc' => home_url('/sok/' . urlencode($vehicle->reg_number)),
                'lastmod' => date('c', strtotime($vehicle->last_lookup)),
            );
        }
        
        return $url_list;
    }
    
    /**
     * Get the maximum number of pages
     */
    public function get_max_num_pages($post_type = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';
        
        $count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT reg_number)
            FROM {$table_name}
            WHERE success = 1 
            AND lookup_time > DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        return ceil($count / 2000);
    }
}
