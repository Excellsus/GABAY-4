# Route Path Visibility Fix

## Problem
After hiding the static walkable paths, the animated dash array route paths stopped appearing when users selected two rooms for directions in `explore.php`. Additionally, all icons disappeared from `floorPlan.php` on all three floors.

## Root Cause
The issue was NOT caused by the CSS hiding rules for walkable paths. The actual problem was in `pathfinding.js`:

**Multiple functions were using `document.querySelector('svg')`** which selected the **legend button's SVG icon** instead of the **Capitol map SVG** in `explore.php`. This caused route paths to be drawn inside the legend button icon (invisible to users) rather than on the map.

Additionally, when the fix was initially applied using `#capitol-map-svg`, it broke `floorPlan.php` because that page uses a different SVG ID (`svg1`), causing all pathfinding visualizations to fail on the admin floor plan page.

## Functions Affected
The following functions in `pathfinding.js` were incorrectly targeting the wrong SVG:

1. **Line 486** - `renderActiveRouteForFloor()`
2. **Line 805** - Floor graph reload function
3. **Line 850** - `drawWalkablePath()`
4. **Line 1032** - `drawEntryPoints()`
5. **Line 1147** - Path clearing before drawing
6. **Line 3202** - `clearAllPaths()`
7. **Line 3488** - `drawCompletePath()` ← **Primary route drawing function**
8. **Line 3604** - `drawPathLines()` ← **Primary route drawing function**

## Solution - Part 1: Initial Fix (Broke floorPlan.php)
Changed all instances from:
```javascript
const svg = document.querySelector('svg');
```

To:
```javascript
const svg = document.querySelector('#capitol-map-svg');
```

This fixed `explore.php` but broke `floorPlan.php`.

## Solution - Part 2: Universal Fix (Final)
Created a helper function that works for both pages:

```javascript
// Helper function to get the correct SVG element (works for both floorPlan.php and explore.php)
function getCapitolSVG() {
    // First try capitol-map-svg (explore.php), then svg1 (floorPlan.php), then any svg
    return document.querySelector('#capitol-map-svg') || 
           document.querySelector('#svg1') || 
           document.querySelector('svg');
}
```

Then updated all 8 functions to use:
```javascript
const svg = getCapitolSVG();
```

This ensures the code works correctly on:
- ✅ **explore.php** (mobile visitor interface) - uses `#capitol-map-svg`
- ✅ **floorPlan.php** (admin floor plan editor) - uses `#svg1`
- ✅ Any future pages with different SVG IDs (fallback to first svg element)

## Files Modified
- **pathfinding.js**: 
  - Added `getCapitolSVG()` helper function at line ~12
  - Updated 8 instances to use the helper function

## Why This Wasn't Caught Earlier
During the initial SVG conflict fix, we focused on `explore.php` and updated 14+ instances there. However, we missed the `pathfinding.js` file which contains the core route drawing logic.

The legend button was added after the initial SVG selector fixes, creating a new "first SVG" in the DOM that broke the route visualization in `explore.php`.

When fixing `pathfinding.js` with hardcoded `#capitol-map-svg` selectors, we didn't test `floorPlan.php` which uses a different SVG ID, causing all icons to disappear there.

## Route Path Architecture
Route paths use separate groups from walkable paths:
- **Static walkable paths**: Inside `#walkable-path-group` (hidden via CSS in explore.php only)
- **Active route paths**: Inside `#path-highlight-group` and `#path-lines-group` (always visible)
- **Entry/door markers**: Inside `#entry-point-group` (always visible)
- **Panorama markers**: Dynamically created (always visible)

The CSS hiding rules for walkable paths were correctly scoped and did NOT affect route paths or markers.

## Testing
After this fix:
1. ✅ Static walkable paths remain hidden in explore.php
2. ✅ Route paths display correctly when selecting 2 rooms in explore.php
3. ✅ Dash array animations work properly in explore.php
4. ✅ Legend button functionality unaffected in explore.php
5. ✅ All icons (entry points, panoramas) visible in floorPlan.php
6. ✅ Walkable paths visible in floorPlan.php (admin needs to see them)
7. ✅ Both pages work with shared pathfinding.js file

## Related Documentation
- `LEGEND_BUTTON_SVG_CONFLICT_FIX.md` - Original SVG selector conflict fix for explore.php
- This fix completes the SVG selector migration across the entire codebase

## Date
January 2025 (Updated with universal fix)
