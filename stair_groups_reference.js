// Stair Group Quick Reference
// Use this to verify which stairs can connect to each other

const STAIR_GROUPS = {
    // West stairs connecting Floor 1 and 2
    west_1: [
        'stair_west_1-1',  // Floor 1, path1
        'stair_west_1-2'   // Floor 2, path1_floor2
    ],
    west_2: [
        'stair_west_2-1',  // Floor 1, path2
        'stair_west_2-2'   // Floor 2, path_floor2
    ],
    
    // Central/Master stairs connecting Floor 1 and 2
    master_1: [
        'stair_master_1-1', // Floor 1, path2
        'stair_master_1-2'  // Floor 2, lobby_horizontal_1
    ],
    master_2: [
        'stair_master_2-1', // Floor 1, path2
        'stair_master_2-2'  // Floor 2, lobby_vertical_2
    ],
    
    // East stairs - Group 1 (Floor 1 and 2 only)
    east_1: [
        'stair_east_1-1',  // Floor 1, path2
        'stair_east_1-2'   // Floor 2, path10_floor2
    ],
    
    // East stairs - Group 2 (extends to Floor 3)
    east_2: [
        'stair_east_2-1',  // Floor 1, path2
        'stair_east_2-2',  // Floor 2, path18_floor2
        'stair_east_1-3'   // Floor 3, path2_floor3
    ],
    
    // Third floor stairs - Group 1
    thirdFloor_1: [
        'stair_thirdFloor_1-2', // Floor 2, lobby_vertical_2
        'stair_thirdFloor_1-3'  // Floor 3, path3_floor3
    ],
    
    // Third floor stairs - Group 2
    thirdFloor_2: [
        'stair_thirdFloor_2-2', // Floor 2, lobby_vertical_2
        'stair_thirdFloor_2-3'  // Floor 3, path2_floor3
    ]
};

// Validation: Check if two stair IDs can transition
function canStairsConnect(stairA, stairB) {
    for (const [groupId, stairs] of Object.entries(STAIR_GROUPS)) {
        if (stairs.includes(stairA) && stairs.includes(stairB)) {
            return { allowed: true, group: groupId };
        }
    }
    return { allowed: false, group: null };
}

// Test examples
console.log('Stair Group Connection Tests:\n');

// Valid connections
console.log('✅ Valid Connections:');
console.log(canStairsConnect('stair_west_1-1', 'stair_west_1-2'));
// { allowed: true, group: 'west_1' }

console.log(canStairsConnect('stair_east_2-1', 'stair_east_1-3'));
// { allowed: true, group: 'east_2' }

console.log(canStairsConnect('stair_thirdFloor_1-2', 'stair_thirdFloor_1-3'));
// { allowed: true, group: 'thirdFloor_1' }

// Invalid connections
console.log('\n❌ Invalid Connections:');
console.log(canStairsConnect('stair_west_1-1', 'stair_west_2-2'));
// { allowed: false, group: null }

console.log(canStairsConnect('stair_master_1-1', 'stair_master_2-2'));
// { allowed: false, group: null }

console.log(canStairsConnect('stair_east_1-1', 'stair_east_2-2'));
// { allowed: false, group: null }
