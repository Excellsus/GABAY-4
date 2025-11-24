# GABAY — AI Coding Guide

This is a PHP-based office directory and indoor navigation system with a desktop admin UI, mobile visitor interface, SVG-based interactive floor plans, QR code generation, and 360° panorama tours. Built on XAMPP (PHP + MySQL/PDO).

## Architecture Overview

**Two-interface system:**
- **Admin** (root files): `home.php`, `officeManagement.php`, `floorPlan.php`, `visitorFeedback.php`, `systemSettings.php`
- **Mobile/Visitor** (`mobileScreen/`): `explore.php`, `rooms.php`, `panorama.php`, `feedback.php`

**Key data flows:**
- Office QR scan → `explore.php?office_id={id}` → extracts floor from office location → loads correct SVG floor → displays office details + floor map
- Panorama QR scan → `explore.php?scanned_panorama=path_id:{id}_point:{idx}_floor:{n}` → opens panorama viewer
- Floor plan editing → drag/drop offices to SVG rooms → `floorjs/savePositions.php` updates `offices.location`
- Pathfinding → `pathfinding.js` loads `floor_graph.json` / `floor_graph_2.json` / `floor_graph_3.json` → A* routing with stair transitions

## Essential Entry Points

**Database:** `connect_db.php` — PDO connection as `$connect` (use prepared statements always)
**Auth:** `auth_guard.php` — token-based auth for admin pages; include at top with `require_once 'auth_guard.php';`
**CSRF:** `csrfToken()` generates tokens, `validateCSRFToken($token)` validates; use in all admin POST endpoints
**SVG floors:** `SVG/Capitol_{1st|2nd|3rd}_floor_layout_*.svg` — element IDs like `room-{number}-{floor}` must match DB `offices.location` exactly
**Navigation graphs:** `floor_graph.json` (floor 1), `floor_graph_2.json` (floor 2), `floor_graph_3.json` (floor 3) — JSON with `rooms`, `walkablePaths`, `pathAccessRules`, `stairGroups`

## Critical Conventions

**Database patterns:**
```php
include 'connect_db.php'; // Sets $connect
$stmt = $connect->prepare("SELECT * FROM offices WHERE id = ?");
$stmt->execute([$office_id]);
$office = $stmt->fetch(PDO::FETCH_ASSOC);
```

**File paths:**
- Admin files: root directory, include `connect_db.php` directly
- Mobile files: `mobileScreen/`, include with `__DIR__ . '/../connect_db.php'`
- Multi-floor support: use `__DIR__` for relative paths in subdirectories

**SVG room mapping:**
- SVG elements: `<path id="room-101-1">` (room 101, floor 1), `<text id="roomlabel-101">`
- Database: `offices.location = 'room-101-1'` (must match exactly)
- Multi-floor: `room-{number}-{floor}` pattern (e.g., `room-201-2` for room 201 on floor 2)

**API endpoint pattern:**
```php
$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'upload': handleUpload(); break;
    case 'delete': handleDelete(); break;
    default: throw new Exception('Invalid action');
}
```
Examples: `panorama_api.php`, `qr_api.php`, `feedback_management_api.php`

**Floor graph structure:**
```json
{
  "rooms": { "room-101-1": { "type": "office", "x": 100, "y": 200 } },
  "walkablePaths": [ { "id": "path1", "pathPoints": [...] } ],
  "pathAccessRules": { "path1": { "transitionStairKeys": ["west"], "enforceTransitions": true } },
  "stairGroups": { "west_1": { "floor": 1, "stairKey": "west" } }
}
```

## Developer Workflows

**Local setup:**
1. XAMPP required (Apache + MySQL)
2. Clone to `htdocs/FinalDev/`
3. Update `connect_db.php` with local MySQL credentials
4. Import `admin (5).sql` to create schema
5. Access via `http://localhost/FinalDev/login.php`

**QR code regeneration:**
```bash
php generate_qrcodes.php              # Office QR codes
php update_panorama_qr_urls.php       # Panorama QR codes
php regenerate_all_panorama_qr.php    # Bulk panorama QR update
```

**Testing auth system:**
```bash
php test_auth.php  # Validates auth_guard.php integration across admin pages
```

## Project-Specific Gotchas

**SVG ID mismatches:** If office doesn't appear on floor plan, inspect SVG element IDs in browser devtools — must match `offices.location` exactly (case-sensitive, including floor suffix)

**Floor transitions:** Cross-floor routing requires stair groups in `floor_graph.json`. See `STAIR_EXCLUSIVITY_GUIDE.md` for stair key enforcement rules.

**Panorama hotspots:** Stored in `panorama_hotspots` table with JSON `hotspot_data`. Editor: `panorama_viewer_photosphere.php`. Icons animate via CSS in `animated_hotspot_icons/`. Saving with zero hotspots is allowed — this clears all hotspots from the database.

**Office QR floor detection:** `explore.php` automatically detects office floor from location string (`room-205-2` → floor 2) and loads correct SVG. Priority: panorama QR > office QR > default floor 1.

**Drawer height management:** Mobile drawer in `explore.php` enforces 40% minimum viewport height for map visibility. Height adjustments delayed until after drawer animation completes (250ms) to prevent SVG container collapse. Always call `svgPanZoomInstance.resize()` after layout changes.

