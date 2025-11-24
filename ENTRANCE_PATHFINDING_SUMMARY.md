# Entrance Pathfinding Implementation - Summary

## What Was Changed

### Modified Files
1. **pathfinding.js** - Line ~187 in `decorateFloorGraph` function
2. **floor_graph_2.json** - Added entrance_west_2 definition
3. **floor_graph_3.json** - Added entrance_main_3 and entrance_west_3 definitions

### Code Change Details

#### pathfinding.js - decorateFloorGraph Function
```javascript
// NEW CODE: Convert entrances to virtual rooms
if (Array.isArray(graph.entrances) && graph.entrances.length > 0) {
  console.log(`[decorateFloorGraph] Converting ${graph.entrances.length} entrances to virtual rooms on floor ${floor}`);
  
  graph.rooms = graph.rooms || {};
  
  graph.entrances.forEach((entrance) => {
    if (!entrance || !entrance.id) return;
    
    // Create virtual room that works identically to regular rooms
    const virtualRoom = {
      type: 'entrance',
      label: entrance.label || entrance.id,
      nearestPathId: entrance.nearestPathId,
      doorPoints: [
        {
          x: entrance.x,
          y: entrance.y,
          nearestPathId: entrance.nearestPathId
        }
      ],
      entryPoints: [
        {
          x: entrance.x,
          y: entrance.y,
          nearestPathId: entrance.nearestPathId
        }
      ],
      entranceData: entrance
    };
    
    // Add to rooms object using entrance ID
    graph.rooms[entrance.id] = virtualRoom;
  });
}
```

#### floor_graph_2.json
```json
"entrances": [
  {
    "id": "entrance_main_2",
    "label": "Main Entrance (Floor 2)",
    "type": "entrance",
    "floor": 2,
    "x": 970,
    "y": 307,
    "nearestPathId": "lobby_vertical_2"
  },
  {
    "id": "entrance_west_2",  // ← ADDED
    "label": "West Entrance (Floor 2)",
    "type": "entrance",
    "floor": 2,
    "x": 205,
    "y": 210,
    "nearestPathId": "path1_floor2"
  }
]
```

#### floor_graph_3.json
```json
"entrances": [
  {
    "id": "entrance_main_3",  // ← ADDED
    "label": "Main Entrance (Floor 3)",
    "type": "entrance",
    "floor": 3,
    "x": 975,
    "y": 140,
    "nearestPathId": "lobby_vertical_2_floor3"
  },
  {
    "id": "entrance_west_3",  // ← ADDED
    "label": "West Entrance (Floor 3)",
    "type": "entrance",
    "floor": 3,
    "x": 845,
    "y": 140,
    "nearestPathId": "path3_floor3"
  }
]
```

## How It Works

### 1. Automatic Conversion
When floor graphs are loaded, `decorateFloorGraph` automatically converts each entrance definition into a virtual room object and adds it to `graph.rooms`.

### 2. Room-Like Structure
Virtual entrance rooms have:
- **doorPoints array** - Single entry point at entrance coordinates
- **entryPoints array** - Same as doorPoints for compatibility
- **nearestPathId** - Path association from entrance definition
- **type: 'entrance'** - Distinguishes from regular rooms
- **label** - Display name for UI

### 3. Seamless Integration
All existing pathfinding functions work with entrances automatically:
- `getEntryPointsForRoom()` - Returns entrance doorPoints
- `getPrimaryPathIdForRoom()` - Returns entrance nearestPathId
- `calculateSingleFloorRoute()` - Routes to/from entrances
- `calculateMultiFloorRoute()` - Handles multi-floor with entrances
- `shouldForceStairTransition()` - Checks entrance path rules

### 4. Path Access Rules
Entrances respect path access rules:
- **West Entrance** (path1 - restricted) → requires West Stair for transitions
- **Main Entrance** (path2 - unrestricted) → can use any stair
- **East Entrance** (path2 - unrestricted) → can use any stair

## Examples

### Example 1: West Entrance → East Entrance
```javascript
await calculateMultiFloorRoute('entrance_west_1', 'entrance_east_1');
```
**Result:** Route uses West Stair because path1 has `enforceTransitions: true`

### Example 2: Main Entrance → Room
```javascript
await calculateMultiFloorRoute('entrance_main_1', 'room-12-1');
```
**Result:** Direct route along path2 (both unrestricted)

### Example 3: West Entrance Floor 1 → Room Floor 2
```javascript
await calculateMultiFloorRoute('entrance_west_1', 'room-1-2');
```
**Result:** Uses West Stair for floor transition (path1 restriction)

