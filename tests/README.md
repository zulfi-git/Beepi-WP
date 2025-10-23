# SEO Meta Tags Test Suite

This directory contains automated tests for validating the SEO implementation in the Beepi Vehicle Lookup WordPress plugin.

## Test Files

### test-seo-meta-tags.php
Comprehensive test suite for validating SEO meta tags implementation.

**Coverage:**
- Class structure and methods
- Meta tag formats (title, description, keywords, robots)
- Canonical URL implementation
- OpenGraph tags for social media
- Twitter Card tags
- Structured data (JSON-LD) schemas
- Security (escaping and sanitization)

**Tests:** 15  
**Expected Result:** 100% pass rate

## Running Tests

### Prerequisites
- PHP 7.4 or higher
- Access to the repository files

### Execute Tests

```bash
# From repository root
php tests/test-seo-meta-tags.php
```

### Expected Output

```
=================================================================
SEO Meta Tags Test Script
Testing Vehicle_Lookup_SEO Implementation
=================================================================

[Test 1] Vehicle_Lookup_SEO class exists... ✓ PASS
[Test 2] SEO class has required methods... ✓ PASS
[Test 3] Robots meta tag has correct format... ✓ PASS
[Test 4] Canonical URL uses correct format... ✓ PASS
[Test 5] Page title includes vehicle details... ✓ PASS
[Test 6] Meta description includes vehicle details... ✓ PASS
[Test 7] OpenGraph tags are implemented... ✓ PASS
[Test 8] Twitter Card tags are implemented... ✓ PASS
[Test 9] Vehicle structured data schema is complete... ✓ PASS
[Test 10] BreadcrumbList schema is implemented... ✓ PASS
[Test 11] Product schema for owner info is implemented... ✓ PASS
[Test 12] WebSite schema with SearchAction is implemented... ✓ PASS
[Test 13] Output is properly escaped for security... ✓ PASS
[Test 14] Input is properly sanitized... ✓ PASS
[Test 15] Structured data uses wp_json_encode... ✓ PASS

=================================================================
Test Summary
=================================================================
Total Tests: 15
Passed: 15 ✓
Failed: 0 ✗
Success Rate: 100%
=================================================================

Status: PASSED ✓

All SEO meta tag requirements are properly implemented!
```

## Test Details

### 1. Class Structure Tests
- Verifies `Vehicle_Lookup_SEO` class file exists
- Checks all required methods are present

### 2. Meta Tag Format Tests
- Validates robots meta tag uses complete format
- Verifies canonical URL structure
- Checks page title format
- Validates meta description content

### 3. Social Media Tests
- OpenGraph tags for Facebook/LinkedIn
- Twitter Card tags
- Proper image handling

### 4. Structured Data Tests
- Vehicle schema (JSON-LD)
- BreadcrumbList schema
- Product schema
- WebSite schema with SearchAction

### 5. Security Tests
- Output escaping (esc_attr, esc_url, esc_html)
- Input sanitization (sanitize_text_field)
- JSON encoding (wp_json_encode)

## Exit Codes

- `0` - All tests passed
- `1` - One or more tests failed

## CI/CD Integration

These tests can be integrated into continuous integration pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run SEO Tests
  run: php tests/test-seo-meta-tags.php
```

## Manual Testing

While automated tests validate the implementation, manual testing on a live WordPress site is recommended:

1. **Google Rich Results Test:** https://search.google.com/test/rich-results
2. **Facebook Sharing Debugger:** https://developers.facebook.com/tools/debug/
3. **Twitter Card Validator:** https://cards-dev.twitter.com/validator
4. **Schema.org Validator:** https://validator.schema.org/
5. **W3C HTML Validator:** https://validator.w3.org/

See `docs/seo/QUICK_TEST_GUIDE.md` for detailed manual testing steps.

## Related Documentation

- `docs/seo/VERIFICATION_REPORT.md` - Complete verification report
- `docs/seo/IMPLEMENTATION_SUMMARY.md` - Implementation summary
- `docs/seo/QUICK_TEST_GUIDE.md` - Quick testing guide
- `docs/seo/TESTING.md` - Comprehensive testing checklist

## Troubleshooting

### Test Failures

If tests fail:

1. Check PHP version (must be 7.4+)
2. Verify file exists: `includes/class-vehicle-lookup-seo.php`
3. Review error messages for specific issues
4. Check for syntax errors: `php -l includes/class-vehicle-lookup-seo.php`

### Common Issues

**"File not found"**
- Ensure you're running from repository root
- Verify path to includes directory is correct

**"Missing method"**
- Check if `class-vehicle-lookup-seo.php` has been modified
- Verify all methods listed in test are present

**"Incomplete robots tag"**
- This indicates the robots meta tag doesn't have full parameters
- Expected: `index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1`

## Contributing

When adding new SEO features:

1. Update `class-vehicle-lookup-seo.php` with new functionality
2. Add corresponding tests to `test-seo-meta-tags.php`
3. Run tests to ensure 100% pass rate
4. Update documentation

---

**Version:** 7.4.0  
**Last Updated:** October 23, 2025  
**Maintained by:** Beepi Development Team
