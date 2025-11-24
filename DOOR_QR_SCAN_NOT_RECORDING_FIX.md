# DOOR QR SCAN NOT RECORDING FIX

## Issue Summary
**Problem:** Door QR code scans were NOT being recorded in the database with the `door_index` column, causing:
- All door scans to show as "0 scans" in the dashboard
- "Today's Scans Only" filter showing nothing after scanning
- "Latest Scanned", "Stale", and "Never Scanned" filters not working correctly
- Door QR statistics appearing as "Never Scanned" despite actual scans

## Root Cause
The `mobileScreen/explore.php` file was logging door QR scans **without the `door_index` column**.

**Line 150-151 (BEFORE FIX):**
```php
$stmt_log = $connect->prepare("INSERT INTO qr_scan_logs (office_id, qr_code_id, check_in_time) VALUES (?, ?, NOW())");
$stmt_log->execute([$door_office_id, $qr_code_info_id]);
```

**Result:** Door scans were being recorded as regular office-level scans, causing:
- `qr_scan_logs.door_index` = NULL (default value)
- Dashboard SQL query filters: `WHERE qsl.door_index IS NOT NULL` excluded these scans
- 706 legacy office scans counted, but 0 door scans counted

## The Fix
**File:** `mobileScreen/explore.php`  
**Line:** 150-151

**AFTER FIX:**
```php
$stmt_log = $connect->prepare("INSERT INTO qr_scan_logs (office_id, qr_code_id, door_index, check_in_time) VALUES (?, ?, ?, NOW())");
$stmt_log->execute([$door_office_id, $qr_code_info_id, $door_index]);
```

**Changes:**
1. Added `door_index` to INSERT column list
2. Added `$door_index` to execute() parameter array
3. Now properly records which door was scanned (0, 1, 2, 3, etc.)

## What This Fixes

### ✅ Before Fix (BROKEN)
- Scan "Kinder Joy Door 1" → Database records:
  ```
  office_id: 145
  qr_code_id: 1
  door_index: NULL  ❌ MISSING!
  check_in_time: 2025-11-13 10:30:00
  ```
- Dashboard SQL: `WHERE door_index IS NOT NULL` → **Excludes this scan** ❌
- Result: "Never Scanned" status, 0 total scans

### ✅ After Fix (WORKING)
- Scan "Kinder Joy Door 1" → Database records:
  ```
  office_id: 145
  qr_code_id: 1
  door_index: 1  ✅ RECORDED!
  check_in_time: 2025-11-13 10:30:00
  ```
- Dashboard SQL: `WHERE door_index IS NOT NULL` → **Includes this scan** ✅
- Result: Shows in "Today's Scans Only", displays "1 scan", correct door number

## Testing Instructions

### 1. Clear Old Session Data
To ensure clean test, clear browser cookies or use incognito mode to start fresh session.

### 2. Scan a Door QR Code
1. Scan any door QR code (e.g., "Kinder Joy Door 1")
2. Mobile device should open: `explore.php?door_qr=1&office_id=145&door_index=1&from_qr=1`
3. Page should load normally with office highlighted

### 3. Verify Database Record
Run diagnostic script:
```bash
php check_recent_scans.php
```

**Expected Output:**
```
Most Recent 10 Door Scans:
--------------------------------------------------------------------------------
Scan #1: Office 'Kinder Joy' (ID: 145) | Door: 1 | Time: 2025-11-13 10:30:00 | QR Code ID: 1

Kinder Joy Office Information:
--------------------------------------------------------------------------------
Office ID: 145
Office Name: Kinder Joy
Location: room-12-1

Scans for Kinder Joy:
  Scan #1: Door 1 | Time: 2025-11-13 10:30:00

Today's Door Scans (using CURDATE()):
--------------------------------------------------------------------------------
Scan #1: Office 'Kinder Joy' | Door: 1 | Time: 2025-11-13 10:30:00 | Scan Date: 2025-11-13 | Today: 2025-11-13
```

### 4. Check Dashboard (home.php)
1. Open `http://localhost/FinalDev/home.php`
2. Click **"Refresh Data"** button (important!)
3. Look for "Kinder Joy" in Office QR Code Monitoring section

**Expected Result:**
- Office: "Kinder Joy"
- Door: "Door 2" (displays as door_index + 1)
- Status: "+1 today" badge (green)
- Total Scans: "1 scan"
- Last Scanned: "Just now" or timestamp

