/**
 * Browser regressions. Requires PHP, Playwright and its Chromium/Firefox builds locally.
 * Run: node --test tests/cookie-consent.test.cjs
 * Live smoke: CONSENT_TEST_URL=https://krivoshein.site node --test tests/cookie-consent.test.cjs
 * Tracking and the unrelated chat widget are stubbed; no analytics visits are sent.
 */
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const { before, after, beforeEach, afterEach, describe, it } = require('node:test');
const { chromium, firefox } = require('playwright');

const root = path.resolve(__dirname, '..');
const live = process.env.CONSENT_TEST_URL;
const paths = ['/', '/blog/', '/sait-snova-otkryt-kopii-na-github/', '/category/devops/', '/konsultatsii/', '/politika-v-otnoshenii-obrabotki-personalnyh-dannyh/'];
let server;
let base;
let fixtureOrigin;

before(async () => {
  if (live) {
    base = live.replace(/\/$/, '');
    return;
  }
  // Render the real PHP bootstrap, notice and dialog, without installing WordPress.
  const markup = JSON.parse(execFileSync('php', ['-r', `
    define('ABSPATH', __DIR__);
    function is_admin(){return false;} function is_feed(){return false;}
    function is_embed(){return false;} function wp_is_json_request(){return false;}
    function add_action(...$args){} function add_filter(...$args){}
    function home_url($p){return $p;}
    function esc_url($p){return htmlspecialchars($p, ENT_QUOTES);}
    require 'inc/krv-cookie.php';
    require 'inc/enqueue-assets.php';
    ob_start(); drslon_krv_cookie_head(); $head=ob_get_clean();
    ob_start(); drslon_theme_fouc_script(); $fouc=ob_get_clean();
    ob_start(); drslon_krv_cookie_footer(); $footer=ob_get_clean();
    echo json_encode(['head'=>$head, 'fouc'=>$fouc, 'footer'=>$footer]);
  `], { cwd: root, encoding: 'utf8' }));
  server = http.createServer((req, res) => {
    const pathname = new URL(req.url, 'http://localhost').pathname;
    res.setHeader('Cache-Control', 'no-cache, must-revalidate');
    const assets = {
      '/cookie.js': ['assets/js/krv-cookie.js', 'text/javascript'],
      '/theme.js': ['assets/js/krv-theme.js', 'text/javascript'],
      '/cookie.css': ['assets/css/components/11-cookie.css', 'text/css']
    };
    if (assets[pathname]) {
      const [file, type] = assets[pathname];
      res.setHeader('Content-Type', type);
      res.end(fs.readFileSync(path.join(root, file)));
      return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.end(`<!doctype html><html><head><meta charset="utf-8">${markup.fouc}${markup.head}
      <link rel="stylesheet" href="/cookie.css"></head><body>
      <header class="drslon-header-utility"><button class="krv-theme-btn" type="button">Theme</button></header>
      <nav><a href="/">Home</a> <a href="/blog/">Blog</a></nav>
      <main><h1>Cookie consent fixture</h1></main>
      <footer><a href="#krv-cookie-settings">Настройка Cookies</a></footer>
      ${markup.footer}<script defer src="/cookie.js"></script>
      <script defer src="/theme.js"></script></body></html>`);
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  fixtureOrigin = `http://127.0.0.1:${server.address().port}`;
  // A real DNS-shaped origin is needed to reproduce parent-domain/host-only duplicates.
  // Requests are forwarded only inside these isolated browser contexts.
  base = `http://cookie.krivoshein.site:${server.address().port}`;
});

after(async () => {
  if (server) await new Promise(resolve => server.close(resolve));
});

const yandexExecutable = process.env.CONSENT_YANDEX_EXECUTABLE || '/opt/yandex/browser/yandex_browser';
const browsers = { chromium, firefox };
if (fs.existsSync(yandexExecutable)) browsers.yandex = chromium;

for (const [name, type] of Object.entries(browsers)) {
  describe(name, { concurrency: false }, () => {
    let browser;
    let context;
    let errors;
    let removedCounterRequests;
    const launchOptions = name === 'chromium'
      ? { channel: 'chromium', ignoreDefaultArgs: ['--disable-back-forward-cache'] }
      : name === 'yandex' ? { executablePath: yandexExecutable, ignoreDefaultArgs: ['--disable-back-forward-cache'] } : {};

    async function prepare(ctx) {
      if (fixtureOrigin) {
        await ctx.route(base + '/**', async route => {
          const response = await route.fetch({ url: route.request().url().replace(base, fixtureOrigin) });
          await route.fulfill({ response });
        });
      }
      // Preserve the consent gates and request counts; do not execute tracking.
      await ctx.route(/^https:\/\/(?:mc\.yandex\.ru|st\.top100\.ru|yandex\.ru)\//, route =>
        route.fulfill({ contentType: 'text/javascript', body: '' }));
      // The remote deferred widget can stall DOMContentLoaded independently of
      // consent. Keep the component suite independent of that service's latency.
      await ctx.route('https://support.krivoshein.site/widget/krv-assistant.js*', route =>
        route.fulfill({ contentType: 'text/javascript', body: '' }));
      ctx.on('page', page => page.on('pageerror', error => errors.push(error.message)));
      ctx.on('request', request => {
        if (/top100|rambler/i.test(request.url())) removedCounterRequests.push(request.url());
      });
    }

    async function visit(page, pathname = '/') {
      const response = await page.goto(base + pathname, { waitUntil: 'domcontentloaded' });
      assert.equal(response.status(), 200, pathname);
      await page.waitForFunction(() => !!window.krvConsent);
      return page;
    }

    async function hidden(page) {
      await page.waitForFunction(() => {
        const bar = document.getElementById('krv-cookie');
        return bar && getComputedStyle(bar).display === 'none';
      });
    }

    async function choose(page, value) {
      const click = () => page.locator(`#krv-cookie [data-krv-consent="${value}"]`).click();
      if (value === 'n') {
        // The first refusal reloads the document to unload opt-out recommendations.
        await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), click()]);
      } else {
        await click();
      }
      await hidden(page);
      assert.equal((await context.cookies(base)).find(c => c.name === 'krv_consent').value, value);
    }

    async function cookie(value, cookieName = 'krv_consent', scope = {}) {
      const stored = {
        name: cookieName, value, url: base + '/',
        expires: Math.floor(Date.now() / 1000) + 31536000,
        secure: base.startsWith('https:'), sameSite: 'Lax', ...scope
      };
      if (scope.domain) delete stored.url;
      await context.addCookies([stored]);
    }

    before(async () => {
      if (name !== 'yandex') browser = await type.launch({ headless: true, ...launchOptions });
    });
    after(async () => { if (name !== 'yandex') await browser.close(); });
    beforeEach(async () => {
      errors = [];
      removedCounterRequests = [];
      // Unlike bundled Chromium, the external Yandex executable is not pinned
      // to Playwright's CDP version. Isolate its process as well as its context.
      if (name === 'yandex') browser = await type.launch({ headless: true, ...launchOptions });
      context = await browser.newContext();
      await prepare(context);
    });
    afterEach(async () => {
      await context.close();
      if (name === 'yandex') await browser.close();
      assert.deepEqual(errors, [], 'No uncaught JavaScript exceptions');
      assert.deepEqual(removedCounterRequests, [], 'Removed counter must never be requested');
    });

    it('shows the banner to a new visitor; analytics waits for consent', async () => {
      const page = await visit(await context.newPage());
      assert.equal(await page.locator('#krv-cookie').isVisible(), true);
      assert.equal(await page.evaluate(() => window.krvConsent.analytics), false);
      const sources = await page.locator('script[src]').evaluateAll(nodes => nodes.map(n => n.src));
      assert.equal(sources.some(s => /metrika\/tag\.js|top100\/top100\.js/.test(s)), false);
      // Existing production policy deliberately allows recommendations before a refusal.
      assert.equal(await page.evaluate(() => window.krvConsent.ads), true);
    });

    for (const value of ['a', 'n']) {
      it(`persists ${value} across six pages, reload and a new tab`, async () => {
        const page = await visit(await context.newPage());
        await choose(page, value);
        const stored = (await context.cookies(base)).find(c => c.name === 'krv_consent');
        assert.equal(stored.path, '/');
        assert.equal(stored.domain, new URL(base).hostname);
        assert.equal(stored.sameSite, 'Lax');
        assert.equal(stored.secure, base.startsWith('https:'));
        assert.ok(Math.abs(stored.expires - Date.now() / 1000 - 31536000) < 15);
        for (const pathname of paths) {
          await visit(page, pathname);
          await hidden(page);
          assert.deepEqual(await page.evaluate(() => window.krvConsent), {
            analytics: value === 'a', ads: value === 'a', necessary: true
          });
        }
        await page.reload({ waitUntil: 'domcontentloaded' });
        await hidden(page);
        await hidden(await visit(await context.newPage(), '/blog/'));
        assert.equal((await context.cookies(base)).filter(c => c.name === 'krv_consent').length, 1);
      });

      it(`keeps ${value} after closing and restarting a persistent browser profile`, async () => {
        const profile = fs.mkdtempSync(path.join(os.tmpdir(), `krv-consent-${name}-`));
        let persistent;
        try {
          persistent = await type.launchPersistentContext(profile, { headless: true, ...launchOptions });
          await prepare(persistent);
          const page = await visit(await persistent.newPage());
          await page.locator(`#krv-cookie [data-krv-consent="${value}"]`).click();
          await hidden(page);
          await persistent.close();
          persistent = await type.launchPersistentContext(profile, { headless: true, ...launchOptions });
          await prepare(persistent);
          await hidden(await visit(await persistent.newPage(), '/blog/'));
          assert.equal((await persistent.cookies(base)).find(c => c.name === 'krv_consent').value, value);
        } finally {
          if (persistent) await persistent.close();
          // Only this test's freshly created browser profile is removed.
          fs.rmSync(profile, { recursive: true, force: true });
        }
      });
    }

    it('reconciles back/forward navigation and persisted pageshow after accepting', async t => {
      const page = await context.newPage();
      await page.addInitScript(() => {
        window.__consentVisit = Math.random();
        window.__consentPageShows = [];
        addEventListener('pageshow', event => window.__consentPageShows.push(event.persisted));
      });
      await visit(page);
      await page.waitForFunction(() => window.__consentPageShows.length > 0);
      const visitId = await page.evaluate(() => window.__consentVisit);
      await visit(page, '/blog/');
      await choose(page, 'a');
      await page.goBack({ waitUntil: 'commit' });
      const restored = await page.evaluate(() => window.__consentVisit) === visitId;
      if (name === 'chromium' || restored) {
        assert.equal(restored, true, 'Document really restored, not reloaded');
        assert.equal(await page.evaluate(() => window.__consentPageShows.includes(true)), true);
      } else {
        t.diagnostic(`${name} automation reloaded history entry; additionally exercising persisted pageshow explicitly.`);
        await page.evaluate(() => {
          document.documentElement.classList.remove('krv-cookie-chosen');
          dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true }));
        });
      }
      await hidden(page);
      assert.equal(await page.evaluate(() => window.krvConsent.analytics), true);
      await page.goForward({ waitUntil: 'commit' });
      await hidden(page);
    });

    it('resynchronizes a previously open tab and honors cross-tab revocation', async () => {
      const original = await visit(await context.newPage());
      const other = await visit(await context.newPage(), '/blog/');
      await choose(other, 'a');
      await original.bringToFront();
      // Headless browser focus is platform-dependent; also exercise the native handler explicitly.
      await original.evaluate(() => dispatchEvent(new Event('focus')));
      await hidden(original);
      assert.equal(await original.evaluate(() => window.krvConsent.analytics), true);
      await other.locator('a[href="#krv-cookie-settings"]').first().click();
      await other.locator('#krv-cookie-analytics').uncheck();
      await other.locator('#krv-cookie-ads').uncheck();
      await Promise.all([
        other.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        other.locator('[data-krv-consent="save"]').click()
      ]);
      const reloaded = original.waitForNavigation({ waitUntil: 'domcontentloaded' });
      await original.bringToFront();
      await original.evaluate(() => dispatchEvent(new Event('focus'))).catch(() => {});
      await reloaded;
      await original.waitForFunction(() => window.krvConsent.analytics === false);
      await hidden(original);
      const sources = await original.locator('script[src]').evaluateAll(nodes => nodes.map(n => n.src));
      assert.equal(sources.some(s => /metrika\/tag\.js|top100\/top100\.js|ads\/system\/context\.js/.test(s)), false);
    });

    it('changes preferences through settings; traps and returns keyboard focus', async () => {
      await cookie('n');
      const page = await visit(await context.newPage());
      const link = page.locator('a[href="#krv-cookie-settings"]').first();
      await link.focus();
      await page.keyboard.press('Enter');
      assert.equal(await page.locator('#krv-cookie-analytics').evaluate(el => el === document.activeElement), true);
      await page.keyboard.press('Shift+Tab');
      assert.equal(await page.locator('#krv-cookie-settings button[data-krv-consent="close"]').evaluate(el => el === document.activeElement), true);
      await page.keyboard.press('Tab');
      assert.equal(await page.locator('#krv-cookie-analytics').evaluate(el => el === document.activeElement), true);
      await page.keyboard.press('Escape');
      assert.equal(await link.evaluate(el => el === document.activeElement), true);
      await link.click();
      await page.locator('#krv-cookie-analytics').check();
      await page.locator('#krv-cookie-ads').check();
      await page.locator('[data-krv-consent="save"]').click();
      await hidden(page);
      assert.equal((await context.cookies(base)).find(c => c.name === 'krv_consent').value, 'a');
      await page.reload({ waitUntil: 'domcontentloaded' });
      await link.click();
      await page.locator('#krv-cookie-analytics').uncheck();
      await page.locator('#krv-cookie-ads').uncheck();
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.locator('[data-krv-consent="save"]').click()
      ]);
      await page.waitForFunction(() => window.krvConsent.analytics === false);
      await hidden(page);
      assert.equal((await context.cookies(base)).find(c => c.name === 'krv_consent').value, 'n');
    });

    it('does not flash the banner while the deferred script is unavailable', async () => {
      await cookie('n');
      let release;
      const gate = new Promise(resolve => { release = resolve; });
      await context.route(/\/(?:krv-cookie|cookie)\.js(?:\?|$)/, async route => {
        await gate;
        await route.fallback();
      });
      const page = await context.newPage();
      await page.addInitScript(() => {
        document.addEventListener('DOMContentLoaded', () => { window.__cookieDOMReady = true; });
      });
      try {
        await page.goto(base + '/', { waitUntil: 'commit' });
        await hidden(page);
        assert.notEqual(await page.evaluate(() => window.__cookieDOMReady), true);
      } finally { release(); }
      await page.waitForLoadState('domcontentloaded');
      await hidden(page);
    });

    it('does not duplicate tracking scripts after repeated lifecycle events', async () => {
      await cookie('a');
      const page = await context.newPage();
      await page.addInitScript(() => {
        window.__consentEvents = [];
        for (const type of ['krv-consent-change', 'krv-ads-ready']) {
          addEventListener(type, () => window.__consentEvents.push(type));
        }
      });
      await visit(page);
      await page.evaluate(() => {
        for (let i = 0; i < 3; i++) {
          dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true }));
          dispatchEvent(new Event('focus'));
          document.dispatchEvent(new Event('visibilitychange'));
          document.dispatchEvent(new Event('DOMContentLoaded'));
        }
      });
      for (const marker of ['metrika/tag.js', 'ads/system/context.js']) {
        assert.equal(await page.locator(`script[src*="${marker}"]`).count(), 1, marker);
      }
      assert.deepEqual(await page.evaluate(() => window.__consentEvents), []);
    });

    it('accepts with Enter on the initially focused button, without a mouse', async () => {
      const page = await visit(await context.newPage());
      assert.equal(await page.locator('#krv-cookie [data-krv-consent="a"]').evaluate(el => el === document.activeElement), true);
      await page.keyboard.press('Enter');
      await hidden(page);
      assert.equal((await context.cookies(base)).find(c => c.name === 'krv_consent').value, 'a');
      await visit(page, '/blog/');
      await hidden(page);
    });

    it('does not capture Enter in forms or turn it into consent', async () => {
      const page = await visit(await context.newPage());
      await page.evaluate(() => {
        const form = document.createElement('form');
        form.innerHTML = '<input name="search" aria-label="Search test">';
        form.addEventListener('submit', event => { event.preventDefault(); window.__searchSubmitted = true; });
        document.body.prepend(form);
        form.querySelector('input').focus();
      });
      await page.keyboard.press('Enter');
      assert.equal(await page.evaluate(() => window.__searchSubmitted), true);
      assert.equal(await page.locator('#krv-cookie').isVisible(), true);
      assert.equal((await context.cookies(base)).some(c => c.name === 'krv_consent'), false);
    });

    it('does not steal focus if a visitor starts typing before deferred JavaScript loads', async () => {
      let release;
      const gate = new Promise(resolve => { release = resolve; });
      await context.route(/\/(?:krv-cookie|cookie)\.js(?:\?|$)/, async route => {
        await gate;
        await route.fallback();
      });
      const page = await context.newPage();
      try {
        await page.goto(base + '/', { waitUntil: 'commit' });
        await page.waitForFunction(() => !!document.getElementById('krv-cookie'));
        await page.evaluate(() => {
          const input = document.createElement('input');
          input.id = 'typing-before-consent';
          document.body.prepend(input);
          input.focus();
        });
      } finally { release(); }
      await page.waitForLoadState('domcontentloaded');
      assert.equal(await page.locator('#typing-before-consent').evaluate(el => el === document.activeElement), true);
    });

    for (const [legacy, expected, analytics, ads] of [
      ['all', 'a', true, true],
      ['necessary', 'n', false, false],
      ['{"a":1,"d":1}', 'a', true, true],
      ['{"a":0,"d":0}', 'n', false, false],
      ['{"a":1,"d":0}', 'm', true, false],
      ['{"a":0,"d":1}', 'd', false, true]
    ]) {
      it(`migrates historical parent-domain ${legacy} without losing either preference`, async () => {
        await cookie(encodeURIComponent(legacy), 'krv_consent', { domain: '.krivoshein.site', path: '/' });
        let release;
        const gate = new Promise(resolve => { release = resolve; });
        await context.route(/\/(?:krv-cookie|cookie)\.js(?:\?|$)/, async route => {
          await gate;
          await route.fallback();
        });
        const page = await context.newPage();
        try {
          await page.goto(base + '/', { waitUntil: 'commit' });
          await hidden(page);
          assert.deepEqual(await page.evaluate(() => window.krvConsent), { analytics, ads, necessary: true });
        } finally { release(); }
        await page.waitForLoadState('domcontentloaded');
        await hidden(page);
        assert.deepEqual(await page.evaluate(() => window.krvConsent), { analytics, ads, necessary: true });
        const saved = (await context.cookies(base)).filter(c => c.name === 'krv_consent');
        assert.equal(saved.length, 1);
        assert.equal(saved[0].value, expected);
        assert.equal(saved[0].domain, new URL(base).hostname);
        await visit(page, '/blog/');
        await hidden(page);
        assert.deepEqual(await page.evaluate(() => window.krvConsent), { analytics, ads, necessary: true });
      });
    }

    it('keeps a newer compact refusal over old domain consent and removes only the duplicate', async () => {
      await cookie('all', 'krv_consent', { domain: '.krivoshein.site', path: '/' });
      await cookie('n');
      await cookie('keep', 'unrelated_cookie', { domain: '.krivoshein.site', path: '/' });
      const page = await visit(await context.newPage());
      await hidden(page);
      assert.deepEqual(await page.evaluate(() => window.krvConsent), { analytics: false, ads: false, necessary: true });
      const saved = await context.cookies(base);
      assert.equal(saved.filter(c => c.name === 'krv_consent').length, 1);
      assert.equal(saved.find(c => c.name === 'unrelated_cookie').value, 'keep');
    });

    it('normalizes conflicting compact scopes conservatively and permits an explicit new choice', async () => {
      await cookie('n', 'krv_consent', { domain: '.krivoshein.site', path: '/' });
      await cookie('a');
      const page = await visit(await context.newPage());
      await hidden(page);
      assert.equal(await page.evaluate(() => window.krvConsent.analytics), false);
      await page.locator('a[href="#krv-cookie-settings"]').first().click();
      await page.locator('#krv-cookie-settings [data-krv-consent="a"]').click();
      assert.equal(await page.evaluate(() => window.krvConsent.analytics), true);
      assert.equal((await context.cookies(base)).filter(c => c.name === 'krv_consent').length, 1);
    });

    it('removes an invalid shadowing domain cookie when a choice is saved', async () => {
      await cookie('true', 'krv_consent', { domain: '.krivoshein.site', path: '/' });
      const page = await visit(await context.newPage());
      await choose(page, 'a');
      assert.equal((await context.cookies(base)).filter(c => c.name === 'krv_consent').length, 1);
      await visit(page, '/blog/');
      await hidden(page);
    });

    it('normalizes a shadowing subpath cookie and preserves the newer root preference', async () => {
      await cookie('necessary', 'krv_consent', { domain: '.krivoshein.site', path: '/blog/' });
      await cookie('a');
      const page = await visit(await context.newPage(), '/blog/');
      await hidden(page);
      assert.equal(await page.evaluate(() => window.krvConsent.analytics), true);
      assert.equal((await context.cookies(base + '/blog/')).filter(c => c.name === 'krv_consent').length, 1);
    });

    for (const [analytics, ads, expected] of [[true, false, 'm'], [false, true, 'd']]) {
      it(`saves independent analytics=${analytics}, ads=${ads} settings`, async () => {
        await cookie('n');
        const page = await visit(await context.newPage());
        await page.locator('a[href="#krv-cookie-settings"]').first().click();
        await page.locator('#krv-cookie-analytics').setChecked(analytics);
        await page.locator('#krv-cookie-ads').setChecked(ads);
        await page.locator('[data-krv-consent="save"]').click();
        await hidden(page);
        assert.equal((await context.cookies(base)).find(c => c.name === 'krv_consent').value, expected);
        await page.reload({ waitUntil: 'domcontentloaded' });
        assert.deepEqual(await page.evaluate(() => window.krvConsent), { analytics, ads, necessary: true });
        assert.equal(await page.locator('script[src*="metrika/tag.js"]').count(), analytics ? 1 : 0);
        assert.equal(await page.locator('script[src*="ads/system/context.js"]').count(), ads ? 1 : 0);
      });
    }

    for (const [legacy, expected] of [
      [{ strict: '1', thirdparty: '1' }, 'a'],
      [{ strict: '1', thirdparty: '0' }, 'n'],
      [{ strict: 0, thirdparty: 0 }, 'n']
    ]) {
      it(`migrates legacy ${JSON.stringify(legacy)} without deleting it`, async () => {
        await cookie(encodeURIComponent(JSON.stringify(legacy)), 'moove_gdpr_popup');
        const page = await visit(await context.newPage());
        await hidden(page);
        const cookies = await context.cookies(base);
        assert.equal(cookies.find(c => c.name === 'krv_consent').value, expected);
        assert.ok(cookies.find(c => c.name === 'moove_gdpr_popup'));
      });
    }

    it('keeps an explicit refusal over an older legacy acceptance', async () => {
      await cookie('n');
      await cookie(encodeURIComponent(JSON.stringify({ thirdparty: 1 })), 'moove_gdpr_popup');
      const page = await visit(await context.newPage());
      await hidden(page);
      assert.equal(await page.evaluate(() => window.krvConsent.analytics), false);
    });

    for (const cookieName of ['krv_consent', 'moove_gdpr_popup']) {
      it(`recovers from malformed percent encoding in ${cookieName}`, async () => {
        await cookie('%E0%A4%A', cookieName);
        const page = await visit(await context.newPage());
        assert.equal(await page.locator('#krv-cookie').isVisible(), true);
        await choose(page, 'n');
        await visit(page, '/blog/');
        await hidden(page);
      });
    }

    it('reopens the notice when consent is removed and resynchronizes visibility', async () => {
      await cookie('n');
      const page = await visit(await context.newPage());
      await hidden(page);
      await context.clearCookies({ name: 'krv_consent' });
      await page.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
      await page.waitForFunction(() => !document.documentElement.classList.contains('krv-cookie-chosen'));
      assert.equal(await page.locator('#krv-cookie').isVisible(), true);
    });

    it('does not pretend a choice was saved when cookie writes are blocked', async () => {
      const page = await context.newPage();
      await page.addInitScript(() => {
        Object.defineProperty(document, 'cookie', {
          get() { return ''; },
          set() { throw new DOMException('Cookie access blocked', 'SecurityError'); }
        });
      });
      await visit(page);
      await page.locator('#krv-cookie [data-krv-consent="a"]').click();
      assert.equal(await page.locator('#krv-cookie').isVisible(), true);
      assert.equal(await page.evaluate(() => window.krvConsent.analytics), false);
    });

    it('keeps consent and the independent theme toggle working on a mobile viewport', async () => {
      const page = await context.newPage();
      await page.setViewportSize({ width: 390, height: 844 });
      await page.clock.install({ time: '2026-11-13T23:00:00' });
      await visit(page);
      await choose(page, 'n');
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
      await page.locator('.drslon-header-utility .krv-theme-btn').first().click();
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'light');
      await page.reload({ waitUntil: 'domcontentloaded' });
      await hidden(page);
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'light');
      assert.equal(await page.evaluate(() => localStorage.getItem('krv_consent')), null);
    });

    it('defaults to auto: theme follows the time of day before deferred scripts load', async () => {
      for (const [time, expected] of [['2026-11-13T12:00:00', 'light'], ['2026-11-13T23:30:00', 'dark'], ['2026-11-13T06:59:00', 'dark'], ['2026-11-13T07:00:00', 'light']]) {
        const page = await context.newPage();
        await page.clock.install({ time });
        await page.goto(base + '/', { waitUntil: 'commit' });
        // The head bootstrap decides before the deferred theme script runs.
        assert.equal(await page.evaluate(() => document.documentElement.getAttribute('data-theme')), expected, time);
        await page.waitForLoadState('domcontentloaded');
        assert.equal(await page.locator('html').getAttribute('data-theme'), expected, time);
        assert.equal(await page.evaluate(() => localStorage.getItem('krv_theme')), null, time);
        const label = await page.locator('.drslon-header-utility .krv-theme-btn').getAttribute('title');
        assert.match(label, /авто/i, time);
      }
    });

    it('cycles auto, light, dark and persists an explicit choice over the clock', async () => {
      const page = await context.newPage();
      await page.clock.install({ time: '2026-11-13T23:00:00' });
      await visit(page);
      const button = page.locator('.drslon-header-utility .krv-theme-btn').first();
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
      assert.equal(await page.evaluate(() => localStorage.getItem('krv_theme')), null);

      await button.click();
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'light');
      assert.match(await button.getAttribute('title'), /светлая/i);
      assert.equal(await page.evaluate(() => localStorage.getItem('krv_theme')), 'light');

      await page.reload({ waitUntil: 'domcontentloaded' });
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'light');

      await button.click();
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
      assert.match(await button.getAttribute('title'), /тёмная/i);

      await button.click();
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
      assert.match(await button.getAttribute('title'), /авто/i);
      assert.equal(await page.evaluate(() => localStorage.getItem('krv_theme')), 'auto');
    });

    it('switches automatically at the day/night boundary without a reload', async () => {
      const page = await context.newPage();
      await page.clock.install({ time: '2026-11-13T19:58:00' });
      await visit(page);
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'light');
      await page.clock.fastForward('05:00');
      await page.waitForFunction(() => document.documentElement.getAttribute('data-theme') === 'dark');
      assert.match(await page.locator('.drslon-header-utility .krv-theme-btn').first().getAttribute('title'), /авто/i);
    });

    it('respects a legacy stored light or dark choice as a manual mode', async () => {
      const page = await context.newPage();
      await page.clock.install({ time: '2026-11-13T12:00:00' });
      await page.addInitScript(() => {
        document.cookie = 'krv_theme=dark; path=/; max-age=31536000; domain=.krivoshein.site';
      });
      await visit(page);
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
      assert.match(await page.locator('.drslon-header-utility .krv-theme-btn').first().getAttribute('title'), /тёмная/i);
      await page.locator('.drslon-header-utility .krv-theme-btn').first().click();
      assert.equal(await page.locator('html').getAttribute('data-theme'), 'light');
      assert.equal(await page.evaluate(() => localStorage.getItem('krv_theme')), 'auto');
    });
  });
}
