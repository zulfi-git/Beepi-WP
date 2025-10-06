# Plate Normalization Documentation

## Overview
All Norwegian license plates are now normalized throughout the WordPress plugin to ensure consistency and prevent lookup failures.

## Normalization Rules
1. **Uppercase**: All letters are converted to uppercase
2. **No Spaces**: All spaces (leading, trailing, and internal) are removed

## Examples
| Input | Normalized Output |
|-------|------------------|
| `AB12345` | `AB12345` |
| `ab12345` | `AB12345` |
| `AB 12345` | `AB12345` |
| `AB  12345` | `AB12345` |
| `ab 12 345` | `AB12345` |
| `  AB12345  ` | `AB12345` |

## Implementation

### PHP
A helper function `Vehicle_Lookup_Helpers::normalize_plate()` is used throughout the PHP codebase:

```php
public static function normalize_plate($plate) {
    if (empty($plate)) {
        return '';
    }
    
    // Convert to string if needed
    $plate = (string) $plate;
    
    // Remove all spaces and convert to uppercase
    return strtoupper(str_replace(' ', '', $plate));
}
```

**Used in:**
- `class-vehicle-lookup.php` - AJAX handlers (handle_lookup, handle_ai_summary_poll)
- `class-vehicle-lookup-helpers.php` - URL parameter extraction (get_reg_from_url)
- `class-vehicle-lookup-cache.php` - Cache key generation (get_cache_key)
- `class-vehicle-lookup-database.php` - Database logging (log_lookup)
- `class-vehicle-lookup-woocommerce.php` - Cookie retrieval (get_registration_number)
- `class-order-confirmation-shortcode.php` - Order meta retrieval (handle_payment_complete, render_shortcode)

### JavaScript
A helper function `normalizePlate()` is defined in a standalone module and used throughout the JavaScript codebase:

**Module:** `assets/js/normalize-plate.js`

```javascript
function normalizePlate(plate) {
    if (!plate) return '';
    return plate.toString().replace(/\s+/g, '').toUpperCase();
}
```

This module exports the function for both Node.js (CommonJS) and browser environments (global scope).

**Used in:**
- `vehicle-lookup.js` - Main form submission, error tracking, polling, purchase handling
- `class-vehicle-search-shortcode.php` - Search form inline JavaScript
- `class-vehicle-eu-search-shortcode.php` - EU search form inline JavaScript
- `test-plate-normalization.sh` - Integration tests

## Benefits

### 1. Cache Consistency
Previously, `AB12345` and `AB 12345` would generate different cache keys, leading to unnecessary API calls. Now they both generate the same cache key.

### 2. Database Consistency
All lookups are logged with normalized plate numbers, making analytics and debugging more reliable.

### 3. Backend/Worker Compatibility
The worker (Cloudflare) uses the same normalization, ensuring consistent KV key lookups.

### 4. User Experience
Users can enter plates with or without spaces (e.g., "AB 12345" or "AB12345") and get consistent results.

### 5. API Call Efficiency
Prevents duplicate API calls for the same plate entered in different formats.

## Testing

### PHP Tests
Run the test script to verify PHP normalization:
```bash
php /tmp/test_normalize.php
```

### JavaScript Tests
Run the test script to verify JavaScript normalization:
```bash
node /tmp/test_normalize.js
```

## Backward Compatibility

### Cache Migration
Existing cache entries with spaces will not match new normalized cache keys. This is acceptable as:
1. Cache TTL is typically 24 hours or less
2. A cache miss will result in a fresh API call (expected behavior)
3. Future lookups will use the normalized cache key

### Database Records
Historical database records may contain plates with spaces. This is acceptable as:
1. New lookups will use normalized format
2. Historical data remains for analytics
3. Queries can normalize on-the-fly if needed

## Coordination with Backend

The Cloudflare Worker backend should implement identical normalization:
```javascript
function normalizePlate(plate) {
    if (!plate) return '';
    return plate.toString().replace(/\s+/g, '').toUpperCase();
}
```

This ensures:
- KV cache keys match between frontend and worker
- Consistent behavior across the entire system
- No lookup failures due to format mismatches

## Future Considerations

1. **Database Migration** (optional): Normalize existing database records for historical consistency
2. **Analytics Updates**: Update analytics queries to account for normalized format
3. **Admin Tools**: Provide admin interface to clear cache entries with old format if needed
