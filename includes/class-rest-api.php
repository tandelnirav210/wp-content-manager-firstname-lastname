<?php
class WPCMPL_REST_API {

    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('dcm/v1', '/promos', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_promos'],
                'permission_callback' => [$this, 'get_promos_permissions_check'],
                'args' => [
                    'limit' => [
                        'description' => 'Maximum number of promo blocks to return',
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function($param) {
                            return is_numeric($param) && $param > 0 && $param <= 50;
                        },
                        'default' => 5
                    ]
                ]
            ]
        ]);
    }

    public function get_promos($request) {
        $limit = $request->get_param('limit');
        $settings = WP_Content_Manager::get_instance()->get_settings();

        // Check if feature is enabled
        if (!$settings->is_feature_enabled()) {
            return new WP_Error(
                'feature_disabled',
                __('Promo blocks feature is disabled', 'wp-content-manager'),
                ['status' => 403]
            );
        }

        $cache = WP_Content_Manager::get_instance()->get_cache();
        $cache_key = 'rest_promo_blocks_' . $limit;

        // Try cache first
        $cached = $cache->get($cache_key);

        if (false !== $cached) {
            return rest_ensure_response($cached);
        }

        $current_date = current_time('Y-m-d');

        $args = [
            'post_type' => 'promo_block',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
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
        $promos = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $image_data = null;
                $image_id = get_post_thumbnail_id();

                if ($image_id) {
                    $image_src = wp_get_attachment_image_src($image_id, 'medium');
                    if ($image_src) {
                        $image_data = [
                            'id' => $image_id,
                            'url' => $image_src[0],
                            'width' => $image_src[1],
                            'height' => $image_src[2],
                            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                        ];
                    }
                }

                $promos[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'image' => $image_data,
                    'cta_text' => get_post_meta($post_id, '_wpcmp_cta_text', true),
                    'cta_url' => get_post_meta($post_id, '_wpcmp_cta_url', true),
                    'display_priority' => intval(get_post_meta($post_id, '_wpcmp_display_priority', true)),
                    'expiry_date' => get_post_meta($post_id, '_wpcmp_expiry_date', true),
                    'date' => get_the_date('c'),
                    'modified' => get_the_modified_date('c')
                ];
            }
            wp_reset_postdata();
        }

        // Cache the response
        $cache_ttl = $settings->get_cache_ttl() * MINUTE_IN_SECONDS;
        $cache->set($cache_key, $promos, $cache_ttl);

        return rest_ensure_response($promos);
    }

    public function get_promos_permissions_check($request) {
        // Allow public access to promo blocks
        return true;
    }
}