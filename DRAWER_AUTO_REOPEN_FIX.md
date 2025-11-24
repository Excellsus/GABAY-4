# Drawer Auto-Reopen Fix

## Problem Description

**User Report:**
> "after i scan a qrcode of a door, the details-drawer keeps popping up whenever i try to switch floors, when i close it and switch floors it opens back up please fix this"

**Symptom:**
1. User scans a door QR code
2. Drawer opens showing "You Are Here" (✅ Expected behavior)
3. User manually closes the drawer
4. User switches to a different floor
5. Drawer automatically reopens (❌ Unwanted behavior)

## Root Cause Analysis

### The Issue
When a QR code is scanned, the backend sets `window.highlightOfficeIdFromPHP` to mark the scanned office. The function `drawPathsAndDoorsWhenReady()` checks this variable and calls `populateAndShowDrawerWithData()` to show office details.

**Problem:** `drawPathsAndDoorsWhenReady()` runs every time:
- Initial page load ✅
- Floor switch (via floor selector) ❌
- Floor graph reload ❌

There was no mechanism to track:
1. Whether the drawer was already opened for the QR scan
2. Whether the user manually closed the drawer

**Result:** Drawer kept reopening on every floor switch because `highlightOfficeIdFromPHP` persisted and triggered the highlight logic repeatedly.

## Solution Implemented

### Strategy
Added two tracking flags to prevent unwanted drawer auto-opens:

1. **`window.drawerManuallyClosed`** (Boolean)
   - Tracks if user manually closed the drawer
   - Set to `true` when user drags drawer down or clicks handle to close
   - Set to `false` when user explicitly clicks a room to open drawer
   - Prevents auto-opens when `true`

2. **`window.initialQRHighlightCompleted`** (Boolean)
   - Tracks if initial QR scan highlight has been completed
   - Set to `true` after first QR highlight runs
   - Prevents repeated QR highlight logic on floor switches

### Code Changes

#### 1. Flag Initialization (Lines 1783-1791)
```javascript
// Flag to track if drawer has been manually closed by user
window.drawerManuallyClosed = false;

// Flag to track if initial QR scan highlight has been completed
window.initialQRHighlightCompleted = false;
```

#### 2. Drag End Handler (Lines ~5560-5590)
**Added flag tracking when drawer is dragged:**
```javascript
function handleDragEnd(e) {
  // ... existing drag logic ...
  
  const snappedPosition = (currentTranslate < (maxTranslate - snapThreshold)) ? minTranslate : maxTranslate;
  
  // Track if user manually closed the drawer (prevent auto-reopen on floor switch)
  if (snappedPosition === maxTranslate) {
    window.drawerManuallyClosed = true;
    console.log('Drawer manually closed by user - auto-reopen disabled');
  } else {
    window.drawerManuallyClosed = false;
    console.log('Drawer opened by user - auto-reopen allowed');
  }
  
  // ... rest of handler ...
}
```

#### 3. Click Toggle Handler (Lines ~5600-5630)
**Added flag tracking when drawer is clicked:**
```javascript
function handleClick() {
  // ... existing toggle logic ...
  
  const newPosition = (currentTranslate === minTranslate) ? maxTranslate : minTranslate;
  
  // Track if user manually closed the drawer (prevent auto-reopen on floor switch)
  if (newPosition === maxTranslate) {
    window.drawerManuallyClosed = true;
    console.log('Drawer manually closed by handle click - auto-reopen disabled');
  } else {
    window.drawerManuallyClosed = false;
    console.log('Drawer opened by handle click - auto-reopen allowed');
  }
  
  // ... rest of handler ...
}
```

#### 4. window.openDrawer() Function (Lines ~5490-5520)
**Added flag check to prevent auto-opens:**
```javascript
window.openDrawer = function(forceOpen = false) {
  // Check if user manually closed drawer - don't auto-reopen unless forced
  if (window.drawerManuallyClosed && !forceOpen) {
    console.log('Drawer auto-open skipped - user closed it manually');
    return;
  }
  
  // ... rest of open logic ...
}
```

**Parameter:**
- `forceOpen` (Boolean, default `false`): When `true`, bypasses the `drawerManuallyClosed` check
- Used when drawer is explicitly opened by user action (clicking a room)

#### 5. QR Highlight Logic (Lines ~2807-2880)
**Added completion flag check:**
```javascript
// Handle office highlighting for QR scan
// FIXED: Only open drawer on first scan, not on every floor switch
if (window.highlightOfficeIdFromPHP && !window.initialQRHighlightCompleted) {
  const targetOffice = officesData.find(office => office.id == window.highlightOfficeIdFromPHP);
  if (targetOffice && targetOffice.location) {
    console.log('📍 QR scan detected - Highlighting office:', targetOffice.name);
    
    // ... existing QR scan logic ...
    
    // Mark that initial QR highlight has been completed
    window.initialQRHighlightCompleted = true;
    
    setTimeout(() => {
      // ... show "You Are Here" and open drawer ...
    }, 500);
  }
}
```

