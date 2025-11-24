# Entrance Pathfinding Implementation Guide

## Overview
This document explains how entrances are integrated into the pathfinding system to function identically to rooms with doorPoints.

## Implementation Summary

### Key Change: Virtual Room Conversion
Entrances are now automatically converted to virtual room objects during floor graph decoration. This allows them to participate in pathfinding using the exact same logic as regular rooms.

### Modified Functions

#### `decorateFloorGraph(data, floor)` - Line ~187
**Purpose:** Converts entrance definitions to virtual room objects

**What it does:**
1. Checks if `graph.entrances` array exists and has entries
2. For each entrance, creates a virtual room object with:
   - `type: 'entrance'` - Identifies this as an entrance room
   - `label` - Entrance display name
   - `nearestPathId` - Path association (from entrance definition)
   - `doorPoints` - Array with entrance coordinates as single door point
   - `entryPoints` - Same as doorPoints for compatibility
   - `entranceData` - Original entrance object for reference

3. Adds virtual room to `graph.rooms` using entrance ID as room ID

**Example:**
```javascript
// Input (floor_graph.json):
"entrances": [
  {
    "id": "entrance_west_1",
    "label": "West Entrance",
    "type": "entrance",
    "floor": 1,
    "x": 70,
    "y": 340,
    "nearestPathId": "path1"
  }
]

// Output (after decoration):
graph.rooms["entrance_west_1"] = {
  type: 'entrance',
  label: 'West Entrance',
  nearestPathId: 'path1',
  doorPoints: [
    { x: 70, y: 340, nearestPathId: 'path1' }
  ],
  entryPoints: [
    { x: 70, y: 340, nearestPathId: 'path1' }
  ],
  entranceData: { /* original entrance object */ }
}
```

## How Entrances Work in Pathfinding

### 1. Room-to-Entrance Navigation
When routing from a room to an entrance (e.g., `room-1-1` → `entrance_west_1`):
- `calculateSingleFloorRoute` treats entrance as a room
- `getEntryPointsForRoom` returns the entrance's doorPoints array
- System finds optimal path from room's doorPoint to entrance's coordinate
- Path respects `nearestPathId` and path access rules

### 2. Entrance-to-Room Navigation
When routing from an entrance to a room (e.g., `entrance_west_1` → `room-5-1`):
- Entrance's doorPoint becomes the starting point
- System uses entrance's `nearestPathId` to determine allowed paths
- Path follows same rules as room-to-room navigation

### 3. Entrance-to-Entrance Navigation
When routing between entrances (e.g., `entrance_west_1` → `entrance_east_1`):
- Both entrances treated as rooms with single doorPoints
- System checks path access rules for both `nearestPathId` values
- If paths have `enforceTransitions: true`, stair transition is enforced
- Example: West Entrance (path1) → East Entrance (path2) requires West Stair

### 4. Multi-Floor with Entrances
Entrances only exist on their defined floor, but work seamlessly with stairs:
- Entrance on Floor 1 → Room on Floor 2: Routes to nearest stair, transitions, continues to destination
- Room on Floor 2 → Entrance on Floor 1: Routes to stair, transitions down, continues to entrance

## Path Access Rules for Entrances

### Example: West Entrance Restriction
```json
"pathAccessRules": {
  "path1": {
    "transitionStairKeys": ["west"],
    "enforceTransitions": true
  }
}

"entrances": [
  {
    "id": "entrance_west_1",
    "nearestPathId": "path1"  // ← This entrance uses restricted path
  }
]
```

**Result:** 
- Routing from `entrance_west_1` (path1) to any room on path2 will enforce West Stair transition
- System automatically applies same stair exclusivity rules as rooms

## Testing Checklist

### Basic Tests
- [ ] Entrance-to-room on same floor
- [ ] Room-to-entrance on same floor
- [ ] Entrance-to-entrance on same floor (same path)
- [ ] Entrance-to-entrance on same floor (different paths)

