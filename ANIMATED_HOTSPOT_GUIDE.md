# 🎯 Animated Hotspot Icons System - User Guide

## Overview

The Animated Hotspot Icons system allows you to upload and manage animated GIF icons that can be used as hotspots in your panorama viewer. This enhances the user experience by providing visually engaging, animated elements.

## Features

- ✅ Upload animated GIF files (max 2MB)
- ✅ Organize icons by categories (Info, Navigation, Office, Services, Facilities, General)
- ✅ Integrated directly into the panorama hotspot editor
- ✅ Real-time preview of animations in panorama view
- ✅ Edit icon metadata (name, description, category)
- ✅ Delete unused icons
- ✅ Automatic file management and cleanup

## How to Use

### 1. Access the Animated Icons Manager

1. Open the **Panorama Hotspot Editor** (`panorama_viewer.html`)
2. Click the **"+ Add Hotspot"** button to open the icon selection overlay
3. Click the **"Manage Animated Icons"** button at the bottom of the overlay

### 2. Upload New Animated Icons

1. In the Animated Icons Manager, go to the **"Upload New Icon"** tab
2. Fill in the required information:

   - **Icon Name**: A unique name for your icon (e.g., "Navigation Arrow")
   - **Description**: Optional description of what the icon represents
   - **Category**: Choose from Info, Navigation, Office, Services, Facilities, or General
   - **GIF File**: Select your animated GIF file (max 2MB)

3. Click **"Upload Animated Icon"** to save

### 3. Use Animated Icons in Hotspots

1. After uploading, go to the **"Manage Icons"** tab
2. Find your uploaded icon and click the **"Use"** button
3. The panorama viewer will enter placement mode
4. Click anywhere on the panorama image to place the animated hotspot
5. Fill in the hotspot details (title, description, type, etc.)
6. Click **"Save All"** to persist your changes

### 4. Manage Existing Icons

- **Edit**: Click the "Edit" button to modify icon name, description, or category
- **Delete**: Click the "Delete" button to remove an icon (will also remove it from any existing hotspots)
- **Filter**: Use category buttons to filter icons by type

## File Requirements

### Supported Formats

- **GIF files only** (animated GIFs recommended)
- Maximum file size: **2MB**
- Recommended dimensions: **64x64 to 128x128 pixels**
- Recommended frame rate: **8-12 FPS** for smooth animation without being distracting

### Best Practices for Icon Creation

1. **Keep it Simple**: Simple, clear animations work best for hotspots
2. **Loop Smoothly**: Ensure your GIF loops seamlessly
3. **Optimize File Size**: Use tools like GIMP, Photoshop, or online GIF compressors
4. **High Contrast**: Use colors that stand out against typical panorama backgrounds
5. **Consistent Style**: Maintain a consistent visual style across your icon library

## Technical Details

### Database Structure

The system uses the existing `panorama_hotspots` table with these key fields:

- `animated_icon_id`: Links to the `animated_hotspot_icons` table
- `hotspot_file`: Stores the path to the GIF file

### File Storage

- Icons are stored in: `/animated_hotspot_icons/`
- Files are automatically renamed with unique identifiers
- Original filenames are preserved in the database

### Integration Points

The animated icons integrate seamlessly with:

- Existing hotspot creation workflow
- Hotspot positioning and scaling controls
- Mobile responsive panorama viewer
- Hotspot save/load functionality

## Testing the System

Use the **Test Page** (`test_animated_icons.html`) to:

- Upload sample GIFs
- View all uploaded icons
- Test database connectivity
- Delete test icons

## Troubleshooting

### Common Issues

1. **"File too large" error**

   - Solution: Compress your GIF or reduce dimensions

2. **"Only GIF files allowed" error**

   - Solution: Ensure file has .gif extension and is actually a GIF format

3. **Animation not showing in panorama**

   - Check: File uploaded successfully and icon was selected before placing hotspot
   - Check: Browser supports GIF animation (most modern browsers do)

4. **Icons not loading**
   - Check: File permissions on `animated_hotspot_icons/` directory
   - Check: Database connection and animated_hotspot_icons table exists

### Database Issues

If you encounter database errors, ensure:

- The `animated_hotspot_icons` table exists and has proper structure
- The `panorama_hotspots` table has the `animated_icon_id` field
- PHP has proper database permissions

## API Endpoints

The system provides these API endpoints in `animated_hotspot_manager.php`:

- `POST ?action=upload` - Upload new animated icon
- `GET ?action=list` - List all icons (with optional category filter)
- `POST ?action=update` - Update icon metadata
- `POST ?action=delete` - Delete icon and associated files

## Future Enhancements

Potential improvements for future versions:

- Support for other animated formats (WebP, APNG)
- Bulk upload functionality
- Icon preview in different panorama contexts
- Animation speed/loop controls
- Icon size recommendations based on panorama dimensions
- Integration with icon libraries or marketplaces

---

**Need Help?**
Check the browser console for detailed error messages, or use the test page to verify system functionality.
