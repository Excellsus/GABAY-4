# Door QR Code Support in Dashboard - Implementation Guide

## Overview
Updated the home.php dashboard to fully support **doorpoint QR codes** for more accurate pathfinding and scan tracking. The system now displays individual door entry points for each office instead of just room-level QR codes.

## What Changed

### 1. Database Query Enhancement
**File:** `home.php` (Lines 142-175)

**Before:**
- Queried only `qrcode_info` table (room-level QR codes)
- Showed one entry per office
- Used `qr_scan_logs` without door index filtering

**After:**
```php
$officeQrMonitoringStmt = $connect->prepare("
    SELECT 
        o.id as office_id,
        o.name as office_name,
        o.location as room_location,
        dqr.id as door_qr_id,
        dqr.door_index,
        dqr.is_active,
        COUNT(DISTINCT qsl.id) as total_scans,
        MAX(qsl.check_in_time) as last_scanned_at,
        COALESCE(
            CASE 
                WHEN MAX(qsl.check_in_time) IS NULL THEN 999
                ELSE DATEDIFF(NOW(), MAX(qsl.check_in_time))
            END, 999
        ) as days_since_last_scan,
        SUM(CASE WHEN DATE(qsl.check_in_time) = CURDATE() THEN 1 ELSE 0 END) as today_scans
    FROM offices o
    INNER JOIN door_qrcodes dqr ON o.id = dqr.office_id
    LEFT JOIN qr_scan_logs qsl ON (o.id = qsl.office_id AND dqr.door_index = qsl.door_index)
    GROUP BY o.id, o.name, o.location, dqr.id, dqr.door_index, dqr.is_active
    ORDER BY days_since_last_scan DESC, total_scans DESC
");
```

**Key Changes:**
- ✅ Joins `door_qrcodes` table to get all door entry points
- ✅ Matches `qr_scan_logs` by both `office_id` AND `door_index`
- ✅ Groups by individual door QR codes (not just offices)
- ✅ Calculates `today_scans` for daily activity tracking
- ✅ Tracks last scan time per doorpoint

### 2. Statistics Update
**Before:** Counted offices with QR codes  
**After:** Counts individual door QR codes

```php
// Count door QR codes by status categories
$activeQrCount = count(array_filter($officeQrMonitoringData, fn($door) => $door['is_active'] == 1));
$staleQrCount = count(array_filter($officeQrMonitoringData, fn($door) => 
    $door['is_active'] == 1 && 
    $door['days_since_last_scan'] >= 7 && 
    $door['last_scanned_at'] !== null
));
$neverScannedCount = count(array_filter($officeQrMonitoringData, fn($door) => 
    $door['last_scanned_at'] === null
));
```

**Statistics Now Show:**
- Active Door QR Codes (total count of all active door entry points)
- Stale Door QR Codes (not scanned in 7+ days, excluding never-scanned)
- Never Scanned (door QR codes with zero scans)

### 3. UI/UX Enhancements

#### Grouped Display
Offices are now grouped with expandable door lists:

```
┌─────────────────────────────────────────────┐
│ 🏢 IT Department        [4 Doors]          │
│    📍 room-12-1                            │
├─────────────────────────────────────────────┤
│  🚪 Door 0    [+2 today]                   │
│     📊 45 total scans                       │
│     🕒 Last: Nov 8, 2025 3:15 PM    ✅ Active│
├─────────────────────────────────────────────┤
│  🚪 Door 1    [+0 today]                   │
│     📊 23 total scans                       │
│     🕒 Last: Nov 5, 2025 10:22 AM   ⚠️ 3 days│
├─────────────────────────────────────────────┤
│  🚪 Door 2                                  │
│     📊 0 total scans                        │
│     ⚠️ Never scanned                        │
└─────────────────────────────────────────────┘
```

#### Visual Indicators
- **Today's Activity Badge:** `+N today` shows scans from current day
- **QR Code Icon:** Differentiates QR scan count
- **Clock Icon:** Shows last scan timestamp with date + time
- **Status Badges:**
  - 🟢 Active (green)
  - ⚠️ Stale (orange) - 7+ days since scan
  - ⚠️ Never Scanned (yellow)
  - ⭕ Inactive (gray)

### 4. Filtering and Sorting System

#### Sort Options
**Control:** "Sort by Scans" dropdown

- **Most Scanned First** (default) - Descending order
- **Least Scanned First** - Ascending order

