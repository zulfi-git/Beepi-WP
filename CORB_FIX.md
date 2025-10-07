# CORB (Cross-Origin Read Blocking) Fix for Tailwind CSS

## Issue
The plugin was experiencing Cross-Origin Read Blocking (CORB) errors when loading Tailwind CSS from the jsdelivr CDN:

```
Cross-Origin Read Blocking (CORB) blocked a cross-origin response.
Request: tailwind.min.css?ver=3.4.1
```

This prevented the stylesheet from loading, breaking the styling for the vehicle lookup interface.

## Root Cause
When loading external stylesheets from CDNs without proper CORS headers, browsers may block the response due to security policies. The Tailwind CSS file was being loaded without the `crossorigin` attribute, which is required for proper CORS handling.

## Solution
Added the `crossorigin="anonymous"` attribute to the Tailwind CSS `<link>` tag. This is accomplished in WordPress by:

1. Enqueuing the stylesheet as usual with `wp_enqueue_style()`
2. Adding a filter on `style_loader_tag` to inject the `crossorigin` attribute
3. The filter specifically targets the 'tailwindcss' handle

### Code Changes
File: `includes/class-vehicle-lookup.php`

**Added filter registration:**
```php
// Add crossorigin attribute to CDN stylesheet to prevent CORB blocking
add_filter('style_loader_tag', array($this, 'add_crossorigin_to_tailwind'), 10, 2);
```

**Added new method:**
```php
/**
 * Add crossorigin attribute to Tailwind CSS to prevent CORB errors
 * 
 * @param string $tag The link tag for the stylesheet
 * @param string $handle The stylesheet handle
 * @return string Modified link tag with crossorigin attribute
 */
public function add_crossorigin_to_tailwind($tag, $handle) {
    if ('tailwindcss' === $handle) {
        $tag = str_replace(' />', ' crossorigin="anonymous" />', $tag);
    }
    return $tag;
}
```

## Result
The Tailwind CSS stylesheet now loads with the proper CORS attribute:
```html
<link rel='stylesheet' id='tailwindcss-css' 
      href='https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css?ver=3.4.1' 
      media='all' 
      crossorigin="anonymous" />
```

This allows the browser to properly load the external stylesheet without CORB errors.

## Technical Details

### What is CORB?
Cross-Origin Read Blocking (CORB) is a security feature that prevents the browser from delivering certain cross-origin network responses to web pages. It's designed to prevent sensitive data leakage.

### Why crossorigin="anonymous"?
- The `anonymous` value indicates that no credentials are sent with the request
- This is the standard approach for loading public CDN resources
- It allows the browser to properly handle CORS headers from the CDN

### Alternative Solutions (Not Implemented)
1. **Host Tailwind locally**: Download and include Tailwind CSS in the plugin assets
   - Pros: No external dependencies, no CORS issues
   - Cons: Increases plugin size, requires manual updates
   
2. **Use WordPress CDN features**: Some WordPress CDN plugins handle CORS automatically
   - Pros: Automatic handling
   - Cons: Requires additional plugins, not always reliable

### Why the Current Solution is Best
- Minimal code change (just adding an attribute)
- No increase in plugin file size
- Maintains CDN benefits (caching, performance)
- Standard and widely-supported approach
- Easy to maintain and understand

## Testing
To verify the fix:
1. Load a page with the vehicle lookup shortcode
2. Open browser DevTools > Console
3. Check for CORB errors - should be none
4. Verify Tailwind styles are applied correctly

## References
- [MDN: CORS settings attributes](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/crossorigin)
- [WordPress style_loader_tag filter](https://developer.wordpress.org/reference/hooks/style_loader_tag/)
- [Chrome CORB documentation](https://chromium.googlesource.com/chromium/src/+/master/services/network/cross_origin_read_blocking_explainer.md)

---

*Fixed: October 2025*
*Related Issue: #37*
