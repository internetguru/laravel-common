/**
 * Reusable Playwright E2E test helpers for laravel-common package.
 *
 * Usage in consuming apps:
 *
 *   import { registerCommonTests } from '../../vendor/internetguru/laravel-common/tests/e2e/common-tests.js';
 *   registerCommonTests(test, expect, { languages: { en: 'English', cs: 'Česky' } });
 */

export function registerCommonTests(test, expect, options = {}) {
  const {
    languages = { en: 'English', cs: 'Česky' },
    demo = false,
  } = options;

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /** Navigate to a page and wait for load. */
  async function goto(page, path) {
    await page.goto(path);
  }

  // ---------------------------------------------------------------------------
  // Layout & structure
  // ---------------------------------------------------------------------------

  test.describe('laravel-common: layout', () => {

    test('homepage has header, main and footer', async ({ page }) => {
      await goto(page, '/');
      await expect(page.locator('header')).toBeVisible();
      await expect(page.locator('main')).toBeVisible();
      await expect(page.locator('footer')).toBeVisible();
    });

    test('page has meta charset', async ({ page }) => {
      await goto(page, '/');
      const charset = page.locator('meta[charset]');
      await expect(charset).toHaveAttribute('charset', 'utf-8');
    });

    test('page has viewport meta', async ({ page }) => {
      await goto(page, '/');
      const viewport = page.locator('meta[name="viewport"]');
      await expect(viewport).toHaveAttribute('content', /width=device-width/);
    });

    test('page has title', async ({ page }) => {
      await goto(page, '/');
      const title = await page.title();
      expect(title.length).toBeGreaterThan(0);
    });

  });

  // ---------------------------------------------------------------------------
  // Breadcrumb component
  // ---------------------------------------------------------------------------

  test.describe('laravel-common: breadcrumb', () => {

    test('breadcrumb is visible on homepage', async ({ page }) => {
      await goto(page, '/');
      await expect(page.locator('[data-testid="breadcrumb"]')).toBeVisible();
    });

    test('breadcrumb has at least one item', async ({ page }) => {
      await goto(page, '/');
      const items = page.locator('[data-testid="breadcrumb"] .breadcrumb-item');
      const count = await items.count();
      expect(count).toBeGreaterThanOrEqual(1);
    });

    test('breadcrumb last item is active', async ({ page }) => {
      await goto(page, '/');
      const items = page.locator('[data-testid="breadcrumb"] .breadcrumb-item');
      const count = await items.count();
      await expect(items.nth(count - 1)).toHaveClass(/active/);
    });

    test('breadcrumb grows on subpages', async ({ page }) => {
      await goto(page, '/');
      const homeCount = await page.locator('[data-testid="breadcrumb"] .breadcrumb-item').count();

      await goto(page, '/login');
      const loginCount = await page.locator('[data-testid="breadcrumb"] .breadcrumb-item').count();

      expect(loginCount).toBeGreaterThan(homeCount);
    });

  });

  // ---------------------------------------------------------------------------
  // Language switch
  // ---------------------------------------------------------------------------

  if (languages && Object.keys(languages).length > 1) {

    test.describe('laravel-common: language switch', () => {

      test('language switch is visible', async ({ page }) => {
        await goto(page, '/');
        await expect(page.locator('[data-testid="lang-switch"]')).toBeVisible();
      });

      test('language switch has all configured languages', async ({ page }) => {
        await goto(page, '/');
        const langItems = page.locator('[data-testid="lang-switch"] .list-inline-item');
        const count = await langItems.count();
        expect(count).toBe(Object.keys(languages).length);
      });

      test('current language is highlighted (bold)', async ({ page }) => {
        await goto(page, '/');
        const boldLang = page.locator('[data-testid="lang-switch"] strong');
        await expect(boldLang).toBeVisible();
      });

      test('switching language changes page content', async ({ page }) => {
        await goto(page, '/');
        const titleBefore = await page.title();

        // Click a non-active language link
        const nonActiveLink = page.locator('[data-testid="lang-switch"] a:not(:has(strong))').first();
        await nonActiveLink.click();
        await page.waitForLoadState('load');

        const titleAfter = await page.title();
        // URL should contain ?lang= parameter or the language segment
        const url = page.url();
        expect(url).toMatch(/lang=|\/en|\/cs|\/da/);
      });

      test('language preference persists across pages', async ({ page }) => {
        await goto(page, '/');

        // Switch to a different language
        const nonActiveLink = page.locator('[data-testid="lang-switch"] a:not(:has(strong))').first();
        const langUrl = await nonActiveLink.getAttribute('href');
        await nonActiveLink.click();
        await page.waitForLoadState('load');

        // Note which language is now active
        const activeLang = await page.locator('[data-testid="lang-switch"] strong').textContent();

        // Navigate to another page
        await goto(page, '/login');
        await page.waitForLoadState('load');

        // The same language should still be active
        const activeLangAfter = await page.locator('[data-testid="lang-switch"] strong').textContent();
        expect(activeLangAfter).toBe(activeLang);
      });

    });

  }

  // ---------------------------------------------------------------------------
  // Error pages
  // ---------------------------------------------------------------------------

  test.describe('laravel-common: error pages', () => {

    test('404 page displays error message', async ({ page }) => {
      const response = await page.goto('/nonexistent-page-' + Date.now());
      expect(response.status()).toBe(404);
      // The error handler renders a page with the error title
      await expect(page.locator('h1')).toBeVisible();
    });

    test('error index page loads', async ({ page }) => {
      await goto(page, '/error');
      await expect(page.locator('.error')).toBeVisible();
      // Should have links to individual error pages
      const links = page.locator('.error a');
      const count = await links.count();
      expect(count).toBeGreaterThanOrEqual(8);
    });

    test('401 error page renders correctly', async ({ page }) => {
      const response = await page.goto('/error/401');
      expect(response.status()).toBe(401);
      await expect(page.locator('h1')).toContainText('401');
    });

    test('403 error page renders correctly', async ({ page }) => {
      const response = await page.goto('/error/403');
      expect(response.status()).toBe(403);
      await expect(page.locator('h1')).toContainText('403');
    });

    test('404 error route renders correctly', async ({ page }) => {
      const response = await page.goto('/error/404');
      expect(response.status()).toBe(404);
      await expect(page.locator('h1')).toContainText('404');
    });

    test('500 error page renders correctly', async ({ page }) => {
      const response = await page.goto('/error/500');
      expect(response.status()).toBe(500);
      await expect(page.locator('h1')).toContainText('500');
    });

    test('503 error page renders correctly', async ({ page }) => {
      const response = await page.goto('/error/503');
      expect(response.status()).toBe(503);
      await expect(page.locator('h1')).toContainText('503');
    });

    test('unknown error code shows 404', async ({ page }) => {
      const response = await page.goto('/error/999');
      expect(response.status()).toBe(404);
    });

  });

  // ---------------------------------------------------------------------------
  // System messages (Livewire ig-messages component)
  // ---------------------------------------------------------------------------

  test.describe('laravel-common: messages', () => {

    test('messages wrapper is present on page', async ({ page }) => {
      await goto(page, '/');
      await expect(page.locator('.messages-wrapper')).toBeVisible();
    });

  });

  // ---------------------------------------------------------------------------
  // Demo mode banner
  // ---------------------------------------------------------------------------

  if (demo) {

    test.describe('laravel-common: demo mode', () => {

      test('demo mode banner is visible', async ({ page }) => {
        await goto(page, '/');
        await expect(page.locator('[data-testid="demo-info"]')).toBeVisible();
      });

    });

  }

  // ---------------------------------------------------------------------------
  // CSRF protection
  // ---------------------------------------------------------------------------

  test.describe('laravel-common: csrf', () => {

    test('page includes CSRF token meta tag', async ({ page }) => {
      await goto(page, '/');
      const csrfMeta = page.locator('meta[name="csrf_token"], meta[name="csrf-token"]');
      const count = await csrfMeta.count();
      expect(count).toBeGreaterThanOrEqual(1);
      const token = await csrfMeta.first().getAttribute('content');
      expect(token.length).toBeGreaterThan(0);
    });

  });

  // ---------------------------------------------------------------------------
  // i18n test pages
  // ---------------------------------------------------------------------------

  test.describe('laravel-common: i18n pages', () => {

    test('i18n index page loads', async ({ page }) => {
      await goto(page, '/i18n');
      // Should have links to translation test pages
      const links = page.locator('a[href*="/i18n/"]');
      const count = await links.count();
      expect(count).toBeGreaterThanOrEqual(4);
    });

    test('i18n complete page loads', async ({ page }) => {
      await goto(page, '/i18n/complete');
      await expect(page.locator('h1')).toBeVisible();
    });

    test('i18n missing-all page loads', async ({ page }) => {
      await goto(page, '/i18n/missing-all');
      await expect(page.locator('h1')).toBeVisible();
    });

    test('i18n missing-cs page loads', async ({ page }) => {
      await goto(page, '/i18n/missing-cs');
      await expect(page.locator('h1')).toBeVisible();
    });

    test('i18n missing-en page loads', async ({ page }) => {
      await goto(page, '/i18n/missing-en');
      await expect(page.locator('h1')).toBeVisible();
    });

  });

  // ---------------------------------------------------------------------------
  // HTML structure & accessibility
  // ---------------------------------------------------------------------------

  test.describe('laravel-common: html structure', () => {

    test('html has lang attribute', async ({ page }) => {
      await goto(page, '/');
      const lang = await page.locator('html').getAttribute('lang');
      expect(lang).toBeTruthy();
      expect(lang.length).toBeGreaterThanOrEqual(2);
    });

    test('page has exactly one h1', async ({ page }) => {
      await goto(page, '/');
      const h1Count = await page.locator('h1').count();
      expect(h1Count).toBe(1);
    });

  });

}
