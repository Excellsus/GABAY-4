# Entrance Stair Exclusivity Implementation

## Overview
This document explains how entrance points now follow the same stair exclusivity rules as doorpoints, ensuring proper navigation through restricted paths.

## Problem Statement

### Issue 1: Direct Path Bypass
When scanning entrance_west_1 (on path1 with stair restrictions), navigating to rooms on path2 would bypass the required stair transitions, creating a direct route that violated path access rules.

**Example:**
- Scan entrance_west_1 → nearestPathId: path1
- Click room on path2
- **Before Fix**: Direct route from path1 → path2 (WRONG)
- **After Fix**: Route goes path1 → west stairs → path2 (CORRECT)

### Issue 2: Cross-Floor Entrance Selection
When scanning an entrance on Floor 1 and switching to Floor 2, the entrance would either:
- Not appear in the "From" dropdown (unusable)
- Appear but create invalid routes

## Solution Architecture

### 1. Virtual Entrance Room Creation (pathfinding.js)

#### The Key Insight
The existing pathfinding system ALREADY handles stair exclusivity for doorpoints through:
- `shouldForceStairTransition(graph, startPathId, endPathId)` - checks if paths need stair transitions
- `calculateConstrainedSameFloorRoute()` - routes through stairs when needed

**The Problem**: These functions require room objects with `nearestPathId` properties.

**The Solution**: Create virtual room objects for entrances so they work with existing logic.

#### Implementation
**File**: `pathfinding.js` Lines 3347-3363

```javascript
// CRITICAL: Handle entrance as start location
if (!startRoom && typeof window !== 'undefined' && window.scannedStartEntrance) {
  const entrance = window.scannedStartEntrance;
  const entranceRoomId = entrance.roomId || `entrance_${entrance.id}_${entrance.floor}`;
  
  if (startRoomId === entranceRoomId) {
    // Create virtual room for entrance with proper nearestPathId
    startRoom = createVirtualRoomForEntrance(entrance);
    console.log('🚪 Created virtual entrance room for path transition check:', startRoom);
  }
}

if (startRoom && endRoom) {
  const startPathId = getPrimaryPathIdForRoom(startRoom);
  const endPathId = getPrimaryPathIdForRoom(endRoom);

  console.log('🛤️ Checking path transition requirements:', {
    startRoomId,
    endRoomId,
    startPathId,
    endPathId,
    isEntrance: startRoom.type === 'entrance'
  });

  // EXISTING LOGIC - unchanged
  if (shouldForceStairTransition(graph, startPathId, endPathId)) {
    console.log('⚠️ Stair transition required for entrance/room on restricted path');
    const constrainedRoute = await calculateConstrainedSameFloorRoute({
      floorNumber: startFloor,
      graph,
      startRoomId,
      endRoomId,
      startPathId,
      endPathId,
    });

    if (constrainedRoute) {
      return constrainedRoute;
    }
  }
}
```

**Effect**: 
- Entrances now participate in existing stair exclusivity checks
- No pathfinding logic modified - only extended to recognize entrances
- All existing doorpoint routing continues to work exactly as before

### 2. Cross-Floor Entrance Availability (explore.php)

**File**: `mobileScreen/explore.php` Lines 3341-3371

Updated entrance dropdown logic to allow cross-floor routing:

```javascript
const entranceFloor = entrance.floor || parseFloorFromRoomId(entrance.roomId);

// Show floor info if entrance is on different floor than destination
if (entranceFloor !== currentFloor) {
  defaultStart.textContent = entrance.label + ` 🚪 (YOU ARE HERE - Floor ${entranceFloor})`;
  console.log(`🚪 Entrance ${entrance.label} (Floor ${entranceFloor}) available for cross-floor routing to Floor ${currentFloor}`);
} else {
  defaultStart.textContent = entrance.label + ' 🚪 (YOU ARE HERE)';
  console.log('🚪 Pathfinding modal: Pre-selected entrance as start:', entrance.label, 'on floor', entranceFloor);
}
```

**UX Behavior**:
- **Same Floor**: "West Entrance 🚪 (YOU ARE HERE)"
- **Different Floor**: "West Entrance 🚪 (YOU ARE HERE - Floor 1)"

## Configuration Requirements

### Floor Graph JSON Structure

Entrances in `floor_graph.json`:
```json
{
  "pathAccessRules": {
    "path1": {
      "transitionStairKeys": ["west"],
      "enforceTransitions": true
    }
  },
  "entrances": [
    {
      "id": "entrance_west_1",
      "label": "West Entrance",
      "type": "entrance",
      "floor": 1,
      "x": 70,
      "y": 340,
      "nearestPathId": "path1"  // Links to restricted path
    }
  ]
}
```

### Key Properties
- **nearestPathId**: Must match a path with `pathAccessRules`
- **floor**: Required for cross-floor routing detection
- **roomId**: Auto-generated as `entrance_{id}_{floor}` if not provided

## Testing Scenarios

### Test 1: Same-Floor Restricted Path
**Setup**: 
- Scan entrance_west_1 (path1 with west stair restriction)
- Navigate to room-12-1 (path2)

