# Office QR Code SVG Map Auto-Load Fix

**Issue:** SVG floor map fails to render automatically when accessing `explore.php` through a scanned office QR code, remaining blank until a floor is manually reselected.

**Date Fixed:** October 26, 2025

## Problem Description

When users scan an office QR code that redirects to `explore.php?office_id={id}`, the SVG floor map does not load automatically. The map container remains blank, and users must manually click a floor button to trigger the SVG rendering.

### User Experience Impact

**Before Fix:**
1. User scans office QR code (e.g., office on floor 2)
2. Browser redirects to `explore.php?office_id=5`
3. Page loads but SVG map is **completely blank**
4. User must manually click "Floor 2" button
5. SVG finally renders and office is highlighted

**This creates confusion** - users think the app is broken or their scan didn't work.

## Root Cause Analysis

### Investigation Process

The code had logic to detect and load the correct floor for **panorama QR scans** but completely **ignored office QR scans**:

```javascript
// OLD CODE (INCOMPLETE)
let initialFloor = 1; // Always defaults to floor 1
if (scannedPanoramaFromPHP && scannedPanoramaFromPHP.floor) {
  initialFloor = scannedPanoramaFromPHP.floor;
  console.log(`🎯 Scanned panorama detected for floor ${initialFloor}...`);
}
// ❌ NO CHECK FOR highlightOfficeIdFromPHP!

// Load the determined initial floor
loadFloorMap(initialFloor); // Always loads floor 1 for office QR scans
```

### Why It Failed

1. **Office QR URL structure:** `explore.php?office_id=5`
   - PHP captures `$_GET['office_id']` → `$highlight_office_id`
   - Passed to JavaScript as `highlightOfficeIdFromPHP`

2. **Office location format:** `room-205-2` (room 205, floor 2)
   - Floor number is encoded in the location string as the last segment
   - `getFloorFromLocation()` function exists to extract this

3. **Missing logic:** Code never called `getFloorFromLocation()` for office QR scans
   - Result: Always loaded floor 1 by default
   - Blank SVG if office is on floor 2 or 3

### Data Flow Tracing

```
QR Scan Flow:
─────────────
Office QR Code
    ↓
explore.php?office_id=5
    ↓
PHP: $highlight_office_id = 5
    ↓
JS: highlightOfficeIdFromPHP = 5
    ↓
❌ OLD: initialFloor = 1 (hardcoded default)
    ↓
loadFloorMap(1) → Wrong floor loaded
    ↓
Office on floor 2 → SVG is blank (office not on this floor)
```

## Solution Implemented

### Code Changes

**File:** `mobileScreen/explore.php`
**Location:** Lines ~4275-4295 (DOMContentLoaded event handler)

Added office QR detection and floor extraction logic:

```javascript
// NEW CODE (FIXED)
let initialFloor = 1; // Default to floor 1
if (scannedPanoramaFromPHP && scannedPanoramaFromPHP.floor) {
  // Panorama QR scan handling (existing logic)
  initialFloor = scannedPanoramaFromPHP.floor;
  console.log(`🎯 Scanned panorama detected for floor ${initialFloor}...`);
  // ... update floor buttons ...
  
} else if (highlightOfficeIdFromPHP) {
  // ✅ NEW: Office QR scan handling
  const highlightedOffice = officesData.find(o => o.id == highlightOfficeIdFromPHP);
  if (highlightedOffice && highlightedOffice.location) {
    const officeFloor = getFloorFromLocation(highlightedOffice.location);
    if (officeFloor) {
      initialFloor = officeFloor;
      console.log(`🏢 Office QR scan detected (ID: ${highlightOfficeIdFromPHP}, Location: ${highlightedOffice.location}), loading floor ${initialFloor}`);
      
      // Update the active floor button to match the office floor
      setTimeout(() => {
        const floorButtons = document.querySelectorAll('.floor-btn');
        floorButtons.forEach(btn => {
          btn.classList.remove('active');
          if (parseInt(btn.getAttribute('data-floor')) === initialFloor) {
            btn.classList.add('active');
          }
        });
      }, 100);
    } else {
      console.warn(`⚠️ Office location doesn't contain valid floor number`);
    }
  } else {
    console.warn(`⚠️ Office not found in offices data or has no location`);
  }
}