**How it works:**
- Sorts doors within each office group
- Maintains office grouping structure
- Preserves filter selections while sorting

#### Filter Options
**Control:** "Filter" dropdown

| Filter | Shows |
|--------|-------|
| **All Door QR Codes** | Every door entry point |
| **Today's Scans Only** | Doors scanned today (today_scans > 0) |
| **Latest Scanned** | Doors with at least one scan |
| **Active Only** | Doors with is_active = 1 |
| **Inactive Only** | Doors with is_active = 0 |
| **Stale (7+ days)** | Active doors not scanned in 7+ days |
| **Never Scanned** | Doors with zero scans ever |

**Smart Behavior:**
- Hides entire office groups if all doors are filtered out
- Shows office group if at least one door matches filter
- Maintains sort order after filtering
- Updates count in console for debugging

### 5. JavaScript Implementation

**File:** `home.php` (Lines ~950-1050)

```javascript
// Door QR Code Filtering and Sorting
document.addEventListener('DOMContentLoaded', function() {
    const sortSelect = document.getElementById('qr-sort-by');
    const filterSelect = document.getElementById('qr-filter-by');
    const qrList = document.getElementById('office-qr-list');

    function applyFiltersAndSort() {
        const sortOrder = sortSelect.value;
        const filterType = filterSelect.value;
        
        const officeGroups = Array.from(qrList.querySelectorAll('.office-group'));
        
        officeGroups.forEach(group => {
            const doorItems = Array.from(group.querySelectorAll('.door-qr-item'));
            let visibleDoors = 0;
            
            // Filter doors based on criteria
            doorItems.forEach(item => {
                // Check filter conditions...
                if (show) {
                    item.style.display = '';
                    visibleDoors++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Hide office group if no visible doors
            group.style.display = visibleDoors === 0 ? 'none' : '';
            
            // Sort visible doors by scan count
            const visibleDoorItems = doorItems.filter(item => item.style.display !== 'none');
            visibleDoorItems.sort((a, b) => {
                const countA = parseInt(a.dataset.scanCount || '0');
                const countB = parseInt(b.dataset.scanCount || '0');
                return sortOrder === 'asc' ? countA - countB : countB - countA;
            });
            
            // Re-append in sorted order
            const doorList = group.querySelector('.door-qr-list');
            visibleDoorItems.forEach(item => doorList.appendChild(item));
        });
    }
    
    sortSelect.addEventListener('change', applyFiltersAndSort);
    filterSelect.addEventListener('change', applyFiltersAndSort);
    applyFiltersAndSort(); // Initial application
});
```

**Data Attributes Used:**
- `data-status`: "active" | "inactive"
- `data-days`: Days since last scan (number)
- `data-scanned`: "yes" | "no"
- `data-scan-count`: Total scan count (number)
- `data-today-scans`: Today's scan count (number)
- `data-last-scan-timestamp`: Unix timestamp of last scan

## Database Schema Requirements

### Required Tables

