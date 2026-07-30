<?php
/**
 * KRV AI Popup Assistant (web widget on support.krivoshein.site).
 *
 * Loads the shared embed used on service landings. Does not load Replain.
 *
 * @package drslon-blog-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print assistant script in footer (defer + data attributes).
 */
function drslon_krv_assistant_footer(): void {
	if ( is_admin() ) {
		return;
	}

	// Skip REST/XML feeds and embeds.
	if ( is_feed() || is_embed() ) {
		return;
	}

	$src = 'https://support.krivoshein.site/widget/krv-assistant.js?v=20260730j';
	$api = 'https://support.krivoshein.site/api/v1';

	printf(
		"\n<!-- KRV AI Assistant -->\n<script src=\"%s\" defer data-api=\"%s\" data-side=\"right\"></script>\n",
		esc_url( $src ),
		esc_attr( $api )
	);
}
add_action( 'wp_footer', 'drslon_krv_assistant_footer', 99 );
