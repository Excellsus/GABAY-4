# Mobile Hotspot Navigation Test Guide

## Test Steps for Mobile Panorama Hotspot Navigation

### Prerequisites

1. Ensure the database schema has been updated by running `update_schema.php`
2. Have at least 2 panorama points uploaded with panorama images
3. Create navigation hotspots using the desktop hotspot editor

### Testing Process

#### 1. **Setup Navigation Hotspots (Desktop)**

1. Open Floor Plan admin interface
2. Click on a panorama point
3. Click "🔗 Edit Hotspots"
4. Add a hotspot:
   - Click "Add Hotspot"
   - Choose a navigation icon (compass, arrow, etc.)
   - Set type to "Navigate to Another View"
   - Select target panorama from dropdown
   - Save hotspots

#### 2. **Test Mobile Navigation**

1. Open mobile explore interface: `mobileScreen/explore.php`
2. Click on a panorama marker (camera icon) on the floor plan
3. Panorama should open in split-screen mode
4. Look for hotspots in the panorama:
   - **Orange hotspots** = Navigation hotspots
   - **Green hotspots** = Information hotspots
5. Click on an orange navigation hotspot
6. Should see confirmation dialog: "Navigate to [Target Name]?"
7. Click "Navigate →"
8. Should transition to the linked panorama

#### 3. **Visual Verification**

- Navigation hotspots should be **orange** with navigation icons
- Information hotspots should be **green**
- Navigation hotspots should have a pulsing "→" indicator
- Confirmation dialog should show hotspot title and description

#### 4. **URL Verification**

- After navigation, URL should update with new path_id, point_index, floor_number
- Browser back button should work to return to previous panorama
- Direct URL access should work: `/Pano/pano.html?path_id=X&point_index=Y&floor_number=Z`

### Expected Behavior

#### Information Hotspots (Green):

- Click shows information panel with title, description, type
- Panel slides up from bottom
- Auto-closes after 8 seconds
- Can be manually closed with X button

#### Navigation Hotspots (Orange):

- Click shows navigation confirmation dialog
- Dialog is centered on screen with navigation icon
- "Cancel" dismisses dialog
- "Navigate →" transitions to linked panorama
- Shows loading indicator during transition

### Troubleshooting

#### No Hotspots Visible:

1. Check browser console for API errors
2. Verify panorama_api.php is accessible
3. Ensure hotspots were saved with navigation data
4. Check database for hotspot records

#### Navigation Not Working:

1. Verify target panorama exists and has image
2. Check linkPathId, linkPointIndex, linkFloorNumber are set
3. Ensure target panorama API call succeeds
4. Check browser console for JavaScript errors

#### Visual Issues:

1. Clear browser cache
2. Check A-Frame console for WebGL errors
3. Verify touch controls are working
4. Test on different mobile devices/browsers

### API Testing

#### Test Hotspot Loading:

```
GET /panorama_api.php?action=get_hotspots&path_id=path1&point_index=5&floor_number=1
```

#### Expected Response:

```json
{
  "success": true,
  "hotspots": [
    {
      "id": "hotspot_123",
      "position": { "x": 2, "y": 0, "z": -3 },
      "title": "Go to Office",
      "type": "navigation",
      "linkType": "panorama",
      "linkPathId": "path2",
      "linkPointIndex": 3,
      "linkFloorNumber": 1,
      "icon": "fas fa-arrow-right"
    }
  ]
}
```

### Success Criteria

- ✅ Navigation hotspots are visually distinct (orange)
- ✅ Navigation confirmation dialog appears
- ✅ Successful transition between panoramas
- ✅ URL updates correctly
- ✅ Browser navigation works
- ✅ Touch controls remain responsive
- ✅ Loading states provide feedback
- ✅ Error handling for invalid targets
