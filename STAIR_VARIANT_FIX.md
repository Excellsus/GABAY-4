# Stair Variant Exclusivity Fix

## Problem
The pathfinding system was incorrectly defaulting to `stair_west_2-2` on Floor 2 when navigating from rooms restricted to `stair_west_1-1` and `path1`. This violated the stair exclusivity rules defined in the floor graph configuration.

## Root Cause
The multi-floor and same-floor constrained routing logic filtered transitions by **stair key** (e.g., "west") but did not enforce which **variant** (e.g., "1" vs "2") should be used. This meant that when multiple stairs shared the same key, the system could pick any variant, breaking exclusivity rules.

## Solution

### 1. Added `getRequiredStairVariantForPath()` Function
- Analyzes path access rules to determine which stair variant is required
- Examines connected stairs and returns the lowest variant number for the required stair key
- Returns `{stairKey: "west", variant: "1"}` for paths with enforced transitions

### 2. Updated `calculateMultiFloorRoute()`
- Identifies required stair variants for both start and end paths
- Filters transitions to enforce correct variants:
  - **First transition**: Must use the start room's required variant
  - **Last transition**: Must return via the start room's required variant
- Prevents fallback to incorrect stair variants

### 3. Updated `calculateConstrainedSameFloorRoute()`
- Applies the same variant filtering for same-floor routes that require stair transitions
- Ensures consistency between same-floor and multi-floor navigation

## Expected Behavior

### Room 3 (Floor 1, path1) → Room 7 (Floor 1, path1)
✅ **Correct flow:**
1. Start from Room 3 on `path1` (Floor 1)
2. Use `stair_west_1-1` (variant "1") to reach Floor 2
3. Arrive at `stair_west_1-2` (variant "1") on Floor 2
4. Traverse Floor 2 path and return via `stair_west_1-2`
5. Continue to Room 7 on Floor 1

❌ **Incorrect behavior (FIXED):**
- System was defaulting to `stair_west_2-2` (variant "2") on Floor 2
- This violated the path1 exclusivity rule requiring variant "1"

## Configuration Reference

### Floor 1 (floor_graph.json)
```json
"pathAccessRules": {
    "path1": {
        "transitionStairKeys": ["west"],
        "enforceTransitions": true
    }
}
```

- `stair_west_1-1`: nearestPathId = `path1`, variant = "1"
- `stair_west_2-1`: nearestPathId = `path2`, variant = "2"

### Floor 2 (floor_graph_2.json)
- `stair_west_1-2`: nearestPathId = `path1_floor2`, variant = "1"
- `stair_west_2-2`: nearestPathId = `path_floor2`, variant = "2"

## Testing
Run the test visualization:
```bash
node test_stair_variant.js
```

This demonstrates how stair IDs are parsed and shows the expected routing behavior.

## Files Modified
- `pathfinding.js`: Added variant enforcement logic to routing functions

## Impact
- ✅ Stair exclusivity is now fully respected
- ✅ Consistent behavior between same-floor and multi-floor navigation
- ✅ No more fallback to incorrect stair variants
- ✅ Path access rules are enforced throughout the entire route
