# Beepi Vehicle Lookup - WordPress Plugin

> **Norwegian vehicle registration lookup service with WooCommerce integration**

[![Version](https://img.shields.io/badge/version-7.0.9-blue.svg)](./vehicle-lookup.php)
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

📚 **[Documentation Index](./docs/README.md)** - Complete documentation navigation and index  
📋 **[Assessment](./docs/architecture/ASSESSMENT.md)** - Current state, strengths, and known issues  
🔧 **[Refactor Plan](./docs/refactoring/REFACTOR_PLAN.md)** - Detailed improvement roadmap with implementation phases  
🏗️ **[Architecture](./docs/architecture/ARCHITECTURE.md)** - System diagrams, data flows, and technical details  
📝 **[Development Notes](./docs/replit.md)** - Recent changes and implementation details  
📜 **[Changelog](./CHANGELOG.md)** - Version history and all notable changes

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
├── docs/                          # All documentation (organized)
│   ├── README.md                  # Documentation index
│   ├── architecture/              # Architecture & analysis
│   ├── refactoring/               # Refactor plans & completions
│   ├── fixes/                     # Bug fixes & improvements
│   ├── investigations/            # Technical investigations
│   └── tests/                     # Test files & demonstrations
└── README.md                      # This file
```

⚠️ = Files identified for potential refactoring (see [docs/refactoring/REFACTOR_PLAN.md](./docs/refactoring/REFACTOR_PLAN.md))

---

## Development

### Code Style
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Sanitize inputs, escape outputs, use nonces for AJAX

### Testing
No automated tests currently. Manual test files in [docs/tests/](./docs/tests/).

---

## Known Issues & Roadmap

See [docs/refactoring/REFACTOR_PLAN.md](./docs/refactoring/REFACTOR_PLAN.md) for improvement plans.

### Completed
- ✅ Admin class split (Phase 2)
- ✅ CSS modularization
- ✅ Performance fixes (Phase 1)

### Planned
- Phase 3: Live metrics dashboard
- Phase 4: Unit tests

---

## Architecture Notes

### Cloudflare Worker
- Edge caching (Cloudflare KV)
- Isolates heavy API calls
- Independent scaling

### Separate Shortcode Classes
- Single Responsibility Principle
- Independent feature management

---

## Performance

- **Caching**: Cloudflare KV edge caching
- **Response Time**: ~1.5s average
- **Daily Lookups**: 500-2000
- **Error Rate**: <2%

---

## License

**⚠️ PROPRIETARY SOFTWARE**

© 2025 Beepi.no - All Rights Reserved

Not open source. See [LICENSE](./LICENSE) for terms.

---

**Version History:** [CHANGELOG.md](./CHANGELOG.md)

