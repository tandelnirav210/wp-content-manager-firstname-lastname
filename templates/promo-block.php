<?php
/**
 * Promo Block Template
 *
 * This template can be overridden by creating a template file in your theme:
 * your-theme/wpcmp-templates/promo-block.php
 *
 * @package WP_Content_Manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Extract variables passed from the renderer
$block = isset($args['block']) ? $args['block'] : [];
$settings = isset($args['settings']) ? $args['settings'] : [];

// Default values
$default_block = [
    'id' => 0,
    'title' => '',
    'content' => '',
    'excerpt' => '',
    'image_id' => 0,
    'image_url' => '',
    'image_alt' => '',
    'cta_text' => '',
    'cta_url' => '',
    'display_priority' => 0,
    'expiry_date' => '',
    'date' => '',
    'modified' => ''
];

$block = wp_parse_args($block, $default_block);

// Don't output if no title or content
if (empty($block['title']) && empty($block['content'])) {
    return;
}

// Check if block is expired (should be filtered already, but double-check)
if (!empty($block['expiry_date'])) {
    $expiry_timestamp = strtotime($block['expiry_date'] . ' 23:59:59');
    $current_timestamp = current_time('timestamp');

    if ($expiry_timestamp < $current_timestamp) {
        return;
    }
}

// CSS Classes
$classes = ['wpcmp-promo-block'];
$classes[] = 'wpcmp-promo-block-' . $block['id'];
$classes[] = $block['image_url'] ? 'has-image' : 'no-image';
$classes[] = (!empty($block['cta_text']) && !empty($block['cta_url'])) ? 'has-cta' : 'no-cta';

// Add custom classes based on settings
if (isset($settings['layout'])) {
    $classes[] = 'layout-' . esc_attr($settings['layout']);
}

// Filter classes
$classes = apply_filters('wpcmp_promo_block_classes', $classes, $block);
$class_string = implode(' ', array_map('esc_attr', $classes));

// Filter the block data before rendering
$block = apply_filters('wpcmp_promo_block_data', $block);

// Start output
?>
    <div class="<?php echo $class_string; ?>"
         data-id="<?php echo esc_attr($block['id']); ?>"
         data-priority="<?php echo esc_attr($block['display_priority']); ?>"
        <?php if (!empty($block['expiry_date'])): ?>
            data-expiry="<?php echo esc_attr($block['expiry_date']); ?>"
        <?php endif; ?>>

        <?php
        // Image section
        if ($block['image_url']):
            $image_alt = !empty($block['image_alt']) ? $block['image_alt'] : $block['title'];
            $image_classes = ['wpcmp-promo-image'];

            // Add lazy loading class
            if (apply_filters('wpcmp_enable_lazy_load', true)) {
                $image_classes[] = 'lazy-load';
            }

            $image_classes = apply_filters('wpcmp_promo_image_classes', $image_classes, $block);
            ?>
            <div class="<?php echo esc_attr(implode(' ', $image_classes)); ?>">
                <?php
                // Allow override with custom image markup
                if (has_filter('wpcmp_promo_image_markup')) {
                    echo apply_filters('wpcmp_promo_image_markup', $block['image_url'], $block);
                } else {
                    ?>
                    <img src="<?php echo esc_url($block['image_url']); ?>"
                         alt="<?php echo esc_attr($image_alt); ?>"
                        <?php if (apply_filters('wpcmp_enable_lazy_load', true)): ?>
                            loading="lazy"
                        <?php endif; ?>
                         class="wpcmp-promo-img" />

                    <?php
                    // Optional: Add responsive image sizes
                    if (apply_filters('wpcmp_enable_responsive_images', false) && $block['image_id']) {
                        echo wp_get_attachment_image(
                            $block['image_id'],
                            'medium',
                            false,
                            [
                                'class' => 'wpcmp-promo-img-responsive',
                                'loading' => apply_filters('wpcmp_enable_lazy_load', true) ? 'lazy' : false,
                                'alt' => esc_attr($image_alt)
                            ]
                        );
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="wpcmp-promo-content">
            <?php
            // Title
            if (!empty($block['title'])):
            $title_tag = apply_filters('wpcmp_promo_title_tag', 'h3');
            $title_classes = ['wpcmp-promo-title'];
            $title_classes = apply_filters('wpcmp_promo_title_classes', $title_classes, $block);
            ?>
            <<?php echo tag_escape($title_tag); ?>
            class="<?php echo esc_attr(implode(' ', $title_classes)); ?>">
            <?php echo esc_html($block['title']); ?>
        </<?php echo tag_escape($title_tag); ?>>
    <?php endif; ?>

        <?php
        // Content/Description
        if (!empty($block['content'])):
            $content_classes = ['wpcmp-promo-description'];
            $content_classes = apply_filters('wpcmp_promo_content_classes', $content_classes, $block);
            ?>
            <div class="<?php echo esc_attr(implode(' ', $content_classes)); ?>">
                <?php
                // Apply content filters similar to the_content
                $content = apply_filters('wpcmp_promo_content', $block['content'], $block);
                echo wp_kses_post(wpautop($content));
                ?>
            </div>
        <?php endif; ?>

        <?php
        // Call to Action
        if (!empty($block['cta_text']) && !empty($block['cta_url'])):
            $cta_classes = ['wpcmp-cta-button'];
            $cta_classes = apply_filters('wpcmp_cta_button_classes', $cta_classes, $block);

            // Add rel attributes for external links
            $cta_url = esc_url($block['cta_url']);
            $rel_attr = '';

            if (apply_filters('wpcmp_add_nofollow_external', true)) {
                $site_url = site_url();
                if (strpos($cta_url, $site_url) === false) {
                    $rel_attr = 'rel="nofollow noopener"';
                }
            }

            // Open in new tab for external links
            $target_attr = '';
            if (apply_filters('wpcmp_open_external_new_tab', true)) {
                if (strpos($cta_url, site_url()) === false) {
                    $target_attr = 'target="_blank"';
                }
            }
            ?>
            <div class="wpcmp-promo-cta">
                <a href="<?php echo $cta_url; ?>"
                   class="<?php echo esc_attr(implode(' ', $cta_classes)); ?>"
                    <?php echo $rel_attr; ?>
                    <?php echo $target_attr; ?>>
                    <?php echo esc_html($block['cta_text']); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php
        // Additional meta information (optional)
        if (apply_filters('wpcmp_show_meta_info', false)):
            ?>
            <div class="wpcmp-promo-meta">
                <?php if (!empty($block['date'])): ?>
                    <span class="wpcmp-promo-date">
                        <?php
                        echo sprintf(
                            esc_html__('Published: %s', 'wp-content-manager'),
                            date_i18n(get_option('date_format'), strtotime($block['date']))
                        );
                        ?>
                    </span>
                <?php endif; ?>

                <?php if (!empty($block['expiry_date'])): ?>
                    <span class="wpcmp-promo-expiry">
                        <?php
                        echo sprintf(
                            esc_html__('Expires: %s', 'wp-content-manager'),
                            date_i18n(get_option('date_format'), strtotime($block['expiry_date']))
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    </div>

<?php
// Hook after promo block output
do_action('wpcmp_after_promo_block', $block);