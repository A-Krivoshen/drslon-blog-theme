/**
 * Cookie choice for krivoshein.site.
 * krv_consent: a=all, n=necessary, m=metrics only, d=ads only.
 * Historical domain cookies and Moove preferences are migrated without opting in.
 */
(function () {
  var METRIKA_ID = 51907757;
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

  function adsBox() {
    return document.getElementById("krv-cookie-ads");
  }

  function readValues(name) {
    var values = [];
    try {
      var cookies = document.cookie.split(";");
      for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].replace(/^\s+/, "");
        if (cookie.indexOf(name + "=") !== 0) continue;
        try { values.push(decodeURIComponent(cookie.slice(name.length + 1))); } catch (error) {}
      }
    } catch (error) {}
    return values;
  }

  function allowsAnalytics(choice) {
    return choice === "a" || choice === "m";
  }

  function allowsAds(choice) {
    // Preserve the existing opt-out policy for recommendations before any choice.
    return choice !== "n" && choice !== "m";
  }

  function choiceFromFlags(analytics, ads) {
    return analytics ? (ads ? "a" : "m") : (ads ? "d" : "n");
  }

  function flag(value) {
    if (value === 1 || value === "1" || value === true) return true;
    if (value === 0 || value === "0" || value === false) return false;
    return null;
  }

  function restrictChoices(choices) {
    var analytics = true;
    var ads = true;
    for (var i = 0; i < choices.length; i++) {
      analytics = analytics && allowsAnalytics(choices[i]);
      ads = ads && allowsAds(choices[i]);
    }
    return choiceFromFlags(analytics, ads);
  }

  function readConsent() {
    var values = readValues("krv_consent");
    var current = [];
    var legacy = [];
    for (var i = 0; i < values.length; i++) {
      var value = values[i];
      if (/^[anmd]$/.test(value)) {
        current.push(value);
      } else if (value === "all" || value === "necessary") {
        legacy.push(value === "all" ? "a" : "n");
      } else {
        try {
          var data = JSON.parse(value);
          if (!data || typeof data !== "object") continue;
          var analytics = flag(data.a);
          var ads = flag(data.d);
          if (analytics !== null && ads !== null) legacy.push(choiceFromFlags(analytics, ads));
        } catch (error) {}
      }
    }
    // A later explicit compact choice overrides the older MU-plugin format.
    // Conflicting compact duplicates are combined conservatively until normalized.
    if (current.length) return restrictChoices(current);
    if (legacy.length) return restrictChoices(legacy);
    values = readValues("moove_gdpr_popup");
    for (var j = 0; j < values.length; j++) {
      try {
        var moove = JSON.parse(values[j]);
        if (moove && flag(moove.thirdparty) === true) legacy.push("a");
        else if (moove && (flag(moove.thirdparty) === false || flag(moove.strict) === true)) legacy.push("n");
      } catch (error) {}
    }
    return legacy.length ? restrictChoices(legacy) : "";
  }

  function writeConsent(value) {
    if (!/^[anmd]$/.test(value)) return false;
    var secure = location.protocol === "https:" ? "; Secure" : "";
    try {
      document.cookie = "krv_consent=" + value + "; Path=/; Max-Age=" + YEAR + "; SameSite=Lax" + secure;
      if (readValues("krv_consent").indexOf(value) === -1) return false;
      // The previous MU-plugin used Domain=.krivoshein.site. A host-only write
      // does not replace that cookie. Remove only same-name obsolete scopes.
      var domains = [""];
      if (location.hostname.indexOf(".") !== -1 && !/^[0-9.]+$/.test(location.hostname)) {
        domains.push("; Domain=" + location.hostname);
      }
      if (/\.krivoshein\.site$/.test(location.hostname)) domains.push("; Domain=krivoshein.site");
      var paths = ["/"];
      var parts = location.pathname.split("/");
      var path = "";
      for (var i = 1; i < parts.length; i++) {
        if (!parts[i]) continue;
        path += "/" + parts[i];
        paths.push(path, path + "/");
      }
      for (var p = 0; p < paths.length; p++) {
        for (var d = 0; d < domains.length; d++) {
          if (paths[p] === "/" && domains[d] === "") continue;
          document.cookie = "krv_consent=; Path=" + paths[p] + domains[d] + "; Max-Age=0; SameSite=Lax" + secure;
        }
      }
      return readConsent() === value;
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
    var adsWereAllowed = allowsAds(currentChoice);
    currentChoice = choice;
    var chosen = choice !== "";
    var values = readValues("krv_consent");
    if (chosen && (values.length !== 1 || values[0] !== choice)) {
      writeConsent(choice);
    }
    root().classList.toggle("krv-cookie-chosen", chosen);
    syncBarSpace();
    window.krvConsent = {
      analytics: allowsAnalytics(choice),
      ads: allowsAds(choice),
      necessary: true
    };
    if (changed) {
      window.dispatchEvent(new Event("krv-consent-change"));
    }
    if (reloadIfRevoked && ((analyticsLoaded && !allowsAnalytics(choice)) || (contextLoaded && !allowsAds(choice)))) {
      // Already executed third-party code cannot be unloaded safely in-place.
      location.reload();
      return;
    }
    if (allowsAnalytics(choice)) {
      loadAnalytics();
    }
    if (allowsAds(choice)) {
      loadContext();
      if (changed && !adsWereAllowed) {
        window.dispatchEvent(new Event("krv-ads-ready"));
      }
    }
  }

  function refreshConsent() {
    syncConsent(true);
  }

  function openSettings() {
    var node = dialog();
    var box = analyticsBox();
    var ads = adsBox();
    if (!node) {
      return;
    }
    if (box) {
      box.checked = allowsAnalytics(readConsent());
    }
    if (ads) ads.checked = allowsAds(readConsent());
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
      var ads = adsBox();
      apply(choiceFromFlags(box && box.checked, ads && ads.checked), true);
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
    // Native Enter activates the focused button; never capture Enter globally.
    if (!readConsent() && !dialogOpen() && (document.activeElement === document.body || document.activeElement === root())) {
      var accept = bar() && bar().querySelector('[data-krv-consent="a"]');
      if (accept) accept.focus({ preventScroll: true });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();
