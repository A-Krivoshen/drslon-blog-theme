<?php
/**
 * Body class and layout shell for drslon-site-core shortcode pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes that render full plugin UI on a page.
 *
 * @return string[]
 */
function drslon_plugin_page_shortcodes(): array {
	return array(
		'krv_services_landing',
		'krv_price_list',
		'krv_services_pages_showcase',
		'krv_partners_grid',
		'krv_service_page',
	);
}

/**
 * @param string[] $classes Body classes.
 * @return string[]
 */
function drslon_plugin_page_body_class( array $classes ): array {
	if ( ! is_singular( 'page' ) ) {
		return $classes;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post || $post->post_content === '' ) {
		return $classes;
	}

	foreach ( drslon_plugin_page_shortcodes() as $shortcode ) {
		if ( has_shortcode( $post->post_content, $shortcode ) ) {
			$classes[] = 'drslon-plugin-page';
			break;
		}
	}

	return $classes;
}

add_filter( 'body_class', 'drslon_plugin_page_body_class' );