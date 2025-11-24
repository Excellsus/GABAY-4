# Door QR Code Monitoring - Complete Fix & User Guide

## 🎯 Issues Fixed

### 1. ✅ Door Numbering (0-based → 1-based)
**Problem**: Doors displayed as "Door 0, Door 1, Door 2..."  
**Solution**: Added `+ 1` to display, now shows "Door 1, Door 2, Door 3..."  
**File**: `home.php` line ~721

### 2. ✅ Statistics Accuracy
**Problem**: Statistics counted ALL scans including 706 legacy office-level scans  
**Solution**: SQL query now filters `WHERE qsl.door_index IS NOT NULL`  
**Result**: Only door-level scans are counted  
**File**: `home.php` lines 142-169

### 3. ✅ Missing Scan Data
**Problem**: No door-level scan logs existed for testing  
**Solution**: Created migration script to populate realistic sample data  
**Script**: `populate_door_scan_data.php` (162 scans across 21 doors)

### 4. ✅ Real-Time Updates
**Problem**: Dashboard doesn't auto-refresh after scanning QR codes  
**Solution**: Added "Refresh Data" button and "Last updated" timestamp  
**Usage**: Click button after scanning to see new scans  
**File**: `home.php` lines ~655-662

### 5. ✅ Filter Logic Improvements
**Problem**: "Latest Scanned", "Today's Scans", "Stale" filters not working properly  
**Solution**: Enhanced JavaScript filtering + sorting logic  
**Changes**:
- "Latest Scanned" now sorts by timestamp (most recent first)
- "Today's Scans" properly checks `data-today-scans > 0`
- "Stale" checks for 7+ days AND previously scanned
- "Never Scanned" shows doors with no scan history

---

## 📊 How Statistics Work

### Data Flow:
```
1. Door QR Scanned → qr_scan_logs table (with door_index)
2. Page loads → SQL query aggregates scans per door
3. JavaScript filters/sorts based on data attributes
```

### Key SQL Fields:
- `total_scans` - All-time scan count for this door
- `today_scans` - Scans from CURDATE() only
- `last_scanned_at` - Timestamp of most recent scan
- `days_since_last_scan` - Days since last scan (999 if never)

### JavaScript Data Attributes:
```html
<div class="door-qr-item"
     data-status="active|inactive"
     data-days="7"
     data-scanned="yes|no"
     data-scan-count="42"
     data-today-scans="3"
     data-last-scan-timestamp="1699876543">
```

---

## 🎛️ Filter Options Explained

### 1. **All Door QR Codes**
Shows all doors regardless of status or scan history.

### 2. **Today's Scans Only** ⏰
- Shows doors scanned TODAY (CURDATE())
- Checks: `data-today-scans > 0`
- **Important**: Refresh page after scanning to update!

### 3. **Latest Scanned** 🕒
- Shows all doors that have been scanned at least once
- Sorts by most recent scan first (timestamp DESC)
- Ignores never-scanned doors

### 4. **Active Only** ✅
- Shows doors with `is_active = 1`
- Ready for visitors to scan

### 5. **Inactive Only** ⛔
- Shows doors with `is_active = 0`
- Disabled/closed entry points

### 6. **Stale (7+ days)** ⚠️
- Shows ACTIVE doors not scanned in 7+ days
- Must have been scanned before (not never-scanned)
- Helps identify unused entry points

### 7. **Never Scanned** 🚫
- Shows doors with NO scan history
- `last_scanned_at IS NULL`

---

## 🔄 Why You Must Refresh

### The Problem:
PHP loads data **once** when page renders. After you scan a QR code:
- ✅ Database updates immediately
- ❌ Dashboard still shows old data
- ❌ Filters work on stale data

### The Solution:
1. **Scan a door QR code**
2. **Click "Refresh Data" button** (or press F5)
3. **Dashboard reloads with latest scans**
4. **Filters now work correctly**

### Why Not Auto-Refresh?
- Would reset user's filter/sort selections
- Could cause performance issues with many users
- Manual refresh gives control to admin

---

## 🧪 Testing Guide

### Test Today's Scans Filter:

1. **Scan a door QR code RIGHT NOW**
   - Use phone to scan any door QR
   - Should navigate to `explore.php?door_qr=1&office_id=X&door_index=Y`

