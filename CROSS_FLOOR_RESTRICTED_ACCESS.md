# Cross-Floor Restricted Access - Final Implementation

## Summary

Successfully implemented a **cross-floor routing constraint** for Floor 3's rooms 4, 5, and 6. These rooms now use **Floor 2's East Stairwell** (`stair_east_2-2`) as their mandatory entry/exit point instead of having direct door points on Floor 3.

## How It Works

### The Core Concept

Rooms 4, 5, and 6 on Floor 3 have **NO door points** on Floor 3. Instead, the pathfinding system routes to/from **Floor 2's East Stair landing** at coordinates (1820, 180).

Think of it like this:
- **Normal Room**: Has doorPoints on its own floor → pathfinding routes directly to the door
- **Restricted Room (4, 5, 6)**: Has NO doorPoints → pathfinding routes to Floor 2 stair landing → visitors manually take stairs up/down

### Configuration in JSON

**floor_graph_3.json** (Floor 3):
```json
{
  "restrictedAccessRules": {
    "room-4-3": {
      "mandatoryEntryPoint": "stair_east_2-2",  // Reference to Floor 2 room
      "entryPointFloor": 2,                      // Specifies it's on Floor 2
      "description": "Room 4 must always use the East Stair (Floor 2) as entry/exit point"
    }
  },
  "rooms": {
    "room-4-3": {
      "doorPoints": [],  // EMPTY - no direct access on Floor 3
      "nearestPathId": "path_central_exclusive_floor3",
      "style": {
        "pointMarker": {
          "color": "orange"  // Visual indicator of restricted access
        }
      }
    }
  }
}
```

**floor_graph_2.json** (Floor 2 - unchanged):
```json
{
  "rooms": {
    "stair_east_2-2": {
      "type": "stair",
      "stairKey": "east",
      "doorPoints": [{"x": 1820, "y": 180, "nearestPathId": "path10_floor2"}],
      "connectsTo": [1, 3]  // This stair connects Floor 1, 2, and 3
    }
  }
}
```

## Routing Examples

### Example 1: From Floor 1 to Room 5 (Floor 3)
```
User clicks: room-1-1 → room-5-3

System logic:
1. Detects room-5-3 has restriction → entry point is "stair_east_2-2" on Floor 2
2. Substitutes destination: room-1-1 → stair_east_2-2 (Floor 2)
3. Calculates route from Floor 1 to Floor 2

Result route:
- Floor 1: room-1-1 → nearest stair
- Stair transition: Floor 1 → Floor 2
- Floor 2: stair landing → stair_east_2-2 (END at coordinates 1820, 180)

User experience:
- Follow digital route to Floor 2 East Stair landing
- Manually take the stairs up one floor to Floor 3
- Access Room 5
```

### Example 2: From Room 4 (Floor 3) to Floor 2
```
User clicks: room-4-3 → room-12-2

System logic:
1. Detects room-4-3 has restriction → entry point is "stair_east_2-2" on Floor 2
2. Substitutes start: stair_east_2-2 (Floor 2) → room-12-2
3. Calculates route on Floor 2

Result route:
- Floor 2: START at stair_east_2-2 → navigate to room-12-2

User experience:
- Come down from Room 4 (Floor 3) to the East Stair landing on Floor 2
- Follow route from stair landing to Room 12
```

### Example 3: Between Two Restricted Rooms
```
User clicks: room-4-3 → room-6-3

System logic:
1. Both rooms use same entry point: stair_east_2-2 on Floor 2
2. Route = single point (the shared entry)
3. Distance = 0

Result route:
- Single point: stair_east_2-2 (Floor 2)

User experience:
- Both rooms accessed from the same stair landing
- User goes to Floor 2 East Stair, then takes stairs to Floor 3
```

## Code Changes

