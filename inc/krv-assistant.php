<?php
/**
 * KRV AI Popup Assistant (web widget on support.krivoshein.site).
 *
 * Loads the shared embed used on service landings. Does not load Replain.
 *
 * @package drslon-blog-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the assistant after a click outside the hero, or after a short idle.
 * The tag is inline so the browser does not fetch the widget during parse.
 */
function drslon_krv_assistant_footer(): void {
	if ( is_admin() ) {
		return;
	}

	if ( is_feed() || is_embed() ) {
		return;
	}

	$src = 'https://support.krivoshein.site/widget/krv-assistant.js?v=20261111r';
	$api = 'https://support.krivoshein.site/api/v1';
	?>

<style id="krv-assistant-slot">
@media (max-width: 640px) {
	.krv-assistant, .krv-assistant-launcher, #krv-assistant, [data-krv-assistant-root] {
		bottom: calc(env(safe-area-inset-bottom) + 4.5rem) !important;
	}
}
</style>
<script id="krv-assistant-loader">
(function () {
	if (window.__krvAssistantQueued) return;
	window.__krvAssistantQueued = true;
	var loaded = false;
	function load() {
		if (loaded) return;
		loaded = true;
		var s = document.createElement("script");
		s.src = <?php echo wp_json_encode( $src ); ?>;
		s.defer = true;
		s.setAttribute("data-api", <?php echo wp_json_encode( $api ); ?>);
		s.setAttribute("data-side", "right");
		document.body.appendChild(s);
	}
	var timer = window.setTimeout(load, 5000);
	function later() {
		window.clearTimeout(timer);
		window.setTimeout(load, 400);
	}
	window.addEventListener("pointerdown", function (event) {
		var node = event.target;
		if (node && node.closest && node.closest(".krv-landing-hero, .krv-cookie, .drslon-header-cta, a[href*='/max']")) return;
		later();
	}, { passive: true });
	window.addEventListener("keydown", later, { once: true });
})();
</script>
	<?php
}
add_action( 'wp_footer', 'drslon_krv_assistant_footer', 99 );
