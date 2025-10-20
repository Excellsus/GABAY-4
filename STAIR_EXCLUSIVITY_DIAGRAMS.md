# Stair Exclusivity - Visual Flow Diagrams

## Before Fix (Incorrect Behavior)

```
FLOOR 1                           FLOOR 2
┌──────────────────┐             ┌──────────────────┐
│                  │             │                  │
│  room-1-1        │             │  room-3-2        │
│  (on path1)      │             │  (destination)   │
│                  │             │                  │
└────────┬─────────┘             └─────────┬────────┘
         │                                 │
         │ Navigate to stair               │ Wrong stair selected!
         ▼                                 │
┌──────────────────┐                      │
│ stair_west_1-1   │                      │
│ variant: "1"     │                      │
│ group: west_1    │                      │
│ path: path1      │                      │
└────────┬─────────┘                      │
         │                                 │
         │ Cross floor                     │
         │ transition                      │
         ▼                                 ▼
         ❌ PROBLEM: System chose wrong variant!
         │                         ┌──────────────────┐
         │                         │ stair_west_2-2   │
         │                         │ variant: "2" ❌  │
         └────────────────────────►│ group: (missing) │
                                   │ path: path3_f2   │
                                   └────────┬─────────┘
                                            │
                                            ▼
                                      (Breaks exclusivity
                                       requirement!)
```

## After Fix (Correct Behavior)

```
FLOOR 1                           FLOOR 2
┌──────────────────┐             ┌──────────────────┐
│                  │             │                  │
│  room-1-1        │             │  room-3-2        │
│  (on path1)      │             │  (destination)   │
│                  │             │                  │
└────────┬─────────┘             └─────────┬────────┘
         │                                 │
         │ Navigate to stair               │ Continues to destination
         ▼                                 │
┌──────────────────┐                      │
│ stair_west_1-1   │                      │
│ variant: "1"     │                      │
│ group: west_1 ✅ │                      │
│ path: path1      │                      │
└────────┬─────────┘                      │
         │                                 │
         │ Cross floor                     │
         │ transition                      │
         │ (matches variant!)              │
         ▼                                 ▼
         ✅ SOLUTION: Correct variant matched!
         │                         ┌──────────────────┐
         │                         │ stair_west_1-2   │
         └────────────────────────►│ variant: "1" ✅  │
                                   │ group: west_1 ✅ │
                                   │ path: path1_f2   │
                                   └────────┬─────────┘
                                            │
                                            ▼
                                   (Maintains exclusivity
                                    throughout route!)
```

## Stair Compatibility Matrix

### Floor 1 → Floor 2 Compatibility

| Floor 1 Stair | Compatible Floor 2 Stair | Incompatible Floor 2 Stair |
|---------------|--------------------------|----------------------------|
| stair_west_1-1 (variant 1) | ✅ stair_west_1-2 (variant 1) | ❌ stair_west_2-2 (variant 2) |
| stair_west_2-1 (variant 2) | ✅ stair_west_2-2 (variant 2) | ❌ stair_west_1-2 (variant 1) |
| stair_master_1-1 (variant 1) | ✅ stair_master_1-2 (variant 1) | ❌ stair_master_2-2 (variant 2) |
| stair_master_2-1 (variant 2) | ✅ stair_master_2-2 (variant 2) | ❌ stair_master_1-2 (variant 1) |
| stair_east_1-1 (variant 1) | ✅ stair_east_1-2 (variant 1) | ❌ stair_east_2-2 (variant 2) |
| stair_east_2-1 (variant 2) | ✅ stair_east_2-2 (variant 2) | ❌ stair_east_1-2 (variant 1) |

### Matching Rules

