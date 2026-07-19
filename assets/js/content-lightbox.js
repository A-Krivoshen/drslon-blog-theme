(function () {
	'use strict';

	var images = Array.prototype.slice.call(
		document.querySelectorAll(
			'.drslon-single-content .wp-block-image img, ' +
			'.drslon-single-content .wp-block-gallery img, ' +
			'.wp-block-post-content .wp-block-image img, ' +
			'.wp-block-post-content .wp-block-gallery img'
		)
	).filter(function (image) {
		return !image.closest('.drslon-content-lightbox')
			&& !image.closest('.wp-lightbox-container, [data-wp-interactive="core/image"]')
			&& !image.closest('.stk--has-lightbox, [data-lightbox], [data-fancybox], [role="button"]')
			&& !image.classList.contains('avatar')
			&& !image.classList.contains('emoji')
			&& !image.classList.contains('wp-smiley');
	});

	if (!images.length) {
		return;
	}

	var dialog = document.createElement('dialog');
	var dialogId = 'drslon-content-lightbox';
	dialog.id = dialogId;
	dialog.className = 'drslon-content-lightbox';
	dialog.setAttribute('aria-label', 'Просмотр изображения');
	dialog.innerHTML =
		'<button class="drslon-content-lightbox__zoom" type="button" aria-label="Увеличить изображение" aria-pressed="false">+</button>' +
		'<button class="drslon-content-lightbox__close" type="button" aria-label="Закрыть просмотр">&times;</button>' +
		'<div class="drslon-content-lightbox__inner">' +
			'<img class="drslon-content-lightbox__image" alt="">' +
			'<p class="drslon-content-lightbox__caption" hidden></p>' +
		'</div>';
	document.body.appendChild(dialog);

	var dialogInner = dialog.querySelector('.drslon-content-lightbox__inner');
	var dialogImage = dialog.querySelector('.drslon-content-lightbox__image');
	var caption = dialog.querySelector('.drslon-content-lightbox__caption');
	var closeButton = dialog.querySelector('.drslon-content-lightbox__close');
	var zoomButton = dialog.querySelector('.drslon-content-lightbox__zoom');
	var desktopQuery = window.matchMedia('(min-width: 782px)');
	var supportsModal = typeof dialog.showModal === 'function';
	var records = [];
	var isEnabled = false;
	var isOpen = false;
	var activeTrigger = null;

	function getCaption(image) {
		var figure = image.closest('figure');
		var figureCaption = figure ? figure.querySelector('figcaption') : null;
		return figureCaption ? figureCaption.textContent.trim() : '';
	}

	function getLargestFromSrcset(srcset) {
		var candidates;

		if (!srcset) {
			return '';
		}

		candidates = srcset.split(',').map(function (item) {
			var parts = item.trim().split(/\s+/);
			var width = parts[1] && /w$/.test(parts[1]) ? parseInt(parts[1], 10) : 0;
			return { url: parts[0] || '', width: width };
		}).filter(function (item) {
			return item.url;
		}).sort(function (a, b) {
			return b.width - a.width;
		});

		return candidates.length ? candidates[0].url : '';
	}

	function isImageUrl(url) {
		return /\.(?:avif|gif|jpe?g|png|svg|webp)(?:[?#].*)?$/i.test(url || '');
	}

	function getImageSource(image) {
		var anchor = image.closest('a[href]');
		var linkedSource = anchor ? anchor.href : '';

		if (isImageUrl(linkedSource)) {
			return linkedSource;
		}

		return getLargestFromSrcset(image.getAttribute('srcset')) || image.currentSrc || image.src;
	}

	function getTriggerLabel(image) {
		var description = getCaption(image) || image.alt.trim();
		return description ? 'Открыть изображение: ' + description : 'Открыть изображение';
	}

	function setZoomed(zoomed) {
		dialog.classList.toggle('is-zoomed', zoomed);
		zoomButton.setAttribute('aria-pressed', zoomed ? 'true' : 'false');
		zoomButton.setAttribute('aria-label', zoomed ? 'Уменьшить изображение' : 'Увеличить изображение');
		zoomButton.textContent = zoomed ? '−' : '+';
		dialogImage.style.removeProperty('width');
		dialogImage.style.removeProperty('height');

		if (!zoomed) {
			if (typeof dialog.scrollTo === 'function') {
				dialog.scrollTo({ top: 0, left: 0 });
			}

			return;
		}

		var naturalWidth = dialogImage.naturalWidth || 0;
		var currentWidth = dialogImage.getBoundingClientRect().width || naturalWidth || 0;

		if (!currentWidth) {
			return;
		}

		var viewportTarget = Math.round(window.innerWidth * 0.9);
		var visualTarget = Math.round(currentWidth * 1.6);
		var maxUpscaleWidth = naturalWidth ? Math.round(naturalWidth * 2) : Math.round(currentWidth * 2);
		var targetWidth = Math.min(Math.max(visualTarget, viewportTarget), maxUpscaleWidth, 2200);

		dialogImage.style.width = targetWidth + 'px';
		dialogImage.style.height = 'auto';

		if (typeof dialog.scrollTo === 'function') {
			dialog.scrollTo({ top: 0, left: 0 });
		}
	}

	function openLightbox(image, trigger) {
		var imageCaption = getCaption(image);

		activeTrigger = trigger;
		dialogImage.src = getImageSource(image);
		dialogImage.alt = image.getAttribute('alt') || '';
		caption.textContent = imageCaption;
		caption.hidden = !imageCaption;
		setZoomed(false);
		isOpen = true;
		document.documentElement.classList.add('drslon-lightbox-open');

		if (supportsModal) {
			try {
				dialog.showModal();
			} catch (error) {
				dialog.setAttribute('open', '');
			}
		} else {
			dialog.setAttribute('role', 'dialog');
			dialog.setAttribute('aria-modal', 'true');
			dialog.setAttribute('open', '');
		}

		closeButton.focus();
	}

	function closeLightbox(restoreFocus) {
		var trigger = activeTrigger;

		if (!isOpen) {
			return;
		}

		isOpen = false;
		activeTrigger = null;
		if (supportsModal && dialog.open) {
			dialog.close();
		} else {
			dialog.removeAttribute('open');
		}
		document.documentElement.classList.remove('drslon-lightbox-open');
		setZoomed(false);
		dialogImage.removeAttribute('src');
		caption.textContent = '';
		caption.hidden = true;

		if (restoreFocus && trigger && trigger.isConnected) {
			trigger.focus();
		}
	}

	function onTriggerClick(event) {
		if (!isEnabled || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}

		event.preventDefault();
		openLightbox(event.currentTarget.querySelector('img'), event.currentTarget);
	}

	function setupTriggers() {
		if (isEnabled) {
			return;
		}

		isEnabled = true;
		images.forEach(function (image) {
			var interactiveParent = image.closest('a[href], button');
			if (interactiveParent && interactiveParent.tagName === 'A' && !isImageUrl(interactiveParent.href)) {
				return;
			}

			var media = image.parentElement && image.parentElement.tagName === 'PICTURE'
				? image.parentElement
				: image;
			var trigger = interactiveParent;
			var generated = false;

			if (!trigger) {
				trigger = document.createElement('button');
				trigger.type = 'button';
				trigger.setAttribute('aria-label', getTriggerLabel(image));
				media.parentNode.insertBefore(trigger, media);
				trigger.appendChild(media);
				generated = true;
			}

			if (records.some(function (record) { return record.trigger === trigger; })) {
				return;
			}

			var record = {
				trigger: trigger,
				media: media,
				generated: generated,
				hadClass: trigger.classList.contains('drslon-content-lightbox-trigger'),
				ariaHaspopup: trigger.getAttribute('aria-haspopup'),
				ariaControls: trigger.getAttribute('aria-controls'),
				ariaLabel: trigger.getAttribute('aria-label')
			};

			if (!trigger.getAttribute('aria-label') && !image.alt.trim()) {
				trigger.setAttribute('aria-label', getTriggerLabel(image));
			}
			trigger.classList.add('drslon-content-lightbox-trigger');
			trigger.setAttribute('aria-haspopup', 'dialog');
			trigger.setAttribute('aria-controls', dialogId);
			trigger.addEventListener('click', onTriggerClick);
			records.push(record);
		});
	}

	function restoreAttribute(element, name, value) {
		if (value === null) {
			element.removeAttribute(name);
		} else {
			element.setAttribute(name, value);
		}
	}

	function teardownTriggers() {
		if (!isEnabled) {
			return;
		}

		isEnabled = false;
		closeLightbox(false);
		records.forEach(function (record) {
			var trigger = record.trigger;
			trigger.removeEventListener('click', onTriggerClick);

			if (record.generated) {
				trigger.parentNode.insertBefore(record.media, trigger);
				trigger.remove();
				return;
			}

			if (!record.hadClass) {
				trigger.classList.remove('drslon-content-lightbox-trigger');
			}
			restoreAttribute(trigger, 'aria-haspopup', record.ariaHaspopup);
			restoreAttribute(trigger, 'aria-controls', record.ariaControls);
			restoreAttribute(trigger, 'aria-label', record.ariaLabel);
		});
		records = [];
	}

	function updateResponsiveState() {
		if (desktopQuery.matches) {
			setupTriggers();
		} else {
			teardownTriggers();
		}
	}

	closeButton.addEventListener('click', function () {
		closeLightbox(true);
	});

	zoomButton.addEventListener('click', function () {
		setZoomed(!dialog.classList.contains('is-zoomed'));
	});

	dialogImage.addEventListener('click', function () {
		setZoomed(!dialog.classList.contains('is-zoomed'));
	});

	dialog.addEventListener('click', function (event) {
		if (event.target === dialog && !dialogInner.contains(event.target)) {
			closeLightbox(true);
		}
	});

	dialog.addEventListener('cancel', function (event) {
		event.preventDefault();
		closeLightbox(true);
	});

	document.addEventListener('keydown', function (event) {
		if (!isOpen) {
			return;
		}

		if (event.key === 'Escape') {
			event.preventDefault();
			closeLightbox(true);
			return;
		}

		if (event.key === 'Tab') {
			var focusable = [zoomButton, closeButton];
			var currentIndex = focusable.indexOf(document.activeElement);
			var nextIndex = event.shiftKey ? currentIndex - 1 : currentIndex + 1;

			if (currentIndex === -1 || nextIndex < 0 || nextIndex >= focusable.length) {
				event.preventDefault();
				focusable[event.shiftKey ? focusable.length - 1 : 0].focus();
			}
		}
	});

	document.addEventListener('focusin', function (event) {
		if (isOpen && !dialog.contains(event.target)) {
			closeButton.focus();
		}
	});

	if (typeof desktopQuery.addEventListener === 'function') {
		desktopQuery.addEventListener('change', updateResponsiveState);
	} else {
		desktopQuery.addListener(updateResponsiveState);
	}

	updateResponsiveState();
})();
