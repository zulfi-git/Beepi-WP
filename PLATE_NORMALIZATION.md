# Plate Normalization Documentation

## Overview
All Norwegian license plates are now normalized throughout the WordPress plugin to ensure consistency and prevent lookup failures.

## Normalization Rules
1. **Uppercase**: All letters are converted to uppercase
2. **No Whitespace**: All whitespace characters are removed, including:
   - ASCII whitespace (spaces, tabs, newlines, etc.)
   - Unicode whitespace (non-breaking spaces, em spaces, thin spaces, etc.)
   - Zero-width characters (zero-width space, zero-width no-break space)

## Examples
| Input | Normalized Output |
|-------|------------------|
| `AB12345` | `AB12345` |
| `ab12345` | `AB12345` |
| `AB 12345` | `AB12345` |
| `AB  12345` | `AB12345` |
| `ab 12 345` | `AB12345` |
| `  AB12345  ` | `AB12345` |
| `AB 12345` (non-breaking space U+00A0) | `AB12345` |
| `AB 12345` (em space U+2003) | `AB12345` |
| `AB​12345` (zero-width space U+200B) | `AB12345` |

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
    
    // Remove all whitespace characters (including Unicode whitespace) and convert to uppercase
    // \p{Z} = all Unicode separator characters (spaces)
    // \p{C} = all Unicode control characters (including zero-width spaces)
    // \s = ASCII whitespace for backwards compatibility
    return strtoupper(preg_replace('/[\p{Z}\p{C}\s]+/u', '', $plate));
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
    // Remove all Unicode whitespace characters:
    // \s = ASCII whitespace
    // \u00A0 = non-breaking space
    // \u2000-\u200B = various Unicode spaces (em space, en space, thin space, zero-width space, etc.)
    // \uFEFF = zero-width no-break space (BOM)
    return plate.toString().replace(/[\s\u00A0\u2000-\u200B\uFEFF]+/g, '').toUpperCase();
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

### 6. Unicode Whitespace Handling
Registration plates copy-pasted from HTML or rich text (containing Unicode whitespace like non-breaking spaces) are correctly normalized, preventing cache mismatches and lookup failures.

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
    // Remove all Unicode whitespace characters:
    // \s = ASCII whitespace
    // \u00A0 = non-breaking space
    // \u2000-\u200B = various Unicode spaces
    // \uFEFF = zero-width no-break space (BOM)
    return plate.toString().replace(/[\s\u00A0\u2000-\u200B\uFEFF]+/g, '').toUpperCase();
}
```

This ensures:
- KV cache keys match between frontend and worker
- Consistent behavior across the entire system
- No lookup failures due to format mismatches
- Unicode whitespace from copy-pasted HTML is handled correctly

## Future Considerations

1. **Database Migration** (optional): Normalize existing database records for historical consistency
2. **Analytics Updates**: Update analytics queries to account for normalized format
3. **Admin Tools**: Provide admin interface to clear cache entries with old format if needed
