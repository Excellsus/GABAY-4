# Entrance QR System - Implementation Guide

## Overview
The entrance QR system allows building entrances to serve as independent starting points for pathfinding navigation. Entrances are not tied to specific offices and don't appear in office statistics.

## Quick Start

### 1. Run Database Migration
```bash
php create_entrance_tables.php
```

This creates:
- `entrance_qrcodes` table - Stores entrance QR data
- `entrance_scan_logs` table - Tracks entrance scans (isolated from office statistics)

### 2. Access Entrance Management
1. Login to admin panel
2. Navigate to **Entrance Management** (added to sidebar)
3. Click **"Generate All Entrance QR Codes"**
4. Download QR codes for printing

### 3. Test Entrance Scanning
1. Print an entrance QR code
2. Scan with mobile device camera
3. URL format: `explore.php?entrance_qr=1&entrance_id=entrance_main_1&floor=1`
4. Verify:
   - Floor switches to entrance floor
   - Green pulsing marker appears at entrance location
   - "🚪 YOU ARE HERE" label displays
   - Entrance set as default pathfinding start

## Architecture

### Database Schema

**entrance_qrcodes**
```sql
- id (PK)
- entrance_id (UNIQUE, e.g., 'entrance_main_1')
- floor (1, 2, or 3)
- label ('Main Entrance')
- x, y (SVG coordinates)
- nearest_path_id (pathfinding integration)
- qr_code_data (full URL)
- qr_code_image (filename)
- is_active (toggle)
```

**entrance_scan_logs**
```sql
- id (PK)
- entrance_id (FK)
- entrance_qr_id (FK to entrance_qrcodes.id)
- check_in_time
- session_id, user_agent, ip_address
```

### Floor Graph Integration

Entrances are defined in floor graph JSON files:

**floor_graph.json (Floor 1)**
```json
{
  "entrances": [
    {
      "id": "entrance_main_1",
      "label": "Main Entrance",
      "type": "entrance",
      "floor": 1,
      "x": 920,
      "y": 50,
      "nearestPathId": "path2"
    }
  ]
}
```

### File Structure

```
gabay/
├── create_entrance_tables.php         # Database migration
├── entrance_qr_api.php                # CRUD API endpoints
├── entranceManagement.php             # Admin UI
├── entrance_qrcodes/                  # QR code images (auto-created)
│   └── entrance_main_1_floor_1.png
├── floor_graph.json                   # Floor 1 entrances
├── floor_graph_2.json                 # Floor 2 entrances
├── floor_graph_3.json                 # Floor 3 entrances
├── pathfinding.js                     # Updated getEntryPointsForRoom()
└── mobileScreen/
    └── explore.php                    # Entrance scan handling + visualization
```

## API Endpoints (entrance_qr_api.php)

All endpoints require admin authentication and CSRF validation for state changes.

### Generate QR Codes
```php
POST entrance_qr_api.php
action=generate
csrf_token={token}

Response:
{
  "success": true,
  "message": "Generated 3 QR codes, skipped 0 existing",
  "generated": 3,
  "skipped": 0
}
```

### Get All Entrances
```php
GET entrance_qr_api.php?action=get_all

Response:
{
  "success": true,
  "entrances": [
    {
      "id": 1,
      "entrance_id": "entrance_main_1",
      "floor": 1,
      "label": "Main Entrance",
      "x": "920.00",
      "y": "50.00",
      "nearest_path_id": "path2",
      "qr_code_data": "http://localhost/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_main_1&floor=1",
      "qr_code_image": "entrance_main_1_floor_1.png",
      "qr_code_path": "entrance_qrcodes/entrance_main_1_floor_1.png",
      "is_active": 1
    }
  ]
}
```

### Get Entrances by Floor
```php
GET entrance_qr_api.php?action=get_by_floor&floor=1
```

### Delete Entrance QR
```php
POST entrance_qr_api.php
action=delete
entrance_id=entrance_main_1
csrf_token={token}
```

### Toggle Active Status
```php
POST entrance_qr_api.php
action=toggle_status
entrance_id=entrance_main_1
is_active=0
csrf_token={token}
```

### Regenerate QR Code
```php
POST entrance_qr_api.php
action=regenerate
entrance_id=entrance_main_1
csrf_token={token}
```

## Pathfinding Integration

### How It Works

1. **Entrance QR Scan** → `explore.php` detects `?entrance_qr=1`
2. **Validation** → Checks entrance exists and is active in database
3. **Logging** → Logs to `entrance_scan_logs` (NOT `qr_scan_logs`)
4. **Visualization** → Shows green pulsing marker + "YOU ARE HERE" label
5. **Global State** → Sets `window.scannedStartEntrance` object
6. **Pathfinding** → `getEntryPointsForRoom()` returns entrance coordinates

### JavaScript Variables

```javascript
// Set by PHP when entrance QR scanned
window.scannedEntranceFromPHP = {
  entrance_id: "entrance_main_1",
  floor: "1",
  label: "Main Entrance",
  x: "920.00",
  y: "50.00",
  nearest_path_id: "path2"
};

// Processed into global state
window.scannedStartEntrance = {
  id: "entrance_main_1",
  label: "Main Entrance",
  floor: 1,
  x: 920,
  y: 50,
  nearestPathId: "path2"
};
```