1. **door_qrcodes**
```sql
CREATE TABLE `door_qrcodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_id` int(11) NOT NULL,
  `door_index` int(11) NOT NULL,
  `room_id` varchar(50) NOT NULL,
  `qr_code_data` text NOT NULL,
  `qr_code_image` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `office_id` (`office_id`),
  CONSTRAINT `door_qrcodes_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE
);
```

2. **qr_scan_logs** (updated)
```sql
-- Must have door_index column
ALTER TABLE qr_scan_logs ADD COLUMN door_index INT(11) DEFAULT NULL;
```

**Critical:** The `qr_scan_logs.door_index` column must exist for proper filtering. If missing, run:
```bash
php add_door_index_to_scan_logs.php
```

## Testing Checklist

### Data Display
- [ ] Office groups show correct office names
- [ ] Room locations display accurately
- [ ] Door count badge matches actual doors
- [ ] Each door shows correct door_index number
- [ ] Today's scan badge appears when today_scans > 0
- [ ] Total scan counts are accurate per door
- [ ] Last scan timestamps show date + time
- [ ] Never scanned doors show warning

### Statistics
- [ ] Active QR Count matches sum of active doors
- [ ] Stale QR Count shows doors >7 days (excluding never-scanned)
- [ ] Never Scanned count shows doors with zero scans
- [ ] Statistics update when filtering

### Filtering
- [ ] "All Door QR Codes" shows everything
- [ ] "Today's Scans Only" shows only doors with today_scans > 0
- [ ] "Latest Scanned" hides never-scanned doors
- [ ] "Active Only" shows only is_active = 1
- [ ] "Inactive Only" shows only is_active = 0
- [ ] "Stale (7+ days)" shows active doors with 7+ days since scan
- [ ] "Never Scanned" shows doors with no scans
- [ ] Office groups hide when all doors filtered out

### Sorting
- [ ] "Most Scanned First" sorts descending
- [ ] "Least Scanned First" sorts ascending
- [ ] Sort maintains after filter changes
- [ ] Doors stay within their office groups

### Edge Cases
- [ ] Offices with 1 door display correctly
- [ ] Offices with 4+ doors all show
- [ ] Zero scan doors show "Never scanned"
- [ ] Future scan times don't break calculations
- [ ] NULL timestamps handled gracefully

## Migration from Old System

### Before (Room QR Codes)
```php
// Old query - one entry per office
SELECT o.id, o.name, COUNT(qsl.id) as total_scans
FROM offices o
LEFT JOIN qrcode_info qc ON o.id = qc.office_id
LEFT JOIN qr_scan_logs qsl ON o.id = qsl.office_id
GROUP BY o.id
```

**Problem:** Couldn't distinguish which door was scanned

### After (Door QR Codes)
```php
// New query - multiple entries per office (one per door)
SELECT o.id, o.name, dqr.door_index, COUNT(qsl.id) as total_scans
FROM offices o
INNER JOIN door_qrcodes dqr ON o.id = dqr.office_id
LEFT JOIN qr_scan_logs qsl ON (o.id = qsl.office_id AND dqr.door_index = qsl.door_index)
GROUP BY o.id, dqr.door_index
```

**Solution:** Accurate per-door tracking

## Performance Considerations

### Query Optimization
- Uses `INNER JOIN` on door_qrcodes (only offices with doors)
- Groups efficiently by composite key (office_id + door_index)
- Calculates today_scans in single query (no additional calls)
- Single query replaces multiple separate queries

### Frontend Rendering
- DOM manipulation only on filter/sort changes
- Uses `display: none` instead of removing elements
- Batch DOM updates for smooth UX
- Logs visible count for debugging without UI impact

### Scalability
- **100 offices × 4 doors avg = 400 items**: Smooth
- **500 offices × 4 doors avg = 2000 items**: Acceptable with pagination
- **1000+ offices**: Consider pagination or lazy loading

## Troubleshooting

### Issue: "No doors showing"
**Cause:** door_qrcodes table empty  
**Fix:** Generate door QR codes via officeManagement.php

### Issue: "All scans show under Door 0"
**Cause:** qr_scan_logs.door_index is NULL  
**Fix:** Run migration script `add_door_index_to_scan_logs.php`

### Issue: "Office group header but no doors"
**Cause:** All doors filtered out  
**Fix:** Expected behavior - group should hide automatically

### Issue: "Today's scans not updating"
**Cause:** Server timezone mismatch  
**Fix:** Check MySQL timezone vs PHP timezone

### Issue: "Sorting not working"
**Cause:** data-scan-count attribute missing or non-numeric  
**Fix:** Verify PHP outputs numeric values in data attributes

## Future Enhancements

1. **Search/Quick Filter**
   - Text search by office name
   - Jump to specific office

2. **Export Functionality**
   - CSV export of filtered results
   - Door-level scan history

3. **Real-time Updates**
   - WebSocket for live scan notifications
   - Auto-refresh today_scans counter

4. **Visual Charts**
   - Heatmap of door usage
   - Time-of-day scan patterns per door

5. **Mobile Responsive**
   - Collapsible office groups
   - Touch-friendly controls

## Related Files

- `home.php` - Main dashboard file (updated)
- `door_qr_api.php` - Door QR code management API
- `qr_api.php` - Legacy room QR code API (deprecated for new features)
- `add_door_index_to_scan_logs.php` - Migration script for door_index column
- `officeManagement.php` - Door QR code generation interface

## See Also

- `QR_SCAN_PATHFINDING_INTEGRATION.md` - How door QR codes enable precise pathfinding
- `DOOR_QR_IMPLEMENTATION.md` - Technical implementation of door QR system
- `OFFICE_QR_SVG_LOAD_FIX.md` - SVG floor loading with door markers
