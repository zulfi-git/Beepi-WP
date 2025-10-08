// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Basic Smoke Test
 * 
 * This test verifies that Playwright is set up correctly.
 * It doesn't require a WordPress instance and will pass in CI/CD.
 */

test.describe('Playwright Setup Verification', () => {
  test('Playwright is configured correctly', async ({ page }) => {
    // Navigate to a reliable external site
    await page.goto('https://playwright.dev/');
    
    // Verify basic page functionality
    await expect(page).toHaveTitle(/Playwright/);
    
    // Verify we can interact with the page
    const getStartedLink = page.getByRole('link', { name: 'Get started' });
    if (await getStartedLink.isVisible()) {
      await expect(getStartedLink).toBeVisible();
    }
  });

  test('Browser context is working', async ({ page, context }) => {
    // Verify we can create pages and navigate
    await page.goto('https://example.com');
    await expect(page).toHaveURL('https://example.com/');
    
    // Verify basic DOM interaction
    const heading = page.locator('h1');
    await expect(heading).toBeVisible();
  });

  test('Network requests are working', async ({ page }) => {
    // Navigate to a page and verify network works
    const response = await page.goto('https://httpbin.org/status/200');
    expect(response?.status()).toBe(200);
  });
});

/**
 * These tests ensure that:
 * 1. Playwright is installed correctly
 * 2. Browsers can launch
 * 3. Navigation works
 * 4. DOM queries work
 * 5. Network requests work
 * 
 * Once these pass, you know the setup is ready for actual
 * WordPress plugin testing against your site.
 */
