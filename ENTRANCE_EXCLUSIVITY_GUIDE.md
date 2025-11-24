# Entrance Exclusivity and Position Management System

## Overview
This system implements entrance-specific routing restrictions (like stair exclusivity) and ensures entrance positions are dynamically loaded from the database rather than static JSON files.

## Features

### 1. Entrance Exclusivity
Entrances can now enforce path restrictions, similar to how stair exclusivity works. Specific paths or rooms can require specific entrances for access.

**How it works:**
- Floor graph JSON files now support `entranceAccessRules` object
- Rules define which entrances can access which paths
- Pathfinding algorithm validates entrance access before route calculation
- Invalid entrance-path combinations are automatically skipped

**Configuration Example:**
```json
{
  "entranceAccessRules": {
    "path1": {
      "allowedEntranceKeys": ["entrance_west_1"],
      "enforceEntrances": true
    }
  }
}
```

### 2. Dynamic Entrance Position Loading
Entrance x,y coordinates are now read from `entrance_qrcodes` table instead of static floor graph JSON files.

**Benefits:**
- Move entrances in admin panel and changes persist after refresh
- Database is source of truth for entrance positions
- JSON files serve as fallback/default values
- Positions sync automatically on page load

### 3. Position Update API
New API endpoint to update entrance positions programmatically.

**Endpoint:** `entrance_qr_api.php?action=update_position`

**Parameters:**
- `entrance_id` (required): Entrance identifier
- `x` (required): New X coordinate
- `y` (required): New Y coordinate
- `nearest_path_id` (optional): Nearest walkable path
- `csrf_token` (required): CSRF validation token

**Example:**
```javascript
fetch('entrance_qr_api.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'update_position',
    entrance_id: 'entrance_west_1',
    x: 120,
    y: 230,
    nearest_path_id: 'path1',
    csrf_token: document.querySelector('[name="csrf_token"]').value
  })
});
```

## Database Schema

### entrance_qrcodes Table
Stores entrance positions and metadata:
```sql
- entrance_id (VARCHAR): Unique entrance identifier
- floor (INT): Floor number (1, 2, or 3)
- x (DECIMAL): X coordinate on floor plan
- y (DECIMAL): Y coordinate on floor plan
- nearest_path_id (VARCHAR): Nearest walkable path
- is_active (BOOLEAN): Whether entrance is active
```

## File Changes

### 1. Floor Graph JSON Files
**Modified files:**
- `floor_graph.json` (Floor 1)
- `floor_graph_2.json` (Floor 2)
- `floor_graph_3.json` (Floor 3)

**Changes:**
- Added `entranceAccessRules` object after `pathAccessRules`
- Empty by default (no restrictions)
- Ready for entrance-specific routing rules

### 2. Pathfinding Engine
**File:** `pathfinding.js`

**New Functions:**
```javascript
getEntranceAccessRule(graph, pathId)
getAllowedEntranceKeys(graph, pathId)
shouldEnforceEntranceRestriction(graph, pathId)
isEntranceAllowedForPath(graph, entranceId, pathId)
```

**Modified Functions:**
- `calculateSingleFloorRoute`: Now validates entrance access before route calculation

**Logic Flow:**
1. Check if user scanned entrance QR (`window.scannedStartEntrance`)
2. For each path combination, validate entrance is allowed
3. If entrance restricted, skip that path and try alternatives
4. Log restriction violations to console for debugging

### 3. Mobile Interface
**File:** `mobileScreen/explore.php`

**New Function:**
```javascript
async function fetchEntrancePositionsFromDB(floorNumber)
```
- Fetches entrance positions from `entrance_qr_api.php`
- Called during floor map loading
- Returns array of entrance records with x,y coordinates

**Updated Function:**
```javascript
loadFloorMap(floorNumber)
```
- Now fetches entrance positions from database after loading JSON
- Merges database coordinates into `window.floorGraph.entrances`
- Updates `window.scannedStartEntrance` with latest coordinates
- Logs position updates to console

### 4. API Endpoint
**File:** `entrance_qr_api.php`

**New Action:** `update_position`
- Accepts entrance_id, x, y, and optional nearest_path_id
- Validates CSRF token for security
- Updates entrance_qrcodes table
- Returns success/error JSON response

**New Function:**
```php
function updateEntrancePosition($connect, $entranceId, $x, $y, $nearestPathId = null)
```

### 5. Sync Script
**File:** `sync_entrance_positions_to_graph.php`

**Purpose:** Sync database positions back to JSON files

**Usage:**
```bash
php sync_entrance_positions_to_graph.php
```

