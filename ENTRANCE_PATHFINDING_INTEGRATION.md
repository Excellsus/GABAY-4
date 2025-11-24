# Entrance QR Pathfinding Integration

## Overview
Entrance QR codes now fully integrate with the pathfinding system, allowing visitors to scan building entrance QR codes and use them as starting points for navigation to any office/room.

## How It Works

### 1. QR Scan Detection (`explore.php`)
When a visitor scans an entrance QR code:
- URL format: `explore.php?entrance_qr=1&entrance_id={id}&floor={floor}`
- System validates entrance is active in database
- Creates `window.scannedStartEntrance` object with:
  - `id`: Entrance database ID
  - `label`: Entrance name (e.g., "Main Entrance", "East Wing Door")
  - `x`, `y`: Coordinates on floor plan
  - `floor`: Floor number
  - `nearestPathId`: Associated walkable path
  - `roomId`: Virtual room ID format `entrance_{id}_{floor}`
  - `type`: 'entrance'

### 2. Visual Marker (`showYouAreHereEntrance`)
- Displays green "YOU ARE HERE" marker at entrance location
- Shows entrance name and floor information
- Marker persists as user navigates map

### 3. Pathfinding Modal Integration
When user clicks any room after scanning entrance QR:
- Pathfinding modal auto-opens
- **Entrance pre-selected as start location** (priority over office QR scans)
- Clicked room pre-selected as destination
- User can override and select different start/end points

### 4. Virtual Room Creation (`pathfinding.js`)
**New Function: `createVirtualRoomForEntrance(entrance)`**
- Creates temporary "room" object for entrance
- Compatible with existing pathfinding algorithms
- Contains entry point with entrance coordinates

**Integration Points:**
- `getEntryPointsForRoom()`: Returns entrance coordinates when entrance roomId matches
- `calculateSingleFloorRoute()`: Injects virtual room if starting from entrance
- `calculateMultiFloorRoute()`: Injects virtual room in multiple locations for cross-floor routes
- `augmentRouteWithRestrictedStart()`: Handles restricted room routing from entrance

### 5. Route Calculation
Entrance starting points work identically to office/door starting points:
- Same-floor routing: Direct path from entrance to destination
- Cross-floor routing: Finds optimal stair transition
- Restricted access: Routes through mandatory entry points
- Path visualization: Animated blue route line on map

## Key Features

### ✅ Automatic Start Point
- Entrance becomes default start for all subsequent pathfinding
- Persists until page reload or new QR scan
- Takes precedence over office QR scans

### ✅ Floor Switching
- Automatically switches to entrance floor when scanned
- Entrance marker remains visible when switching floors
- Cross-floor routes handled seamlessly

### ✅ Active/Inactive Validation
- Only active entrances are scannable
- Inactive/deleted entrance QRs redirect to 404 page
- Scan logging only occurs for active entrances

### ✅ Visual Feedback
- Green entrance icon on map (circular background)
- "YOU ARE HERE" label with entrance name
- Entrance listed in legend dialog

## Database Structure

**Table: `entrance_qrcodes`**
```sql
- id (primary key)
- entrance_id (varchar, unique identifier)
- label (varchar, entrance name)
- floor_number (int, 1-3)
- x (float, SVG coordinate)
- y (float, SVG coordinate)
- nearest_path_id (varchar, walkable path)
- qr_code_path (varchar, PNG file path)
- is_active (tinyint, 0=inactive, 1=active)
```

**Table: `entrance_scan_logs`**
```sql
- id (primary key)
- entrance_qr_id (foreign key)
- scan_timestamp (datetime)
- user_ip (varchar)
- user_agent (text)
```

## Testing Checklist

1. **Basic Entrance Scan**
   - [ ] Scan entrance QR code
   - [ ] Verify green entrance icon appears on correct floor
   - [ ] Check "YOU ARE HERE" label displays entrance name

2. **Pathfinding Modal**
   - [ ] Click any room after entrance scan
   - [ ] Verify modal opens automatically
   - [ ] Confirm entrance pre-selected as start with 🚪 emoji
   - [ ] Confirm clicked room pre-selected as destination

3. **Route Calculation**
   - [ ] Click "Find Route" button
   - [ ] Verify blue animated route appears from entrance to destination
   - [ ] Check route instructions panel shows correct steps
   - [ ] Test same-floor and cross-floor routes

4. **Edge Cases**
   - [ ] Scan inactive entrance QR → should redirect to 404
   - [ ] Scan entrance then office QR → entrance should take precedence
   - [ ] Switch floors manually → entrance marker should persist
   - [ ] Clear route → entrance remains as default start

## Console Logging

For debugging, check browser console for:
```
🚪 Entrance QR scan detected: Main Entrance on floor 1
✅ Scanned entrance set as default start location for pathfinding: Main Entrance
   Entrance will act as starting point (roomId: entrance_entrance_main_1_1) at coordinates: 150 200
🚪 Pathfinding modal: Pre-selected entrance as start: Main Entrance with roomId: entrance_entrance_main_1_1
🚪 Creating virtual room for entrance: Main Entrance
```

## Related Files

**Backend:**
- `entrance_qr_api.php` - CRUD operations for entrance QRs
- `generate_all_door_qrs.php` - Regenerate entrance QRs
- `entrance_qrcodes/` - QR code PNG files

**Frontend:**
- `mobileScreen/explore.php` - Lines 3024-3055 (entrance scan handler)
- `mobileScreen/explore.php` - Lines 3319-3395 (pathfinding modal)
- `mobileScreen/explore.php` - Lines 4324-4408 (showYouAreHereEntrance)

**Pathfinding:**
- `pathfinding.js` - Lines 1296-1423 (getEntryPointsForRoom with entrance support)
- `pathfinding.js` - Lines 1426-1449 (createVirtualRoomForEntrance)
- `pathfinding.js` - Lines 1763-1845 (calculateSingleFloorRoute entrance injection)
- `pathfinding.js` - Lines 3294-3311 (multi-floor entrance injection)

**Floor Graphs:**
- `floor_graph.json` - Floor 1 entrances array
- `floor_graph_2.json` - Floor 2 entrances array
- `floor_graph_3.json` - Floor 3 entrances array

## Future Enhancements

1. **Entrance Analytics Dashboard**
   - Most used entrance tracking
   - Peak usage times
   - Common destination patterns from each entrance

2. **Multi-Entrance Routes**
   - Show routes from all entrances simultaneously
   - Compare which entrance is closest to destination

3. **Entrance Status Toggle**
   - Admin UI to activate/deactivate entrances
   - Temporarily close entrances during events

4. **Outdoor Integration**
   - Link entrances to parking areas
   - Show outdoor approach paths

## Troubleshooting

**Problem: Entrance QR scan not working**
- Check database: `SELECT * FROM entrance_qrcodes WHERE entrance_id = 'entrance_main_1'`
- Verify `is_active = 1`
- Check QR URL format in PNG file

**Problem: Pathfinding modal doesn't auto-open**
- Check console for `window.scannedStartEntrance` object
- Verify `roomId` property exists
- Check if clicked room has valid `location` property

**Problem: Route not calculating from entrance**
- Check entrance has `nearestPathId` in database
- Verify `floor_graph.json` has matching path ID
- Check console for "Creating virtual room for entrance" message

**Problem: Entrance marker not visible**
- Check `drawEntranceIcons()` function called after floor load
- Verify entrance `x`, `y` coordinates are within SVG viewBox
- Check CSS for `.entrance-marker` class
