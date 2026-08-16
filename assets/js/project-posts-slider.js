(function () {
	'use strict';

	function prefersReducedMotion() {
		return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	function cardStep(viewport) {
		const card = viewport.querySelector('.krv-project-posts__item');
		if (!card) {
			return viewport.clientWidth;
		}
		const styles = window.getComputedStyle(viewport.querySelector('.krv-project-posts__grid') || viewport);
		const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
		return card.getBoundingClientRect().width + gap;
	}

	function visibleCount(viewport) {
		const step = cardStep(viewport);
		if (step <= 0) {
			return 1;
		}
		return Math.max(1, Math.round(viewport.clientWidth / step));
	}

	function pageCount(root, viewport) {
		const items = viewport.querySelectorAll('.krv-project-posts__item').length;
		const vis = visibleCount(viewport);
		return Math.max(1, Math.ceil(items / vis));
	}

	function currentPage(viewport) {
		const step = cardStep(viewport);
		const vis = visibleCount(viewport);
		if (step <= 0) {
			return 0;
		}
		const pageStep = step * vis;
		return Math.round(viewport.scrollLeft / pageStep);
	}

	function maxScroll(viewport) {
		return Math.max(0, viewport.scrollWidth - viewport.clientWidth);
	}

	function allFit(viewport) {
		return maxScroll(viewport) < 4;
	}

	function updateChrome(root) {
		const viewport = root.querySelector('.krv-project-posts__viewport');
		const prev = root.querySelector('.krv-project-posts__arrow--prev');
		const next = root.querySelector('.krv-project-posts__arrow--next');
		const dotsWrap = root.querySelector('.krv-project-posts__dots');
		if (!viewport) {
			return;
		}

		const staticMode = allFit(viewport);
		root.classList.toggle('is-static', staticMode);

		if (prev) {
			prev.disabled = staticMode || viewport.scrollLeft <= 2;
		}
		if (next) {
			next.disabled = staticMode || viewport.scrollLeft >= maxScroll(viewport) - 2;
		}

		if (!dotsWrap) {
			return;
		}

		const pages = pageCount(root, viewport);
		if (staticMode || pages <= 1) {
			dotsWrap.replaceChildren();
			return;
		}

		if (dotsWrap.childElementCount !== pages) {
			const frag = document.createDocumentFragment();
			for (let i = 0; i < pages; i++) {
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'krv-project-posts__dot-btn';
				btn.setAttribute('role', 'tab');
				btn.setAttribute('aria-label', 'Страница ' + (i + 1));
				btn.dataset.page = String(i);
				frag.appendChild(btn);
			}
			dotsWrap.replaceChildren(frag);
		}

		const active = Math.min(pages - 1, Math.max(0, currentPage(viewport)));
		dotsWrap.querySelectorAll('.krv-project-posts__dot-btn').forEach(function (btn, i) {
			btn.classList.toggle('is-active', i === active);
			btn.setAttribute('aria-selected', i === active ? 'true' : 'false');
		});
	}

	function scrollToPage(viewport, page) {
		const step = cardStep(viewport) * visibleCount(viewport);
		const left = Math.min(maxScroll(viewport), Math.max(0, page * step));
		viewport.scrollTo({
			left: left,
			behavior: prefersReducedMotion() ? 'auto' : 'smooth',
		});
	}

	function move(root, direction) {
		const viewport = root.querySelector('.krv-project-posts__viewport');
		if (!viewport) {
			return;
		}
		scrollToPage(viewport, currentPage(viewport) + direction);
	}

	function bind(root) {
		if (root.dataset.sliderReady === '1') {
			return;
		}
		root.dataset.sliderReady = '1';

		const viewport = root.querySelector('.krv-project-posts__viewport');
		if (!viewport) {
			return;
		}

		root.addEventListener('click', function (event) {
			const arrow = event.target.closest('.krv-project-posts__arrow');
			if (arrow && root.contains(arrow)) {
				event.preventDefault();
				move(root, arrow.classList.contains('krv-project-posts__arrow--prev') ? -1 : 1);
				return;
			}
			const dot = event.target.closest('.krv-project-posts__dot-btn');
			if (dot && root.contains(dot)) {
				event.preventDefault();
				scrollToPage(viewport, parseInt(dot.dataset.page || '0', 10));
			}
		});

		viewport.addEventListener('scroll', function () {
			window.requestAnimationFrame(function () {
				updateChrome(root);
			});
		}, { passive: true });

		viewport.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				move(root, -1);
			} else if (event.key === 'ArrowRight') {
				event.preventDefault();
				move(root, 1);
			}
		});

		window.addEventListener('resize', function () {
			updateChrome(root);
		});

		updateChrome(root);
	}

	function init() {
		document.querySelectorAll('.krv-project-posts--slider').forEach(bind);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
