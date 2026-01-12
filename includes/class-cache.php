<?php
class WPCMPL_Cache {

    private $cache_group = 'wpcmp_cache';

    public function init() {
        // Clear cache on post save
        add_action('save_post_promo_block', [$this, 'clear_post_cache'], 10, 2);
        add_action('wpcmp_clear_cache', [$this, 'clear_all_cache']);

        // Schedule cache cleanup for expired promos
        add_action('wpcmp_clear_expired_cache', [$this, 'clear_expired_cache']);
        add_action('init', [$this, 'schedule_cache_cleanup']);
    }

    public function get($key) {
        $key = $this->sanitize_key($key);
        return wp_cache_get($key, $this->cache_group);
    }

    public function set($key, $data, $expiration = 0) {
        $key = $this->sanitize_key($key);
        return wp_cache_set($key, $data, $this->cache_group, $expiration);
    }

    public function delete($key) {
        $key = $this->sanitize_key($key);
        return wp_cache_delete($key, $this->cache_group);
    }

    public function clear_all_cache() {
        wp_cache_flush();

        // Also clear transients if used
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_wpcmp_%'
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_wpcmp_%'
            )
        );
    }

    public function clear_post_cache($post_id, $post) {
        if ($post->post_type !== 'promo_block') {
            return;
        }

        // Clear specific cache keys
        $this->delete('promo_blocks');
        $this->delete('promo_blocks_count');

        // Clear REST API cache if exists
        $this->delete('rest_promo_blocks');
    }

    public function clear_expired_cache() {
        // Get all promo blocks and clear cache for expired ones
        $args = [
            'post_type' => 'promo_block',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_wpcmp_expiry_date',
                    'value' => current_time('Y-m-d'),
                    'compare' => '<',
                    'type' => 'DATE'
                ]
            ],
            'fields' => 'ids'
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                // Update cache if needed
                $this->delete('promo_blocks');
            }
        }
    }

    public function schedule_cache_cleanup() {
        if (!wp_next_scheduled('wpcmp_clear_expired_cache')) {
            wp_schedule_event(time(), 'hourly', 'wpcmp_clear_expired_cache');
        }
    }

    private function sanitize_key($key) {
        return sanitize_key('wpcmp_' . $key);
    }
}