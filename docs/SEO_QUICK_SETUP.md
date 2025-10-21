# SEO Quick Setup Guide

## Quick Start (5 Minutes)

### 1. Activate SEO Features
The SEO features are automatically enabled when the plugin is active. No configuration needed to start!

### 2. Flush Rewrite Rules
To enable the sitemap and clean URLs:
1. Go to **WordPress Admin → Settings → Permalinks**
2. Click **"Save Changes"** (no need to change anything)
3. Done! Your sitemap is now available at: `https://yourdomain.com/vehicle-sitemap.xml`

### 3. Configure Analytics (Optional but Recommended)
1. Go to **WordPress Admin → Vehicle Lookup → Analytics**
2. Enter your **Google Analytics 4 Measurement ID** (e.g., G-XXXXXXXXXX)
3. Enter your **Google Search Console verification code**
4. Click **"Save Settings"**

### 4. Submit to Google Search Console
1. Visit [Google Search Console](https://search.google.com/search-console)
2. Add your property and verify (use the code from step 3)
3. Go to **Sitemaps** in the left menu
4. Add sitemap: `https://yourdomain.com/vehicle-sitemap.xml`
5. Click **Submit**

## What You Get

### ✅ Automatic SEO Optimization
Every vehicle page now has:
- Optimized title tags (e.g., "Toyota Corolla 2020 (CO11204) - Eierinformasjon")
- Unique meta descriptions
- Structured data (JSON-LD) for rich snippets
- OpenGraph tags for social media
- Canonical URLs

### ✅ XML Sitemap
- Automatically generated from your lookup database
- Updates based on popular searches
- Includes up to 1000 most searched vehicles
- Accessible at `/vehicle-sitemap.xml`

### ✅ Performance Boost
- Browser caching (1 hour)
- CDN caching (12 hours)
- Lazy loading images
- Deferred JavaScript

### ✅ Internal Linking
- Related vehicles section on each page
- "Andre søkte også på" widget
- [related_vehicles] shortcode available

### ✅ Analytics Tracking
- Google Analytics 4 integration
- Custom events for vehicle lookups
- Dashboard widget with statistics
- Search Console integration

## Testing Your Setup

### 1. Test Meta Tags
Visit any vehicle page and view source (Ctrl+U):
```
https://yourdomain.com/sok/CO11204
```
Look for:
- `<title>` tag with vehicle info
- `<meta name="description">` 
- `<meta property="og:title">`
- `<script type="application/ld+json">` with Vehicle schema

### 2. Test Sitemap
Open in browser:
```
https://yourdomain.com/vehicle-sitemap.xml
```
You should see XML with vehicle URLs.

### 3. Validate Structured Data
1. Go to [Google Rich Results Test](https://search.google.com/test/rich-results)
2. Enter a vehicle page URL
3. Should detect "Vehicle" and "Product" schemas

### 4. Test Social Sharing
1. Go to [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
2. Enter a vehicle page URL
3. Should show title, description, and image preview

### 5. Check Performance
1. Go to [PageSpeed Insights](https://pagespeed.web.dev/)
2. Enter a vehicle page URL
3. Target: 90+ score

## Common Issues & Solutions

### Sitemap Returns 404
**Solution:** Flush rewrite rules (Settings → Permalinks → Save Changes)

### Meta Tags Not Showing
**Solution:** Clear all caches (browser, WordPress, CDN) and check for conflicting SEO plugins

### Analytics Not Tracking
**Solution:** Verify GA4 Measurement ID format (should start with G-) in Vehicle Lookup → Analytics

## Advanced Features

### Related Vehicles Shortcode
Add to any page or post:
```
[related_vehicles count="5" title="Populære søk"]
```

### Custom Cache Duration
Edit `includes/class-vehicle-lookup-performance.php` to change cache times.

### More Vehicles in Sitemap
Edit `includes/class-vehicle-lookup-sitemap.php` line 49 to increase from 1000.

## Support

For detailed documentation, see: `docs/SEO_OPTIMIZATION_GUIDE.md`

For issues or questions, contact the development team.

---

**Last Updated:** October 2025  
**Plugin Version:** 7.3.0+