### Example 4: Entrance in Pathfinding Modal
```javascript
// User selects from dropdown
startRoom = 'entrance_west_1';
endRoom = 'room-5-1';
calculateRoute(startRoom, endRoom);
```
**Result:** Works identically to selecting a regular room

## Benefits

### ✅ Zero Code Duplication
No separate entrance routing logic needed - reuses all existing pathfinding functions.

### ✅ Automatic Feature Inheritance
Entrances automatically get:
- Path access rule enforcement
- Stair exclusivity
- Multi-floor transitions
- Distance calculations
- Route optimization
- Restricted access support

### ✅ Simple Addition Process
To add new entrance:
1. Add to `entrance_qrcodes` database table
2. Add to floor_graph.json `entrances` array
3. Done! No code changes needed

### ✅ Consistent Behavior
Entrances behave identically to rooms with doorPoints - no special cases or edge case handling required.

## Testing

### Quick Test in Console
```javascript
// Load floor graph
await ensureFloorGraphLoaded(1);

// Verify entrance converted to virtual room
console.log(floorGraphCache[1].rooms['entrance_west_1']);

// Expected output:
// {
//   type: 'entrance',
//   label: 'West Entrance',
//   nearestPathId: 'path1',
//   doorPoints: [{ x: 70, y: 340, nearestPathId: 'path1' }],
//   entryPoints: [{ x: 70, y: 340, nearestPathId: 'path1' }],
//   entranceData: { /* original entrance object */ }
// }

// Test routing
const route = await calculateMultiFloorRoute('entrance_west_1', 'entrance_east_1');
console.log('Route segments:', route.segments.length);
console.log('Uses stair:', route.segments.some(s => s.type === 'stair-transition'));
```

### User Testing
1. Hard refresh (Ctrl+F5) to clear cache
2. Open pathfinding modal
3. Select entrance from dropdown
4. Select destination room
5. Click "Find Route"
6. Verify path renders correctly with stair transitions if needed

## Database Schema

### entrance_qrcodes Table
```sql
CREATE TABLE `entrance_qrcodes` (
  `id` int(11) NOT NULL,
  `entrance_id` varchar(50) NOT NULL,     -- matches floor_graph.json entrance.id
  `floor` int(11) NOT NULL,                -- floor number
  `label` varchar(255) NOT NULL,           -- display name
  `x` decimal(10,2) NOT NULL,              -- SVG X coordinate
  `y` decimal(10,2) NOT NULL,              -- SVG Y coordinate
  `nearest_path_id` varchar(100) DEFAULT NULL,  -- path association
  `qr_code_data` text NOT NULL,           -- QR URL
  `qr_code_image` varchar(255) NOT NULL,  -- QR image file
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
)
```

### Current Entrances
| ID | Floor | Label | nearestPathId |
|----|-------|-------|---------------|
| entrance_main_1 | 1 | Main Entrance | path2 |
| entrance_west_1 | 1 | West Entrance | path1 |
| entrance_east_1 | 1 | East Entrance | path2 |
| entrance_main_2 | 2 | Main Entrance (Floor 2) | lobby_vertical_2 |
| entrance_west_2 | 2 | West Entrance (Floor 2) | path1_floor2 |
| entrance_main_3 | 3 | Main Entrance (Floor 3) | lobby_vertical_2_floor3 |
| entrance_west_3 | 3 | West Entrance (Floor 3) | path3_floor3 |

## What Didn't Change

### No Changes Required To:
- ✅ calculateSingleFloorRoute
- ✅ calculateMultiFloorRoute
- ✅ getEntryPointsForRoom
- ✅ getPrimaryPathIdForRoom
- ✅ shouldForceStairTransition
- ✅ getPathBetweenPoints
- ✅ All other pathfinding utilities

### No UI Changes Required To:
- ✅ Pathfinding modal dropdowns
- ✅ Map rendering
- ✅ Route highlighting
- ✅ Instruction generation
- ✅ Distance display
- ✅ Search functionality

## Migration Notes

### From Old System
If you had custom entrance handling code before, you can now remove it. Entrances are just rooms.

### Backward Compatibility
- Old floor_graph.json files without `entrances` array still work
- New system gracefully handles empty `entrances` array
- Existing room pathfinding unaffected

## Support

See detailed documentation in:
- **ENTRANCE_PATHFINDING_IMPLEMENTATION.md** - Technical details
- **ENTRANCE_PATHFINDING_TEST_GUIDE.md** - Complete test scenarios

## Conclusion

Entrances now work **identically** to rooms with doorPoints. The system automatically converts entrance definitions to virtual rooms during graph loading, enabling seamless navigation with full support for:
- Path access rules ✅
- Stair exclusivity ✅
- Multi-floor routing ✅
- Distance calculation ✅
- Route optimization ✅

**No special cases. No duplicated code. Just works.**
