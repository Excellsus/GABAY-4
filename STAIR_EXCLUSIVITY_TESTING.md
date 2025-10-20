# Stair Exclusivity Testing Checklist

## Quick Test Commands

Open your browser console when testing the floor plan to see detailed pathfinding logs.

## Test Cases

### ✅ Test 1: Path1 Exclusive Routing (Primary Fix)
**Scenario:** Room on path1 (Floor 1) to any room on Floor 2

**Steps:**
1. Click `room-1-1` on Floor 1 (or `room-2-1`, `room-3-1`, `room-4-1`)
2. Click any room on Floor 2 (e.g., `room-3-2`, `room-1-2`)
3. Observe the route

**Expected Result:**
- Route uses `stair_west_1-1` on Floor 1
- Route uses `stair_west_1-2` on Floor 2 (NOT `stair_west_2-2`)
- Console shows: `Accepting transition stair_west_1-1 -> stair_west_1-2`

**Failure Indicators:**
- Route shows `stair_west_2-2` on Floor 2
- Console shows: `Rejecting transition` messages
- Error: "No allowable transitions remain"

---

### ✅ Test 2: Path2 General Routing (Unrestricted)
**Scenario:** Rooms on path2 should use any available stair

**Steps:**
1. Click `room-12-1` on Floor 1 (on path2, has multiple doors)
2. Click `room-12-2` on Floor 2
3. Observe the route

**Expected Result:**
- Route uses optimal stair (likely `stair_master_1-1` or `stair_east_1-1`)
- No variant restrictions applied
- Console shows: `Multi-floor pathfinding constraints: { constrainedStairKeys: [] }`

---

### ✅ Test 3: Reverse Direction (Floor 2 → Floor 1)
**Scenario:** Ensure variant matching works in reverse

**Steps:**
1. Click `room-3-2` on Floor 2
2. Click `room-1-1` on Floor 1 (on path1)
3. Observe the route

**Expected Result:**
- Route uses `stair_west_1-2` on Floor 2
- Route uses `stair_west_1-1` on Floor 1
- Console shows variant consistency

---

### ✅ Test 4: Same Floor (No Cross-Floor)
**Scenario:** Ensure same-floor routing still works

**Steps:**
1. Click `room-1-1` on Floor 1
2. Click `room-2-1` on Floor 1
3. Observe the route

**Expected Result:**
- Route stays on Floor 1
- Uses path1 walkable paths
- No stair transitions involved
- Console shows: `type: 'single-floor'`

---

### ✅ Test 5: East Stair Variants
**Scenario:** Test east stair variant matching

**Steps:**
1. Click `room-14-1` on Floor 1 (near `stair_east_2-1`)
2. Click `room-21-2` on Floor 2 (near `stair_east_2-2`)
3. Observe the route

**Expected Result:**
- Route uses matching east stair variants
- If path access rules apply, enforces variant matching

---

### ✅ Test 6: Central Stair Variants
**Scenario:** Test central stair variant matching

**Steps:**
1. Click `room-12-1` on Floor 1
2. Click `room-11-2` on Floor 2
3. Observe the route

**Expected Result:**
- Route uses central stairs
- Variants match between floors (master_1 ↔ master_1 or master_2 ↔ master_2)

---

## Console Log Checklist

When running tests, verify these console outputs:

### ✅ Constraint Detection
```javascript
Multi-floor pathfinding constraints: {
  startRoomId: "room-1-1",
  endRoomId: "room-3-2",
  startPathId: "path1",
  endPathId: "path3_floor2",
  constrainedStairKeys: ["west"],
  requiredStartVariant: { stairKey: "west", variant: "1" },
  requiredEndVariant: null
}
```

### ✅ Transition Acceptance
```javascript
Accepting transition stair_west_1-1 -> stair_west_1-2
```

### ✅ Transition Rejection (if trying wrong variant)
```javascript
Rejecting transition stair_west_2-2: variant "2" doesn't match required "1"
```

### ✅ Stair Compatibility
```javascript
// Should NOT see warnings about incompatible stairs when using correct variants
// If you see this, there's an issue:
Could not determine stair variant for compatibility check
```

---

## Visual Verification

### On Floor Plan:
1. **Path highlights** should be continuous and correct
2. **Stair markers** should show the correct stair being used
3. **Route instructions panel** should show:
   - "Floor 1: Proceed to West Stair"
   - "Take West Stair to Floor 2"
   - "Floor 2: Continue to [destination]"

### Route Instructions Should Show:
```
Route from room-1-1 to room-3-2
Total Distance: [X] units

1. Floor 1: Proceed to West Stair
   Distance: [X] units

2. Take West Stair to Floor 2

3. Floor 2: Continue to room-3-2
   Distance: [X] units
```

---

## Known Issues to Watch For

### ❌ Issue: Route uses wrong stair variant
**Symptom:** Route shows `stair_west_2-2` when starting from path1 rooms
**Cause:** `stairGroup` mismatch or compatibility logic failure
**Fix:** Check console for rejection messages, verify `stairGroup` in JSON

### ❌ Issue: No route found
**Symptom:** "No allowable transitions remain" error
**Cause:** Too strict filtering or missing stair connections
**Fix:** Check `connectsTo` arrays, verify stairs actually connect floors

### ❌ Issue: Stair compatibility warnings
**Symptom:** Console shows "Could not determine stair variant"
**Cause:** Malformed room IDs or missing stairGroup
**Fix:** Verify room ID format: `stair_{key}_{variant}-{floor}`

---

## Regression Tests

After any changes to pathfinding or floor graphs:

1. ✅ Run all 6 test cases above
2. ✅ Check console for unexpected errors
3. ✅ Verify path rendering is smooth and correct
4. ✅ Test both directions (Floor 1→2 and Floor 2→1)
5. ✅ Test all stair types (west, central, east)
6. ✅ Test restricted AND unrestricted paths

---

## Performance Check

- Route calculation should complete in < 100ms
- No infinite loops or excessive recursion
- Console should not show repeated rejection/acceptance messages for same transition

---

## Success Criteria

✅ **All tests pass** when:
1. Path1 rooms consistently use stair variant 1
2. Path2 rooms can use any optimal stair
3. Cross-floor routes maintain variant consistency
4. Same-floor routes work without issues
5. Console logs show correct constraint detection
6. No compatibility warnings appear
7. Visual rendering is accurate

---

## Quick Debug Commands

Paste these in browser console to check state:

```javascript
// Check current floor graph cache
console.log(floorGraphCache);

// Check active route
console.log(activeRoute);

// Check stair nodes for a specific floor
console.log(floorGraphCache[1].stairNodes);
console.log(floorGraphCache[2].stairNodes);

// Manually test stair compatibility
const floor1Graph = floorGraphCache[1];
const floor2Graph = floorGraphCache[2];
const westStair1_1 = floor1Graph.stairNodes.find(n => n.roomId === 'stair_west_1-1');
const westStair1_2 = floor2Graph.stairNodes.find(n => n.roomId === 'stair_west_1-2');
const westStair2_2 = floor2Graph.stairNodes.find(n => n.roomId === 'stair_west_2-2');

console.log('westStair1_1 ↔ westStair1_2 compatible?', areStairNodesCompatible(westStair1_1, westStair1_2)); // Should be TRUE
console.log('westStair1_1 ↔ westStair2_2 compatible?', areStairNodesCompatible(westStair1_1, westStair2_2)); // Should be FALSE
```
