# Cookie consent: persistence and lifecycle

## Historical cookie conflict missed by the first fix

The previous MU-plugin, found in the server's source repository, stored
`krv_consent` with `Domain=.krivoshein.site`, using `all`, `necessary`, or JSON
such as `{"a":1,"d":0}`. The replacement theme understood only `a` and `n`
and wrote a host-only cookie. Those cookies can coexist: a host-only write
does not replace a domain cookie.

The failure was reproduced against production in Firefox, Chromium and the
actual Yandex Browser binary. For example, `document.cookie` contained
`krv_consent=necessary; krv_consent=a` after accepting. Reading only the first
match returned the old, unrecognized value, so the banner remained visible.
The first regression suite used clean browser profiles and missed this state.
The old MU-plugin is not active; its persisted browser data still matters.

The reader now examines all same-name cookies, recognizes the former formats,
and prefers a valid new compact choice over an older serialized choice.
Conflicting compact duplicates are combined conservatively, never opting a
previous refusal into tracking. After a confirmed root-cookie write, only
obsolete scopes of `krv_consent` are expired. Other cookies, including Moove
preferences, are not removed.

## Back/forward-cache failure

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
- Focus the initial Accept button only if focus is still on the document.
  Enter uses native button activation; no global Enter handler is added.
- Preserve separate analytics and advertising preferences from historical JSON.
  Both checkboxes remain editable in cookie settings.

Storage remains a host-only `krv_consent` cookie, `Path=/`,
`Max-Age=31536000`, `SameSite=Lax`, and `Secure` on HTTPS. Values:

| Value | Yandex Metrika | Recommendations/RSYA |
| --- | --- | --- |
| `a` | On | On |
| `n` | Off | Off |
| `m` | On | Off |
| `d` | Off | On |

The extra compact values preserve mixed legacy preferences without granting
an additional category or withdrawing a separately granted one during migration.
No consent localStorage or server-side per-user HTML was added.

## Advertising policy deliberately unchanged

The production theme and `drslon-site-core` explicitly use an opt-out policy
for Yandex recommendations: `context.js` may run before any choice, while
Metrika waits for analytics permission (`a` or `m`). The notice explains this
distinction. `n` disables both loaders, including after reload or cross-tab
revocation. Advertising has its own settings checkbox and can be disabled
without disabling Metrika. Only the Yandex services remain in the loader.
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
stubs and serves the actual JavaScript/CSS. Its DNS-shaped origin is forwarded
to a local HTTP server only within isolated test contexts, allowing real
parent-domain/host-only duplicate-cookie tests. If a local Yandex Browser binary
exists, it is tested too; `CONSENT_YANDEX_EXECUTABLE` overrides its path.
To test the deployed integration:

```sh
CONSENT_TEST_URL=https://krivoshein.site \
  NODE_PATH=/tmp/opencode/cookie-consent-tools/node_modules TMPDIR=/tmp/opencode \
  node --test tests/cookie-consent.test.cjs
```

Coverage includes all four choices; six page types; reload/new tab; actual
browser-process restart with a persistent profile; history navigation;
cross-tab acceptance/revocation; settings and keyboard focus; early hiding
with blocked deferred JavaScript, including historical preferences;
loader deduplication; legacy migration and conflicting Domain/Path scopes;
invalid encoding; cookie reset; blocked writes; mobile viewport/theme toggle;
Enter acceptance and preservation of normal Enter behavior in forms.
Tracking responses are stubbed to avoid generating analytics visits.
This checks consent gates and script insertion, not successful ad fill.

Chromium is required to perform a genuine back/forward-cache restore.
Firefox automation may reload history entries instead; the test reports
that limitation and additionally exercises a persisted `pageshow` explicitly.

Revocation assertions wait for the replacement document, not just the old
document's immediately updated flag. Physical macOS browsers and mobile/Safari
devices are not implied by automated Linux browser coverage.

Pre-commit verification: all 102 local browser regressions passed using
Chromium 158, Firefox 159 and the actual Yandex Browser 26.3 executable.
All six historical cookie representations were checked before the deferred
script could run, as well as after migration. The policy update also passed
its dry run against the existing production page without database writes.

## Policy content correction

`scripts/update-cookie-policy.php` corrects only the existing policy page,
its `_genesis_description` and its featured-image alt text. It removes the
retired counter, describes recommendations/RSYA and fixes the old settings
anchors. It does not change the page slug, title, design or publication date.

Run the script through WP-CLI with the social autopost plugins skipped, as
documented in its header. The default is a database-free dry run. Applying
requires an administrator with `unfiltered_html`, the reviewed original
content SHA in `KRV_POLICY_EXPECTED_SHA` and
an unused `KRV_POLICY_BACKUP` path outside the web root. The original post and
affected description/image alt are backed up before any update. An unrelated
legacy page-template value is preserved without revalidation by the current
block theme; otherwise WordPress can return a template error after writing
the body. An unchanged body is not written again.

Run `TMPDIR=/tmp/opencode php tests/update-cookie-policy.test.php` for eight
standalone migration regressions, including template preservation, metadata-only
updates, HTML capability, SHA/backup guards and idempotence.

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

The bootstrap appears in every public HTML page. After deployment, clear all
of this site's WP Fastest Cache HTML/mobile/widget/minified assets so pages
reference the new `filemtime` version of `krv-cookie.js`. For a full site Redis
purge, use the existing drop-in's selective prefix flush with
`WP_REDIS_SELECTIVE_FLUSH=1` for that command; do not flush a shared Redis DB.
Invalidate PHP-FPM OPcache entries belonging to this site's document root.

The audited Nginx site uses FastCGI forwarding but no FastCGI/proxy cache zone.
Do not claim to purge an unconfigured cache, restart the server, drop Linux
page caches, or flush neighboring projects. No consent-specific cache key is
needed. Verify the source checkout SHA and deployed file hashes, then repeat
browser tests against production.

For rollback, restore only the backed-up changed theme files, invalidate the
same HTML cache and verify. Reconcile Git with a normal revert commit rather
than resetting or force-pushing published history.
