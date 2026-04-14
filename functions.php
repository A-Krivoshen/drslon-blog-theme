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

/**
 * Featured post card for blog home: sticky first, fallback latest.
 */
function drslon_featured_post_shortcode(): string {
	$sticky_ids = get_option( 'sticky_posts' );
	$sticky_ids = is_array( $sticky_ids ) ? array_map( 'intval', $sticky_ids ) : array();

	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => 1,
	);

	if ( ! empty( $sticky_ids ) ) {
		$args['post__in'] = $sticky_ids;
		$args['orderby']  = 'date';
	} else {
		$args['orderby'] = 'date';
	}

	$query = new WP_Query( $args );

	if ( ! $query->have_posts() ) {
		return '';
	}

	ob_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		?>
		<section class="drslon-featured-post" aria-label="<?php esc_attr_e( 'Featured post', 'drslon-blog' ); ?>">
			<p class="drslon-featured-post__eyebrow"><?php echo ! empty( $sticky_ids ) ? esc_html__( 'Избранный материал', 'drslon-blog' ) : esc_html__( 'Рекомендуем начать с этого', 'drslon-blog' ); ?></p>
			<h2 class="drslon-featured-post__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<p class="drslon-featured-post__meta"><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( get_the_author() ); ?></p>
			<?php if ( has_post_thumbnail() ) : ?>
				<a class="drslon-featured-post__thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large' ); ?></a>
			<?php endif; ?>
			<div class="drslon-featured-post__excerpt"><?php the_excerpt(); ?></div>
		</section>
		<?php
	}
	wp_reset_postdata();

	return (string) ob_get_clean();
}
add_shortcode( 'drslon_featured_post', 'drslon_featured_post_shortcode' );

/**
 * Category tiles section for blog home.
 */
function drslon_category_tiles_shortcode(): string {
	$categories = get_categories(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'number'     => 8,
		)
	);

	if ( empty( $categories ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="drslon-category-tiles" aria-label="<?php esc_attr_e( 'Blog categories', 'drslon-blog' ); ?>">
		<h2 class="drslon-category-tiles__title"><?php esc_html_e( 'Рубрики', 'drslon-blog' ); ?></h2>
		<div class="drslon-category-tiles__grid">
			<?php foreach ( $categories as $category ) : ?>
				<a class="drslon-category-tiles__item" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">
					<span class="drslon-category-tiles__name"><?php echo esc_html( $category->name ); ?></span>
					<span class="drslon-category-tiles__count"><?php echo esc_html( number_format_i18n( (int) $category->count ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php

	return (string) ob_get_clean();
}
add_shortcode( 'drslon_category_tiles', 'drslon_category_tiles_shortcode' );
