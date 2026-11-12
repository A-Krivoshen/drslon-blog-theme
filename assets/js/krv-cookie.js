/**
 * Cookie choice for krivoshein.site.
 * krv_consent=a analytics, krv_consent=n necessary only.
 * An old moove_gdpr_popup cookie is read once and copied over.
 */
(function () {
  var METRIKA_ID = 51907757;
  var TOP100_ID = 7712953;
  var YEAR = 60 * 60 * 24 * 365;
  var analyticsLoaded = false;
  var contextLoaded = false;
  var currentChoice = null;
  var started = false;
  var settingsFocus = null;

  function root() {
    return document.documentElement;
  }

  function bar() {
    return document.getElementById("krv-cookie");
  }

  function dialog() {
    return document.getElementById("krv-cookie-settings");
  }

  function analyticsBox() {
    return document.getElementById("krv-cookie-analytics");
  }

  function readRaw(name) {
    try {
      var match = document.cookie.match(new RegExp("(?:^|;\\s*)" + name + "=([^;]*)"));
      return match ? decodeURIComponent(match[1]) : "";
    } catch (error) {
      return "";
    }
  }

  function readConsent() {
    var own = readRaw("krv_consent");
    if (own === "a" || own === "n") {
      return own;
    }
    var legacy = readRaw("moove_gdpr_popup");
    if (!legacy) {
      return "";
    }
    try {
      var data = JSON.parse(legacy);
      if (data && (data.thirdparty === "1" || data.thirdparty === 1)) {
        return "a";
      }
      if (data && (data.strict === "1" || data.strict === 1 || data.thirdparty === "0" || data.thirdparty === 0)) {
        return "n";
      }
    } catch (error) {
      return "";
    }
    return "";
  }

  function writeConsent(value) {
    var secure = location.protocol === "https:" ? "; Secure" : "";
    try {
      document.cookie = "krv_consent=" + value + "; Path=/; Max-Age=" + YEAR + "; SameSite=Lax" + secure;
      return readRaw("krv_consent") === value;
    } catch (error) {
      return false;
    }
  }

  function loadMetrika() {
    if (window.ym) {
      return;
    }
    (function (m, e, t, r, i, k, a) {
      m[i] = m[i] || function () {
        (m[i].a = m[i].a || []).push(arguments);
      };
      m[i].l = 1 * new Date();
      for (var j = 0; j < document.scripts.length; j++) {
        if (document.scripts[j].src === r) {
          return;
        }
      }
      k = e.createElement(t);
      a = e.getElementsByTagName(t)[0];
      k.async = 1;
      k.src = r;
      a.parentNode.insertBefore(k, a);
    })(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
    window.ym(METRIKA_ID, "init", {
      clickmap: true,
      trackLinks: true,
      accurateTrackBounce: true
    });
  }

  function loadTop100() {
    (function (w, d, c) {
      (w[c] = w[c] || []).push(function () {
        try {
          w.top100Counter = new w.top100({ project: TOP100_ID });
        } catch (error) {}
      });
      var n = d.getElementsByTagName("script")[0];
      var s = d.createElement("script");
      var f = function () {
        n.parentNode.insertBefore(s, n);
      };
      s.async = true;
      s.src = (d.location.protocol === "https:" ? "https:" : "http:") + "//st.top100.ru/top100/top100.js";
      if (w.opera == "[object Opera]") {
        d.addEventListener("DOMContentLoaded", f, false);
      } else {
        f();
      }
    })(window, document, "_top100q");
  }

  function loadContext() {
    contextLoaded = true;
    var marker = "/ads/system/context.js";
    var scripts = document.scripts;
    for (var i = 0; i < scripts.length; i++) {
      if (scripts[i].src && scripts[i].src.indexOf(marker) !== -1) {
        return;
      }
    }
    var node = document.createElement("script");
    node.async = true;
    node.src = "https://yandex.ru/ads/system/context.js";
    document.head.appendChild(node);
  }

  function loadAnalytics() {
    if (analyticsLoaded) {
      return;
    }
    analyticsLoaded = true;
    loadMetrika();
    loadTop100();
    loadContext();
  }

  function syncBarSpace() {
    var node = bar();
    var html = root();
    var open = node && !html.classList.contains("krv-cookie-chosen") && !dialogOpen();
    if (!open) {
      html.classList.remove("krv-cookie-bar-open");
      return;
    }
    html.classList.add("krv-cookie-bar-open");
    html.style.setProperty("--krv-cookie-bar-space", node.offsetHeight + "px");
  }

  function dialogOpen() {
    var node = dialog();
    return !!(node && !node.hidden);
  }

  function apply(value, reloadIfRevoked) {
    if (!writeConsent(value)) {
      return;
    }
    closeDialog();
    syncConsent(reloadIfRevoked);
  }

  // A bfcache restore does not run DOMContentLoaded or the head bootstrap again.
  // Cookies can also change in another tab while this document stays alive.
  function syncConsent(reloadIfRevoked) {
    var choice = readConsent();
    // The head already published initial state; do not restart ad placements at boot.
    var changed = currentChoice !== null && currentChoice !== choice;
    currentChoice = choice;
    var chosen = choice === "a" || choice === "n";
    if (chosen && readRaw("krv_consent") !== choice) {
      writeConsent(choice);
    }
    root().classList.toggle("krv-cookie-chosen", chosen);
    syncBarSpace();
    window.krvConsent = {
      analytics: choice === "a",
      // Recommendations retain the existing opt-out policy; "n" disables them.
      ads: choice !== "n",
      necessary: true
    };
    if (changed) {
      window.dispatchEvent(new Event("krv-consent-change"));
    }
    if (reloadIfRevoked && ((analyticsLoaded && choice !== "a") || (contextLoaded && choice === "n"))) {
      // Already executed third-party code cannot be unloaded safely in-place.
      location.reload();
      return;
    }
    if (choice === "a") {
      loadAnalytics();
      if (changed) {
        window.dispatchEvent(new Event("krv-ads-ready"));
      }
      return;
    }
    if (!chosen) {
      loadContext();
    }
  }

  function refreshConsent() {
    syncConsent(true);
  }

  function openSettings() {
    var node = dialog();
    var box = analyticsBox();
    if (!node) {
      return;
    }
    if (box) {
      box.checked = readConsent() === "a";
    }
    if (node.hidden) {
      settingsFocus = document.activeElement;
    }
    node.hidden = false;
    syncBarSpace();
    var focus = box || node.querySelector("button");
    if (focus) {
      focus.focus();
    }
  }

  function closeDialog() {
    var node = dialog();
    var wasOpen = dialogOpen();
    if (node) {
      node.hidden = true;
    }
    if (location.hash === "#krv-cookie-settings") {
      history.replaceState(null, "", location.pathname + location.search);
    }
    if (wasOpen && settingsFocus && settingsFocus.isConnected) {
      settingsFocus.focus();
    }
    syncBarSpace();
  }

  function onClick(event) {
    var settingsLink = event.target.closest('a[href="#krv-cookie-settings"]');
    if (settingsLink) {
      event.preventDefault();
      openSettings();
      return;
    }
    var button = event.target.closest("[data-krv-consent]");
    if (!button) {
      return;
    }
    var action = button.getAttribute("data-krv-consent");
    if (action === "settings") {
      openSettings();
      return;
    }
    if (action === "close") {
      closeDialog();
      return;
    }
    if (action === "save") {
      var box = analyticsBox();
      apply(box && box.checked ? "a" : "n", true);
      return;
    }
    if (action === "a" || action === "n") {
      apply(action, true);
    }
  }

  function onKey(event) {
    if (event.key === "Escape" && dialogOpen()) {
      closeDialog();
    }
    if (event.key === "Tab" && dialogOpen()) {
      var controls = dialog().querySelectorAll('button, input:not([disabled])');
      var first = controls[0];
      var last = controls[controls.length - 1];
      if ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last)) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
      }
    }
  }

  function start() {
    if (started) {
      return;
    }
    started = true;
    syncConsent(false);
    document.addEventListener("click", onClick);
    document.addEventListener("keydown", onKey);
    window.addEventListener("resize", syncBarSpace);
    window.addEventListener("pageshow", refreshConsent);
    window.addEventListener("focus", refreshConsent);
    document.addEventListener("visibilitychange", function () {
      if (!document.hidden) {
        refreshConsent();
      }
    });
    if (location.hash === "#krv-cookie-settings") {
      openSettings();
    }
    syncBarSpace();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();
