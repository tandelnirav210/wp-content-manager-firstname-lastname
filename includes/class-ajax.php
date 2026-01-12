<?php
class WPCMPL_Ajax {

    public function init() {
        add_action('wp_ajax_get_promo_blocks', [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_get_promo_blocks', [$this, 'handle_ajax_request']);
    }

    public function handle_ajax_request() {
        // Verify nonce
        if (!isset($_REQUEST['nonce']) ||
            !wp_verify_nonce($_REQUEST['nonce'], 'wpcmp_ajax_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
            wp_die();
        }

        // Check if feature is enabled
        $settings = WP_Content_Manager::get_instance()->get_settings();
        if (!$settings->is_feature_enabled()) {
            wp_send_json_error(['message' => 'Feature disabled'], 403);
            wp_die();
        }

        // Get promo blocks
        $frontend = WP_Content_Manager::get_instance()->get_frontend();
        $promo_blocks = $this->get_promo_blocks_ajax();

        if (empty($promo_blocks)) {
            wp_send_json_success(['html' => '']);
            wp_die();
        }

        // Render HTML
        ob_start();
        ?>
        <div class="wpcmp-promo-blocks">
            <?php foreach ($promo_blocks as $block): ?>
                <div class="wpcmp-promo-block" data-id="<?php echo esc_attr($block['id']); ?>">
                    <?php if ($block['image_url']): ?>
                        <div class="wpcmp-promo-image">
                            <img src="<?php echo esc_url($block['image_url']); ?>"
                                 alt="<?php echo esc_attr($block['title']); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="wpcmp-promo-content">
                        <h3 class="wpcmp-promo-title"><?php echo esc_html($block['title']); ?></h3>
                        <div class="wpcmp-promo-description">
                            <?php echo wp_kses_post(wpautop($block['content'])); ?>
                        </div>

                        <?php if (!empty($block['cta_text']) && !empty($block['cta_url'])): ?>
                            <div class="wpcmp-promo-cta">
                                <a href="<?php echo esc_url($block['cta_url']); ?>"
                                   class="wpcmp-cta-button">
                                    <?php echo esc_html($block['cta_text']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
        wp_die();
    }

    private function get_promo_blocks_ajax() {
        $settings = WP_Content_Manager::get_instance()->get_settings();
        $cache = WP_Content_Manager::get_instance()->get_cache();

        $cache_key = 'ajax_promo_blocks';
        $cached = $cache->get($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $max_blocks = $settings->get_max_blocks();
        $current_date = current_time('Y-m-d');

        $args = [
            'post_type' => 'promo_block',
            'post_status' => 'publish',
            'posts_per_page' => $max_blocks,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_wpcmp_display_priority',
                    'type' => 'NUMERIC'
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_wpcmp_expiry_date',
                        'value' => '',
                        'compare' => '='
                    ],
                    [
                        'key' => '_wpcmp_expiry_date',
                        'value' => $current_date,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ]
                ]
            ],
            'orderby' => [
                'meta_value_num' => 'DESC',
                'date' => 'DESC'
            ],
            'order' => 'DESC'
        ];

        $query = new WP_Query($args);
        $promo_blocks = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $promo_blocks[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'image_id' => get_post_thumbnail_id(),
                    'image_url' => get_the_post_thumbnail_url($post_id, 'medium'),
                    'cta_text' => get_post_meta($post_id, '_wpcmp_cta_text', true),
                    'cta_url' => get_post_meta($post_id, '_wpcmp_cta_url', true),
                    'display_priority' => intval(get_post_meta($post_id, '_wpcmp_display_priority', true)),
                    'expiry_date' => get_post_meta($post_id, '_wpcmp_expiry_date', true)
                ];
            }
            wp_reset_postdata();
        }

        // Cache the results
        $cache_ttl = $settings->get_cache_ttl() * MINUTE_IN_SECONDS;
        $cache->set($cache_key, $promo_blocks, $cache_ttl);

        return $promo_blocks;
    }
}