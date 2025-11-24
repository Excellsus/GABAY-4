# Entrance Pathfinding - Quick Reference

## 🎯 What Changed
Single function modified: `decorateFloorGraph()` in pathfinding.js (~line 187)

## ✨ What It Does
Automatically converts entrance definitions from floor_graph.json into virtual room objects that work identically to rooms with doorPoints.

## 📋 Files Modified

### 1. pathfinding.js
**Location:** Line ~187 in `decorateFloorGraph` function  
**Change:** Added entrance-to-virtual-room conversion logic  
**Result:** Entrances added to `graph.rooms` object

### 2. floor_graph_2.json
**Added:** `entrance_west_2` to entrances array

### 3. floor_graph_3.json
**Added:** `entrance_main_3` and `entrance_west_3` to entrances array

## 🚀 How To Use

### In JavaScript
```javascript
// Entrances work exactly like rooms
await calculateMultiFloorRoute('entrance_west_1', 'room-5-1');
await calculateMultiFloorRoute('room-12-1', 'entrance_east_1');
await calculateMultiFloorRoute('entrance_main_1', 'entrance_west_1');

// Multi-floor routing
await calculateMultiFloorRoute('entrance_main_1', 'room-12-2'); // Floor 1 → Floor 2

// Path access rules respected
await calculateMultiFloorRoute('entrance_west_1', 'entrance_east_1'); 
// ↑ Uses West Stair (path1 restriction)
```

### In UI
- Select entrance from pathfinding modal dropdown
- Click entrance on map to route to it
- Search for entrance by name
- Scan entrance QR code to start from that location

## 📊 Current Entrances

| Entrance ID | Floor | Label | Path | Restriction |
|------------|-------|-------|------|-------------|
| entrance_main_1 | 1 | Main Entrance | path2 | None |
| entrance_west_1 | 1 | West Entrance | path1 | ✅ West Stair only |
| entrance_east_1 | 1 | East Entrance | path2 | None |
| entrance_main_2 | 2 | Main Entrance (F2) | lobby_vertical_2 | None |
| entrance_west_2 | 2 | West Entrance (F2) | path1_floor2 | ✅ West Stair only |
| entrance_main_3 | 3 | Main Entrance (F3) | lobby_vertical_2_floor3 | None |
| entrance_west_3 | 3 | West Entrance (F3) | path3_floor3 | None |

## ✅ Testing

### Quick Console Test
```javascript
// 1. Verify entrance converted to virtual room
await ensureFloorGraphLoaded(1);
console.log(floorGraphCache[1].rooms['entrance_west_1']);
// Should show: { type: 'entrance', doorPoints: [...], nearestPathId: 'path1' }

// 2. Test routing
const route = await calculateMultiFloorRoute('entrance_west_1', 'entrance_east_1');
console.log('Segments:', route.segments.length);
console.log('Uses West Stair:', route.segments.some(s => s.description?.includes('West Stair')));

// 3. Run full verification
// Copy/paste contents of test_entrance_pathfinding.js into console
```

### Visual Testing
1. Hard refresh (Ctrl+F5)
2. Open pathfinding modal
3. Select entrance from dropdown
4. Select destination
5. Verify route renders correctly

## 🎨 Virtual Room Structure

```javascript
// Input (floor_graph.json):
{
  "id": "entrance_west_1",
  "label": "West Entrance",
  "floor": 1,
  "x": 70,
  "y": 340,
  "nearestPathId": "path1"
}

// Output (graph.rooms):
{
  type: 'entrance',
  label: 'West Entrance',
  nearestPathId: 'path1',
  doorPoints: [
    { x: 70, y: 340, nearestPathId: 'path1' }
  ],
  entryPoints: [
    { x: 70, y: 340, nearestPathId: 'path1' }
  ],
  entranceData: { /* original entrance object */ }
}
```

## 🔑 Key Functions That Now Support Entrances

### Automatically Compatible
- ✅ `calculateSingleFloorRoute()` - Routes to/from entrances
- ✅ `calculateMultiFloorRoute()` - Handles multi-floor with entrances
- ✅ `getEntryPointsForRoom()` - Returns entrance doorPoints
- ✅ `getPrimaryPathIdForRoom()` - Returns entrance nearestPathId
- ✅ `shouldForceStairTransition()` - Checks entrance path rules
- ✅ `getPathBetweenPoints()` - Finds paths to/from entrances

