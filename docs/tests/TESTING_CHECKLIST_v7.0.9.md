# Testing Checklist for Cloudflare Worker API v7.0.9

## Overview
This checklist helps verify that the WordPress plugin correctly handles the Cloudflare Worker API.

## Prerequisites
- WordPress site with the plugin installed and activated
- Access to WordPress admin dashboard
- Access to browser developer console
- Valid Norwegian registration numbers for testing

## 1. Basic Vehicle Lookup Tests

### ✅ Valid Registration Number
- [ ] Enter a valid Norwegian registration number (e.g., "EO10265")
- [ ] Click "Søk" (Search) button
- [ ] Verify vehicle data is displayed correctly
- [ ] Check console for correlation ID in response
- [ ] Verify no errors are shown

**Expected Result:** Vehicle data displayed with all sections populated.

### ✅ Invalid Registration Number
- [ ] Enter an invalid registration number (e.g., "INVALID123")
- [ ] Click search
- [ ] Verify appropriate error message is shown
- [ ] Check console for structured error with:
  - `error`: Human-readable message
  - `code`: Error code (likely `INVALID_INPUT` or `KJENNEMERKE_UKJENT`)
  - `correlationId`: Request tracking ID
  - `timestamp`: ISO 8601 timestamp

**Expected Result:** Error message "Registreringsnummeret finnes ikke..." or similar.

### ✅ Empty Input
- [ ] Leave input field empty and click search
- [ ] Verify validation error is shown
- [ ] Verify no API call is made (check network tab)

**Expected Result:** Client-side validation prevents submission.

## 2. AI Summary Tests

### ✅ AI Summary Generation Flow
- [ ] Perform a valid vehicle lookup
- [ ] Observe "AI sammendrag genereres..." message
- [ ] Wait for polling to complete
- [ ] Verify AI summary section appears with content
- [ ] Check console for polling responses:
  - Initial: `status: "generating"`
  - Final: `status: "complete"` with `summary` data

**Expected Result:** AI summary appears after 3-10 seconds with analysis content.

### ✅ AI Summary Error Handling
To test this, you may need to use a registration number that causes AI generation to fail, or test during API maintenance.

- [ ] Check console for error response if AI fails:
  - `status: "error"`
  - `error: { message: "...", code: "AI_GENERATION_TIMEOUT" or similar }`
- [ ] Verify error message is shown in AI summary section
- [ ] Verify polling stops after error

**Expected Result:** Error message shown, polling stops, no continuous retries.

## 3. Market Listings Tests

### ✅ Market Listings Generation Flow
- [ ] Perform a valid vehicle lookup
- [ ] Observe "Markedsdata hentes..." message
- [ ] Wait for polling to complete
- [ ] Verify market listings section appears
- [ ] Check console for polling responses:
  - Initial: `status: "generating"`
  - Final: `status: "complete"` with `listings` array

**Expected Result:** Market listings appear after 3-10 seconds with finn.no data.

### ✅ Market Listings Error Handling
- [ ] If market listings fail, verify error message is shown
- [ ] Check console for error response:
  - `status: "error"`
  - `error: { message: "...", code: "FINN_FETCH_FAILED" or similar }`
- [ ] Verify polling stops after error

**Expected Result:** Error message shown, polling stops gracefully.

## 4. Error Response Format Tests

### ✅ Flat Error Structure
- [ ] Trigger an error (invalid registration, rate limit, etc.)
- [ ] Open browser developer console
- [ ] Check network tab for API response
- [ ] Verify error response has flat structure:
```json
{
  "error": "Human-readable error message",
  "code": "ERROR_CODE",
  "timestamp": "2025-10-10T03:14:28.663Z",
  "correlationId": "req-1728848123-abc123"
}
```
- [ ] Verify PHP processes this correctly and returns to frontend

**Expected Result:** Error responses use flat structure, not nested objects.

### ✅ Correlation ID Tracking
- [ ] Perform any lookup (successful or error)
- [ ] Check browser console for correlation ID logs
- [ ] Verify correlation ID is in format: `req-TIMESTAMP-RANDOMSTRING`
- [ ] Check WordPress database `vehicle_lookup_history` table
- [ ] Verify `correlation_id` column is populated

**Expected Result:** Correlation IDs are logged consistently.

## 5. Rate Limiting Tests

### ✅ Rate Limit Response
To test this, you may need to make many rapid requests to trigger rate limiting.

- [ ] Trigger rate limit (429 response)
- [ ] Verify error response includes:
  - `code: "RATE_LIMIT_EXCEEDED"`
  - `error: "For mange forespørsler..."`
