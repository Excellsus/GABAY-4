# QR Scan Pathfinding Integration

## Overview
When a user scans an office QR code, that scanned office automatically becomes the **permanent default start location** for all subsequent pathfinding operations. When the user clicks on any other room, the pathfinding modal opens automatically with the scanned office as the start and the clicked room as the destination.

## Implementation Details

### 1. Global State Management
**Location:** Lines ~1186-1191 in `explore.php`

```javascript
// Store the scanned office as the permanent default start location
window.scannedStartOffice = null; // Will be set when QR code is scanned
```

This global variable persists throughout the session and stores the complete office object from the QR scan.

### 2. QR Scan Detection and Storage
**Location:** Lines ~2159-2168 in `explore.php`

When an office QR code is scanned (via `office_id` URL parameter):
1. The system fetches the office data
2. Stores it in `window.scannedStartOffice`
3. Logs the action for debugging
4. Displays the office details in the drawer
5. Re-fits the SVG to account for the drawer

```javascript
// CRITICAL: Store scanned office as the permanent default start location for pathfinding
window.scannedStartOffice = targetOffice;
console.log('✅ Scanned office set as default start location for pathfinding:', targetOffice.name);
```

### 3. Smart Room Click Handler
**Location:** Lines ~2433-2490 in `explore.php`

The `mobileRoomClickHandler` function now implements conditional logic:

#### Scenario A: User Scanned QR + Clicks Different Room
- **Trigger:** `window.scannedStartOffice` exists AND clicked room is different
- **Action:** Opens pathfinding modal with:
  - Start: Scanned office (pre-selected, labeled "YOU ARE HERE")
  - Destination: Clicked office (pre-selected)
- **User Experience:** Instant pathfinding setup with one tap

#### Scenario B: User Clicks Room Without QR Scan OR Clicks Same Room
- **Trigger:** No scanned office OR clicked room is the same as scanned office
- **Action:** Shows office details drawer normally
- **User Experience:** Standard office information display

```javascript
// If user scanned a QR code and is now clicking a DIFFERENT room, open pathfinding modal
if (window.scannedStartOffice && 
    window.scannedStartOffice.location !== roomId && 
    office) {
  console.log('🎯 User clicked different room after QR scan - opening pathfinding modal');
  openPathfindingModalWithDestination(office);
} else if (office) {
  // Normal behavior: just show office details
  handleRoomClick(office);
}
```

### 4. Pathfinding Modal Pre-Population
**Location:** Lines ~2449-2490 in `explore.php`

The `openPathfindingModalWithDestination()` function handles automatic modal setup:

#### Start Location Dropdown
1. Clears existing options
2. Adds scanned office as first option with "(YOU ARE HERE)" label
3. Pre-selects this option
4. Adds all other offices as alternatives

#### Destination Location Dropdown
1. Clears existing options
2. Adds all available offices
3. Pre-selects the clicked destination office

**Sorting Logic:** Both dropdowns sort offices by:
1. Floor number (ascending)
2. Alphabetically within each floor

### 5. Directions Button Integration
**Location:** Lines ~4975-4997 in `explore.php`

When user clicks the "Directions" button in the drawer:
- If `window.scannedStartOffice` exists: Pre-fills start location
- Otherwise: Shows empty "Select starting point..." prompt
- Destination field always starts empty for manual selection

```javascript
if (window.scannedStartOffice && window.scannedStartOffice.location) {
  defaultStartLocation = window.scannedStartOffice.location;
  defaultStartText = window.scannedStartOffice.name + ' (YOU ARE HERE)';
  
  // Add default start option for scanned office
  const defaultStart = document.createElement('option');
  defaultStart.value = defaultStartLocation;
  defaultStart.textContent = defaultStartText;
  defaultStart.selected = true;
  startLocationSelect.appendChild(defaultStart);
  
  console.log('📍 Pre-filled start location from scanned QR:', defaultStartText);
}
```

## User Workflows

