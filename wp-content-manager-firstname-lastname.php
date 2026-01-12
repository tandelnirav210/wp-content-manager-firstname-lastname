<?php
/**
 * Plugin Name: Custom Content Manager
 * Plugin URI: https://github.com/tandelnirav210/wp-content-manager-firstname-lastname
 * Description: Manage and display promotional blocks with advanced features
 * Version: 1.0.0
 * Author: Nirav Tandel
 * Author URI: https://www.linkedin.com/in/nirav-tandel
 * License: GPL v2 or later
 * Text Domain: wp-content-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPCMPL_VERSION', '1.0.0');
define('WPCMPL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPCMPL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPCMPL_CACHE_KEY', 'wpcmp_promo_blocks_cache');

/**
 * Create classes for every feature and include here.
 */
require_once WPCMPL_PLUGIN_DIR . 'includes/class-ajax.php';
require_once WPCMPL_PLUGIN_DIR . 'includes/class-cache.php';
require_once WPCMPL_PLUGIN_DIR . 'includes/class-frontend.php';
require_once WPCMPL_PLUGIN_DIR . 'includes/class-post-type.php';
require_once WPCMPL_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once WPCMPL_PLUGIN_DIR . 'includes/class-settings.php';


// Initialize plugin
class WP_Content_Manager {

    private static $instance = null;
    private $post_type;
    private $settings;
    private $frontend;
    private $rest_api;
    private $ajax;
    private $cache;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        $this->post_type = new WPCMPL_Post_Type();
        $this->settings = new WPCMPL_Settings();
        $this->frontend = new WPCMPL_Frontend();
        $this->rest_api = new WPCMPL_REST_API();
        $this->ajax = new WPCMPL_Ajax();
        $this->cache = new WPCMPL_Cache();
    }

    private function init_hooks() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('plugins_loaded', [$this, 'init_components']);

        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-content-manager',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function init_components() {
        $this->post_type->init();
        $this->settings->init();
        $this->frontend->init();
        $this->rest_api->init();
        $this->ajax->init();
        $this->cache->init();
    }

    public function activate() {
        // Flush rewrite rules on activation
        flush_rewrite_rules();

        // Set default options
        $defaults = [
            'enable_feature' => '1',
            'max_blocks' => 5,
            'cache_ttl' => 30,
            'ajax_loading' => '0'
        ];

        if (!get_option('wpcmp_settings')) {
            update_option('wpcmp_settings', $defaults);
        }
    }

    public function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('wpcmp_clear_expired_cache');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    // Getters for components
    public function get_post_type() {
        return $this->post_type;
    }

    public function get_settings() {
        return $this->settings;
    }

    public function get_frontend() {
        return $this->frontend;
    }

    public function get_rest_api() {
        return $this->rest_api;
    }

    public function get_ajax() {
        return $this->ajax;
    }

    public function get_cache() {
        return $this->cache;
    }
}

// Initialize the plugin
WP_Content_Manager::get_instance();