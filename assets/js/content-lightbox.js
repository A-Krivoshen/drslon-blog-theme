(function () {
        const desktopQuery = window.matchMedia('(min-width: 782px)');

        if (!desktopQuery.matches) {
                return;
        }

        if (!document.body.matches('.single-post, .single-project, .single-arkai-portfolio')) {
                return;
        }

        const content = document.querySelector('.drslon-single-content, .wp-block-post-content');

        if (!content) {
                return;
        }

        let previousActiveElement = null;

        function isImageUrl(url) {
                return /\.(png|jpe?g|gif|webp|avif)(\?.*)?$/i.test(url || '');
        }

        function getLargestFromSrcset(srcset) {
                if (!srcset) {
                        return '';
                }

                return srcset
                        .split(',')
                        .map(function (item) {
                                const parts = item.trim().split(/\s+/);
                                const url = parts[0] || '';
                                const width = parts[1] && parts[1].endsWith('w') ? parseInt(parts[1], 10) : 0;

                                return { url: url, width: width };
                        })
                        .filter(function (item) {
                                return item.url;
                        })
                        .sort(function (a, b) {
                                return b.width - a.width;
                        })[0]?.url || '';
        }

        function getImageUrl(img) {
                const link = img.closest('a[href]');

                if (link && isImageUrl(link.href)) {
                        return link.href;
                }

                return getLargestFromSrcset(img.getAttribute('srcset')) || img.currentSrc || img.src || '';
        }

        function getCaption(img) {
                const figure = img.closest('figure');
                const caption = figure ? figure.querySelector('figcaption') : null;

                if (caption && caption.textContent.trim()) {
                        return caption.textContent.trim();
                }

                return img.getAttribute('alt') || '';
        }

        const lightbox = document.createElement('div');

        lightbox.className = 'drslon-content-lightbox';
        lightbox.setAttribute('role', 'dialog');
        lightbox.setAttribute('aria-modal', 'true');
        lightbox.setAttribute('aria-hidden', 'true');
        lightbox.innerHTML = ''
                + '<button class="drslon-content-lightbox__zoom" type="button" aria-label="Увеличить изображение" aria-pressed="false">+</button>'
                + '<button class="drslon-content-lightbox__close" type="button" aria-label="Закрыть">×</button>'
                + '<div class="drslon-content-lightbox__inner">'
                + '<img class="drslon-content-lightbox__image" alt="">'
                + '<div class="drslon-content-lightbox__caption"></div>'
                + '</div>';

        document.body.appendChild(lightbox);

        const zoomButton = lightbox.querySelector('.drslon-content-lightbox__zoom');
        const closeButton = lightbox.querySelector('.drslon-content-lightbox__close');
        const lightboxImage = lightbox.querySelector('.drslon-content-lightbox__image');
        const lightboxCaption = lightbox.querySelector('.drslon-content-lightbox__caption');

        function setZoomed(isZoomed) {
                lightbox.classList.toggle('is-zoomed', isZoomed);
                zoomButton.setAttribute('aria-pressed', isZoomed ? 'true' : 'false');
                zoomButton.setAttribute('aria-label', isZoomed ? 'Уменьшить изображение' : 'Увеличить изображение');
                zoomButton.textContent = isZoomed ? '−' : '+';

                lightboxImage.style.removeProperty('width');
                lightboxImage.style.removeProperty('height');

                if (!isZoomed) {
                        if (typeof lightbox.scrollTo === 'function') {
                                lightbox.scrollTo({ top: 0, left: 0 });
                        }

                        return;
                }

                const naturalWidth = lightboxImage.naturalWidth || 0;
                const currentWidth = lightboxImage.getBoundingClientRect().width || naturalWidth || 0;

                if (!currentWidth) {
                        return;
                }

                const viewportTarget = Math.round(window.innerWidth * 0.9);
                const visualTarget = Math.round(currentWidth * 1.6);
                const maxUpscaleWidth = naturalWidth ? Math.round(naturalWidth * 2) : Math.round(currentWidth * 2);
                const targetWidth = Math.min(
                        Math.max(visualTarget, viewportTarget),
                        maxUpscaleWidth,
                        2200
                );

                lightboxImage.style.width = targetWidth + 'px';
                lightboxImage.style.height = 'auto';

                if (typeof lightbox.scrollTo === 'function') {
                        lightbox.scrollTo({ top: 0, left: 0 });
                }
        }

        function closeLightbox() {
                if (!lightbox.classList.contains('is-open')) {
                        return;
                }

                lightbox.classList.remove('is-open');
                lightbox.setAttribute('aria-hidden', 'true');
                setZoomed(false);
                document.documentElement.classList.remove('drslon-lightbox-open');

                lightboxImage.removeAttribute('src');
                lightboxImage.alt = '';
                lightboxCaption.textContent = '';
                lightboxCaption.hidden = true;

                if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                        try {
                                previousActiveElement.focus({ preventScroll: true });
                        } catch (error) {
                                previousActiveElement.focus();
                        }
                }

                previousActiveElement = null;
        }

        function openLightbox(img) {
                const imageUrl = getImageUrl(img);

                if (!imageUrl) {
                        return;
                }

                previousActiveElement = document.activeElement;
                setZoomed(false);

                const caption = getCaption(img);

                lightboxImage.src = imageUrl;
                lightboxImage.alt = img.getAttribute('alt') || '';

                lightboxCaption.textContent = caption;
                lightboxCaption.hidden = !caption;

                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');
                document.documentElement.classList.add('drslon-lightbox-open');

                closeButton.focus();
        }

        zoomButton.addEventListener('click', function () {
                setZoomed(!lightbox.classList.contains('is-zoomed'));
        });

        closeButton.addEventListener('click', closeLightbox);

        lightboxImage.addEventListener('click', function () {
                setZoomed(!lightbox.classList.contains('is-zoomed'));
        });

        function handleDesktopChange(event) {
                if (!event.matches) {
                        closeLightbox();
                }
        }

        if (typeof desktopQuery.addEventListener === 'function') {
                desktopQuery.addEventListener('change', handleDesktopChange);
        } else if (typeof desktopQuery.addListener === 'function') {
                desktopQuery.addListener(handleDesktopChange);
        }

        lightbox.addEventListener('click', function (event) {
                if (event.target === lightbox) {
                        closeLightbox();
                }
        });

        document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
                        closeLightbox();
                }
        });

        content.querySelectorAll('.wp-block-image img, .wp-block-gallery img').forEach(function (img) {
                if (img.classList.contains('emoji') || img.classList.contains('wp-smiley')) {
                        return;
                }

                const link = img.closest('a[href]');

                if (link && !isImageUrl(link.href)) {
                        return;
                }

                img.classList.add('drslon-content-lightbox-target');
                img.setAttribute('tabindex', '0');
                img.setAttribute('role', 'button');
                img.setAttribute('aria-label', 'Открыть изображение');
        });

        content.addEventListener('click', function (event) {
                if (!desktopQuery.matches) {
                        closeLightbox();
                        return;
                }

                const img = event.target.closest('.drslon-content-lightbox-target');

                if (!img) {
                        return;
                }

                event.preventDefault();
                openLightbox(img);
        });

        content.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                }

                if (!desktopQuery.matches) {
                        closeLightbox();
                        return;
                }

                const img = event.target.closest('.drslon-content-lightbox-target');

                if (!img) {
                        return;
                }

                event.preventDefault();
                openLightbox(img);
        });
})();
