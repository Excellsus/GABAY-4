# Drawer SVG Disappearing Bug Fix

## Problem Description

When scanning an office QR code in `mobileScreen/explore.php`, the SVG floor map would:
1. Load correctly for a split second
2. Disappear immediately when the details drawer automatically opened
3. Leave users with a blank screen despite the drawer showing office information

## Root Cause Analysis

### Chain of Events
1. **QR Scan → Floor Load**: Office QR code triggers floor map load with correct SVG
2. **Office Highlighting**: After 500ms delay, `handleRoomClick()` is called
3. **Drawer Opens**: `window.openDrawer()` is invoked to show office details
4. **Height Recalculation**: Opening drawer triggers `adjustMainContentHeight()`
5. **SVG Collapse**: Height calculation resulted in insufficient space for SVG container

### Technical Root Causes

**1. Inadequate Height Calculation**
- Original formula: `calc(100vh - 80px - 60px - ${occupiedDrawerHeight}px - -85px)`
- The double negative and fixed offsets caused unpredictable results
- When drawer opened fully, `occupiedDrawerHeight` became very large
- Resulted in near-zero or negative height for main content area

**2. No Minimum Height Constraint**
- Function didn't enforce minimum visible area for map
- SVG container could be compressed to 0px height
- User lost all spatial context when viewing office details

**3. Timing Issue**
- Height adjustment occurred during drawer animation
- Caused layout thrashing and visual glitches
- SVG pan-zoom instance couldn't recalculate properly mid-animation

**4. Missing SVG Refresh**
- After height changes, SVG viewport wasn't recalculated
- Pan-zoom instance maintained stale dimensions
- SVG appeared "lost" even when container had proper height

## Solution Implementation

### 1. Enhanced Height Calculation (Lines ~4486-4513)

**Before:**
```javascript
function adjustMainContentHeight(translateY) {
  const occupiedDrawerHeight = Math.max(0, drawerHeight - translateY - navHeight);
  const newMainHeight = `calc(100vh - 80px - 60px - ${occupiedDrawerHeight}px - -85px)`;
  mainContent.style.height = newMainHeight;
}
```

**After:**
```javascript
function adjustMainContentHeight(translateY) {
  const calculatedHeight = window.innerHeight - headerHeight - navHeight - occupiedDrawerHeight;
  
  // CRITICAL: Maintain at least 40% viewport for map visibility
  const minContentHeight = window.innerHeight * 0.4;
  const finalHeight = Math.max(calculatedHeight, minContentHeight);
  
  mainContent.style.height = `${finalHeight}px`;
  
  // Also ensure SVG container maintains proper height
  const svgContainer = document.getElementById('svg-container');
  if (svgContainer) {
    svgContainer.style.minHeight = `${finalHeight}px`;
    svgContainer.style.height = '100%';
  }
}
```

**Improvements:**
- Uses explicit pixel calculations instead of calc() with confusing negatives
- Enforces 40% minimum viewport height for map visibility
- Directly sets SVG container height to prevent collapse
- Detailed console logging for debugging

### 2. Delayed Height Adjustment (Lines ~4515-4534)

**Before:**
```javascript
window.openDrawer = function() {
  detailsDrawer.style.transform = `translateY(${minTranslate}px)`;
  currentTranslate = minTranslate;
}
```

**After:**
```javascript
window.openDrawer = function() {
  detailsDrawer.style.transform = `translateY(${minTranslate}px)`;
  currentTranslate = minTranslate;
  
  // Update height AFTER transition completes
  setTimeout(() => {
    adjustMainContentHeight(currentTranslate);
    
    // Force SVG refresh
    if (window.svgPanZoomInstance) {
      window.svgPanZoomInstance.resize();
      window.svgPanZoomInstance.fit();
      window.svgPanZoomInstance.center();
    }
  }, 250); // Wait for drawer animation
}
```

**Improvements:**
- Height adjustment happens after drawer animation completes
- Prevents layout thrashing during transition
- Explicitly refreshes SVG pan-zoom instance
- Re-centers and re-fits SVG viewport

### 3. SVG Visibility Safeguards (Lines ~2157-2188)

**Added to Office QR Highlighting:**
```javascript
setTimeout(() => {
  const svgContainer = document.getElementById('svg-container');
  const svg = document.querySelector('#capitol-map-svg');
  
  if (svgContainer && svg) {
    // Force visibility
    svg.style.display = 'block';
    svg.style.visibility = 'visible';
    svg.style.opacity = '1';
    
    // Refresh pan-zoom
    if (window.svgPanZoomInstance) {
      window.svgPanZoomInstance.resize();
      window.svgPanZoomInstance.updateBBox();
    }
  }
}, 300); // After drawer animation
```

