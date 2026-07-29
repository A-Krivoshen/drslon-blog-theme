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

	// jQuery UI + translator stay registered (language switcher is functional).
	// They are deferred below so they no longer block first paint.

	// --- jQuery Migrate: not needed for modern guest front-end (saves ~14KB). ---
	// Keep for logged-in users (admin bar / legacy editor paths more likely to need it).
	if ( ! is_user_logged_in() ) {
		wp_dequeue_script( 'jquery-migrate' );
		$wp_scripts = wp_scripts();
		if ( isset( $wp_scripts->registered['jquery'] ) ) {
			$wp_scripts->registered['jquery']->deps = array_values(
				array_diff( $wp_scripts->registered['jquery']->deps, array( 'jquery-migrate' ) )
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'drslon_perf_dequeue_assets', 100 );

/**
 * Prefer compact site-logo size (~156px) instead of full 1066px PNG in header/footer.
 *
 * @param array|false  $image         Image data.
 * @param int          $attachment_id Attachment ID.
 * @param string|int[] $size          Requested size.
 * @return array|false
 */
function drslon_perf_logo_image_src( $image, int $attachment_id, $size ) {
	$logo_id = (int) get_option( 'site_logo' );
	if ( $logo_id <= 0 || $attachment_id !== $logo_id ) {
		return $image;
	}

	// Block/custom-logo often request "full" even when displayed at 52px.
	if ( $size !== 'full' && $size !== 'post-thumbnail' && ! ( is_array( $size ) && isset( $size[0] ) && (int) $size[0] > 200 ) ) {
		return $image;
	}

	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( empty( $meta['sizes']['drslon-site-logo']['file'] ) ) {
		return $image;
	}

	$base = trailingslashit( dirname( wp_get_attachment_url( $attachment_id ) ) );
	$file = $meta['sizes']['drslon-site-logo']['file'];
	$w    = (int) $meta['sizes']['drslon-site-logo']['width'];
	$h    = (int) $meta['sizes']['drslon-site-logo']['height'];

	return array( $base . $file, $w, $h, true );
}
add_filter( 'wp_get_attachment_image_src', 'drslon_perf_logo_image_src', 10, 3 );

/**
 * Eager LCP-friendly attributes for site logo; avoid lazy placeholder fight.
 *
 * @param array   $attr       Attributes.
 * @param WP_Post $attachment Attachment.
 * @param mixed   $size       Size.
 * @return array
 */
function drslon_perf_logo_image_attr( array $attr, $attachment, $size ): array {
	$logo_id = (int) get_option( 'site_logo' );
	if ( $logo_id <= 0 || ! $attachment instanceof WP_Post || (int) $attachment->ID !== $logo_id ) {
		return $attr;
	}

	$attr['loading']       = 'eager';
	$attr['fetchpriority'] = 'high';
	$attr['decoding']      = 'async';
	// WPFC premium respects data-no-lazy / keywords on the tag.
	$attr['data-no-lazy']  = '1';
	// Class helps WPFC lazy-exclude keywords and theme CSS.
	$class                 = isset( $attr['class'] ) ? (string) $attr['class'] : '';
	if ( false === strpos( $class, 'custom-logo' ) ) {
		$attr['class'] = trim( $class . ' custom-logo' );
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'drslon_perf_logo_image_attr', 10, 3 );

/**
 * Keep site-logo srcset on compact sizes only (avoid 86KB full PNG in candidates).
 *
 * @param array  $sources One or more arrays of source data.
 * @param array  $size_array  Width/height.
 * @param string $image_src Image URL.
 * @param array  $image_meta Meta.
 * @param int    $attachment_id ID.
 * @return array
 */
function drslon_perf_logo_srcset( array $sources, array $size_array, string $image_src, array $image_meta, int $attachment_id ): array {
	$logo_id = (int) get_option( 'site_logo' );
	if ( $logo_id <= 0 || $attachment_id !== $logo_id || $sources === array() ) {
		return $sources;
	}

	$allowed = array();
	foreach ( $sources as $width => $source ) {
		// Drop full-resolution candidate (> 200px wide for this mark).
		if ( (int) $width > 200 ) {
			continue;
		}
		$allowed[ $width ] = $source;
	}

	return $allowed !== array() ? $allowed : $sources;
}
add_filter( 'wp_calculate_image_srcset', 'drslon_perf_logo_srcset', 10, 5 );

/**
 * Preload compact site logo for LCP (header).
 */
function drslon_perf_preload_logo(): void {
	if ( is_admin() ) {
		return;
	}

	$logo_id = (int) get_option( 'site_logo' );
	if ( $logo_id <= 0 ) {
		return;
	}

	$url = wp_get_attachment_image_url( $logo_id, 'drslon-site-logo' );
	if ( ! $url ) {
		$url = wp_get_attachment_image_url( $logo_id, 'full' );
	}
	if ( ! $url ) {
		return;
	}

	printf(
		'<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n",
		esc_url( $url )
	);
}
add_action( 'wp_head', 'drslon_perf_preload_logo', 2 );

/**
 * Defer non-critical front scripts.
 *
 * Theme scripts already wait for DOMContentLoaded.
 * Translator + jQuery UI power the language widget (needed, not critical path).
 * GDPR banner may appear a tick later — acceptable; consent still works.
 *
 * Do NOT defer jquery-core / jquery-migrate: plugins inject inline jQuery.
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
		// Theme.
		'drslon-sticky-header',
		'drslon-krv-theme',
		'drslon-featured-slider',
		'drslon-content-lightbox',
		// Translator Revolution chain (widget only).
		'jquery-ui-core',
		'jquery-ui-mouse',
		'jquery-ui-draggable',
		'surstudio-plugin-translator-revolution-lite',
		// Cookie banner (non-LCP).
		'moove_gdpr_frontend',
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
