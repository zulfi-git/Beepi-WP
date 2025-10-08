# Playwright Tests

This directory contains end-to-end UI tests for the Beepi Vehicle Lookup WordPress plugin.

## Test Structure

- `vehicle-lookup.spec.js` - Main test suite covering:
  - Vehicle lookup form functionality
  - Market listings display
  - Mobile responsiveness
  - Accessibility features

## Running Tests

### Prerequisites

1. Install Node.js dependencies:
   ```bash
   npm install
   ```

2. Install Playwright browsers (first time only):
   ```bash
   npx playwright install --with-deps
   ```

3. Set up a test WordPress instance with the plugin installed and activated

### Basic Usage

```bash
# Run all tests
npm test

# Run with visible browser
npm run test:headed

# Interactive UI mode
npm run test:ui

# Debug mode
npm run test:debug
```

### Testing Against Different Environments

Set the `BASE_URL` environment variable to test against a specific WordPress instance:

```bash
# Test against local development
BASE_URL=http://localhost:8080 npm test

# Test against staging
BASE_URL=https://staging.beepi.no npm test

# Test against production (use caution)
BASE_URL=https://beepi.no npm test
```

## Writing New Tests

### Test File Structure

```javascript
const { test, expect } = require('@playwright/test');

test.describe('Feature Name', () => {
  test.beforeEach(async ({ page }) => {
    // Setup before each test
    await page.goto(`${BASE_URL}/vehicle-lookup`);
  });

  test('should do something', async ({ page }) => {
    // Your test code
    const element = page.locator('#some-element');
    await expect(element).toBeVisible();
  });
});
```

### Best Practices

1. **Use descriptive test names** - Clearly state what is being tested
2. **Keep tests independent** - Each test should be able to run on its own
3. **Use data-testid attributes** - Add these to the WordPress plugin for easier selection
4. **Handle async operations** - Use proper waits instead of arbitrary timeouts
5. **Test real user scenarios** - Focus on user workflows, not implementation details

### Selectors

The tests use various CSS selectors to find elements:

- Form: `#vehicle-lookup-form`
- Input: `input[name="reg_number"]`
- Results: `#vehicle-lookup-results`
- Market listings: `.market-listings-section`
- Listing cards: `.market-listing-item`

### Mobile Testing

Tests include mobile-specific scenarios. You can also run tests on specific devices:

```bash
# Test on specific device
npx playwright test --project="Mobile Chrome"
npx playwright test --project="Mobile Safari"
```

## CI/CD Integration

Tests automatically run on:
- Pull requests to `main` or `develop` branches
- Pushes to `main` or `develop` branches

### GitHub Actions Configuration

The workflow is defined in `.github/workflows/playwright.yml` and:
- Runs on Ubuntu latest
- Uses Node.js 20
- Installs Playwright browsers with dependencies
- Uploads test reports as artifacts

### Setting up CI Testing

1. Add a `PLAYWRIGHT_BASE_URL` secret in your GitHub repository settings
2. Point it to a stable test WordPress instance with the plugin installed
3. Ensure the test instance is accessible from GitHub Actions runners

## Debugging Failed Tests

### Locally

1. Run in debug mode:
   ```bash
   npm run test:debug
   ```

2. View trace:
   ```bash
   npx playwright show-report
   ```

### In CI

1. Go to the failed workflow run in GitHub Actions
2. Download the "playwright-report" artifact
3. Extract and open `index.html` in a browser
4. Review screenshots, videos, and traces

## Common Issues

### Tests timing out
- Increase timeout in test file: `test.setTimeout(30000);`
- Check that WordPress site is accessible and responsive
- Verify network conditions

### Elements not found
- Check that the plugin is activated on the test site
- Verify the shortcode is placed on the correct page
- Update selectors if the HTML structure changed

### Flaky tests
- Avoid arbitrary `waitForTimeout()` calls
- Use `waitForSelector()` or other explicit waits
- Ensure test data is consistent

## Resources

- [Playwright Documentation](https://playwright.dev/)
- [Playwright Best Practices](https://playwright.dev/docs/best-practices)
- [Playwright API Reference](https://playwright.dev/docs/api/class-playwright)