2. **Verify scan was recorded**:
   ```bash
   php test_today_scans.php
   ```
   Should show your scan in "Most Recent 5 Door Scans"

3. **Refresh home.php**:
   - Click "Refresh Data" button
   - Or press F5

4. **Apply "Today's Scans Only" filter**:
   - Should show the door you just scanned
   - Badge should say "+1 today" or similar

5. **Check "Last updated" timestamp**:
   - Should match current time

### Test Latest Scanned Filter:

1. **Apply "Latest Scanned" filter**
2. **Verify sorting**:
   - Doors should be ordered by most recent scan first
   - Check timestamps: "Last: Nov 13, 2025 2:51 AM" should be at top

### Test Stale Filter:

1. **Apply "Stale (7+ days)" filter**
2. **Should show**:
   - Only active doors
   - Not scanned in 7+ days
   - Must have scan history (not never-scanned)

---

## 🛠️ Maintenance Scripts

### `populate_door_scan_data.php`
**Purpose**: Creates realistic sample scan data for testing  
**Usage**:
```bash
php populate_door_scan_data.php
```
**What it does**:
- Finds all door QR codes
- Generates 162 scans with varied patterns:
  - 20% very active (multiple today)
  - 30% active (last 3 days)
  - 20% moderate (4-6 days ago)
  - 20% stale (7-30 days ago)
  - 10% never scanned

**When to use**:
- Initial setup
- After clearing test data
- Testing filter functionality

### `test_today_scans.php`
**Purpose**: Verifies "Today's Scans" detection is working  
**Usage**:
```bash
php test_today_scans.php
```
**What it checks**:
- MySQL date/time (CURDATE(), NOW())
- Most recent 5 door scans
- Today's scans query accuracy
- Filter logic expectations

**When to use**:
- After scanning a QR code
- When "Today's Scans" filter shows nothing
- Debugging date/time issues

### `verify_door_qr_stats.php`
**Purpose**: Comprehensive statistics verification  
**Usage**:
```bash
php verify_door_qr_stats.php
```
**What it checks**:
- Door QR code count
- Door-level scan count vs legacy scans
- Sample statistics query
- Category counts (active, stale, never)
- All verification checks

**When to use**:
- After implementing fixes
- Before deployment
- Regular health checks

---

## 📋 Quick Reference

### Current System Status:
- ✅ 21 door QR codes
- ✅ 162 door-level scans
- ✅ 706 legacy scans (excluded from stats)
- ✅ Door numbering: 1-based
- ✅ Filters: Working correctly
- ✅ Refresh button: Added

### Common Issues:

**"Today's Scans" shows nothing after scanning**
→ Did you refresh the page? Click "Refresh Data"

**Filter shows wrong results**
→ Check "Last updated" timestamp. If old, refresh page.

**Door numbers still show as 0, 1, 2**
→ Clear browser cache and reload

**Statistics seem inflated**
→ SQL query should filter `door_index IS NOT NULL`

**"Latest Scanned" not sorting correctly**
→ Check JavaScript console for errors

---

## 🔮 Future Improvements

### Potential Enhancements:
1. **Auto-refresh** with WebSocket or polling
2. **Real-time notifications** when door is scanned
3. **Statistics API endpoint** for AJAX updates
4. **Export to CSV** functionality
5. **Date range picker** for custom time periods
6. **Scan heatmap** visualization
7. **Email alerts** for stale doors

---

## 📞 Support

**Files Modified**:
- `home.php` - Main dashboard (SQL query, display, filters)
- `populate_door_scan_data.php` - Sample data generator
- `test_today_scans.php` - Today's scans tester
- `verify_door_qr_stats.php` - Statistics verifier

**Key Concepts**:
- Door index: 0-based in database, 1-based in display
- Scan logs: Must have `door_index IS NOT NULL`
- Filters: Client-side JavaScript on server-rendered data
- Refresh: Required to see new scans

**Database Schema**:
- `door_qrcodes` - Door QR code definitions
- `qr_scan_logs` - Scan history with `door_index` column
- `offices` - Office information

---

Last Updated: November 13, 2025  
Version: 2.0 - Complete Door QR Fix