```
┌─────────────────────────────────────────────────────────┐
│ Stair Compatibility Check                               │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  1. Check stairGroup property                           │
│     ├─ Both have stairGroup?                            │
│     │  └─ YES → Must match exactly                      │
│     │           (west_1 ≠ west_2)                       │
│     │                                                    │
│     └─ Only one has stairGroup?                         │
│        └─ Parse room ID for variant                     │
│           └─ stairKey AND variant must match            │
│                                                          │
│  2. Parse stair ID format                               │
│     ├─ Format: stair_{key}_{variant}-{floor}           │
│     │  Example: stair_west_1-1                          │
│     │           └─ key: "west"                          │
│     │           └─ variant: "1"                         │
│     │           └─ floor: "1"                           │
│     │                                                    │
│     └─ Comparison:                                      │
│        ├─ stair_west_1-1 vs stair_west_1-2 → ✅ Match  │
│        └─ stair_west_1-1 vs stair_west_2-2 → ❌ Fail   │
│                                                          │
│  3. Fallback check                                      │
│     └─ Extract variant from room ID pattern             │
│        └─ If can't determine → ❌ Reject (conservative) │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## Path Access Rule Flow

```
┌─────────────────────────────────────────────────────────────┐
│ Route Calculation for room-1-1 → room-3-2                  │
└─────────────────────────────────────────────────────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │ 1. Identify start room's path │
        │    room-1-1 → path1           │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │ 2. Check path access rules    │
        │    path1: {                   │
        │      transitionStairKeys: [   │
        │        "west"                 │
        │      ],                       │
        │      enforceTransitions: true │
        │    }                          │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │ 3. Get required variant       │
        │    Find stairs on path1       │
        │    with stairKey "west"       │
        │    → stair_west_1-1           │
        │    Extract variant: "1"       │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │ 4. Filter floor transitions   │
        │    Check all Floor 1→2 stairs │
        │    ├─ stair_west_1-1 →        │
        │    │  stair_west_1-2 ✅       │
        │    │  (variant "1" matches)   │
        │    │                          │
        │    └─ stair_west_1-1 →        │
        │       stair_west_2-2 ❌       │
        │       (variant "2" rejected)  │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │ 5. Build route                │
        │    ├─ Floor 1: room-1-1 →     │
        │    │           stair_west_1-1 │
        │    │                          │
        │    ├─ Transition: West Stair  │
        │    │   (Floor 1 → Floor 2)    │
        │    │                          │
        │    └─ Floor 2: stair_west_1-2 │
        │               → room-3-2      │
        └───────────────────────────────┘
```

## Logging Flow

### Console Output Example (Successful Route)

```javascript
// Step 1: Constraint detection
Multi-floor pathfinding constraints: {
  startRoomId: "room-1-1",
  endRoomId: "room-3-2",
  startPathId: "path1",
  endPathId: "path3_floor2",
  constrainedStairKeys: ["west"],
  requiredStartVariant: {
    stairKey: "west",
    variant: "1"
  },
  requiredEndVariant: null
}

// Step 2: Transition evaluation
Accepting transition stair_west_1-1 -> stair_west_1-2

// Step 3: Route calculated
✅ Route calculated successfully
   Type: multi-floor
   Floors: [1, 2]
   Stair: West Stair
   Total Distance: 150 units
```

### Console Output Example (Blocked Invalid Route)

```javascript
// Step 1: Constraint detection
Multi-floor pathfinding constraints: {
  startRoomId: "room-1-1",
  endRoomId: "room-3-2",
  constrainedStairKeys: ["west"],
  requiredStartVariant: {
    stairKey: "west",
    variant: "1"
  }
}

// Step 2: Transition rejection
Rejecting transition stair_west_2-2: variant "2" doesn't match required "1"

// Step 3: Error
❌ No allowable transitions remain after applying path access constraints
   startPathId: "path1"
   endPathId: "path3_floor2"
```

## Architecture Layers

```
┌─────────────────────────────────────────────────────────┐
│                    USER INTERACTION                     │
│  (Click room-1-1, then click room-3-2)                  │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              ROUTE CALCULATION LAYER                    │
│  calculateMultiFloorRoute(startRoomId, endRoomId)       │
│  ├─ Detect floor numbers                                │
│  ├─ Load floor graphs                                   │
│  ├─ Identify path access rules                          │
│  └─ Get required stair variants                         │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│           STAIR TRANSITION FINDER LAYER                 │
│  findStairTransitionsBetweenFloors(floor1, floor2)      │
│  ├─ Get all stair nodes on both floors                  │
│  ├─ Check compatibility for each pair                   │
│  └─ Filter by variant requirements                      │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│          STAIR COMPATIBILITY CHECK LAYER                │
│  areStairNodesCompatible(nodeA, nodeB)                  │
│  ├─ Check stairGroup match (Priority 1)                 │
│  ├─ Parse and check variant (Priority 2)                │
│  └─ Conservative rejection if uncertain                 │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│               DATA STRUCTURE LAYER                      │
│  Floor Graph JSON Files                                 │
│  ├─ rooms: { stairGroup, doorPoints, ... }              │
│  ├─ pathAccessRules: { transitionStairKeys, ... }       │
│  └─ walkablePaths: [ ... ]                              │
└─────────────────────────────────────────────────────────┘
```

## Summary

### Key Changes

1. ✅ **Added stairGroup to Floor 2** - Enables precise stair identification
2. ✅ **Enhanced compatibility logic** - Enforces strict variant matching
3. ✅ **Improved filtering** - Validates ALL transition steps, not just endpoints
4. ✅ **Added logging** - Comprehensive debugging information

### Result

- **Before**: stair_west_1-1 could incorrectly route to stair_west_2-2
- **After**: stair_west_1-1 ONLY routes to stair_west_1-2
- **Benefit**: Consistent stair exclusivity across all floors
