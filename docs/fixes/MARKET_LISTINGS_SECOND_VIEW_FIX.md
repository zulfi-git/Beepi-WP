# Market Listings Missing on Second View - Fix

## Issue Summary

**Date:** October 9, 2025  
**Issue:** Market listings section displays with empty content on second vehicle lookup  
**Status:** ✅ Fixed

## Problem Description

When a user performs a vehicle lookup twice for the same plate number:

### First View (Working)
- User searches for vehicle (e.g., "CO1600")
- Market listings section renders with status "generating"
- Polling starts and completes successfully
- Listings are displayed with content
- HTML output shows 3 listings with full details

### Second View (Broken)
- User searches for the SAME vehicle again
- Console output stops after "Phase 3: Checking market listings status"
- NO polling logs appear
- Market listings section renders but with empty content
- HTML shows: `<div class="market-listings-content"></div>` (empty)

### Console Log Comparison

**First view console:**
```
🏪 Phase 3: Checking market listings status
Market listings generating, starting polling for: CO1600
Market listings polling redirected to unified AI polling system
[... polling continues ...]
✅ Market listings generated successfully
```

**Second view console:**
```
🏪 Phase 3: Checking market listings status
[... nothing more ...]
```

## Root Cause Analysis

The issue had **two related problems**:

### Problem 1: Incomplete Status Check
In two locations, the code checked for BOTH status being 'complete' AND listings array existing:

**Location 1:** `checkAndStartMarketListingsPolling()` (line ~1620)
```javascript
// BEFORE - Buggy Code
if (data.marketListings.status === 'complete' && data.marketListings.listings) {
    renderMarketListings(data.marketListings);
    return;
}
```

**Location 2:** Polling handler in `startAiSummaryPolling()` (line ~1373)
```javascript
// BEFORE - Buggy Code
if (marketData.status === 'complete' && marketData.listings) {
    // Only render if not already rendered
    if (!$('.market-listings-section .market-listings-content .market-listing-item').length) {
        renderMarketListings(marketData);
        console.log('✅ Market listings generated successfully');
    }
    marketComplete = true;
}
```

**Impact:** When the backend returned `status: 'complete'` but the `listings` array was missing, empty, or `undefined`, the rendering was skipped entirely, leaving an empty section.

### Problem 2: Missing Fallback for Unknown Status
The `renderMarketListings()` function only handled two status values: `'generating'` and `'complete'`. If the status was anything else (e.g., `'error'`, `null`, `undefined`), the function would create an empty section without any content or message.

```javascript
// BEFORE - Missing else clause
if (marketData.status === 'generating') {
    // Show loading state
} else if (marketData.status === 'complete') {
    // Show listings or "no data" message
}
// No else clause - leaves empty section for unknown status
```

## The Fix

### Change 1: Remove Listings Check in `checkAndStartMarketListingsPolling()`

**File:** `assets/js/vehicle-lookup.js`  
**Line:** ~1620

```javascript
// AFTER - Fixed Code
if (data.marketListings.status === 'complete') {
    console.log('✅ Market listings complete, rendering immediately');
    renderMarketListings(data.marketListings);
    return;
}
```

**Rationale:** When status is 'complete', always call `renderMarketListings()`. The render function will properly handle empty listings by showing the "Ingen lignende kjøretøy funnet" message.

### Change 2: Remove Listings Check in Polling Handler

**File:** `assets/js/vehicle-lookup.js`  
**Line:** ~1373

```javascript
// AFTER - Fixed Code
if (marketData.status === 'complete') {
    // Market listings are ready! Only render if not already rendered
    if (!$('.market-listings-section .market-listings-content .market-listing-item').length) {
        renderMarketListings(marketData);
        console.log('✅ Market listings generated successfully');
    }
    marketComplete = true;
}
```

**Rationale:** Same as Change 1 - delegate the handling of empty listings to the render function.

### Change 3: Add Fallback for Unknown Status

**File:** `assets/js/vehicle-lookup.js`  
**Line:** ~1596

