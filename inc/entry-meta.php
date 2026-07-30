<?php
/**
 * Smart entry meta for posts and projects (no orphan middots).
 *
 * @package drslon-blog-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singular types that show full entry meta (date / reading / views).
 *
 * @return list<string>
 */
function drslon_entry_meta_post_types(): array {
	return array( 'post', 'project' );
}

/**
 * Whether current view is a content singular we care about.
 */
function drslon_is_entry_meta_singular(): bool {
	return is_singular( drslon_entry_meta_post_types() );
}

/**
 * Reading-time label for a post object.
 */
function drslon_entry_meta_reading_label( WP_Post $post ): string {
	$content = wp_strip_all_tags( (string) $post->post_content );
	preg_match_all( '/[\p{L}\p{N}_-]+/u', $content, $matches );

	$words   = ! empty( $matches[0] ) ? count( $matches[0] ) : 0;
	$minutes = (int) floor( $words / 120 );

	if ( $minutes < 1 ) {
		$minutes = 1;
	}

	if ( 1 === $minutes ) {
		return '1 минута чтения';
	}

	if ( $minutes >= 2 && $minutes <= 4 ) {
		return $minutes . ' минуты чтения';
	}

	return $minutes . ' минут чтения';
}

/**
 * View-count label for a post id.
 */
function drslon_entry_meta_views_label( int $post_id ): string {
	$count = (int) get_post_meta( $post_id, 'arkai_post_views', true );

	if ( 0 === $count ) {
		$count = (int) get_post_meta( $post_id, 'post_views_count', true );
	}

	if ( $count % 10 === 1 && $count % 100 !== 11 ) {
		$label = 'просмотр';
	} elseif (
		$count % 10 >= 2 &&
		$count % 10 <= 4 &&
		! in_array( $count % 100, array( 12, 13, 14 ), true )
	) {
		$label = 'просмотра';
	} else {
		$label = 'просмотров';
	}

	return $count . ' ' . $label;
}

/**
 * Build meta pieces for the current post in the loop / singular.
 *
 * @return list<string> HTML fragments (already escaped as needed).
 */
function drslon_entry_meta_pieces( ?WP_Post $post = null ): array {
	$post = $post instanceof WP_Post ? $post : get_post();
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	if ( ! in_array( $post->post_type, drslon_entry_meta_post_types(), true ) ) {
		return array();
	}

	$pieces = array();

	// Date.
	$pieces[] = sprintf(
		'<time class="drslon-entry-meta__date" datetime="%s">%s</time>',
		esc_attr( get_the_date( DATE_W3C, $post ) ),
		esc_html( get_the_date( '', $post ) )
	);

	// Author (posts always; projects if author is set — even without CPT author support).
	$author_id = (int) $post->post_author;
	if ( $author_id > 0 ) {
		$name = get_the_author_meta( 'display_name', $author_id );
		if ( is_string( $name ) && $name !== '' ) {
			$url = get_author_posts_url( $author_id );
			if ( $url ) {
				$pieces[] = sprintf(
					'<a class="drslon-entry-meta__author" href="%s">%s</a>',
					esc_url( $url ),
					esc_html( $name )
				);
			} else {
				$pieces[] = '<span class="drslon-entry-meta__author">' . esc_html( $name ) . '</span>';
			}
		}
	}

	// Categories only for posts (projects have no public taxonomies).
	if ( 'post' === $post->post_type ) {
		$cats = get_the_category( $post->ID );
		if ( is_array( $cats ) && $cats !== array() ) {
			$links = array();
			foreach ( $cats as $cat ) {
				if ( ! $cat instanceof WP_Term ) {
					continue;
				}
				$links[] = sprintf(
					'<a href="%s" rel="tag">%s</a>',
					esc_url( get_category_link( $cat->term_id ) ),
					esc_html( $cat->name )
				);
			}
			if ( $links !== array() ) {
				$pieces[] = '<span class="drslon-entry-meta__terms">' . implode( ', ', $links ) . '</span>';
			}
		}
	}

	// Reading time + views for both post and project.
	$pieces[] = '<span class="drslon-inline-meta drslon-inline-meta--reading-time">' . esc_html( drslon_entry_meta_reading_label( $post ) ) . '</span>';
	$pieces[] = '<span class="drslon-inline-meta drslon-inline-meta--views">' . esc_html( drslon_entry_meta_views_label( (int) $post->ID ) ) . '</span>';

	return $pieces;
}

/**
 * [drslon_entry_meta] — date · author · terms · reading · views (skip empty).
 */
function drslon_entry_meta_shortcode(): string {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return '';
	}

	// In singular templates the main post is available; also allow in loop.
	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	if ( ! in_array( $post->post_type, drslon_entry_meta_post_types(), true ) ) {
		return '';
	}

	// Avoid leaking into unrelated widgets: only main queried object on singular,
	// or any in-loop post of supported type (rare for this shortcode placement).
	if ( is_singular() && (int) get_queried_object_id() !== (int) $post->ID ) {
		return '';
	}

	$pieces = drslon_entry_meta_pieces( $post );
	if ( $pieces === array() ) {
		return '';
	}

	$sep = '<span class="drslon-entry-meta__sep" aria-hidden="true">·</span>';

	return '<span class="drslon-entry-meta">' . implode( $sep, $pieces ) . '</span>';
}
add_shortcode( 'drslon_entry_meta', 'drslon_entry_meta_shortcode' );

/**
 * Keep legacy shortcodes working on projects (if still used elsewhere).
 */
function drslon_entry_meta_override_legacy_shortcodes(): void {
	// Re-register after plugin (plugin loads earlier; theme after).
	remove_shortcode( 'drslon_reading_time' );
	remove_shortcode( 'drslon_post_views' );

	add_shortcode(
		'drslon_reading_time',
		static function (): string {
			if ( ! drslon_is_entry_meta_singular() ) {
				return '';
			}
			$post = get_post();
			if ( ! $post instanceof WP_Post ) {
				return '';
			}
			return '<span class="drslon-inline-meta drslon-inline-meta--reading-time">' . esc_html( drslon_entry_meta_reading_label( $post ) ) . '</span>';
		}
	);

	add_shortcode(
		'drslon_post_views',
		static function (): string {
			if ( ! drslon_is_entry_meta_singular() ) {
				return '';
			}
			$post_id = (int) get_the_ID();
			if ( $post_id <= 0 ) {
				return '';
			}
			return '<span class="drslon-inline-meta drslon-inline-meta--views">' . esc_html( drslon_entry_meta_views_label( $post_id ) ) . '</span>';
		}
	);
}
add_action( 'init', 'drslon_entry_meta_override_legacy_shortcodes', 30 );
