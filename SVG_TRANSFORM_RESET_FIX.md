# SVG Transform Reset Bug Fix

## Problem Description

After scanning a QR code or switching floors, the SVG map would reset its transform matrix (zoom and pan position) on the **first user interaction** (drag or pan) when the details drawer was visible. This created a jarring user experience where:

1. User scans QR code → floor loads with office highlighted
2. Drawer automatically opens showing office details
3. User tries to pan/zoom the map
4. **On first touch/drag, the SVG suddenly resets to default zoom and centered position**
5. Subsequent interactions work normally

This behavior occurred specifically after:
- Scanning an office QR code (which auto-opens the drawer)
- Manually opening the drawer after viewing the map
- Any scenario where drawer state changed while pathfinding was active

## Root Cause Analysis

### Chain of Events Leading to Reset

1. **Drawer Opens/Moves**
   - User action (drag, click) or programmatic call (`window.openDrawer()`)
   - Drawer animates to new position

2. **Height Adjustment Triggered**
   - `adjustMainContentHeight()` called to resize main content area
   - SVG container height updated via `style.height` and `style.minHeight`

3. **Browser Layout Recalculation**
   - Changing element heights triggers browser reflow
   - This may fire window `resize` events or similar layout events

4. **Resize Handler Executes**
   - `panZoomResizeHandler` listens for window resize events
   - **Original code called `fit()` and `center()` on every resize**
   - These methods reset zoom to "fit all content" and pan to "center"

5. **Transform Reset**
   - User's carefully positioned view is destroyed
   - SVG resets to default state, losing user context

### Technical Root Causes

**1. Aggressive Resize Handler Behavior**
```javascript
// ORIGINAL PROBLEMATIC CODE
window.panZoomResizeHandler = () => {
  window.svgPanZoomInstance.resize();
  setTimeout(() => {
    window.svgPanZoomInstance.fit();    // ← Resets zoom
    window.svgPanZoomInstance.center(); // ← Resets pan
  }, 10);
};
```

**Problem:** Every resize event (including those triggered by drawer interactions) would reset the user's view position and zoom level.

**2. Drawer Open Calling fit()/center()**
```javascript
// ORIGINAL PROBLEMATIC CODE
window.openDrawer = function() {
  setTimeout(() => {
    window.svgPanZoomInstance.resize();
    window.svgPanZoomInstance.fit();    // ← Resets zoom
    window.svgPanZoomInstance.center(); // ← Resets pan
  }, 250);
}
```

**Problem:** When office QR codes auto-opened the drawer, this explicitly reset the user's view.

**3. No Protection During Drawer Interactions**
- No mechanism to prevent resize handler from firing during drawer animations
- Height changes during drag triggered resize events continuously
- No distinction between "real" window resizes (orientation change) vs "internal" layout changes (drawer movement)

**4. Stability Check Interference (Minor)**
- Stability check interval running every 3 seconds
- Could attempt to "fix" invalid states during rapid interactions
- Added noise to the debugging process

## Solution Implementation

### 1. Interaction Flag System (Lines ~4495-4498, 4567-4570)

**Purpose:** Prevent resize handler from interfering during drawer interactions

```javascript
// Global flag to track drawer state
window.isDrawerInteracting = false;

// Set flag during drag start
function handleDragStart(e) {
  window.isDrawerInteracting = true;
  // ... rest of drag logic
}

// Clear flag after drag completes
function handleDragEnd(e) {
  // ... snap logic
  setTimeout(() => {
    window.isDrawerInteracting = false;
  }, 300); // After animation completes
}
```

**Benefits:**
- Resize handler skips execution during drawer movement
- Prevents competing transform updates
- User interactions remain uninterrupted

### 2. Resize Handler Fix (Lines ~4031-4051)

**Before:**
```javascript
window.panZoomResizeHandler = () => {
  window.svgPanZoomInstance.resize();
  setTimeout(() => {
    window.svgPanZoomInstance.fit();
    window.svgPanZoomInstance.center();
  }, 10);
};
```

**After:**
```javascript
window.panZoomResizeHandler = () => {
  // Skip during drawer interactions
  if (window.isDrawerInteracting) {
    console.log('Skipping SVG resize during drawer interaction');
    return;
  }
  
  // Only call resize() - preserve zoom and pan
  window.svgPanZoomInstance.resize();
  console.log('SVG resized - zoom/pan preserved');
};
```

