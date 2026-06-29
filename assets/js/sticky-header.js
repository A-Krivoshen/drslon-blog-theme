(function () {
	'use strict';

	var headerShell = document.querySelector('.wp-site-blocks > header.wp-block-template-part');
	var header = document.querySelector('.drslon-site-header');
	if (!headerShell || !header) {
		return;
	}

	var scrollThreshold = 24;
	var ticking = false;

	function updateHeaderHeight() {
		document.documentElement.style.setProperty(
			'--drslon-header-height',
			headerShell.offsetHeight + 'px'
		);
	}

	function updateScrollState() {
		header.classList.toggle('is-scrolled', window.scrollY > scrollThreshold);
		updateHeaderHeight();
	}

	function onScroll() {
		if (!ticking) {
			window.requestAnimationFrame(function () {
				updateScrollState();
				ticking = false;
			});
			ticking = true;
		}
	}

	function onLayoutChange() {
		updateScrollState();
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', onLayoutChange);
	window.addEventListener('load', onLayoutChange);

	if (document.fonts && typeof document.fonts.ready === 'object') {
		document.fonts.ready.then(onLayoutChange).catch(function () {});
	}

	onLayoutChange();
})();