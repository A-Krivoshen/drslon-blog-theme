<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme option: show sidebar on list pages.
 */
function drslon_blog_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'drslon_blog_layout',
		array(
			'title'       => __( 'Blog Layout', 'drslon-blog' ),
			'priority'    => 160,
			'description' => __( 'Controls for post list pages.', 'drslon-blog' ),
		)
	);

	$wp_customize->add_setting(
		'drslon_show_list_sidebar',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);

	$wp_customize->add_control(
		'drslon_show_list_sidebar',
		array(
			'label'   => __( 'Show sidebar on blog list pages', 'drslon-blog' ),
			'section' => 'drslon_blog_layout',
			'type'    => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'drslon_blog_customize_register' );

/**
 * Returns true when sidebar should be visible on post list pages.
 */
function drslon_show_list_sidebar(): bool {
	return (bool) get_theme_mod( 'drslon_show_list_sidebar', true );
}

/**
 * Hide sidebar template-part content for list pages when disabled.
 */
function drslon_maybe_hide_list_sidebar( string $content, array $block ): string {
	$slug = $block['attrs']['slug'] ?? '';

	if ( 'sidebar' !== $slug ) {
		return $content;
	}

	if ( drslon_show_list_sidebar() ) {
		return $content;
	}

	if ( is_home() || is_archive() || is_search() ) {
		return '';
	}

	return $content;
}
add_filter( 'render_block_core/template-part', 'drslon_maybe_hide_list_sidebar', 10, 2 );

/**
 * Body class for sidebar toggle styling.
 */
function drslon_body_classes( array $classes ): array {
	if ( ! drslon_show_list_sidebar() && ( is_home() || is_archive() || is_search() ) ) {
		$classes[] = 'drslon-list-sidebar-disabled';
	}

	return $classes;
}
add_filter( 'body_class', 'drslon_body_classes' );
