<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Featured posts slider for blog home: all sticky posts by latest date, fallback latest posts.
 */
function drslon_featured_post_shortcode(): string {
    $sticky_ids = get_option( 'sticky_posts' );
    $sticky_ids = is_array( $sticky_ids ) ? array_values( array_filter( array_map( 'intval', $sticky_ids ) ) ) : array();

    $args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'ignore_sticky_posts' => 1,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
    );

    if ( ! empty( $sticky_ids ) ) {
        $args['post__in']       = $sticky_ids;
        $args['posts_per_page'] = -1;
    } else {
        $args['posts_per_page'] = 5;
    }

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '';
    }

    $slider_id = 'drslon-featured-slider-' . wp_rand( 1000, 999999 );

    $html  = '<section id="' . esc_attr( $slider_id ) . '" class="drslon-featured-slider" aria-label="' . esc_attr__( 'Featured posts', 'drslon-blog' ) . '">';
    $html .= '<div class="drslon-featured-slider__heading">';
    $html .= '<div>';
    $html .= '<p class="drslon-featured-slider__eyebrow">' . esc_html__( 'Избранные материалы', 'drslon-blog' ) . '</p>';
    $html .= '<p class="drslon-featured-slider__lead">' . esc_html__( 'Закреплённые публикации и важные материалы блога.', 'drslon-blog' ) . '</p>';
    $html .= '</div>';
    $html .= '<div class="drslon-featured-slider__controls" aria-hidden="false">';
    $html .= '<button class="drslon-featured-slider__arrow drslon-featured-slider__arrow--prev" type="button" aria-label="' . esc_attr__( 'Предыдущий материал', 'drslon-blog' ) . '">‹</button>';
    $html .= '<button class="drslon-featured-slider__arrow drslon-featured-slider__arrow--next" type="button" aria-label="' . esc_attr__( 'Следующий материал', 'drslon-blog' ) . '">›</button>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="drslon-featured-slider__frame">';
    $html .= '<div class="drslon-featured-slider__viewport">';

    while ( $query->have_posts() ) {
        $query->the_post();

        $post_id    = get_the_ID();
        $categories = get_the_category( $post_id );
        $lead_cat   = ! empty( $categories ) ? $categories[0] : null;
        $excerpt    = wp_trim_words( get_the_excerpt( $post_id ), 32, '…' );

        $cat_html = '';
        if ( $lead_cat ) {
            $cat_html = '<a class="drslon-featured-slider__category" href="' . esc_url( get_category_link( $lead_cat->term_id ) ) . '">' . esc_html( $lead_cat->name ) . '</a>';
        }

        if ( has_post_thumbnail( $post_id ) ) {
            $media = get_the_post_thumbnail(
                $post_id,
                'large',
                array(
                    'class' => 'drslon-featured-slider__image',
                )
            );
        } else {
            $media = '<span class="drslon-featured-slider__placeholder"></span>';
        }

        $html .= '<article class="drslon-featured-slider__card">';
        $html .= '<a class="drslon-featured-slider__media" href="' . esc_url( get_permalink( $post_id ) ) . '">' . $media . '</a>';
        $html .= '<div class="drslon-featured-slider__content">';
        $html .= '<div class="drslon-featured-slider__meta-line">' . $cat_html . '<span>' . esc_html( get_the_date( '', $post_id ) ) . '</span></div>';
        $html .= '<h2 class="drslon-featured-slider__title"><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></h2>';
        $html .= '<p class="drslon-featured-slider__excerpt">' . esc_html( $excerpt ) . '</p>';
        $html .= '<a class="drslon-featured-slider__button" href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html__( 'Читать материал', 'drslon-blog' ) . '</a>';
        $html .= '</div>';
        $html .= '</article>';
    }

    wp_reset_postdata();

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}
add_shortcode( 'drslon_featured_post', 'drslon_featured_post_shortcode' );

/**
 * Category tiles section for blog home.
 */
function drslon_category_tiles_shortcode(): string {
    $category_slugs = array(
        'linux',
        'instrumenty',
        'wordpress',
        'novosti',
    );

    $category_leads = array(
        'linux'       => 'Linux, серверы и консольная практика.',
        'instrumenty' => 'Утилиты, сервисы и рабочие инструменты.',
        'wordpress'   => 'WordPress, плагины, темы и оптимизация.',
        'novosti'     => 'Технологические новости и важные обновления.',
    );

    $categories = array();

    foreach ( $category_slugs as $slug ) {
        $category = get_category_by_slug( $slug );

        if ( $category && ! is_wp_error( $category ) && (int) $category->count > 0 ) {
            $categories[] = $category;
        }
    }

    if ( empty( $categories ) ) {
        return '';
    }

    $html  = '<section class="drslon-category-tiles" aria-label="' . esc_attr__( 'Blog categories', 'drslon-blog' ) . '">';
    $html .= '<div class="drslon-category-tiles__heading">';
    $html .= '<h2 class="drslon-category-tiles__title">' . esc_html__( 'Популярные разделы', 'drslon-blog' ) . '</h2>';
    $html .= '<p class="drslon-category-tiles__lead">' . esc_html__( 'Быстрый вход в основные темы блога.', 'drslon-blog' ) . '</p>';
    $html .= '</div>';
    $html .= '<div class="drslon-category-tiles__grid">';

    foreach ( $categories as $category ) {
        $tile_query = new WP_Query(
            array(
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => 1,
                'cat'                 => (int) $category->term_id,
                'ignore_sticky_posts' => 1,
                'no_found_rows'       => true,
            )
        );

        $tile_post_id = ! empty( $tile_query->posts[0] ) ? (int) $tile_query->posts[0]->ID : 0;
        $description  = $category_leads[ $category->slug ] ?? '';
        $class_slug   = sanitize_html_class( $category->slug );

        if ( $tile_post_id && has_post_thumbnail( $tile_post_id ) ) {
            $media = get_the_post_thumbnail(
                $tile_post_id,
                'medium_large',
                array(
                    'class' => 'drslon-category-tiles__image',
                )
            );
        } else {
            $media = '<span class="drslon-category-tiles__placeholder"></span>';
        }

        $html .= '<a class="drslon-category-tiles__item drslon-category-tiles__item--' . esc_attr( $class_slug ) . '" href="' . esc_url( get_category_link( $category->term_id ) ) . '">';
        $html .= '<span class="drslon-category-tiles__media">' . $media . '</span>';
        $html .= '<span class="drslon-category-tiles__body">';
        $html .= '<span class="drslon-category-tiles__name">' . esc_html( $category->name ) . '</span>';
        $html .= '<span class="drslon-category-tiles__description">' . esc_html( $description ) . '</span>';
        $html .= '<span class="drslon-category-tiles__count">' . esc_html( number_format_i18n( (int) $category->count ) ) . '</span>';
        $html .= '</span>';
        $html .= '</a>';
    }

    $html .= '</div>';
    $html .= '</section>';

    return $html;
}
add_shortcode( 'drslon_category_tiles', 'drslon_category_tiles_shortcode' );