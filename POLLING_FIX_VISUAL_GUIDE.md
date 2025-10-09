# Second View Polling Conflict - Visual Guide

## The Problem (Before Fix)

```
User Timeline:
════════════════════════════════════════════════════════════════

t=0s    Search "CO1180"
        │
        ├─► AJAX Request → Success
        │
        └─► Start Polling (Timeout #1)
            │
            ├─ setTimeout(poll, 1000ms)
            │
t=1s    └─► Poll #1: Status=generating
                │
                ├─ setTimeout(poll, 2000ms)
                │
t=3s            │ 🔴 User searches "EV12345"
                │   │
                │   ├─► resetFormState() ❌ (doesn't cancel timeout)
                │   │   └─ Clear UI ✓
                │   │
                │   ├─► AJAX Request → Success
                │   │
                │   └─► Start NEW Polling (Timeout #2)
                │       └─ setTimeout(poll, 1000ms)
                │
                └─► 💥 Poll #2 executes (OLD TIMEOUT!)
                    └─ Try to render CO1180 data ❌
                        └─ But UI was cleared for EV12345 ❌
                            └─ Race condition with new polling ❌

t=4s            └─► Poll #1 for EV12345
                    ├─ Try to render EV12345 data
                    └─ But CO1180 data might have overwritten sections ❌

RESULT: ❌ Inconsistent display, missing sections, wrong data
```

## The Solution (After Fix)

```
User Timeline:
════════════════════════════════════════════════════════════════

t=0s    Search "CO1180"
        │
        ├─► currentLookupRegNumber = "CO1180" ✅
        │
        ├─► AJAX Request → Success
        │
        └─► Start Polling
            │
            ├─ activePollingTimeoutId = setTimeout(poll, 1000ms) ✅
            │
t=1s    └─► Poll #1: Check lookup still "CO1180" ✅
                │
                ├─ Status=generating
                │
                ├─ activePollingTimeoutId = setTimeout(poll, 2000ms) ✅
                │
t=3s            │ ✅ User searches "EV12345"
                │   │
                │   ├─► currentLookupRegNumber = "EV12345" ✅
                │   │
                │   ├─► resetFormState()
                │   │   ├─ clearTimeout(activePollingTimeoutId) ✅
                │   │   │  └─ Timeout #1 CANCELLED ✅
                │   │   └─ Clear UI ✓
                │   │
                │   ├─► AJAX Request → Success
                │   │
                │   └─► Start NEW Polling
                │       └─ activePollingTimeoutId = setTimeout(poll, 1000ms) ✅
                │
                └─► ⚠️  Poll #2 callback executes (timeout was cancelled,
                        but if it somehow runs...)
                        │
                        └─► Check: "CO1180" !== "EV12345" ✅
                            └─► Log: "🛑 Polling cancelled..." ✅
                                └─► return early ✅
                                    └─ Does NOT render ✅

t=4s            └─► Poll #1 for EV12345
                    ├─ Check: "EV12345" === currentLookupRegNumber ✅
                    ├─ Status=complete
                    └─ Render EV12345 data ✅

RESULT: ✅ Clean display, correct data, no conflicts
```

## Code Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Form Submit Handler                       │
│                                                               │
│  1. Normalize registration number                            │
│  2. currentLookupRegNumber = regNumber ◄── NEW ✅           │
│  3. resetFormState()                                          │
│     ├─ Check activePollingTimeoutId ◄── NEW ✅             │
│     ├─ clearTimeout() if exists ◄── NEW ✅                 │
│     └─ Clear UI elements                                     │
│  4. Make AJAX request                                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    AJAX Success Handler                       │
│                                                               │
│  1. Process vehicle data                                      │
│  2. Display results                                           │
│  3. Check if AI/Market data needs polling                     │
│     └─ Start polling if needed                               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              startAiSummaryPolling(regNumber)                 │
│                                                               │
│  Check #1: regNumber === currentLookupRegNumber? ◄── NEW ✅ │
│     └─ If NO: Log "🛑 Stopping polling" and return          │
│                                                               │
│  activePollingTimeoutId = setTimeout(() => { ◄── NEW ✅     │
│                                                               │
│    Check #2: regNumber === currentLookupRegNumber? ◄── NEW ✅│
│       └─ If NO: Log "🛑 Polling cancelled" and return       │
│                                                               │
│    Make AJAX poll request                                     │
│       │                                                       │
│       └─► Success Handler                                    │
│           │                                                   │
│           Check #3: regNumber === current? ◄── NEW ✅       │
│              └─ If NO: Log "🛑 Ignoring response" and return│
│                                                               │
│           Process AI/Market data                              │
│           │                                                   │
│           ├─ Status = complete                               │
│           │  └─ Render data ✅                               │
│           │                                                   │
│           └─ Status = generating                             │
│              │                                                │
│              Check #4: Still current? ◄── NEW ✅            │
│              │                                                │
│              └─ Schedule next poll (recursive)               │
│                 └─ Goes back to Check #1                     │
│       │                                                       │
│       └─► Error Handler                                      │
│           │                                                   │
│           Check #5: regNumber === current? ◄── NEW ✅       │
│              └─ If NO: Log "🛑 Not retrying" and return     │
│              └─ If YES: Schedule retry with backoff          │
│  })                                                           │
└─────────────────────────────────────────────────────────────┘
```

## State Management

```
┌───────────────────────────────────────────────────────┐
│          Module-Level State Variables                  │
├───────────────────────────────────────────────────────┤
│                                                         │
│  let activePollingTimeoutId = null;                    │
│  ├─ Stores: ID of current setTimeout                   │
│  ├─ Set by: startAiSummaryPolling()                    │
│  ├─ Cleared by: resetFormState()                       │
│  └─ Used for: clearTimeout() when new lookup starts    │
│                                                         │
│  let currentLookupRegNumber = null;                    │
│  ├─ Stores: Registration number being looked up        │
│  ├─ Set by: Form submit handler                        │
│  ├─ Checked by: All polling functions                  │
│  └─ Used for: Detecting stale polling callbacks        │
│                                                         │
└───────────────────────────────────────────────────────┘
```

## Multi-Layer Defense

```
Layer 1: Proactive Cancellation
┌────────────────────────────────┐
│  resetFormState()              │
│  └─ clearTimeout() ✅          │
└────────────────────────────────┘
        Prevents timeout from firing

