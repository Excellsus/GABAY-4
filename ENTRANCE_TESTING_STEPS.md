# Entrance QR System - Quick Testing Steps

## Prerequisites
✅ XAMPP running (Apache + MySQL)
✅ Access to `http://localhost/gabay/`
✅ Admin account credentials

## Step 1: Run Database Migration
```bash
# In PowerShell, navigate to gabay directory
cd "C:\Program Files\xampp\htdocs\gabay"

# Run migration script
php create_entrance_tables.php
```

**Expected Output:**
```
✓ Created table: entrance_qrcodes
✓ Created table: entrance_scan_logs

========================================
✅ SUCCESS: Entrance tables created!
========================================
```

## Step 2: Verify Floor Graphs Have Entrances
The system already includes sample entrances:

**Floor 1** (`floor_graph.json`):
- `entrance_main_1` - Main Entrance
- `entrance_west_1` - West Entrance  
- `entrance_east_1` - East Entrance

**Floor 2** (`floor_graph_2.json`):
- `entrance_main_2` - Main Entrance (Floor 2)
- `entrance_west_2` - West Entrance (Floor 2)

**Floor 3** (`floor_graph_3.json`):
- `entrance_main_3` - Main Entrance (Floor 3)
- `entrance_west_3` - West Entrance (Floor 3)

## Step 3: Generate QR Codes
1. Open browser: `http://localhost/gabay/login.php`
2. Login with admin credentials
3. Navigate to **Entrance Management** (in sidebar)
4. Click **"Generate All Entrance QR Codes"** button
5. Verify success message shows count of generated codes

**Expected Result:**
- 7 entrances should be generated (3 floor 1, 2 floor 2, 2 floor 3)
- Each entrance shows as a card with Download/Toggle/Delete buttons

## Step 4: Download and Test a QR Code
1. Click **"⬇ Download QR"** on any entrance card
2. QR code image downloads (e.g., `entrance_main_1_floor_1.png`)
3. Display QR code on phone/tablet or print it
4. Access mobile view: `http://localhost/gabay/mobileScreen/explore.php`
5. Scan the entrance QR code with phone camera

**Expected Behavior:**
- ✅ Floor switches to entrance floor (if not already there)
- ✅ Green pulsing circle appears at entrance location
- ✅ "🚪 YOU ARE HERE" label displays above entrance
- ✅ Entrance name shows below "YOU ARE HERE"
- ✅ Map automatically pans to center entrance

## Step 5: Test Pathfinding from Entrance
1. After scanning entrance QR, click any room/office on the map
2. Verify pathfinding modal opens
3. Check **Start Location** dropdown shows:
   - Entrance name with 🚪 emoji
   - "(YOU ARE HERE)" suffix
   - Pre-selected as start
4. Select a destination room
5. Click **"Find Route"**
6. Verify path draws from entrance to destination

**Expected Routing:**
- Path starts at entrance coordinates (x, y)
- Uses entrance's `nearestPathId` to connect to walkable path
- Correctly handles cross-floor routing if destination on different floor

## Step 6: Verify Statistics Exclusion
1. Scan entrance QR 5-10 times
2. Navigate to **Dashboard** (`home.php`)
3. Check QR statistics section
4. **Verify:** Entrance scans do NOT appear in office QR counts

**Test Query (Optional):**
```sql
-- Check entrance scans are logged separately
SELECT * FROM entrance_scan_logs ORDER BY check_in_time DESC LIMIT 10;

-- Verify office scan logs are separate
SELECT * FROM qr_scan_logs WHERE office_id IS NOT NULL ORDER BY check_in_time DESC LIMIT 10;
```

## Step 7: Test Active/Inactive Toggle
1. In **Entrance Management**, click **"✗ Inactive"** button on an entrance
2. Status changes to inactive (card grays out)
3. Try scanning that entrance's QR code
4. **Expected:** Redirected to 404 error page
5. Click **"✓ Active"** to re-enable
6. Scan QR again - should work normally

## Step 8: Test Delete Functionality
1. Click **"🗑 Delete"** on test entrance
2. Confirm deletion
3. **Expected:**
   - Entrance removed from UI
   - QR code file deleted from `entrance_qrcodes/` folder
   - Database records removed (cascading delete includes scan logs)

