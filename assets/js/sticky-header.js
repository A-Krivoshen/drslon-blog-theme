(function () {
	'use strict';

	var header = document.querySelector('.drslon-site-header');
	if (!header) {
		return;
	}

	var mq = window.matchMedia('(min-width: 1100px)');
	var scrollThreshold = 40;
	var ticking = false;

	function isStickyEnabled() {
		return mq.matches;
	}

	function updateHeaderHeight() {
		if (!isStickyEnabled()) {
			document.documentElement.style.removeProperty('--drslon-header-height');
			header.classList.remove('is-scrolled');
			return;
		}

		document.documentElement.style.setProperty(
			'--drslon-header-height',
			header.offsetHeight + 'px'
		);
	}

	function updateScrollState() {
		if (!isStickyEnabled()) {
			header.classList.remove('is-scrolled');
			return;
		}

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

	if (typeof mq.addEventListener === 'function') {
		mq.addEventListener('change', onLayoutChange);
	} else if (typeof mq.addListener === 'function') {
		mq.addListener(onLayoutChange);
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', onLayoutChange);
	window.addEventListener('load', onLayoutChange);

	if (document.fonts && typeof document.fonts.ready === 'object') {
		document.fonts.ready.then(onLayoutChange).catch(function () {});
	}

	onLayoutChange();
})();