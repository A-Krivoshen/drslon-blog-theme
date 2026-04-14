<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		$excerpt = wp_trim_words( get_the_excerpt(), 34, '…' );
		?>
		<section class="drslon-featured-post" aria-label="<?php esc_attr_e( 'Featured post', 'drslon-blog' ); ?>">
			<p class="drslon-featured-post__eyebrow"><?php echo ! empty( $sticky_ids ) ? esc_html__( 'Избранный материал', 'drslon-blog' ) : esc_html__( 'Рекомендуем начать с этого', 'drslon-blog' ); ?></p>
			<div class="drslon-featured-post__layout">
				<div class="drslon-featured-post__content">
					<h2 class="drslon-featured-post__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p class="drslon-featured-post__meta"><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( get_the_author() ); ?></p>
					<p class="drslon-featured-post__excerpt"><?php echo esc_html( $excerpt ); ?></p>
					<p class="drslon-featured-post__cta"><a href="<?php the_permalink(); ?>"><?php esc_html_e( 'Читать материал', 'drslon-blog' ); ?></a></p>
				</div>
				<?php if ( has_post_thumbnail() ) : ?>
					<a class="drslon-featured-post__thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large' ); ?></a>
				<?php endif; ?>
			</div>
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
					<?php $description = wp_trim_words( wp_strip_all_tags( (string) $category->description ), 14, '…' ); ?>
					<a class="drslon-category-tiles__item" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">
						<span class="drslon-category-tiles__head">
							<span class="drslon-category-tiles__name"><?php echo esc_html( $category->name ); ?></span>
							<span class="drslon-category-tiles__count"><?php echo esc_html( number_format_i18n( (int) $category->count ) ); ?></span>
						</span>
						<?php if ( ! empty( $description ) ) : ?>
							<span class="drslon-category-tiles__description"><?php echo esc_html( $description ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php

	return (string) ob_get_clean();
}
add_shortcode( 'drslon_category_tiles', 'drslon_category_tiles_shortcode' );
