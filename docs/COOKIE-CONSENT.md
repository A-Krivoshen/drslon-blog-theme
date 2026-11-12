# Cookie consent: persistence and lifecycle

## Confirmed failure

Reproduced on production on 2026-11-12, before this fix:

1. Open the home page without consent.
2. Navigate to `/blog/` and accept cookies there.
3. Return to the home page with the browser's Back button.

`krv_consent=a` remained present, with `Path=/`, `Secure`, `SameSite=Lax`
and a 365-day expiry. However, Chromium restored the original home document
from its back/forward cache (`pageshow.persisted === true`). That document
still had a visible notice and no `krv-cookie-chosen` class.

The original code only read consent during the head bootstrap and
`DOMContentLoaded`. Neither runs again on a back/forward-cache restore.
An already open tab likewise retained stale consent and analytics state.
This is a lifecycle synchronization failure, not a failed cookie write.

## Fix and storage contract

- Synchronize the notice, `window.krvConsent` and optional script loaders
  on startup, `pageshow`, window focus and return to a visible document.
- Register event handlers once. Repeated lifecycle events must not insert
  additional tracking scripts or repeatedly publish readiness events.
- After revocation, reload a document that already ran optional scripts.
  A restored or previously open document must not keep running analytics
  after another tab saved `n`.
- Keep the existing early head bootstrap so a valid stored choice hides
  the notice before the deferred footer script is available.
- Treat malformed percent encoding as invalid consent, not an uncaught
  exception that prevents initialization and click handlers.
- Preserve existing legacy cookies. Migrate recognized Moove preferences
  only when no valid `krv_consent` exists; also recognize numeric `0`.
- Verify cookie writes before presenting a newly saved choice as persisted.
- Keep keyboard focus within settings and return it on close.

Storage remains a host-only cookie, `krv_consent=a|n`, `Path=/`,
`Max-Age=31536000`, `SameSite=Lax`, and `Secure` on HTTPS.
No consent localStorage or server-side per-user HTML was added.

## Advertising policy deliberately unchanged

The production theme and `drslon-site-core` explicitly use an opt-out policy
for Yandex recommendations: `context.js` may run before any choice, while
Metrika and Top100 wait for `a`. The notice already explains this distinction.
`n` disables all three loaders, including after reload or cross-tab revocation.
Changing recommendations to opt-in would be a separate business/policy change,
not necessary to repair persistence. No advertising identifiers or placement
settings are changed here.

## Browser regression tests

Tests require Node.js, PHP and Playwright with local Chromium/Firefox builds.
Install test tools on a development machine, never on production:

```sh
npm install --prefix /tmp/opencode/cookie-consent-tools --no-audit --no-fund playwright@1.65.0
/tmp/opencode/cookie-consent-tools/node_modules/.bin/playwright install chromium firefox
NODE_PATH=/tmp/opencode/cookie-consent-tools/node_modules TMPDIR=/tmp/opencode \
  node --test tests/cookie-consent.test.cjs
```

The default fixture renders the actual PHP head and footer with WordPress
stubs and serves the actual JavaScript/CSS. To test the deployed integration:

```sh
CONSENT_TEST_URL=https://krivoshein.site \
  NODE_PATH=/tmp/opencode/cookie-consent-tools/node_modules TMPDIR=/tmp/opencode \
  node --test tests/cookie-consent.test.cjs
```

Coverage includes both choices; six page types; reload/new tab; actual
browser-process restart with a persistent profile; history navigation;
cross-tab acceptance/revocation; settings and keyboard focus; early hiding
with blocked deferred JavaScript; loader deduplication; legacy migration;
invalid encoding; cookie reset; blocked writes; mobile viewport/theme toggle.
Tracking responses are stubbed to avoid generating analytics visits.
This checks consent gates and script insertion, not successful ad fill.

Chromium is required to perform a genuine back/forward-cache restore.
Firefox automation may reload history entries instead; the test reports
that limitation and additionally exercises a persisted `pageshow` explicitly.

### Verification on 2026-11-12

- All 38 tests passed both against the local fixture and against production,
  using Chromium 158 and Firefox 159. Revocation assertions wait for the
  replacement document, not just the old document's immediately updated flag.
- Both persistent choices survived an actual browser-process restart.
- A real Chromium bfcache restore passed after the production deployment.
- Separate, unstubbed Chromium smoke testing received HTTP 200 for Metrika,
  Top100 and Yandex context scripts, with no console errors or uncaught
  exceptions. Revoking consent reloaded the page without those scripts.
- All six checked page types returned HTTP 200, referenced the new cookie
  script version, and included the new head bootstrap. Served optimized CSS
  retained the cookie hiding rules.
- No new PHP warnings/fatal errors appeared in the Nginx error log during
  deployment checks. Active Moove GDPR plugin was not present.
- Actual Firefox bfcache restoration and physical mobile/Safari devices were
  not verified; Firefox history navigation, persisted-event handling and
  mobile viewports in both automated browsers were verified.

## Deployment and cache scope

Production uses SSH alias `beget`, source checkout
`/home/drslon/src/drslon-blog-theme`, and
`/home/drslon/bin/deploy-static.sh theme --apply` to copy runtime files to
`/var/www/krivoshein.site/htdocs/wp-content/themes/drslon-blog-theme`.
The active theme directory itself is not a Git checkout.

Before deploying, require a clean source checkout on `main`, verify the remote,
and compare runtime file hashes with the previous commit. Back up changed
runtime files outside the web root. Update the source checkout with a
fast-forward only, inspect the deployment dry run, then apply it.

The bootstrap appears in every public HTML page. Invalidate WP Fastest Cache
HTML/mobile HTML after deployment so pages reference the new `filemtime`
version of `krv-cookie.js`. Keep Redis, minified CSS, Nginx and PHP-FPM running;
no consent-specific cache key is needed. Verify the source checkout SHA and
deployed file hashes, then repeat browser tests against production.

For rollback, restore only the backed-up changed theme files, invalidate the
same HTML cache and verify. Reconcile Git with a normal revert commit rather
than resetting or force-pushing published history.
