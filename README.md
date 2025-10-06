# Beepi Vehicle Lookup - WordPress Plugin

> **Norwegian vehicle registration lookup service with WooCommerce integration**

[![Version](https://img.shields.io/badge/version-7.0.1-blue.svg)](./vehicle-lookup.php)
[![WordPress](https://img.shields.io/badge/WordPress-6.x-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.x-purple.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](./LICENSE)
[![Maintenance](https://img.shields.io/badge/Maintained-Yes-green.svg)](https://github.com/zulfi-git/Beepi-WP/graphs/commit-activity)
[![Status](https://img.shields.io/badge/status-Production-success.svg)](./README.md)
[![Last Commit](https://img.shields.io/github/last-commit/zulfi-git/Beepi-WP)](https://github.com/zulfi-git/Beepi-WP/commits/main)
[![Code Size](https://img.shields.io/github/languages/code-size/zulfi-git/Beepi-WP)](https://github.com/zulfi-git/Beepi-WP)
[![Dependabot](https://img.shields.io/badge/Dependabot-enabled-success?logo=dependabot)](https://github.com/zulfi-git/Beepi-WP/blob/main/.github/dependabot.yml)

---

## Quick Links

📋 **[Assessment](./ASSESSMENT.md)** - Current state, strengths, and known issues  
🔧 **[Refactor Plan](./REFACTOR_PLAN.md)** - Detailed improvement roadmap with implementation phases  
🏗️ **[Architecture](./ARCHITECTURE.md)** - System diagrams, data flows, and technical details  
📝 **[Development Notes](./replit.md)** - Recent changes and implementation details

---

## What This Plugin Does

Beepi Vehicle Lookup enables WordPress/WooCommerce sites to provide Norwegian vehicle information lookup and owner detail sales:

### Core Features
- ✅ **Vehicle Lookup** - Search by Norwegian registration number
- ✅ **Owner Details** - Purchase owner information (5-69 NOK via WooCommerce)
- ✅ **AI Summaries** - OpenAI-generated vehicle descriptions
- ✅ **Market Listings** - Current Finn.no marketplace data
- ✅ **SMS Notifications** - Twilio-powered order confirmations
- ✅ **Admin Dashboard** - Analytics, quotas, and service monitoring
- ✅ **Rate Limiting** - IP-based throttling and daily quotas
- ✅ **Caching** - 12-hour WordPress transient cache

### Technology Stack
- **Backend**: WordPress 6.x, PHP 7.4+
- **E-commerce**: WooCommerce 8.x
- **External API**: Cloudflare Worker (lookup.beepi.no)
- **Data Source**: Statens Vegvesen (Norwegian vehicle registry)
- **AI**: OpenAI GPT (via worker)
- **SMS**: Twilio
- **Payment**: Vipps, WooCommerce Payments

---

## Installation

### Requirements
- WordPress 6.0+
- WooCommerce 8.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+

### Setup Steps

1. **Upload plugin files** to `/wp-content/plugins/beepi-vehicle-lookup/`

2. **Activate plugin** in WordPress admin

3. **Configure settings** at `Vehicle Lookup > Settings`:
   - Worker URL: `https://lookup.beepi.no` (default)
   - Timeout: 15 seconds (default)
   - Rate Limit: 100 requests/hour per IP (default)
   - Cache Duration: 12 hours (default)
   - Daily Quota: 5000 lookups/day (default)

4. **Create WooCommerce products** for owner details:
   - Basic owner info (Product ID: 62)
   - Premium owner package (Product ID: 739)

5. **Add shortcodes** to your pages:

```
[vehicle_lookup product_id="62"]  <!-- Main lookup interface -->
[vehicle_search]                   <!-- Simple search form -->
[popular_vehicles]                 <!-- Most searched vehicles -->
```

---

## File Structure

```
beepi-vehicle-lookup/
├── vehicle-lookup.php              # Main plugin file (bootstrapper)
├── includes/                       # PHP classes
│   ├── class-vehicle-lookup.php              # Core orchestration (412 lines)
│   ├── class-vehicle-lookup-admin.php        # Admin interface (1,197 lines) ⚠️
│   ├── class-vehicle-lookup-api.php          # External API communication
│   ├── class-vehicle-lookup-cache.php        # Caching abstraction
│   ├── class-vehicle-lookup-database.php     # Data persistence
│   ├── class-vehicle-lookup-access.php       # Rate limiting & tiers
│   ├── class-vehicle-lookup-woocommerce.php  # WooCommerce integration
│   ├── class-vehicle-lookup-helpers.php      # Utility functions
│   ├── class-sms-handler.php                 # Twilio SMS notifications
│   ├── class-vehicle-lookup-shortcode.php    # Main lookup UI
│   ├── class-vehicle-search-shortcode.php    # Search form
│   ├── class-vehicle-eu-search-shortcode.php # EU search variant
│   ├── class-popular-vehicles-shortcode.php  # Popular vehicles display
│   └── class-order-confirmation-shortcode.php # Post-purchase UI
├── assets/
│   ├── js/
│   │   ├── vehicle-lookup.js       # Frontend controller (1,533 lines) ⚠️
│   │   └── admin.js                # Admin dashboard logic (881 lines)
│   ├── css/
│   │   ├── vehicle-lookup.css      # Frontend styles (1,788 lines) ⚠️
│   │   └── admin.css               # Admin styles (801 lines)
│   └── images/                     # Logos and icons
└── docs/                           # Documentation (you are here)
    ├── ASSESSMENT.md               # Current state overview
    ├── REFACTOR_PLAN.md            # Improvement roadmap
    ├── ARCHITECTURE.md             # Technical documentation
    └── README.md                   # This file
```

⚠️ = Files identified for potential refactoring (see [REFACTOR_PLAN.md](./REFACTOR_PLAN.md))

---

## Usage Examples

### Basic Vehicle Lookup

```php
// In your WordPress page/post:
[vehicle_lookup product_id="62"]
```

### Search Form (Redirects to Results Page)

```php
[vehicle_search results_page="/sok" button_text="Søk"]
```

### Popular Vehicles Widget

```php
[popular_vehicles limit="10" days="30"]
```

### Programmatic Lookup

```php
// Get vehicle data
$lookup = new Vehicle_Lookup();
$result = $lookup->handle_lookup();

// Check cache
$cache = new VehicleLookupCache();
$cached_data = $cache->get('XX12345');

// Query analytics
$db = new Vehicle_Lookup_Database();
$stats = $db->get_stats('2024-10-01', '2024-10-31');
```

---

## API Endpoints (AJAX)

### Frontend Endpoints

**Vehicle Lookup**
```javascript
POST /wp-admin/admin-ajax.php
{
    action: 'vehicle_lookup',
    nonce: '...',
    regNumber: 'XX12345',
    includeSummary: true
}
```

**AI Summary Polling**
```javascript
POST /wp-admin/admin-ajax.php
{
    action: 'vehicle_lookup_ai_poll',
    nonce: '...',
    regNumber: 'XX12345'
}
```

### Admin Endpoints

- `vehicle_lookup_test_api` - Test API connectivity
- `vehicle_lookup_check_upstream` - Check upstream service health
- `clear_worker_cache` - Clear Cloudflare Worker cache
- `clear_local_cache` - Clear WordPress transient cache
- `reset_analytics_data` - Reset analytics database

---

## Configuration Options

Access via `Vehicle Lookup > Settings` in WordPress admin:

| Setting | Default | Description |
|---------|---------|-------------|
| Worker URL | `https://lookup.beepi.no` | Cloudflare Worker endpoint |
| Timeout | 15 seconds | API request timeout |
| Rate Limit | 100/hour | Requests per hour per IP |
| Cache Duration | 12 hours | Local cache TTL |
| Daily Quota | 5000 | Max lookups per day |
| Log Retention | 90 days | Analytics data retention |

---

## Troubleshooting

### Common Issues

**Lookups failing with timeout errors**
- Check Worker URL is accessible
- Increase timeout in settings
- Verify Cloudflare Worker is running

**Rate limit errors**
- Administrators bypass rate limits
- Increase rate limit in settings
- Check IP detection is working correctly

**Cache not working**
- Clear both local and worker cache
- Verify transient functionality
- Check database for cache entries

**SMS not sending**
- Verify Twilio credentials
- Check phone number formatting
- Review SMS Handler logs in error_log

### Debug Mode

Enable WordPress debug logging in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at `/wp-content/debug.log`

---

## Development

### Running Locally

This plugin is developed and tested on WordPress 6.x with PHP 7.4+.

1. Clone repository to `/wp-content/plugins/beepi-vehicle-lookup/`
2. Activate plugin in WordPress admin
3. Configure worker URL (use staging: `https://staging.lookup.beepi.no`)
4. Test with demo data

### Code Style

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use WordPress functions (wp_remote_post, get_transient, etc.)
- Sanitize all inputs, escape all outputs
- Use nonces for AJAX security

### Testing

Currently **no automated tests** exist. See [REFACTOR_PLAN.md](./REFACTOR_PLAN.md) for testing recommendations.

Manual testing files:
- `test-structured-errors.html` - Error handling scenarios
- `ai-summary-test.html` - AI summary generation

---

## Known Issues & Roadmap

### High Priority Fixes Needed
1. ⚠️ **Performance**: Rewrite rules flushed on every request (should be activation-only)
2. ⚠️ **Code Quality**: Admin class too large (1,197 lines - should split into 4 classes)
3. ⚠️ **Bug**: Duplicate rate_limit setting registration

### Planned Improvements
- Split monolithic admin class (5 days)
- Add unit tests (ongoing)
- Modularize JavaScript (3 days)
- Add local logo fallbacks (1 day)
- Extract AJAX handlers (2 days)

See [REFACTOR_PLAN.md](./REFACTOR_PLAN.md) for complete roadmap.

---

## Architecture Decisions

### Why Cloudflare Worker?
- Edge caching for faster responses
- Isolates heavy API calls from WordPress
- Circuit breaker for upstream failures
- Scales independently

### Why WordPress Transients?
- Native WordPress caching
- No additional dependencies
- TTL support built-in
- Works with object cache drop-ins

### Why Separate Shortcode Classes?
- Single Responsibility Principle
- Easier to maintain and test
- Can be enabled/disabled independently
- Clear ownership of features

---

## Performance Metrics

### Current Performance (as of v7.0.1)
- **Cache Hit Rate**: ~70% (logged in database)
- **Average Response Time**: ~200ms (cache hit), ~1.5s (cache miss)
- **Daily Lookups**: 500-2000 (varies)
- **Error Rate**: <2%

### Optimization Opportunities
- Implement Phase 1 quick wins (~20% performance improvement)
- Add Redis for high-traffic sites
- Bundle and minify assets
- Lazy load market listings

---

## Contributing

This is a production plugin for Beepi.no. Internal development only.

### Internal Contributors
- Follow the refactor plan when making changes
- Add tests for new functionality
- Update documentation for API changes
- Run manual test suite before deployment

---

## License & Credits

**Proprietary Software** - © 2024 Beepi.no  
Internal use only.

### Dependencies
- WordPress 6.x
- WooCommerce 8.x
- Cloudflare Workers
- OpenAI API
- Twilio API
- Statens Vegvesen API

---

## Support

**Internal Team**: See Slack #beepi-dev channel  
**Documentation Issues**: Update this README or related docs  
**Bug Reports**: Create GitHub issue with reproduction steps

---

## Version History

### v7.0.1 (Current)
- Unified design system with CSS variables
- Mobile-first UI improvements
- Enhanced error handling with correlation IDs
- AI summary integration
- Market listings from Finn.no

### Previous Versions
See git history for detailed changelog.

---

## Additional Resources

- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [WooCommerce Documentation](https://woocommerce.com/documentation/)
- [Cloudflare Workers Docs](https://developers.cloudflare.com/workers/)
- [Statens Vegvesen API](https://www.vegvesen.no/)

---

**Last Updated**: October 2024  
**Maintainer**: Internal Beepi.no team  
**Status**: ✅ Production (with known improvement opportunities)