Layer 2: Function Entry Check
┌────────────────────────────────┐
│  startAiSummaryPolling()       │
│  └─ Check current lookup ✅    │
└────────────────────────────────┘
        Catches late calls

Layer 3: Callback Entry Check
┌────────────────────────────────┐
│  setTimeout(() => {            │
│    └─ Check current lookup ✅  │
│  })                            │
└────────────────────────────────┘
        Double-checks before AJAX

Layer 4: Response Handler Check
┌────────────────────────────────┐
│  success: function(response) { │
│    └─ Check current lookup ✅  │
│  }                             │
└────────────────────────────────┘
        Ignores stale responses

Layer 5: Retry Prevention Check
┌────────────────────────────────┐
│  Before scheduling retry:      │
│    └─ Check current lookup ✅  │
└────────────────────────────────┘
        Stops retry chains

Layer 6: Error Handler Check
┌────────────────────────────────┐
│  error: function(xhr) {        │
│    └─ Check current lookup ✅  │
│  }                             │
└────────────────────────────────┘
        Prevents error retries
```

## Console Output Examples

### Normal Flow (Single Lookup)
```
🔍 Vehicle lookup initiated for: CO1180
🧹 Clearing previous vehicle data...
✅ Previous vehicle data cleared
📡 AJAX response received
Success: true
✅ Valid vehicle data received
🚀 Phase 1: Processing vehicle data immediately
📊 Processing vehicle data for: CO1180
💾 Checking cache status...
Cache info - isCached: false cacheTime: 2025-10-09T02:28:52+02:00
✅ Cache notice displayed
📝 Rendering basic info...
✅ Results displayed
🎉 Vehicle lookup complete for: CO1180
🤖 Phase 2: Checking AI summary status
Polling response received: {success: true, data: {...}}
AI Summary data: {status: 'generating', progress: '20%'}
Continuing polling - AI: generating Market: done
✅ AI summary generated successfully
✅ Polling complete - both AI and market data finished
```

### Second Lookup (Polling Cancelled)
```
🔍 Vehicle lookup initiated for: EV12345
🧹 Clearing previous vehicle data...
🛑 Cancelled active polling from previous lookup ◄── NEW!
✅ Previous vehicle data cleared
...
🤖 Phase 2: Checking AI summary status
🛑 Stopping polling for CO1180 - new lookup in progress for EV12345 ◄── NEW!
Polling response received: {success: true, data: {...}}
🛑 Ignoring polling response for CO1180 - current lookup is EV12345 ◄── NEW!
...
```

## Edge Cases Covered

```
Case 1: Rapid Consecutive Lookups
────────────────────────────────────
Search A → Search B → Search C (within 2 seconds)
✅ Each search cancels previous
✅ Only C displays
✅ No timeouts leak

Case 2: Same Plate Twice
────────────────────────────────────
Search CO1180 → Wait 3s → Search CO1180 again
✅ First polling cancelled
✅ Second polling starts fresh
✅ No duplicate rendering

Case 3: AJAX In-Flight
────────────────────────────────────
Search A → AJAX sent → Search B → AJAX response arrives
✅ Response for A ignored
✅ Only B data rendered
✅ No flashing of A data

Case 4: Retry Chain
────────────────────────────────────
Search A → Error → Retry scheduled → Search B
✅ Retry check fails
✅ No retry for A
✅ Only B polling active

Case 5: Exponential Backoff
────────────────────────────────────
Search A → Error → Retry with 2s delay → Error → Retry with 4s...
✅ Each retry checks current lookup
✅ Stops if lookup changed
✅ Prevents infinite retry chains
```

## Key Takeaways

1. **Problem:** Asynchronous callbacks (setTimeout) outliving their context
2. **Solution:** Track active callbacks and cancel them proactively
3. **Defense:** Multiple layers of checks ensure stale callbacks are ignored
4. **Observability:** Console logs make debugging easy
5. **Resource Efficiency:** No orphaned timeouts or unnecessary AJAX calls

The fix ensures that only the most recent vehicle lookup's polling is active at any time, preventing race conditions and ensuring consistent, reliable results.
