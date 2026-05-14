(function () {
        const isDesktop = window.matchMedia('(min-width: 782px)').matches;

        if (!isDesktop) {
                return;
        }

        if (!document.body.matches('.single-post, .single-project, .single-arkai-portfolio')) {
                return;
        }

        const content = document.querySelector('.drslon-single-content, .wp-block-post-content');

        if (!content) {
                return;
        }

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
        lightbox.innerHTML = ''
                + '<button class="drslon-content-lightbox__close" type="button" aria-label="Закрыть">×</button>'
                + '<div class="drslon-content-lightbox__inner">'
                + '<img class="drslon-content-lightbox__image" alt="">'
                + '<div class="drslon-content-lightbox__caption"></div>'
                + '</div>';

        document.body.appendChild(lightbox);

        const closeButton = lightbox.querySelector('.drslon-content-lightbox__close');
        const lightboxImage = lightbox.querySelector('.drslon-content-lightbox__image');
        const lightboxCaption = lightbox.querySelector('.drslon-content-lightbox__caption');

        function closeLightbox() {
                lightbox.classList.remove('is-open');
                document.documentElement.classList.remove('drslon-lightbox-open');
                lightboxImage.removeAttribute('src');
        }

        function openLightbox(img) {
                const imageUrl = getImageUrl(img);

                if (!imageUrl) {
                        return;
                }

                const caption = getCaption(img);

                lightboxImage.src = imageUrl;
                lightboxImage.alt = img.getAttribute('alt') || '';

                lightboxCaption.textContent = caption;
                lightboxCaption.hidden = !caption;

                lightbox.classList.add('is-open');
                document.documentElement.classList.add('drslon-lightbox-open');

                closeButton.focus();
        }

        closeButton.addEventListener('click', closeLightbox);

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

                const img = event.target.closest('.drslon-content-lightbox-target');

                if (!img) {
                        return;
                }

                event.preventDefault();
                openLightbox(img);
        });
})();
