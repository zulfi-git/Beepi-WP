# Playwright Testing Quick Start

## First Time Setup

1. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

2. **Install Playwright browsers:**
   ```bash
   npx playwright install --with-deps
   ```
   
   Note: This will download Chromium, Firefox, and WebKit browsers (~500MB total).
   For CI only, browsers are installed automatically by GitHub Actions.

## Running Tests

### Quick Commands

```bash
# Run all tests (headless mode - no browser window)
npm test

# Run with visible browser (useful for debugging)
npm run test:headed

# Open interactive UI (recommended for development)
npm run test:ui

# Run in debug mode with Playwright Inspector
npm run test:debug

# Run specific test file
npx playwright test vehicle-lookup.spec.js

# Run tests matching a pattern
npx playwright test -g "market listings"

# Run on specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit
```

### Testing Against Different Environments

By default, tests try to connect to `http://localhost:8080`. To test against a different WordPress instance:

```bash
# Test against local development
BASE_URL=http://localhost:8080 npm test

# Test against staging
BASE_URL=https://staging.beepi.no npm test

# Test against specific page
BASE_URL=https://your-site.com npm test
```

**Important:** Your WordPress site must have:
- The Beepi Vehicle Lookup plugin installed and activated
- A page with the `[vehicle_lookup]` shortcode (usually at `/vehicle-lookup`)

## Understanding Test Results

### Successful Test Run
```
✓  setup-verification.spec.js:12:3 › Playwright is configured correctly (2s)
✓  vehicle-lookup.spec.js:24:3 › should display the vehicle lookup form (3s)

  2 passed (5s)
```

### Failed Test Run
When tests fail, Playwright automatically:
- Takes a screenshot
- Records a video (if configured)
- Creates a trace file

View the HTML report:
```bash
npx playwright show-report
```

## Test Files

- `setup-verification.spec.js` - Smoke tests to verify Playwright setup (works without WordPress)
- `vehicle-lookup.spec.js` - Main test suite for the vehicle lookup plugin

## Common Issues

### "page.goto: net::ERR_CONNECTION_REFUSED"
**Cause:** WordPress site is not running or BASE_URL is incorrect.
**Fix:** Ensure your WordPress site is accessible at the BASE_URL.

### Tests timeout
**Cause:** WordPress site is slow or elements don't exist.
**Fix:** 
- Check that the plugin is activated
- Verify the shortcode is on the page
- Increase timeout in test file if needed

### Browser installation fails
**Cause:** Network issues or insufficient disk space.
**Fix:**
- Check internet connection
- Ensure you have ~500MB free disk space
- Try: `npx playwright install --with-deps chromium` (install one browser only)

## CI/CD Integration

Tests automatically run on GitHub Actions when:
- Opening or updating a pull request
- Pushing to `main` or `develop` branches

To configure CI testing:
1. Go to your repository settings
2. Add `PLAYWRIGHT_BASE_URL` secret with your test WordPress URL
3. Ensure the test site is accessible from GitHub Actions runners

## Tips for Writing Tests

1. **Use data-testid attributes** in your WordPress plugin for reliable selectors
2. **Avoid hardcoded waits** - use `waitForSelector()` instead of `waitForTimeout()`
3. **Keep tests independent** - each test should work on its own
4. **Test user workflows** - focus on what users actually do
5. **Use descriptive names** - clearly state what's being tested

## Resources

- [Playwright Documentation](https://playwright.dev/)
- [Test Runner API](https://playwright.dev/docs/api/class-test)
- [Writing Tests Guide](https://playwright.dev/docs/writing-tests)
- [Best Practices](https://playwright.dev/docs/best-practices)

## Need Help?

- Check the [tests/playwright/README.md](tests/playwright/README.md) for detailed documentation
- Review test examples in the test files
- Consult the main [README.md](README.md) Testing section
