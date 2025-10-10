# Cloudflare Worker API Revamp - Update Summary

## Overview
Updated WordPress plugin to support the revamped Cloudflare Worker API standard as documented in `docs/WordPress Integration Guide.md`.

## Changes Made

### 1. Error Response Format Update
**Previous format (nested):**
```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Error message",
    "correlationId": "req-xxx"
  }
}
```

**New format (flat):**
```json
{
  "error": "Error message",
  "code": "ERROR_CODE",
  "timestamp": "2025-10-10T03:14:28.663Z",
  "correlationId": "req-1728848123-abc123"
}
```

### 2. Updated Files

#### `includes/class-vehicle-lookup-api.php`
- **`process_response()`**: Updated to handle flat error structure with `error`, `code`, `timestamp`, and `correlationId` at root level
- **`process_ai_summary_response()`**: 
  - Added handling for flat error responses
  - Added support for `status: "error"` with nested error object in response envelope
  - Enhanced error handling for AI-specific error codes
- **`process_market_listings_response()`**:
  - Added handling for flat error responses
  - Added support for `status: "error"` with nested error object in response envelope
  - Enhanced error handling for market listing-specific error codes
- **`map_error_code_to_failure_type()`**: 
  - Added AI Summary specific error codes: `AI_GENERATION_TIMEOUT`, `AI_INVALID_JSON`, `AI_INVALID_STRUCTURE`, `AI_GENERATION_FAILED`, `EXTERNAL_API_ERROR`
  - Added Market Listing specific error codes: `FINN_HTTP_ERROR`, `FINN_FETCH_FAILED`, `MARKET_SEARCH_FAILED`
  - Added Vegvesen Registry specific error codes: `PKK_INFORMASJON_IKKE_TILGJENGELIG`, `OPPLYSNINGER_UTILGJENGELIG`, `INGEN_AKTIVE_GODKJENNINGER`

#### `vehicle-lookup.php`
- Updated version from 7.0.7 to 7.0.8
- Updated `VEHICLE_LOOKUP_VERSION` constant

#### `CHANGELOG.md`
- Added version 7.0.8 entry
- Documented all API changes and improvements

### 3. Response Envelope Support

All async endpoints (`/ai-summary`, `/market-listings`) now properly support the response envelope pattern:

**Generating state:**
```json
{
  "status": "generating",
  "startedAt": "2025-10-10T03:14:28.663Z",
  "estimatedTime": 5000,
  "pollUrl": "/ai-summary/EO10265",
  "registrationNumber": "EO10265",
  "correlationId": "req-1728848123-abc123"
}
```

**Complete state:**
```json
{
  "status": "complete",
  "completedAt": "2025-10-10T03:14:33.663Z",
  "cached": true,
  "summary": { /* data */ },
  "registrationNumber": "EO10265",
  "correlationId": "req-1728848123-abc123"
}
```

**Error state:**
```json
{
  "status": "error",
  "completedAt": "2025-10-10T03:14:58.663Z",
  "error": {
    "message": "AI generation timed out after 30 seconds",
    "code": "AI_GENERATION_TIMEOUT"
  },
  "registrationNumber": "EO10265",
  "correlationId": "req-1728848123-abc123"
}
```

### 4. Backward Compatibility

The updated code maintains backward compatibility:
- **Handles legacy nested error format**: `{"error": {"code": "...", "message": "...", "correlationId": "..."}}` from older workers
- Still handles legacy Norwegian API error responses in `responser` array
- Still validates correlation ID format
- Still supports circuit breaker state awareness for `SERVICE_UNAVAILABLE`
- Falls back gracefully for responses without `status` field

**Note**: All three response processing methods (`process_response()`, `process_ai_summary_response()`, and `process_market_listings_response()`) now detect and properly handle both the new flat error format and the legacy nested error format, preventing older worker errors from being treated as successful responses.

### 5. Frontend JavaScript

**No changes required** - The JavaScript code in `assets/js/vehicle-lookup.js` already properly handles:
- `status: 'generating'` - Shows loading states
- `status: 'complete'` - Renders the data
- `status: 'error'` - Shows error messages and stops polling

The PHP changes ensure that the JavaScript receives data in the expected format.

## Testing Recommendations

1. **Basic vehicle lookup**: Test with valid Norwegian registration numbers
2. **AI summary polling**: Verify generation, completion, and error states
3. **Market listings polling**: Verify generation, completion, and error states
4. **Error handling**: Test with invalid registration numbers and simulate API errors
5. **Rate limiting**: Verify 429 responses are handled correctly
6. **Correlation ID tracking**: Verify correlation IDs are logged for all requests

## API Documentation

All changes align with the specification in `docs/WordPress Integration Guide.md`.