**SVG transform preservation:** During drawer interactions, set `window.isDrawerInteracting = true` to prevent resize handler from calling `fit()`/`center()` which reset user's zoom/pan. Only call `resize()` to update dimensions without resetting view. Clear flag after animation completes (300ms).

**CSRF validation:** Admin POST endpoints must validate token:
```php
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    die(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
}
```

**Session handling:** `auth_guard.php` auto-regenerates session IDs every 15 min; session timeout is 30 min inactivity.

**Multi-floor caching:** `pathfinding.js` caches floor graphs in `floorGraphCache` by floor number. Clear cache when updating graph JSON files.

**QR scan pathfinding:** When office QR scanned, `window.scannedStartOffice` stores office as permanent default start location. Clicking another room auto-opens pathfinding modal with scanned office as start and clicked room as destination. User can override start location via dropdown.

**Search functionality:** Search bar next to floor selector dynamically filters offices by name, details, services, or contact. Results show with highlighted keywords (yellow background). Clicking result switches floor if needed, highlights office, opens drawer, and pans to location. 300ms debounce prevents excessive searches.

## Key Files to Read

**Auth system:** `auth_guard.php`, `ADMIN_AUTH_DOCUMENTATION.md`
**Floor plan editing:** `floorPlan.php`, `floorjs/dragDropSetup.js`, `floorjs/labelSetup.js`, `floorjs/savePositions.php`
**Pathfinding:** `pathfinding.js`, `NAVIGATION_CONFIG_GUIDE.md`, `STAIR_EXCLUSIVITY_GUIDE.md`
**Panoramas:** `panorama_api.php`, `panorama_viewer_photosphere.php`, `MOBILE_PHOTOSPHERE_INTEGRATION.md`
**QR system:** `generate_qrcodes.php`, `qr_api.php`, `panorama_qr_api.php`
**Search feature:** `mobileScreen/explore.php` (lines ~5188-5390), `SEARCH_FEATURE_GUIDE.md`
**Mobile interface:** `mobileScreen/explore.php`, `mobileScreen/rooms.php`
**Feedback system:** `feedback_management_api.php`, `FEEDBACK_COMPLETE_FIX.md`

## Maintenance & Fix Scripts

Before making structural changes, check for existing documentation:
- `fix_*.php` — Database schema fixes and migrations
- `*_GUIDE.md` / `*_DOCUMENTATION.md` — Feature implementation guides
- `*_FIX.md` / `*_SUMMARY.md` — Bug fix documentation with context

Examples: `HOTSPOT_PERSISTENCE_FIX.md`, `ROUTE_PATH_VISIBILITY_FIX.md`, `VIRTUAL_DOORPOINTS_SUMMARY.md`, `OFFICE_QR_SVG_LOAD_FIX.md`, `PANORAMA_EMPTY_SAVE_FIX.md`, `DRAWER_SVG_DISAPPEAR_FIX.md`, `SVG_TRANSFORM_RESET_FIX.md`, `SVG_WHITE_SCREEN_FIX.md`, `QR_SCAN_PATHFINDING_INTEGRATION.md`, `SEARCH_FEATURE_GUIDE.md`

## Quick Debugging Checklist

- **DB connection fails:** Check `connect_db.php` credentials match MySQL
- **Office not showing on map:** Verify `offices.location` matches SVG `id` exactly
- **Route not found:** Check `floor_graph.json` has `walkablePaths` connecting rooms
- **White screen on QR scan:** Ensure SVG container has explicit display/visibility/height before `loadFloorMap()` call
- **Panorama won't load:** Verify `Pano/` directory has image file, check `panorama_images` table
- **QR scan fails:** Check QR URL base path in `generate_qrcodes.php` → `getPanoramaBaseUrl()`
- **SVG disappears on drawer open:** Check `adjustMainContentHeight()` enforces 40% minimum, verify `svgPanZoomInstance.resize()` called after height changes
- **SVG offset after QR scan:** Ensure `fit()` and `center()` called after drawer opens to re-center SVG in reduced viewport
- **Pathfinding not auto-opening:** Verify `window.scannedStartOffice` set and clicked room differs from scanned room
- **Search not returning results:** Check `officesData` array populated, verify office has `location` field, check console for errors
- **Auth redirect loop:** Clear browser cookies, check session storage in browser devtools

## Database Schema Notes

**Core tables:** `offices`, `admin`, `feedback`, `activities`, `office_hours`, `floor_plan`
**Panorama:** `panorama_images`, `panorama_hotspots`, `panorama_qrcodes`, `panorama_qr_scans`
**QR tracking:** `qrcode_info`, `qr_scan_logs`
**Navigation:** `nav_path` (legacy), graphs stored in JSON files
**Geofencing:** `geofence_access_logs` (location-based access control)

## When You Need More Info

This guide prioritizes discoverable patterns and architectural decisions. For detailed subsystem docs, see:
- Feedback archival: `FEEDBACK_ARCHIVE_DELETE_SYSTEM.md`
- Cross-floor routing: `CROSS_FLOOR_RESTRICTED_ACCESS.md`
- Animated hotspots: `ANIMATED_HOTSPOT_GUIDE.md`
- Panorama tours: `PANORAMA_TOUR_GUIDE.md`

Ask if you need expansion on: DB schema details, specific API endpoints, pathfinding algorithm internals, or step-by-step feature workflows.