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
        'orderby'             => 'date',
    );

    if ( ! empty( $sticky_ids ) ) {
        $args['post__in'] = $sticky_ids;
        $args['orderby']  = 'post__in';
    }

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '';
    }

    ob_start();
    while ( $query->have_posts() ) {
        $query->the_post();

        $excerpt    = wp_trim_words( get_the_excerpt(), 34, '…' );
        $categories = get_the_category();
        $lead_cat   = ! empty( $categories ) ? $categories[0] : null;
        ?>
        <section class="drslon-featured-post" aria-label="<?php esc_attr_e( 'Featured post', 'drslon-blog' ); ?>">
            <div class="drslon-featured-post__layout">
                <div class="drslon-featured-post__media">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a class="drslon-featured-post__thumb" href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail( 'large' ); ?>
                        </a>
                    <?php else : ?>
                        <a class="drslon-featured-post__thumb drslon-featured-post__thumb--placeholder" href="<?php the_permalink(); ?>"></a>
                    <?php endif; ?>
                </div>

                <div class="drslon-featured-post__content">
                    <p class="drslon-featured-post__eyebrow">
                        <?php echo ! empty( $sticky_ids ) ? esc_html__( 'Избранный материал', 'drslon-blog' ) : esc_html__( 'Рекомендуем к прочтению', 'drslon-blog' ); ?>
                    </p>

                    <?php if ( $lead_cat ) : ?>
                        <p class="drslon-featured-post__category">
                            <a href="<?php echo esc_url( get_category_link( $lead_cat->term_id ) ); ?>"><?php echo esc_html( $lead_cat->name ); ?></a>
                        </p>
                    <?php endif; ?>

                    <h2 class="drslon-featured-post__title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>

                    <p class="drslon-featured-post__meta">
                        <?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( get_the_author() ); ?>
                    </p>

                    <p class="drslon-featured-post__excerpt"><?php echo esc_html( $excerpt ); ?></p>

                    <p class="drslon-featured-post__cta">
                        <a href="<?php the_permalink(); ?>"><?php esc_html_e( 'Читать материал', 'drslon-blog' ); ?></a>
                    </p>
                </div>
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
    $category_slugs = array(
        'linux',
        'instrumenty',
        'wordpress',
        'novosti',
    );

    $category_leads = array(
        'linux'      => 'Linux, серверы, консоль и всё, что обычно чинится ночью.',
        'instrumenty'=> 'Полезные утилиты, сервисы и рабочие штуки для админа.',
        'wordpress'  => 'WordPress, плагины, темы, оптимизация и разработка.',
        'novosti'    => 'Технологические новости без лишней пены.',
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

    ob_start();
    ?>
    <section class="drslon-category-tiles" aria-label="<?php esc_attr_e( 'Blog categories', 'drslon-blog' ); ?>">
        <div class="drslon-category-tiles__heading">
            <h2 class="drslon-category-tiles__title"><?php esc_html_e( 'Популярные разделы', 'drslon-blog' ); ?></h2>
            <p class="drslon-category-tiles__lead"><?php esc_html_e( 'Быстрый вход в основные темы блога.', 'drslon-blog' ); ?></p>
        </div>

        <div class="drslon-category-tiles__grid">
            <?php foreach ( $categories as $category ) : ?>
                <?php
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
                $description  = $category_leads[ $category->slug ] ?? wp_trim_words( wp_strip_all_tags( (string) $category->description ), 12, '…' );
                $class_slug   = sanitize_html_class( $category->slug );
                ?>
                <a class="drslon-category-tiles__item drslon-category-tiles__item--<?php echo esc_attr( $class_slug ); ?>" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">
                    <span class="drslon-category-tiles__media">
                        <?php if ( $tile_post_id && has_post_thumbnail( $tile_post_id ) ) : ?>
                            <?php echo get_the_post_thumbnail( $tile_post_id, 'medium_large', array( 'class' => 'drslon-category-tiles__image' ) ); ?>
                        <?php else : ?>
                            <span class="drslon-category-tiles__placeholder"></span>
                        <?php endif; ?>
                    </span>

                    <span class="drslon-category-tiles__body">
                        <span class="drslon-category-tiles__head">
                            <span class="drslon-category-tiles__name"><?php echo esc_html( $category->name ); ?></span>
                            <span class="drslon-category-tiles__count"><?php echo esc_html( number_format_i18n( (int) $category->count ) ); ?></span>
                        </span>

                        <span class="drslon-category-tiles__description"><?php echo esc_html( $description ); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php

    return (string) ob_get_clean();
}
add_shortcode( 'drslon_category_tiles', 'drslon_category_tiles_shortcode' );