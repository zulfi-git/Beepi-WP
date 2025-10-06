# Architecture Documentation - Beepi Vehicle Lookup

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         WordPress Frontend                           │
│  ┌────────────────┐  ┌────────────────┐  ┌─────────────────────┐   │
│  │ vehicle-lookup │  │ vehicle-search │  │ popular-vehicles    │   │
│  │   shortcode    │  │   shortcode    │  │    shortcode        │   │
│  └────────┬───────┘  └────────┬───────┘  └──────────┬──────────┘   │
│           │                   │                      │               │
│           └───────────────────┼──────────────────────┘               │
│                               │                                      │
│                               ▼                                      │
│                  ┌────────────────────────┐                          │
│                  │  vehicle-lookup.js     │                          │
│                  │  (Frontend Controller) │                          │
│                  └────────────┬───────────┘                          │
└───────────────────────────────┼──────────────────────────────────────┘
                                │ AJAX
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      WordPress Backend (PHP)                         │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    Vehicle_Lookup (Core)                     │   │
│  │  • Coordinates all subsystems                                │   │
│  │  • Handles AJAX endpoints                                    │   │
│  │  • Manages WordPress hooks                                   │   │
│  └───┬──────────────────────────────────────────────┬──────────┘   │
│      │                                               │               │
│      ▼                                               ▼               │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────────┐   │
│  │   API Layer  │  │ Cache Layer  │  │    Access Control      │   │
│  ├──────────────┤  ├──────────────┤  ├────────────────────────┤   │
│  │ VehicleAPI   │  │ Cache        │  │ VehicleLookupAccess    │   │
│  │ • lookup()   │  │ • get()      │  │ • check_rate_limit()   │   │
│  │ • poll_ai()  │  │ • set()      │  │ • determine_tier()     │   │
│  │ • validate() │  │ • clear()    │  │ • get_quota_status()   │   │
│  └──────┬───────┘  └──────────────┘  └────────────────────────┘   │
│         │                                                            │
│         ▼                                                            │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                    External Services                          │  │
│  │  • Cloudflare Worker (https://lookup.beepi.no)               │  │
│  │  • Statens Vegvesen API (via worker)                         │  │
│  │  • OpenAI API (AI summaries, via worker)                     │  │
│  │  • Finn.no API (market listings, via worker)                 │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                    Data Persistence                           │  │
│  │  ┌────────────────────────┐  ┌──────────────────────────┐   │  │
│  │  │ Vehicle_Lookup_Database│  │  WordPress Transients    │   │  │
│  │  │ • log_lookup()         │  │  • Cache storage         │   │  │
│  │  │ • get_stats()          │  │  • TTL: 12 hours         │   │  │
│  │  │ • get_daily_quota()    │  │                          │   │  │
│  │  └────────────────────────┘  └──────────────────────────┘   │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                  WooCommerce Integration                      │  │
│  │  ┌───────────────────────┐  ┌──────────────────────────┐    │  │
│  │  │ VehicleLookupWoo      │  │     SMS_Handler          │    │  │
│  │  │ • Order metadata      │  │  • Twilio integration    │    │  │
│  │  │ • Phone formatting    │  │  • Owner notifications   │    │  │
│  │  └───────────────────────┘  └──────────────────────────┘    │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                 Admin Interface (⚠️ Needs Split)             │  │
│  │  ┌──────────────────────────────────────────────────────┐   │  │
│  │  │          Vehicle_Lookup_Admin (1,197 lines)          │   │  │
│  │  │  • Dashboard rendering                                │   │  │
│  │  │  • Settings management                                │   │  │
│  │  │  • Analytics calculations                             │   │  │
│  │  │  • AJAX handlers (5)                                  │   │  │
│  │  │  • Stats queries (7 methods)                          │   │  │
│  │  └──────────────────────────────────────────────────────┘   │  │
│  └──────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────────-──┘
```

---

## Request Flow Diagrams

### 1. Vehicle Lookup Request (Cache Hit)

```
User → Search Form → vehicle-lookup.js → AJAX Request
                                              ↓
                                     Vehicle_Lookup::handle_lookup()
                                              ↓
                                     Check VehicleLookupCache
                                              ↓
                                     Cache HIT → Return cached data
                                              ↓
                                     Log to Database (cached=1)
                                              ↓
                                     Trigger async AI generation
                                              ↓
                                     ← JSON Response ←
                                              ↓
                                     vehicle-lookup.js renders results
```

### 2. Vehicle Lookup Request (Cache Miss)

```
User → Search Form → vehicle-lookup.js → AJAX Request
                                              ↓
                                     Vehicle_Lookup::handle_lookup()
                                              ↓
                                     Check VehicleLookupCache
                                              ↓
                                     Cache MISS
                                              ↓
                                     Check VehicleLookupAccess
                                     • Rate limit
                                     • Daily quota
                                     • User tier
                                              ↓
                                     VehicleLookupAPI::lookup()
                                              ↓
                                     POST https://lookup.beepi.no/lookup
                                              ↓
                                     Cloudflare Worker
                                              ↓
                                     Statens Vegvesen API
                                              ↓
                                     ← Vehicle Data ←
                                              ↓
                                     Store in Cache (12h TTL)
                                              ↓
                                     Log to Database (cached=0)
                                              ↓
                                     ← JSON Response ←
                                              ↓
                                     vehicle-lookup.js renders results
```

### 3. Purchase Flow (WooCommerce Integration)

```
User Views Owner Section → Click "Se eier" button
                                ↓
                       Add product to cart (WooCommerce)
                                ↓
                       Cookie: vehicle_reg_number = XX12345
                                ↓
                       Checkout → Payment (Vipps/Card)
                                ↓
                       woocommerce_payment_complete hook
                                ↓
              ┌────────────────┴────────────────┐
              ▼                                 ▼
   SMS_Handler::send_owner_notification   Order metadata saved
              ↓                                 • reg_number
   Get owner details from API                   • formatted_phone
              ↓
   Format customer phone
              ↓
   Send SMS via Twilio API
              ↓
   Log SMS status in order meta
```

### 4. Admin Dashboard Load

```
Admin → WordPress Admin → Vehicle Lookup Menu
                                ↓
                   Vehicle_Lookup_Admin::admin_page()
                                ↓
              ┌─────────────────┴─────────────────┐
              ▼                                   ▼
    get_lookup_stats()                  get_cache_stats()
              ↓                                   ▼
    Query vehicle_lookup_logs          Check WordPress transients
              ↓                                   │
    Calculate metrics:                           │
    • Today's lookups                            │
    • Success rate                               │
    • Quota usage                                │
    • Trend percentage                           │
              └─────────────────┬─────────────────┘
                                ↓
                        Render dashboard HTML
                        • Metrics cards
                        • Service status
                        • Recent activity
```

---

## Data Flow

### Database Schema: `wp_vehicle_lookup_logs`

```sql
CREATE TABLE wp_vehicle_lookup_logs (
    id                BIGINT(20) AUTO_INCREMENT PRIMARY KEY,
    reg_number        VARCHAR(20) NOT NULL,
    ip_address        VARCHAR(45) NOT NULL,
    user_agent        TEXT,
    lookup_time       DATETIME DEFAULT CURRENT_TIMESTAMP,
    success           TINYINT(1) NOT NULL,
    error_message     TEXT,
    failure_type      VARCHAR(20),         -- invalid_plate, rate_limit, api_error, etc.
    tier              VARCHAR(10),          -- free, basic, premium
    response_time_ms  INT,
    cached            TINYINT(1) DEFAULT 0,
    response_data     LONGTEXT,            -- Full JSON response
    error_code        VARCHAR(50),         -- Structured error codes
    correlation_id    VARCHAR(100),        -- req-{timestamp}-{random}
    
    KEY idx_reg_number (reg_number),
    KEY idx_lookup_time (lookup_time),
    KEY idx_success (success),
    KEY idx_ip_address (ip_address),
    KEY idx_tier (tier),
    KEY idx_error_code (error_code),
    KEY idx_correlation_id (correlation_id)
);
```

### Cache Structure (WordPress Transients)

```php
Transient Key: vehicle_cache_{md5(REGNUMBER)}
Value: [
    'data' => [
        'regNumber' => 'XX12345',
        'make' => 'Tesla',
        'model' => 'Model 3',
        'year' => 2023,
        // ... full vehicle data
    ],
    'cache_time' => '2024-10-06T10:30:00+00:00'
]
TTL: 43200 seconds (12 hours)
```

---

## Class Responsibilities Matrix

| Class | Primary Responsibility | Dependencies | Lines | Status |
|-------|------------------------|--------------|-------|--------|
| **Vehicle_Lookup** | Core orchestration, AJAX routing | All classes | 412 | ⚠️ Mixed concerns |
| **Vehicle_Lookup_Admin** | Admin UI (dashboard, settings, analytics) | Database | 1,197 | ⚠️ Too large |
| **VehicleLookupAPI** | External API communication | Helpers | 422 | ✅ Good |
| **VehicleLookupCache** | Caching abstraction | None | 108 | ✅ Good |
| **Vehicle_Lookup_Database** | Data persistence, analytics | None | 336 | ✅ Good |
| **VehicleLookupAccess** | Rate limiting, tier management | Database | 109 | ✅ Good |
| **VehicleLookupWooCommerce** | Order integration | None | 156 | ✅ Good |
| **SMS_Handler** | SMS notifications | API, Database | 310 | ✅ Good |
| **Vehicle_Lookup_Helpers** | Utility functions | None | 158 | ✅ Good |
| **Vehicle_Lookup_Shortcode** | Main lookup UI | Helpers | 229 | ✅ Good |
| **Vehicle_Search_Shortcode** | Search form UI | None | 72 | ✅ Good |
| **Vehicle_EU_Search_Shortcode** | EU search variant | None | 73 | ✅ Good |
| **Popular_Vehicles_Shortcode** | Popular vehicles display | Database | 138 | ✅ Good |
| **Order_Confirmation_Shortcode** | Post-purchase UI | API, WooCommerce | 422 | ✅ Good |

---

## Plugin Lifecycle Hooks

### Activation (`vehicle_lookup_activate()`)
1. Create database table via `Vehicle_Lookup_Database::create_table()`
2. Flush rewrite rules (register `/sok/{reg}` routes)

### Deactivation (`vehicle_lookup_deactivate()`)
1. Flush rewrite rules (clean up routes)
2. Leave data intact (do not drop tables or clear cache)

### Runtime Initialization (`Vehicle_Lookup::init()`)
1. Initialize subsystems (API, Cache, Access, WooCommerce)
2. Register WordPress hooks:
   - `wp_enqueue_scripts` - Load frontend assets
   - `init` - Add rewrite rules (⚠️ performance issue)
   - `wp_ajax_*` - Register AJAX endpoints
   - `wp_scheduled_delete` - Database cleanup
3. Initialize shortcodes

### Admin Initialization (`Vehicle_Lookup_Admin::init()`)
1. Register admin menu pages
2. Register settings
3. Enqueue admin assets
4. Register AJAX handlers for admin operations

---

## External Dependencies

### Required WordPress Plugins
- **WooCommerce** (e-commerce functionality)
- **Vipps for WooCommerce** (payment processing)

### External APIs
- **Cloudflare Worker** (https://lookup.beepi.no)
  - `/lookup` - Vehicle data retrieval
  - `/ai-summary/{reg}` - AI summary polling
  - `/market-listings/{reg}` - Market data
  - `/cache/clear` - Cache invalidation
- **Twilio** (SMS notifications)
- **Statens Vegvesen** (via worker)
- **OpenAI** (via worker)
- **Finn.no** (via worker)

### Third-Party Assets
- **carlogos.org** - Manufacturer logos (⚠️ external dependency risk)

---

## Security Measures

### Input Validation
- ✅ Registration number format validation (multiple patterns)
- ✅ Phone number formatting and validation
- ✅ IP address validation (FILTER_VALIDATE_IP)
- ✅ User agent sanitization (500 char limit)

### WordPress Security
- ✅ AJAX nonce verification
- ✅ Capability checks (`manage_options` for admin)
- ✅ `sanitize_text_field()` on all inputs
- ✅ `wp_remote_post()` with timeout limits
- ✅ SQL prepared statements

### Rate Limiting
- ✅ IP-based hourly rate limits (100/hour default)
- ✅ Daily quota tracking (5000/day default)
- ✅ Admin bypass for administrators

---

## Performance Considerations

### Caching Strategy
- **Local Cache**: WordPress transients (12-hour TTL)
- **Worker Cache**: Cloudflare Worker cache (configurable)
- **Cache Hit Rate**: Logged in database for monitoring

### Known Performance Issues
1. ⚠️ Rewrite rules added on every `init` hook (should be activation-only)
2. ⚠️ Admin dashboard makes multiple database queries (could be optimized)
3. ⚠️ External logo loading blocks rendering (should have fallback)

### Optimization Opportunities
1. Implement lazy loading for market listings
2. Add database indexes for common queries (partially done)
3. Consider Redis for high-traffic installations
4. Bundle and minify frontend assets

---

## Error Handling Strategy

### Error Types Tracked
- `invalid_plate` - Invalid registration format
- `rate_limit` - Rate limit exceeded
- `quota_exceeded` - Daily quota exceeded
- `api_error` - External API failure
- `http_error` - HTTP request failure
- `timeout` - Request timeout
- `unknown` - Unclassified errors

### Error Logging
- All lookups logged to database with correlation IDs
- Failed lookups include `error_code` and `failure_type`
- PHP error_log for debugging
- Frontend console logging for development

### Error Recovery
- Graceful degradation for cache failures
- Retry logic for transient API errors (circuit breaker aware)
- User-friendly error messages in UI
- Admin notifications for critical failures

---

## Monitoring & Analytics

### Metrics Tracked
- Total lookups (daily, hourly, all-time)
- Success/failure rates
- Cache hit rate
- Average response time
- Quota usage
- Popular registration numbers
- Error distribution

### Admin Dashboard Widgets
1. **Today's Lookups** - Count with trend indicator
2. **Success Rate** - Percentage with status badge
3. **API Costs** - Quota usage with progress bar
4. **Service Status** - Health checks for external services
5. **Recent Activity** - Latest lookups and failures

---

## Recommended Reading Order

1. **Start here:** [ASSESSMENT.md](./ASSESSMENT.md) - Current state overview
2. **For changes:** [REFACTOR_PLAN.md](./REFACTOR_PLAN.md) - Detailed refactor recommendations
3. **Architecture:** This document (ARCHITECTURE.md)
4. **Development:** replit.md - Recent changes and system details

---

## Questions & Answers

**Q: Why is the admin class so large?**  
A: It combines dashboard rendering, settings management, analytics calculations, and AJAX handlers. Should be split into 4 classes. See [REFACTOR_PLAN.md](./REFACTOR_PLAN.md).

**Q: Can we add unit tests?**  
A: Yes! Start with helper functions and cache operations. See testing recommendations in [REFACTOR_PLAN.md](./REFACTOR_PLAN.md).

**Q: Is this production-ready?**  
A: Yes, it's currently running in production (v7.0.3). The refactoring is for maintainability, not stability.

**Q: What's the biggest risk?**  
A: Rewrite rules being flushed on every request. This should be fixed immediately.

**Q: Where should new features go?**  
A: New AJAX endpoints → separate handler class; New admin features → split admin classes; New shortcodes → new shortcode class.
