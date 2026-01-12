<?php
/**
 * No Promos Found Template
 *
 * @package WP_Content_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$message = isset($args['message']) ? $args['message'] :
    __('No promotional blocks available at this time.', 'wp-content-manager');

$show_admin_link = current_user_can('edit_posts') && apply_filters('wpcmp_show_admin_link_empty', true);
?>

<div class="wpcmp-no-promos">
    <p class="wpcmp-empty-message"><?php echo esc_html($message); ?></p>

    <?php if ($show_admin_link): ?>
        <p class="wpcmp-admin-notice">
            <?php
            printf(
                __('%sAdd promotional blocks%s', 'wp-content-manager'),
                '<a href="' . admin_url('edit.php?post_type=promo_block') . '">',
                '</a>'
            );
            ?>
        </p>
    <?php endif; ?>
</div>