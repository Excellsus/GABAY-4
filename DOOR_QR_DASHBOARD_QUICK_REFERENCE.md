# Door QR Dashboard - Quick Reference

## 🎯 What Was Updated

The **home.php Actions Panel** now fully supports **doorpoint QR codes** instead of just room-level QR codes.

## ✨ Key Features

### 📊 Enhanced Statistics
```
┌─────────────────────────┐
│  45  Active Door QRs    │
│  12  Stale (7+ days)    │
│   8  Never Scanned      │
└─────────────────────────┘
```
- Counts **individual door QR codes**, not just offices
- Stale = Active doors not scanned in 7+ days
- Never Scanned = Doors with zero scans

### 🏢 Grouped Office Display

Each office shows:
- **Office Name** with visual header
- **Room Location** (e.g., room-12-1)
- **Door Count Badge** (e.g., "4 Doors")
- **Individual Door Entries** with:
  - Door index (🚪 Door 0, Door 1, etc.)
  - Today's activity badge (+N today)
  - Total scan count
  - Last scan timestamp (date + time)
  - Status indicator (Active/Stale/Never/Inactive)

### 🔍 Filtering Options

| Filter | Shows |
|--------|-------|
| **All Door QR Codes** | Every door entry point (default) |
| **Today's Scans Only** | Doors scanned today |
| **Latest Scanned** | Doors with at least one scan |
| **Active Only** | Active doors (is_active = 1) |
| **Inactive Only** | Inactive doors (is_active = 0) |
| **Stale (7+ days)** | Active doors >7 days since last scan |
| **Never Scanned** | Doors with zero scans |

### 🔢 Sorting Options

- **Most Scanned First** (default) - Descending by scan count
- **Least Scanned First** - Ascending by scan count

*Sorting applies within each office group*

## 🎨 Visual Indicators

### Status Badges
- 🟢 **Active** - Green badge, door is operational
- ⚠️ **Stale** - Orange/red badge, "X days ago"
- ⚠️ **Never Scanned** - Yellow badge with warning icon
- ⭕ **Inactive** - Gray badge with X icon

### Activity Indicators
- **+N today** - Blue badge showing today's scan count
- **📊 N total scans** - QR code icon with total count
- **🕒 Last: Date Time** - Clock icon with timestamp

## 🔧 Technical Implementation

### Database Query
```php
// Joins door_qrcodes and filters by door_index
SELECT 
    o.name, 
    dqr.door_index,
    COUNT(qsl.id) as total_scans,
    MAX(qsl.check_in_time) as last_scanned_at,
    SUM(CASE WHEN DATE(qsl.check_in_time) = CURDATE() THEN 1 ELSE 0 END) as today_scans
FROM offices o
INNER JOIN door_qrcodes dqr ON o.id = dqr.office_id
LEFT JOIN qr_scan_logs qsl ON (o.id = qsl.office_id AND dqr.door_index = qsl.door_index)
GROUP BY o.id, dqr.door_index
```

### Key Improvements
✅ Accurate per-door scan tracking  
✅ Today's activity monitoring  
✅ Individual door last-scan timestamps  
✅ Smart filtering (hides empty office groups)  
✅ Sortable by scan count within offices  
✅ Real-time status indicators  

## 📋 Example Output

```
╔══════════════════════════════════════════════╗
║ 🏢 IT Department              [4 Doors]     ║
║    📍 room-12-1                             ║
╠══════════════════════════════════════════════╣
║  🚪 Door 0    [+2 today]                    ║
║     📊 45 total scans                        ║
║     🕒 Last: Nov 8, 2025 3:15 PM             ║
║                                    🟢 Active ║
╠──────────────────────────────────────────────╣
║  🚪 Door 1                                   ║
║     📊 23 total scans                        ║
║     🕒 Last: Nov 5, 2025 10:22 AM            ║
║                              ⚠️ 3 days ago   ║
╠──────────────────────────────────────────────╣
║  🚪 Door 2                                   ║
║     📊 12 total scans                        ║
║     🕒 Last: Oct 28, 2025 2:45 PM            ║
║                              ⚠️ 11 days ago  ║
╠──────────────────────────────────────────────╣
║  🚪 Door 3                                   ║
║     📊 0 total scans                         ║
║     ⚠️ Never scanned                         ║
╚══════════════════════════════════════════════╝
```

## 🚀 Quick Start

1. **Access Dashboard:** `http://localhost/FinalDev/home.php`
2. **Scroll to Actions Panel:** "Office QR List" section
3. **View Statistics:** Top cards show Active/Stale/Never counts
4. **Apply Filters:** Use dropdowns to filter/sort
5. **Monitor Activity:** Look for "+N today" badges for active doors

## ⚙️ Requirements

### Database Schema
- ✅ `door_qrcodes` table exists
- ✅ `qr_scan_logs.door_index` column exists
- ✅ Foreign key constraint: door_qrcodes → offices

### Data Population
- Door QR codes generated via officeManagement.php
- Scan logs populated with door_index values
- Active/inactive status set for each door

## 🔍 Troubleshooting

**No doors showing?**
→ Check if door_qrcodes table has entries

**All scans on Door 0?**
→ Run `add_door_index_to_scan_logs.php` migration

**Statistics seem wrong?**
→ Verify qr_scan_logs has door_index populated

**Filter not working?**
→ Check console for JavaScript errors

**Office groups empty?**
→ Expected if all doors filtered out

## 📊 Benefits Over Old System

| Old (Room QR) | New (Door QR) |
|---------------|---------------|
| 1 QR per office | Multiple QRs per office |
| Can't tell which door | Exact door tracking |
| No door-level stats | Per-door scan counts |
| Generic "room scanned" | Precise entry point data |
| Limited pathfinding | Accurate pathfinding from exact door |

## 🎯 Use Cases

1. **Security Monitoring**
   - Identify unused entry points
   - Track high-traffic doors
   - Monitor today's activity

2. **Maintenance Planning**
   - Find stale QR codes that need attention
   - Identify never-scanned doors (potential issues)

3. **Visitor Analytics**
   - Popular entry points
   - Time-based patterns (via today_scans)

4. **QR Code Health**
   - Active vs inactive status
   - Scan frequency monitoring

## 📝 Related Documentation

- `DOOR_QR_DASHBOARD_IMPLEMENTATION.md` - Full technical guide
- `QR_SCAN_PATHFINDING_INTEGRATION.md` - Pathfinding integration
- `DOOR_QR_IMPLEMENTATION.md` - Door QR system overview
