# GABAY Office Directory & Navigation System - AI Coding Guide

## Architecture Overview

This is a PHP-based office directory and indoor navigation system for government buildings, supporting both desktop administration and mobile visitor interfaces. The system centers around QR code-based navigation and interactive floor plans.

### Core Components

- **Admin Interface**: Desktop-focused office management (`home.php`, `officeManagement.php`, `floorPlan.php`)
- **Mobile Interface**: Visitor-facing mobile web app in `mobileScreen/` folder
- **Database**: MySQL with PDO connections via `connect_db.php`
- **Floor Plans**: SVG-based interactive maps with drag-drop positioning
- **QR System**: Automated QR code generation linking to mobile office details

## Key Database Tables

```sql
offices          // Core office data (name, details, contact, location, services)
office_hours     // Operating hours per office per weekday
office_image     // Multiple images per office with upload timestamps
qrcode_info      // QR code metadata linking to offices
qr_scan_logs     // Visitor tracking with timestamps
activities       // Admin activity logging
feedback         // Visitor feedback with ratings
```

## Critical Patterns & Conventions

### Database Connection
- Always use `include 'connect_db.php'` or `include __DIR__ . '/../connect_db.php'` for mobile screens
- Database object is `$connect` (PDO instance)
- Default database name is "admin" on localhost MySQL

### Mobile vs Desktop Structure
- Desktop files: Root directory (`floorPlan.php`, `home.php`, etc.)
- Mobile files: `mobileScreen/` subdirectory with relative path adjustments
- Mobile screens use `explore.php` as main entry point with `office_id` parameter

### SVG Floor Plan Integration
- Floor plans stored in `SVG/` directory with specific naming:
  - `Capitol_1st_floor_layout_20_modified.svg` (Floor 1)
  - `Capitol_2nd_floor_layout_6_modified.svg` (Floor 2)
  - `Capitol_3rd_floor_layout_6.svg` (Floor 3)
- Room elements use IDs like `room-{number}-1` pattern
- Room labels use `roomlabel-{number}` pattern in SVG text elements

### File Upload Patterns
- Office images: `office_images/` with format `office_{timestamp}.{ext}`
- QR codes: `qrcodes/` with format `{sanitized_office_name}_{office_id}.png`
- Use `uniqid()` for unique file naming

### JavaScript/CSS Organization
- Floor plan interactions: `floorjs/` directory
- Mobile navigation: Separate CSS files per screen (`explore.css`, `about.css`, etc.)
- Dark mode toggle: `darkMode.js` for theme switching

## Development Workflows

### Adding New Offices
1. Insert into `offices` table with `location` field for SVG room mapping
2. Generate QR codes via `generate_qrcodes.php` 
3. Update floor plan positions using drag-drop interface in `floorPlan.php`
4. Add office hours in `office_hours` table

### QR Code System
- Base URL in `generate_qrcodes.php`: Update `$baseUrl` for deployment
- QR codes link to `mobileScreen/explore.php?office_id={id}`
- Scan logging automatic when office_id present in URL

### Mobile Screen Development
- All mobile screens extend from parent directory includes
- Use `office_id` parameter for office-specific content
- Implement responsive design for mobile-first experience

## Error Handling Conventions
- Enable error reporting in development: `ini_set('display_errors', 1)`
- Use `error_log()` for production logging instead of displaying errors
- Wrap database operations in try-catch blocks
- Check `$connect` validity before queries

## Asset Management
- Office images: Upload to `office_images/` with automatic resize handling
- SVG modifications: Update room coordinates directly in SVG files
- QR regeneration: Run `generate_qrcodes.php` after adding offices

## Security Notes
- Password hashing: Use `password_hash()` and `password_verify()`
- Input sanitization: PDO prepared statements for all user inputs
- File uploads: Validate extensions and use `move_uploaded_file()`

## Testing & Debugging
- Check activities table for admin action logging
- Monitor qr_scan_logs for visitor tracking
- Use browser dev tools for SVG coordinate debugging
- Test mobile interface on actual mobile devices for touch interactions

## Common Pitfalls
- SVG room IDs must match database `location` field exactly
- Mobile paths need `../` prefix for parent directory includes
- QR base URL must be updated for production deployment
- Floor plan coordinate system is SVG-based, not pixel-based