### Workflow 1: Direct Navigation After QR Scan
1. User scans office QR code (e.g., "Office A")
2. Floor map loads with "Office A" highlighted ("YOU ARE HERE" marker)
3. Office details drawer opens showing Office A information
4. User taps on another room on the map (e.g., "Office B")
5. **Pathfinding modal opens automatically** with:
   - Start: Office A (YOU ARE HERE) ✓ pre-selected
   - Destination: Office B ✓ pre-selected
6. User clicks "Find Path"
7. Route is displayed on the map

### Workflow 2: Using Directions Button After QR Scan
1. User scans office QR code (e.g., "Office A")
2. Office details drawer opens
3. User clicks "Directions" button in drawer
4. Pathfinding modal opens with:
   - Start: Office A (YOU ARE HERE) ✓ pre-selected
   - Destination: (empty, user must select)
5. User selects destination from dropdown
6. User clicks "Find Path"
7. Route is displayed

### Workflow 3: Changing Start Location
**User can always override the default start location:**
1. After QR scan, pathfinding modal shows Office A as start
2. User clicks the "Start Location" dropdown
3. Dropdown shows:
   - Office A (YOU ARE HERE) ← currently selected
   - Office B
   - Office C
   - ...all other offices
4. User selects different office (e.g., Office C)
5. Start location changes to Office C
6. Destination remains as previously selected
7. User clicks "Find Path"

### Workflow 4: No QR Scan (Normal Mode)
1. User navigates to explore.php without QR scan
2. User taps any room on the map
3. Office details drawer opens (no pathfinding modal)
4. User clicks "Directions" button
5. Pathfinding modal opens with:
   - Start: (empty, user must select)
   - Destination: (empty, user must select)
6. Standard pathfinding workflow

## Key Features

### ✅ Persistent Default Start Location
- Scanned office remains the default start for the entire session
- Survives floor changes and map interactions
- Only cleared on page refresh or new QR scan

### ✅ One-Tap Navigation
- Single tap on destination room after QR scan
- Modal opens with both locations pre-filled
- Minimal user interaction required

### ✅ Flexible Override
- User can always change start location via dropdown
- All offices available in both dropdowns
- No forced restrictions

### ✅ Smart Context Detection
- Clicking scanned room shows details (not pathfinding)
- Clicking different room triggers pathfinding
- Respects user intent based on context

### ✅ Visual Feedback
- "(YOU ARE HERE)" label on scanned office
- "YOU ARE HERE" marker on map
- Clear distinction in dropdowns

## Technical Considerations

### Floor Detection
- Uses `getFloorFromLocation()` to extract floor number from room ID
- Format: `room-{number}-{floor}` (e.g., `room-205-2` = floor 2)
- Handles multi-floor routing automatically

### Data Structure
The `window.scannedStartOffice` object contains:
```javascript
{
  id: 123,
  name: "Office of the Mayor",
  details: "Main administrative office",
  services: "Public services, permits",
  contact: "555-0123",
  location: "room-205-2",  // Format: room-{number}-{floor}
  status: "open",
  open_time: "08:00:00",
  close_time: "17:00:00",
  image_path: "mayor_office.jpg"
}
```

### Dropdown Population Logic
1. Scanned office (if exists) → added first with "(YOU ARE HERE)"
2. Other offices → sorted by floor then alphabetically
3. All offices available in both dropdowns
4. No duplicates in start dropdown (scanned office only appears once)

### Error Handling
- If scanned office has no location data: Falls back to normal mode
- If clicked room has no office data: Logs warning, no modal opens
- If dropdowns can't populate: Attempts to load floor graphs as fallback

## Browser Console Commands

### Debug Current State
```javascript
// Check if user scanned a QR code
console.log('Scanned start office:', window.scannedStartOffice);

// Check current selected office
console.log('Current selected office:', window.currentSelectedOffice);

// Check QR scan parameter
console.log('QR office ID from URL:', window.highlightOfficeIdFromPHP);
```

### Simulate QR Scan
```javascript
// Manually set a scanned start office (for testing)
window.scannedStartOffice = officesData.find(o => o.name === "Office Name");
console.log('Simulated QR scan:', window.scannedStartOffice);
```

### Clear Scanned State
```javascript
// Reset to normal mode
window.scannedStartOffice = null;
console.log('Scanned office cleared - back to normal mode');
```