**Purpose:**
- Ensures SVG remains visible after all animations complete
- Explicitly sets visibility properties to prevent CSS conflicts
- Refreshes pan-zoom bounding box for accurate rendering
- Runs 300ms after drawer opens (50ms after height adjustment)

## Testing Checklist

### QR Code Scanning Tests
- [ ] **Office QR Scan - Floor 1**: Map loads and stays visible when drawer opens
- [ ] **Office QR Scan - Floor 2**: Correct floor loads, SVG persists after drawer
- [ ] **Office QR Scan - Floor 3**: Multi-floor navigation maintains SVG visibility
- [ ] **Panorama QR Scan**: Panorama opens without affecting map visibility

### Drawer Interaction Tests
- [ ] **Manual Room Click**: Clicking room on map opens drawer, SVG stays visible
- [ ] **Drawer Drag**: Dragging drawer up/down maintains proper SVG sizing
- [ ] **Drawer Toggle**: Clicking handle to open/close preserves map visibility
- [ ] **Multiple Office Scans**: Scanning different offices updates drawer without SVG flicker

### Height Calculation Tests
- [ ] **Small Screen**: 320px width phone maintains 40% minimum map height
- [ ] **Large Screen**: Tablet-sized devices show proper proportions
- [ ] **Landscape Mode**: Orientation change maintains SVG visibility
- [ ] **Drawer Fully Open**: Map remains visible at minimum 40% height

### Pan-Zoom Functionality Tests
- [ ] **After Drawer Open**: Pan/zoom still works after drawer animation
- [ ] **Center/Fit**: SVG properly centered after height adjustments
- [ ] **Zoom Reset**: Reset button works after drawer interactions
- [ ] **Pinch Gesture**: Touch gestures remain responsive with drawer open

## Console Logging for Debugging

The fix includes detailed console logging at each stage:

```javascript
// Height adjustment logging
console.log(`Adjusting main content height. 
  Drawer translateY: ${translateY}px, 
  Occupied: ${occupiedDrawerHeight}px, 
  Final Height: ${finalHeight}px`);

// SVG visibility logging
console.log('Ensuring SVG visibility after drawer open. 
  SVG display:', svg.style.display, 
  'Container height:', svgContainer.offsetHeight);

// Pan-zoom refresh logging
console.log('SVG pan-zoom refreshed after drawer open');
```

**Useful for verifying:**
- Actual calculated heights at each stage
- SVG visibility state after drawer operations
- Pan-zoom instance refresh success

## Performance Notes

### Minimal Impact
- **2 setTimeout calls**: 250ms + 300ms (overlapping, not sequential)
- **DOM operations**: Limited to necessary visibility/height changes
- **Pan-zoom refresh**: Only called when instance exists
- **No forced reflows**: Height set once per animation cycle

### Smooth User Experience
- Drawer animation: 200ms CSS transition (unchanged)
- Height adjustment: Delayed 250ms to avoid layout thrashing
- SVG refresh: Delayed 300ms to ensure stability
- Total perceived delay: Imperceptible to users

## Related Files

- **Main file**: `mobileScreen/explore.php` (lines 2157-2188, 4486-4534)
- **CSS**: `mobileScreen/explore.css` (lines 172-190) - `.content` and `.svg-container` styles
- **Related fix**: `OFFICE_QR_SVG_LOAD_FIX.md` - Floor detection logic for QR scans

## Prevention Guidelines

### When Modifying Drawer Behavior
1. **Always test with QR scans**: Don't just test manual room clicks
2. **Check height calculations**: Log actual pixel values, not just formulas
3. **Enforce minimum heights**: Never allow critical UI elements to collapse
4. **Delay heavy operations**: Wait for animations before recalculating layouts

### When Adding Animation Interactions
1. **Avoid layout changes mid-animation**: Use setTimeout to defer
2. **Refresh SVG after layout changes**: Always call resize/updateBBox
3. **Test on small screens**: 320px width devices expose edge cases
4. **Verify visibility states**: Explicitly set display/visibility/opacity

### When Debugging "Disappearing" Elements
1. **Check computed styles**: May differ from inline styles
2. **Verify parent heights**: Parent with 0px height hides all children
3. **Test timing**: Element may be briefly visible then hidden
4. **Log dimensions**: offsetHeight/offsetWidth reveal actual sizes

## Future Enhancements

### Suggested Improvements
1. **Responsive drawer height**: Adjust drawer max height based on screen size
2. **Split-screen mode**: Option for side-by-side map/details on tablets
3. **Drawer peek mode**: Show partial map above drawer when fully open
4. **Smart height allocation**: Use viewport aspect ratio to optimize space

### Code Cleanup Opportunities
1. Consolidate height calculation logic into single source of truth
2. Create utility functions for SVG refresh operations
3. Use CSS Grid for more predictable layout behavior
4. Consider Intersection Observer API for visibility tracking
