# GABAY Geofencing Toggle Feature

## Overview
The admin can now enable or disable the geofencing system from the System Settings page. When disabled, mobile visitors can access the app without location verification.

## Setup Instructions

### 1. Database Migration (REQUIRED - Do This First!)

**Option A: Automatic Migration (Recommended)**
- Visit: `http://localhost/FinalDev/migrate_geofence_enabled.php`
- The script will automatically add the `enabled` column to your database

**Option B: Manual Migration**
- Open phpMyAdmin
- Select your database
- Run this SQL:
```sql
ALTER TABLE `geofences` 
ADD COLUMN `enabled` TINYINT(1) NOT NULL DEFAULT 1 
COMMENT 'Whether geofencing is enabled (1) or disabled (0)';

UPDATE `geofences` SET `enabled` = 1 WHERE `name` = 'default';
```

### 2. How to Use

1. Go to **System Settings** page in admin dashboard
2. Find the **"Geofencing Configuration"** section at the bottom
3. At the top of this section, you'll see a **toggle switch** labeled "Geofencing System Status"
4. Toggle ON (green) = Geofencing is ENABLED - visitors must be in the allowed area
5. Toggle OFF (gray) = Geofencing is DISABLED - visitors can access from anywhere

### 3. What Happens When Toggled

**When ENABLED (Default):**
- Mobile visitors see location verification overlay
- Access is restricted to users within defined geofence zones
- Location is checked continuously every 30 seconds
- Used for high-security scenarios

**When DISABLED:**
- No location verification overlay appears
- All mobile visitors can access the app
- No GPS checking occurs
- Useful for testing, maintenance, or public access events

## Technical Details

### Files Modified
1. **systemSettings.php** - Added toggle UI and backend handler
2. **explore.php** - Added status check before enforcing geofencing
3. **rooms.php** - Added status check before enforcing geofencing
4. **geofencing.js** - Added status check in initialization
5. **check_geofence_status.php** - New API endpoint for status checks

### Database Changes
- Table: `geofences`
- New Column: `enabled` (TINYINT(1), DEFAULT 1)
- Values: 1 = enabled, 0 = disabled

### Safety Features
- **Fail-safe design**: If status check fails, geofencing defaults to ENABLED for security
- **Default state**: Geofencing is enabled by default
- **Instant updates**: Changes take effect immediately on next page load
- **Visual feedback**: Toggle shows loading state and success/error messages

## API Endpoint

**Check Geofence Status:**
```
GET /FinalDev/check_geofence_status.php
```

**Response:**
```json
{
  "success": true,
  "enabled": true,
  "message": "Geofencing is enabled"
}
```

## Troubleshooting

**Toggle doesn't work:**
1. Check browser console for JavaScript errors
2. Verify database migration was successful
3. Ensure `check_geofence_status.php` is accessible

**Geofencing still enforces when disabled:**
1. Clear browser cache
2. Reload the mobile page completely (hard refresh)
3. Check database value: `SELECT enabled FROM geofences WHERE name = 'default'`

**Toggle shows error:**
1. Check PHP error logs
2. Verify database connection
3. Ensure user has permission to update geofences table

## Use Cases

1. **Public Events**: Disable during open houses or public tours
2. **Testing**: Disable for development and testing
3. **Maintenance**: Disable during system maintenance
4. **Emergency Access**: Quickly disable if legitimate users are locked out
5. **Normal Operation**: Keep enabled for regular visitor management

## Support

If you encounter issues:
1. Run the migration script first
2. Check browser console (F12) for errors
3. Review PHP error logs
4. Verify database table structure
5. Test the API endpoint directly in browser
