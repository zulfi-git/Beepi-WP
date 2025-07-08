<?php
class Popular_Vehicles_Shortcode {
    public function init() {
        add_shortcode('popular_vehicles', array($this, 'render_popular_vehicles_shortcode'));
    }

    public function render_popular_vehicles_shortcode($atts) {
        // Extract attributes with defaults
        $atts = shortcode_atts(array(
            'limit' => 5,
            'title' => 'Mest søkte registreringsnummer',
            'show_count' => 'true',
            'show_status' => 'false',
            'link_to_search' => 'true',
            'search_page' => '/sok'
        ), $atts);

        $limit = max(1, min(20, intval($atts['limit']))); // Between 1-20
        $show_count = $atts['show_count'] === 'true';
        $show_status = $atts['show_status'] === 'true';
        $link_to_search = $atts['link_to_search'] === 'true';
        $search_page = esc_url($atts['search_page']);

        // Get popular searches data
        $popular_searches = $this->get_popular_searches($limit);

        if (empty($popular_searches)) {
            return '<div class="popular-vehicles-empty">Ingen søkedata tilgjengelig ennå.</div>';
        }

        ob_start();
        ?>
        <div class="popular-vehicles-container">
            <?php if (!empty($atts['title'])): ?>
                <h3 class="popular-vehicles-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <div class="popular-vehicles-list">
                <?php foreach ($popular_searches as $index => $search): ?>
                    <div class="popular-vehicle-item">
                        <div class="vehicle-rank"><?php echo $index + 1; ?></div>
                        <div class="vehicle-info">
                            <?php if ($link_to_search): ?>
                                <a href="<?php echo $search_page . '/' . esc_attr($search->registration_number); ?>" 
                                   class="vehicle-reg-link">
                                    <span class="vehicle-reg"><?php echo esc_html($search->registration_number); ?></span>
                                </a>
                            <?php else: ?>
                                <span class="vehicle-reg"><?php echo esc_html($search->registration_number); ?></span>
                            <?php endif; ?>
                            
                            <div class="vehicle-meta">
                                <?php if ($show_count): ?>
                                    <span class="search-count"><?php echo $search->search_count; ?> søk</span>
                                <?php endif; ?>
                                
                                <?php if ($show_status): ?>
                                    <span class="vehicle-status <?php echo $search->has_valid_result ? 'valid' : 'invalid'; ?>">
                                        <?php echo $search->has_valid_result ? '✓ Gyldig' : '✗ Ugyldig'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .popular-vehicles-container {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .popular-vehicles-title {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.2em;
            font-weight: 600;
        }

        .popular-vehicles-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .popular-vehicle-item {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 6px;
            padding: 12px 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .popular-vehicle-item:hover {
            transform: translateY(-1px);
        }

        .vehicle-rank {
            background: #007cba;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .vehicle-info {
            flex: 1;
        }

        .vehicle-reg {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1em;
            color: #333;
        }

        .vehicle-reg-link {
            text-decoration: none;
        }

        .vehicle-reg-link:hover .vehicle-reg {
            color: #007cba;
        }

        .vehicle-meta {
            display: flex;
            gap: 10px;
            margin-top: 4px;
            font-size: 0.9em;
        }

        .search-count {
            color: #666;
        }

        .vehicle-status.valid {
            color: #46b450;
        }

        .vehicle-status.invalid {
            color: #dc3232;
        }

        .popular-vehicles-empty {
            text-align: center;
            color: #666;
            padding: 20px;
            font-style: italic;
        }

        @media (max-width: 480px) {
            .popular-vehicles-container {
                padding: 15px;
            }
            
            .popular-vehicle-item {
                padding: 10px 12px;
            }
            
            .vehicle-meta {
                flex-direction: column;
                gap: 2px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    private function get_popular_searches($limit) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_lookup_logs';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        if (!$table_exists) {
            return array();
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                reg_number as registration_number,
                COUNT(*) as search_count,
                MAX(success) as has_valid_result,
                MAX(lookup_time) as last_searched
            FROM {$table_name}
            WHERE reg_number IS NOT NULL 
            AND reg_number != ''
            AND lookup_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY reg_number
            ORDER BY search_count DESC
            LIMIT %d
        ", $limit));
    }
}
