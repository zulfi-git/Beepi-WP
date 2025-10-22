# SEO Testing and Validation Checklist

## Pre-Testing Setup

### Environment Check
- [ ] Plugin is activated
- [ ] WordPress version 5.5 or higher
- [ ] PHP version 7.4 or higher
- [ ] Permalink structure is set (not default)
- [ ] Rewrite rules flushed (Settings → Permalinks → Save)

### Test Vehicle Page
Choose a vehicle that has been looked up recently for testing:
- Example: `/sok/CO11204`
- Ensure it has cached data in database

## 1. Meta Tags Validation

### Page Title Testing
**Test URL:** `https://yourdomain.com/sok/[REG_NUMBER]`

**Expected:**
```html
<title>Make Model Year (REG_NUMBER) - Eierinformasjon og Kjøretøydata | Beepi</title>
```

**How to Test:**
1. Open vehicle page
2. View page source (Ctrl+U or Cmd+U)
3. Find `<title>` tag in `<head>` section
4. Verify it contains vehicle make, model, year, and registration number

**Status:** [ ] Pass [ ] Fail

---

### Meta Description Testing

**Expected:**
```html
<meta name="description" content="Se detaljert informasjon om [Make] [Model] [Year] ([REG]). Finn eieropplysninger, tekniske spesifikasjoner, markedspris og historikk." />
```

**How to Test:**
1. View page source
2. Find `<meta name="description">`
3. Verify length is 150-160 characters
4. Verify it includes vehicle details

**Status:** [ ] Pass [ ] Fail

---

### Keywords Meta Tag

**Expected:**
```html
<meta name="keywords" content="[REG], [Make] [Model], [Year], kjøretøyoppslag, eieropplysninger, bildata, registreringsnummer" />
```

**Status:** [ ] Pass [ ] Fail

---

### Robots Meta Tag

**Expected:**
```html
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
```

**Status:** [ ] Pass [ ] Fail

---

### Canonical URL

**Expected:**
```html
<link rel="canonical" href="https://yourdomain.com/sok/[REG_NUMBER]" />
```

**Status:** [ ] Pass [ ] Fail

---

## 2. Structured Data (JSON-LD) Validation

### Vehicle Schema

**How to Test:**
1. View page source
2. Find `<script type="application/ld+json">` with `"@type": "Vehicle"`
3. Copy the JSON

**Expected Fields:**
- `@context`: "https://schema.org"
- `@type`: "Vehicle"
- `name`: Vehicle make and model
- `vehicleIdentificationNumber`: Registration number
- `manufacturer`
- `model`
- `productionDate` or `vehicleModelDate`

