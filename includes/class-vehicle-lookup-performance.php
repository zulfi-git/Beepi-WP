<?php
/**
 * Performance Optimization Handler for Vehicle Lookup
 * 
 * Handles caching headers, resource optimization, and lazy loading
 * for improved page load performance.
 */
class Vehicle_Lookup_Performance {
    
    /**
     * Initialize performance optimizations
     */
    public function init() {
        // Add cache headers for vehicle pages
        add_action('template_redirect', array($this, 'add_cache_headers'), 1);
        
        // Add lazy loading to images
        add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_loading'), 10, 2);
        
        // Add preconnect for external resources
        add_action('wp_head', array($this, 'add_resource_hints'), 1);
        
        // Defer non-critical JavaScript
        add_filter('script_loader_tag', array($this, 'defer_scripts'), 10, 3);
        
        // Add cache control for static assets
        add_action('init', array($this, 'setup_static_cache'));
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
     * Add cache headers for vehicle pages
     */
    public function add_cache_headers() {
        if (!$this->is_vehicle_page()) {
            return;
        }
        
        // Check if we have a registration number
        $reg_number = get_query_var('reg_number');
        
        if (!empty($reg_number)) {
            // Vehicle-specific pages can be cached longer
            // Cache for 1 hour in browser, 12 hours on CDN
            header('Cache-Control: public, max-age=3600, s-maxage=43200');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
            
            // Add ETag for efficient revalidation
            $etag = md5($reg_number . '-' . date('Y-m-d-H'));
            header('ETag: "' . $etag . '"');
            
            // Handle If-None-Match for 304 responses
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
                trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        } else {
            // Search page with shorter cache
            header('Cache-Control: public, max-age=600, s-maxage=3600');
        }
        
        // Add Vary header for proper caching
        header('Vary: Accept-Encoding');
    }
    
    /**
     * Add lazy loading to images
     */
    public function add_lazy_loading($attr, $attachment) {
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }
        
        return $attr;
    }
    
    /**
     * Add resource hints for external domains
     */
    public function add_resource_hints() {
        if (!$this->is_vehicle_page()) {
            return;
        }
        
        // Preconnect to Cloudflare Worker API
        echo '<link rel="preconnect" href="https://lookup.beepi.no" crossorigin />' . "\n";
        
        // DNS prefetch for other external resources
        echo '<link rel="dns-prefetch" href="//lookup.beepi.no" />' . "\n";
        
        // Preconnect to image CDN if used
        echo '<link rel="dns-prefetch" href="//beepi.no" />' . "\n";
    }
    
    /**
     * Defer non-critical JavaScript
     */
    public function defer_scripts($tag, $handle, $src) {
        // List of scripts that should be deferred
        $defer_scripts = array(
            'vehicle-lookup-script',
            'normalize-plate'
        );
        
        // Don't defer if it's already async or has defer
        if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
            return $tag;
        }
        
        // Add defer to our scripts
        if (in_array($handle, $defer_scripts)) {
            return str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Setup caching for static assets
     */
    public function setup_static_cache() {
        // This is handled by .htaccess or server config typically
        // But we can add headers programmatically if needed
        
        // Add filter to modify script/style URLs with cache busting
        add_filter('style_loader_src', array($this, 'add_cache_buster'), 10, 2);
        add_filter('script_loader_src', array($this, 'add_cache_buster'), 10, 2);
    }
    
    /**
     * Add cache busting parameter to assets
     * Note: This is already handled by WordPress version parameter,
     * but we can add additional logic if needed
     */
    public function add_cache_buster($src, $handle) {
        // Check if it's our plugin asset
        if (strpos($src, 'vehicle-lookup') === false) {
            return $src;
        }
        
        // WordPress already adds version, so we just return as-is
        // If we needed custom cache busting, we'd modify here
        return $src;
    }
    
    /**
     * Get recommendations for .htaccess optimization
     */
    public static function get_htaccess_recommendations() {
        return '
# Beepi Vehicle Lookup Performance Optimization
# Add these rules to your .htaccess file for better performance

<IfModule mod_expires.c>
    ExpiresActive On
    
    # CSS and JavaScript
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType application/x-javascript "access plus 1 year"
    
    # Images
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    
    # Fonts
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType application/font-woff "access plus 1 year"
    ExpiresByType application/font-woff2 "access plus 1 year"
</IfModule>

<IfModule mod_deflate.c>
    # Compress HTML, CSS, JavaScript, Text, XML
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE application/xml
</IfModule>

<IfModule mod_headers.c>
    # Add Vary header for proper caching
    <FilesMatch "\.(css|js|html|htm)$">
        Header set Vary "Accept-Encoding"
    </FilesMatch>
    
    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
';
    }
}
