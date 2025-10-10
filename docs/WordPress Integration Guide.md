# WordPress Integration Guide

Quick reference for integrating the Beepi-SVV Norwegian Vehicle Registry API into WordPress.

**Base URL:** `https://beepi-svv.zhaiden.workers.dev`

## Quick Reference

| Topic | What You'll Find |
|-------|------------------|
| [Endpoints](#endpoints) | All API endpoints with request/response formats |
| [Response Envelope](#response-envelope-structure) | Common response structure and status field usage |
| [Error Codes](#error-codes) | Complete error code reference with HTTP status codes |
| [Rate Limiting](#rate-limiting) | Rate limits and handling strategies |
| [Polling Pattern](#polling-pattern) | How to poll for AI summaries and market listings |
| [Registration Format](#registration-number-format) | Valid Norwegian plate formats and validation |
| [Caching](#caching-strategy) | Cache TTLs and WordPress caching implementation |
| [Complete Example](#complete-integration-example) | Full PHP/WordPress integration code |
| [Testing Checklist](#testing-checklist) | What to test before going live |

---

## Endpoints

### 1. POST /lookup
Main vehicle data endpoint with optional AI summary and automatic market listings.

**Request:**
```json
{
  "registrationNumber": "EO10265",
  "includeSummary": true
}
```

**Response (200 OK):**
```json
{
  "responser": [/* Norwegian registry data */],
  "correlationId": "req-1728848123-abc123",
  "cached": false,
  "aiSummary": {
    "status": "generating",
    "startedAt": "2025-10-10T03:14:28.663Z",
    "estimatedTime": 5000,
    "pollUrl": "/ai-summary/EO10265"
  },
  "marketListings": {
    "status": "generating",
    "startedAt": "2025-10-10T03:14:28.663Z",
    "progress": "0%",
    "pollUrl": "/market-listings/EO10265"
  }
}
```

---

### 2. GET /ai-summary/{registrationNumber}
Poll endpoint for AI summary status.

**Response (generating):**
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

**Response (complete):**
```json
{
  "status": "complete",
  "completedAt": "2025-10-10T03:14:33.663Z",
  "cached": true,
  "summary": {
    "summary": "This KTM 450 EXC-F is a high-performance enduro motorcycle...",
    "highlights": [
      "2020 model year",
      "450cc single-cylinder 4-stroke engine",
      "Known for exceptional off-road capability"
    ],
    "recommendation": "Excellent choice for serious off-road enthusiasts...",
    "marketInsights": "KTM's 450 EXC-F is highly sought after in Norway...",
    "redFlags": []
  },
  "registrationNumber": "EO10265",
  "correlationId": "req-1728848123-abc123"
}
```

**Response (error):**
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

---

### 3. GET /market-listings/{registrationNumber}
Poll endpoint for market data from finn.no.

**Response (generating):**
```json
{
  "status": "generating",
  "startedAt": "2025-10-10T03:14:28.663Z",
  "progress": "50%",
  "pollUrl": "/market-listings/EO10265",
  "registrationNumber": "EO10265",
  "correlationId": "req-1728848123-abc123"
}
```

**Response (complete):**
```json
{
  "status": "complete",
  "completedAt": "2025-10-10T03:14:31.663Z",
  "cached": false,
  "searchCriteria": "KTM 450 EXC-F",
  "searchUrl": "https://www.finn.no/mobility/search/mc?q=KTM+450+EXC-F",
  "listings": [
    {
      "title": "KTM 450 EXC-F 2024",
      "price": "185,000",
      "year": "2024",
      "mileage": "1,200",
      "url": "https://www.finn.no/car/used/ad/123456"
    }
  ],
  "marketSummary": {
    "averagePrice": "175,000",
    "priceRange": "165,000-185,000",
    "averageMileage": "2,500",
    "totalFound": 3
  },
  "fetchedAt": "2025-10-10T03:14:31.663Z",
  "registrationNumber": "EO10265",
  "correlationId": "req-1728848123-abc123"
}
```

**Response (error):**
```json
{
  "status": "error",
  "completedAt": "2025-10-10T03:14:33.663Z",
  "error": {
    "message": "Failed to fetch market data",
    "code": "FINN_FETCH_FAILED"
  },
  "registrationNumber": "EO10265",
  "correlationId": "req-1728848123-abc123"
}
```

---

### 4. GET /health
System health check endpoint.

**Response (200 OK):**
```json
{
  "status": "healthy",
  "timestamp": "2025-10-10T03:14:28.663Z",
  "correlationId": "req-1728848123-abc123",
  "service": "beepi-svv-worker",
  "version": "1.1.2"
}
```

**Response (503 Service Unavailable):**
```json
{
  "status": "degraded",
  "timestamp": "2025-10-10T03:14:28.663Z",
  "correlationId": "req-1728848123-abc123",
  "service": "beepi-svv-worker",
  "version": "1.1.2"
}
```

---

## Response Envelope Structure

All async API responses (AI summaries, market listings) use a consistent envelope with a `status` field:

### States

| State | Description | Fields |
|-------|-------------|--------|
| `generating` | Resource is being created | `status`, `startedAt`, `estimatedTime`, `pollUrl` |
| `complete` | Resource is ready | `status`, `completedAt`, `cached`, data fields |
| `error` | Generation failed | `status`, `completedAt`, `error` |

### Common Fields

| Field | Type | Present In | Description |
|-------|------|------------|-------------|
| `status` | string | All | Current state: `"generating"`, `"complete"`, or `"error"` |
| `registrationNumber` | string | All | Vehicle registration number |
| `correlationId` | string | All | Request correlation ID for tracing |
| `startedAt` | ISO 8601 | generating | When generation started |
| `completedAt` | ISO 8601 | complete, error | When generation completed |
| `cached` | boolean | complete | Whether data came from cache |
| `pollUrl` | string | All | URL to check status |
| `error` | object | error | Error details with `message` and `code` |

### WordPress Implementation

**Check response state:**
```php
$response = json_decode($api_response, true);

switch ($response['status']) {
    case 'generating':
        // Show loading state, poll using $response['pollUrl']
        break;
    case 'complete':
        // Display data: $response['summary'] or $response['listings']
        break;
    case 'error':
        // Handle error: $response['error']['message']
        break;
}
```

---

## Error Codes

### General Errors

| Code | HTTP | Description |
|------|------|-------------|
| `INVALID_INPUT` | 400 | Invalid registration number format |
| `NOT_FOUND` | 404 | Endpoint or resource not found |
| `FORBIDDEN` | 403 | Origin not allowed or access denied |
| `RATE_LIMIT_EXCEEDED` | 429 | Rate limit exceeded |
| `TIMEOUT` | 408 | Request timeout |
| `SERVICE_UNAVAILABLE` | 503 | Service temporarily unavailable |
| `INTERNAL_ERROR` | 500 | Internal server error |

### AI Summary Errors

| Code | Description |
|------|-------------|
| `AI_GENERATION_TIMEOUT` | AI generation exceeded 30s timeout |
| `AI_INVALID_JSON` | Invalid JSON response from OpenAI |
| `AI_INVALID_STRUCTURE` | Missing required fields in AI response |
| `AI_GENERATION_FAILED` | General AI generation failure |
| `EXTERNAL_API_ERROR` | OpenAI API returned an error |

### Market Listing Errors

| Code | Description |
|------|-------------|
| `FINN_HTTP_ERROR` | HTTP error from finn.no |
| `FINN_FETCH_FAILED` | Failed to fetch market data |
| `MARKET_SEARCH_FAILED` | General market search failure |

### Vegvesen Registry Errors

| Code | HTTP | Description |
|------|------|-------------|
| `KJENNEMERKE_UKJENT` | 404 | Registration number not found in registry |
| `OPPLYSNINGER_UTILGJENGELIG` | 403 | Vehicle information not available (privacy) |
| `UGYLDIG_KJENNEMERKE` | 400 | Invalid registration number format |
| `PKK_INFORMASJON_IKKE_TILGJENGELIG` | 503 | Inspection system temporarily down |
| `INGEN_AKTIVE_GODKJENNINGER` | 404 | No active vehicle approvals |

### Error Response Format

All errors return:
```json
{
  "error": "Human-readable error message",
  "code": "ERROR_CODE",
  "timestamp": "2025-10-10T03:14:28.663Z",
  "correlationId": "req-1728848123-abc123"
}
```

---

## Rate Limiting

### Limits

| Type | Limit | Window | Reset |
|------|-------|--------|-------|
| Per IP (Hourly) | 100 requests | 1 hour | Rolling |
| Per IP (Burst) | 20 requests | 10 minutes | Rolling |
| Vegvesen Daily Quota | 5000 requests | 24 hours | Midnight UTC |

### Rate Limit Headers

When rate limited (HTTP 429):
```json
{
  "error": "Rate limit exceeded: Too many requests from this IP...",
  "code": "RATE_LIMIT_EXCEEDED",
  "timestamp": "2025-10-10T03:14:28.663Z",
  "correlationId": "req-1728848123-abc123"
}
```

Response headers include:
- `X-RateLimit-Limit-Type`: Type of limit hit (`burst_limit`, `hourly_limit`, `vegvesen_quota_exhausted`)

### Handling Rate Limits

```php
if ($response->getStatusCode() === 429) {
    $body = json_decode($response->getBody(), true);
    
    // Log and show user-friendly message
    error_log('Rate limited: ' . $body['error']);
    
    // Implement exponential backoff before retry
}
```

---

## Polling Pattern

For AI summaries and market listings, implement polling:

### Basic Pattern

1. Call `/lookup` with `includeSummary: true`
2. Check `aiSummary.status` and `marketListings.status`
3. If `"generating"`, poll using `pollUrl` every 2-3 seconds
4. Stop when `status` is `"complete"` or `"error"`
5. Maximum 10-15 poll attempts (30-45 seconds total)

### WordPress Example

```php
function poll_for_ai_summary($reg_number, $max_attempts = 10) {
    $poll_url = "https://beepi-svv.zhaiden.workers.dev/ai-summary/{$reg_number}";
    
    for ($i = 0; $i < $max_attempts; $i++) {
        $response = wp_remote_get($poll_url);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] === 'complete') {
            return $data['summary'];
        }
        
        if ($data['status'] === 'error') {
            error_log('AI generation failed: ' . $data['error']['message']);
            return null;
        }
        
        // Still generating, wait before next poll
        sleep(2);
    }
    
    // Timeout after all attempts
    error_log('AI generation timed out after ' . $max_attempts . ' attempts');
    return null;
}
```

### Polling Best Practices

- **Initial delay:** Wait 2-3 seconds before first poll
- **Poll interval:** 2-3 seconds between polls
- **Max attempts:** 10-15 attempts (AI: ~5s, Market: ~3s typical)
- **Error handling:** Stop polling on `"error"` status
- **Caching:** If `cached: true`, no need to poll again
- **User feedback:** Show progress indicator during polling

---

## Registration Number Format

Norwegian registration numbers follow strict formats:

### Valid Formats
- **Standard:** 2 letters + 4-5 digits (e.g., `EO10265`, `AB12345`)
- **Case insensitive:** `eo10265` → `EO10265`
- **Space tolerant:** `EO 10265` → `EO10265`

### Validation Pattern
```
/^[A-Z]{2}[0-9]{4,5}$/
```

### WordPress Validation
```php
function validate_norwegian_plate($plate) {
    // Normalize: uppercase, remove spaces
    $normalized = strtoupper(str_replace(' ', '', $plate));
    
    // Validate format
    if (!preg_match('/^[A-Z]{2}[0-9]{4,5}$/', $normalized)) {
        return false;
    }
    
    return $normalized;
}
```

---

## Caching Strategy

### Cache TTLs
- **Vehicle Data:** 1 hour
- **AI Summaries:** 24 hours
- **Market Listings:** 3 hours
- **Vehicle Classifications:** 24 hours

### Cache Indicators
Response includes `cached: true/false` field:
```json
{
  "cached": true,
  "completedAt": "2025-10-10T03:14:28.663Z"
}
```

### WordPress Caching
```php
// Check transient cache first
$cache_key = 'beepi_vehicle_' . $reg_number;
$cached_data = get_transient($cache_key);

if ($cached_data !== false) {
    return $cached_data;
}

// Make API call
$response = wp_remote_post('https://beepi-svv.zhaiden.workers.dev/lookup', [
    'body' => json_encode([
        'registrationNumber' => $reg_number,
        'includeSummary' => true
    ]),
    'headers' => ['Content-Type' => 'application/json']
]);

$data = json_decode(wp_remote_retrieve_body($response), true);

// Cache for 1 hour (WordPress)
set_transient($cache_key, $data, HOUR_IN_SECONDS);

return $data;
```

---

## CORS Configuration

API supports cross-origin requests with these headers:
- `Access-Control-Allow-Origin`: Request origin or `*`
- `Access-Control-Allow-Methods`: `GET, POST, OPTIONS`
- `Access-Control-Allow-Headers`: `Content-Type, Origin`

WordPress makes same-origin requests, so CORS is handled automatically.

---

## Complete Integration Example

```php
<?php
// WordPress function to lookup vehicle with AI summary
function beepi_lookup_vehicle($registration_number) {
    // Validate and normalize
    $reg_number = validate_norwegian_plate($registration_number);
    if (!$reg_number) {
        return ['error' => 'Invalid registration number'];
    }
    
    // Check cache
    $cache_key = 'beepi_vehicle_' . $reg_number;
    $cached = get_transient($cache_key);
    if ($cached) return $cached;
    
    // Call lookup endpoint
    $response = wp_remote_post('https://beepi-svv.zhaiden.workers.dev/lookup', [
        'body' => json_encode([
            'registrationNumber' => $reg_number,
            'includeSummary' => true
        ]),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    // Handle AI summary polling
    if (isset($data['aiSummary']) && $data['aiSummary']['status'] === 'generating') {
        $ai_summary = poll_for_ai_summary($reg_number);
        if ($ai_summary) {
            $data['aiSummary'] = $ai_summary;
        }
    }
    
    // Handle market listings polling
    if (isset($data['marketListings']) && $data['marketListings']['status'] === 'generating') {
        $market_data = poll_for_market_listings($reg_number);
        if ($market_data) {
            $data['marketListings'] = $market_data;
        }
    }
    
    // Cache complete result
    set_transient($cache_key, $data, HOUR_IN_SECONDS);
    
    return $data;
}

function poll_for_ai_summary($reg_number, $max_attempts = 10) {
    $poll_url = "https://beepi-svv.zhaiden.workers.dev/ai-summary/{$reg_number}";
    
    sleep(2); // Initial delay
    
    for ($i = 0; $i < $max_attempts; $i++) {
        $response = wp_remote_get($poll_url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] === 'complete') {
            return [
                'status' => 'complete',
                'completedAt' => $data['completedAt'],
                'cached' => $data['cached'],
                'summary' => $data['summary']
            ];
        }
        
        if ($data['status'] === 'error') {
            error_log('AI generation failed: ' . $data['error']['message']);
            return null;
        }
        
        sleep(2);
    }
    
    return null;
}

function poll_for_market_listings($reg_number, $max_attempts = 10) {
    $poll_url = "https://beepi-svv.zhaiden.workers.dev/market-listings/{$reg_number}";
    
    sleep(2); // Initial delay
    
    for ($i = 0; $i < $max_attempts; $i++) {
        $response = wp_remote_get($poll_url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] === 'complete') {
            return [
                'status' => 'complete',
                'completedAt' => $data['completedAt'],
                'cached' => $data['cached'],
                'searchCriteria' => $data['searchCriteria'],
                'searchUrl' => $data['searchUrl'],
                'listings' => $data['listings'],
                'marketSummary' => $data['marketSummary']
            ];
        }
        
        if ($data['status'] === 'error') {
            error_log('Market search failed: ' . $data['error']['message']);
            return null;
        }
        
        sleep(2);
    }
    
    return null;
}
?>
```

---

## Testing Checklist

- [ ] Valid registration number lookup (`EO10265`)
- [ ] Invalid format handling (`123`, `TOOLONG123`)
- [ ] AI summary polling (2-5 seconds)
- [ ] Market listings polling (3-5 seconds)
- [ ] Rate limit handling (429 response)
- [ ] Error code handling (404, 503, etc.)
- [ ] Caching behavior (subsequent requests)
- [ ] Timeout handling (30s+ requests)
- [ ] Empty/null data handling
- [ ] Network error recovery

---

## Support

For issues or questions:
1. Check `correlationId` in responses for debugging
2. Review error `code` and `message` fields
3. Verify rate limits haven't been exceeded
4. Check `/health` endpoint for system status

**Repository:** https://github.com/zulfi-git/BeepiWorker
