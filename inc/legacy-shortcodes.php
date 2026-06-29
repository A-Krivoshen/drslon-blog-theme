<?php
/**
 * Blog shortcodes bridge — implementation lives in drslon-site-core.
 *
 * @deprecated 0.3.0 Load from plugin includes/shortcodes/blog-shortcodes.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'drslon_featured_post_shortcode' ) ) {
	return;
}

$plugin_file = WP_PLUGIN_DIR . '/drslon-site-core-main/includes/shortcodes/blog-shortcodes.php';

if ( is_readable( $plugin_file ) ) {
	require_once $plugin_file;
	return;
}

$theme_fallback = get_template_directory() . '/inc/legacy-shortcodes.fallback.php';

if ( is_readable( $theme_fallback ) ) {
	require_once $theme_fallback;
}