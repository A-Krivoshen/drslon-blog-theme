<?php
/**
 * Shared contact UI: header MAX chip and footer connect row.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MAX messenger badge (external SVG — no duplicate gradient IDs in DOM).
 */
function drslon_max_icon_markup(): string {
	return sprintf(
		'<img class="drslon-max-icon" src="%s" width="22" height="22" alt="" decoding="async" aria-hidden="true" />',
		esc_url( drslon_theme_asset_uri( 'assets/icons/max-messenger.svg' ) )
	);
}

/**
 * Header MAX link shortcode.
 */
function drslon_header_max_shortcode(): string {
	return sprintf(
		'<a class="drslon-header-max" href="%s" aria-label="%s" title="MAX">%s</a>',
		esc_url( home_url( '/max' ) ),
		esc_attr__( 'MAX — написать в мессенджере', 'drslon-blog' ),
		drslon_max_icon_markup()
	);
}
add_shortcode( 'drslon_header_max', 'drslon_header_max_shortcode' );

/**
 * Footer connect row shortcode.
 */
function drslon_footer_connect_shortcode(): string {
	$telegram_icon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21.543 2.498a1.53 1.53 0 0 0-1.58-.26L3.55 8.617a1.54 1.54 0 0 0 .08 2.893l4.11 1.353 1.59 5.01a1.54 1.54 0 0 0 2.52.66l2.29-2.21 3.78 2.78a1.54 1.54 0 0 0 2.42-.9L21.98 4.01a1.53 1.53 0 0 0-.437-1.512ZM9.33 11.97l8.09-4.98-6.7 6.46-.26 2.76-1.13-4.24Z"/></svg>';
	$email_icon    = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm0 2v.35l8 5.15 8-5.15V6H4Zm16 2.76-7.55 4.86a1 1 0 0 1-1.02 0L4 8.76V18h16V8.76Z"/></svg>';

	return sprintf(
		'<div class="drslon-footer-connect"><p class="drslon-footer-connect__label">%s</p><div class="drslon-footer-connect__row" role="group" aria-label="%s"><a class="drslon-footer-connect__btn drslon-footer-connect__btn--telegram" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s" title="%s">%s</a><a class="drslon-footer-connect__btn drslon-footer-connect__btn--email" href="%s" aria-label="%s" title="%s">%s</a><a class="drslon-footer-connect__btn drslon-footer-connect__btn--max" href="%s" aria-label="%s" title="MAX">%s</a></div></div>',
		esc_html__( 'Связаться', 'drslon-blog' ),
		esc_attr__( 'Контакты', 'drslon-blog' ),
		esc_url( 'https://t.me/DrSlon' ),
		esc_attr__( 'Написать в Telegram', 'drslon-blog' ),
		esc_attr__( 'Написать в Telegram', 'drslon-blog' ),
		$telegram_icon,
		esc_url( 'mailto:aleksey@krivoshein.site' ),
		esc_attr( 'aleksey@krivoshein.site' ),
		esc_attr( 'aleksey@krivoshein.site' ),
		$email_icon,
		esc_url( home_url( '/max' ) ),
		esc_attr__( 'MAX — написать в мессенджере', 'drslon-blog' ),
		drslon_max_icon_markup()
	);
}
add_shortcode( 'drslon_footer_connect', 'drslon_footer_connect_shortcode' );

/**
 * Prevent wpautop from breaking contact shortcodes in template parts.
 */
add_filter( 'render_block', 'drslon_render_contact_shortcode_block', 9, 2 );
function drslon_render_contact_shortcode_block( string $block_content, array $block ): string {
	if ( empty( $block['blockName'] ) || 'core/shortcode' !== $block['blockName'] ) {
		return $block_content;
	}

	$raw = '';

	if ( ! empty( $block['innerContent'][0] ) ) {
		$raw = trim( (string) $block['innerContent'][0] );
	}

	if ( '' === $raw && preg_match( '/\[(drslon_footer_connect|drslon_header_max)\]/', $block_content, $matches ) ) {
		$raw = '[' . $matches[1] . ']';
	}

	if ( ! preg_match( '/^\[(drslon_footer_connect|drslon_header_max)\]$/', $raw ) ) {
		return $block_content;
	}

	return do_shortcode( $raw );
}