**Benefits:**
- Respects user's current zoom and pan position
- Only updates SVG viewport dimensions
- No unexpected view resets during normal use

### 3. Drawer Open Function Fix (Lines ~4545-4571)

**Before:**
```javascript
window.openDrawer = function() {
  setTimeout(() => {
    adjustMainContentHeight(currentTranslate);
    window.svgPanZoomInstance.resize();
    window.svgPanZoomInstance.fit();    // ← Removed
    window.svgPanZoomInstance.center(); // ← Removed
  }, 250);
}
```

**After:**
```javascript
window.openDrawer = function() {
  window.isDrawerInteracting = true;
  
  setTimeout(() => {
    adjustMainContentHeight(currentTranslate);
    // Only resize - preserve user's zoom/pan
    window.svgPanZoomInstance.resize();
    
    setTimeout(() => {
      window.isDrawerInteracting = false;
    }, 50);
  }, 250);
}
```

**Benefits:**
- Drawer opens without disrupting user's view
- SVG dimensions updated properly
- No jarring reset when office details appear

### 4. Click Handler Protection (Lines ~4642-4656)

**Added flag management to toggle handler:**
```javascript
function handleClick() {
  window.isDrawerInteracting = true;
  
  // ... toggle logic
  
  setTimeout(() => {
    window.isDrawerInteracting = false;
  }, 300);
}
```

**Benefits:**
- Consistent behavior across all drawer interaction types
- Click toggles don't trigger SVG resets
- Smooth transitions maintained

## Technical Details

### When resize() Should Be Called
✅ **Appropriate times to call `resize()`:**
- Window orientation changes (portrait ↔ landscape)
- Actual window resize events (browser window size changes)
- Container dimension changes (drawer movement, layout shifts)

❌ **When NOT to call `fit()` or `center()`:**
- During user pan/zoom interactions
- When drawer opens/closes
- During any programmatic layout changes
- Unless explicitly requested by user (e.g., "Reset View" button)

### svg-pan-zoom Method Behaviors

| Method | Behavior | Use Case |
|--------|----------|----------|
| `resize()` | Updates viewport dimensions without changing transform | Container size changes |
| `fit()` | Resets zoom to fit entire SVG in viewport | Initial load, "Fit to Screen" button |
| `center()` | Resets pan to center SVG in viewport | Initial load, "Center View" button |
| `getPan()` | Returns current pan position {x, y} | State preservation |
| `getZoom()` | Returns current zoom level (number) | State preservation |
| `updateBBox()` | Recalculates bounding box | After SVG content changes |

### Interaction Flag Lifecycle

```
User Action → Set flag = true → Drawer animates → Height adjusts → 
Wait for animation (250ms-300ms) → Clear flag = false → Normal operation resumes
```

**Timing Considerations:**
- Drawer animation: 200ms (CSS transition)
- Height adjustment: Immediate
- Flag clear delay: 300ms (animation + buffer)
- Total protection window: ~300-550ms

This window ensures all layout recalculations complete before re-enabling the resize handler.

## Testing Checklist

### QR Code Scanning Tests
- [ ] **Office QR Scan**: Map loads, drawer opens, pan/zoom works immediately without reset
- [ ] **First Drag After Scan**: SVG maintains position on first touch/drag
- [ ] **Multiple Drags**: Subsequent drags continue working smoothly
- [ ] **Zoom After Scan**: Pinch-to-zoom works without unexpected resets

### Drawer Interaction Tests
- [ ] **Drag Drawer Up**: SVG doesn't reset while dragging
- [ ] **Drag Drawer Down**: SVG position maintained during movement
- [ ] **Click Handle to Toggle**: Toggle between open/closed preserves view
- [ ] **Rapid Toggle**: Quick open/close cycles don't cause resets

### Pathfinding Tests
- [ ] **Get Directions**: Opening directions modal doesn't reset map
- [ ] **Route Display**: Drawing route preserves user's zoom level
- [ ] **Clear Path**: Clearing path doesn't reset view
- [ ] **Multiple Routes**: Successive pathfinding operations stable

