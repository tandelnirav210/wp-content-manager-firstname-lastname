<?php
class WPCMPL_Frontend {

    private $settings;
    private $cache;
    private $shortcode_used = false;

    public function init() {
        $this->settings = WP_Content_Manager::get_instance()->get_settings();
        $this->cache = WP_Content_Manager::get_instance()->get_cache();

        // Shortcode
        add_shortcode('dynamic_promo', [$this, 'render_shortcode']);

        // Conditional asset loading
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);

        // Add lazy loading to images
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_lazy_loading'], 10, 2);
    }

    public function render_shortcode($atts = []) {
        // Check if feature is enabled
        if (!$this->settings->is_feature_enabled()) {
            return '';
        }

        // Mark shortcode as used for conditional asset loading
        $this->shortcode_used = true;

        // Get promo blocks
        $promo_blocks = $this->get_promo_blocks();

        if (empty($promo_blocks)) {
            return '';
        }

        // Check if AJAX loading is enabled
        if ($this->settings->is_ajax_enabled()) {
            return $this->render_ajax_container();
        }

        // Render directly
        return $this->render_promo_blocks($promo_blocks);
    }

    private function get_promo_blocks() {
        // Try to get from cache first
        $cached = $this->cache->get('promo_blocks');

        if (false !== $cached) {
            return $cached;
        }

        $settings = $this->settings->get_settings();
        $max_blocks = $this->settings->get_max_blocks();
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
        $cache_ttl = $this->settings->get_cache_ttl() * MINUTE_IN_SECONDS;
        $this->cache->set('promo_blocks', $promo_blocks, $cache_ttl);

        return $promo_blocks;
    }

    private function render_promo_blocks($blocks) {
        ob_start();
        ?>
        <div class="wpcmp-promo-blocks">
            <?php foreach ($blocks as $block):
                // Get image alt text if available
                if ($block['image_id']) {
                    $block['image_alt'] = get_post_meta($block['image_id'], '_wp_attachment_image_alt', true);
                }

                // Load template
                $this->load_template('promo-block.php', [
                    'block' => $block,
                    'settings' => $this->settings->get_settings()
                ]);
            endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function load_template($template_name, $args = []) {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        // Look for template in theme first
        $theme_template = locate_template('wpcmp-templates/' . $template_name);

        if ($theme_template) {
            $template_path = $theme_template;
        } else {
            $template_path = WPCMPL_PLUGIN_DIR . 'templates/' . $template_name;
        }

        // Allow filtering of template path
        $template_path = apply_filters('wpcmp_template_path', $template_path, $template_name, $args);

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback to inline rendering
            $this->render_promo_block_fallback($args['block']);
        }
    }

    private function render_promo_block_fallback($block) {
        ?>
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
        <?php
    }

    private function render_ajax_container() {
        ob_start();
        ?>
        <div id="wpcmp-ajax-container"
             data-nonce="<?php echo esc_attr(wp_create_nonce('wpcmp_ajax_nonce')); ?>">
            <div class="wpcmp-loading">
                <?php esc_html_e('Loading promo blocks...', 'wp-content-manager'); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function maybe_enqueue_assets() {
        // Only enqueue if shortcode is used or we're on a page that might have it
        global $post;

        $has_shortcode = false;

        if (is_a($post, 'WP_Post')) {
            $has_shortcode = has_shortcode($post->post_content, 'dynamic_promo');
        }

        if ($this->shortcode_used || $has_shortcode) {
            wp_enqueue_style(
                'wpcmp-frontend-styles',
                WPCMPL_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                WPCMPL_VERSION
            );

            if ($this->settings->is_ajax_enabled()) {
                wp_enqueue_script(
                    'wpcmp-frontend-scripts',
                    WPCMPL_PLUGIN_URL . 'assets/js/frontend.js',
                    ['jquery'],
                    WPCMPL_VERSION,
                    true
                );

                wp_localize_script('wpcmp-frontend-scripts', 'wpcmp_ajax', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wpcmp_ajax_nonce'),
                    'action' => 'get_promo_blocks'
                ]);
            }
        }
    }

    public function add_lazy_loading($attr, $attachment) {
        // Only add to our promo block images
        if (doing_filter('post_thumbnail_html') &&
            get_post_type() === 'promo_block') {
            $attr['loading'] = 'lazy';
        }
        return $attr;
    }
}