### Multi-Floor Tests
- [ ] Entrance Floor 1 → Room Floor 2
- [ ] Room Floor 2 → Entrance Floor 1
- [ ] Entrance Floor 1 → Room Floor 3
- [ ] Room Floor 3 → Entrance Floor 1

### Path Access Rule Tests
- [ ] West Entrance (path1) → East Entrance (path2) uses West Stair
- [ ] West Entrance (path1) → Room on path2 uses West Stair
- [ ] Room on path1 → East Entrance (path2) uses West Stair
- [ ] Main Entrance (path2) → any room on path2 (no stair needed)

### Edge Cases
- [ ] Entrance as start point with QR scan
- [ ] Entrance as destination from search
- [ ] Entrance in dropdown selection
- [ ] Entrance highlighted on map

## No Changes Required To:

### Existing Functions (Already Compatible)
- `calculateSingleFloorRoute` - Works with entrance virtual rooms automatically
- `calculateMultiFloorRoute` - Handles entrances like any other room
- `getEntryPointsForRoom` - Returns entrance doorPoints correctly
- `getPrimaryPathIdForRoom` - Returns entrance nearestPathId correctly
- `shouldForceStairTransition` - Checks entrance path rules automatically

### UI Components
- Room selection dropdowns (entrances now in rooms object)
- Map highlighting (entrance IDs work as room IDs)
- Search functionality (entrances searchable as rooms)
- Pathfinding modal (entrances selectable as start/end)

## Database Integration

### Entrance Table Structure (from admin (16).sql)
```sql
CREATE TABLE `entrance_qrcodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrance_id` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `floor` int(11) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `nearest_path_id` varchar(50) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `entrance_id` (`entrance_id`)
)
```

### PHP Integration (explore.php ~line 305)
Entrances are fetched from database and passed to JavaScript:
```php
$stmt_entrances = $connect->query("SELECT entrance_id, label, floor, x, y, nearest_path_id 
    FROM entrance_qrcodes WHERE is_active = 1 ORDER BY floor, entrance_id");
$entrances = $stmt_entrances->fetchAll(PDO::FETCH_ASSOC);
```

JavaScript receives this data and can use entrance IDs directly in pathfinding:
```javascript
calculateRoute('entrance_west_1', 'room-5-1');  // Just works!
```

## Advantages of This Approach

### 1. Zero Code Duplication
- No separate entrance routing logic needed
- No special cases in pathfinding functions
- Single source of truth for path access rules

### 2. Automatic Feature Inheritance
Entrances automatically get:
- Door point filtering (active/inactive doors)
- Restricted access rule support
- Path exclusivity enforcement
- Multi-floor transition handling
- Distance calculation
- Route optimization

### 3. Maintainability
- Changes to room pathfinding automatically apply to entrances
- Bug fixes propagate to all navigation types
- New features work for entrances without modification

### 4. Simplicity
- Entrances are just rooms with `type: 'entrance'`
- Developers can treat them identically to rooms
- No mental overhead for entrance-specific logic

## Logging

Console logs show entrance conversion:
```
[decorateFloorGraph] Converting 3 entrances to virtual rooms on floor 1
[decorateFloorGraph] Created virtual room for entrance: entrance_west_1 
  { x: 70, y: 340, nearestPathId: 'path1' }
```

## Future Enhancements

### Potential Additions (Optional)
1. **Entrance-specific styling** - Use `type: 'entrance'` to apply different map markers
2. **Entrance metadata** - Store opening hours, accessibility info in `entranceData`
3. **Entrance grouping** - Group related entrances (main, side, emergency)
4. **Entrance analytics** - Track which entrances are used most frequently

All of these can leverage the existing virtual room structure without changing pathfinding logic.

## Summary

Entrances now function identically to rooms with doorPoints by:
1. Converting entrance definitions to virtual room objects during graph decoration
2. Giving each entrance a single doorPoint at its coordinates
3. Associating entrances with paths via `nearestPathId`
4. Allowing all existing pathfinding logic to work without modification

**Result:** Seamless navigation to/from/between entrances with full support for path access rules, stair exclusivity, and multi-floor routing.
