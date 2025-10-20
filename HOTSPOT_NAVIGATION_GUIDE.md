# Panorama Hotspot Navigation System - User Guide

## Overview

Your panorama system now supports hotspot-to-hotspot navigation, allowing visitors to click on hotspots to jump between different panorama views, similar to Google Street View.

## Features Added

### 1. **Navigation Hotspots**

- Orange-colored hotspots that link to other panorama points
- Clickable navigation between different panorama views
- Automatic validation of link targets

### 2. **Enhanced Hotspot Types**

- **Information Only**: Standard informational hotspots
- **Navigate to Another View**: Links to other panoramas
- **Office Link**: Links to office details
- **External URL**: Links to external websites

### 3. **Smart Hotspot Editor**

- Icon-based hotspot creation with drag-and-drop
- Real-time preview of navigation targets
- Automatic linking validation

## How to Use

### Setting Up Navigation Between Panoramas

1. **Upload Panoramas First**

   - Go to Floor Plan admin interface
   - Click on path points to upload panorama images
   - Ensure you have panoramas at multiple locations

2. **Access Hotspot Editor**

   - In the Floor Plan admin, click on a panorama point
   - Click "🔗 Edit Hotspots" button (appears when panorama exists)
   - The hotspot editor opens in a new window

3. **Create Navigation Hotspots**
   - Click "Add Hotspot" to open icon selection
   - Choose navigation icons (arrows, compass, etc.)
   - Drag and drop the icon onto the panorama
   - Set hotspot type to "Navigate to Another View"
   - Select target panorama from dropdown
   - Save hotspots

### Hotspot Editor Interface

#### **Toolbar Controls**

- **Add Hotspot**: Opens icon library for hotspot creation
- **Test Hotspot**: Creates a test hotspot for positioning
- **Save All**: Saves all hotspots to database

#### **Hotspot Properties**

- **Title**: Display name for the hotspot
- **Description**: Additional information
- **Type**: Choose navigation vs information
- **Navigate To**: Dropdown of available panorama points

#### **Icon Library**

- Categories: Information, Navigation, Offices, Services, Facilities
- Drag and drop icons directly onto panorama
- Different colors for different hotspot types

### Visitor Experience

When visitors view panoramas:

1. **Information Hotspots** (Green): Show details when clicked
2. **Navigation Hotspots** (Orange): Navigate to linked panorama when clicked
3. **Confirmation Dialog**: "Navigate to [Hotspot Title]?" before jumping

## Technical Implementation

### Database Schema

New columns added to `panorama_hotspots` table:

- `link_type`: Type of link (panorama, external, none)
- `link_path_id`, `link_point_index`, `link_floor_number`: Target panorama coordinates
- `navigation_angle`: Optional viewing angle for navigation
- `is_navigation`: Boolean flag for navigation hotspots

### API Endpoints

- `get_linkable_panoramas`: Lists available panorama targets
- `validate_hotspot_link`: Validates navigation targets
- Enhanced `save_hotspots` with navigation data

## Setup Instructions

### 1. Run Database Update

```bash
# Access your server and run:
php update_schema.php
```

This adds the necessary database columns.

### 2. Verify File Permissions

Ensure these files are accessible:

- `panorama_viewer.html`
- `panorama_api.php`
- `Pano/` directory with uploaded images

### 3. Test Navigation

1. Upload panoramas to at least 2 different points
2. Create navigation hotspots linking them
3. Test clicking between views

## Best Practices

### Planning Navigation Flow

1. **Start with Key Points**: Place panoramas at important locations
2. **Logical Connections**: Link related areas (hallways, entrances, offices)
3. **Clear Naming**: Use descriptive titles like "Go to Main Entrance"

### Icon Selection

- **Arrows**: For directional navigation (left, right, up, down)
- **Doors**: For entering rooms or areas
- **Compass**: For general navigation points
- **Locations**: For specific destinations

### Performance Tips

- Keep hotspot count reasonable (5-10 per panorama)
- Use consistent naming for navigation targets
- Test navigation flow from visitor perspective

## Troubleshooting

### Common Issues

1. **"No panorama found" Error**

   - Ensure target panorama exists and is active
   - Check path_id, point_index, and floor_number are correct

2. **Hotspots Not Saving**

   - Verify database schema update completed
   - Check PHP error logs for permission issues

3. **Editor Won't Open**

   - Enable pop-ups for your admin domain
   - Check that panorama_viewer.html exists

4. **Navigation Not Working**
   - Verify hotspot type is set to "navigation"
   - Check that link_path_id and link_point_index are set
   - Ensure target panorama image exists

### Browser Compatibility

- Modern browsers with WebGL support required
- Chrome, Firefox, Safari, Edge recommended
- Mobile browsers supported for visitor viewing

## Example Navigation Setup

### Scenario: Office Building Tour

1. **Entrance Hall** → Links to:

   - "Go to Elevators" → Floor navigation points
   - "Visit Reception" → Reception desk panorama

2. **2nd Floor Hallway** → Links to:

   - "Go to Office 201" → Individual office
   - "Return to Elevator" → Elevator area

3. **Office Room** → Links to:
   - "Exit to Hallway" → Return to hallway
   - "View Office Details" → Information modal

This creates a seamless navigation experience similar to Google Street View's indoor mapping.