## Troubleshooting

### Migration Script Fails
**Error:** "Connection refused" or "Access denied"
- Check `connect_db.php` has correct MySQL credentials
- Verify MySQL is running in XAMPP control panel

### No Entrances Appear in Management UI
**Error:** Empty state shows "No Entrances Found"
- Run: `php create_entrance_tables.php` again
- Click **"Generate All Entrance QR Codes"**
- Check browser console for JavaScript errors

### QR Scan Doesn't Highlight Entrance
**Check:**
1. Browser console for errors
2. Floor graph has entrance definition with correct `x`, `y` coordinates
3. Entrance is active in database: `SELECT * FROM entrance_qrcodes WHERE entrance_id = 'entrance_main_1'`

### Entrance Shows in Office Statistics
**Issue:** Scans logged to wrong table
- Check: `SELECT * FROM entrance_scan_logs` should have records
- NOT in: `qr_scan_logs` (office-only)
- Verify `explore.php` lines 243-270 log to `entrance_scan_logs`

### Pathfinding Doesn't Start from Entrance
**Debug:**
```javascript
// In browser console after scanning entrance QR:
console.log(window.scannedStartEntrance);
// Should show: {id, label, floor, x, y, nearestPathId}

console.log(window.scannedStartOffice);
// Should be: null (entrance takes precedence)
```

## Manual Testing URLs

### Direct Entrance QR Simulation
Instead of scanning, manually navigate to:
```
http://localhost/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_main_1&floor=1
```

### Test Each Entrance
```
Floor 1:
- Main:  ?entrance_qr=1&entrance_id=entrance_main_1&floor=1
- West:  ?entrance_qr=1&entrance_id=entrance_west_1&floor=1
- East:  ?entrance_qr=1&entrance_id=entrance_east_1&floor=1

Floor 2:
- Main:  ?entrance_qr=1&entrance_id=entrance_main_2&floor=2
- West:  ?entrance_qr=1&entrance_id=entrance_west_2&floor=2

Floor 3:
- Main:  ?entrance_qr=1&entrance_id=entrance_main_3&floor=3
- West:  ?entrance_qr=1&entrance_id=entrance_west_3&floor=3
```

## Success Criteria

✅ **All tests pass when:**
1. Database tables created without errors
2. All 7 entrances generate QR codes
3. Entrance QR scan shows "YOU ARE HERE" marker
4. Pathfinding uses entrance as start location
5. Entrance scans excluded from office statistics
6. Inactive entrances redirect to 404
7. Deleted entrances cannot be scanned

## Next Steps After Testing

1. **Print QR Codes** — Download all entrance QR codes and print at high DPI
2. **Place at Locations** — Mount printed QR codes at actual building entrances
3. **User Training** — Show visitors how to scan entrance QRs to start navigation
4. **Monitor Usage** — Check `entrance_scan_logs` table for scan frequency
5. **Adjust Coordinates** — If markers appear in wrong location, update `x`, `y` in floor graphs

## Performance Notes

- Entrance QR scans are logged ONCE per session (prevents duplicates)
- Session key format: `entrance_scanned_{entrance_id}`
- Scan logs include: timestamp, session ID, user agent, IP address
- QR generation reads floor graphs each time (not cached)

## Support Commands

### Clear entrance scan session (for re-testing):
```php
// In PHP console or add to explore.php temporarily:
session_start();
unset($_SESSION['entrance_scanned_entrance_main_1']);
```

### Reset all entrance data:
```sql
-- Delete all scan logs
TRUNCATE TABLE entrance_scan_logs;

-- Delete all QR codes
DELETE FROM entrance_qrcodes;

-- Regenerate from floor graphs
-- Then click "Generate All Entrance QR Codes" in UI
```

### Verify entrance coordinates match SVG:
```javascript
// In browser console on explore.php:
const svg = document.querySelector('#capitol-map-svg');
const viewBox = svg.getAttribute('viewBox');
console.log('SVG viewBox:', viewBox);
// Entrance x,y should be within viewBox dimensions
```

---

**Last Updated:** Implementation completed
**System Status:** Ready for testing
**Documentation:** See `ENTRANCE_QR_SYSTEM_GUIDE.md` for full details
