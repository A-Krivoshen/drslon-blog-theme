<?php
/**
 * RU | EN for the commercial pages. The blog stays Russian.
 *
 * English pages are children of the page slug "en":
 * /en/home/ /en/services/ /en/price/ /en/consultations/ /en/resume/ /en/contacts/
 * Exact /en/ stays the static brief (nginx serves htdocs/en/index.html).
 *
 * @package drslon-blog-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Russian path => English path. Both end with a slash, home is "/".
 *
 * @return array<string, string>
 */
function drslon_lang_pairs(): array {
	return array(
		'/'               => '/en/home/',
		'/servisy/'       => '/en/services/',
		'/prays-list/'    => '/en/price/',
		'/konsultatsii/'  => '/en/consultations/',
		'/resume/'        => '/en/resume/',
		'/contacts/'      => '/en/contacts/',
	);
}

/**
 * Path of the current public request, or "/" for the front page.
 */
function drslon_request_path(): string {
	if ( is_front_page() ) {
		return '/';
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$path = '/' . trim( $path, '/' );

	return ( '/' === $path ) ? '/' : trailingslashit( $path );
}

/**
 * True on the English commercial pages (children of slug "en").
 */
function drslon_is_en_page(): bool {
	if ( ! is_page() ) {
		return false;
	}

	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	if ( 'en' === $post->post_name ) {
		return true;
	}

	if ( ! $post->post_parent ) {
		return false;
	}

	$parent = get_post( (int) $post->post_parent );

	return $parent instanceof WP_Post && 'en' === $parent->post_name;
}

/**
 * Where RU and EN point from this request.
 *
 * @return array{ru:string,en:string,en_current:bool}
 */
function drslon_lang_urls(): array {
	$path  = drslon_request_path();
	$pairs = drslon_lang_pairs();
	$flip  = array_flip( $pairs );

	if ( isset( $pairs[ $path ] ) ) {
		return array(
			'ru'         => $path,
			'en'         => $pairs[ $path ],
			'en_current' => false,
		);
	}

	if ( isset( $flip[ $path ] ) ) {
		return array(
			'ru'         => $flip[ $path ],
			'en'         => $path,
			'en_current' => true,
		);
	}

	if ( drslon_is_en_page() ) {
		return array(
			'ru'         => '/',
			'en'         => $path,
			'en_current' => true,
		);
	}

	return array(
		'ru'         => $path,
		'en'         => '/en/home/',
		'en_current' => false,
	);
}

/**
 * Russian nav URL => English URL and label. Blog, projects and partners stay on the Russian URL.
 *
 * @return array<string, array{0:string,1:string}>
 */
function drslon_en_nav_map(): array {
	return array(
		'/#uslugi'        => array( '/en/home/#services', 'Services' ),
		'/prays-list/'    => array( '/en/price/', 'Price list' ),
		'/konsultatsii/'  => array( '/en/consultations/', 'Consultations' ),
		'/resume/'        => array( '/en/resume/', 'Resume' ),
		'/project/'       => array( '/project/', 'Projects' ),
		'/partnery/'      => array( '/partnery/', 'Partners' ),
		'/blog/'          => array( '/blog/', 'Blog' ),
		'/servisy/'       => array( '/en/services/', 'Tools' ),
		'/contacts/'      => array( '/en/contacts/', 'Contacts' ),
	);
}

/**
 * Point the RU and EN items at the pair of this page, and translate the menu on English pages.
 *
 * @param string               $html  Block HTML.
 * @param array<string, mixed> $block Block data.
 */
function drslon_lang_render_link( string $html, array $block ): string {
	$name = (string) ( $block['blockName'] ?? '' );
	if ( 'core/navigation-link' !== $name && 'core/navigation-submenu' !== $name ) {
		return $html;
	}

	$class = (string) ( $block['attrs']['className'] ?? '' );
	$urls  = drslon_lang_urls();

	if ( 'core/navigation-link' === $name && ( false !== strpos( $class, 'drslon-lang--ru' ) || false !== strpos( $class, 'drslon-lang--en' ) ) ) {
		$is_en = false !== strpos( $class, 'drslon-lang--en' );
		$url   = $is_en ? $urls['en'] : $urls['ru'];
		$html  = (string) preg_replace( '#href="[^"]*"#', 'href="' . esc_url( $url ) . '"', $html, 1 );
		if ( $is_en === $urls['en_current'] ) {
			$html = (string) preg_replace( '#<a\b#', '<a aria-current="page"', $html, 1 );
		}
		return $html;
	}

	if ( ! drslon_is_en_page() ) {
		return $html;
	}

	$url = (string) ( $block['attrs']['url'] ?? '' );
	$map = drslon_en_nav_map();
	if ( ! isset( $map[ $url ] ) ) {
		return $html;
	}

	$label = (string) ( $block['attrs']['label'] ?? '' );
	$html  = (string) preg_replace( '#href="[^"]*"#', 'href="' . esc_url( $map[ $url ][0] ) . '"', $html, 1 );
	if ( $label !== '' ) {
		$html = str_replace( '>' . $label . '<', '>' . esc_html( $map[ $url ][1] ) . '<', $html );
	}

	return $html;
}
add_filter( 'render_block', 'drslon_lang_render_link', 12, 2 );

/**
 * Price button in the header follows the English price page.
 *
 * @param string               $html  Block HTML.
 * @param array<string, mixed> $block Block data.
 */
function drslon_lang_render_price_button( string $html, array $block ): string {
	if ( ! drslon_is_en_page() || empty( $block['blockName'] ) || 'core/button' !== $block['blockName'] ) {
		return $html;
	}

	$class = (string) ( $block['attrs']['className'] ?? '' );
	if ( false === strpos( $class, 'drslon-header-cta-btn' ) ) {
		return $html;
	}

	$html = str_replace( 'href="/prays-list/"', 'href="' . esc_url( '/en/price/' ) . '"', $html );
	$html = str_replace( '>Прайс<', '>Price<', $html );

	return $html;
}
add_filter( 'render_block', 'drslon_lang_render_price_button', 12, 2 );

/**
 * Hreflang on the paired commercial pages. The front page already prints its own set.
 */
function drslon_lang_hreflang(): void {
	if ( is_admin() || is_front_page() ) {
		return;
	}

	$path  = drslon_request_path();
	$pairs = drslon_lang_pairs();
	$flip  = array_flip( $pairs );
	$ru    = '';
	$en    = '';

	if ( isset( $pairs[ $path ] ) ) {
		$ru = $path;
		$en = $pairs[ $path ];
	} elseif ( isset( $flip[ $path ] ) ) {
		$ru = $flip[ $path ];
		$en = $path;
	}

	if ( '' === $ru || '' === $en ) {
		return;
	}

	echo '<link rel="alternate" hreflang="ru" href="' . esc_url( home_url( $ru ) ) . "\" />\n";
	echo '<link rel="alternate" hreflang="en" href="' . esc_url( home_url( $en ) ) . "\" />\n";
}
add_action( 'wp_head', 'drslon_lang_hreflang', 3 );

/**
 * English commercial pages advertise lang="en". The rest of the site stays Russian.
 *
 * @param string $output language_attributes() output.
 */
function drslon_lang_attributes( string $output ): string {
	if ( ! drslon_is_en_page() ) {
		return $output;
	}

	if ( false !== strpos( $output, 'lang=' ) ) {
		return (string) preg_replace( '/lang="[^"]*"/', 'lang="en"', $output, 1 );
	}

	return trim( $output . ' lang="en"' );
}
add_filter( 'language_attributes', 'drslon_lang_attributes' );

/**
 * English pages only: hide the extra /en/home/ template H1 and translate the shared footer.
 * Footer links are core/html, so the navigation-link rewriter does not see them.
 *
 * @param string               $html  Block HTML.
 * @param array<string, mixed> $block Block data.
 */
function drslon_lang_render_en_page( string $html, array $block ): string {
	if ( ! drslon_is_en_page() ) {
		return $html;
	}

	$name = (string) ( $block['blockName'] ?? '' );

	if ( 'core/search' === $name ) {
		return strtr(
			$html,
			array(
				'Поиск…' => 'Search…',
				'Поиск'  => 'Search',
				'Найти'  => 'Search',
				'Искать' => 'Search',
			)
		);
	}

	if ( 'core/post-title' === $name ) {
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || 'home' !== $post->post_name || ! $post->post_parent ) {
			return $html;
		}

		$parent = get_post( (int) $post->post_parent );
		if ( $parent instanceof WP_Post && 'en' === $parent->post_name ) {
			return '';
		}

		return $html;
	}

	if ( 'core/heading' === $name ) {
		if ( false === strpos( $html, 'drslon-footer-brand-title' ) ) {
			return $html;
		}

		return str_replace( 'ИТ Решения', 'IT Solutions', $html );
	}

	if ( 'core/paragraph' === $name ) {
		$blurb = 'Разработка сайтов, WordPress, Flask, Linux, DevOps и техническая поддержка.';
		if ( $blurb === trim( wp_strip_all_tags( $html ) ) ) {
			return str_replace( $blurb, 'Sites, WordPress, Flask, Linux, DevOps and technical care.', $html );
		}

		$class = (string) ( $block['attrs']['className'] ?? '' );
		if ( false !== strpos( $class, 'drslon-footer-bottom__tagline' ) || false !== strpos( $html, 'drslon-footer-bottom__tagline' ) ) {
			return str_replace( 'Сделано с Linux, кофе и упрямством.', 'Made with Linux, coffee and stubbornness.', $html );
		}

		return $html;
	}

	if ( 'core/html' !== $name || false === strpos( $html, 'drslon-footer-nav' ) ) {
		return $html;
	}

	return strtr(
		$html,
		array(
			'aria-label="Быстрые ссылки"' => 'aria-label="Shortcuts"',
			'>Быстрые ссылки<'            => '>Shortcuts<',
			'href="/#uslugi"'             => 'href="/en/home/#services"',
			'>Услуги<'                    => '>Services<',
			'href="/prays-list/"'         => 'href="/en/price/"',
			'>Прайс-лист<'                => '>Price list<',
			'href="/contacts/"'           => 'href="/en/contacts/"',
			'>Контакты<'                  => '>Contacts<',
			'>Блог<'                      => '>Blog<',
			'href="/servisy/"'            => 'href="/en/services/"',
			'>Сервисы<'                   => '>Tools<',
			'aria-label="Документы"'      => 'aria-label="Documents"',
			'>Документы<'                 => '>Documents<',
			'>Политика ПДн и cookie<'     => '>Privacy and cookies<',
			'>Настройка Cookies<'         => '>Cookie settings<',
			'>Оферта<'                    => '>Public offer<',
			'>Информация о домене<'       => '>Domain summary<',
			'aria-label="Рубрики блога"'  => 'aria-label="Topics"',
			'>Все рубрики<'               => '>All topics<',
			'>Рубрики<'                   => '>Topics<',
			'>Инструменты<'               => '>Tools<',
		)
	);
}
add_filter( 'render_block', 'drslon_lang_render_en_page', 13, 2 );

/**
 * English commercial pages use English core strings (skip link, social labels).
 * Cookie copy is not gettext, so it stays as written.
 */
function drslon_lang_switch_locale(): void {
	if ( drslon_is_en_page() ) {
		switch_to_locale( 'en_US' );
	}
}
add_action( 'wp', 'drslon_lang_switch_locale', 1 );

/**
 * The site name in the tab is the Russian blog name. Keep the same slot in English.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function drslon_lang_document_title( array $parts ): array {
	if ( ! drslon_is_en_page() ) {
		return $parts;
	}

	if ( isset( $parts['site'] ) && is_string( $parts['site'] ) ) {
		$parts['site'] = 'Krivoshein';
	}

	return $parts;
}
add_filter( 'document_title_parts', 'drslon_lang_document_title' );
