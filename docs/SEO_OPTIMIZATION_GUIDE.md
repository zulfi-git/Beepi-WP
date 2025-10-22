# SEO and AI Optimization Guide

## Overview

The Beepi Vehicle Lookup plugin provides vehicle-specific SEO optimization that complements standard WordPress SEO plugins. This implementation focuses on what general SEO tools cannot provide: vehicle-specific structured data, dynamic meta tags for vehicle pages, and internal linking for vehicle discovery.

**Design Philosophy:** Work alongside popular WordPress plugins (Rank Math SEO, LiteSpeed Cache, Site Kit by Google) rather than replace them. We handle vehicle-specific optimizations; they handle general site optimization.

## What This Plugin Provides

### 1. Vehicle-Specific SEO Meta Tags

Every dynamically generated vehicle page (`/sok/CO11204`) includes:

#### Dynamic Title Tags
Optimized with actual vehicle details from your database:
```html
<title>Toyota Corolla 2020 (CO11204) - Eierinformasjon og Kjøretøydata | Beepi</title>
```

#### Unique Meta Descriptions
Custom descriptions per vehicle (150-160 characters):
```html
<meta name="description" content="Se detaljert informasjon om Toyota Corolla 2020 (CO11204). Finn eieropplysninger, tekniske spesifikasjoner, markedspris og historikk." />
```

#### Targeted Keywords
Registration number plus vehicle details:
```html
<meta name="keywords" content="CO11204, Toyota Corolla, 2020, kjøretøyoppslag, eieropplysninger, bildata, registreringsnummer" />
```

#### Search Engine Directives
```html
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
<link rel="canonical" href="https://beepi.no/sok/CO11204" />
```

**Why This Matters:** General SEO plugins like Rank Math can't generate vehicle-specific titles and descriptions because they don't have access to your vehicle database. Our implementation bridges this gap.

### 2. Vehicle-Specific Structured Data (JSON-LD)

Each vehicle page includes industry-standard schema markup:

#### Vehicle Schema
```json
{
  "@context": "https://schema.org",
  "@type": "Vehicle",
  "name": "Toyota Corolla",
  "vehicleIdentificationNumber": "CO11204",
  "manufacturer": {
    "@type": "Organization",
    "name": "Toyota"
  },
  "model": "Corolla",
  "productionDate": "2020",
  "color": "Blå",
  "fuelType": "Bensin"
}
```

#### Product Schema (Owner Information Service)
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Eieropplysninger for Toyota Corolla (CO11204)",
  "offers": {
    "@type": "Offer",
    "price": "69",
    "priceCurrency": "NOK",
    "availability": "https://schema.org/InStock"
  }
}
```

#### BreadcrumbList Schema
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Hjem", "item": "https://beepi.no"},
    {"@type": "ListItem", "position": 2, "name": "Kjøretøyoppslag", "item": "https://beepi.no/sok"},
    {"@type": "ListItem", "position": 3, "name": "Toyota Corolla (CO11204)", "item": "https://beepi.no/sok/CO11204"}
  ]
}
```

#### WebSite Schema with SearchAction
```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Beepi",
  "url": "https://beepi.no",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://beepi.no/sok/{search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
```

**Why This Matters:** These schemas enable Google to understand and display vehicle information as rich snippets in search results. Rank Math provides general schemas, but doesn't understand vehicle-specific data structures.

### 3. Social Media Optimization

#### OpenGraph Tags (Facebook, LinkedIn, Discord, etc.)
```html
<meta property="og:type" content="website" />
<meta property="og:title" content="Toyota Corolla 2020 (CO11204)" />
<meta property="og:description" content="Se detaljert informasjon om Toyota Corolla 2020..." />
<meta property="og:url" content="https://beepi.no/sok/CO11204" />
<meta property="og:site_name" content="Beepi" />
<meta property="og:locale" content="nb_NO" />
<meta property="og:image" content="..." />
```

#### Twitter Card Tags
```html
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="Toyota Corolla 2020 (CO11204)" />
<meta name="twitter:description" content="Se detaljert informasjon om Toyota Corolla 2020..." />
<meta name="twitter:image" content="..." />
```

**Why This Matters:** When vehicle pages are shared on social media or in AI chatbots, these tags ensure rich, attractive previews with vehicle-specific information.

### 4. Content Enhancement & Internal Linking

#### "Andre søkte også på" Widget
Automatically displayed at the bottom of each vehicle page:
- Shows 5 related vehicles
- Prioritizes vehicles with the same make
- Falls back to popular recent searches
- Responsive grid layout
- Mobile-optimized

#### [related_vehicles] Shortcode
Flexible placement anywhere:
```
[related_vehicles count="5" title="Populære søk"]
```

