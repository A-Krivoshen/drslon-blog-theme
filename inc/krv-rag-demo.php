<?php
/**
 * RAG demo on /rag-demo/: the embed grows with its content.
 * Placeholder height matches the empty form so the page does not jump on load.
 *
 * @package drslon-blog-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace the stored iframe with one that matches the theme and reports its height.
 *
 * @param string $content Post content.
 */
function drslon_rag_demo_iframe( string $content ): string {
	if ( ! is_page( 'rag-demo' ) || false === strpos( $content, 'blog-rag-demo' ) ) {
		return $content;
	}

	$embed = <<<'HTML'
<div id="krv-rag-slot"></div>
<script>
(function () {
  var t = document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light";
  var slot = document.getElementById("krv-rag-slot");
  if (!slot) return;
  var f = document.createElement("iframe");
  f.className = "krv-rag-demo";
  f.title = "Демо RAG по блогу";
  f.setAttribute("scrolling", "no");
  f.setAttribute("referrerpolicy", "strict-origin-when-cross-origin");
  f.style.cssText = "width:100%;height:207px;border:0;display:block;overflow:hidden;background:transparent";
  slot.appendChild(f);
  function guess(w) {
    if (w >= 650) return 207;
    if (w >= 600) return 261;
    if (w >= 560) return 302;
    if (w >= 358) return 347;
    if (w >= 310) return 388;
    return 433;
  }
  function applyGuess() {
    var w = f.getBoundingClientRect().width;
    if (w > 0) f.style.height = guess(w) + "px";
  }
  applyGuess();
  requestAnimationFrame(applyGuess);
  f.src = "https://support.krivoshein.site/blog-rag-demo?embed=1&v=20261106c&theme=" + t;
  var origin = "https://support.krivoshein.site";
  var timer = 0;
  var want = 0;
  window.addEventListener("message", function (e) {
    if (e.origin !== origin || e.source !== f.contentWindow) return;
    var d = e.data || {};
    if (d.type !== "krv-rag-height") return;
    var h = Math.round(Number(d.height) || 0);
    if (h < 180 || h > 12000) return;
    want = h + 4;
    clearTimeout(timer);
    timer = setTimeout(function () {
      var cur = parseInt(f.style.height, 10) || 0;
      if (Math.abs(cur - want) > 2) f.style.height = want + "px";
    }, 140);
  });
  function push() {
    var n = document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light";
    try {
      if (f.contentWindow) f.contentWindow.postMessage({ type: "krv-rag-theme", theme: n }, origin);
    } catch (err) {}
  }
  f.addEventListener("load", push);
  if (window.MutationObserver) {
    new MutationObserver(push).observe(document.documentElement, { attributes: true, attributeFilter: ["data-theme"] });
  }
})();
</script>
HTML;

	$replaced = preg_replace(
		'/<iframe\b[^>]*blog-rag-demo[^>]*>\s*<\/iframe>/i',
		$embed,
		$content,
		1,
		$count
	);

	return ( is_string( $replaced ) && $count > 0 ) ? $replaced : $content;
}
add_filter( 'the_content', 'drslon_rag_demo_iframe', 12 );
