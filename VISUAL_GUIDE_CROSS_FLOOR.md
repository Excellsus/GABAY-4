# Visual Guide: Cross-Floor Restricted Access

## Before vs After

### BEFORE (Normal Room Routing)
```
Floor 3 Layout:
┌─────────────────────────────────┐
│  Room 1 [door●]                 │
│  Room 2 [door●]                 │
│  Room 3 [door●]                 │
│  Room 4 [door●] ← Has doorPoints│
└─────────────────────────────────┘

Routing: anywhere → Room 4
Result: Route ends at Room 4's door on Floor 3 ✓
```

### AFTER (Cross-Floor Entry Point)
```
Floor 3 Layout:
┌─────────────────────────────────┐
│  Room 1 [door●]                 │
│  Room 2 [door●]                 │
│  Room 3 [door●]                 │
│  Room 4 [    ] ← NO doorPoints! │
│  Room 5 [    ] ← NO doorPoints! │
│  Room 6 [    ] ← NO doorPoints! │
└─────────────────────────────────┘
           ↓
   (Entry via Floor 2)
           ↓
Floor 2 Layout:
┌─────────────────────────────────┐
│                                 │
│           East Stair [●] ← Entry│
│           (1820, 180)           │
└─────────────────────────────────┘

Routing: anywhere → Room 4/5/6
Result: Route ends at Floor 2 East Stair ✓
User Action: Take stairs up to Floor 3
```

## Data Flow Diagram

```
User Selects: Room 1-1 → Room 5-3
                          └─(Floor 3, Restricted)
                │
                ▼
        Check restrictedAccessRules
                │
                ▼
        Found: mandatoryEntryPoint = "stair_east_2-2"
               entryPointFloor = 2
                │
                ▼
        Load Floor 2 Graph
                │
                ▼
        Find: stair_east_2-2 at (1820, 180)
                │
                ▼
        Substitute Destination:
        Room 1-1 → stair_east_2-2 (Floor 2)
                │
                ▼
        Calculate Multi-Floor Route:
        Floor 1 → Floor 2 (East Stair)
                │
                ▼
        Return Route:
        {
          segments: [
            { floor: 1, from: room-1-1, to: nearest_stair },
            { type: 'stair', from: floor1, to: floor2 },
            { floor: 2, from: stair_landing, to: stair_east_2-2 }
          ]
        }
```

## Room Configuration Comparison

### Normal Room (Room 1-3)
```json
{
  "room-1-3": {
    "doorPoints": [{"x": 815, "y": 233}],  // ✓ Has door
    "nearestPathId": "lobby_vertical_1_floor3",
    "style": {
      "pointMarker": {
        "color": "green"  // Green = normal access
      }
    }
  }
}
```

### Restricted Room (Room 4-3)
```json
{
  "restrictedAccessRules": {
    "room-4-3": {
      "mandatoryEntryPoint": "stair_east_2-2",  // Floor 2 stair
      "entryPointFloor": 2                      // Cross-floor!
    }
  },
  "rooms": {
    "room-4-3": {
      "doorPoints": [],  // ✗ NO door on Floor 3
      "nearestPathId": "path_central_exclusive_floor3",
      "style": {
        "pointMarker": {
          "color": "orange"  // Orange = restricted access
        }
      }
    }
  }
}
```

## Route Endpoint Comparison

### To Normal Room
```
Start: room-1-1 (Floor 1)
End: room-2-3 (Floor 3)

Route Path:
Floor 1: room-1-1 → stair
Floor 2: stair → stair (transition)
Floor 3: stair → room-2-3 [door at x:833, y:355]
         └─────────────────┘
         Ends at actual door on Floor 3 ✓
```

### To Restricted Room
```
Start: room-1-1 (Floor 1)
End: room-5-3 (Floor 3, RESTRICTED)

Route Path:
Floor 1: room-1-1 → stair
Floor 2: stair → stair_east_2-2 [x:1820, y:180]
         └──────────────────────┘
         Ends at Floor 2 stair ✓

NO Floor 3 segment!
User manually goes: Floor 2 stair → up stairs → Floor 3 room-5-3
```

## Color Legend on Floor Plans

| Color  | Meaning              | Door Points | Access Method           |
|--------|---------------------|-------------|-------------------------|
| Green  | Normal access        | ✓ Yes       | Direct via floor path   |
| Orange | Restricted access    | ✗ No        | Via Floor 2 stair only  |
| Blue   | Special (varies)     | ✓ Yes       | Varies by configuration |
| Red    | Virtual entry (old)  | N/A         | (Not used anymore)      |

## Console Log Examples

### When routing TO restricted room:
```
Multi-floor: End room room-5-3 restricted, using entry point stair_east_2-2 on floor 2
Calculating route: room-1-1 → stair_east_2-2
Route calculated successfully: 2 floors, 3 segments
```

### When routing FROM restricted room:
```
Multi-floor: Start room room-4-3 restricted, using entry point stair_east_2-2 on floor 2
Calculating route: stair_east_2-2 → room-12-2
Route calculated successfully: 1 floor, 1 segment
```

### When routing BETWEEN restricted rooms:
```
Both rooms use same mandatory entry: stair_east_2-2
Single-point route, distance: 0m
```

## Physical User Experience

### Scenario: Going to Room 5 (Floor 3)

1. **Digital Navigation** (handled by system):
   ```
   Your device shows:
   "Navigate to East Stairwell on Floor 2"
   
   Route on screen:
   - Follow path from current location
   - Take stairs to Floor 2 (if needed)
   - Arrive at East Stair landing (coordinates shown)
   ```

2. **Physical Navigation** (handled by user):
   ```
   User arrives at Floor 2 East Stair landing
   User looks for physical signs
   User takes stairs UP one floor
   User exits on Floor 3
   User locates Room 5 nearby
   ```

### Why Stop at Floor 2?

The system **guides you to the correct entry point** (Floor 2 East Stair), but doesn't tell you "go up the stairs" because:

- Stairwell traversal is physical, not digital
- Users can see stair signs in real life
- Access control (if any) happens at the stairwell entrance
- System focuses on navigating to the right place, not climbing stairs

## Quick Reference

| User Action          | System Behavior                      | End Point            |
|---------------------|--------------------------------------|----------------------|
| Click room-4-3      | Routes to stair_east_2-2 (Floor 2)   | Floor 2 stair        |
| Click room-5-3      | Routes to stair_east_2-2 (Floor 2)   | Floor 2 stair        |
| Click room-6-3      | Routes to stair_east_2-2 (Floor 2)   | Floor 2 stair        |
| From room-4-3       | Starts at stair_east_2-2 (Floor 2)   | Floor 2 stair        |
| 4-3 → 5-3           | Single point (both use Floor 2)      | Floor 2 stair        |
| Any → normal room   | Routes to actual door                | Room door on floor   |

---

**Remember**: Orange rooms on Floor 3 = Access via Floor 2 East Stair only! 🎯
