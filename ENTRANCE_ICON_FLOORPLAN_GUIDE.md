# Entrance Icon Feature on Floor Plan - Implementation Guide

## Overview
This document explains the entrance icon feature on the `floorPlan.php` admin interface. Entrance icons appear on the SVG floor map, allowing administrators to quickly download entrance QR codes without navigating to the Entrance Management page.

## Features
- **Visual Markers**: Green circular icons with entrance symbol on floor plan
- **Interactive**: Click any entrance icon to download its QR code
- **Multi-Floor Support**: Automatically loads entrances for the currently selected floor
- **Hover Effects**: Icons expand and lighten on hover with tooltip
- **Consistent Styling**: Matches the design pattern of doorpoint icons

## Technical Implementation

### 1. Icon Rendering Function
**Location**: `floorPlan.php` (lines ~1790-1890)
**Function**: `drawEntranceIcons(floor)`

**Process Flow**:
1. Determines correct floor graph file (`floor_graph.json`, `floor_graph_2.json`, or `floor_graph_3.json`)
2. Fetches floor graph data via AJAX
3. Extracts `entrances` array from floor graph
4. Creates SVG group `#entrance-icon-group` within svg-pan-zoom viewport
5. For each entrance:
   - Creates circular background (green, 14px radius)
   - Embeds entrance icon SVG path (white)
   - Positions at entrance x,y coordinates
   - Adds click handler for QR download
   - Adds hover effects and tooltip

### 2. Icon Appearance
**Background Color**: `#10B981` (green) - distinguishes from doorpoints (orange `#F97316`)
**Icon**: White entrance symbol from `entrance-14-svgrepo-com.svg`
**Size**: 14px radius (slightly larger than doorpoint 12px)
**Hover State**: Expands to 16px radius with lighter green `#34D399`

### 3. SVG Path Data
```xml
<path d="m 4,0 0,4 2,0 0,-2 6,0 0,10 -6,0 0,-2 -2,0 0,4 10,0 0,-14 z m 3,3.5 0,2.25 -6,0 0,2.5 6,0 0,2.25 4,-3.5 z" />
```
This path creates an entrance/door arrow symbol scaled to fit the 14x14 viewBox.

### 4. Integration with Floor Loading
**Location**: `floorPlan.php` (lines ~1099-1350 in `loadFloorMap()` function)

**Three Call Points**:
1. **After successful floor load** (line ~1302):
   ```javascript
   setTimeout(() => {
       console.log('Drawing entrance icons...');
       drawEntranceIcons(parseInt(floor, 10));
   }, 900); // After door status application
   ```

2. **After pan-zoom failure** (line ~1320):
   ```javascript
   setTimeout(() => {
       console.log('Drawing entrance icons (no pan-zoom)...');
       drawEntranceIcons(parseInt(floor, 10));
   }, 900);
   ```

3. **When SVG not available** (line ~1338):
   ```javascript
   setTimeout(() => {
       console.log('Drawing entrance icons (no pan-zoom or SVG)...');
       drawEntranceIcons(parseInt(floor, 10));
   }, 900);
   ```

**Timing**: 900ms delay ensures:
- SVG is fully loaded and rendered
- Pan-zoom initialized (if available)
- Doorpoints drawn by pathfinding system (800ms)
- Door statuses applied (800ms)

### 5. QR Code Download Function
**Location**: `floorPlan.php` (lines ~1876-1900)
**Function**: `downloadEntranceQR(entranceId, entranceLabel)`

**Process**:
1. Constructs QR image path: `entrance_qrcodes/{entranceId}.png`
2. Performs HEAD request to check file existence
3. If exists:
   - Creates temporary download link
   - Triggers browser download
   - Shows success alert
4. If not exists:
   - Shows alert prompting user to generate QR codes first

**Error Handling**:
- File not found: "QR code not found. Please generate entrance QR codes from the Entrance Management page first."
- Network error: "Error downloading QR code. Please try again."

## Usage Instructions

### For Administrators

1. **Access Floor Plan**:
   - Navigate to `floorPlan.php` in admin interface
   - Select desired floor (1, 2, or 3) from dropdown

2. **Locate Entrance Icons**:
   - Look for **green circular markers** with entrance symbol
   - Icons appear at entrance locations defined in floor graph

3. **Download QR Code**:
   - Hover over entrance icon (expands with tooltip)
   - Click icon to download QR code PNG
   - If QR not generated, go to Entrance Management page first

### Initial Setup (First Time)

1. **Generate QR Codes**:
   - Go to `entranceManagement.php`
   - Click "Generate All Entrance QR Codes" button
   - Wait for success message

2. **Verify QR Codes Exist**:
   - Check `entrance_qrcodes/` directory
   - Should contain PNG files: `entrance_main_1.png`, `entrance_west_1.png`, etc.

3. **Test Download**:
   - Return to `floorPlan.php`
   - Click any entrance icon
   - QR code should download successfully

## Troubleshooting

### Icons Not Appearing
**Symptoms**: No green entrance icons on floor map
**Causes & Solutions**:
1. **No entrances defined**: Check `floor_graph.json` for `entrances` array
2. **Floor graph fetch failed**: Check browser console for 404 errors
3. **SVG not loaded**: Verify `loadFloorMap()` completed successfully
4. **Timing issue**: Try refreshing page or switching floors

