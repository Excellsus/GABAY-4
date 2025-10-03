// Test script to verify stair variant logic
// This demonstrates how the pathfinding should work with the new variant enforcement

const STAIR_ID_PATTERN = /^stair_([^_]+)_(\d+)-(\d+)$/i;

function parseStairId(roomId) {
    if (!roomId) return null;
    const match = STAIR_ID_PATTERN.exec(roomId);
    if (!match) return null;
    return {
        key: match[1],
        variant: match[2],
        floor: parseInt(match[3], 10)
    };
}

// Test cases
const testCases = [
    'stair_west_1-1',
    'stair_west_2-1',
    'stair_west_1-2',
    'stair_west_2-2',
    'stair_master_1-2',
    'stair_east_1-3'
];

console.log('Stair ID Parsing Test:\n');
testCases.forEach(stairId => {
    const parsed = parseStairId(stairId);
    console.log(`${stairId} => key: "${parsed.key}", variant: "${parsed.variant}", floor: ${parsed.floor}`);
});

console.log('\n\nScenario: Room 3 (Floor 1, path1) to Room 7 (Floor 1, path1)');
console.log('Expected behavior:');
console.log('1. Identify that path1 requires west stair variant "1"');
console.log('2. Use stair_west_1-1 to go to Floor 2');
console.log('3. Use stair_west_1-2 on Floor 2 (same variant "1")');
console.log('4. Return via stair_west_1-2 back to Floor 1');
console.log('5. Complete route on path1 to Room 7');
console.log('\nIncorrect behavior (FIXED):');
console.log('❌ Should NOT use stair_west_2-2 (variant "2") on Floor 2');
console.log('❌ Should NOT default to a different variant');
