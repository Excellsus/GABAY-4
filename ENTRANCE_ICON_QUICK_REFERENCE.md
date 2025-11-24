# Entrance Icons on Floor Plan - Quick Reference

## What Was Added

✅ **Green entrance icons** now appear on the floor plan SVG map in `floorPlan.php`
✅ **Click any entrance icon** to download its QR code PNG file
✅ **Hover effects** - icons expand and show tooltip with entrance name
✅ **Multi-floor support** - automatically loads correct entrances when switching floors

## Visual Appearance

- **Icon Color**: Green circular background (`#10B981`)
- **Icon Symbol**: White entrance/door arrow icon
- **Size**: 14px radius (slightly larger than orange doorpoint icons)
- **Hover State**: Expands to 16px with lighter green color

## How to Use

### Step 1: Generate QR Codes (First Time Only)
1. Go to `entranceManagement.php`
2. Click **"Generate All Entrance QR Codes"** button
3. Wait for success message confirming 7 QR codes created

### Step 2: Download QR Codes from Floor Plan
1. Open `floorPlan.php`
2. Select a floor (1, 2, or 3) from dropdown
3. Look for **green circular icons** on the map
4. **Hover** over an entrance icon to see its name
5. **Click** the icon to download the QR code

## File Locations

- **Floor Plan Admin**: `floorPlan.php` (main file with entrance icons)
- **QR Management**: `entranceManagement.php` (generate QR codes)
- **QR Code Files**: `entrance_qrcodes/` directory
  - `entrance_main_1.png`
  - `entrance_west_1.png`
  - `entrance_east_1.png`
  - etc.

## Implementation Details

### Functions Added to floorPlan.php

1. **`drawEntranceIcons(floor)`** (line ~1790)
   - Fetches floor graph JSON for specified floor
   - Creates SVG icon elements at entrance coordinates
   - Adds click handlers for QR download
   - Adds hover effects and tooltips

2. **`downloadEntranceQR(entranceId, entranceLabel)`** (line ~1876)
   - Checks if QR code file exists
   - Triggers browser download
   - Shows error if QR not generated yet

### Integration Points

- **Called after floor loads**: 900ms delay ensures SVG, doorpoints, and door statuses are ready
- **Three call locations**: Normal load, pan-zoom failure, no SVG fallback
- **Separate SVG group**: `#entrance-icon-group` prevents conflicts with doorpoints

## Sample Entrances

### Floor 1 (3 entrances)
- Main Entrance (920, 50)
- West Entrance (150, 300)
- East Entrance (1600, 300)

### Floor 2 (2 entrances)
- Main Entrance (920, 50)
- West Entrance (150, 300)

### Floor 3 (2 entrances)
- Main Entrance (920, 50)
- West Entrance (150, 300)

## Troubleshooting

### Icons Not Showing
- **Check console**: Open browser DevTools → Console tab
- **Verify floor graph**: Ensure `entrances` array exists in floor graph JSON
- **Refresh page**: Try switching floors or reloading page

### Download Shows "Not Found" Error
- **Generate QR codes first**: Go to `entranceManagement.php` and click "Generate All"
- **Check directory**: Verify `entrance_qrcodes/` folder contains PNG files
- **File permissions**: Ensure web server can read `entrance_qrcodes/` directory

### Icons Not Clickable
- **Check cursor**: Icon should show pointer cursor on hover
- **Console errors**: Look for JavaScript errors in browser console
- **Clear cache**: Try hard refresh (Ctrl+Shift+R / Cmd+Shift+R)

## Code Structure

```javascript
// Function call (executed after floor loads)
setTimeout(() => {
    drawEntranceIcons(parseInt(floor, 10));
}, 900);

// Function implementation
function drawEntranceIcons(floor) {
    // 1. Load floor graph JSON
    // 2. Get entrances array
    // 3. Create SVG icon group
    // 4. For each entrance:
    //    - Create circle background
    //    - Create icon path
    //    - Add click handler
    //    - Add hover effects
}

function downloadEntranceQR(entranceId, entranceLabel) {
    // 1. Check if QR file exists
    // 2. Trigger download or show error
}
```

## Next Steps

1. **Test the feature**:
   ```
   http://localhost/gabay/floorPlan.php
   ```

2. **Generate QR codes** (if not done yet):
   ```
   http://localhost/gabay/entranceManagement.php
   ```

3. **Verify downloads work**:
   - Click each entrance icon
   - Check Downloads folder for PNG files

## Documentation

- **Detailed Guide**: `ENTRANCE_ICON_FLOORPLAN_GUIDE.md`
- **Entrance System**: `ENTRANCE_QR_SYSTEM_GUIDE.md`
- **Testing Steps**: `ENTRANCE_TESTING_STEPS.md`

## Summary

The entrance icon feature is now fully integrated into `floorPlan.php`. Admins can click green entrance icons on the SVG map to quickly download QR codes without navigating to the Entrance Management page. The implementation matches the existing doorpoint icon pattern and supports all three floors.
