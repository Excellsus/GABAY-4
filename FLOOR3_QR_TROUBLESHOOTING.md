# Floor 3 QR Code Generation Troubleshooting Guide

## Problem
QR code generation fails for offices on the third floor.

## Root Causes & Solutions

### 1. **No Offices Assigned to Floor 3** (Most Common)

**Symptoms:**
- QR generation button appears but fails when clicked
- Error message may say "No door points found for this room"

**Diagnosis:**
1. Open `officeManagement.php`
2. Look at the office list - are any offices assigned to floor 3 rooms?
3. Office locations should be like: `room-1-3`, `room-2-3`, `room-3-3`, etc.

**Solution:**
1. Click on an office in the list
2. Click the Edit button (pencil icon)
3. In the edit form, click "Select Room on Floor Plan"
4. Switch to "Floor 3" using the floor selector
5. Click on a room on the 3rd floor SVG map
6. Save the office

**Alternative - Assign via Floor Plan page:**
1. Go to Floor Plan page
2. Click floor selector → Floor 3
3. Click "Edit" button (top right)
4. Drag offices from the sidebar to rooms on the floor plan
5. Click "Save" when done

### 2. **Floor Graph File Missing or Malformed**

**Symptoms:**
- Error: "Floor graph file not found for floor 3"
- Error: "Failed to parse floor graph JSON"

**Diagnosis:**
```bash
# Check if file exists
Test-Path "c:\Program Files\xampp\htdocs\FinalDev\floor_graph_3.json"

# Check if JSON is valid
Get-Content "c:\Program Files\xampp\htdocs\FinalDev\floor_graph_3.json" | ConvertFrom-Json
```

**Solution:**
Ensure `floor_graph_3.json` exists in the root directory and contains valid JSON with proper structure:
```json
{
  "rooms": {
    "room-1-3": {
      "doorPoints": [{"x": 815, "y": 233}],
      "nearestPathId": "lobby_vertical_1_floor3"
    },
    "room-2-3": {
      "doorPoints": [{"x": 833, "y": 355}],
      "nearestPathId": "path1_floor3"
    }
    // ... more rooms
  },
  "walkablePaths": [ /* ... */ ]
}
```

### 3. **Room Missing doorPoints Property**

**Symptoms:**
- Error: "No doorPoints found for room 'room-X-3'"
- Error lists available properties but not doorPoints

**Diagnosis:**
1. Open `floor_graph_3.json`
2. Find the room entry (e.g., `"room-1-3"`)
3. Check if it has a `doorPoints` array

**Solution:**
Add doorPoints to the room definition:
```json
"room-1-3": {
  "doorPoints": [
    {"x": 815, "y": 233}
  ],
  "nearestPathId": "lobby_vertical_1_floor3"
}
```

### 4. **SVG Room IDs Don't Match Database**

**Symptoms:**
- Office assigned to floor 3 but QR generation says room not found in graph
- Error: "Room 'room-X-3' not found in floor 3 graph"

**Diagnosis:**
1. Check office location in database: `SELECT location FROM offices WHERE id = ?`
2. Check if that exact ID exists in `floor_graph_3.json` under `rooms`
3. Check if that exact ID exists in `SVG/Capitol_3rd_floor_layout_6.svg`

**Solution - If IDs are inconsistent:**
```sql
-- Update office location to match SVG/graph ID
UPDATE offices 
SET location = 'room-1-3' 
WHERE id = 123;
```

OR update the SVG and floor graph to use consistent IDs.

### 5. **PHP Error Logging Not Enabled**

**Symptoms:**
- Generic "failed" message with no details

**Solution:**
Enable error logging to see detailed errors:

1. Check `php.ini` settings:
```ini
error_reporting = E_ALL
display_errors = On
log_errors = On
error_log = "C:/xampp/php/logs/php_error_log"
```

2. Check Apache error log:
```
C:\xampp\apache\logs\error.log
```

3. Check browser console (F12) for JavaScript errors

## Testing Procedure

### Manual Test:
1. Go to `officeManagement.php`
2. Find an office assigned to floor 3 (location like `room-1-3`)
3. Click the QR code icon for that office
4. Click "Generate All Door QR Codes"
5. Check browser console (F12 → Console tab) for error messages
6. Check PHP error log for server-side errors

### Automated Test:
Run the diagnostic script:
```
http://localhost/FinalDev/test_floor3_offices.php
```

This will show:
- ✅ Offices assigned to floor 3
- ✅ Floor graph file status
- ✅ Room existence in graph
- ✅ doorPoints availability
- ❌ Any configuration issues

## Recent Improvements (Applied)

### Enhanced Error Messages
`door_qr_api.php` now provides detailed error messages:
- ❌ "Floor graph file not found: /path/to/file (Floor 3)"
- ❌ "Room 'room-1-3' not found in floor 3 graph. Available rooms: room-1-3, room-2-3..."
- ❌ "No doorPoints found for room 'room-1-3'. Room has properties: nearestPathId, style"
- ❌ "Failed to parse floor graph JSON: Syntax error"

### JavaScript Error Handling
`officeManagement.php` now shows detailed AJAX errors in browser console and alert dialogs.

### Debug Logging for Floor 3
All floor 3 QR generation attempts are logged to PHP error log with full context.

## Quick Fix Checklist

- [ ] At least one office is assigned to a floor 3 room (`room-X-3`)
- [ ] File `floor_graph_3.json` exists
- [ ] File `floor_graph_3.json` contains valid JSON
- [ ] Floor graph has `rooms` property
- [ ] Each room in graph has `doorPoints` array
- [ ] Office `location` matches room ID in graph exactly (case-sensitive)
- [ ] PHP error logging is enabled
- [ ] Browser console shows no JavaScript errors
- [ ] CSRF token is valid (page not stale)

## Common Error Messages & Solutions

| Error Message | Cause | Solution |
|--------------|-------|----------|
| "Office has no location assigned" | Office not placed on floor plan | Assign office to a room via Edit form or Floor Plan page |
| "Floor graph file not found for floor 3" | Missing `floor_graph_3.json` | Create/restore the floor graph file |
| "Room 'room-X-3' not found in floor 3 graph" | Room ID mismatch | Ensure office.location matches graph room ID |
| "No doorPoints found for room" | Room missing doorPoints property | Add doorPoints array to room in graph JSON |
| "Failed to parse floor graph JSON" | Malformed JSON syntax | Validate JSON syntax (trailing commas, brackets) |
| "CSRF token invalid" | Stale page | Refresh the page and try again |

## Prevention

To avoid floor 3 QR issues in the future:
1. Always assign offices to rooms via the Floor Plan editor (not manual DB edits)
2. Never edit `floor_graph_3.json` without JSON validation
3. Keep SVG room IDs, database locations, and graph room IDs synchronized
4. Test QR generation immediately after assigning an office to floor 3

## Contact/Support

If issues persist after following this guide:
1. Run `test_floor3_offices.php` and save the output
2. Check `C:\xampp\php\logs\php_error_log` for recent errors
3. Check browser console (F12) and save any error messages
4. Provide all diagnostic output for further analysis
