<?php
/**
 * Automatic table of contents for single posts and projects.
 *
 * Injects a nav block at the start of the content and ensures h2–h4
 * have stable id anchors. Skips short notes (< 2 headings).
 *
 * @package drslon-blog-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post types that get an auto TOC.
 *
 * @return list<string>
 */
function drslon_toc_post_types(): array {
	return array( 'post', 'project' );
}

/**
 * Minimum headings required before showing TOC.
 */
function drslon_toc_min_headings(): int {
	return 2;
}

/**
 * Whether the current request should try to inject a TOC.
 */
function drslon_toc_should_run(): bool {
	if ( is_admin() || is_feed() || wp_is_json_request() ) {
		return false;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	if ( ! is_singular( drslon_toc_post_types() ) ) {
		return false;
	}

	return true;
}

/**
 * Build a URL-safe, preferably readable anchor id from heading text.
 */
function drslon_toc_make_base_id( string $text ): string {
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) ?? '' );

	if ( $text === '' ) {
		return 'section';
	}

	$slug = sanitize_title( $text );
	if ( $slug !== '' && $slug !== '0' ) {
		return $slug;
	}

	// Cyrillic / mixed titles often become empty after sanitize_title.
	$slug = mb_strtolower( $text, 'UTF-8' );
	$slug = preg_replace( '/[^\p{L}\p{N}]+/u', '-', $slug ) ?? '';
	$slug = trim( $slug, '-' );

	if ( $slug === '' ) {
		return 'section';
	}

	if ( mb_strlen( $slug ) > 64 ) {
		$slug = rtrim( mb_substr( $slug, 0, 64 ), '-' );
	}

	return $slug;
}

/**
 * Ensure unique id among already used anchors in this content.
 *
 * @param array<string, true> $used
 */
function drslon_toc_unique_id( string $base, array &$used ): string {
	$id = $base !== '' ? $base : 'section';
	$n  = 2;

	while ( isset( $used[ $id ] ) ) {
		$id = $base . '-' . $n;
		++$n;
	}

	$used[ $id ] = true;

	return $id;
}

/**
 * Build a flat ordered list (indent via CSS by heading level).
 * Flat markup avoids invalid nested <ol> when articles skip h3 etc.
 *
 * @param list<array{level:int,id:string,text:string}> $items
 */
function drslon_toc_render_list( array $items ): string {
	if ( $items === array() ) {
		return '';
	}

	$min  = (int) min( array_column( $items, 'level' ) );
	$html = '<ol class="drslon-toc__list">';

	foreach ( $items as $item ) {
		$level = (int) $item['level'];
		$depth = max( 0, $level - $min );

		$html .= sprintf(
			'<li class="drslon-toc__item drslon-toc__item--h%d drslon-toc__item--depth-%d"><a class="drslon-toc__link" href="#%s">%s</a></li>',
			$level,
			$depth,
			esc_attr( $item['id'] ),
			esc_html( $item['text'] )
		);
	}

	$html .= '</ol>';

	return $html;
}

/**
 * Render the TOC nav wrapper.
 *
 * @param list<array{level:int,id:string,text:string}> $items
 */
function drslon_toc_render_nav( array $items ): string {
	$list = drslon_toc_render_list( $items );
	if ( $list === '' ) {
		return '';
	}

	// Open by default when the outline is short.
	$open  = count( $items ) <= 12 ? ' open' : '';
	$label = __( 'Оглавление', 'drslon-blog-theme' );

	return sprintf(
		'<nav class="drslon-toc" aria-label="%1$s">' .
		'<details class="drslon-toc__details"%2$s>' .
		'<summary class="drslon-toc__summary"><span class="drslon-toc__title">%3$s</span><span class="drslon-toc__count">%4$d</span></summary>' .
		'%5$s' .
		'</details>' .
		'</nav>',
		esc_attr( $label ),
		$open,
		esc_html( $label ),
		count( $items ),
		$list
	);
}

/**
 * Add missing ids to h2–h4 and collect TOC items.
 *
 * @return array{0:string,1:list<array{level:int,id:string,text:string}>}
 */
function drslon_toc_process_content( string $content ): array {
	$items = array();
	$used  = array();

	// Pre-seed used ids with any existing id attributes in the content (any element).
	if ( preg_match_all( '/\sid=(["\'])([^"\']+)\1/i', $content, $pre ) ) {
		foreach ( $pre[2] as $existing ) {
			$used[ $existing ] = true;
		}
	}

	$processed = preg_replace_callback(
		'/<h([2-4])(\s[^>]*)?>(.*?)<\/h\1>/is',
		static function ( array $m ) use ( &$items, &$used ) {
			$level = (int) $m[1];
			$attrs = isset( $m[2] ) ? $m[2] : '';
			$inner = $m[3];
			$text  = trim( html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			$text  = preg_replace( '/\s+/u', ' ', $text ) ?? $text;
			$text  = trim( $text );

			if ( $text === '' ) {
				return $m[0];
			}

			$id = '';
			if ( preg_match( '/\sid=(["\'])([^"\']+)\1/i', $attrs, $im ) ) {
				$id = $im[2];
			}

			if ( $id === '' ) {
				$id = drslon_toc_unique_id( drslon_toc_make_base_id( $text ), $used );
				$attrs = rtrim( $attrs ) . ' id="' . esc_attr( $id ) . '"';
			} else {
				$used[ $id ] = true;
			}

			$items[] = array(
				'level' => $level,
				'id'    => $id,
				'text'  => $text,
			);

			return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
		},
		$content
	);

	if ( ! is_string( $processed ) ) {
		return array( $content, array() );
	}

	return array( $processed, $items );
}

/**
 * Inject TOC into the main singular content.
 *
 * @param string $content Post content HTML.
 */
function drslon_auto_toc_content( string $content ): string {
	if ( $content === '' || ! drslon_toc_should_run() ) {
		return $content;
	}

	// Only the main queried post/project — not related cards, widgets, etc.
	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}

	if ( (int) get_queried_object_id() !== (int) $post->ID ) {
		return $content;
	}

	if ( ! in_array( $post->post_type, drslon_toc_post_types(), true ) ) {
		return $content;
	}

	// Already processed (or manual TOC present).
	if ( false !== strpos( $content, 'class="drslon-toc"' ) || false !== strpos( $content, "class='drslon-toc'" ) ) {
		return $content;
	}

	// Password-protected posts before unlock.
	if ( post_password_required( $post ) ) {
		return $content;
	}

	list( $processed, $items ) = drslon_toc_process_content( $content );

	if ( count( $items ) < drslon_toc_min_headings() ) {
		// Still return processed so any ids we added stay (usually none if < 2).
		// Prefer not rewriting content when we won't show TOC — keep original.
		return $content;
	}

	$nav = drslon_toc_render_nav( $items );
	if ( $nav === '' ) {
		return $content;
	}

	return $nav . $processed;
}
add_filter( 'the_content', 'drslon_auto_toc_content', 12 );
