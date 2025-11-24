# Entrance Exclusivity Examples

## Example 1: West Path Requires West Entrance
Restrict access to path1 (west corridor) to only allow entry from West Entrance.

**Floor 1 Configuration:**
```json
{
  "entranceAccessRules": {
    "path1": {
      "allowedEntranceKeys": ["entrance_west_1"],
      "enforceEntrances": true
    }
  }
}
```

**Effect:**
- Visitors scanning West Entrance QR can navigate to path1
- Visitors scanning Main or East Entrance cannot use path1
- Alternative routes will be calculated automatically

## Example 2: VIP Area - Main Entrance Only
Restrict access to lobby paths (main entrance area) to only Main Entrance.

**Floor 1 Configuration:**
```json
{
  "entranceAccessRules": {
    "path2": {
      "allowedEntranceKeys": ["entrance_main_1"],
      "enforceEntrances": true
    }
  }
}
```

## Example 3: Multiple Entrances for Shared Path
Allow both Main and East entrances to access a common path.

**Floor 1 Configuration:**
```json
{
  "entranceAccessRules": {
    "path2": {
      "allowedEntranceKeys": ["entrance_main_1", "entrance_east_1"],
      "enforceEntrances": true
    }
  }
}
```

## Example 4: Floor 2 Entrance Restrictions
Apply entrance restrictions on upper floors.

**Floor 2 Configuration:**
```json
{
  "entranceAccessRules": {
    "path1_floor2": {
      "allowedEntranceKeys": ["entrance_west_2"],
      "enforceEntrances": true
    },
    "lobby_vertical_2": {
      "allowedEntranceKeys": ["entrance_main_2"],
      "enforceEntrances": true
    }
  }
}
```

## Example 5: Combined Stair and Entrance Restrictions
Use both stair and entrance exclusivity for maximum control.

**Floor 1 Configuration:**
```json
{
  "pathAccessRules": {
    "path1": {
      "transitionStairKeys": ["west"],
      "enforceTransitions": true
    }
  },
  "entranceAccessRules": {
    "path1": {
      "allowedEntranceKeys": ["entrance_west_1"],
      "enforceEntrances": true
    }
  }
}
```

**Effect:**
- Path1 requires West Entrance AND West Stair
- Creates a completely isolated west wing routing zone
- Prevents cross-contamination with main lobby traffic

## Example 6: Emergency Exit Restrictions
Mark certain paths as emergency-only (no public entrance access).

**Floor 1 Configuration:**
```json
{
  "entranceAccessRules": {
    "emergency_corridor": {
      "allowedEntranceKeys": [],
      "enforceEntrances": true
    }
  }
}
```

**Effect:**
- Empty allowedEntranceKeys array = no public access
- Path only accessible from inside building
- Prevents navigation through emergency exits

## Testing Your Configuration

### Step 1: Add Rules to JSON
```bash
# Edit the floor graph file
code floor_graph.json

# Add your entranceAccessRules
```

### Step 2: Refresh Mobile Interface
```
http://192.168.254.164/gabay/mobileScreen/explore.php
```

### Step 3: Scan Different Entrance QRs
1. Scan West Entrance QR code
2. Try navigating to restricted path
3. Check console for:
```
✅ Entrance entrance_west_1 allowed on path path1
```

### Step 4: Verify Restrictions
1. Scan Main Entrance QR code
2. Try navigating to west-only path
3. Check console for:
```
🚫 Entrance entrance_main_1 not allowed on path path1 - skipping
```

## Common Patterns

### Pattern: Building Wings
```json
{
  "entranceAccessRules": {
    "west_wing_path": {
      "allowedEntranceKeys": ["entrance_west_1"],
      "enforceEntrances": true
    },
    "east_wing_path": {
      "allowedEntranceKeys": ["entrance_east_1"],
      "enforceEntrances": true
    }
  }
}
```

### Pattern: Public vs Private Areas
```json
{
  "entranceAccessRules": {
    "public_lobby": {
      "allowedEntranceKeys": ["entrance_main_1", "entrance_west_1", "entrance_east_1"],
      "enforceEntrances": true
    },
    "private_offices": {
      "allowedEntranceKeys": ["entrance_staff_only"],
      "enforceEntrances": true
    }
  }
}
```

### Pattern: Time-Based Access (Future)
```json
{
  "entranceAccessRules": {
    "after_hours_path": {
      "allowedEntranceKeys": ["entrance_security_1"],
      "enforceEntrances": true,
      "schedule": {
        "start": "18:00",
        "end": "06:00"
      }
    }
  }
}
```
*Note: Schedule support not yet implemented*

## Debugging Tips

### Enable Detailed Logging
```javascript
// In browser console
localStorage.setItem('pathfindingDebug', 'true');
```

### Check Entrance Data
```javascript
// After scanning entrance QR
console.log(window.scannedStartEntrance);
```

### Verify Rule Loading
```javascript
// After floor loads
console.log(window.floorGraph.entranceAccessRules);
```

### Test Without Restrictions
```json
{
  "entranceAccessRules": {
    "path1": {
      "allowedEntranceKeys": ["entrance_west_1"],
      "enforceEntrances": false  // Temporarily disable
    }
  }
}
```

## Performance Considerations

### Rule Evaluation Cost
- Entrance checks add ~1ms per path combination
- Negligible impact for typical floor layouts (10-20 paths)
- Consider reducing path combinations if >100 paths

### Caching Strategy
- Entrance access rules cached with floor graph
- No database lookup per route calculation
- Rules reload only on floor switch

## Best Practices

1. **Start Simple**: Begin with 1-2 entrance restrictions, expand gradually
2. **Test Thoroughly**: Verify each entrance QR can reach essential rooms
3. **Document Rules**: Add comments to JSON explaining restriction rationale
4. **Provide Alternatives**: Ensure at least one valid route exists for each room
5. **Monitor Analytics**: Track entrance usage to optimize restrictions

## Migration Guide

### From No Restrictions to Entrance Exclusivity
1. Identify current traffic patterns
2. Map entrances to logical building zones
3. Add empty `entranceAccessRules` object
4. Enable one restriction at a time
5. Monitor user navigation patterns
6. Adjust rules based on feedback

### Removing Restrictions
1. Set `enforceEntrances: false` to test impact
2. Remove entire rule object if no longer needed
3. Keep empty `entranceAccessRules: {}` for future use
