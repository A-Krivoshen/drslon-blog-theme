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

require_once get_template_directory() . '/inc/plugin-bridge.php';

$plugin_file = drslon_locate_site_core_file( 'includes/shortcodes/blog-shortcodes.php' );

if ( null !== $plugin_file ) {
	require_once $plugin_file;
	return;
}

$theme_fallback = get_template_directory() . '/inc/legacy-shortcodes.fallback.php';

if ( is_readable( $theme_fallback ) ) {
	require_once $theme_fallback;
}