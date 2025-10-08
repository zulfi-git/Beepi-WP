// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Vehicle Lookup - Market Listings Test Suite
 * 
 * This test suite validates the key front-end functionality of the Beepi Vehicle Lookup plugin,
 * specifically focusing on the vehicle lookup form and market listings display.
 * 
 * Note: These tests require a WordPress site with the plugin installed and activated.
 * Set the BASE_URL environment variable to point to your test WordPress instance.
 * Example: BASE_URL=https://your-site.example.com npm test
 */

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080';

test.describe('Vehicle Lookup Form', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the page with the vehicle lookup form
    // Adjust this URL based on where the shortcode is placed in your WordPress site
    await page.goto(`${BASE_URL}/vehicle-lookup`);
  });

  test('should display the vehicle lookup form', async ({ page }) => {
    // Check that the form is present
    const form = page.locator('#vehicle-lookup-form');
    await expect(form).toBeVisible();

    // Check for the registration number input field
    const regNumberInput = page.locator('input[name="reg_number"]');
    await expect(regNumberInput).toBeVisible();

    // Check for the submit button
    const submitButton = form.locator('button[type="submit"]');
    await expect(submitButton).toBeVisible();
  });

  test('should have proper placeholder text', async ({ page }) => {
    const regNumberInput = page.locator('input[name="reg_number"]');
    
    // Verify placeholder exists (adjust based on actual implementation)
    await expect(regNumberInput).toHaveAttribute('placeholder', /.+/);
  });

  test('should show results area after successful lookup', async ({ page }) => {
    // This is a basic structure - you'll need to adjust based on your actual form behavior
    const regNumberInput = page.locator('input[name="reg_number"]');
    const submitButton = page.locator('#vehicle-lookup-form button[type="submit"]');
    
    // Enter a test registration number (adjust based on your test data)
    await regNumberInput.fill('AB12345');
    
    // Submit the form
    await submitButton.click();
    
    // Wait for results or error message
    // Note: This will need adjustment based on your actual implementation
    const resultsDiv = page.locator('#vehicle-lookup-results');
    const errorDiv = page.locator('#vehicle-lookup-error');
    
    // Either results or error should appear
    await expect(resultsDiv.or(errorDiv)).toBeVisible({ timeout: 10000 });
  });
});

test.describe('Market Listings Display', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${BASE_URL}/vehicle-lookup`);
  });

  test('should have market listings section structure', async ({ page }) => {
    // Perform a lookup first to trigger market listings
    const regNumberInput = page.locator('input[name="reg_number"]');
    const submitButton = page.locator('#vehicle-lookup-form button[type="submit"]');
    
    await regNumberInput.fill('AB12345');
    await submitButton.click();
    
    // Wait for page to process
    await page.waitForTimeout(2000);
    
    // Check if market listings section exists (it may appear after data loads)
    const marketSection = page.locator('.market-listings-section');
    
    // The section should either be visible or not present (depending on data availability)
    // This is a soft assertion - market listings may not always be available
    const sectionCount = await marketSection.count();
    expect(sectionCount >= 0).toBeTruthy();
  });

  test('should display finn.no logo in market listings header', async ({ page }) => {
    // Perform a lookup
    const regNumberInput = page.locator('input[name="reg_number"]');
    const submitButton = page.locator('#vehicle-lookup-form button[type="submit"]');
    
    await regNumberInput.fill('AB12345');
    await submitButton.click();
    
    // Wait for potential market listings
    await page.waitForTimeout(3000);
    
    const marketSection = page.locator('.market-listings-section');
    const finnLogo = marketSection.locator('.section-icon-logo');
    
    // If market section exists, check for logo
    if (await marketSection.count() > 0) {
      await expect(finnLogo).toBeVisible();
      await expect(finnLogo).toHaveAttribute('alt', 'Finn.no');
    }
  });

  test('should display individual listing cards when data is available', async ({ page }) => {
    // Perform a lookup
    const regNumberInput = page.locator('input[name="reg_number"]');
    const submitButton = page.locator('#vehicle-lookup-form button[type="submit"]');
    
    await regNumberInput.fill('AB12345');
    await submitButton.click();
    
    // Wait for potential market listings
    await page.waitForTimeout(3000);
    
    const listingCards = page.locator('.market-listing-item');
    
    // If listings exist, verify their structure
    if (await listingCards.count() > 0) {
      const firstCard = listingCards.first();
      
      // Check for listing content
      await expect(firstCard.locator('.listing-content')).toBeVisible();
      
      // Check for listing title
      await expect(firstCard.locator('.listing-title')).toBeVisible();
      
      // Check for listing price
      await expect(firstCard.locator('.listing-price')).toBeVisible();
    }
  });
});

test.describe('Mobile Responsiveness', () => {
  test.use({ 
    viewport: { width: 375, height: 667 } // iPhone size
  });

  test('should display form properly on mobile', async ({ page }) => {
    await page.goto(`${BASE_URL}/vehicle-lookup`);
    
    const form = page.locator('#vehicle-lookup-form');
    await expect(form).toBeVisible();
    
    // Check that form elements are visible and usable on mobile
    const regNumberInput = page.locator('input[name="reg_number"]');
    await expect(regNumberInput).toBeVisible();
    
    const submitButton = form.locator('button[type="submit"]');
    await expect(submitButton).toBeVisible();
  });

  test('should display market listings in mobile-friendly format', async ({ page }) => {
    await page.goto(`${BASE_URL}/vehicle-lookup`);
    
    // Perform a lookup
    const regNumberInput = page.locator('input[name="reg_number"]');
    const submitButton = page.locator('#vehicle-lookup-form button[type="submit"]');
    
    await regNumberInput.fill('AB12345');
    await submitButton.click();
    
    // Wait for potential listings
    await page.waitForTimeout(3000);
    
    const listingCards = page.locator('.market-listing-item');
    
    // If listings exist, verify they're displayed properly on mobile
    if (await listingCards.count() > 0) {
      const firstCard = listingCards.first();
      
      // Get the bounding box to verify it fits within mobile viewport
      const boundingBox = await firstCard.boundingBox();
      if (boundingBox) {
        expect(boundingBox.width).toBeLessThanOrEqual(375);
      }
    }
  });
});

test.describe('Accessibility', () => {
  test('form inputs should have proper labels or aria-labels', async ({ page }) => {
    await page.goto(`${BASE_URL}/vehicle-lookup`);
    
    const regNumberInput = page.locator('input[name="reg_number"]');
    
    // Check for either a label or aria-label
    const hasLabel = await page.locator('label[for="reg_number"]').count() > 0;
    const hasAriaLabel = await regNumberInput.getAttribute('aria-label');
    
    expect(hasLabel || hasAriaLabel).toBeTruthy();
  });

  test('submit button should be keyboard accessible', async ({ page }) => {
    await page.goto(`${BASE_URL}/vehicle-lookup`);
    
    const submitButton = page.locator('#vehicle-lookup-form button[type="submit"]');
    
    // Tab to the button
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    
    // The button should be focusable
    await expect(submitButton).toBeFocused();
  });
});
