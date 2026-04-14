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
