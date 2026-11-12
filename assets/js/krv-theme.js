/**
 * DrSlon theme modes — same contract as static landings.
 * - html[data-theme="light|dark"]
 * - stored mode in localStorage krv_theme and cookie krv_theme on
 *   .krivoshein.site: "auto" (default), "light", "dark"
 * - auto: 07:00-19:59 light, else dark; re-checked while the page is open
 * - legacy stored "light"/"dark" values stay valid manual choices
 * - one header button cycles auto -> light -> dark -> auto
 */
(function () {
  "use strict";

  var THEME_KEY = "krv_theme";
  var COOKIE_DOMAIN = ".krivoshein.site";
  var DAY_START = 7;
  var DAY_END = 20;
  var AUTO_CHECK_MS = 30000;

  var NEXT_MODE = { auto: "light", light: "dark", dark: "auto" };
  var MODE_GLYPH = { auto: "\u25D0", light: "\u2600", dark: "\u263E" };
  var MODE_LABEL = {
    auto: "Тема: авто (по времени)",
    light: "Тема: светлая",
    dark: "Тема: тёмная"
  };
  var NEXT_LABEL = { auto: "светлая", light: "тёмная", dark: "авто" };

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function validMode(value) {
    return value === "auto" || value === "light" || value === "dark";
  }

  function autoTheme() {
    var h = new Date().getHours();
    return h >= DAY_START && h < DAY_END ? "light" : "dark";
  }

  function readCookie(name) {
    try {
      var parts = document.cookie.split(";");
      for (var i = 0; i < parts.length; i++) {
        var p = parts[i].replace(/^\s+/, "");
        if (p.indexOf(name + "=") === 0) {
          var v = p.slice(name.length + 1);
          if (validMode(v)) return v;
        }
      }
    } catch (e) {}
    return null;
  }

  function writeCookie(name, value) {
    try {
      var secure = location.protocol === "https:" ? "; Secure" : "";
      document.cookie =
        name +
        "=" +
        value +
        "; path=/; max-age=31536000; domain=" +
        COOKIE_DOMAIN +
        "; SameSite=Lax" +
        secure;
    } catch (e) {}
  }

  function readStorage() {
    try {
      var s = localStorage.getItem(THEME_KEY);
      if (validMode(s)) return s;
    } catch (e) {}
    return null;
  }

  function getMode() {
    return readCookie(THEME_KEY) || readStorage() || "auto";
  }

  function effectiveTheme(mode) {
    return mode === "auto" ? autoTheme() : mode;
  }

  function setThemeColorMeta(theme) {
    var m = document.querySelector('meta[name="theme-color"]');
    if (!m) {
      m = document.createElement("meta");
      m.setAttribute("name", "theme-color");
      document.head.appendChild(m);
    }
    m.setAttribute("content", theme === "dark" ? "#0b1220" : "#315fe8");
  }

  function describeButton(btn, mode) {
    // Fixed-width glyph slot — metrics differ between ☀/☾/◐ and used to nudge the menu.
    var icon = btn.querySelector(".krv-theme-btn__icon");
    if (!icon) {
      icon = document.createElement("span");
      icon.className = "krv-theme-btn__icon";
      icon.setAttribute("aria-hidden", "true");
      btn.textContent = "";
      btn.appendChild(icon);
    }
    icon.textContent = MODE_GLYPH[mode];
    btn.title = MODE_LABEL[mode] + ". Нажмите: " + NEXT_LABEL[mode];
    btn.setAttribute("aria-label", btn.title);
    // aria-pressed cannot represent three states.
    btn.removeAttribute("aria-pressed");
  }

  function applyMode(mode, anim, persist) {
    var html = document.documentElement;
    if (!validMode(mode)) mode = "auto";
    var t = effectiveTheme(mode);
    if (anim) html.classList.add("theme-anim");
    html.setAttribute("data-theme", t);
    setThemeColorMeta(t);

    var btns = document.querySelectorAll(".krv-theme-btn");
    for (var i = 0; i < btns.length; i++) {
      describeButton(btns[i], mode);
    }

    if (persist) {
      try {
        localStorage.setItem(THEME_KEY, mode);
      } catch (e) {}
      writeCookie(THEME_KEY, mode);
    }

    if (anim) {
      setTimeout(function () {
        html.classList.remove("theme-anim");
      }, 220);
    }
  }

  function toggleTheme(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    applyMode(NEXT_MODE[getMode()], true, true);
  }

  function checkAuto() {
    if (getMode() !== "auto") {
      return;
    }
    var current = document.documentElement.getAttribute("data-theme");
    var expected = autoTheme();
    if (expected !== current) {
      applyMode("auto", true, false);
    }
  }

  function makeButton() {
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "krv-theme-btn";
    btn.setAttribute("aria-label", "Theme");
    var icon = document.createElement("span");
    icon.className = "krv-theme-btn__icon";
    icon.setAttribute("aria-hidden", "true");
    icon.textContent = MODE_GLYPH.auto;
    btn.appendChild(icon);
    btn.addEventListener("click", toggleTheme);
    return btn;
  }

  function removeDrawerToggles() {
    var extras = document.querySelectorAll(
      ".wp-block-navigation__responsive-container-content .krv-controls, " +
        ".wp-block-navigation__responsive-container-content .krv-theme-btn, " +
        ".krv-controls--drawer"
    );
    for (var i = 0; i < extras.length; i++) {
      var el = extras[i];
      var wrap =
        el.classList && el.classList.contains("krv-controls")
          ? el
          : el.closest(".krv-controls") || el;
      if (wrap && wrap.parentNode) wrap.parentNode.removeChild(wrap);
    }
  }

  function bindHeaderButtons() {
    var btns = document.querySelectorAll(
      ".drslon-header-utility .krv-theme-btn"
    );
    for (var i = 0; i < btns.length; i++) {
      var btn = btns[i];
      if (btn.dataset.krvBound === "1") continue;
      btn.dataset.krvBound = "1";
      btn.type = "button";
      btn.addEventListener("click", toggleTheme);
    }
  }

  function injectControls() {
    // Single toggle only: static slot in header (no late DOM insert → no menu jump).
    removeDrawerToggles();
    bindHeaderButtons();

    if ($(".drslon-header-utility .krv-theme-btn")) return;

    var host = $(".drslon-header-utility");
    if (!host) return;

    // Prefer pre-rendered empty slot (header.html).
    var slot = host.querySelector(".krv-controls[data-krv-theme-slot], .krv-controls");
    if (slot) {
      if (!slot.querySelector(".krv-theme-btn")) {
        slot.appendChild(makeButton());
      }
      bindHeaderButtons();
      return;
    }

    var box = document.createElement("div");
    box.className = "krv-controls";
    box.setAttribute("data-krv-theme-slot", "1");
    box.appendChild(makeButton());

    // Prefer: after search, before «Прайс»
    var cta = host.querySelector(".drslon-header-cta, .wp-block-buttons");
    var search = host.querySelector(".drslon-header-search, .wp-block-search");
    if (cta && cta.parentElement === host) {
      host.insertBefore(box, cta);
    } else if (search && search.parentElement === host && search.nextSibling) {
      host.insertBefore(box, search.nextSibling);
    } else if (search && search.parentElement === host) {
      search.parentElement.appendChild(box);
    } else {
      host.appendChild(box);
    }
    bindHeaderButtons();
  }

  function ready(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  try {
    var c = readCookie(THEME_KEY);
    var s = readStorage();
    if (c && s !== c) {
      try {
        localStorage.setItem(THEME_KEY, c);
      } catch (e) {}
    } else if (s && c !== s) {
      writeCookie(THEME_KEY, s);
    }
  } catch (e) {}

  applyMode(getMode(), false, false);

  // auto follows the clock while the document stays open (laptop sleep, long tabs).
  setInterval(checkAuto, AUTO_CHECK_MS);
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) {
      checkAuto();
    }
  });
  window.addEventListener("pageshow", checkAuto);

  ready(function () {
    injectControls();
    applyMode(getMode(), false, false);
  });
})();
