# Stair Group Connection Enforcement

## Overview
This update ensures that stairs in the pathfinding system are **strictly connected to their defined counterparts** using explicit `stairGroup` identifiers. Each stair belongs to a specific group and can **only transition to other stairs in that same group**.

## Stair Group Definitions

### Group Mapping (Floor 1 ↔ Floor 2 ↔ Floor 3)

| Group ID | Floor 1 | Floor 2 | Floor 3 | Description |
|----------|---------|---------|---------|-------------|
| `west_1` | `stair_west_1-1` | `stair_west_1-2` | — | West Stair Group 1 (path1) |
| `west_2` | `stair_west_2-1` | `stair_west_2-2` | — | West Stair Group 2 (path2) |
| `master_1` | `stair_master_1-1` | `stair_master_1-2` | — | Central Stair Group 1 |
| `master_2` | `stair_master_2-1` | `stair_master_2-2` | — | Central Stair Group 2 |
| `east_1` | `stair_east_1-1` | `stair_east_1-2` | — | East Stair Group 1 |
| `east_2` | `stair_east_2-1` | `stair_east_2-2` | `stair_east_1-3` | East Stair Group 2 (to 3F) |
| `thirdFloor_1` | — | `stair_thirdFloor_1-2` | `stair_thirdFloor_1-3` | Third Floor Group 1 |
| `thirdFloor_2` | — | `stair_thirdFloor_2-2` | `stair_thirdFloor_2-3` | Third Floor Group 2 |

## Allowed Transitions

### ✅ Valid Connections (Within Same Group)

```
west_1:
  stair_west_1-1 (F1) ↔ stair_west_1-2 (F2)

west_2:
  stair_west_2-1 (F1) ↔ stair_west_2-2 (F2)

master_1:
  stair_master_1-1 (F1) ↔ stair_master_1-2 (F2)

master_2:
  stair_master_2-1 (F1) ↔ stair_master_2-2 (F2)

east_1:
  stair_east_1-1 (F1) ↔ stair_east_1-2 (F2)

east_2:
  stair_east_2-1 (F1) ↔ stair_east_2-2 (F2) ↔ stair_east_1-3 (F3)

thirdFloor_1:
  stair_thirdFloor_1-2 (F2) ↔ stair_thirdFloor_1-3 (F3)

thirdFloor_2:
  stair_thirdFloor_2-2 (F2) ↔ stair_thirdFloor_2-3 (F3)
```

### ❌ Invalid Connections (Prevented by stairGroup)

```
stair_west_1-1 ⇏ stair_west_2-2     (different groups: west_1 ≠ west_2)
stair_master_1-1 ⇏ stair_master_2-2 (different groups: master_1 ≠ master_2)
stair_east_1-1 ⇏ stair_east_2-2     (different groups: east_1 ≠ east_2)
stair_west_1-1 ⇏ stair_master_1-2   (different groups: west_1 ≠ master_1)
```

## Implementation Details

### 1. Floor Graph Changes

Added `stairGroup` property to all stair definitions:

**floor_graph.json (Floor 1)**
```json
"stair_west_1-1": {
    "type": "stair",
    "stairKey": "west",
    "stairGroup": "west_1",  // ← NEW
    "nearestPathId": "path1",
    "connectsTo": [2]
}
```

**floor_graph_2.json (Floor 2)**
```json
"stair_west_1-2": {
    "type": "stair",
    "stairKey": "west",
    "stairGroup": "west_1",  // ← NEW
    "nearestPathId": "path1_floor2",
    "connectsTo": [1, 3]
}
```

**floor_graph_3.json (Floor 3)**
```json
"stair_thirdFloor_1-3": {
    "type": "stair",
    "stairKey": "west",
    "stairGroup": "thirdFloor_1",  // ← NEW
    "nearestPathId": "path3_floor3",
    "connectsTo": [2]
}
```

### 2. Pathfinding Logic Update

Modified `areStairNodesCompatible()` in `pathfinding.js`:

```javascript
function areStairNodesCompatible(nodeA, nodeB) {
    if (!nodeA || !nodeB) {
        return false;
    }

    // Primary check: stairGroup must match if both nodes have it defined
    const groupA = nodeA.room && nodeA.room.stairGroup;
    const groupB = nodeB.room && nodeB.room.stairGroup;
    
    if (groupA && groupB) {
        // If both have stairGroup defined, they MUST match
        return groupA === groupB;
    }

    // Fallback: if stairGroup is not defined, use existing logic
    // (for backward compatibility)
    ...
}
```

### 3. Automatic Enforcement

The stair group check is automatically enforced by:
- `findStairTransitionsBetweenFloors()` - filters transitions using `areStairNodesCompatible()`
- `calculateMultiFloorRoute()` - only considers compatible stair transitions
- `calculateConstrainedSameFloorRoute()` - respects group restrictions

## Benefits

✅ **Explicit Control**: Each stair group is explicitly defined, not inferred  
✅ **Prevents Misrouting**: System cannot mix stairs from different groups  
✅ **Clear Configuration**: Easy to see which stairs connect in floor graphs  
✅ **Future-Proof**: New stairs can be added with clear group assignments  
✅ **Backward Compatible**: Fallback logic preserves existing behavior if `stairGroup` not defined  

## Testing Scenarios

### Scenario 1: Path1 West Stair Exclusivity
**Route**: Room 3 (F1, path1) → Room 7 (F1, path1)

**Expected**:
- Uses `stair_west_1-1` (group: `west_1`)
- Transitions to `stair_west_1-2` (group: `west_1`)
- ❌ Cannot use `stair_west_2-2` (group: `west_2`)

### Scenario 2: East Stair to Floor 3
**Route**: Room 14 (F1) → Room 6 (F3)

**Expected**:
- Uses `stair_east_2-1` (group: `east_2`)
- Transitions to `stair_east_2-2` (group: `east_2`)
- Continues to `stair_east_1-3` (group: `east_2`)
- ❌ Cannot use `stair_east_1-2` (group: `east_1`)

### Scenario 3: Third Floor Central Stairs
**Route**: Room X (F2) → Room 3 (F3)

**Expected**:
- Uses `stair_thirdFloor_1-2` (group: `thirdFloor_1`)
- Transitions to `stair_thirdFloor_1-3` (group: `thirdFloor_1`)
- ❌ Cannot use `stair_thirdFloor_2-3` (group: `thirdFloor_2`)

## Files Modified

1. **floor_graph.json** - Added `stairGroup` to all Floor 1 stairs
2. **floor_graph_2.json** - Added `stairGroup` to all Floor 2 stairs
3. **floor_graph_3.json** - Added `stairGroup` to all Floor 3 stairs
4. **pathfinding.js** - Updated `areStairNodesCompatible()` to enforce groups

## Migration Notes

- All existing stairs now have `stairGroup` defined
- The pathfinding system prioritizes `stairGroup` over legacy matching
- Fallback logic ensures compatibility if `stairGroup` is missing
- No changes required to existing routing calls

## Validation

Run syntax check:
```bash
node --check pathfinding.js
```

Expected: No errors (✅ Passed)

## Summary

The pathfinding system now **strictly enforces stair group connections**. Each stair belongs to a specific group (e.g., `west_1`, `master_2`, `east_2`) and can **only transition to other stairs in that same group**. This prevents the system from selecting unrelated stairs and ensures consistent, predictable routing behavior across all floors.
