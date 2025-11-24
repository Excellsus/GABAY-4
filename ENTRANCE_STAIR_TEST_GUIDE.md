# Entrance Stair Exclusivity - Quick Test Guide

## Test Setup

### Prerequisites
1. XAMPP Apache + MySQL running
2. Access to http://localhost/FinalDev/mobileScreen/explore.php
3. Browser console open (F12)
4. entrance_west_1 QR code available

## Test Case 1: Restricted Path Navigation (Primary Test)

### Scenario
Entrance on path1 (restricted) navigating to room on path2.

### Steps
1. Open `mobileScreen/explore.php` in browser
2. Open Developer Console (F12)
3. Scan entrance_west_1 QR code (or manually trigger):
   ```javascript
   // Simulate scan in console:
   window.location.href = 'explore.php?entrance_id=entrance_west_1';
   ```
4. Wait for page load and entrance marker to appear
5. Click on room-12-1 (this room is on path2)

### Expected Results

**Console Output** (in order):
```
🚪 Entrance entrance_west_1 on restricted path path1 - requires stairs: ["west"]
🚫 Cannot route directly from path1 to path2 - stair transition required
🚪 Created virtual entrance room for path transition check: {...}
🛤️ Checking path transition requirements: {
  startRoomId: "entrance_entrance_west_1_1",
  endRoomId: "room-12-1",
  startPathId: "path1",
  endPathId: "path2",
  isEntrance: true
}
⚠️ Stair transition required for entrance/room on restricted path
```

**Visual Results**:
- Route drawn from entrance_west_1 to stair (green path)
- Stair icon appears at west stairwell
- Route continues from stair to room-12-1
- Text directions show: "Proceed to West Stair" → "Use West Stair to reach Floor 2" → etc.

### ❌ Failure Indicators
- Direct route from entrance to room (bypassing stairs)
- Console shows: "No candidate stair keys found"
- Error: "Unable to determine floors for rooms"

---

## Test Case 2: Same Path Navigation (No Restriction)

### Scenario
Entrance on path1 navigating to another room on path1.

### Steps
1. Scan entrance_west_1 QR code
2. Click on room-1-1 (also on path1)

### Expected Results

**Console Output**:
```
✅ Calculating single-floor route
✅ Route found: {distance: X, points: [...]}
```

**Visual Results**:
- Direct route from entrance_west_1 to room-1-1
- No stair transitions
- Green path drawn along path1

### ❌ Failure Indicators
- Route uses stairs unnecessarily
- Console shows "stair transition required" for same path

---

## Test Case 3: Cross-Floor Navigation

### Scenario
Entrance on Floor 1, destination on Floor 2.

### Steps
1. Scan entrance_west_1 QR code (Floor 1)
2. Switch to Floor 2 using floor selector
3. Open pathfinding modal by clicking any room on Floor 2
4. Check "From" dropdown

### Expected Results

**Dropdown Shows**:
```
West Entrance 🚪 (YOU ARE HERE - Floor 1)
```

**Console Output**:
```
🚪 Entrance entrance_west_1 (Floor 1) available for cross-floor routing to Floor 2
```

**Route Behavior**:
- Route from entrance on Floor 1 → stairs → destination on Floor 2
- Multi-floor route segments shown

### ❌ Failure Indicators
- Entrance not in dropdown
- Console shows: "Entrance not available - different from destination floor"
- Dropdown shows: "(YOU ARE HERE)" without floor number

---

## Test Case 4: Entrance Dropdown Behavior

### Scenario
Verify entrance appears correctly in pathfinding modal.

### Steps
1. Scan entrance_west_1 QR code
2. Click any room to open pathfinding modal
3. Check "From" dropdown content
4. Check "To" dropdown content

### Expected Results

**"From" Dropdown**:
```
Option 1 (selected): West Entrance 🚪 (YOU ARE HERE)
Option 2: Room 1 (Floor 1)
Option 3: Room 2 (Floor 1)
...
```

**"To" Dropdown**:
```
Option 1 (selected): [Clicked Room Name]
Option 2: Room 1 (Floor 1)
...
```

**Console Output**:
```
🚪 Pathfinding modal opened with entrance start: West Entrance and destination: Room X
```

### ❌ Failure Indicators
- Entrance not in "From" dropdown
- Entrance value is empty or undefined
- Console error: "entrance.roomId is undefined"

---

## Test Case 5: Database Position Integration

