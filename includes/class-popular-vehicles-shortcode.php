<?php
class Popular_Vehicles_Shortcode {
    public function init() {
        add_shortcode('popular_vehicles', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'title' => 'Mest søkte biler'
        ), $atts);

        $popular_searches = $this->get_popular_searches($atts['limit']);

        if (empty($popular_searches)) {
            return '';
        }

        ob_start();
        ?>
        <div class="popular-vehicles-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="popular-vehicles-grid">
                <?php foreach ($popular_searches as $search): ?>
                    <a href="/sok/<?php echo esc_attr($search->reg_number); ?>" class="popular-vehicle-card">
                        <div class="reg-number"><?php echo esc_html($search->reg_number); ?></div>
                        <div class="search-count"><?php echo $search->search_count; ?> søk</div>
                        <?php if ($search->vehicle_info): ?>
                            <div class="vehicle-preview"><?php echo esc_html($search->vehicle_info); ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .popular-vehicles-container {
            margin: 2rem 0;
        }

        .popular-vehicles-container h3 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #0f172a;
            font-size: 1.25rem;
        }

        .popular-vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .popular-vehicle-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            transition: all 0.2s ease;
            text-align: center;
        }

        .popular-vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #3b82f6;
        }

        .reg-number {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e40af;
            margin-bottom: 0.5rem;
        }

        .search-count {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .vehicle-preview {
            font-size: 0.8rem;
            color: #374151;
            font-weight: 500;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    private function get_popular_searches($limit) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        // Validate and sanitize limit parameter
        $limit = absint($limit);
        if ($limit < 1 || $limit > 100) {
            $limit = 5; // Safe default
        }

        // Use safer separate queries to avoid complex subquery injection risks
        $popular_regs = $wpdb->get_results($wpdb->prepare("
            SELECT 
                reg_number,
                COUNT(*) as search_count,
                MAX(lookup_time) as last_searched
            FROM `{$table_name}`
            WHERE reg_number IS NOT NULL 
            AND reg_number != ''
            AND success = 1
            AND lookup_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY reg_number
            ORDER BY search_count DESC
            LIMIT %d
        ", $limit));

        // Safely get vehicle info for each registration number
        foreach ($popular_regs as $search) {
            $vehicle_info = $wpdb->get_var($wpdb->prepare("
                SELECT CONCAT(
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(response_data, '$.responser[0].kjoretoydata.godkjenning.tekniskGodkjenning.tekniskeData.generelt.merke[0].merke')), ''),
                    ' ',
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(response_data, '$.responser[0].kjoretoydata.godkjenning.tekniskGodkjenning.tekniskeData.generelt.handelsbetegnelse[0]')), '')
                ) FROM `{$table_name}`
                WHERE reg_number = %s
                AND success = 1 
                AND response_data IS NOT NULL
                ORDER BY lookup_time DESC 
                LIMIT 1
            ", $search->reg_number));

            $search->vehicle_info = $vehicle_info;
        }

        return $popular_regs;
    }
}