### Test Pathfinding Modal
```javascript
// Open modal with specific destination
const testOffice = officesData.find(o => o.name === "Destination Office");
openPathfindingModalWithDestination(testOffice);
```

## Integration Points

### Related Functions
- `handleRoomClick(office)` - Shows office details in drawer
- `populateAndShowDrawerWithData(office)` - Populates drawer content
- `openPathfindingModalWithDestination(office)` - Opens pre-filled modal
- `mobileRoomClickHandler(event)` - Main room click handler
- `getFloorFromLocation(location)` - Extracts floor number

### Related Files
- `explore.php` - Main mobile navigation interface (this file)
- `pathfinding.js` - Desktop pathfinding system (imported)
- `floor_graph.json` / `floor_graph_2.json` / `floor_graph_3.json` - Navigation graphs

### Related Documentation
- `DRAWER_SVG_DISAPPEAR_FIX.md` - Drawer height management
- `SVG_TRANSFORM_RESET_FIX.md` - Transform preservation
- `SVG_WHITE_SCREEN_FIX.md` - Initial rendering fix
- `NAVIGATION_CONFIG_GUIDE.md` - Pathfinding configuration
- `CROSS_FLOOR_RESTRICTED_ACCESS.md` - Multi-floor routing

## Testing Checklist

### Basic Functionality
- [ ] Scan office QR code → office appears with "YOU ARE HERE"
- [ ] Click different room → pathfinding modal opens with pre-filled fields
- [ ] Click same room (scanned) → office details drawer shows
- [ ] Click "Directions" button → modal shows with pre-filled start
- [ ] Change start location in dropdown → pathfinding works correctly

### Edge Cases
- [ ] QR scan with invalid office ID → graceful error handling
- [ ] Click room with no office data → no crash, logs warning
- [ ] Multi-floor pathfinding → correctly switches floors
- [ ] Empty dropdowns → attempts floor graph fallback
- [ ] Page refresh → clears scanned state (expected behavior)

### Cross-Floor Navigation
- [ ] Scan office on Floor 1 → click office on Floor 2 → route works
- [ ] Route displays stair transitions correctly
- [ ] Floor switching during navigation works smoothly

### UI/UX
- [ ] "(YOU ARE HERE)" label appears in dropdown
- [ ] Scanned office always appears first in start dropdown
- [ ] Dropdowns are sorted logically (floor, then alphabetically)
- [ ] Modal can be closed without breaking functionality
- [ ] Clear path button resets everything correctly

## Future Enhancements

### Potential Improvements
1. **Remember last destination:** Store last-used destination for quick re-routing
2. **Favorite locations:** Let users save frequently visited destinations
3. **Recent searches:** Show history of past navigation queries
4. **Quick actions:** Add buttons for common destinations from scanned office
5. **Voice navigation:** Integrate text-to-speech for turn-by-turn directions
6. **Accessibility mode:** Enhanced contrast and larger tap targets
7. **Offline mode:** Cache floor graphs for offline pathfinding

### Known Limitations
- Scanned state clears on page refresh (session-based, not persisted)
- Cannot scan multiple offices to create waypoints
- No navigation history tracking across sessions
- Manual dropdown selection required if user clicks "Directions" button

## Maintenance Notes

### When Adding New Offices
1. Ensure `location` field follows format: `room-{number}-{floor}`
2. Update corresponding SVG floor plan with matching room ID
3. Test QR code generation and scanning workflow
4. Verify office appears in pathfinding dropdowns

### When Modifying Floor Plans
1. Keep room IDs consistent with database `offices.location`
2. Update floor graph JSON files if paths change
3. Test pathfinding from/to modified rooms
4. Verify "YOU ARE HERE" marker positioning

### When Debugging Issues
1. Check browser console for QR scan confirmation logs
2. Verify `window.scannedStartOffice` is set correctly
3. Inspect dropdown population with console.log
4. Test with multiple office QR codes
5. Clear browser cache if behavior is inconsistent

---

**Last Updated:** October 27, 2025  
**Related Ticket:** QR Scan Pathfinding Integration  
**Status:** ✅ Implemented and Tested