### Download Fails
**Symptoms**: Alert saying "QR code not found"
**Solution**: Generate QR codes first:
```bash
# Option 1: Via admin UI
Visit entranceManagement.php → Click "Generate All Entrance QR Codes"

# Option 2: Via terminal
cd /path/to/gabay
php entrance_qr_api.php  # Run generate action
```

### Icons Not Clickable
**Symptoms**: Clicking icon has no effect
**Causes & Solutions**:
1. **Pointer events disabled**: Check if `marker` element has `cursor: pointer` style
2. **Click handler not attached**: Verify `marker.addEventListener('click', ...)` executed
3. **Icon overlay issue**: Ensure `entranceIcon.style.pointerEvents = 'none'` (clicks should pass to marker)

### Wrong Floor Icons
**Symptoms**: Icons appear for different floor than selected
**Causes & Solutions**:
1. **Floor graph mismatch**: Verify `drawEntranceIcons()` receives correct floor number
2. **Cache issue**: Clear browser cache and reload
3. **Multiple calls**: Check console for duplicate "Drawing entrance icons" logs

## File Structure

### Related Files
```
gabay/
├── floorPlan.php                        # Main implementation
├── entrance_qr_api.php                  # QR generation backend
├── entranceManagement.php               # QR management UI
├── floor_graph.json                     # Floor 1 entrance definitions
├── floor_graph_2.json                   # Floor 2 entrance definitions
├── floor_graph_3.json                   # Floor 3 entrance definitions
├── entrance_qrcodes/                    # Generated QR codes directory
│   ├── entrance_main_1.png
│   ├── entrance_west_1.png
│   └── ...
├── assets/3d/entrance-14-svgrepo-com.svg # Icon source file
└── ENTRANCE_QR_SYSTEM_GUIDE.md         # Overall entrance system docs
```

### Floor Graph Structure
```json
{
  "rooms": { ... },
  "walkablePaths": [ ... ],
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

## Integration with Existing Systems

### 1. Doorpoint System
- **Similarity**: Uses same SVG group pattern as `#entry-point-group`
- **Difference**: Separate group `#entrance-icon-group` prevents conflicts
- **Timing**: Draws after doorpoints (900ms vs 800ms delay)

### 2. Pan-Zoom Library
- **Non-Scaling Stroke**: Uses `vector-effect="non-scaling-stroke"` for consistent appearance at all zoom levels
- **Group Placement**: Icons added to `.svg-pan-zoom_viewport` group
- **Coordinate System**: Uses absolute SVG coordinates (x, y) from floor graph

### 3. Entrance QR System
- **QR Generation**: Uses `entrance_qr_api.php` to create QR codes
- **QR Storage**: PNG files in `entrance_qrcodes/` directory
- **QR Naming**: `{entranceId}.png` (e.g., `entrance_main_1.png`)
- **Scan Handling**: QR codes link to `explore.php?entrance_qr=1&entrance_id=X&floor=Y`

## Browser Console Messages

### Successful Execution
```
Drawing entrance icons for floor 1...
Found 3 entrances on floor 1: [Array]
Drawing entrance icon: Main Entrance at (920, 50)
Successfully added entrance icon for Main Entrance
Drawing entrance icon: West Entrance at (150, 300)
Successfully added entrance icon for West Entrance
Drawing entrance icon: East Entrance at (1600, 300)
Successfully added entrance icon for East Entrance
Finished drawing entrance icons
```

### Error Messages
```
No entrances defined for floor 2
// OR
SVG element not found
// OR
Error loading floor graph for entrance icons: [Error object]
```

## Performance Considerations

1. **Fetch Optimization**: Floor graphs cached by browser (same files used by pathfinding)
2. **DOM Manipulation**: Groups cleared before redraw prevents memory leaks
3. **Event Listeners**: Attached to marker group (not individual paths) for efficiency
4. **Timing**: 900ms delay balances responsiveness with render stability

## Future Enhancements

### Potential Improvements
1. **Bulk Download**: Select multiple entrances to download all QR codes as ZIP
2. **QR Preview**: Show QR code in modal before download
3. **Icon Customization**: Allow admin to change entrance icon color/style
4. **Label Display**: Optional text labels near icons (like room numbers)
5. **Status Indicator**: Show active/inactive status via icon color

### Code Extension Points
- `drawEntranceIcons()`: Add parameters for custom styling
- `downloadEntranceQR()`: Extend for bulk operations
- Floor graph: Add `style` object to entrance definitions

## Related Documentation

- **Entrance System Overview**: `ENTRANCE_QR_SYSTEM_GUIDE.md`
- **Testing Procedures**: `ENTRANCE_TESTING_STEPS.md`
- **Implementation Summary**: `ENTRANCE_IMPLEMENTATION_SUMMARY.md`
- **Navigation Config**: `NAVIGATION_CONFIG_GUIDE.md`

## Support

For issues or questions:
1. Check browser console for error messages
2. Verify floor graph JSON structure
3. Confirm QR codes generated in `entrance_qrcodes/` directory
4. Review this guide's troubleshooting section
5. Test with different floors to isolate floor-specific issues