### Orientation Change Tests
- [ ] **Portrait → Landscape**: SVG resizes properly (this is when resize should work)
- [ ] **Landscape → Portrait**: View updates to fit new dimensions
- [ ] **Drawer Open During Rotation**: Both drawer and map handle rotation gracefully

### Multi-Floor Tests
- [ ] **Floor Switch**: Switching floors resets view (expected behavior)
- [ ] **Floor Switch with Drawer Open**: New floor loads, drawer preserved, no extra resets
- [ ] **Cross-Floor Navigation**: Transitioning between floors maintains stability

## Console Logging for Debugging

The fix includes detailed console logging to help track behavior:

```javascript
// Resize handler logging
console.log('Skipping SVG resize during drawer interaction');
console.log('SVG resized - zoom/pan preserved');

// Drawer interaction logging
console.log('Drawer interaction complete - resize handler re-enabled');
console.log('Drawer toggle complete - resize handler re-enabled');
console.log('Drawer open complete - resize handler re-enabled');
console.log('SVG resized after drawer open - zoom/pan preserved');
```

**Usage:**
1. Open browser DevTools console
2. Perform actions (scan QR, drag drawer, pan map)
3. Check for these log messages to verify correct behavior
4. Look for "Skipping" messages during drawer interactions
5. Verify "re-enabled" messages after interactions complete

## Performance Impact

### Minimal Overhead
- **Flag checks**: O(1) boolean comparison per resize event
- **setTimeout calls**: Already present, now with flag clearing
- **Removed operations**: Eliminated unnecessary `fit()` and `center()` calls
- **Net effect**: Slightly better performance (fewer transform calculations)

### User Experience Improvements
- **Immediate:** No more unexpected view resets
- **Smooth:** Drawer interactions feel more natural
- **Predictable:** SVG behaves consistently across all scenarios
- **Responsive:** Pan/zoom gestures work on first touch

## Related Files

- **Main file**: `mobileScreen/explore.php` (lines 4031-4051, 4495-4656)
- **CSS**: `mobileScreen/explore.css` (drawer and content styling)
- **Related fixes**: 
  - `DRAWER_SVG_DISAPPEAR_FIX.md` - Height management fix
  - `OFFICE_QR_SVG_LOAD_FIX.md` - Floor detection logic

## Prevention Guidelines

### When Modifying Pan-Zoom Logic
1. **Never call `fit()` or `center()` in response to layout changes**
   - Only call these on explicit user request or initial load
   - Use `resize()` alone for dimension updates

2. **Protect user state during animations**
   - Add interaction flags for any new drawer-like UI elements
   - Ensure flags clear after animations complete

3. **Test with real user workflows**
   - Always test QR code scanning → drawer opening → interaction sequence
   - Verify first touch/drag behavior specifically

### When Adding New UI Components
1. **If component affects SVG container dimensions:**
   - Add interaction flag similar to `isDrawerInteracting`
   - Skip resize handler during component animations
   - Clear flag after transition completes

2. **If component needs SVG refresh:**
   - Call `resize()` only, never `fit()/center()` unless intentional
   - Add console logging to track behavior
   - Test with existing zoom/pan positions

### When Debugging Transform Issues
1. **Check console for flag state:**
   - Look for "Skipping" and "re-enabled" messages
   - Verify timing of flag changes

2. **Monitor resize events:**
   - Add temporary logging to `panZoomResizeHandler`
   - Check if events fire during unexpected times

3. **Verify animation timing:**
   - Ensure flag clear delays match animation durations
   - Add buffer time for browser layout recalculations

## Future Enhancements

### Potential Improvements
1. **State Preservation System**
   - Save user's zoom/pan before intentional resets
   - Restore state after floor switches if desired

2. **Smart Reset Detection**
   - Distinguish between user-initiated resets (tap "Reset View" button)
   - vs. accidental resets (layout changes)

3. **Resize Event Filtering**
   - Use ResizeObserver for more precise container monitoring
   - Distinguish between SVG container resize vs. window resize

4. **Gesture Recognition**
   - Detect pan vs. drawer drag at touch start
   - Route events to appropriate handlers earlier

### Code Cleanup Opportunities
1. Consolidate all drawer interaction handling into a single manager object
2. Create utility functions for SVG refresh operations with built-in protection
3. Document expected behavior for each pan-zoom method in code comments
4. Add unit tests for interaction flag timing and state transitions
