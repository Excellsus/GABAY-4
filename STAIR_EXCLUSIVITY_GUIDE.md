# Stair Exclusivity & Variant Matching Guide

## Overview

The pathfinding system enforces **stair exclusivity rules** to ensure that rooms connected to specific stairwells maintain consistent routing through those designated stairs, even during cross-floor navigation.

## Problem Solved

Previously, when navigating from rooms restricted to `stair_west_1-1` (variant 1) on Floor 1 to another floor, the pathfinder would incorrectly select `stair_west_2-2` (variant 2) on Floor 2, breaking the exclusivity rule.

## Solution Components

### 1. Stair Group Property

Each stair node now has a `stairGroup` property that identifies which physical stairwell it belongs to:

```json
{
  "stair_west_1-1": {
    "type": "stair",
    "stairKey": "west",
    "stairGroup": "west_1",  // <-- Identifies this as variant 1
    "connectsTo": [2]
  },
  "stair_west_2-1": {
    "type": "stair",
    "stairKey": "west",
    "stairGroup": "west_2",  // <-- Different variant
    "connectsTo": [2]
  }
}
```

### 2. Cross-Floor Consistency

Floor 2 stairs have been updated with matching `stairGroup` values:

- `stair_west_1-2` → `stairGroup: "west_1"` (connects to `stair_west_1-1`)
- `stair_west_2-2` → `stairGroup: "west_2"` (connects to `stair_west_2-1`)
- `stair_master_1-2` → `stairGroup: "master_1"`
- `stair_master_2-2` → `stairGroup: "master_2"`
- `stair_east_1-2` → `stairGroup: "east_1"`
- `stair_east_2-2` → `stairGroup: "east_2"`

### 3. Enhanced Compatibility Logic

The `areStairNodesCompatible()` function now enforces strict matching:

**Priority 1: StairGroup Matching**
- If both stairs have `stairGroup` defined, they MUST match exactly
- Example: `west_1` only connects to `west_1`, never to `west_2`

**Priority 2: Variant Matching**
- If only one has `stairGroup`, or neither do, the system parses the room ID
- Both `stairKey` AND `variant` must match
- Example: `stair_west_1-1` (key=west, variant=1) only connects to `stair_west_1-2` (key=west, variant=1)

**Priority 3: Strict Rejection**
- No fallback to matching by `stairKey` alone
- Prevents `stair_west_1-1` from connecting to `stair_west_2-2` just because both have `stairKey: "west"`

### 4. Multi-Floor Variant Enforcement

The `calculateMultiFloorRoute()` function now:

1. **Detects required variants** from path access rules
   ```javascript
   const requiredStartVariant = getRequiredStairVariantForPath(startGraph, startPathId);
   // Returns: { stairKey: "west", variant: "1" }
   ```

2. **Filters all transitions** to match the required variant
   ```javascript
   // For EVERY transition step, not just first/last
   if (requiredStartVariant && transition.stairKey === requiredStartVariant.stairKey) {
       const startParsed = parseStairId(transition.startNode.roomId);
       if (startParsed.variant !== requiredStartVariant.variant) {
           return false; // Reject this transition
       }
   }
   ```

3. **Logs detailed debugging info** to help diagnose routing decisions

## Path Access Rules

Rooms on `path1` (Floor 1) have this rule:

```json
{
  "pathAccessRules": {
    "path1": {
      "transitionStairKeys": ["west"],
      "enforceTransitions": true
    }
  }
}
```

This means:
- Rooms on `path1` MUST use `west` stairs for transitions
- The system identifies `stair_west_1-1` as the connected stair (variant 1)
- Cross-floor routing will ONLY use `stair_west_1-2` on Floor 2 (matching variant)

## Testing Scenarios

### Test 1: Same Variant Cross-Floor
**Route:** Room 1 (Floor 1, path1) → Room 3 (Floor 2)

**Expected Behavior:**
1. Start at `room-1-1` on Floor 1
2. Navigate to `stair_west_1-1` (variant 1)
3. Take west stair to Floor 2
4. Arrive at `stair_west_1-2` (variant 1, NOT variant 2)
5. Navigate to `room-3-2`

**Console Logs to Verify:**
```
Multi-floor pathfinding constraints: {
  requiredStartVariant: { stairKey: "west", variant: "1" }
}
Accepting transition stair_west_1-1 -> stair_west_1-2
```

### Test 2: General Rooms (No Restriction)
**Route:** Room 12 (Floor 1, path2) → Room 7 (Floor 2)

**Expected Behavior:**
- No variant restriction (path2 has no exclusive rules)
- Can use any available stair (central, east)
- System chooses optimal route based on distance

### Test 3: Within Same Floor
**Route:** Room 1 (Floor 1) → Room 2 (Floor 1)

**Expected Behavior:**
- No cross-floor transition needed
- Uses `path1` exclusively
- No stair variant filtering applied

### Test 4: Rejected Transition (Invalid Variant)
**Route:** Room 1 (Floor 1, path1) → Room 3 (Floor 2)

**If system incorrectly tried to use stair_west_2-2:**
```
Rejecting transition stair_west_2-2: variant "2" doesn't match required "1"
No allowable transitions remain after applying path access constraints
```

## Debugging

Enable console logging to see routing decisions:

```javascript
console.log('Multi-floor pathfinding constraints:', {
    startRoomId,
    endRoomId,
    constrainedStairKeys,
    requiredStartVariant,
    requiredEndVariant
});
```

Look for these key messages:
- ✅ `Accepting transition stair_west_1-1 -> stair_west_1-2`
- ❌ `Rejecting transition stair_west_2-2: variant "2" doesn't match required "1"`

## Architecture Summary

```
Floor 1                      Floor 2
┌─────────────┐             ┌─────────────┐
│ room-1-1    │             │ room-3-2    │
│ (path1)     │             │             │
└──────┬──────┘             └──────┬──────┘
       │                            │
       ▼                            ▼
┌─────────────┐             ┌─────────────┐
│stair_west_1-1│   Cross    │stair_west_1-2│
│variant: "1" ├────Floor───►│variant: "1" │
│group: west_1│   Match!    │group: west_1│
└─────────────┘             └─────────────┘

       ✗ BLOCKS              
       │
       ▼
┌─────────────┐             ┌─────────────┐
│stair_west_2-1│            │stair_west_2-2│
│variant: "2" │   ✗ NOT    │variant: "2" │
│group: west_2│  ALLOWED   │group: west_2│
└─────────────┘             └─────────────┘
```

## Key Files Modified

1. **floor_graph_2.json**: Added `stairGroup` properties to all stair nodes
2. **pathfinding.js**: 
   - Enhanced `areStairNodesCompatible()` with strict variant matching
   - Updated `calculateMultiFloorRoute()` to enforce variant consistency across ALL transitions
   - Added comprehensive console logging for debugging

## Maintenance Notes

When adding new stairs:
1. Always include `stairGroup` property matching the variant
2. Format: `{stairKey}_{variant}` (e.g., `"west_1"`, `"east_2"`)
3. Ensure cross-floor stairs have matching `stairGroup` values
4. Update `pathAccessRules` if the stair is exclusive to certain paths
