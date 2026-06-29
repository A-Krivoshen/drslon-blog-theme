(function () {
	'use strict';

	function getCardStep(viewport) {
		const card = viewport.querySelector('.drslon-featured-slider__card');

		if (!card) {
			return viewport.clientWidth;
		}

		const styles = window.getComputedStyle(viewport);
		const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;

		return card.getBoundingClientRect().width + gap;
	}

	function moveSlider(button) {
		const slider = button.closest('.drslon-featured-slider');

		if (!slider) {
			return;
		}

		const viewport = slider.querySelector('.drslon-featured-slider__viewport');

		if (!viewport) {
			return;
		}

		const direction = button.classList.contains('drslon-featured-slider__arrow--prev') ? -1 : 1;
		const step = getCardStep(viewport);
		const maxScroll = viewport.scrollWidth - viewport.clientWidth;
		let nextLeft = viewport.scrollLeft + direction * step;

		if (nextLeft < 0) {
			nextLeft = maxScroll;
		}

		if (nextLeft > maxScroll - 2) {
			nextLeft = 0;
		}

		const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		viewport.scrollTo({
			left: nextLeft,
			behavior: reduceMotion ? 'auto' : 'smooth',
		});
	}

	document.addEventListener('click', function (event) {
		const button = event.target.closest('.drslon-featured-slider__arrow');

		if (!button) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
		moveSlider(button);
	});
})();