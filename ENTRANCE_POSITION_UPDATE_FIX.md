# Entrance Position Update System - How It Works

## Problem
When you update entrance positions in the database, the "YOU ARE HERE" marker and green overlay circle were not showing at the updated coordinates after scanning the entrance QR code.

## Root Cause
The entrance icons and markers were being drawn using coordinates from the floor graph JSON files **before** the database positions were fetched and merged. This created a race condition where:
1. Floor loads with JSON coordinates (e.g., X: 70, Y: 340)
2. Entrance icons drawn at JSON position
3. Database positions fetched asynchronously (e.g., X: 100, Y: 215)
4. Coordinates updated in memory
5. BUT icons were never redrawn with new coordinates

## Solution
Updated `explore.php` to **redraw entrance icons and markers** after database positions are loaded.

## How It Works Now

### Step-by-Step Flow

1. **Page Load / QR Scan**
   - User scans entrance QR code
   - PHP reads entrance data from `entrance_qrcodes` table
   - Sets `window.scannedEntranceFromPHP` with DATABASE coordinates

2. **Floor Map Loads**
   ```javascript
   loadFloorMap(floorNumber)
   ```
   - Loads SVG floor plan
   - Loads floor graph JSON (with default positions)
   - Stores in `window.floorGraph.entrances`

3. **Database Position Fetch** (ASYNC)
   ```javascript
   fetchEntrancePositionsFromDB(floorNumber)
   ```
   - Calls `entrance_qr_api.php?action=get_by_floor&floor=X`
   - Returns entrance records from database with current x,y coordinates

4. **Position Merge**
   ```javascript
   window.floorGraph.entrances.forEach(entrance => {
     const dbEntrance = dbEntrances.find(db => db.entrance_id === entrance.id);
     if (dbEntrance) {
       entrance.x = parseFloat(dbEntrance.x);
       entrance.y = parseFloat(dbEntrance.y);
     }
   });
   ```

5. **Scanned Entrance Update**
   ```javascript
   if (window.scannedStartEntrance) {
     const dbEntrance = dbEntrances.find(db => db.entrance_id === window.scannedStartEntrance.id);
     window.scannedStartEntrance.x = parseFloat(dbEntrance.x);
     window.scannedStartEntrance.y = parseFloat(dbEntrance.y);
   }
   ```

6. **CRITICAL: Redraw with Updated Coordinates** ✨
   ```javascript
   // Redraw "YOU ARE HERE" marker
   setTimeout(() => {
     window.showYouAreHereEntrance(window.scannedStartEntrance);
   }, 100);
   
   // Redraw entrance icons
   drawEntranceIcons(window.floorGraph.entrances, floorNumber);
   ```

## Where Coordinates Come From

### For QR Scans (PHP)
**File:** `mobileScreen/explore.php` (lines 203-274)
```php
$stmt = $connect->prepare("SELECT * FROM entrance_qrcodes WHERE entrance_id = ? AND is_active = 1");
$entrance_data = $stmt->fetch(PDO::FETCH_ASSOC);
$scanned_entrance = $entrance_data; // Contains x, y from DATABASE
```

### For Floor Icons (JavaScript)
**File:** `mobileScreen/explore.php` (lines 4773-4815)
```javascript
fetchEntrancePositionsFromDB(floorNumber).then(dbEntrances => {
  // Merges DATABASE positions into JSON positions
  // Then redraws icons
});
```

## Testing Your Position Updates

### Method 1: Update Database Position
```php
$stmt = $connect->prepare("UPDATE entrance_qrcodes SET x = 150, y = 250 WHERE entrance_id = 'entrance_west_1'");
$stmt->execute();
```

### Method 2: Use API Endpoint
```javascript
fetch('entrance_qr_api.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'update_position',
    entrance_id: 'entrance_west_1',
    x: 150,
    y: 250,
    csrf_token: token
  })
});
```

### Verify Update
1. Scan entrance QR code
2. Open browser console
3. Look for these messages:
```
📍 Fetched 3 entrance positions from database for floor 1
📍 Updating entrance entrance_west_1 from (70, 340) to (150, 250)
📍 Updating scanned entrance position from (70, 340) to (150, 250)
🔄 Redrawing YOU ARE HERE marker with updated entrance position
🔄 Redrawing entrance icons with updated positions
```
4. "YOU ARE HERE" marker should appear at (150, 250)
5. Green entrance icon should appear at (150, 250)

## Important Notes

### Database is Source of Truth
- **Database (`entrance_qrcodes` table)** = LIVE position used by system
- **JSON files (`floor_graph.json`)** = Default/fallback positions only

### When to Update JSON Files
Run sync script to save database positions to JSON:
```bash
php sync_entrance_positions_to_graph.php
```
This updates the JSON files with database positions (useful for version control).

### Cache Clearing
If positions don't update:
1. Hard refresh browser (Ctrl + Shift + R)
2. Clear browser cache
3. Check database to confirm position saved:
```sql
SELECT entrance_id, x, y FROM entrance_qrcodes WHERE entrance_id = 'entrance_west_1';
```

## Console Logging

### Success Messages
```
✅ Scanned entrance set as default start location
📍 Fetched 3 entrance positions from database for floor 1
📍 Updating entrance entrance_west_1 from (70, 340) to (150, 250)
🔄 Redrawing YOU ARE HERE marker with updated entrance position
🔄 Redrawing entrance icons with updated positions
YOU ARE HERE entrance marker and labels added at: {x: 150, y: 250}
```

### Error Messages
```
❌ Could not fetch entrance positions from database: [error]
⚠️ Entrance QR does not exist or is inactive
```

## Files Modified

### `mobileScreen/explore.php`
- **Lines 4773-4815**: Added entrance icon and marker redraw after database positions load
- **Lines 4656-4672**: Added `fetchEntrancePositionsFromDB()` function

### `entrance_qr_api.php`
- **Lines 460-487**: Added `update_position` action and `updateEntrancePosition()` function

### `sync_entrance_positions_to_graph.php`
- New script to sync database → JSON (optional, for persistence)

## Troubleshooting

### Issue: "YOU ARE HERE" shows at wrong position
**Cause:** Browser cached old entrance data
**Fix:** Hard refresh (Ctrl + Shift + R)

### Issue: Position doesn't update after database change
**Cause:** Page loaded before database update
**Fix:** Refresh page after updating database

### Issue: Console shows old coordinates
**Cause:** Database not actually updated
**Fix:** Check database directly:
```sql
SELECT * FROM entrance_qrcodes WHERE entrance_id = 'entrance_west_1';
```

### Issue: Green overlay circle missing
**Cause:** `drawEntranceIcons()` function not called after position update
**Fix:** Check console for "🔄 Redrawing entrance icons" message

## Summary

✅ **Database positions are now used for:**
- "YOU ARE HERE" marker position
- Green entrance icon position
- Entrance overlay circle position
- Pathfinding starting coordinates

✅ **System automatically redraws after fetching database positions**

✅ **No manual intervention needed - just update the database and refresh**