// Load the determined initial floor
loadFloorMap(initialFloor); // Now loads correct floor!
```

### How It Works

1. **Check for office QR parameter:**
   ```javascript
   if (highlightOfficeIdFromPHP)
   ```

2. **Find the office in loaded data:**
   ```javascript
   const highlightedOffice = officesData.find(o => o.id == highlightOfficeIdFromPHP);
   ```

3. **Extract floor from location string:**
   ```javascript
   const officeFloor = getFloorFromLocation(highlightedOffice.location);
   // Example: "room-205-2" → extracts 2
   ```

4. **Update floor button UI to match:**
   ```javascript
   // Mark the correct floor button as active visually
   btn.classList.add('active');
   ```

5. **Load the correct SVG:**
   ```javascript
   loadFloorMap(initialFloor); // Now loads floor 2 instead of floor 1!
   ```

## Testing Checklist

- [x] Scan office QR on floor 1 → Floor 1 SVG loads immediately, office highlighted
- [x] Scan office QR on floor 2 → Floor 2 SVG loads immediately, office highlighted
- [x] Scan office QR on floor 3 → Floor 3 SVG loads immediately, office highlighted
- [x] Scan panorama QR → Panorama floor still loads correctly (no regression)
- [x] Direct URL access without QR → Floor 1 loads by default (expected)
- [x] Office with invalid location → Falls back to floor 1 with warning logged
- [x] Office not in database → Falls back to floor 1 with warning logged
- [x] Floor button UI reflects correct floor on load

## Edge Cases Handled

### 1. Missing Office Data
```javascript
if (highlightedOffice && highlightedOffice.location) {
  // Only proceed if office exists and has location
}
```

### 2. Invalid Location Format
```javascript
if (officeFloor) {
  // Only use extracted floor if valid number
} else {
  console.warn(`⚠️ Office location doesn't contain valid floor number`);
}
```

### 3. Malformed Location String
The `getFloorFromLocation()` function safely handles:
- `null` or `undefined` → returns `null`
- Non-string values → returns `null`
- Invalid formats → returns `null`
- Valid formats (`room-205-2`) → returns `2`

## User Experience Improvement

### Before Fix:
```
Scan QR → Blank screen → Confusion → Manual floor selection → Map appears
(~5-10 seconds of user uncertainty)
```

### After Fix:
```
Scan QR → Map immediately loads with office highlighted
(~1-2 seconds, instant visual feedback)
```

## Technical Details

### Floor Detection Function

Already existed in the codebase but wasn't being used for office QR scans:

```javascript
const getFloorFromLocation = (location) => {
  if (!location || typeof location !== 'string') return null;
  const parts = location.split('-');
  const possibleFloor = parseInt(parts[parts.length - 1], 10);
  return Number.isNaN(possibleFloor) ? null : possibleFloor;
};
```

**Examples:**
- `"room-101-1"` → `1`
- `"room-205-2"` → `2`
- `"room-305-3"` → `3`
- `"invalid"` → `null`
- `null` → `null`

### Load Priority

The fix maintains proper priority:

1. **Panorama QR** (highest priority) - explicit floor in URL
2. **Office QR** (new logic) - floor derived from office location
3. **Default** (fallback) - floor 1 if neither QR type detected

## Related Systems

This fix integrates with:
- **QR code generation:** `generate_qrcodes.php` creates office QR codes
- **Office management:** `officeManagement.php` assigns office locations
- **Floor plan editor:** `floorPlan.php` maps offices to SVG rooms
- **Pathfinding:** `pathfinding.js` uses floor detection for routing

## Database Schema Reference

**Relevant tables:**
- `offices` - contains `location` field (format: `room-{number}-{floor}`)
- `qrcode_info` - stores QR code data with `office_id` reference
- `qr_scan_logs` - tracks when QR codes are scanned

## Prevention Guidelines

When adding new QR scan types:

1. ✅ **DO** check for the new scan parameter early in initialization
2. ✅ **DO** extract floor/location data before loading SVG
3. ✅ **DO** update floor button UI to reflect the determined floor
4. ✅ **DO** log the detection for debugging
5. ✅ **DO** handle edge cases (missing data, invalid format)
6. ❌ **DON'T** assume default floor without checking scan parameters

## Future Enhancements

Consider adding:
- Loading indicator during SVG fetch
- Error modal if office QR points to deleted office
- Auto-zoom to office location after SVG loads
- Animation transition between floors
- Breadcrumb trail showing QR scan source

## Console Logging

The fix adds helpful debug logging:

```javascript
// Panorama QR detected
console.log(`🎯 Scanned panorama detected for floor ${initialFloor}...`);

// Office QR detected (NEW)
console.log(`🏢 Office QR scan detected (ID: ${officeId}, Location: ${location}), loading floor ${floor}`);

// Warning: Invalid data
console.warn(`⚠️ Office location doesn't contain valid floor number`);
console.warn(`⚠️ Office not found in offices data or has no location`);
```

These logs help developers debug QR scan issues in production.

## Performance Impact

- **No performance impact:** The floor detection logic runs once during page load
- **Reduces user wait time:** SVG loads immediately instead of after manual selection
- **Better UX:** Single automatic load vs. two loads (default + user correction)

---

**Fix verified and deployed:** October 26, 2025  
**Impact:** Resolves blank SVG issue for office QR scans  
**Risk level:** Low (adds detection logic without changing existing panorama flow)  
**Breaking changes:** None (backward compatible, maintains all existing behavior)
