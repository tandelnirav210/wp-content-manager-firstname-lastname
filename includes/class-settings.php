<?php
class WPCMPL_Settings {

    private $options;

    public function init() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_settings_page() {
        add_options_page(
            __('Dynamic Content Settings', 'wp-content-manager'),
            __('Dynamic Content', 'wp-content-manager'),
            'manage_options',
            'wpcmp-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting(
            'wpcmp_settings_group',
            'wpcmp_settings',
            [$this, 'sanitize_settings']
        );

        add_settings_section(
            'wpcmp_main_section',
            __('Promo Blocks Configuration', 'wp-content-manager'),
            null,
            'wpcmp-settings'
        );

        add_settings_field(
            'enable_feature',
            __('Enable Feature', 'wp-content-manager'),
            [$this, 'render_checkbox_field'],
            'wpcmp-settings',
            'wpcmp_main_section',
            [
                'label_for' => 'enable_feature',
                'description' => __('Enable/disable the promo blocks feature', 'wp-content-manager')
            ]
        );

        add_settings_field(
            'max_blocks',
            __('Maximum Blocks', 'wp-content-manager'),
            [$this, 'render_number_field'],
            'wpcmp-settings',
            'wpcmp_main_section',
            [
                'label_for' => 'max_blocks',
                'description' => __('Maximum number of promo blocks to display', 'wp-content-manager'),
                'min' => 1,
                'max' => 50
            ]
        );

        add_settings_field(
            'cache_ttl',
            __('Cache TTL (minutes)', 'wp-content-manager'),
            [$this, 'render_number_field'],
            'wpcmp-settings',
            'wpcmp_main_section',
            [
                'label_for' => 'cache_ttl',
                'description' => __('Cache time-to-live in minutes', 'wp-content-manager'),
                'min' => 0,
                'max' => 1440
            ]
        );

        add_settings_field(
            'ajax_loading',
            __('AJAX Loading', 'wp-content-manager'),
            [$this, 'render_checkbox_field'],
            'wpcmp-settings',
            'wpcmp_main_section',
            [
                'label_for' => 'ajax_loading',
                'description' => __('Load promo blocks via AJAX after page load', 'wp-content-manager')
            ]
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-content-manager'));
        }

        $this->options = get_option('wpcmp_settings');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('wpcmp_settings_group');
                do_settings_sections('wpcmp-settings');
                submit_button();
                ?>
            </form>

            <div class="wpcmp-settings-info">
                <h3><?php esc_html_e('Usage Instructions', 'wp-content-manager'); ?></h3>
                <p><?php esc_html_e('Shortcode:', 'wp-content-manager'); ?> <code>[dynamic_promo]</code></p>
                <p><?php esc_html_e('REST API Endpoint:', 'wp-content-manager'); ?> <code>/wp-json/dcm/v1/promos</code></p>
            </div>
        </div>
        <?php
    }

    public function render_checkbox_field($args) {
        $value = isset($this->options[$args['label_for']]) ? $this->options[$args['label_for']] : '';
        ?>
        <input type="checkbox"
               id="<?php echo esc_attr($args['label_for']); ?>"
               name="wpcmp_settings[<?php echo esc_attr($args['label_for']); ?>]"
               value="1"
            <?php checked(1, $value, true); ?> />
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    public function render_number_field($args) {
        $value = isset($this->options[$args['label_for']]) ? $this->options[$args['label_for']] : '';
        $min = isset($args['min']) ? $args['min'] : 0;
        $max = isset($args['max']) ? $args['max'] : 100;
        ?>
        <input type="number"
               id="<?php echo esc_attr($args['label_for']); ?>"
               name="wpcmp_settings[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo esc_attr($min); ?>"
               max="<?php echo esc_attr($max); ?>"
               class="small-text" />
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    public function sanitize_settings($input) {
        $sanitized = [];

        // Nonce verification is handled by WordPress settings API

        // Sanitize each field
        $sanitized['enable_feature'] = isset($input['enable_feature']) ? '1' : '0';

        $sanitized['max_blocks'] = isset($input['max_blocks']) ?
            absint($input['max_blocks']) : 5;
        $sanitized['max_blocks'] = max(1, min(50, $sanitized['max_blocks']));

        $sanitized['cache_ttl'] = isset($input['cache_ttl']) ?
            absint($input['cache_ttl']) : 30;
        $sanitized['cache_ttl'] = min(1440, $sanitized['cache_ttl']);

        $sanitized['ajax_loading'] = isset($input['ajax_loading']) ? '1' : '0';

        // Clear cache when settings are updated
        if ($input !== $this->options) {
            do_action('wpcmp_clear_cache');
        }

        return $sanitized;
    }

    public function enqueue_admin_assets($hook) {
        if ('settings_page_wpcmp-settings' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wpcmp-admin-styles',
            WPCMPL_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WPCMPL_VERSION
        );
    }

    public function get_settings() {
        return get_option('wpcmp_settings', []);
    }

    public function is_feature_enabled() {
        $settings = $this->get_settings();
        return isset($settings['enable_feature']) && $settings['enable_feature'] === '1';
    }

    public function get_max_blocks() {
        $settings = $this->get_settings();
        return isset($settings['max_blocks']) ? absint($settings['max_blocks']) : 5;
    }

    public function get_cache_ttl() {
        $settings = $this->get_settings();
        return isset($settings['cache_ttl']) ? absint($settings['cache_ttl']) : 30;
    }

    public function is_ajax_enabled() {
        $settings = $this->get_settings();
        return isset($settings['ajax_loading']) && $settings['ajax_loading'] === '1';
    }
}