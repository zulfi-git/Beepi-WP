# Beepi Vehicle Lookup - WordPress Plugin

> **Norwegian vehicle registration lookup service with WooCommerce integration**

[![Version](https://img.shields.io/badge/version-7.0.3-blue.svg)](./vehicle-lookup.php)
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
- ✅ **Plate Normalization** - Automatic uppercase and space removal for consistent lookups
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
├── ASSESSMENT.md               # Current state overview
├── REFACTOR_PLAN.md            # Improvement roadmap
├── ARCHITECTURE.md             # Technical documentation
├── replit.md                   # Development notes
└── README.md                   # This file
```

⚠️ = Files identified for potential refactoring (see [REFACTOR_PLAN.md](./REFACTOR_PLAN.md))

---

## Development

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

### Current Performance (as of v7.0.3)
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

**Proprietary Software** - © 2025 Beepi.no  
Internal development only.

### Internal Contributors
- Follow the refactor plan when making changes
- Update documentation for API changes

---

## Version History

### v7.0.3 (Current)
- Unified design system with CSS variables
- Mobile-first UI improvements
- Enhanced error handling with correlation IDs
- AI summary integration
- Market listings from Finn.no

### Previous Versions
See git history for detailed changelog.

---

**Last Updated**: October 2024  
**Maintainer**: Internal Beepi.no team  
**Status**: ✅ Production (with known improvement opportunities)
