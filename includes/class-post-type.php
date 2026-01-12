<?php
class WPCMPL_Post_Type {

    public function init() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes'], 10, 2);
        add_filter('manage_promo_block_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_promo_block_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
        add_filter('manage_edit-promo_block_sortable_columns', [$this, 'make_columns_sortable']);
    }

    public function register_post_type() {
        $labels = [
            'name' => __('Promo Blocks', 'wp-content-manager'),
            'singular_name' => __('Promo Block', 'wp-content-manager'),
            'menu_name' => __('Promo Blocks', 'wp-content-manager'),
            'add_new' => __('Add New', 'wp-content-manager'),
            'add_new_item' => __('Add New Promo Block', 'wp-content-manager'),
            'edit_item' => __('Edit Promo Block', 'wp-content-manager'),
            'new_item' => __('New Promo Block', 'wp-content-manager'),
            'view_item' => __('View Promo Block', 'wp-content-manager'),
            'search_items' => __('Search Promo Blocks', 'wp-content-manager'),
            'not_found' => __('No promo blocks found', 'wp-content-manager'),
            'not_found_in_trash' => __('No promo blocks found in Trash', 'wp-content-manager'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
        ];

        register_post_type('promo_block', $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'wpcmp_promo_details',
            __('Promo Block Details', 'wp-content-manager'),
            [$this, 'render_meta_box'],
            'promo_block',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('wpcmp_save_meta', 'wpcmp_meta_nonce');

        $cta_text = get_post_meta($post->ID, '_wpcmp_cta_text', true);
        $cta_url = get_post_meta($post->ID, '_wpcmp_cta_url', true);
        $display_priority = get_post_meta($post->ID, '_wpcmp_display_priority', true);
        $expiry_date = get_post_meta($post->ID, '_wpcmp_expiry_date', true);

        if (empty($display_priority)) {
            $display_priority = 0;
        }

        ?>
        <div class="wpcmp-meta-field">
            <label for="wpcmp_cta_text"><?php esc_html_e('CTA Text:', 'wp-content-manager'); ?></label>
            <input type="text" id="wpcmp_cta_text" name="wpcmp_cta_text"
                   value="<?php echo esc_attr($cta_text); ?>"
                   class="widefat" />
        </div>

        <div class="wpcmp-meta-field">
            <label for="wpcmp_cta_url"><?php esc_html_e('CTA URL:', 'wp-content-manager'); ?></label>
            <input type="url" id="wpcmp_cta_url" name="wpcmp_cta_url"
                   value="<?php echo esc_url($cta_url); ?>"
                   class="widefat" />
        </div>

        <div class="wpcmp-meta-field">
            <label for="wpcmp_display_priority"><?php esc_html_e('Display Priority:', 'wp-content-manager'); ?></label>
            <input type="number" id="wpcmp_display_priority" name="wpcmp_display_priority"
                   value="<?php echo intval($display_priority); ?>"
                   class="small-text" />
            <p class="description"><?php esc_html_e('Higher numbers appear first. Use negative numbers to push down.', 'wp-content-manager'); ?></p>
        </div>

        <div class="wpcmp-meta-field">
            <label for="wpcmp_expiry_date"><?php esc_html_e('Expiry Date:', 'wp-content-manager'); ?></label>
            <input type="date" id="wpcmp_expiry_date" name="wpcmp_expiry_date"
                   value="<?php echo esc_attr($expiry_date); ?>"
                   class="regular-text" />
            <p class="description"><?php esc_html_e('Leave empty for no expiry', 'wp-content-manager'); ?></p>
        </div>
        <?php
    }

    public function save_meta_boxes($post_id, $post) {
        // Check nonce
        if (!isset($_POST['wpcmp_meta_nonce']) ||
            !wp_verify_nonce($_POST['wpcmp_meta_nonce'], 'wpcmp_save_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save meta fields
        $fields = [
            'wpcmp_cta_text' => 'sanitize_text_field',
            'wpcmp_cta_url' => 'esc_url_raw',
            'wpcmp_display_priority' => 'intval',
            'wpcmp_expiry_date' => 'sanitize_text_field'
        ];

        foreach ($fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize_callback, $_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }

        // Clear cache when promo block is saved
        do_action('wpcmp_clear_cache');
    }

    public function add_custom_columns($columns) {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            if ($key === 'title') {
                $new_columns['display_priority'] = __('Priority', 'wp-content-manager');
                $new_columns['expiry_date'] = __('Expiry Date', 'wp-content-manager');
                $new_columns['status'] = __('Status', 'wp-content-manager');
            }
        }

        return $new_columns;
    }

    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'display_priority':
                $priority = get_post_meta($post_id, '_wpcmp_display_priority', true);
                echo intval($priority);
                break;

            case 'expiry_date':
                $expiry = get_post_meta($post_id, '_wpcmp_expiry_date', true);
                echo $expiry ? esc_html($expiry) : '—';
                break;

            case 'status':
                if ($this->is_expired($post_id)) {
                    echo '<span style="color: #dc3232;">' . esc_html__('Expired', 'wp-content-manager') . '</span>';
                } else {
                    echo '<span style="color: #46b450;">' . esc_html__('Active', 'wp-content-manager') . '</span>';
                }
                break;
        }
    }

    public function make_columns_sortable($columns) {
        $columns['display_priority'] = 'display_priority';
        $columns['expiry_date'] = 'expiry_date';
        return $columns;
    }

    private function is_expired($post_id) {
        $expiry_date = get_post_meta($post_id, '_wpcmp_expiry_date', true);

        if (empty($expiry_date)) {
            return false;
        }

        $expiry_timestamp = strtotime($expiry_date . ' 23:59:59');
        $current_timestamp = current_time('timestamp');

        return $expiry_timestamp < $current_timestamp;
    }
}