### Scenario
Verify entrance uses database coordinates, not just JSON defaults.

### Steps
1. Update entrance position in database:
   ```sql
   UPDATE entrance_qrcodes 
   SET x = 150, y = 300 
   WHERE entrance_id = 'entrance_west_1';
   ```
2. Refresh explore.php
3. Scan entrance_west_1 QR code
4. Check marker position

### Expected Results

**Console Output**:
```
📍 Fetched X entrance positions from database for floor 1
📍 Updating entrance entrance_west_1 from (70, 340) to (150, 300)
🔄 Redrawing YOU ARE HERE marker with updated entrance position
🔄 Redrawing entrance icons with updated positions
```

**Visual Results**:
- Green "YOU ARE HERE" marker at (150, 300)
- Entrance icon at (150, 300)

### ❌ Failure Indicators
- Marker stays at old position (70, 340)
- Console shows no redraw messages
- Position update doesn't persist after refresh

---

## Quick Console Commands

### Check Scanned Entrance
```javascript
console.log('Scanned Entrance:', window.scannedStartEntrance);
```

### Check Floor Graph
```javascript
console.log('Floor Graph:', window.floorGraph);
console.log('Path Access Rules:', window.floorGraph.pathAccessRules);
```

### Manually Trigger Route
```javascript
activateRouteBetweenRooms('entrance_entrance_west_1_1', 'room-12-1');
```

### Check Virtual Room Creation
```javascript
// After scanning entrance, check if virtual room exists:
const entrance = window.scannedStartEntrance;
const virtualRoom = createVirtualRoomForEntrance(entrance);
console.log('Virtual Room:', virtualRoom);
console.log('nearestPathId:', virtualRoom.nearestPathId);
```

---

## Common Issues and Fixes

### Issue: "Path transition check not triggered"
**Cause**: Entrance not recognized as being on path with restrictions
**Fix**: Verify entrance.nearestPathId matches pathAccessRules key

### Issue: "Direct route still created"
**Cause**: continue statement not reached in door loop
**Fix**: Check console for entrance validation logs

### Issue: "Entrance not available after floor switch"
**Cause**: parseFloorFromRoomId returns null
**Fix**: Ensure entrance.floor property exists in database

### Issue: "Route uses wrong stairs"
**Cause**: transitionStairKeys not matching available stair keys
**Fix**: Check floor_graph.json stairGroups and pathAccessRules

---

## Success Criteria

✅ **All Tests Pass If**:
1. Restricted path forces stair usage (Console shows stair transition messages)
2. Same path allows direct routing (No stair messages)
3. Cross-floor shows entrance with floor number in dropdown
4. Entrance appears in "From" dropdown with 🚪 emoji
5. Database position updates reflect in marker position immediately
6. All console logs match expected patterns
7. No JavaScript errors in console
8. Routes are visually correct on SVG map

---

## Debugging Commands

### Enable Verbose Logging
```javascript
// Run before testing to see all routing decisions:
window.DEBUG_PATHFINDING = true;
```

### Check Path Rules
```javascript
const graph = window.floorGraph;
console.log('Path 1 Rules:', getPathAccessRule(graph, 'path1'));
console.log('Path 2 Rules:', getPathAccessRule(graph, 'path2'));
```

### Verify Stair Detection
```javascript
const graph = window.floorGraph;
console.log('Should Force Transition:', shouldForceStairTransition(graph, 'path1', 'path2'));
```

### Inspect Route Object
```javascript
// After route is calculated:
console.log('Active Route:', window.activeRoute);
console.log('Route Segments:', window.activeRoute?.segments);
```

---

## Test Completion Checklist

- [ ] Test Case 1: Restricted path navigation works
- [ ] Test Case 2: Same path navigation works  
- [ ] Test Case 3: Cross-floor entrance available
- [ ] Test Case 4: Dropdown shows entrance correctly
- [ ] Test Case 5: Database positions update properly
- [ ] No console errors during any test
- [ ] All expected console messages appear
- [ ] Visual routes match expected behavior
- [ ] Documentation matches actual behavior

## Next Steps After Testing

If all tests pass:
1. Document any configuration needed for production
2. Update admin panel to show entrance path restrictions
3. Add UI indicators for restricted entrances on SVG

If tests fail:
1. Note which specific console message is missing
2. Check browser network tab for API errors
3. Verify database has correct entrance data
4. Review ENTRANCE_STAIR_EXCLUSIVITY_FIX.md for troubleshooting section
