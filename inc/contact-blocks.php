<?php
/**
 * Shared contact UI: header MAX chip and footer connect row.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MAX messenger badge — same SVG as homepage services landing contacts.
 */
function drslon_max_icon_markup(): string {
	if ( function_exists( 'krv_max_messenger_icon_svg' ) ) {
		return krv_max_messenger_icon_svg( 'drslon-max-icon' );
	}

	return '<svg class="drslon-max-icon" viewBox="7 7 22 22" aria-hidden="true" focusable="false"><path d="M18.1,28.3c-2,0-2.9-0.3-4.4-1.5c-1,1.3-4.2,2.3-4.3,0.6c0-1.3-0.3-2.4-0.6-3.6C8.4,22.4,8,20.8,8,18.4c0-5.7,4.7-10,10.2-10S28,13,28,18.4C27.9,23.9,23.6,28.3,18.1,28.3z M18.2,13.3c-2.7-0.1-4.8,1.7-5.2,4.7c-0.4,2.4,0.3,5.4,0.9,5.5c0.3,0.1,0.9-0.5,1.4-0.9c0.7,0.5,1.5,0.8,2.4,0.9c2.8,0.1,5.2-2,5.4-4.8C23.1,15.9,20.9,13.5,18.2,13.3z"/></svg>';
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
 * Telegram + MAX row for the bottom of the mobile navigation drawer.
 */
function drslon_mobile_menu_contacts_markup(): string {
	$telegram_icon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21.543 2.498a1.53 1.53 0 0 0-1.58-.26L3.55 8.617a1.54 1.54 0 0 0 .08 2.893l4.11 1.353 1.59 5.01a1.54 1.54 0 0 0 2.52.66l2.29-2.21 3.78 2.78a1.54 1.54 0 0 0 2.42-.9L21.98 4.01a1.53 1.53 0 0 0-.437-1.512ZM9.33 11.97l8.09-4.98-6.7 6.46-.26 2.76-1.13-4.24Z"/></svg>';

	return sprintf(
		'<div class="drslon-mobile-menu-contacts" role="group" aria-label="%1$s"><p class="drslon-mobile-menu-contacts__label">%2$s</p><div class="drslon-mobile-menu-contacts__row"><a class="drslon-mobile-menu-contacts__btn drslon-mobile-menu-contacts__btn--telegram" href="%3$s" target="_blank" rel="noopener noreferrer" aria-label="%4$s" title="%4$s">%5$s<span>Telegram</span></a><a class="drslon-mobile-menu-contacts__btn drslon-mobile-menu-contacts__btn--max" href="%6$s" aria-label="%7$s" title="MAX">%8$s<span>MAX</span></a></div></div>',
		esc_attr__( 'Контакты', 'drslon-blog' ),
		esc_html__( 'Связаться', 'drslon-blog' ),
		esc_url( 'https://t.me/drslon_channel' ),
		esc_attr__( 'Telegram-канал', 'drslon-blog' ),
		$telegram_icon,
		esc_url( home_url( '/max' ) ),
		esc_attr__( 'MAX — написать в мессенджере', 'drslon-blog' ),
		drslon_max_icon_markup()
	);
}

/**
 * Append contact chips to the mobile navigation overlay.
 */
add_filter( 'render_block', 'drslon_navigation_append_mobile_contacts', 12, 2 );
function drslon_navigation_append_mobile_contacts( string $block_content, array $block ): string {
	if ( empty( $block['blockName'] ) || 'core/navigation' !== $block['blockName'] ) {
		return $block_content;
	}

	$class_name = (string) ( $block['attrs']['className'] ?? '' );
	if ( strpos( $class_name, 'drslon-main-navigation' ) === false && strpos( $block_content, 'drslon-main-navigation' ) === false ) {
		return $block_content;
	}

	if ( strpos( $block_content, 'drslon-mobile-menu-contacts' ) !== false ) {
		return $block_content;
	}

	$contacts = drslon_mobile_menu_contacts_markup();
	$updated  = preg_replace(
		'#(class="wp-block-navigation__responsive-container-content"[^>]*>\s*<ul[^>]*wp-block-navigation__container[^>]*>.*?</ul>)#s',
		'$1' . $contacts,
		$block_content,
		1,
		$count
	);

	return $count ? $updated : $block_content;
}

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