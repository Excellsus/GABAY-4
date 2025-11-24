# Entrance Dropdown Persistence Fix

## Problem
After scanning an entrance QR code, the entrance appeared as "YOU ARE HERE" in the pathfinding modal. However, after switching floors and clicking another room, the entrance disappeared from the dropdown list.

## Root Cause
The `openPathfindingModalWithDestination()` function tried to collect entrances from `window.floorGraphCache[floor]`, but the cache was never populated. The floor graphs were loaded into `window.floorGraph` but not stored in the cache.

## Solution
Implemented a three-part fix:

### 1. Cache Floor Graphs When Loaded
**File**: `mobileScreen/explore.php` (lines ~4830-4840)

```javascript
// Store floor graph in cache so entrance dropdown can access it
if (!window.floorGraphCache) {
  window.floorGraphCache = {};
}
window.floorGraphCache[floorNumber] = graphData;
```

### 2. Preload All Floor Graphs on Page Load
**File**: `mobileScreen/explore.php` (lines ~4740-4775)

```javascript
async function preloadAllFloorGraphs() {
  console.log('🔄 Preloading all floor graphs for entrance dropdown...');
  
  if (!window.floorGraphCache) {
    window.floorGraphCache = {};
  }
  
  for (let floor = 1; floor <= 3; floor++) {
    if (window.floorGraphCache[floor]) continue;
    
    const promise = fetch(floorGraphs[floor])
      .then(response => response.json())
      .then(graphData => {
        window.floorGraphCache[floor] = graphData;
        console.log(`✅ Preloaded floor ${floor} graph`);
      });
  }
}
```

Called in DOMContentLoaded:
```javascript
preloadAllFloorGraphs().then(() => {
  console.log('✅ All floor graphs preloaded and cached');
});
```

### 3. Enhanced Logging for Debugging
**File**: `mobileScreen/explore.php` (lines ~3410-3430)

Added detailed console logs to verify entrance collection:
- Cache status check
- Per-floor entrance discovery
- Total entrance count
- Error conditions

## Testing Steps

1. **Scan Entrance QR Code**
   - Open mobile explore.php
   - Scan `entrance_west_1` QR code
   - Verify: "West Entrance 🚪 (YOU ARE HERE)" appears in modal

2. **Switch Floors**
   - Click Floor 2 button
   - Click any room to open pathfinding modal
   - Verify: "West Entrance 🚪 (YOU ARE HERE - Floor 1)" still appears in "From" dropdown

3. **Check All Entrances Available**
   - Open pathfinding modal on any floor
   - Verify "From" dropdown shows:
     - Main Entrance 🚪 (Floor 1)
     - West Entrance 🚪 (Floor 1)
     - Main Entrance 🚪 (Floor 2)
     - West Entrance 🚪 (Floor 2)
     - Main Entrance 🚪 (Floor 3)
     - West Entrance 🚪 (Floor 3)

4. **Console Verification**
   - Check console for:
     - `✅ All floor graphs preloaded and cached`
     - `🚪 Found X entrance options for dropdown`
     - `✅ Found Y entrances on floor Z`

## Expected Console Output

```
🔄 Preloading all floor graphs for entrance dropdown...
✅ Preloaded floor 1 graph (2 entrances)
✅ Preloaded floor 2 graph (2 entrances)
✅ Preloaded floor 3 graph (2 entrances)
✅ All floor graphs preloaded and cached

[User clicks room]
🔍 Collecting entrances from floor graph cache: {1: {…}, 2: {…}, 3: {…}}
✅ Found 2 entrances on floor 1
✅ Found 2 entrances on floor 2
✅ Found 2 entrances on floor 3
🚪 Found 5 entrance options for dropdown (excluding scanned entrance)
📍 Total location options: 45 (5 entrances + 40 offices)
```

## Key Changes Summary

| File | Lines | Change |
|------|-------|--------|
| explore.php | ~4740-4775 | Added `preloadAllFloorGraphs()` function |
| explore.php | ~4835 | Store graph in `window.floorGraphCache[floor]` |
| explore.php | ~5736 | Call `preloadAllFloorGraphs()` on page load |
| explore.php | ~3410-3430 | Enhanced entrance collection with logging |

## Related Issues Fixed

- ✅ Entrance disappeared after floor switch
- ✅ Only current floor's entrance visible in dropdown
- ✅ Cross-floor routing not showing all entrance options
- ✅ Cache not properly populated for entrance access

## Previous Implementation

Before this fix, the system:
- Loaded floor graphs only when switching floors
- Stored graphs in `window.floorGraph` only
- Entrance dropdown had no access to other floors' data
- Cache was populated only by pathfinding.js `ensureFloorGraphLoaded()`

## Current Implementation

Now the system:
- Preloads all floor graphs on page load
- Stores graphs in both `window.floorGraph` AND `window.floorGraphCache[floor]`
- Entrance dropdown accesses all floors via cache
- Cache is populated immediately on page load and maintained during floor switches

## Architecture Notes

**Data Flow**:
1. Page loads → `preloadAllFloorGraphs()` → Cache all 3 floors
2. User switches floor → `loadFloorMap()` → Update cache for that floor
3. User clicks room → `openPathfindingModalWithDestination()` → Read from cache
4. Modal opens → All entrances from all floors available in dropdown

**Cache Hierarchy**:
```javascript
window.floorGraphCache = {
  1: { rooms: {...}, entrances: [...], walkablePaths: [...] },
  2: { rooms: {...}, entrances: [...], walkablePaths: [...] },
  3: { rooms: {...}, entrances: [...], walkablePaths: [...] }
}
```

## Debugging Commands

If entrances still not appearing:

```javascript
// Check cache status
console.log('Cache:', window.floorGraphCache);

// Check specific floor
console.log('Floor 1 entrances:', window.floorGraphCache[1]?.entrances);

// Check scanned entrance
console.log('Scanned entrance:', window.scannedStartEntrance);

// Force preload
await preloadAllFloorGraphs();
```

## Related Documentation

- `ENTRANCE_STAIR_EXCLUSIVITY_FIX.md` - Virtual entrance room implementation
- `ENTRANCE_STAIR_IMPLEMENTATION_SUMMARY.md` - Overall entrance system architecture
- `QR_SCAN_PATHFINDING_INTEGRATION.md` - QR scan to pathfinding workflow
