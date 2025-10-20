# GABAY Panorama Tour Implementation Guide

## Overview

This guide explains how to create interconnected panoramic tours in the GABAY system, similar to professional tools like Pano2VR. The system supports hotspot-based navigation between panoramas with smooth transitions and target viewing angles.

## Architecture

### Database Structure

The panorama linking system uses these key tables:

```sql
-- Core panorama data
panorama_image (
    id, path_id, point_index, floor_number,
    image_filename, title, description, ...
)

-- Hotspot navigation data
panorama_hotspots (
    id, path_id, point_index, floor_number, hotspot_id,
    position_x, position_y, position_z,
    title, description, hotspot_type,
    -- Navigation linking fields
    link_type,           -- 'panorama', 'office', 'external'
    link_path_id,        -- Target panorama path
    link_point_index,    -- Target panorama point
    link_floor_number,   -- Target floor
    navigation_angle,    -- Target viewing yaw angle
    is_navigation,       -- Boolean flag for navigation hotspots
    -- Media assets
    video_hotspot_path,
    animated_icon_path,
    ...
)
```

### File Structure

```
Pano/
├── pano_photosphere.html         # Enhanced panorama viewer
├── path1/
│   ├── point_1.jpg
│   ├── point_2.jpg
│   └── ...
├── path2/
│   └── ...
└── ...

panorama_api.php                  # API for panorama management
panorama_tour_manager.php         # Tour creation interface
```

## Implementation Steps

### 1. Upload Panoramas

Upload panoramic images for each tour point:

```php
// Using the panorama API
POST panorama_api.php?action=upload
{
    path_id: "main_hall",
    point_index: 1,
    floor_number: 1,
    point_x: 100,
    point_y: 150,
    title: "Main Entrance",
    description: "Capitol building main entrance"
}
```

### 2. Create Navigation Hotspots

#### Method A: Using the Tour Manager Interface

1. Open `panorama_tour_manager.php`
2. Select source and target panoramas
3. Set target viewing angle and zoom
4. Create the navigation link

#### Method B: Direct API Usage

```javascript
// Create navigation hotspot
const navigationHotspot = {
  id: "nav_to_lobby",
  title: "Go to Lobby",
  description: "Navigate to the main lobby area",
  linkType: "panorama",
  linkPathId: "main_hall",
  linkPointIndex: 2,
  linkFloorNumber: 1,
  navigationAngle: 45, // Target yaw angle
  position: { x: 5, y: 0, z: -8 },
  type: "navigation",
  isNavigation: true,
};

// Save hotspot
fetch("panorama_api.php?action=save_hotspots", {
  method: "POST",
  body: new FormData({
    path_id: "main_hall",
    point_index: 1,
    floor_number: 1,
    hotspots: JSON.stringify([navigationHotspot]),
  }),
});
```

### 3. Position Hotspots

Use the panorama editor to position navigation hotspots:

1. Open panorama in viewer
2. Click to add hotspot at desired 3D position
3. Set hotspot type to "Navigation"
4. Configure target panorama and view angle

### 4. Test Navigation

Access panoramas with navigation:

```
pano_photosphere.html?path_id=main_hall&point_index=1&floor_number=1
```

## Advanced Features

### Target View Control

Set specific viewing angles when users navigate:

```javascript
// Navigate with target view
navigateToPanorama(
  "reception_area", // target path
  3, // target point
  1, // target floor
  90 // target yaw angle (90° = East)
);
```

### Cross-Floor Navigation

Link panoramas across different floors:

```javascript
const crossFloorLink = {
  linkType: "panorama",
  linkPathId: "stairwell",
  linkPointIndex: 1,
  linkFloorNumber: 2, // Navigate to Floor 2
  title: "Go to Second Floor",
};
```

### Enhanced Marker Types

Different hotspot styles for different navigation types:

```css
/* Standard navigation hotspot */
.split-nav-marker.navigation-link {
  background: linear-gradient(135deg, #04aa6d, #038659);
  animation: navigationPulse 2s ease-in-out infinite;
}

/* Cross-floor navigation */
.split-nav-marker.floor-change {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: 3px solid #ff6b6b;
}
```

## URL Parameters

### Viewer Parameters

