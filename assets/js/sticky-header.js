(function () {
	'use strict';

	var headerShell = document.querySelector('.wp-site-blocks > header.wp-block-template-part');
	var header = document.querySelector('.drslon-site-header');
	if (!headerShell || !header) {
		return;
	}

	var scrollThreshold = 24;
	var ticking = false;
	var isScrolled = null;
	var headerHeight = 0;

	function setHeaderHeight(height) {
		height = Math.round(height);
		if (height <= 0 || height === headerHeight) {
			return;
		}

		headerHeight = height;
		document.documentElement.style.setProperty(
			'--drslon-header-height',
			height + 'px'
		);
		document.documentElement.classList.add('drslon-sticky-header-ready');
	}

	function updateScrollState() {
		var nextState = window.scrollY > scrollThreshold;
		if (nextState === isScrolled) {
			return;
		}

		isScrolled = nextState;
		header.classList.toggle('is-scrolled', nextState);
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
		setHeaderHeight(headerShell.offsetHeight);
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', onLayoutChange);
	window.addEventListener('load', onLayoutChange);

	if (document.fonts && typeof document.fonts.ready === 'object') {
		document.fonts.ready.then(onLayoutChange).catch(function () {});
	}

	if ('ResizeObserver' in window) {
		new ResizeObserver(function (entries) {
			var entry = entries[0];
			var size = entry.borderBoxSize;
			var height = size && size.length ? size[0].blockSize : entry.target.offsetHeight;

			setHeaderHeight(height);
		}).observe(headerShell);
	}

	onLayoutChange();
})();
