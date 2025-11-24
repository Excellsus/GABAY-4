# Entrance Pathfinding Test Guide

## Prerequisites
1. Hard refresh browser (Ctrl+F5) to clear floor graph cache
2. Open browser console (F12) to see pathfinding logs
3. Test on explore.php mobile interface

## Test Scenarios

### Floor 1 Tests

#### Test 1A: West Entrance → East Entrance (Path Exclusivity)
**Setup:**
- Start: `entrance_west_1` (nearestPathId: `path1` - restricted)
- End: `entrance_east_1` (nearestPathId: `path2` - unrestricted)

**Expected Result:**
- Route must use West Stair (stair_west_1-1) due to path1 enforceTransitions rule
- Console should show: "shouldForceStairTransition returned TRUE"
- Path: West Entrance → path1 → West Stair → path2 → East Entrance

**Verification:**
```javascript
// In console:
await calculateMultiFloorRoute('entrance_west_1', 'entrance_east_1');
```

#### Test 1B: Main Entrance → East Entrance (Same Path)
**Setup:**
- Start: `entrance_main_1` (nearestPathId: `path2`)
- End: `entrance_east_1` (nearestPathId: `path2`)

**Expected Result:**
- Direct route along path2 (no stair transition needed)
- Console should show: "No stair transition required"

#### Test 1C: West Entrance → Room on Path1
**Setup:**
- Start: `entrance_west_1` (nearestPathId: `path1`)
- End: `room-1-1` (nearestPathId: `path1`)

**Expected Result:**
- Direct route along path1 (both on same restricted path)
- No stair transition needed

#### Test 1D: West Entrance → Room on Path2
**Setup:**
- Start: `entrance_west_1` (nearestPathId: `path1` - restricted)
- End: `room-7-1` (nearestPathId: `path2`)

**Expected Result:**
- Route must use West Stair due to path1 enforceTransitions
- Path: West Entrance → path1 → West Stair → path2 → Room 7

#### Test 1E: Room on Path2 → West Entrance
**Setup:**
- Start: `room-12-1` (nearestPathId: `path2`)
- End: `entrance_west_1` (nearestPathId: `path1` - restricted)

**Expected Result:**
- Route must use West Stair
- Path: Room 12 → path2 → West Stair → path1 → West Entrance

### Floor 2 Tests

#### Test 2A: Main Entrance Floor 2 → West Entrance Floor 2
**Setup:**
- Start: `entrance_main_2` (nearestPathId: `lobby_vertical_2`)
- End: `entrance_west_2` (nearestPathId: `path1_floor2` - restricted)

**Expected Result:**
- Route must use West Stair due to path1_floor2 enforceTransitions
- Path: Main Entrance (F2) → lobby → West Stair → path1_floor2 → West Entrance (F2)

#### Test 2B: Main Entrance Floor 2 → Room on Floor 2
**Setup:**
- Start: `entrance_main_2` (nearestPathId: `lobby_vertical_2`)
- End: `room-12-2` (nearestPathId: `path10_floor2`)

**Expected Result:**
- Direct route through lobby to path10_floor2

### Multi-Floor Tests

#### Test 3A: Entrance Floor 1 → Room Floor 2
**Setup:**
- Start: `entrance_main_1` (floor 1, nearestPathId: `path2`)
- End: `room-12-2` (floor 2, nearestPathId: `path10_floor2`)

**Expected Result:**
- Route to Central Stair, transition to Floor 2, continue to Room 12
- Should select appropriate stair based on path compatibility

#### Test 3B: Room Floor 2 → Entrance Floor 1
**Setup:**
- Start: `room-1-2` (floor 2, nearestPathId: `path1_floor2`)
- End: `entrance_east_1` (floor 1, nearestPathId: `path2`)

**Expected Result:**
- Route to West Stair (due to path1_floor2 restriction)
- Transition down to Floor 1
- Continue to East Entrance on Floor 1

#### Test 3C: West Entrance Floor 1 → Room Floor 3
**Setup:**
- Start: `entrance_west_1` (floor 1, nearestPathId: `path1`)
- End: `room-2-3` (floor 3, nearestPathId: `path1_floor3`)

**Expected Result:**
- Route through West Stair system (only stair allowed for path1)
- Transitions: Floor 1 → Floor 2 (West Stair) → Floor 3 (West Stair)

#### Test 3D: Entrance Floor 3 → Room Floor 1
**Setup:**
- Start: `entrance_main_3` (floor 3, nearestPathId: `lobby_vertical_2_floor3`)
- End: `room-12-1` (floor 1, nearestPathId: `path2`)

**Expected Result:**
- Route through Central Stair system
- Transitions: Floor 3 → Floor 2 → Floor 1

### Floor 3 Tests

#### Test 4A: Main Entrance Floor 3 → West Entrance Floor 3
**Setup:**
- Start: `entrance_main_3` (nearestPathId: `lobby_vertical_2_floor3`)
- End: `entrance_west_3` (nearestPathId: `path3_floor3`)

**Expected Result:**
- Direct route through lobby to path3_floor3

#### Test 4B: West Entrance Floor 3 → Room on Floor 3
**Setup:**
- Start: `entrance_west_3` (nearestPathId: `path3_floor3`)
- End: `room-1-3` (nearestPathId: `lobby_vertical_1_floor3`)

