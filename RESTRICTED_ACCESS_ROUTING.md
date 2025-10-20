# Restricted Access Routing System

## Overview

The GABAY navigation system now supports **restricted access rules** for specific rooms that require routing through designated entry points. This is useful for rooms with controlled access, security requirements, or specific entry protocols.

## Configuration

### Floor Graph Structure

Restricted access rules are defined in the floor graph JSON files (e.g., `floor_graph_3.json`) using the `restrictedAccessRules` property:

```json
{
  "restrictedAccessRules": {
    "room-4-3": {
      "mandatoryEntryPoint": "entry-stair_east_2-2-0",
      "description": "Room 4 must always use the East Stair (Floor 2) entry point"
    },
    "room-5-3": {
      "mandatoryEntryPoint": "entry-stair_east_2-2-0",
      "description": "Room 5 must always use the East Stair (Floor 2) entry point"
    },
    "room-6-3": {
      "mandatoryEntryPoint": "entry-stair_east_2-2-0",
      "description": "Room 6 must always use the East Stair (Floor 2) entry point"
    }
  }
}
```

### Virtual Entry Points

Entry points can be defined as special "virtual entry" rooms that represent access control points:

```json
{
  "entry-stair_east_2-2-0": {
    "type": "virtual_entry",
    "label": "East Stair Entry Point (Floor 2 to Floor 3)",
    "description": "Mandatory entry point for restricted rooms 4, 5, and 6 on floor 3",
    "x": 1820,
    "y": 180,
    "linkedStair": "stair_east_2-2",
    "targetFloor": 3,
    "nearestPathId": "path_central_exclusive_floor3",
    "doorPoints": [{"x": 1810, "y": 174, "nearestPathId": "path_central_exclusive_floor3"}]
  }
}
```

## Routing Behavior

### Case 1: Destination is a Restricted Room

When routing **TO** a restricted room (e.g., room-4-3, room-5-3, or room-6-3):
- The pathfinding automatically redirects the route to the mandatory entry point
- Navigation ends at `entry-stair_east_2-2-0` instead of the actual room door
- Example: Route from `room-1-2` → `room-4-3` becomes `room-1-2` → `entry-stair_east_2-2-0`

### Case 2: Starting Point is a Restricted Room

When routing **FROM** a restricted room:
- The pathfinding starts from the mandatory entry point instead of the room door
- Example: Route from `room-5-3` → `room-1-1` becomes `entry-stair_east_2-2-0` → `room-1-1`

### Case 3: Both Rooms Share Same Entry Point

When both start and destination are restricted rooms with the same mandatory entry point:
- Route simplified to just the entry point
- Example: `room-4-3` → `room-6-3` = `entry-stair_east_2-2-0` (single point)
- Distance = 0 (both rooms accessed from same control point)

### Case 4: Multi-Floor Routing

For routes spanning multiple floors:
- Restriction rules are checked on both start and end floors
- Entry point substitution happens before floor transition calculation
- Example: `room-1-1` (Floor 1) → `room-5-3` (Floor 3):
  - System routes to `entry-stair_east_2-2-0` on Floor 3
  - Uses appropriate stairwell based on entry point location

## Implementation Details

### Helper Functions in `pathfinding.js`

1. **`getRestrictedAccessRule(graph, roomId)`**
   - Checks if a room has restricted access
   - Returns the restriction rule object or null

2. **`getMandatoryEntryPoint(graph, roomId)`**
   - Retrieves the mandatory entry point for a restricted room
   - Returns entry point room object with `roomId` and `room` properties

3. **`haveSameMandatoryEntry(graph, startRoomId, endRoomId)`**
   - Checks if two rooms share the same mandatory entry point
   - Used for optimization in same-entry routing

### Modified Functions

- **`calculateSingleFloorRoute()`**: Enhanced with restriction handling at the beginning
- **`calculateMultiFloorRoute()`**: Checks restrictions before floor transition logic

## Example Configuration: Floor 3 Restricted Rooms

### Current Setup (floor_graph_3.json)

- **Restricted Rooms**: room-4-3, room-5-3, room-6-3
- **Mandatory Entry Point**: `entry-stair_east_2-2-0`
- **Physical Location**: East Stairwell, Floor 2-3 transition
- **Linked Stair**: `stair_east_2-2` (on Floor 2)

### Path Configuration

The entry point connects to `path_central_exclusive_floor3`, which provides access to the restricted room area.

## Visual Indicators

Entry points are rendered with distinct styling:
```json
"style": {
  "pointMarker": {
    "type": "square",      // Square marker (vs circles for normal rooms)
    "radius": 8,
    "color": "red",        // Red color indicates restricted access
    "hoverColor": "#FF0000",
    "strokeColor": "#000",
    "strokeWidth": 3       // Thicker border for visibility
  }
}
```

## Adding New Restricted Rooms

To add restriction rules for additional rooms:

1. **Update the floor graph JSON:**
   ```json
   "restrictedAccessRules": {
     "room-7-3": {
       "mandatoryEntryPoint": "entry-stair_east_2-2-0",
       "description": "Room 7 requires East Stair entry"
     }
   }
   ```

2. **Define the room normally in the `rooms` section**

3. **Create entry point if it doesn't exist** (as shown above)

4. **No code changes needed** - the pathfinding logic automatically handles the rules

## Testing

Test scenarios to verify restricted access:
- [ ] Route TO restricted room from different floor
- [ ] Route FROM restricted room to different floor
- [ ] Route between two restricted rooms with same entry
- [ ] Route between two restricted rooms with different entries
- [ ] Same-floor route involving restricted room
- [ ] Verify entry point appears on floor plan
- [ ] Verify route instructions mention entry point

## Console Logging

The system logs restriction-related routing decisions:
```
Multi-floor: End room room-5-3 restricted, using entry point entry-stair_east_2-2-0
Start room room-4-3 restricted, routing from entry-stair_east_2-2-0 to room-1-2
Both rooms use same mandatory entry: entry-stair_east_2-2-0
```

Enable browser console to see these logs during navigation.

## Security & Access Control

This system provides **logical routing constraints** but does not enforce physical access control. Actual access restrictions must be implemented through:
- Physical locks/card readers
- Building management systems
- Security personnel
- Integrated access control systems

The routing system ensures visitors are **directed** to proper entry points but does not **prevent** unauthorized access.