**Validation:**
1. Go to [Google Rich Results Test](https://search.google.com/test/rich-results)
2. Enter vehicle page URL
3. Should detect "Vehicle" schema
4. Check for errors or warnings

**Status:** [ ] Pass [ ] Fail

---

### Product Schema

**Expected for owner information service:**
- `@type`: "Product"
- `name`: "Eieropplysninger for [Vehicle]"
- `offers` with price in NOK

**Status:** [ ] Pass [ ] Fail

---

### BreadcrumbList Schema

**Expected structure:**
1. Home
2. Kjøretøyoppslag
3. [Make] [Model] ([REG])

**Status:** [ ] Pass [ ] Fail

---

### WebSite Schema (Search Page)

**Test URL:** `https://yourdomain.com/sok`

**Expected:**
- `@type`: "WebSite"
- `potentialAction`: SearchAction with URL template

**Status:** [ ] Pass [ ] Fail

---

## 3. OpenGraph Tags Validation

### Facebook/LinkedIn Sharing

**Expected Tags:**
```html
<meta property="og:type" content="website" />
<meta property="og:title" content="[Make] [Model] [Year] ([REG])" />
<meta property="og:description" content="..." />
<meta property="og:url" content="https://yourdomain.com/sok/[REG]" />
<meta property="og:site_name" content="Beepi" />
<meta property="og:locale" content="nb_NO" />
<meta property="og:image" content="..." />
```

**How to Test:**
1. Go to [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
2. Enter vehicle page URL
3. Click "Scrape Again"
4. Verify preview shows correct title, description, and image

**Status:** [ ] Pass [ ] Fail

---

## 4. Twitter Card Validation

**Expected Tags:**
```html
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="..." />
<meta name="twitter:description" content="..." />
<meta name="twitter:image" content="..." />
```

**How to Test:**
1. Go to [Twitter Card Validator](https://cards-dev.twitter.com/validator)
2. Enter vehicle page URL
3. Verify preview appears correctly

**Status:** [ ] Pass [ ] Fail

---

## 5. Sitemap Validation

### Standalone Sitemap

**Test URL:** `https://yourdomain.com/vehicle-sitemap.xml`

**Expected:**
- Valid XML format
- Main search page listed with priority 1.0
- Vehicle pages listed with registration numbers
- lastmod dates present
- changefreq and priority values

**How to Test:**
1. Open sitemap URL in browser
2. Check XML is well-formed (no errors)
3. Verify vehicle URLs are present
4. Count: Should have popular vehicles (up to 1000)

**Status:** [ ] Pass [ ] Fail

---

### WordPress Core Sitemap

**Test URL:** `https://yourdomain.com/wp-sitemap.xml`

**Expected:**
- Main sitemap index should list vehicle sitemap
- Vehicle sitemap: `https://yourdomain.com/wp-sitemap-vehicles-1.xml`

**Status:** [ ] Pass [ ] Fail

---

### Search Console Submission

**Steps:**
1. Go to [Google Search Console](https://search.google.com/search-console)
2. Select your property
3. Go to Sitemaps (left menu)
4. Add sitemap URL: `/vehicle-sitemap.xml`
5. Click Submit

**Verification:**
- Status shows "Success"
- URLs discovered count is > 0
- No errors

**Status:** [ ] Pass [ ] Fail

---

## 6. Performance Validation

### Cache Headers

**How to Test:**
```bash
curl -I https://yourdomain.com/sok/CO11204
```

**Expected Headers:**
```
Cache-Control: public, max-age=3600, s-maxage=43200
ETag: "..."
Vary: Accept-Encoding
```

**Status:** [ ] Pass [ ] Fail

---

### Resource Hints

**Expected in `<head>`:**
```html
<link rel="preconnect" href="https://lookup.beepi.no" crossorigin />
<link rel="dns-prefetch" href="//lookup.beepi.no" />
<link rel="dns-prefetch" href="//beepi.no" />
```

**Status:** [ ] Pass [ ] Fail

---

### Lazy Loading

**Check image tags:**
```html
<img ... loading="lazy" decoding="async" />
```

**How to Test:**
1. View page source
2. Find `<img>` tags
3. Verify `loading="lazy"` attribute

**Status:** [ ] Pass [ ] Fail

---

### Deferred JavaScript

**Expected:**
```html
<script defer src=".../vehicle-lookup.js"></script>
<script defer src=".../normalize-plate.js"></script>
```

**Status:** [ ] Pass [ ] Fail

---

### PageSpeed Insights

**How to Test:**
1. Go to [PageSpeed Insights](https://pagespeed.web.dev/)
2. Enter vehicle page URL
3. Run test for Mobile and Desktop

**Target Scores:**
- Mobile: 85+
- Desktop: 90+

**Actual Scores:**
- Mobile: ___
- Desktop: ___

**Status:** [ ] Pass [ ] Fail

---

## 7. Content Enhancement Validation

### Related Vehicles Section

**Expected:**
- Section appears at bottom of vehicle page
- Shows 5 related vehicles
- Links are clickable
- Responsive design

**How to Test:**
1. Navigate to vehicle page
2. Scroll to bottom
3. Look for "Andre søkte også på" section
4. Verify vehicles are displayed
5. Click one link to test

**Status:** [ ] Pass [ ] Fail

---

### Related Vehicles Shortcode

**How to Test:**
1. Create a test page/post
2. Add shortcode: `[related_vehicles count="5"]`
3. View page
4. Verify popular vehicles are displayed

**Status:** [ ] Pass [ ] Fail

---

## 8. Analytics Validation

### Google Analytics 4 Integration

**Prerequisites:**
- GA4 Measurement ID entered in settings
- Format: G-XXXXXXXXXX

**How to Test:**
1. Go to vehicle page
2. Open browser DevTools (F12)
3. Go to Network tab
4. Filter for "gtag"
5. Verify requests to `www.googletagmanager.com/gtag/js?id=G-...`

**Alternative Test:**
1. Install Google Tag Assistant extension
2. Visit vehicle page
3. Verify GA4 tag is detected and firing

**Status:** [ ] Pass [ ] Fail

---

### Custom Event Tracking

**Expected Events:**
- `view_vehicle` - When viewing a vehicle page
- `vehicle_lookup` - When performing a lookup
- `click_owner_info` - When clicking owner info

**How to Test:**
1. Go to GA4 Real-Time report
2. Navigate to vehicle page
3. Check Events section in Real-Time
4. Verify "view_vehicle" event appears

**Status:** [ ] Pass [ ] Fail

---

### Search Console Verification

**How to Test:**
1. Go to [Google Search Console](https://search.google.com/search-console)
2. Add property (your domain)
3. Choose "HTML tag" verification method
4. Enter verification code in plugin settings
5. Click "Verify" in Search Console

**Expected:**
```html
<meta name="google-site-verification" content="..." />
```

**Status:** [ ] Pass [ ] Fail

---

### Dashboard Widget

**How to Test:**
1. Go to WordPress Admin Dashboard
2. Look for "Vehicle Lookup Analytics" widget
3. Verify it shows:
   - Today's lookups
   - This week's lookups
   - This month's lookups
   - Top 5 most searched vehicles

**Status:** [ ] Pass [ ] Fail

---

## 9. Cross-Browser Testing

Test on multiple browsers to ensure compatibility:

### Chrome/Chromium
- [ ] Meta tags display correctly
- [ ] Structured data validates
- [ ] Related vehicles section appears
- [ ] Analytics tracking works

### Firefox
- [ ] Meta tags display correctly
- [ ] Structured data validates
- [ ] Related vehicles section appears
- [ ] Analytics tracking works

### Safari
- [ ] Meta tags display correctly
- [ ] Structured data validates
- [ ] Related vehicles section appears
- [ ] Analytics tracking works

### Edge
- [ ] Meta tags display correctly
- [ ] Structured data validates
- [ ] Related vehicles section appears
- [ ] Analytics tracking works

---

## 10. Mobile Responsiveness

### Related Vehicles Section
- [ ] Grid layout adapts to mobile
- [ ] Text is readable
- [ ] Links are tappable
- [ ] Spacing is appropriate

### Meta Tags
- [ ] Viewport meta tag present
- [ ] Social media previews work on mobile sharing

---

## 11. SEO Tools Validation

### Google Rich Results Test
**URL:** https://search.google.com/test/rich-results

**Test Vehicle Page:**
- [ ] Vehicle schema detected
- [ ] Product schema detected
- [ ] BreadcrumbList detected
- [ ] No errors
- [ ] Warnings reviewed and addressed

---

### Schema.org Validator
**URL:** https://validator.schema.org/

**Test Vehicle Page:**
- [ ] JSON-LD validates
- [ ] No errors
- [ ] All required properties present

---

### W3C HTML Validator
**URL:** https://validator.w3.org/

**Test Vehicle Page:**
- [ ] HTML validates
- [ ] Meta tags are well-formed
- [ ] No critical errors

---

## 12. Security Validation

### XSS Prevention
- [ ] Registration numbers are sanitized
- [ ] Meta tag content is escaped
- [ ] URLs are validated
- [ ] JSON-LD data is properly encoded

### SQL Injection Prevention
- [ ] Database queries use prepared statements
- [ ] Input is sanitized
- [ ] No direct SQL concatenation

---

## 13. Conflict Testing

### Other SEO Plugins
Test with popular SEO plugins (if installed):
- [ ] Yoast SEO - No conflicts
- [ ] Rank Math - No conflicts
- [ ] All in One SEO - No conflicts

**Note:** If conflicts exist, our plugin's meta tags should have priority for vehicle pages.

### Caching Plugins
Test with caching plugins:
- [ ] WP Super Cache - Works correctly
- [ ] W3 Total Cache - Works correctly
- [ ] WP Rocket - Works correctly
- [ ] Cache headers are respected

---

## 14. Edge Cases

### Vehicle Without Cached Data
**Test:** Visit `/sok/NONEXISTENT`
- [ ] Page loads without errors
- [ ] Generic meta tags are shown
- [ ] No PHP errors in logs

### Search Page (No Registration Number)
**Test:** Visit `/sok`
- [ ] Generic meta tags shown
- [ ] WebSite schema with SearchAction
- [ ] No errors

### Special Characters in Registration Number
**Test:** Registration numbers with special formats
- [ ] URL encoding works correctly
- [ ] Meta tags display correctly
- [ ] No XSS vulnerabilities

---

## Final Validation Summary

### Critical Issues (Must Fix)
List any failing critical tests:
1. ___________________________________
2. ___________________________________
3. ___________________________________

### Warnings (Should Fix)
List any warnings or minor issues:
1. ___________________________________
2. ___________________________________
3. ___________________________________

### Passed Tests
Total: ___ / ___

### Overall Status
- [ ] Ready for Production
- [ ] Needs Minor Fixes
- [ ] Needs Major Fixes

---

## Testing Completed By

**Name:** ___________________________  
**Date:** ___________________________  
**Version Tested:** 7.3.0+

---

## Notes and Observations

_Use this space for any additional notes, observations, or recommendations:_

___________________________________________
___________________________________________
___________________________________________
___________________________________________

---

**Last Updated:** October 2025