**Why This Matters:** 
- Improves internal linking structure (important SEO signal)
- Reduces bounce rate
- Increases pages per session
- Helps users discover more vehicles

## Integration with Standard WordPress Plugins

This plugin is designed to complement, not replace, standard WordPress optimization plugins:

### Rank Math SEO (or Yoast SEO)
**Use Rank Math For:**
- General site SEO settings
- XML sitemap generation
- Breadcrumb navigation
- Schema for blog posts and pages
- Focus keyword optimization
- SEO analysis

**We Handle:**
- Vehicle-specific structured data
- Dynamic vehicle page titles and descriptions
- Vehicle schema markup
- Related vehicle suggestions

**Configuration:**
1. Install Rank Math SEO
2. Run the setup wizard
3. Enable sitemap generation
4. Submit sitemap to Google Search Console
5. Our vehicle pages will automatically appear in Rank Math's sitemap

### LiteSpeed Cache (or WP Rocket, W3 Total Cache)
**Use LiteSpeed Cache For:**
- Page caching
- Browser caching
- Object caching
- Image optimization and lazy loading
- CSS/JS minification
- CDN integration

**We Removed:**
- Our performance optimization class (redundant with LiteSpeed)

**Configuration:**
1. Install LiteSpeed Cache
2. Enable page caching for public pages
3. Enable lazy loading for images
4. Enable CSS/JS optimization
5. Set cache TTL to 1 hour for dynamic pages
6. Our vehicle pages will be automatically cached

### Site Kit by Google
**Use Site Kit For:**
- Google Analytics 4 tracking
- Search Console integration
- PageSpeed Insights monitoring
- AdSense integration

**We Removed:**
- Our analytics integration class (redundant with Site Kit)

**Configuration:**
1. Install Site Kit by Google
2. Connect your Google account
3. Enable Google Analytics 4
4. Enable Search Console
5. Vehicle page views will be automatically tracked

## Setup Guide

### Step 1: Install Required Plugins

**Essential:**
1. Beepi Vehicle Lookup (this plugin)
2. Rank Math SEO (or Yoast SEO)

**Recommended:**
3. LiteSpeed Cache (or WP Rocket/W3 Total Cache)
4. Site Kit by Google

### Step 2: Activate Beepi Vehicle Lookup

1. Activate the plugin in WordPress
2. Go to Settings → Permalinks
3. Click "Save Changes" (flushes rewrite rules)
4. Vehicle pages now work at `/sok/[registration]`

### Step 3: Configure Rank Math SEO

1. Install and activate Rank Math SEO
2. Run the setup wizard:
   - Connect Google Search Console
   - Choose site type (Business)
   - Enable sitemap
3. Go to Rank Math → Sitemap Settings
4. Ensure "Posts" sitemap is enabled
5. Submit sitemap URL to Search Console:
   - URL: `https://yourdomain.com/sitemap_index.xml`

### Step 4: Configure LiteSpeed Cache

1. Install and activate LiteSpeed Cache
2. Go to LiteSpeed Cache → Cache settings:
   - Enable cache for public pages
   - Set TTL to 3600 seconds (1 hour)
3. Go to Page Optimization:
   - Enable CSS minification
   - Enable JavaScript minification
   - Enable lazy load for images
4. Test a vehicle page to ensure caching works

### Step 5: Configure Site Kit by Google

1. Install and activate Site Kit
2. Click "Start Setup"
3. Sign in with Google
4. Grant permissions
5. Enable these services:
   - Search Console
   - Google Analytics (choose GA4)
6. Complete setup

Your vehicle pages will now:
- Appear in Google Search Console
- Be tracked in Google Analytics
- Show up in PageSpeed Insights

## Technical Implementation Details

### Data Source for Meta Tags

Vehicle information comes from:
1. **WordPress transient cache** (12-hour TTL)
2. **Database lookup logs** (most recent successful lookup)
3. **Fallback**: Registration number only if no data available

This approach ensures:
- Fast page loads (cached data)
- Always up-to-date information
- Graceful degradation if data unavailable

### Page Detection

The plugin detects vehicle pages by checking:
1. Is the page slug `sok`?
2. Does the URL have a `reg_number` query parameter?
3. Does the URL match pattern `/sok/[registration]`?

### Hook Priority

Our hooks are carefully ordered to avoid conflicts:
- `wp_head` priority 1: Canonical URL, Search Console verification
- `wp_head` priority 2: Structured data (JSON-LD)
- `document_title_parts`: Title modification
- `wp_footer` priority 20: Related vehicles widget

## Testing Your Implementation

### 1. Test Meta Tags

Visit a vehicle page (e.g., `/sok/CO11204`) and view source (Ctrl+U):

