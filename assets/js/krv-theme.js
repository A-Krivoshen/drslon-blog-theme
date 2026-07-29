/**
 * DrSlon theme toggle — same contract as static landings.
 * - html[data-theme="light|dark"]
 * - localStorage krv_theme
 * - cookie krv_theme on .krivoshein.site (cross-subdomain)
 * - auto: 07:00–19:59 light, else dark (when no stored choice)
 */
(function () {
  "use strict";

  var THEME_KEY = "krv_theme";
  var COOKIE_DOMAIN = ".krivoshein.site";
  var DAY_START = 7;
  var DAY_END = 20;

  function $(sel, root) {
    return (root || document).querySelector(sel);
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
          if (v === "light" || v === "dark") return v;
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

  function getTheme() {
    var c = readCookie(THEME_KEY);
    if (c === "light" || c === "dark") return c;
    try {
      var s = localStorage.getItem(THEME_KEY);
      if (s === "light" || s === "dark") return s;
    } catch (e) {}
    return autoTheme();
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

  function applyTheme(theme, anim, persist) {
    var html = document.documentElement;
    var t = theme === "dark" ? "dark" : "light";
    if (anim) html.classList.add("theme-anim");
    html.setAttribute("data-theme", t);
    setThemeColorMeta(t);

    var btns = document.querySelectorAll(".krv-theme-btn");
    for (var i = 0; i < btns.length; i++) {
      var btn = btns[i];
      btn.textContent = t === "dark" ? "\u2600" : "\u263E";
      btn.title = t === "dark" ? "Светлая тема / Light" : "Тёмная тема / Dark";
      btn.setAttribute("aria-label", btn.title);
      btn.setAttribute("aria-pressed", t === "dark" ? "true" : "false");
    }

    if (persist) {
      try {
        localStorage.setItem(THEME_KEY, t);
      } catch (e) {}
      writeCookie(THEME_KEY, t);
    }

    if (anim) {
      setTimeout(function () {
        html.classList.remove("theme-anim");
      }, 280);
    }
  }

  function toggleTheme() {
    var cur =
      document.documentElement.getAttribute("data-theme") === "dark"
        ? "dark"
        : "light";
    applyTheme(cur === "dark" ? "light" : "dark", true, true);
  }

  function makeButton() {
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "krv-theme-btn";
    btn.setAttribute("aria-label", "Theme");
    btn.addEventListener("click", toggleTheme);
    return btn;
  }

  function injectControls() {
    if ($(".krv-theme-btn")) return;

    var host =
      $(".drslon-header-utility") ||
      $(".drslon-site-header__inner") ||
      $("header .wp-block-group");

    if (!host) return;

    var box = document.createElement("div");
    box.className = "krv-controls";
    box.appendChild(makeButton());

    var cta = host.querySelector(".drslon-header-cta, .wp-block-buttons");
    if (cta && cta.parentElement === host) {
      host.insertBefore(box, cta);
    } else if (cta && cta.parentElement) {
      cta.parentElement.insertBefore(box, cta);
    } else {
      host.appendChild(box);
    }

    var drawer = document.querySelector(
      ".wp-block-navigation__responsive-container-content"
    );
    if (drawer && !drawer.querySelector(".krv-theme-btn")) {
      var row = document.createElement("div");
      row.className = "krv-controls krv-controls--drawer";
      row.appendChild(makeButton());
      drawer.appendChild(row);
    }
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
    var s = null;
    try {
      s = localStorage.getItem(THEME_KEY);
    } catch (e) {}
    if ((c === "light" || c === "dark") && s !== c) {
      try {
        localStorage.setItem(THEME_KEY, c);
      } catch (e) {}
    } else if ((s === "light" || s === "dark") && c !== s) {
      writeCookie(THEME_KEY, s);
    }
  } catch (e) {}

  applyTheme(getTheme(), false, false);

  ready(function () {
    injectControls();
    applyTheme(getTheme(), false, false);
  });
})();
