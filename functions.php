<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/legacy-compat.php';
require_once get_template_directory() . '/inc/legacy-shortcodes.php';
require_once get_template_directory() . '/inc/admin-sticky.php';
require_once get_template_directory() . '/inc/plugin-page-shell.php';

/**
 * Header Telegram social link: channel (subscribe), not personal DM.
 */
add_filter( 'render_block', 'drslon_header_telegram_channel_a11y', 10, 2 );
function drslon_header_telegram_channel_a11y( $block_content, $block ) {
	if ( empty( $block['blockName'] ) || 'core/social-link' !== $block['blockName'] ) {
		return $block_content;
	}

	if ( empty( $block['attrs']['service'] ) || 'telegram' !== $block['attrs']['service'] ) {
		return $block_content;
	}

	if ( false === strpos( $block_content, 'drslon_channel' ) ) {
		return $block_content;
	}

	if ( false !== strpos( $block_content, 'aria-label=' ) ) {
		return $block_content;
	}

	return preg_replace(
		'/<a\s+/',
		'<a aria-label="Telegram-канал" title="Telegram-канал" ',
		$block_content,
		1
	);
}

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

/* =============================================
   Добавляем класс колонок для страниц рубрик
   ============================================= */
add_filter( 'body_class', 'drslon_category_columns_body_class' );
function drslon_category_columns_body_class( $classes ) {
    if ( is_category() ) {
        $columns = get_theme_mod( 'drslon_category_columns', '2' );
        $classes[] = 'category-columns-' . $columns;
    }
    return $classes;
}

/**
 * Enqueue theme stylesheet shell + CSS components.
 */
add_action( 'wp_enqueue_scripts', function () {
	$theme_dir  = get_template_directory();
	$theme_uri  = get_template_directory_uri();
	$style_path = $theme_dir . '/style.css';
	$version    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_style( 'drslon-blog-theme-style', get_stylesheet_uri(), array(), $version );

	$components = array(
		'drslon-css-base'          => 'assets/css/components/01-base.css',
		'drslon-css-archive'       => 'assets/css/components/02-archive.css',
		'drslon-css-blog-home'     => 'assets/css/components/03-blog-home.css',
		'drslon-css-blog-sections' => 'assets/css/components/04-blog-sections.css',
		'drslon-css-single'        => 'assets/css/components/05-single.css',
		'drslon-css-shell'         => 'assets/css/components/06-shell.css',
		'drslon-css-header-sticky' => 'assets/css/components/07-header-sticky.css',
	);

	$prev = 'drslon-blog-theme-style';

	foreach ( $components as $handle => $relative_path ) {
		$path = $theme_dir . '/' . $relative_path;

		if ( ! file_exists( $path ) ) {
			continue;
		}

		wp_enqueue_style(
			$handle,
			$theme_uri . '/' . $relative_path,
			array( $prev ),
			(string) filemtime( $path )
		);

		$prev = $handle;
	}
}, 20 );

/**
 * Desktop sticky header with compact-on-scroll behavior.
 */
add_action( 'wp_enqueue_scripts', function () {
	$script_path = get_template_directory() . '/assets/js/sticky-header.js';

	if ( ! file_exists( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'drslon-sticky-header',
		get_template_directory_uri() . '/assets/js/sticky-header.js',
		array(),
		(string) filemtime( $script_path ),
		true
	);
}, 25 );

/**
 * Blog featured slider controls.
 */
add_action( 'wp_footer', function () {
	?>
	<script>
	(function () {
		function getCardStep(viewport) {
			const card = viewport.querySelector('.drslon-featured-slider__card');
			if (!card) {
				return viewport.clientWidth;
			}

			const styles = window.getComputedStyle(viewport);
			const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;

			return card.getBoundingClientRect().width + gap;
		}

		function moveSlider(button) {
			const slider = button.closest('.drslon-featured-slider');
			if (!slider) return;

			const viewport = slider.querySelector('.drslon-featured-slider__viewport');
			if (!viewport) return;

			const direction = button.classList.contains('drslon-featured-slider__arrow--prev') ? -1 : 1;
			const step = getCardStep(viewport);
			const maxScroll = viewport.scrollWidth - viewport.clientWidth;

			let nextLeft = viewport.scrollLeft + direction * step;

			if (nextLeft < 0) {
				nextLeft = maxScroll;
			}

			if (nextLeft > maxScroll - 2) {
				nextLeft = 0;
			}

			const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

			viewport.scrollTo({
				left: nextLeft,
				behavior: reduceMotion ? 'auto' : 'smooth'
			});
		}

		document.addEventListener('click', function (event) {
			const button = event.target.closest('.drslon-featured-slider__arrow');
			if (!button) return;

			event.preventDefault();
			event.stopPropagation();

			moveSlider(button);
		});
	})();
	</script>
	<?php
}, 100 );
/**
 * Desktop lightbox for images inside post/project content.
 */
add_action( 'wp_enqueue_scripts', function () {
        if ( ! is_singular( array( 'post', 'project', 'arkai-portfolio' ) ) ) {
                return;
        }

        $script_path = get_template_directory() . '/assets/js/content-lightbox.js';

        if ( ! file_exists( $script_path ) ) {
                return;
        }

        wp_enqueue_script(
                'drslon-content-lightbox',
                get_template_directory_uri() . '/assets/js/content-lightbox.js',
                array(),
                (string) filemtime( $script_path ),
                true
        );
}, 30 );
