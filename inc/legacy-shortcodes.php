<?php
/**
 * Blog shortcode fallback when drslon-site-core is not active.
 *
 * The active plugin loads before the theme and registers these functions first.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'drslon_featured_post_shortcode' ) ) {
	return;
}

$theme_fallback = get_template_directory() . '/inc/legacy-shortcodes.fallback.php';

if ( is_readable( $theme_fallback ) ) {
	require_once $theme_fallback;
}
