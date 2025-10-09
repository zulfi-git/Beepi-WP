# Testing Guide for Cache Removal

## Overview
This guide outlines the testing needed to verify that the cache removal changes work correctly.

## Pre-Testing Checklist
- [ ] Backup production database
- [ ] Deploy to staging environment first
- [ ] Review CACHE_REMOVAL_SUMMARY.md for context

## Functional Testing

### 1. Basic Vehicle Lookup
**Test Case**: Search for a vehicle by registration number

Steps:
1. Navigate to vehicle lookup page
2. Enter a valid Norwegian registration number (e.g., "AB12345")
3. Click search
4. Verify vehicle data displays correctly
5. Note response time (expect ~1-2 seconds)

Expected Result:
- ✓ Vehicle information displays
- ✓ No cache-related errors in console
- ✓ Data returned from Cloudflare Worker

### 2. AI Summary Generation
**Test Case**: Vehicle lookup with AI summary enabled

Steps:
1. Search for a vehicle with AI summary enabled
2. Wait for initial response
3. Verify polling starts for AI summary
4. Wait for AI summary to complete

Expected Result:
- ✓ AI summary generates successfully
- ✓ Polling works without cache errors
- ✓ Summary displays when complete

### 3. Market Listings
**Test Case**: Market listings display

Steps:
1. Search for a popular vehicle model
2. Wait for market listings to load
3. Verify listings display correctly

Expected Result:
- ✓ Market listings appear
- ✓ No cache-related errors
- ✓ Data refreshes on each search

### 4. Repeated Searches
**Test Case**: Search for the same vehicle multiple times

Steps:
1. Search for vehicle "CO10003"
2. Wait for complete results
3. Search for "CO10003" again immediately
4. Compare response times

Expected Result:
- ✓ Second search works correctly
- ✓ Data is consistent
- ✓ No "second viewing" flickering or missing content
- ✓ Response time may be similar (Cloudflare KV handles caching)

### 5. Rate Limiting
**Test Case**: Verify rate limiting still works

Steps:
1. Make multiple rapid searches (>100 in an hour)
2. Verify rate limit error appears

Expected Result:
- ✓ Rate limiting enforces correctly
- ✓ Error message displays: "For mange forespørsler"

### 6. Daily Quota
**Test Case**: Verify daily quota enforcement

Steps:
1. Check current daily quota usage in admin
2. If near limit, make searches to exceed it
3. Verify quota error appears

Expected Result:
- ✓ Quota enforces correctly
- ✓ Error message displays: "Daglig grense nådd"

## Admin Interface Testing

### 7. Settings Page
**Test Case**: Verify settings page displays correctly

Steps:
1. Navigate to Vehicle Lookup Settings
2. Verify sections display
3. Check for any cache-related fields

Expected Result:
- ✓ No cache duration field
- ✓ No cache enabled checkbox
- ✓ Section labeled "Rate Limiting & Quotas"
- ✓ All other settings intact

### 8. Dashboard Page
**Test Case**: Verify dashboard loads without errors

Steps:
1. Navigate to Vehicle Lookup Dashboard
2. Check service status indicators
3. Verify metrics display

Expected Result:
- ✓ No JavaScript errors
- ✓ No cache statistics section
- ✓ Cloudflare status shows correctly
- ✓ All other metrics display

### 9. Analytics Page
**Test Case**: Verify analytics page works

Steps:
1. Navigate to Analytics page
2. Verify stats display
3. Check popular searches

Expected Result:
- ✓ Stats calculate correctly
- ✓ No cache-related errors
- ✓ All tables render properly

## Browser Console Testing

### 10. Console Errors
**Test Case**: Check for JavaScript errors

Steps:
1. Open browser developer console
2. Navigate through all pages
3. Perform vehicle searches
4. Check for errors

Expected Result:
- ✓ No errors related to cache functions
- ✓ No "undefined is not a function" errors
- ✓ No AJAX errors for removed endpoints

## Performance Testing

### 11. Response Time Monitoring
**Test Case**: Monitor response times over time

Steps:
1. Perform 10 vehicle searches
2. Record response times
3. Calculate average

Expected Metrics:
- First search: ~1-2 seconds
- Subsequent searches: ~1-2 seconds (Cloudflare KV caching)
- No significant difference between first and repeat searches

### 12. API Call Frequency
**Test Case**: Monitor Cloudflare Worker API calls

Steps:
1. Check Cloudflare Worker analytics
2. Perform searches
3. Verify cache behavior

Expected Result:
- ✓ Cloudflare KV handles caching
- ✓ Cache hit rates visible in worker stats
- ✓ No WordPress-side caching

## Edge Cases

### 13. Invalid Registration Numbers
**Test Case**: Verify validation still works

Steps:
1. Try invalid formats: "123", "ABCDEFGH", "A1B2C3D4E5"
2. Verify error messages

Expected Result:
- ✓ Validation errors display correctly
- ✓ No cache errors

### 14. Empty Searches
**Test Case**: Search with empty input

Steps:
1. Submit search with empty field
2. Verify error handling

Expected Result:
- ✓ Error message displays
- ✓ No cache errors

### 15. API Failures
**Test Case**: Handle API errors gracefully

Steps:
1. If possible, simulate API timeout
2. Verify error handling

Expected Result:
- ✓ Error displays to user
- ✓ No cache-related errors
- ✓ System remains functional

## Database Verification

### 16. Lookup Logging
**Test Case**: Verify lookups are still logged

Steps:
1. Perform several searches
2. Check wp_vehicle_lookup_logs table
3. Verify entries

Expected Result:
- ✓ All searches logged
- ✓ Success/failure tracked
- ✓ Response times recorded
- ✓ No cached=1 flag issues

### 17. Transient Cleanup
**Test Case**: Verify no orphaned cache entries

Steps:
1. Check wp_options table
2. Search for: `_transient_vehicle_cache_%`

Expected Result:
- ✓ No vehicle cache transients exist
- ✓ No orphaned timeout entries

## Rollback Testing

### 18. Verify Rollback Readiness
**Test Case**: Document rollback procedure

Verify:
- ✓ Git commit hash recorded
- ✓ Backup created
- ✓ Rollback steps documented in CACHE_REMOVAL_SUMMARY.md

## Sign-Off

After all tests pass:
- [ ] Staging environment approved
- [ ] Performance metrics acceptable
- [ ] No cache-related errors
- [ ] Ready for production deployment

## Production Monitoring (Post-Deployment)

Monitor for 48 hours after deployment:
1. **Response Times**: Track average response times
2. **Error Rates**: Monitor error logs
3. **User Reports**: Watch for 'second viewing' issues
4. **Cloudflare KV**: Monitor cache hit rates

## Success Criteria

✓ All vehicle lookups work correctly
✓ No 'second viewing' issues
✓ No cache-related errors in logs
✓ Response times within acceptable range (1-2s)
✓ Admin interface fully functional
✓ No degradation in user experience
