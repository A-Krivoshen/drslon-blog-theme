<?php
/**
 * Safe front-end asset pruning — keep design/function, cut unused waterfall weight.
 *
 * Rules (conservative):
 * - Never touch admin / logged-in admin-bar needs lightly.
 * - Drop icon fonts not used by any menu item type on this site.
 * - Conditionally drop PDF/video assets unless the current content needs them.
 * - Defer small theme scripts that already wait for DOMContentLoaded.
 *
 * @package drslon-blog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collect singular post content for detection (main query).
 */
function drslon_perf_main_content(): string {
	if ( ! is_singular() ) {
		return '';
	}

	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	return (string) $post->post_content;
}

/**
 * True if content likely needs Embed PDF Viewer assets.
 */
function drslon_perf_needs_pdf( string $content ): bool {
	if ( $content === '' ) {
		return false;
	}

	return (bool) preg_match(
		'/\.pdf\b|embed-pdf|wp:embed-pdf|pdf-embedder|\[pdf/i',
		$content
	);
}

/**
 * True if content likely needs WP Omnivideo assets.
 */
function drslon_perf_needs_omnivideo( string $content ): bool {
	if ( $content === '' ) {
		return false;
	}

	return (bool) preg_match(
		'/omnivideo|\[omni.?video|wp-omnivideo/i',
		$content
	);
}

/**
 * True if Font Awesome classes are used in content/shortcode widgets.
 */
function drslon_perf_needs_fontawesome( string $content ): bool {
	// Consult shortcode partial uses fas/fab icons.
	if ( is_singular() && has_shortcode( $content, 'krv_consult' ) ) {
		return true;
	}
	if ( is_singular() && has_shortcode( $content, 'krv_consultations' ) ) {
		return true;
	}

	if ( $content === '' ) {
		return false;
	}

	return (bool) preg_match( '/\b(fa|fas|far|fal|fab|fad)\s+fa-/', $content )
		|| (bool) preg_match( '/class="[^"]*\bfa[srbld]?\s/', $content );
}

/**
 * True if dashicons appear in content (rare on public pages).
 */
function drslon_perf_needs_dashicons( string $content ): bool {
	if ( is_admin_bar_showing() ) {
		return true;
	}

	if ( $content === '' ) {
		return false;
	}

	return false !== strpos( $content, 'dashicons-' );
}

/**
 * Dequeue unused / conditional front assets late (after plugins).
 */
function drslon_perf_dequeue_assets(): void {
	if ( is_admin() ) {
		return;
	}

	$content = drslon_perf_main_content();

	// --- Always: icon packs never used by menu items on this site (only fa + dashicons exist in meta) ---
	$unused_icon_styles = array(
		'elusive',
		'foundation-icons',
		'genericons',
		// possible alternate handles after menu-icons re-register:
		'menu-icon-elusive',
		'menu-icon-foundation-icons',
		'menu-icon-genericon',
	);
	foreach ( $unused_icon_styles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	// --- PDF viewer: only where PDF is embedded ---
	if ( ! drslon_perf_needs_pdf( $content ) ) {
		wp_dequeue_style( 'embed-pdf-viewer' );
		wp_dequeue_script( 'embed-pdf-viewer' );
	}

	// --- Omnivideo: only 2 posts site-wide use it ---
	if ( ! drslon_perf_needs_omnivideo( $content ) ) {
		wp_dequeue_style( 'wp-omnivideo-style' );
		wp_dequeue_script( 'wp-omnivideo-script' );
	}

	// --- Dashicons: not needed for guests without dashicon markup ---
	if ( ! drslon_perf_needs_dashicons( $content ) ) {
		wp_dequeue_style( 'dashicons' );
	}

	// --- Font Awesome (menu-icons): skip when page has no FA usage ---
	// Consult shortcode needs FA; most shell pages do not.
	if ( ! drslon_perf_needs_fontawesome( $content ) ) {
		foreach ( array( 'font-awesome', 'menu-icon-font-awesome', 'menu-icons-extra' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
	}

	// --- jQuery UI pieces only required by translator: leave translator alone (functional). ---
	// Optional: if translator is disabled on a URL later, can dequeue chain here.
}
add_action( 'wp_enqueue_scripts', 'drslon_perf_dequeue_assets', 100 );

/**
 * Defer non-critical theme scripts (they self-init on DOM ready / load).
 *
 * @param string $tag    Script HTML.
 * @param string $handle Script handle.
 * @param string $src    Script URL.
 */
function drslon_perf_script_loader_tag( string $tag, string $handle, string $src ): string {
	if ( is_admin() ) {
		return $tag;
	}

	$defer_handles = array(
		'drslon-sticky-header',
		'drslon-krv-theme',
		'drslon-featured-slider',
		'drslon-content-lightbox',
	);

	if ( ! in_array( $handle, $defer_handles, true ) ) {
		return $tag;
	}

	if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
		return $tag;
	}

	return str_replace( ' src=', ' defer src=', $tag );
}
add_filter( 'script_loader_tag', 'drslon_perf_script_loader_tag', 10, 3 );