**Check for:**
- `<title>` with vehicle details
- `<meta name="description">` with vehicle info
- `<meta name="keywords">` with registration number
- `<link rel="canonical">`
- OpenGraph tags (`og:title`, `og:description`, etc.)
- Twitter Card tags

### 2. Test Structured Data

**Option A: Google Rich Results Test**
1. Go to https://search.google.com/test/rich-results
2. Enter your vehicle page URL
3. Should detect: Vehicle, Product, BreadcrumbList schemas
4. Check for errors or warnings

**Option B: Schema.org Validator**
1. Go to https://validator.schema.org/
2. Paste your vehicle page URL
3. Verify JSON-LD validates without errors

### 3. Test Social Sharing

**Facebook:**
1. Go to https://developers.facebook.com/tools/debug/
2. Enter vehicle page URL
3. Click "Scrape Again"
4. Verify preview shows vehicle title and description

**Twitter:**
1. Go to https://cards-dev.twitter.com/validator
2. Enter vehicle page URL
3. Verify card preview appears correctly

### 4. Test Related Vehicles Widget

1. Navigate to a vehicle page
2. Scroll to bottom
3. Look for "Andre søkte også på" section
4. Verify 5 vehicles are displayed
5. Click a link to test navigation

### 5. Test Search Console Integration

1. Go to Google Search Console
2. Use URL Inspection tool
3. Enter a vehicle page URL
4. Check indexing status
5. Request indexing if needed

## Expected Results

### Short-term (1-2 weeks)
- Vehicle pages start appearing in Search Console
- Rich snippets begin testing in Google results
- Social media shares show vehicle-specific previews

### Medium-term (1-3 months)
- Improved rankings for specific registration numbers
- More vehicle pages indexed by Google
- Increased organic traffic to vehicle pages
- Better click-through rates from search results

### Long-term (3-6 months)
- Established authority for Norwegian vehicle lookups
- 20-50% increase in organic search traffic
- Lower bounce rates due to related vehicle suggestions
- Consistent growth in indexed vehicle pages

## Troubleshooting

### Meta Tags Not Showing

**Problem:** View source shows no vehicle-specific meta tags

**Solutions:**
1. Check if another SEO plugin is overriding (temporarily disable Rank Math)
2. Clear all caches (browser, WordPress, CDN)
3. Verify plugin is activated
4. Check error logs for PHP errors

### Structured Data Errors

**Problem:** Google Rich Results Test shows errors

**Solutions:**
1. Ensure you're testing a vehicle page with recent lookup data
2. Do a fresh lookup to populate cache
3. Check that vehicle data includes required fields (make, model)
4. Validate JSON-LD syntax manually

### Related Vehicles Not Showing

**Problem:** "Andre søkte også på" section missing

**Solutions:**
1. Verify you're on a vehicle page (not search page)
2. Check if there's lookup data in database (need at least 5 vehicles)
3. Clear cache and reload
4. Check browser console for JavaScript errors

### Page Not Caching

**Problem:** Vehicle pages always load slowly

**Solutions:**
1. Verify LiteSpeed Cache is active and configured
2. Check cache status in response headers
3. Exclude logged-in users from cache
4. Set appropriate TTL (3600 seconds recommended)

## Best Practices

### For Site Owners

1. **Regular Monitoring**
   - Check Search Console weekly for indexing issues
   - Monitor popular vehicle searches
   - Review structured data errors monthly

2. **Content Quality**
   - Ensure vehicle data is accurate and complete
   - Keep database updated with recent lookups
   - Monitor for any data quality issues

3. **Performance**
   - Use LiteSpeed Cache or similar
   - Enable CDN for better global performance
   - Monitor page load times monthly

### For Developers

1. **Extending the Plugin**
   - Use WordPress hooks to add custom functionality
   - Don't modify core plugin files
   - Test changes on staging first

2. **Custom Schemas**
   - Add custom structured data via `wp_head` hook
   - Use priority > 2 to run after our schemas
   - Validate with Google Rich Results Test

3. **Performance**
   - Minimize database queries
   - Use transient cache for frequently accessed data
   - Profile page load times regularly

## Support and Updates

### Documentation
- **This Guide**: Complete feature documentation
- **Quick Setup**: `docs/SEO_QUICK_SETUP.md`
- **Testing Checklist**: `docs/SEO_TESTING_CHECKLIST.md`

### External Resources
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
- [Twitter Card Validator](https://cards-dev.twitter.com/validator)
- [Schema.org Validator](https://validator.schema.org/)

### Updates
The plugin is regularly updated to:
- Adapt to search engine algorithm changes
- Add new structured data types
- Improve compatibility with popular plugins
- Optimize performance

---

**Last Updated:** October 2025  
**Plugin Version:** 7.3.0+  
**Compatibility:** WordPress 5.5+, PHP 7.4+