### 1. Enhanced `getMandatoryEntryPoint()`
```javascript
function getMandatoryEntryPoint(graph, roomId) {
    const rule = getRestrictedAccessRule(graph, roomId);
    
    // Check if entry point is on a different floor
    if (rule.entryPointFloor && rule.entryPointFloor !== graph.floorNumber) {
        const entryFloorGraph = floorGraphCache[rule.entryPointFloor];
        const entryRoom = entryFloorGraph.rooms[rule.mandatoryEntryPoint];
        
        return {
            roomId: rule.mandatoryEntryPoint,
            room: entryRoom,
            floor: rule.entryPointFloor  // NEW: track which floor
        };
    }
    
    // Normal same-floor entry point...
}
```

### 2. Enhanced `calculateMultiFloorRoute()`
```javascript
// Check if end room has restricted access
const endRestriction = getRestrictedAccessRule(endGraph, endRoomId);
if (endRestriction) {
    const entryPoint = getMandatoryEntryPoint(endGraph, endRoomId);
    
    // If entry point is on a different floor
    if (entryPoint.floor !== endFloor) {
        await ensureFloorGraphLoaded(entryPoint.floor);
        
        // Route to the entry point floor instead
        return calculateMultiFloorRoute(startRoomId, entryPoint.roomId);
    }
}
```

## Testing Instructions

### Test 1: Navigate to Restricted Room
1. Open the floor plan system
2. Select any room on Floor 1 or 2 as start
3. Select room-4-3, room-5-3, or room-6-3 as destination
4. **Expected**: Route ends at Floor 2, East Stair (x: 1820, y: 180)
5. **Console should show**: `"Multi-floor: End room room-X-3 restricted, using entry point stair_east_2-2 on floor 2"`

### Test 2: Navigate from Restricted Room
1. Select room-5-3 as start
2. Select any room on Floor 1 or 2 as destination
3. **Expected**: Route starts at Floor 2, East Stair
4. **Console should show**: `"Multi-floor: Start room room-5-3 restricted, using entry point stair_east_2-2 on floor 2"`

### Test 3: Between Restricted Rooms
1. Select room-4-3 as start
2. Select room-6-3 as destination
3. **Expected**: Single point route at Floor 2 East Stair
4. **Distance**: 0
5. **Console should show**: `"Both rooms use same mandatory entry: stair_east_2-2"`

### Test 4: Visual Verification
1. Load Floor 3 floor plan
2. **Look for**: Rooms 4, 5, and 6 marked in ORANGE (not green)
3. **Verify**: No door point markers on these rooms
4. Load Floor 2 floor plan
5. **Look for**: Normal stair marker for stair_east_2-2 at coordinates (1820, 180)

## Files Modified

1. **floor_graph_3.json**
   - Added `restrictedAccessRules` with cross-floor references
   - Removed doorPoints from room-4-3, room-5-3, room-6-3 (set to `[]`)
   - Changed room colors to orange

2. **pathfinding.js**
   - Enhanced `getMandatoryEntryPoint()` to handle cross-floor entries
   - Enhanced `calculateMultiFloorRoute()` with cross-floor logic
   - Enhanced `haveSameMandatoryEntry()` to check floor consistency
   - Added `entryPoint.floor` tracking

3. **FLOOR3_RESTRICTED_ACCESS_SUMMARY.md**
   - Updated documentation with cross-floor explanation
   - Added routing scenarios with floor transitions
   - Clarified visual indicators

## Why This Design?

This approach models real-world building access control where:
- Certain floors/areas require controlled entry points
- Security checkpoints are on specific floors
- Visitors must pass through designated stairwells
- Navigation systems guide users to the correct entry floor
- Physical access (taking stairs) is handled by the user

The system provides **routing guidance** to the correct entry point, but doesn't simulate the physical stair traversal - that's left to the visitor.

## Key Takeaways

✅ Rooms 4, 5, 6 on Floor 3 have **no doorPoints** - they're "door-less" in the pathfinding graph

✅ All routes to/from these rooms use **Floor 2's stair_east_2-2** as the endpoint

✅ The `entryPointFloor` property enables **cross-floor entry point references**

✅ Orange room markers visually indicate **restricted access**

✅ This is **data-driven** - no hard-coded floor/room logic, purely JSON configuration

✅ System is **extensible** - can add more cross-floor restrictions using the same pattern