- `path_id`: Panorama path identifier
- `point_index`: Point within the path
- `floor_number`: Floor level
- `target_yaw`: Initial/target horizontal angle (-180 to 180)
- `target_pitch`: Initial/target vertical angle (-90 to 90)
- `target_zoom`: Initial/target zoom level (30-90)

### Example URLs

```
# Basic panorama view
pano_photosphere.html?path_id=lobby&point_index=1&floor_number=1

# With target view angle
pano_photosphere.html?path_id=lobby&point_index=1&floor_number=1&target_yaw=45&target_pitch=10&target_zoom=70
```

## API Endpoints

### Get Linkable Panoramas

```php
GET panorama_api.php?action=get_linkable_panoramas
&current_path_id=lobby&current_point_index=1&current_floor=1
```

### Validate Navigation Link

```php
GET panorama_api.php?action=validate_hotspot_link
&link_path_id=reception&link_point_index=2&link_floor_number=1
```

### Save Navigation Hotspots

```php
POST panorama_api.php?action=save_hotspots
{
    path_id: "source_path",
    point_index: 1,
    floor_number: 1,
    hotspots: [/* hotspot array */]
}
```

## Best Practices

### 1. Tour Planning

- Map out the navigation flow before creating hotspots
- Use consistent naming for paths and points
- Plan target viewing angles to create smooth transitions

### 2. Hotspot Positioning

- Place navigation hotspots at logical positions (doorways, corridors)
- Use descriptive titles ("Go to Reception", "Exit to Parking")
- Position hotspots at eye level (y ≈ 0 to 2)

### 3. Performance Optimization

- Use compressed panoramic images (recommended: 4096x2048)
- Limit number of hotspots per panorama (max 10-15)
- Test on mobile devices for touch interaction

### 4. User Experience

- Set appropriate target angles for natural navigation flow
- Use consistent visual styling for navigation hotspots
- Provide clear feedback during navigation transitions

## Integration with Mobile Interface

### QR Code Generation

Panoramas automatically generate QR codes linking to mobile viewer:

```php
// Auto-generated QR URL format
$mobileUrl = "mobileScreen/explore.php?scanned_panorama=path_id:{$pathId}_point:{$pointIndex}_floor:{$floorNumber}";
```

### Split-Screen Mode

The panorama viewer is optimized for split-screen mobile usage:

```javascript
// Enhanced for mobile touch controls
touchmoveTwoFingers: false,  // Enable single finger pan
mousewheelCtrlKey: false,    // Allow zoom without Ctrl key
navbar: false,               // Hide controls for clean split-screen
```

## Troubleshooting

### Common Issues

1. **Hotspots Not Appearing**

   - Check database panorama_hotspots table
   - Verify path_id, point_index, floor_number match exactly
   - Ensure is_navigation flag is set for navigation hotspots

2. **Navigation Not Working**

   - Validate target panorama exists using API
   - Check linkType is set to 'panorama'
   - Verify linkPathId and linkPointIndex are correct

3. **Target View Not Applied**
   - Ensure target_yaw parameter is in URL
   - Check viewer ready event timing
   - Verify angle values are within valid ranges

### Debug Tools

```javascript
// Enable panorama debugging
console.log("Current panorama:", {
  pathId: this.pathId,
  pointIndex: this.pointIndex,
  floorNumber: this.floorNumber,
});

// Check hotspot data
console.log("Loaded hotspots:", this.hotspots);

// Monitor navigation
window.addEventListener("beforeunload", () => {
  console.log("Navigating from panorama:", window.location.search);
});
```

## Comparison with Pano2VR

### GABAY Advantages

- Integrated with office directory system
- Automatic QR code generation
- Mobile-optimized split-screen interface
- Database-driven hotspot management
- Real-time hotspot editing

### Pano2VR-Style Features Supported

- ✅ Hotspot-based navigation between panoramas
- ✅ Target view angle setting (pan/tilt/fov)
- ✅ Cross-panorama linking
- ✅ Custom hotspot styling
- ✅ Smooth navigation transitions
- ✅ Template-based output (via API)

### Implementation Differences

- **GABAY**: Web-based, database-driven, integrated system
- **Pano2VR**: Desktop application, XML-based, standalone tours

This implementation provides a modern, web-based alternative to traditional panoramic tour tools while maintaining compatibility with mobile devices and integration with the broader office directory system.