**Expected**:
```
Console Output:
🚪 Entrance entrance_west_1 on restricted path path1 - requires stairs: ["west"]
🚫 Cannot route directly from path1 to path2 - stair transition required
⚠️ Stair transition required for entrance/room on restricted path
✅ Route segments:
  1. Floor 1: Proceed to West Stair
  2. Use West Stair to reach Floor 2
  3. Floor 2: Transition across landing
  4. Return via West Stair to Floor 1
  5. Floor 1: Continue to destination
```

### Test 2: Cross-Floor Navigation
**Setup**:
- Scan entrance_main_1 (Floor 1)
- Switch to Floor 2
- Navigate to room-12-2 (Floor 2)

**Expected**:
```
Dropdown shows: "Main Entrance 🚪 (YOU ARE HERE - Floor 1)"
Route uses stairs to reach Floor 2
```

### Test 3: Same Path (No Restriction)
**Setup**:
- Scan entrance_west_1 (path1)
- Navigate to room-1-1 (also path1)

**Expected**:
```
Direct route within path1 (no stair transition)
```

## Console Logging

### Key Messages

**Stair Restriction Detection**:
```
🚪 Entrance entrance_west_1 on restricted path path1 - requires stairs: ["west"]
```

**Path Validation**:
```
🚫 Cannot route directly from path1 to path2 - stair transition required
```

**Virtual Room Creation**:
```
🚪 Created virtual entrance room for path transition check: {roomId, nearestPathId, ...}
```

**Path Transition Check**:
```
🛤️ Checking path transition requirements: {
  startRoomId: "entrance_west_1_1",
  endRoomId: "room-12-1",
  startPathId: "path1",
  endPathId: "path2",
  isEntrance: true
}
```

**Constrained Route**:
```
⚠️ Stair transition required for entrance/room on restricted path
```

## Implementation Details

### Function Call Hierarchy

```
activateRouteBetweenRooms(startRoomId, endRoomId)
  └─> calculateMultiFloorRoute(startRoomId, endRoomId)
       ├─> [Same Floor Check]
       │    ├─> createVirtualRoomForEntrance() // If entrance
       │    ├─> shouldForceStairTransition(graph, startPathId, endPathId)
       │    └─> calculateConstrainedSameFloorRoute() // If restricted
       │         ├─> calculateSingleFloorRoute(start → stair1)
       │         ├─> calculateSingleFloorRoute(stair1 → stair2 on Floor 2)
       │         └─> calculateSingleFloorRoute(stair2 → end)
       └─> [Multi-Floor Logic for different floors]
            └─> Uses entrance floor as starting point
```

### Edge Cases Handled

1. **Entrance Not in graph.rooms**: Virtual room created from `window.scannedStartEntrance`
2. **Cross-Floor with Restrictions**: Both entrance exclusivity and stair exclusivity applied
3. **Multiple Stair Options**: System selects best stair based on path access rules
4. **Entrance on Same Path as Destination**: Direct route allowed (no restriction)

## Related Files

- **pathfinding.js**: Core routing logic with entrance support
- **mobileScreen/explore.php**: Entrance dropdown and QR scan handling
- **floor_graph.json**: Path access rules and entrance definitions
- **ENTRANCE_EXCLUSIVITY_GUIDE.md**: General entrance exclusivity documentation
- **STAIR_EXCLUSIVITY_GUIDE.md**: Stair transition system documentation

## Known Limitations

1. **Entrance Access Rules**: Currently empty (`entranceAccessRules: {}`), but infrastructure is ready for implementation
2. **Multi-Entrance Paths**: If multiple entrances exist on same restricted path, all follow same stair rules
3. **Admin Panel**: Entrance position updates don't automatically regenerate QR codes with new coordinates

## Future Enhancements

1. Implement entrance-specific path restrictions via `entranceAccessRules`
2. Add entrance exclusivity UI in admin panel
3. Support entrance-to-entrance routing (currently entrance-to-room only)
4. Add visual indicators on SVG for restricted entrance paths

## Troubleshooting

### Route Goes Direct (Ignoring Stairs)
**Check**:
1. Verify `pathAccessRules` has `enforceTransitions: true` for entrance's path
2. Console shows `🚪 Entrance on restricted path` message
3. `window.scannedStartEntrance` is set correctly

### Entrance Not in Dropdown
**Check**:
1. Entrance has `floor` property in database
2. `parseFloorFromRoomId()` returns valid floor number
3. Console shows entrance dropdown messages

### Wrong Stair Used
**Check**:
1. `pathAccessRules.transitionStairKeys` matches available stairs
2. Stair has correct `stairKey` and `connectsTo` floors
3. `calculateConstrainedSameFloorRoute` finds valid stair path

## Related Documentation
- [ENTRANCE_POSITION_UPDATE_FIX.md](./ENTRANCE_POSITION_UPDATE_FIX.md) - Position persistence system
- [NAVIGATION_CONFIG_GUIDE.md](./NAVIGATION_CONFIG_GUIDE.md) - Overall pathfinding configuration
- [STAIR_EXCLUSIVITY_GUIDE.md](./STAIR_EXCLUSIVITY_GUIDE.md) - Stair restriction patterns
