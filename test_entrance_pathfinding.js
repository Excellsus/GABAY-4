/**
 * Entrance Pathfinding Verification Script
 * 
 * Run this in browser console after loading explore.php to verify
 * that entrances are properly converted to virtual rooms and work
 * identically to rooms with doorPoints.
 * 
 * Usage:
 * 1. Open explore.php in browser
 * 2. Open console (F12)
 * 3. Copy and paste this entire script
 * 4. Press Enter
 * 5. Check results
 */

(async function verifyEntrancePathfinding() {
  console.log('='.repeat(80));
  console.log('ENTRANCE PATHFINDING VERIFICATION');
  console.log('='.repeat(80));
  
  const results = {
    graphLoading: { passed: 0, failed: 0, tests: [] },
    virtualRoomConversion: { passed: 0, failed: 0, tests: [] },
    pathfinding: { passed: 0, failed: 0, tests: [] },
    pathAccessRules: { passed: 0, failed: 0, tests: [] }
  };
  
  function pass(category, test, message) {
    results[category].passed++;
    results[category].tests.push({ status: '✅', test, message });
    console.log(`✅ ${test}: ${message}`);
  }
  
  function fail(category, test, message) {
    results[category].failed++;
    results[category].tests.push({ status: '❌', test, message });
    console.error(`❌ ${test}: ${message}`);
  }
  
  // TEST 1: Floor Graph Loading
  console.log('\n📦 TEST 1: Floor Graph Loading');
  console.log('-'.repeat(80));
  
  try {
    await ensureFloorGraphLoaded(1);
    const floor1 = floorGraphCache[1];
    
    if (floor1 && floor1.rooms) {
      pass('graphLoading', 'Floor 1 loaded', `Graph has ${Object.keys(floor1.rooms).length} rooms`);
    } else {
      fail('graphLoading', 'Floor 1 loaded', 'Graph missing rooms object');
    }
    
    if (Array.isArray(floor1.entrances)) {
      pass('graphLoading', 'Entrances array exists', `Found ${floor1.entrances.length} entrance definitions`);
    } else {
      fail('graphLoading', 'Entrances array exists', 'No entrances array in floor graph');
    }
  } catch (error) {
    fail('graphLoading', 'Floor 1 loaded', error.message);
  }
  
  try {
    await ensureFloorGraphLoaded(2);
    pass('graphLoading', 'Floor 2 loaded', 'Successfully loaded Floor 2 graph');
  } catch (error) {
    fail('graphLoading', 'Floor 2 loaded', error.message);
  }
  
  try {
    await ensureFloorGraphLoaded(3);
    pass('graphLoading', 'Floor 3 loaded', 'Successfully loaded Floor 3 graph');
  } catch (error) {
    fail('graphLoading', 'Floor 3 loaded', error.message);
  }
  
  // TEST 2: Virtual Room Conversion
  console.log('\n🏠 TEST 2: Virtual Room Conversion');
  console.log('-'.repeat(80));
  
  const floor1 = floorGraphCache[1];
  const floor2 = floorGraphCache[2];
  const floor3 = floorGraphCache[3];
  
  // Check Floor 1 entrances
  const entrances1 = ['entrance_main_1', 'entrance_west_1', 'entrance_east_1'];
  entrances1.forEach(entranceId => {
    if (floor1.rooms[entranceId]) {
      const room = floor1.rooms[entranceId];
      
      if (room.type === 'entrance') {
        pass('virtualRoomConversion', `${entranceId} type`, 'type === "entrance"');
      } else {
        fail('virtualRoomConversion', `${entranceId} type`, `type is "${room.type}", expected "entrance"`);
      }
      
      if (room.doorPoints && room.doorPoints.length === 1) {
        pass('virtualRoomConversion', `${entranceId} doorPoints`, 'Has single doorPoint');
      } else {
        fail('virtualRoomConversion', `${entranceId} doorPoints`, 'Missing doorPoints array');
      }
      
      if (room.entryPoints && room.entryPoints.length === 1) {
        pass('virtualRoomConversion', `${entranceId} entryPoints`, 'Has single entryPoint');
      } else {
        fail('virtualRoomConversion', `${entranceId} entryPoints`, 'Missing entryPoints array');
      }
      
      if (room.nearestPathId) {
        pass('virtualRoomConversion', `${entranceId} nearestPathId`, `Path: ${room.nearestPathId}`);
      } else {
        fail('virtualRoomConversion', `${entranceId} nearestPathId`, 'Missing nearestPathId');
      }
    } else {
      fail('virtualRoomConversion', `${entranceId} exists`, 'Entrance not found in rooms object');
    }
  });
  
  // Check Floor 2 entrances
  if (floor2.rooms['entrance_main_2']) {
    pass('virtualRoomConversion', 'entrance_main_2', 'Floor 2 main entrance converted');
  } else {
    fail('virtualRoomConversion', 'entrance_main_2', 'Floor 2 main entrance missing');
  }
  
  if (floor2.rooms['entrance_west_2']) {
    pass('virtualRoomConversion', 'entrance_west_2', 'Floor 2 west entrance converted');
  } else {
    fail('virtualRoomConversion', 'entrance_west_2', 'Floor 2 west entrance missing');
  }
  
  // Check Floor 3 entrances
  if (floor3.rooms['entrance_main_3']) {
    pass('virtualRoomConversion', 'entrance_main_3', 'Floor 3 main entrance converted');
  } else {
    fail('virtualRoomConversion', 'entrance_main_3', 'Floor 3 main entrance missing');
  }
  
  if (floor3.rooms['entrance_west_3']) {
    pass('virtualRoomConversion', 'entrance_west_3', 'Floor 3 west entrance converted');
  } else {
    fail('virtualRoomConversion', 'entrance_west_3', 'Floor 3 west entrance missing');
  }
  
  // TEST 3: Pathfinding Functions
  console.log('\n🧭 TEST 3: Pathfinding Functions');
  console.log('-'.repeat(80));
  
  try {
    const route1 = await calculateMultiFloorRoute('entrance_main_1', 'room-12-1');
    if (route1 && route1.segments && route1.segments.length > 0) {
      pass('pathfinding', 'Entrance to Room', `${route1.segments.length} segments, ${Math.round(route1.totalDistance)}px distance`);
    } else {
      fail('pathfinding', 'Entrance to Room', 'Route calculation failed');
    }
  } catch (error) {
    fail('pathfinding', 'Entrance to Room', error.message);
  }
  
  try {
    const route2 = await calculateMultiFloorRoute('room-7-1', 'entrance_east_1');
    if (route2 && route2.segments && route2.segments.length > 0) {
      pass('pathfinding', 'Room to Entrance', `${route2.segments.length} segments, ${Math.round(route2.totalDistance)}px distance`);
    } else {
      fail('pathfinding', 'Room to Entrance', 'Route calculation failed');
    }
  } catch (error) {
    fail('pathfinding', 'Room to Entrance', error.message);
  }
  
  try {
    const route3 = await calculateMultiFloorRoute('entrance_main_1', 'entrance_east_1');
    if (route3 && route3.segments && route3.segments.length > 0) {
      pass('pathfinding', 'Entrance to Entrance (same path)', `${route3.segments.length} segments, ${Math.round(route3.totalDistance)}px distance`);
    } else {
      fail('pathfinding', 'Entrance to Entrance (same path)', 'Route calculation failed');
    }
  } catch (error) {
    fail('pathfinding', 'Entrance to Entrance (same path)', error.message);
  }
  
  // Multi-floor tests
  try {
    const route4 = await calculateMultiFloorRoute('entrance_main_1', 'room-12-2');
    if (route4 && route4.segments && route4.segments.length > 0) {
      const hasStairTransition = route4.segments.some(s => s.type === 'stair-transition');
      if (hasStairTransition) {
        pass('pathfinding', 'Multi-floor (Floor 1 → Floor 2)', `Route includes stair transition`);
      } else {
        fail('pathfinding', 'Multi-floor (Floor 1 → Floor 2)', 'No stair transition found');
      }
    } else {
      fail('pathfinding', 'Multi-floor (Floor 1 → Floor 2)', 'Route calculation failed');
    }
  } catch (error) {
    fail('pathfinding', 'Multi-floor (Floor 1 → Floor 2)', error.message);
  }
  
  // TEST 4: Path Access Rules
  console.log('\n🚦 TEST 4: Path Access Rules');
  console.log('-'.repeat(80));
  
  try {
    console.log('Testing West Entrance (path1) → East Entrance (path2)...');
    const route5 = await calculateMultiFloorRoute('entrance_west_1', 'entrance_east_1');
    
    if (route5 && route5.segments && route5.segments.length > 0) {
      // Check if route uses West Stair
      const usesWestStair = route5.segments.some(seg => 
        seg.description && seg.description.toLowerCase().includes('west stair')
      );
      
      if (usesWestStair) {
        pass('pathAccessRules', 'Path exclusivity enforced', 'Route uses West Stair (path1 restriction)');
      } else {
        fail('pathAccessRules', 'Path exclusivity enforced', 'Route did not use West Stair despite path1 restriction');
        console.warn('Route segments:', route5.segments.map(s => s.description));
      }
    } else {
      fail('pathAccessRules', 'Path exclusivity enforced', 'Route calculation failed');
    }
  } catch (error) {
    fail('pathAccessRules', 'Path exclusivity enforced', error.message);
  }
  
  try {
    const westEntrance = floor1.rooms['entrance_west_1'];
    const pathId = getPrimaryPathIdForRoom(westEntrance);
    
    if (pathId === 'path1') {
      pass('pathAccessRules', 'getPrimaryPathIdForRoom', 'Returns correct nearestPathId for entrance');
    } else {
      fail('pathAccessRules', 'getPrimaryPathIdForRoom', `Expected "path1", got "${pathId}"`);
    }
  } catch (error) {
    fail('pathAccessRules', 'getPrimaryPathIdForRoom', error.message);
  }
  
  try {
    const westEntrance = floor1.rooms['entrance_west_1'];
    const entryPoints = getEntryPointsForRoom(westEntrance);
    
    if (entryPoints && entryPoints.length === 1) {
      pass('pathAccessRules', 'getEntryPointsForRoom', 'Returns doorPoints for entrance');
    } else {
      fail('pathAccessRules', 'getEntryPointsForRoom', 'Failed to return doorPoints');
    }
  } catch (error) {
    fail('pathAccessRules', 'getEntryPointsForRoom', error.message);
  }
  
  try {
    const forceTransition = shouldForceStairTransition(floor1, 'path1', 'path2');
    
    if (forceTransition === true) {
      pass('pathAccessRules', 'shouldForceStairTransition', 'Correctly detects path1 → path2 requires stair');
    } else {
      fail('pathAccessRules', 'shouldForceStairTransition', 'Failed to detect path restriction');
    }
  } catch (error) {
    fail('pathAccessRules', 'shouldForceStairTransition', error.message);
  }
  
  // Summary
  console.log('\n' + '='.repeat(80));
  console.log('SUMMARY');
  console.log('='.repeat(80));
  
  const categories = ['graphLoading', 'virtualRoomConversion', 'pathfinding', 'pathAccessRules'];
  let totalPassed = 0;
  let totalFailed = 0;
  
  categories.forEach(cat => {
    const r = results[cat];
    totalPassed += r.passed;
    totalFailed += r.failed;
    console.log(`\n${cat.toUpperCase()}:`);
    console.log(`  ✅ Passed: ${r.passed}`);
    console.log(`  ❌ Failed: ${r.failed}`);
  });
  
  console.log('\n' + '='.repeat(80));
  console.log(`TOTAL: ${totalPassed} passed, ${totalFailed} failed`);
  console.log('='.repeat(80));
  
  if (totalFailed === 0) {
    console.log('\n🎉 ALL TESTS PASSED! Entrance pathfinding is working correctly.');
  } else {
    console.warn('\n⚠️  SOME TESTS FAILED. Review errors above.');
  }
  
  // Return detailed results for further inspection
  return results;
})();