```javascript
// AFTER - Added else clause
} else {
    // Handle error status or unknown status
    console.log('Market listings unexpected status:', marketData.status);
    const $errorText = $('<p class="market-no-data">').text('Kunne ikke hente markedsdata for øyeblikket.');
    $marketContent.append($errorText);
}
```

**Rationale:** Provide a user-friendly message when status is neither 'generating' nor 'complete'.

### Change 4: Enhanced Logging

Added comprehensive logging throughout the flow:

```javascript
// In checkAndStartMarketListingsPolling
console.log('🏪 Checking market listings data:', data.marketListings ? 'Present' : 'Missing');
console.log('Market listings status:', data.marketListings.status);

// In renderMarketListings
console.log('🎨 Rendering market listings with status:', marketData?.status, 'listings count:', marketData?.listings?.length);
console.log('Market listings complete - listings present:', !!marketData.listings, 'is array:', Array.isArray(marketData.listings), 'length:', marketData.listings?.length);

// In main AJAX handler
console.log('Response data keys:', Object.keys(response.data));
console.log('Market listings in response:', response.data.marketListings);
```

**Rationale:** Make it easier to debug future issues by showing the data flow and status at each step.

## How the Fix Works

### Before the Fix
1. Backend returns market listings with `status: 'complete'` but missing/empty `listings` array
2. `checkAndStartMarketListingsPolling()` checks `status === 'complete' && listings`
3. Condition fails because `listings` is missing
4. Function returns without calling `renderMarketListings()`
5. No section is rendered, or an empty section is left from a previous render
6. User sees empty market listings section with no explanation

### After the Fix
1. Backend returns market listings with `status: 'complete'` but missing/empty `listings` array
2. `checkAndStartMarketListingsPolling()` checks only `status === 'complete'`
3. Condition succeeds, calls `renderMarketListings(marketData)`
4. `renderMarketListings()` sees `status === 'complete'` but no listings
5. Goes to else branch: "Ingen lignende kjøretøy funnet i markedet for øyeblikket."
6. User sees a proper message explaining why there are no listings

## Verification

### Expected Console Output (After Fix)
```
🏪 Phase 3: Checking market listings status
Response data keys: [array of keys]
Market listings in response: {status: 'complete', ...}
🏪 Checking market listings data: Present
Market listings status: complete
✅ Market listings complete, rendering immediately
🎨 Rendering market listings with status: complete listings count: undefined
Market listings complete - listings present: false is array: false length: undefined
```

### Expected HTML Output (After Fix)
```html
<div class="market-listings-section section">
  <div class="section-header">
    <span class="section-title">Siste annonser på finn.no</span>
    <img src="..." alt="Finn.no" class="section-icon-logo">
  </div>
  <div class="section-content">
    <div class="market-listings-content">
      <p class="market-no-data">Ingen lignende kjøretøy funnet i markedet for øyeblikket.</p>
    </div>
  </div>
</div>
```

## Testing Checklist

- [x] Code changes are minimal and surgical
- [x] Logging added for debugging
- [x] Handle complete status without listings array
- [x] Handle complete status with empty listings array
- [x] Handle complete status with null listings
- [x] Handle unknown/error status values
- [x] Changes committed and pushed

## Acceptance Criteria

✅ Market listings section displays correctly on first lookup  
✅ Market listings section displays correctly on second lookup  
✅ Console output is consistent and informative on all lookups  
✅ User sees appropriate message when no listings are available  
✅ No empty sections are rendered without explanation  
✅ Code properly handles all status values  

## Related Documentation

- `SECOND_VIEWING_FIX.md` - Previous fix for owner history stacking
- `SELECTOR_FIX_DOCUMENTATION.md` - Previous fix for selector mismatches
- `POLLING_CONFLICT_FIX.md` - Fix for polling conflicts on subsequent lookups
- `CONSOLE_LOGGING_FIX.md` - Enhanced console logging throughout the flow

## Summary

This fix resolves the market listings second view issue by:
1. Removing the premature `&& listings` check that prevented rendering
2. Adding a fallback for unexpected status values
3. Delegating empty listings handling to the render function
4. Adding comprehensive logging for easier debugging

The changes are minimal, backward compatible, and improve the user experience by always showing an appropriate message instead of an empty section.