### 5. Test Filters
**Today's Scans Only:**
- Click filter → Should show "Kinder Joy Door 2" with "+1 today" badge

**Latest Scanned:**
- Click filter → "Kinder Joy Door 2" should appear at top, sorted by timestamp DESC

**Active Only:**
- Click filter → Should show all active doors including "Kinder Joy Door 2"

**Never Scanned:**
- Click filter → "Kinder Joy Door 2" should NOT appear (it's been scanned)

## Related Files

### Primary Fix
- **mobileScreen/explore.php** (lines 150-151) - Door scan logging

### Dashboard Query
- **home.php** (lines 142-173) - Statistics query with `WHERE qsl.door_index IS NOT NULL`

### Diagnostic Tools
- **check_recent_scans.php** - Verify door scans recorded correctly
- **test_today_scans.php** - Check today's scan detection
- **verify_door_qr_stats.php** - Comprehensive statistics validation

### Documentation
- **DOOR_QR_MONITORING_GUIDE.md** - Complete user guide for door QR monitoring system

## Database Schema

**Table: qr_scan_logs**
```sql
CREATE TABLE qr_scan_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  office_id INT NOT NULL,
  qr_code_id INT NOT NULL,
  door_index INT DEFAULT NULL,  -- NULL = office scan, 0-N = door scan
  check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (qr_code_id) REFERENCES qrcode_info(id)
);
```

**Key Points:**
- `door_index IS NULL` → Legacy office-level scan
- `door_index IS NOT NULL` → Door-level scan (the fix ensures this is set)
- Dashboard filters use: `WHERE qsl.door_index IS NOT NULL` to exclude legacy scans

## Verification Checklist

After applying fix, verify:
- [ ] PHP syntax check passes: `php -l mobileScreen/explore.php`
- [ ] Scan door QR code with mobile device
- [ ] Check `qr_scan_logs` table has new record with `door_index` populated
- [ ] Run `php check_recent_scans.php` - shows recent door scan
- [ ] Dashboard shows door with scan count > 0
- [ ] "Today's Scans Only" filter shows scanned door
- [ ] "Latest Scanned" filter shows door at top
- [ ] "Never Scanned" filter excludes scanned door
- [ ] Door number displays correctly (door_index + 1)

## Common Issues After Fix

### "Still showing 0 scans after scanning"
**Cause:** Scans before fix don't have `door_index` set  
**Solution:** Re-scan door QR code after fix applied

### "Old scans don't show up"
**Cause:** Legacy scans (before fix) have `door_index = NULL`  
**Solution:** Cannot retroactively fix old scans. New scans will work correctly.

### "Dashboard still shows 'Never Scanned'"
**Cause:** Forgot to click "Refresh Data" button  
**Solution:** Dashboard loads data once at page load. Click "Refresh Data" after scanning.

### "Today's filter shows nothing"
**Cause:** Sample data from `populate_door_scan_data.php` is dated Nov 12, 2025  
**Solution:** Scan a door QR code today (Nov 13) to create current data

## Technical Notes

### Why door_index Was Missing
The door QR scan feature was added after the initial QR system. The logging code at line 150-151 was copied from the original office QR scan logic, which didn't have door support. The `door_index` parameter was available in the `$door_index` variable but wasn't being passed to the SQL INSERT statement.

### Foreign Key Constraint
The `qr_scan_logs.qr_code_id` column references `qrcode_info.id` (legacy office QR codes). For door scans, we fetch the office's legacy QR code ID on line 147-148:
```php
$stmt_qr_info = $connect->prepare("SELECT id FROM qrcode_info WHERE office_id = ? LIMIT 1");
$stmt_qr_info->execute([$door_office_id]);
```
This maintains referential integrity while supporting door-level tracking via `door_index`.

### Session Deduplication
Door scans are deduplicated per session using:
```php
$door_scan_key = "door_scanned_" . $door_office_id . "_" . $door_index;
if (!isset($_SESSION[$door_scan_key])) {
    // Log scan
    $_SESSION[$door_scan_key] = true;
}
```
This prevents duplicate scans if user refreshes page or returns to same door within same browser session.

## Date: November 13, 2025
**Fixed By:** GitHub Copilot  
**Reported By:** User (Kinder Joy Door 1 scan not registering)  
**Impact:** CRITICAL - All door QR scans were not being tracked correctly  
**Status:** ✅ FIXED
