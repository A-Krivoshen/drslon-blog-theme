<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/legacy-compat.php';
require_once get_template_directory() . '/inc/legacy-shortcodes.php';

/* =============================================
   Настройка количества постов на страницах рубрик
   ============================================= */
add_action( 'customize_register', 'drslon_customize_category_posts' );
function drslon_customize_category_posts( $wp_customize ) {

    $wp_customize->add_section( 'drslon_category_section', array(
        'title'    => 'Страницы рубрик',
        'priority' => 130,
    ));

    $wp_customize->add_setting( 'drslon_posts_per_category', array(
        'default'           => 12,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control( 'drslon_posts_per_category', array(
        'label'       => 'Количество постов на странице рубрики',
        'section'     => 'drslon_category_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 48,
            'step' => 1,
        ),
    ));
}

/* =============================================
   Количество постов на страницах рубрик
   ============================================= */
add_action( 'pre_get_posts', 'drslon_set_posts_per_category' );
function drslon_set_posts_per_category( $query ) {
    if ( ! is_admin() && $query->is_main_query() && is_category() ) {
        $posts_per_page = get_theme_mod( 'drslon_posts_per_category', 12 );
        $query->set( 'posts_per_page', $posts_per_page );
    }
}

/* =============================================
   Выбор количества колонок на страницах рубрик
   ============================================= */
add_action( 'customize_register', 'drslon_customize_category_layout' );
function drslon_customize_category_layout( $wp_customize ) {

    $wp_customize->add_setting( 'drslon_category_columns', array(
        'default'           => '2',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control( 'drslon_category_columns', array(
        'label'    => 'Количество колонок в рубрике',
        'section'  => 'drslon_category_section',
        'type'     => 'select',
        'choices'  => array(
            '1' => '1 колонка',
            '2' => '2 колонки',
        ),
    ));
}
