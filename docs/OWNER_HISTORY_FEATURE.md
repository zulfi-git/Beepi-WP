# Owner History Feature Documentation

## Overview

This feature adds support for retrieving and displaying full owner history (eierhistorikk) for Norwegian vehicle registrations.

## API Parameters

The `/lookup` endpoint now supports the following additional parameters:

### `includeOwnerHistory` (boolean, optional)

When set to `true`, requests the full ownership history timeline for the vehicle.

**Example Request:**
```json
{
  "registrationNumber": "BV12345",
  "includeOwnerHistory": true
}
```

**Example Response:**
```json
{
  "responser": [
    {
      "kjoretoydata": {
        "kjoretoyId": { "kjennemerke": "BV12345" },
        "eierskap": {
          "eier": { "navn": "Current Owner Name" }
        },
        "eierhistorikk": [
          {
            "eier": { 
              "person": {
                "fornavn": "Previous",
                "etternavn": "Owner 1"
              },
              "adresse": {
                "adresselinje1": "Storgata 1",
                "postnummer": "0101",
                "poststed": "Oslo"
              }
            },
            "registrertDato": "2020-06-15T00:00:00Z"
          },
          {
            "eier": { 
              "person": {
                "fornavn": "Previous",
                "etternavn": "Owner 2"
              },
              "adresse": {
                "adresselinje1": "Kongens gate 23",
                "postnummer": "7011",
                "poststed": "Trondheim"
              }
            },
            "registrertDato": "2018-03-22T00:00:00Z"
          }
        ]
      }
    }
  ],
  "correlationId": "req-12345",
  "cached": false
}
```

### `dtg` (string, optional)

Date/Time Group for historical owner lookup. Must be in ISO 8601 format.

When provided, returns owner information as it was at the specified date/time.

**Example Request:**
```json
{
  "registrationNumber": "BV12345",
  "dtg": "2023-01-15T10:00:00Z"
}
```

**Example Response:**
```json
{
  "responser": [
    {
      "kjoretoydata": {
        "kjoretoyId": { "kjennemerke": "BV12345" },
        "eierskap": {
          "eier": { "navn": "Previous Owner Name" }
        }
      }
    }
  ],
  "correlationId": "req-12345",
  "cached": false
}
```

## Frontend Implementation

### AJAX Request

The JavaScript code automatically includes `includeOwnerHistory: true` in the AJAX request:

```javascript
const requestData = {
    action: 'vehicle_lookup',
    nonce: vehicleLookupAjax.nonce,
    regNumber: regNumber,
    includeSummary: true,
    includeOwnerHistory: true  // Request full owner history timeline
};
```

### Display Logic

The owner history is displayed in the results section using the `populateOwnerHistoryTable()` function:

1. **With Access**: If the user has purchased access (or is on the order confirmation page), the real owner history data is displayed with:
   - Registration date
   - Owner name
   - Owner address

2. **Without Access**: If the user doesn't have access, mock data is shown with a blur effect and a premium purchase overlay.

### Data Structure

The owner history data comes from the API response at:
```
vehicleData.eierhistorikk[]
```

Each entry contains:
- `eier.person.fornavn` - First name
- `eier.person.etternavn` - Last name
- `eier.adresse.adresselinje1` - Address line 1
- `eier.adresse.postnummer` - Postal code
- `eier.adresse.poststed` - City
- `registrertDato` - Registration date (ISO 8601 format)

## Code Changes

### PHP Changes

1. **class-vehicle-lookup-api.php**: Updated `lookup()` method to accept `$dtg` and `$includeOwnerHistory` parameters
2. **class-vehicle-lookup.php**: Updated `handle_lookup()` to read and pass these parameters from AJAX request

### JavaScript Changes

1. **vehicle-lookup.js**: 
   - Added `includeOwnerHistory: true` to AJAX request
   - Updated `populateOwnerHistoryTable()` to accept `vehicleData` parameter
   - Added logic to display real owner history when available and user has access

## Access Control

Owner history data is only displayed to users who:
- Have purchased access (checked via `checkOwnerAccessToken()`)
- Are on the order confirmation page

Without access, users see blurred mock data with a premium purchase prompt.

## Testing

To test this feature:

1. Make a lookup request with `includeOwnerHistory: true`
2. Verify the API receives the parameter
3. Check that the response includes `eierhistorikk` array
4. Verify the frontend displays the data correctly based on user access level

## Notes

- The `dtg` parameter is optional and can be used independently of `includeOwnerHistory`
- When `dtg` is provided, it returns a historical snapshot of the vehicle data at that specific date/time
- The `includeOwnerHistory` parameter requests the full ownership timeline regardless of the current or historical date
