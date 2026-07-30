/**
 * TOC open-state: long outlines stay collapsed on small screens,
 * expand on desktop where vertical space is cheaper.
 */
(function () {
  function apply() {
    var details = document.querySelector(".drslon-toc__details[data-toc-long]");
    if (!details) return;

    var desktop = window.matchMedia("(min-width: 782px)").matches;
    // Only auto-open on desktop. Do not force-close if user already toggled.
    if (desktop && !details.hasAttribute("data-toc-user")) {
      details.open = true;
    }
  }

  function markUser() {
    var details = document.querySelector(".drslon-toc__details[data-toc-long]");
    if (details) details.setAttribute("data-toc-user", "1");
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", apply);
  } else {
    apply();
  }

  document.addEventListener(
    "toggle",
    function (e) {
      if (e.target && e.target.matches && e.target.matches(".drslon-toc__details[data-toc-long]")) {
        markUser();
      }
    },
    true
  );

  // Optional: if user resizes phone→desktop, expand once (unless they toggled).
  if (window.matchMedia) {
    var mq = window.matchMedia("(min-width: 782px)");
    if (mq.addEventListener) {
      mq.addEventListener("change", apply);
    } else if (mq.addListener) {
      mq.addListener(apply);
    }
  }
})();