### Entrance as Start Location

In pathfinding modal:
- **Start dropdown** shows: `"Main Entrance 🚪 (YOU ARE HERE)"`
- **Value** format: `entrance_{id}_{floor}` (e.g., `entrance_main_1_1`)
- **getEntryPointsForRoom()** handles entrance ID pattern
- Returns entrance coordinates as entry point

### Visual Markers

Entrance marker (explore.php):
```javascript
// Green pulsing circle
<circle cx="920" cy="50" r="20" fill="#00ff00" opacity="0.3" stroke="#00ff00" stroke-width="3">
  <animate attributeName="r" values="20;25;20" dur="2s" repeatCount="indefinite"/>
</circle>

// Label
<text x="920" y="20" fill="#00ff00" font-weight="bold">🚪 YOU ARE HERE</text>
```

## Statistics Exclusion

Entrance scans are **automatically excluded** from office statistics:

- `home.php` queries only `qr_scan_logs` table (offices)
- Entrance scans go to `entrance_scan_logs` table (isolated)
- No code changes needed in existing statistics queries

## Testing Checklist

### Database Migration
- [ ] Run `create_entrance_tables.php`
- [ ] Verify tables created: `entrance_qrcodes`, `entrance_scan_logs`
- [ ] Check foreign key constraints

### Admin UI
- [ ] Login to admin panel
- [ ] Access `entranceManagement.php`
- [ ] Generate QR codes for all floors
- [ ] Download an entrance QR
- [ ] Toggle entrance active/inactive
- [ ] Delete an entrance QR
- [ ] Filter entrances by floor

### QR Code Scanning
- [ ] Print/display entrance QR
- [ ] Scan with mobile device
- [ ] Verify floor switches to entrance floor
- [ ] Check green marker appears at entrance location
- [ ] Confirm "YOU ARE HERE" label displays
- [ ] Verify entrance name shown

### Pathfinding
- [ ] After scanning entrance QR, click any room
- [ ] Verify pathfinding modal opens
- [ ] Check entrance pre-selected as start location
- [ ] Select a destination room
- [ ] Click "Find Route"
- [ ] Verify path starts from entrance coordinates
- [ ] Test cross-floor routing from entrance

### Statistics Verification
- [ ] Scan entrance QR multiple times
- [ ] Access `home.php` dashboard
- [ ] Verify entrance scans NOT counted in office QR statistics
- [ ] Check office QR counts remain unchanged
- [ ] Verify door QR statistics unaffected

### Error Handling
- [ ] Try scanning inactive entrance QR → Expect 404 redirect
- [ ] Try scanning deleted entrance QR → Expect 404 redirect
- [ ] Test with empty entrances array → Expect empty state UI
- [ ] Test pathfinding from non-existent entrance → Expect graceful failure

## Troubleshooting

### QR Generation Fails
**Error:** "No entrances found in floor graph JSON files"
**Solution:** Add `"entrances": []` array to floor graph files

### Entrance Not Appearing After Scan
**Check:**
1. Database: `SELECT * FROM entrance_qrcodes WHERE entrance_id = 'entrance_main_1'`
2. `is_active = 1`
3. Floor graph has matching entrance definition
4. `x`, `y` coordinates within SVG viewport

### Pathfinding Not Starting From Entrance
**Check:**
1. `window.scannedStartEntrance` set in console
2. Start location dropdown shows entrance
3. Entrance ID format: `entrance_{id}_{floor}`
4. `getEntryPointsForRoom()` recognizes entrance pattern

### Entrance Scans Showing in Office Statistics
**Check:**
1. Scans going to `entrance_scan_logs` table (not `qr_scan_logs`)
2. `home.php` queries filtered correctly
3. No accidental JOIN to entrance tables

## Future Enhancements

### Suggested Improvements
1. **Visual Floor Plan Editor** — Drag-and-drop entrance placement in `floorPlan.php`
2. **Entrance Icons** — Custom SVG icons for different entrance types (main, side, emergency)
3. **Entrance Analytics** — Separate dashboard for entrance scan statistics
4. **Multi-Language** — Entrance labels in multiple languages
5. **Accessibility** — Audio descriptions for entrances
6. **Emergency Mode** — Highlight nearest emergency exits from any entrance

### Integration Points
- **Geofencing** — Auto-select entrance based on user's GPS location
- **Hours Management** — Define entrance operating hours (locked at night)
- **Access Control** — Restrict certain entrances to authorized users
- **Notifications** — Alert admins when entrance QRs scanned

## Code References

### Key Functions

**explore.php**
- `showYouAreHereEntrance()` — Renders entrance marker and labels
- Entrance scan logging (lines 209-273)
- Entrance floor switching (lines 2968-3004)

**pathfinding.js**
- `getEntryPointsForRoom()` — Updated to handle entrance IDs (lines 1291-1400)

**entrance_qr_api.php**
- `generateEntranceQRCodes()` — Reads floor graphs and creates QR codes
- `getEntrancesFromFloorGraphs()` — Parses entrance arrays from JSON

## Support

For issues or questions:
1. Check `error_log` for PHP errors
2. Check browser console for JavaScript errors
3. Verify floor graph JSON syntax
4. Test with simplified entrance definition

## License
Part of GABAY Indoor Navigation System