### No Changes Needed To
- ✅ UI dropdowns (entrances now in rooms)
- ✅ Map rendering (entrance IDs work as room IDs)
- ✅ Search (entrances searchable as rooms)
- ✅ QR scanning (entrance QR system already in place)

## 📝 Adding New Entrance

### Step 1: Database
```sql
INSERT INTO entrance_qrcodes (entrance_id, floor, label, x, y, nearest_path_id, qr_code_data, qr_code_image, is_active)
VALUES ('entrance_new_1', 1, 'New Entrance', 500, 300, 'path2', '...', 'entrance_new_1.png', 1);
```

### Step 2: floor_graph.json
```json
"entrances": [
  {
    "id": "entrance_new_1",
    "label": "New Entrance",
    "type": "entrance",
    "floor": 1,
    "x": 500,
    "y": 300,
    "nearestPathId": "path2"
  }
]
```

### Step 3: Done!
No code changes needed. System automatically converts entrance to virtual room on next page load.

## 🐛 Troubleshooting

### Entrance not appearing
**Solution:** Hard refresh (Ctrl+F5) to clear floor graph cache

### Path jumps between paths
**Solution:** Check path access rules enforced in console logs

### Wrong coordinates
**Solution:** Update both database and floor_graph.json to match

### Multi-floor fails
**Solution:** Verify stair connectsTo arrays include correct floors

## 📚 Documentation Files

- **ENTRANCE_PATHFINDING_SUMMARY.md** - Complete overview
- **ENTRANCE_PATHFINDING_IMPLEMENTATION.md** - Technical details
- **ENTRANCE_PATHFINDING_TEST_GUIDE.md** - Test scenarios
- **test_entrance_pathfinding.js** - Automated verification script

## 💡 Key Benefits

### Zero Code Duplication
No separate entrance routing logic - reuses all existing pathfinding functions

### Automatic Feature Inheritance
Entrances get path rules, stair exclusivity, multi-floor, distance calculation automatically

### Simple Maintenance
Changes to room pathfinding automatically apply to entrances

### Consistent Behavior
Entrances behave identically to rooms - no special cases

## 🎓 Example Scenarios

### Scenario 1: West to East (Path Restriction)
```javascript
// West Entrance (path1 - restricted) → East Entrance (path2)
const route = await calculateMultiFloorRoute('entrance_west_1', 'entrance_east_1');
// Result: Uses West Stair due to path1 enforceTransitions rule
```

### Scenario 2: Main to Room (Unrestricted)
```javascript
// Main Entrance (path2) → Room on path2
const route = await calculateMultiFloorRoute('entrance_main_1', 'room-12-1');
// Result: Direct route along path2
```

### Scenario 3: Multi-Floor
```javascript
// Entrance Floor 1 → Room Floor 2
const route = await calculateMultiFloorRoute('entrance_main_1', 'room-12-2');
// Result: Routes to appropriate stair, transitions, continues to room
```

### Scenario 4: QR Scan
```
User scans: explore.php?entrance_qr=1&entrance_id=entrance_west_1&floor=1
Result: 
- Map loads Floor 1
- Entrance highlighted as "You Are Here"
- User clicks destination
- Pathfinding modal opens with entrance as start
- Route calculated from entrance to destination
```

## 🔍 Verification Checklist

- [ ] All entrances appear in console logs during graph loading
- [ ] Virtual rooms created for each entrance (check floorGraphCache)
- [ ] Entrances appear in pathfinding dropdown
- [ ] Routes calculate correctly to/from entrances
- [ ] Path access rules enforced for entrance paths
- [ ] Multi-floor routing works with entrances
- [ ] QR scanning highlights entrances correctly
- [ ] Search finds entrances by name

## 🎯 Success Criteria

✅ West Entrance → East Entrance uses West Stair  
✅ Entrance → Room routing works seamlessly  
✅ Room → Entrance routing works seamlessly  
✅ Multi-floor with entrances works correctly  
✅ Console shows entrance conversion logs  
✅ No JavaScript errors  
✅ Visual paths render smoothly  

---

**Status:** ✅ Implementation Complete  
**Last Updated:** November 24, 2025  
**Next Steps:** Test thoroughly, then deploy