- [ ] Check for `X-RateLimit-Limit-Type` header
- [ ] Verify retry countdown is shown to user

**Expected Result:** Rate limit error is handled gracefully with countdown.

## 6. Polling Tests

### ✅ Polling Timing
- [ ] Perform a lookup that triggers AI and market data generation
- [ ] Monitor network tab in developer tools
- [ ] Verify polling requests occur every 2-3 seconds
- [ ] Verify first poll occurs after ~1 second
- [ ] Verify polling stops when both complete or error

**Expected Result:** Polling is efficient and stops when complete.

### ✅ Concurrent Lookups
- [ ] Start a lookup for registration "ABC123"
- [ ] Before it completes, start another lookup for "XYZ789"
- [ ] Verify old polling is cancelled
- [ ] Verify only data for "XYZ789" is shown
- [ ] Check console for "Polling cancelled" messages

**Expected Result:** Old polling is cancelled, no stale data shown.

## 7. Backward Compatibility Tests

### ✅ Legacy Norwegian API Responses
If your API still returns legacy format responses, verify:

- [ ] Legacy `responser` array format is still handled
- [ ] Old error codes in `feilmelding` field are mapped correctly
- [ ] Circuit breaker state is still respected for `SERVICE_UNAVAILABLE`

**Expected Result:** Both old and new formats work correctly.

## 8. Error Code Mapping Tests

### ✅ Error Code Classification
Verify the following error codes are mapped to correct failure types:

| Error Code | Expected Failure Type | Expected User Message |
|------------|----------------------|----------------------|
| `INVALID_INPUT` | `invalid_plate` | Validation error message |
| `KJENNEMERKE_UKJENT` | `invalid_plate` | Registration not found |
| `RATE_LIMIT_EXCEEDED` | `rate_limit` | Rate limit message with retry |
| `SERVICE_UNAVAILABLE` | `http_error` | Service unavailable message |
| `TIMEOUT` | `http_error` | Timeout message with retry |
| `AI_GENERATION_TIMEOUT` | `http_error` | AI unavailable message |
| `FINN_FETCH_FAILED` | `http_error` | Market data unavailable |

**Expected Result:** All error codes are handled with appropriate messages.

## 9. Health Check Tests

### ✅ Health Endpoint (Admin Only)
- [ ] Log in to WordPress admin
- [ ] Navigate to plugin settings/dashboard
- [ ] Click "Test API Connection" or similar button
- [ ] Verify health check response:
```json
{
  "status": "healthy",
  "timestamp": "...",
  "correlationId": "...",
  "service": "beepi-svv-worker",
  "version": "..."
}
```
- [ ] Verify success message is shown

**Expected Result:** Health check returns worker status and version.

## 10. Console Logging Tests

### ✅ Structured Logging
- [ ] Perform various operations (lookup, polling, errors)
- [ ] Check browser console for structured logs
- [ ] Verify logs include:
  - Correlation IDs
  - Error codes
  - Timestamps
  - Clear operation descriptions
- [ ] Verify log groups for errors

**Expected Result:** Console logs are clear and useful for debugging.

## 11. Database Tests

### ✅ History Table
- [ ] Perform several lookups (successful and errors)
- [ ] Check WordPress database table `vehicle_lookup_history`
- [ ] Verify new columns are populated:
  - `error_code`: Error code from API
  - `correlation_id`: Request tracking ID
- [ ] Verify both success and error lookups are logged

**Expected Result:** All requests are logged with correlation IDs and error codes.

## 12. Performance Tests

### ✅ Response Times
- [ ] Perform multiple lookups
- [ ] Check browser network tab for timing
- [ ] Verify response times are reasonable:
  - Vehicle lookup: < 3 seconds
  - AI summary: 3-10 seconds
  - Market listings: 3-10 seconds
- [ ] Verify no memory leaks from polling

**Expected Result:** Performance is acceptable, no memory leaks.

## Summary

After completing all tests:

- [ ] All basic lookups work correctly
- [ ] Error handling works with new flat structure
- [ ] AI summary polling works with response envelope
- [ ] Market listings polling works with response envelope
- [ ] Correlation IDs are tracked throughout
- [ ] Error codes are mapped correctly
- [ ] Backward compatibility is maintained
- [ ] Performance is acceptable

## Notes

Record any issues or observations:
- 
- 
- 

## Test Environment

- WordPress Version: _____
- Plugin Version: 7.0.9
- PHP Version: _____
- Browser: _____
- Date Tested: _____
- Tester Name: _____
