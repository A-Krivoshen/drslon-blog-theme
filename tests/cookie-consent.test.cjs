/**
 * Browser regressions. Requires PHP, Playwright and its Chromium/Firefox builds locally.
 * Run: node --test tests/cookie-consent.test.cjs
 * Live smoke: CONSENT_TEST_URL=https://krivoshein.site node --test tests/cookie-consent.test.cjs
 * Third-party scripts are stubbed: these tests never send analytics visits.
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

before(async () => {
  if (live) {
    base = live.replace(/\/$/, '');
    return;
  }
  // Render the real PHP bootstrap and dialog, without installing WordPress.
  const markup = JSON.parse(execFileSync('php', ['-r', `
    define('ABSPATH', __DIR__);
    function is_admin(){return false;} function is_feed(){return false;}
    function is_embed(){return false;} function wp_is_json_request(){return false;}
    function add_action(...$args){} function home_url($p){return $p;}
    function esc_url($p){return htmlspecialchars($p, ENT_QUOTES);}
    require 'inc/krv-cookie.php';
    ob_start(); drslon_krv_cookie_head(); $head=ob_get_clean();
    ob_start(); drslon_krv_cookie_footer(); $footer=ob_get_clean();
    echo json_encode(['head'=>$head, 'footer'=>$footer]);
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
    res.end(`<!doctype html><html><head><meta charset="utf-8">${markup.head}
      <link rel="stylesheet" href="/cookie.css"></head><body>
      <header class="drslon-header-utility"><button class="krv-theme-btn" type="button">Theme</button></header>
      <nav><a href="/">Home</a> <a href="/blog/">Blog</a></nav>
      <main><h1>Cookie consent fixture</h1></main>
      <footer><a href="#krv-cookie-settings">Настройка Cookies</a></footer>
      ${markup.footer}<script defer src="/cookie.js"></script>
      <script defer src="/theme.js"></script></body></html>`);
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  base = `http://127.0.0.1:${server.address().port}`;
});

after(async () => {
  if (server) await new Promise(resolve => server.close(resolve));
});

for (const [name, type] of Object.entries({ chromium, firefox })) {
  describe(name, { concurrency: false }, () => {
    let browser;
    let context;
    let errors;
    const launchOptions = name === 'chromium'
      ? { channel: 'chromium', ignoreDefaultArgs: ['--disable-back-forward-cache'] }
      : {};

    async function prepare(ctx) {
      // Preserve the consent gates and request counts; do not execute tracking.
      await ctx.route(/^https:\/\/(?:mc\.yandex\.ru|st\.top100\.ru|yandex\.ru)\//, route =>
        route.fulfill({ contentType: 'text/javascript', body: '' }));
      ctx.on('page', page => page.on('pageerror', error => errors.push(error.message)));
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

    async function cookie(value, cookieName = 'krv_consent') {
      await context.addCookies([{
        name: cookieName, value, url: base + '/',
        expires: Math.floor(Date.now() / 1000) + 31536000,
        secure: base.startsWith('https:'), sameSite: 'Lax'
      }]);
    }

    before(async () => { browser = await type.launch({ headless: true, ...launchOptions }); });
    after(async () => { await browser.close(); });
    beforeEach(async () => {
      errors = [];
      context = await browser.newContext();
      await prepare(context);
    });
    afterEach(async () => {
      await context.close();
      assert.deepEqual(errors, [], 'No uncaught JavaScript exceptions');
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
        t.diagnostic('Firefox automation reloaded history entry; additionally exercising persisted pageshow explicitly.');
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
      await page.locator('[data-krv-consent="save"]').click();
      await hidden(page);
      assert.equal((await context.cookies(base)).find(c => c.name === 'krv_consent').value, 'a');
      await page.reload({ waitUntil: 'domcontentloaded' });
      await link.click();
      await page.locator('#krv-cookie-analytics').uncheck();
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
        await route.continue();
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
      for (const marker of ['metrika/tag.js', 'top100/top100.js', 'ads/system/context.js']) {
        assert.equal(await page.locator(`script[src*="${marker}"]`).count(), 1, marker);
      }
      assert.deepEqual(await page.evaluate(() => window.__consentEvents), []);
    });

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
      await visit(page);
      await choose(page, 'n');
      const previous = await page.locator('html').getAttribute('data-theme');
      await page.locator('.drslon-header-utility .krv-theme-btn').first().click();
      const selected = await page.locator('html').getAttribute('data-theme');
      assert.notEqual(selected, previous);
      await page.reload({ waitUntil: 'domcontentloaded' });
      await hidden(page);
      assert.equal(await page.locator('html').getAttribute('data-theme'), selected);
      assert.equal(await page.evaluate(() => localStorage.getItem('krv_consent')), null);
    });
  });
}