**Expected Result:**
- Route through path3_floor3 to lobby

### Console Log Verification

For each test, check console for these key logs:

#### Entrance Conversion Log
```
[decorateFloorGraph] Converting 3 entrances to virtual rooms on floor 1
[decorateFloorGraph] Created virtual room for entrance: entrance_west_1 
  { x: 70, y: 340, nearestPathId: 'path1' }
```

#### Entry Points Log
```
🚪 Entry points retrieved: {
  startRoom: 'entrance_west_1',
  startDoors: [{ x: 70, y: 340, nearestPathId: 'path1' }],
  endRoom: 'entrance_east_1',
  endDoors: [{ x: 1850, y: 215, nearestPathId: 'path2' }]
}
```

#### Path Access Rule Enforcement
```
🔍 Same-floor routing check: {
  startRoomExists: true,
  endRoomExists: true,
  startIsEntrance: true,
  endIsEntrance: true
}
🔍 Path IDs: { 
  startPathId: 'path1', 
  endPathId: 'path2' 
}
⚠️ shouldForceStairTransition returned TRUE
```

## Visual Verification

### On Map
1. **Entrance markers** should appear at entrance coordinates
2. **Path highlighting** should connect entrance doorPoint to first path point
3. **Stair transitions** should be visible when routes cross paths
4. **Smooth routing** without jumps or gaps in path

### In Drawer
1. **Entrance name** displays correctly (from label field)
2. **Coordinates** shown accurately
3. **"You Are Here"** marker if entrance scanned via QR

### In Instructions Panel
1. **Clear directions** like "Start at West Entrance"
2. **Stair transitions** clearly indicated
3. **Floor changes** shown with floor numbers
4. **Distance calculations** accurate

## Edge Case Tests

### Test 5A: Entrance as Scanned Start Point
**Setup:**
1. Scan entrance QR code: `explore.php?entrance_qr=1&entrance_id=entrance_west_1&floor=1`
2. Click on destination room

**Expected Result:**
- Entrance highlighted as "You Are Here"
- Pathfinding modal auto-opens with entrance as start
- Route calculates from entrance to destination

### Test 5B: Entrance in Search
**Setup:**
1. Type entrance name in search bar: "West Entrance"
2. Select from search results

**Expected Result:**
- Floor switches to entrance floor if different
- Entrance highlighted on map
- Drawer opens showing entrance details

### Test 5C: Entrance in Dropdown
**Setup:**
1. Open pathfinding modal
2. Select entrance from start/end dropdown

**Expected Result:**
- Entrance appears in dropdown with proper label
- Selection works identically to selecting a room

## Common Issues to Check

### Issue 1: Entrance not appearing in pathfinding
**Cause:** Floor graph cache not cleared
**Solution:** Hard refresh (Ctrl+F5)

### Issue 2: Path jumps between restricted paths
**Cause:** Path access rules not enforced
**Solution:** Check console for "shouldForceStairTransition returned TRUE"

### Issue 3: Entrance coordinates incorrect
**Cause:** Database coordinates don't match SVG
**Solution:** Update entrance_qrcodes table or floor_graph.json

### Issue 4: Multi-floor routing fails from entrance
**Cause:** Stair compatibility issue
**Solution:** Check stair connectsTo arrays include correct floors

## Success Criteria

✅ **All tests pass** with correct routing
✅ **Path access rules enforced** for entrance paths
✅ **Console logs show** entrance conversion and usage
✅ **Visual paths render** smoothly without gaps
✅ **Instructions panel shows** clear step-by-step directions
✅ **No JavaScript errors** in console
✅ **QR scanning works** for entrance codes
✅ **Search finds** entrances by name
✅ **Dropdowns include** all entrances

## Performance Check

Run this in console to verify entrance count:
```javascript
// Floor 1
await ensureFloorGraphLoaded(1);
const floor1 = floorGraphCache[1];
console.log('Floor 1 entrances:', Object.keys(floor1.rooms).filter(id => id.startsWith('entrance_')));

// Floor 2
await ensureFloorGraphLoaded(2);
const floor2 = floorGraphCache[2];
console.log('Floor 2 entrances:', Object.keys(floor2.rooms).filter(id => id.startsWith('entrance_')));

// Floor 3
await ensureFloorGraphLoaded(3);
const floor3 = floorGraphCache[3];
console.log('Floor 3 entrances:', Object.keys(floor3.rooms).filter(id => id.startsWith('entrance_')));
```

**Expected Output:**
```
Floor 1 entrances: ['entrance_main_1', 'entrance_west_1', 'entrance_east_1']
Floor 2 entrances: ['entrance_main_2', 'entrance_west_2']
Floor 3 entrances: ['entrance_main_3', 'entrance_west_3']
```

## Database Verification

Run this SQL to verify entrance data matches floor_graph.json:
```sql
SELECT entrance_id, floor, x, y, nearest_path_id, is_active 
FROM entrance_qrcodes 
ORDER BY floor, entrance_id;
```

Cross-reference results with floor_graph.json entrances array.

## Next Steps After Testing

1. **Document any issues** found during testing
2. **Update coordinates** if visual alignment is off
3. **Adjust path associations** if routing seems suboptimal
4. **Add new entrances** using same structure in JSON and database
5. **Consider entrance-specific styling** (optional enhancement)
