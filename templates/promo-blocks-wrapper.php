<?php
/**
 * Promo Blocks Wrapper Template
 *
 * @package WP_Content_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$wrapper_classes = ['wpcmp-promo-blocks'];
$wrapper_attributes = '';

// Add layout class
if (isset($args['layout'])) {
    $wrapper_classes[] = 'layout-' . esc_attr($args['layout']);
}

// Add grid class based on number of columns
$columns = isset($args['columns']) ? absint($args['columns']) : 3;
$wrapper_classes[] = 'columns-' . $columns;

// Filter classes
$wrapper_classes = apply_filters('wpcmp_wrapper_classes', $wrapper_classes, $args);
$class_string = implode(' ', array_map('esc_attr', $wrapper_classes));

// Data attributes for AJAX
if (isset($args['ajax_enabled']) && $args['ajax_enabled']) {
    $wrapper_attributes = sprintf(
        ' data-ajax="true" data-nonce="%s"',
        esc_attr(wp_create_nonce('wpcmp_ajax_nonce'))
    );
}
?>

<div class="<?php echo $class_string; ?>"<?php echo $wrapper_attributes; ?>>
    <?php
    // Content will be loaded here - either directly or via AJAX
    if (!empty($args['content'])) {
        echo $args['content'];
    } elseif (isset($args['ajax_enabled']) && $args['ajax_enabled']) {
        // Show loading state for AJAX
        ?>
        <div class="wpcmp-loading">
            <span class="spinner"></span>
            <?php esc_html_e('Loading promotional content...', 'wp-content-manager'); ?>
        </div>
        <?php
    }
    ?>
</div>