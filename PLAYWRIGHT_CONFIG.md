# Playwright Environment Configuration

This file explains how to configure Playwright tests to run against your WordPress instance.

## Local Development Configuration

### Option 1: Environment Variable (Recommended)
Create a `.env` file in the project root (already in .gitignore):

```bash
BASE_URL=http://localhost:8080
```

Then install dotenv:
```bash
npm install --save-dev dotenv
```

Update `playwright.config.js` to load it:
```javascript
require('dotenv').config();
```

### Option 2: Direct Command Line
```bash
BASE_URL=http://localhost:8080 npm test
```

### Option 3: Update playwright.config.js
Uncomment and modify the `baseURL` in `playwright.config.js`:
```javascript
use: {
  baseURL: 'http://localhost:8080',
  // ... other options
},
```

## WordPress Setup Requirements

Your WordPress site must have:

1. **Plugin installed and activated**
   - The Beepi Vehicle Lookup plugin must be active

2. **Test page with shortcode**
   - Create a page (e.g., `/vehicle-lookup`)
   - Add the shortcode: `[vehicle_lookup]`
   - Publish the page

3. **Accessible URL**
   - The site must be reachable from where tests run
   - For CI/CD, it must be publicly accessible or in same network

## CI/CD Configuration (GitHub Actions)

### Step 1: Set up a test WordPress instance
You have several options:

#### Option A: Use a staging/test site
- Set up a separate WordPress installation
- Install and activate the plugin
- Ensure it's publicly accessible

#### Option B: Spin up WordPress in CI
Add to `.github/workflows/playwright.yml`:

```yaml
- name: Start WordPress
  run: |
    docker-compose up -d
    # Wait for WordPress to be ready
    timeout 60 bash -c 'until curl -f http://localhost:8080; do sleep 1; done'

- name: Install plugin
  run: |
    # Copy plugin files
    docker cp . wordpress-container:/var/www/html/wp-content/plugins/vehicle-lookup
    # Activate plugin via WP-CLI
    docker exec wordpress-container wp plugin activate vehicle-lookup
```

### Step 2: Configure GitHub Secrets

1. Go to repository **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Add:
   - **Name:** `PLAYWRIGHT_BASE_URL`
   - **Value:** Your test WordPress URL (e.g., `https://test.beepi.no`)

### Step 3: Update workflow to use secret
The workflow already uses the secret:
```yaml
env:
  BASE_URL: ${{ secrets.PLAYWRIGHT_BASE_URL || 'http://localhost:8080' }}
```

## Test Data Considerations

### Registration Numbers
The tests use example registration numbers like `AB12345`. Consider:

1. **Mock API responses** - If possible, mock the external vehicle lookup API
2. **Use real test data** - Have known vehicle registrations for testing
3. **Skip integration tests** - Use the `setup-verification.spec.js` only

### Test Isolation
Each test should:
- Not depend on data from other tests
- Clean up after itself if it creates data
- Work in any order

## Example: Local WordPress with Docker

1. **Create docker-compose.yml** (already exists or create one):
```yaml
version: '3'
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - ./:/var/www/html/wp-content/plugins/vehicle-lookup
  
  db:
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: somewordpress
```

2. **Start WordPress:**
```bash
docker-compose up -d
```

3. **Set up WordPress:**
   - Visit http://localhost:8080
   - Complete WordPress installation
   - Activate the Beepi Vehicle Lookup plugin
   - Create a page with `[vehicle_lookup]` shortcode

4. **Run tests:**
```bash
BASE_URL=http://localhost:8080 npm test
```

## Debugging Test Failures

### Check WordPress Accessibility
```bash
curl -I $BASE_URL/vehicle-lookup
```

Should return `200 OK`.

### Check Plugin Activation
Log into WordPress admin and verify the plugin is active.

### Check Shortcode Output
Visit the page directly in a browser and verify the form appears.

### Run Tests in Debug Mode
```bash
BASE_URL=http://localhost:8080 npm run test:debug
```

### View Full Browser
```bash
BASE_URL=http://localhost:8080 npm run test:headed
```

## Security Considerations

⚠️ **Important Security Notes:**

1. **Never commit secrets** - Don't put real URLs/credentials in code
2. **Test sites should be isolated** - Don't use production data
3. **Limit test site access** - Use authentication/IP restrictions if needed
4. **Rotate credentials** - Change test site passwords regularly
5. **Monitor test site** - Watch for abuse or unauthorized access

## Advanced Configuration

### Custom Timeouts
In test files:
```javascript
test.setTimeout(60000); // 60 seconds
```

### Custom Viewports
In test files:
```javascript
test.use({ 
  viewport: { width: 1920, height: 1080 } 
});
```

### Multiple Environments
Create separate config files:
- `playwright.config.local.js`
- `playwright.config.staging.js`
- `playwright.config.production.js`

Run with:
```bash
npx playwright test --config=playwright.config.staging.js
```

## Troubleshooting

### Tests pass locally but fail in CI
- Check that BASE_URL is correctly set in GitHub secrets
- Verify test site is accessible from GitHub Actions runners
- Check for environment-specific issues (timezone, locale, etc.)

### Tests are flaky
- Increase timeouts
- Add explicit waits for dynamic content
- Check for race conditions
- Review network request timing

### Browser installation fails in CI
- Ensure sufficient disk space in runner
- Check for network issues
- Try installing only chromium: `npx playwright install chromium`

## Need Help?

- Review [tests/playwright/README.md](tests/playwright/README.md)
- Check [PLAYWRIGHT_QUICKSTART.md](PLAYWRIGHT_QUICKSTART.md)
- See [Playwright Documentation](https://playwright.dev/)