**Process:**
1. Fetches all active entrances from database
2. Groups by floor number
3. Loads each floor graph JSON file
4. Updates entrance x,y coordinates
5. Saves modified JSON back to file
6. Logs all position changes

## Usage Guide

### Admin: Moving Entrance Icons
1. Open admin floor plan editor (`floorPlan.php`)
2. Click "Edit" mode (if entrance dragging is implemented)
3. Drag entrance icon to new position
4. System calls `entrance_qr_api.php?action=update_position`
5. Database updates immediately
6. Refresh page to see updated position

### Developer: Adding Entrance Restrictions
1. Open appropriate `floor_graph_X.json` file
2. Add rule to `entranceAccessRules`:
```json
"entranceAccessRules": {
  "path3": {
    "allowedEntranceKeys": ["entrance_main_1"],
    "enforceEntrances": true
  }
}
```
3. Save file
4. Test by scanning different entrance QRs and navigating to restricted path

### Developer: Syncing Positions
After moving entrances in database:
```bash
cd /path/to/gabay
php sync_entrance_positions_to_graph.php
```

## Console Logging

### Entrance Position Updates
```
📍 Fetched 3 entrance positions from database for floor 1
📍 Updating entrance entrance_west_1 from (70, 340) to (85, 355)
📍 Updating scanned entrance position from (70, 340) to (85, 355)
```

### Entrance Restriction Violations
```
🚫 Entrance entrance_east_1 not allowed on path path3 - skipping
✅ Valid entrance entrance_main_1 allowed on path path3
```

### Entrance Pathfinding
```
🚪 Entrance QR scan detected: Main Entrance on floor 1
✅ Scanned entrance set as default start location
🚪 Entrance start detected - creating virtual room at 920 100
```

## Troubleshooting

### "YOU ARE HERE" marker shows old position after moving entrance
**Cause:** Browser cached old entrance data
**Solution:** 
1. Hard refresh (Ctrl+Shift+R)
2. Clear session storage
3. Verify database has updated x,y coordinates:
```sql
SELECT entrance_id, x, y FROM entrance_qrcodes WHERE entrance_id = 'entrance_west_1';
```

### Pathfinding fails with entrance restrictions
**Symptoms:** "No available route" error despite valid path
**Debug steps:**
1. Check console for `🚫 Entrance X not allowed on path Y` messages
2. Verify entrance ID matches `allowedEntranceKeys` array
3. Ensure `enforceEntrances: true` is set
4. Try removing restriction temporarily to isolate issue

### Entrance position not updating in JSON file
**Cause:** `sync_entrance_positions_to_graph.php` not run
**Solution:** Run sync script:
```bash
php sync_entrance_positions_to_graph.php
```

### API returns "Invalid CSRF token"
**Cause:** CSRF token missing or expired
**Solution:**
```php
// In PHP template
<input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

// In JavaScript
const token = document.querySelector('[name="csrf_token"]').value;
```

## Security Considerations

### CSRF Protection
- All entrance position updates require valid CSRF token
- Tokens auto-regenerated every 15 minutes
- POST requests only for state-changing operations

### Input Validation
- Coordinates validated as numeric
- Entrance IDs sanitized against SQL injection
- Floor numbers restricted to 1-3

### Access Control
- Update API requires admin authentication (via `auth_guard.php`)
- Sync script runs server-side only
- Mobile interface read-only for entrance positions

## Future Enhancements

### Planned Features
1. **Drag-and-drop entrance editing** in admin panel
2. **Entrance icon customization** (color, size, style)
3. **Multi-entrance routing** (compare routes from different entrances)
4. **Entrance-specific restrictions per room** (not just per path)
5. **Analytics dashboard** for entrance usage patterns

### Technical Improvements
1. **Real-time position sync** via WebSocket
2. **Entrance collision detection** (prevent overlapping icons)
3. **Undo/redo** for entrance moves
4. **Batch position updates** (move multiple entrances at once)

## Related Documentation
- `STAIR_EXCLUSIVITY_GUIDE.md` - Stair restriction system (similar pattern)
- `NAVIGATION_CONFIG_GUIDE.md` - Pathfinding configuration
- `QR_SCAN_PATHFINDING_INTEGRATION.md` - QR scan to routing flow
- `SEARCH_FEATURE_GUIDE.md` - Search bar entrance navigation

## Support
For issues or questions:
1. Check console logs for detailed error messages
2. Verify database schema matches expected structure
3. Review CSRF token implementation
4. Test with single entrance first before adding restrictions