**Change:** Added `&& !window.initialQRHighlightCompleted` check to prevent repeated executions.

#### 6. handleRoomClick() Function (Lines ~3102-3115)
**Added flag reset when user clicks a room:**
```javascript
function handleRoomClick(office) {
  console.log("handleRoomClick called with office:", office);
  
  // Store selected office globally for pathfinding
  window.currentSelectedOffice = office;
  
  // Reset manual close flag - user explicitly wants to see this office's details
  window.drawerManuallyClosed = false;
  console.log('User clicked room - drawer auto-reopen re-enabled');
  
  populateAndShowDrawerWithData(office);
  setTimeout(refreshSvgContainer, 250);
}
```

**Logic:** When user explicitly clicks a room, they want to see details, so reset the flag.

#### 7. populateAndShowDrawerWithData() Function (Lines ~3090-3095)
**Changed to force-open drawer:**
```javascript
if (window.openDrawer) {
  console.log("Calling window.openDrawer() from populateAndShowDrawerWithData.");
  // Force open drawer - this is an explicit user action (clicked room or QR scan initial highlight)
  window.openDrawer(true);
} else {
  console.error("window.openDrawer is not available. Cannot open drawer for QR office.");
}
```

**Change:** Added `true` parameter to force open drawer, since this function is only called for explicit user actions.

## Behavior After Fix

### Test Scenario 1: QR Scan and Floor Switch
1. User scans door QR code → Drawer opens ✅
2. User closes drawer manually → `drawerManuallyClosed = true` ✅
3. User switches to floor 2 → Drawer stays closed ✅
4. User switches to floor 3 → Drawer stays closed ✅

### Test Scenario 2: Manual Room Click
1. User manually closes drawer → `drawerManuallyClosed = true` ✅
2. User clicks a different room → `drawerManuallyClosed = false`, drawer opens ✅
3. User switches floor → Drawer reopens (user explicitly opened it) ✅

### Test Scenario 3: QR Scan Multiple Times
1. User scans QR code → Drawer opens, `initialQRHighlightCompleted = true` ✅
2. User closes drawer → `drawerManuallyClosed = true` ✅
3. Floor switches multiple times → QR highlight logic doesn't re-run ✅
4. Drawer stays closed until user explicitly opens it ✅

## Console Debugging

**Logs to watch:**
```
Drawer manually closed by user - auto-reopen disabled
Drawer auto-open skipped - user closed it manually
User clicked room - drawer auto-reopen re-enabled
Drawer opened by user - auto-reopen allowed
📍 QR scan detected - Highlighting office: [name]
Calling window.openDrawer() from populateAndShowDrawerWithData.
```

## Files Modified

**File:** `mobileScreen/explore.php`

**Modified Sections:**
- Lines 1783-1791: Flag initialization
- Lines ~2807-2880: QR highlight logic (added completion check)
- Lines ~3015-3100: populateAndShowDrawerWithData (force open drawer)
- Lines ~3102-3115: handleRoomClick (reset manual close flag)
- Lines ~5490-5520: window.openDrawer (added forceOpen parameter)
- Lines ~5560-5590: handleDragEnd (track manual close)
- Lines ~5600-5630: handleClick (track manual close)

## Key Takeaways

### Why This Happened
- QR scan variables persisted across floor switches
- `drawPathsAndDoorsWhenReady()` runs on every floor load
- No state tracking for user's drawer close intent

### The Fix
- Track user's explicit close action
- Track QR highlight completion
- Respect user intent: don't auto-reopen if they closed it
- Allow explicit opens: force-open when user clicks a room

### Design Pattern
```javascript
// Auto-open (respects user preference)
window.openDrawer();  // Can be blocked by drawerManuallyClosed

// Force open (explicit user action)
window.openDrawer(true);  // Always opens, ignores drawerManuallyClosed
```

## Testing Checklist

- [x] Scan QR code → Drawer opens
- [x] Close drawer → Flag set to true
- [x] Switch floors → Drawer stays closed
- [x] Click a room → Drawer opens (flag reset)
- [x] QR scan doesn't repeat on floor switch
- [x] Drag to close → Flag set correctly
- [x] Handle click to close → Flag set correctly
- [x] Console logs confirm flag states

## Related Issues

**Previous Fix:** `DRAWER_SVG_DISAPPEAR_FIX.md` (SVG visibility on drawer open)
**Previous Fix:** `SVG_TRANSFORM_RESET_FIX.md` (Preserve zoom/pan on drawer interaction)

This fix builds on those solutions by adding user intent tracking to the drawer system.
