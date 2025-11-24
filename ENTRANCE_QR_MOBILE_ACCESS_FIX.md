# Entrance QR Code Mobile Access Fix

## Problem
Entrance QR codes were generated with `http://localhost/gabay/...` URLs, which cannot be accessed from mobile devices because `localhost` refers to the phone itself, not your XAMPP server.

## Root Cause
When scanning a QR code on a mobile device:
- ❌ `http://localhost/gabay/...` → Phone tries to connect to itself
- ✅ `http://192.168.1.100/gabay/...` → Phone connects to your computer's IP

## Solution

### Step 1: Find Your Computer's IP Address

**Windows:**
```powershell
ipconfig
```
Look for "IPv4 Address" under your active network adapter (usually WiFi or Ethernet).
Example: `192.168.1.100`

**Alternative:** Use the regeneration tool which auto-detects your IP.

### Step 2: Regenerate QR Codes with Network IP

**Option A: Via Browser (Recommended)**
1. Open: `http://localhost/gabay/regenerate_entrance_qr_with_ip.php`
2. Verify the detected IP address (e.g., `192.168.1.100`)
3. Click "Regenerate All Entrance QR Codes"
4. Download regenerated QR codes from `entrance_qrcodes/` folder

**Option B: Via Command Line**
```powershell
cd "C:\Program Files\xampp\htdocs\gabay"
& "C:\Program Files\xampp\php\php.exe" regenerate_entrance_qr_with_ip.php
```

### Step 3: Test on Mobile

**Before scanning QR code:**
1. Connect your phone to the **same WiFi network** as your computer
2. Open mobile browser and test this URL:
   ```
   http://YOUR_IP_HERE/gabay/mobileScreen/explore.php
   ```
   Example: `http://192.168.1.100/gabay/mobileScreen/explore.php`
3. If the page loads, you're ready to scan QR codes!

**Scan QR code:**
1. Use any QR scanner app or camera app
2. Scan the entrance QR code
3. Should redirect to explore.php with entrance highlighted
4. Map should show "YOU ARE HERE" marker at the entrance

## Verification Checklist

- [ ] Computer IP address identified (run `ipconfig`)
- [ ] Mobile device on same WiFi network
- [ ] Can access `http://YOUR_IP/gabay/mobileScreen/explore.php` on mobile browser
- [ ] QR codes regenerated with network IP (check database: `qr_code_data` column)
- [ ] QR code PNG files updated in `entrance_qrcodes/` folder
- [ ] QR code scan redirects to explore.php (not 404 or connection error)
- [ ] Entrance marker appears on map with "YOU ARE HERE" label

## Diagnostic Commands

**Check current QR URLs in database:**
```powershell
cd "C:\Program Files\xampp\htdocs\gabay"
& "C:\Program Files\xampp\php\php.exe" check_entrance_qr_status.php
```

**View QR codes in browser:**
```
http://localhost/gabay/floorPlan.php
```
Click any entrance icon → QR modal → Preview shows current URL

## Common Issues

### Issue 1: "This site can't be reached" on mobile
**Cause:** Mobile not on same WiFi, or firewall blocking access
**Fix:** 
- Verify mobile WiFi matches computer WiFi
- Temporarily disable Windows Firewall to test
- Add firewall rule for port 80 (Apache)

### Issue 2: QR scan redirects to 404 page
**Cause:** Entrance is inactive in database
**Fix:** Check `is_active` column in `entrance_qrcodes` table

### Issue 3: QR scan shows "Entrance not found"
**Cause:** `entrance_id` in QR URL doesn't match database
**Fix:** Regenerate QR codes with the tool

### Issue 4: Network IP keeps changing
**Cause:** Router assigns dynamic IP via DHCP
**Solution:** Set static IP in Windows network settings or router

## Technical Details

### QR Code URL Format
```
http://192.168.1.100/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_main_1&floor=1
```

**Parameters:**
- `entrance_qr=1` → Tells explore.php this is an entrance scan
- `entrance_id=entrance_main_1` → Unique entrance identifier
- `floor=1` → Floor number (1, 2, or 3)

### Mobile Handler
File: `mobileScreen/explore.php` (lines 207-273)

**Flow:**
1. Parse URL parameters
2. Validate entrance exists and is active in database
3. If inactive → redirect to `404_inactive_door.php`
4. If active → log scan, show entrance marker, open drawer

### Database Tables
- `entrance_qrcodes` → Entrance metadata and QR URLs
- `entrance_scan_logs` → Scan history (separate from office scans)

## Production Deployment

For actual deployment (not localhost testing):

1. **Domain/Public IP:** Replace with your actual domain or public IP
2. **HTTPS:** Use HTTPS for security (not HTTP)
3. **Update regeneration tool:** Modify `getLocalIP()` to return production URL

Example production URL:
```
https://capitol.gov.ph/visitor/explore.php?entrance_qr=1&entrance_id=entrance_main_1&floor=1
```

## Related Files

- `entrance_qr_api.php` → QR generation API
- `floorPlan.php` → Admin entrance management UI
- `mobileScreen/explore.php` → Mobile QR scan handler
- `create_entrance_tables.php` → Database schema creation
- `check_entrance_qr_status.php` → Diagnostic tool
- `regenerate_entrance_qr_with_ip.php` → IP-based regeneration tool (this fix)

## Support

If QR codes still don't work after regeneration:
1. Run diagnostic: `php check_entrance_qr_status.php`
2. Check Apache error log: `C:\xampp\apache\logs\error.log`
3. Check browser console on mobile for JavaScript errors
4. Verify database: `SELECT * FROM entrance_qrcodes;`
