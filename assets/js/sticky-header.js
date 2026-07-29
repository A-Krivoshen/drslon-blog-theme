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


/* Header search popup modal (desktop + mobile) — never expand inline */
(function () {
  var modal = null;
  var input = null;
  var lastFocus = null;

  function homeAction() {
    try {
      return (document.querySelector('link[rel="home"]') || {}).href || "/";
    } catch (e) {
      return "/";
    }
  }

  function ensureModal() {
    if (modal) return modal;
    modal = document.createElement("div");
    modal.className = "drslon-search-modal";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.setAttribute("aria-label", "Поиск по сайту");
    modal.innerHTML =
      '<div class="drslon-search-modal__panel">' +
      '<div class="drslon-search-modal__head">' +
      '<h2 class="drslon-search-modal__title" id="drslon-search-modal-title">Поиск по сайту</h2>' +
      '<button type="button" class="drslon-search-modal__close" aria-label="Закрыть">×</button>' +
      "</div>" +
      '<form class="drslon-search-modal__form" role="search" method="get" action="' +
      homeAction() +
      '">' +
      '<input class="drslon-search-modal__input" type="search" name="s" placeholder="Введите запрос…" autocomplete="off" enterkeyhint="search" />' +
      '<button class="drslon-search-modal__submit" type="submit">Найти</button>' +
      "</form>" +
      '<p class="drslon-search-modal__hint">Поиск по блогу и страницам. На внутренних страницах форма также в сайдбаре.</p>' +
      "</div>";
    modal.setAttribute("aria-labelledby", "drslon-search-modal-title");
    document.body.appendChild(modal);
    input = modal.querySelector(".drslon-search-modal__input");
    modal.querySelector(".drslon-search-modal__close").addEventListener("click", closeModal);
    modal.addEventListener("click", function (e) {
      if (e.target === modal) closeModal();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal && modal.classList.contains("is-open")) {
        closeModal();
      }
    });
    return modal;
  }

  function openModal() {
    ensureModal();
    lastFocus = document.activeElement;
    modal.classList.add("is-open");
    document.body.classList.add("drslon-search-modal-open");
    setTimeout(function () {
      if (input) {
        input.value = "";
        input.focus();
      }
    }, 40);
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove("is-open");
    document.body.classList.remove("drslon-search-modal-open");
    if (lastFocus && typeof lastFocus.focus === "function") {
      try {
        lastFocus.focus();
      } catch (e) {}
    }
  }

  function bindOne(form) {
    if (!form || form.dataset.modalBound === "1") return;
    form.dataset.modalBound = "1";
    var btn = form.querySelector(".wp-block-search__button, .drslon-header-search__loupe");
    var field = form.querySelector(".wp-block-search__input");
    if (field) {
      field.removeAttribute("required");
      field.setAttribute("tabindex", "-1");
      field.setAttribute("aria-hidden", "true");
    }
    if (btn) {
      btn.setAttribute("type", "button");
      btn.setAttribute("aria-haspopup", "dialog");
      btn.setAttribute("aria-label", "Открыть поиск");
      btn.addEventListener(
        "click",
        function (e) {
          e.preventDefault();
          e.stopPropagation();
          openModal();
        },
        true
      );
    }
    form.addEventListener(
      "submit",
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        openModal();
      },
      true
    );
    /* block focus on hidden field so header never expands */
    form.addEventListener(
      "focusin",
      function (e) {
        if (e.target && e.target.classList && e.target.classList.contains("wp-block-search__input")) {
          e.preventDefault();
          e.target.blur();
          openModal();
        }
      },
      true
    );
  }

  function bindHeaderSearch() {
    var forms = document.querySelectorAll(
      "form.drslon-header-search, .drslon-header-utility form.wp-block-search"
    );
    for (var i = 0; i < forms.length; i++) bindOne(forms[i]);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bindHeaderSearch);
  } else {
    bindHeaderSearch();
  }
  /* late widgets / cached fragments */
  window.addEventListener("load", bindHeaderSearch);
})();