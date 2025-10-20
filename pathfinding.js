let floorGraph = {};
let selectedRooms = [];
let pathResult = [];

// Cached floor graphs keyed by floor number to support multi-floor routing
const floorGraphCache = {};
let currentFloorNumber = 1;
let activeRoute = null;
let routeInstructionsPanel = null;

const ROUTE_EVENT_NAME = 'routeCalculated';

const FLOOR_GRAPH_FILENAMES = {
    1: 'floor_graph.json',
    2: 'floor_graph_2.json',
    3: 'floor_graph_3.json'
};

const STAIR_NAME_MAP = {
    west: 'West Stair',
    central: 'Central Stair',
    east: 'East Stair'
};

const STAIR_ID_PATTERN = /^stair_([^_]+)_(\d+)-(\d+)$/i;

function getPathIdBase(pathId) {
    if (!pathId) return null;
    const underscoreIndex = pathId.indexOf('_');
    return underscoreIndex === -1 ? pathId : pathId.substring(0, underscoreIndex);
}

function getPathAccessRule(graph, pathId) {
    if (!graph || !graph.pathAccessRules || !pathId) {
        return null;
    }
    if (graph.pathAccessRules[pathId]) {
        return graph.pathAccessRules[pathId];
    }
    const baseId = getPathIdBase(pathId);
    if (baseId && graph.pathAccessRules[baseId]) {
        return graph.pathAccessRules[baseId];
    }
    return null;
}

function getAllowedTransitionStairKeys(graph, pathId) {
    const rule = getPathAccessRule(graph, pathId);
    if (!rule) return null;
    if (Array.isArray(rule.transitionStairKeys) && rule.transitionStairKeys.length) {
        return [...rule.transitionStairKeys];
    }
    if (Array.isArray(rule.allowedStairKeys) && rule.allowedStairKeys.length) {
        return [...rule.allowedStairKeys];
    }
    return null;
}

function shouldForceStairTransition(graph, startPathId, endPathId) {
    if (!graph || !startPathId || !endPathId) {
        return false;
    }
    if (startPathId === endPathId) {
        return false;
    }
    const startRule = getPathAccessRule(graph, startPathId);
    const endRule = getPathAccessRule(graph, endPathId);
    return Boolean((startRule && startRule.enforceTransitions) || (endRule && endRule.enforceTransitions));
}

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

function findStairNodesBy(graph, predicate) {
    if (!graph || !Array.isArray(graph.stairNodes)) return [];
    return graph.stairNodes.filter(node => predicate(node.room, node));
}

function findStairNodeByKeyAndVariant(graph, stairKey, variant) {
    return findStairNodesBy(graph, (room, node) => {
        const meta = parseStairId(node.roomId);
        return meta && meta.key === stairKey && meta.variant === variant;
    })[0] || null;
}

function getPrimaryPathIdForRoom(room) {
    if (!room) return null;
    if (room.nearestPathId) {
        return room.nearestPathId;
    }
    const entryPoints = getEntryPointsForRoom(room);
    if (entryPoints.length) {
        return entryPoints[0].nearestPathId || null;
    }
    return null;
}

function intersectArrays(a, b) {
    if (!Array.isArray(a) || !Array.isArray(b)) return [];
    const setB = new Set(b);
    return a.filter(item => setB.has(item));
}

function determineCandidateStairKeys(startGraph, startPathId, endGraph, endPathId) {
    const startKeys = getAllowedTransitionStairKeys(startGraph, startPathId);
    const endKeys = getAllowedTransitionStairKeys(endGraph, endPathId);
    if (startKeys && startKeys.length && endKeys && endKeys.length) {
        return intersectArrays(startKeys, endKeys);
    }
    if (startKeys && startKeys.length) {
        return [...startKeys];
    }
    if (endKeys && endKeys.length) {
        return [...endKeys];
    }
    return [];
}

function parseFloorFromRoomId(roomId) {
    if (!roomId) return null;
    const parts = roomId.split('-');
    const last = parts[parts.length - 1];
    const parsed = parseInt(last, 10);
    return Number.isNaN(parsed) ? null : parsed;
}

function getGraphFilePath(floor) {
    const basePath = window.FLOOR_GRAPH_BASE_PATH || '';
    const fileName = FLOOR_GRAPH_FILENAMES[floor] || `floor_graph_${floor}.json`;
    return `${basePath}${fileName}`;
}

async function ensureFloorGraphLoaded(floor) {
    if (!floor || floor < 1) {
        throw new Error(`Invalid floor requested: ${floor}`);
    }

    if (floorGraphCache[floor]) {
        return floorGraphCache[floor];
    }

    const graphFile = getGraphFilePath(floor);
    const response = await fetch(`${graphFile}?${new Date().getTime()}`);
    if (!response.ok) {
        throw new Error(`Failed to load ${graphFile}`);
    }
    const data = await response.json();
    const decorated = decorateFloorGraph(data, floor);
    floorGraphCache[floor] = decorated;
    return decorated;
}

function decorateFloorGraph(data, floor) {
    const graph = data || {};
    graph.floorNumber = floor;
    graph.stairNodes = extractStairNodes(graph);
    graph.pathAccessRules = graph.pathAccessRules || {};

    // Normalize entry point data so new code can rely on entryPoints while old JSON still using doorPoints keeps working.
    if (graph.rooms && typeof graph.rooms === 'object') {
        Object.values(graph.rooms).forEach(room => {
            if (!room) return;

            const legacyDoorPoints = Array.isArray(room.doorPoints) ? room.doorPoints : [];
            if (!Array.isArray(room.entryPoints) && legacyDoorPoints.length) {
                // Reuse the same array reference to avoid duplicating memory; callers now read via entryPoints.
                room.entryPoints = legacyDoorPoints;
            }
        });
    }
    return graph;
}

function extractStairNodes(graph) {
    if (!graph || !graph.rooms) return [];
    return Object.entries(graph.rooms)
        .filter(([_, room]) => room && room.type === 'stair' && room.stairKey)
        .map(([roomId, room]) => ({
            roomId,
            stairKey: room.stairKey,
            label: room.label || STAIR_NAME_MAP[room.stairKey] || roomId,
            room
        }));
}

function getSharedStairKeys(floorRange) {
    if (!floorRange || !floorRange.length) return [];
    let sharedKeys = null;
    floorRange.forEach(floor => {
        const graph = floorGraphCache[floor];
        if (!graph) return;
        const keys = new Set((graph.stairNodes || []).map(node => node.stairKey));
        if (sharedKeys === null) {
            sharedKeys = keys;
        } else {
            sharedKeys = new Set([...sharedKeys].filter(key => keys.has(key)));
        }
    });
    return sharedKeys ? [...sharedKeys] : [];
}

function floorsAreAdjacent(floorA, floorB) {
    if (floorA == null || floorB == null) return false;
    return Math.abs(floorA - floorB) === 1;
}

function getStairNodesConnectingToFloor(graph, targetFloor) {
    if (!graph || !Array.isArray(graph.stairNodes)) return [];
    return graph.stairNodes.filter(node => Array.isArray(node.room.connectsTo) && node.room.connectsTo.includes(targetFloor));
}

function isMasterStairNode(node) {
    if (!node || !node.roomId) return false;
    return node.roomId.toLowerCase().includes('stair_master');
}

function isThirdFloorStairNode(node) {
    if (!node || !node.roomId) return false;
    return node.roomId.toLowerCase().startsWith('stair_thirdfloor');
}

function isThirdFloorTransition(floorA, floorB) {
    return floorA === 3 || floorB === 3;
}

function isStairTransitionAllowed(nodeA, nodeB, floorA, floorB) {
    if (!nodeA || !nodeB) return false;

    if (!floorsAreAdjacent(floorA, floorB)) {
        return false;
    }

    const lower = Math.min(floorA, floorB);
    const upper = Math.max(floorA, floorB);

    if ((isMasterStairNode(nodeA) || isMasterStairNode(nodeB)) && (upper > 2)) {
        return false;
    }

    if (isThirdFloorTransition(floorA, floorB)) {
        if (!isThirdFloorStairNode(nodeA) || !isThirdFloorStairNode(nodeB)) {
            return false;
        }
    }

    return true;
}

function areStairNodesCompatible(nodeA, nodeB) {
    if (!nodeA || !nodeB) {
        return false;
    }

    // Primary check: stairGroup must match if both nodes have it defined
    const groupA = nodeA.room && nodeA.room.stairGroup;
    const groupB = nodeB.room && nodeB.room.stairGroup;
    
    if (groupA && groupB) {
        // If both have stairGroup defined, they MUST match exactly
        return groupA === groupB;
    }

    // Secondary check: If only one has stairGroup, try to match by variant
    if (groupA || groupB) {
        const infoA = parseStairId(nodeA.roomId);
        const infoB = parseStairId(nodeB.roomId);
        
        if (infoA && infoB) {
            // Both must have same stairKey and variant
            if (infoA.key === infoB.key && infoA.variant === infoB.variant) {
                return true;
            }
        }
        // If we can't match by variant, they're incompatible
        return false;
    }

    // Fallback: if stairGroup is not defined on either, use existing logic
    const infoA = parseStairId(nodeA.roomId);
    const infoB = parseStairId(nodeB.roomId);

    if (infoA && infoB) {
        // STRICT: Both stairKey AND variant must match
        if (infoA.key === infoB.key && infoA.variant === infoB.variant) {
            return true;
        }
        // Don't allow just stairKey matching - variant must match too
        return false;
    }

    // Last resort: check stairKey only if we couldn't parse IDs
    if (nodeA.stairKey && nodeB.stairKey && nodeA.stairKey === nodeB.stairKey) {
        // Even here, try to ensure they're the same variant
        const variantA = nodeA.roomId.match(/(\d+)-\d+$/);
        const variantB = nodeB.roomId.match(/(\d+)-\d+$/);
        
        if (variantA && variantB) {
            return variantA[1] === variantB[1];
        }
        
        // If we can't determine variant, be conservative
        console.warn('Could not determine stair variant for compatibility check', {
            nodeA: nodeA.roomId,
            nodeB: nodeB.roomId
        });
        return false;
    }

    return false;
}

function resolveTransitionStairKey(nodeA, nodeB) {
    if (nodeA && nodeB && nodeA.stairKey && nodeA.stairKey === nodeB.stairKey) {
        return nodeA.stairKey;
    }

    if (nodeB && nodeB.stairKey) {
        return nodeB.stairKey;
    }

    if (nodeA && nodeA.stairKey) {
        return nodeA.stairKey;
    }

    return null;
}

function getRequiredStairVariantForPath(graph, pathId) {
    if (!graph || !pathId || !Array.isArray(graph.stairNodes)) return null;
    
    const rule = getPathAccessRule(graph, pathId);
    if (!rule || !rule.enforceTransitions) return null;
    
    const allowedKeys = getAllowedTransitionStairKeys(graph, pathId);
    if (!allowedKeys || !allowedKeys.length) return null;
    
    // Find stairs that connect to this path - prefer the one with lowest variant number
    const connectedStairs = graph.stairNodes.filter(node => {
        if (!allowedKeys.includes(node.stairKey)) return false;
        const primaryPath = getPrimaryPathIdForRoom(node.room);
        return primaryPath === pathId;
    });
    
    if (!connectedStairs.length) return null;
    
    // Parse and find the lowest variant for the required stair key
    const parsedStairs = connectedStairs
        .map(node => ({node, parsed: parseStairId(node.roomId)}))
        .filter(item => item.parsed && allowedKeys.includes(item.parsed.key));
    
    if (!parsedStairs.length) return null;
    
    // Group by stair key and find lowest variant for each
    const variantsByKey = {};
    parsedStairs.forEach(item => {
        const key = item.parsed.key;
        if (!variantsByKey[key] || item.parsed.variant < variantsByKey[key]) {
            variantsByKey[key] = item.parsed.variant;
        }
    });
    
    // Return the first required stair key with its variant
    const firstKey = allowedKeys[0];
    return variantsByKey[firstKey] ? {stairKey: firstKey, variant: variantsByKey[firstKey]} : null;
}

function findStairTransitionsBetweenFloors(floorA, floorB) {
    const graphA = floorGraphCache[floorA];
    const graphB = floorGraphCache[floorB];

    if (!graphA || !graphB) return [];

    const nodesA = getStairNodesConnectingToFloor(graphA, floorB);
    const nodesB = getStairNodesConnectingToFloor(graphB, floorA);

    const transitions = [];

    nodesA.forEach(nodeA => {
        nodesB.forEach(nodeB => {
            if (!areStairNodesCompatible(nodeA, nodeB)) {
                return;
            }

            if (!isStairTransitionAllowed(nodeA, nodeB, floorA, floorB)) {
                return;
            }

            transitions.push({
                stairKey: resolveTransitionStairKey(nodeA, nodeB),
                fromFloor: floorA,
                toFloor: floorB,
                startNode: nodeA,
                endNode: nodeB
            });
        });
    });

    return transitions;
}

function calculatePolylineLength(points) {
    if (!points || points.length < 2) return 0;
    let length = 0;
    for (let i = 1; i < points.length; i++) {
        length += getDistance(points[i - 1], points[i]);
    }
    return length;
}

function describeStairKey(stairKey) {
    return STAIR_NAME_MAP[stairKey] || `Stair ${stairKey}`;
}

const PATH_FLOW_ANIMATION_CLASS = 'path-highlight-animated';
let pathFlowStylesInjected = false;

function ensurePathFlowAnimationStyles() {
    if (pathFlowStylesInjected) {
        return;
    }

    if (typeof document === 'undefined') {
        return;
    }

    const existing = document.getElementById('path-flow-animation-style');
    if (existing) {
        pathFlowStylesInjected = true;
        return;
    }

    const style = document.createElement('style');
    style.id = 'path-flow-animation-style';
    style.textContent = `
        @keyframes path-flow {
            from {
                stroke-dashoffset: 0px;
            }
            to {
                stroke-dashoffset: calc(-1 * var(--dash-cycle, 15px));
            }
        }

        .${PATH_FLOW_ANIMATION_CLASS} {
            animation-name: path-flow;
            animation-timing-function: linear;
            animation-iteration-count: infinite;
            animation-duration: var(--path-flow-duration, 1.2s);
        }
    `;

    const target = document.head || document.body;
    if (target) {
        target.appendChild(style);
        pathFlowStylesInjected = true;
    }
}

function setActiveRoute(route) {
    activeRoute = route;
    window.activeRoute = route;
    if (route && route.segments) {
        // Precompute lookup by floor for quick rendering
        route._segmentsByFloor = route.segments.reduce((acc, segment) => {
            if (segment.floor != null) {
                if (!acc[segment.floor]) acc[segment.floor] = [];
                acc[segment.floor].push(segment);
            }
            return acc;
        }, {});
    }
    renderRouteInstructions(route);
}

function renderActiveRouteForFloor(floor) {
    clearAllPaths();

    const svg = document.querySelector('svg');
    if (svg) {
        const previouslySelected = svg.querySelectorAll('.selected-room');
        previouslySelected.forEach(el => el.classList.remove('selected-room'));
    }

    if (!activeRoute || !activeRoute._segmentsByFloor) return;

    const segments = activeRoute._segmentsByFloor[floor] || [];
    const walkSegments = segments.filter(segment => segment.type === 'walk' && segment.points && segment.points.length);

    if (walkSegments.length) {
        walkSegments.forEach((segment, index) => {
            drawCompletePath(segment.points, { clearExisting: index === 0 });
        });
    }

    const selectionIds = [activeRoute.startRoomId, activeRoute.endRoomId].filter(Boolean);
    selectionIds.forEach(roomId => {
        const element = document.getElementById(roomId);
        if (element) {
            element.classList.add('selected-room');
        }
    });
}

function broadcastRoute(route) {
    try {
        window.dispatchEvent(new CustomEvent(ROUTE_EVENT_NAME, { detail: route }));
    } catch (err) {
        console.warn('Failed to broadcast route event', err);
    }
}

function ensureRouteInstructionsPanel() {
    if (routeInstructionsPanel && document.body.contains(routeInstructionsPanel)) {
        return routeInstructionsPanel;
    }

    if (!document.body) {
        return null;
    }

    const panel = document.createElement('div');
    panel.id = 'route-instructions-panel';
    panel.style.position = 'fixed';
    panel.style.bottom = '20px';
    panel.style.left = '20px';
    panel.style.right = '20px';
    panel.style.maxWidth = '360px';
    panel.style.margin = '0 auto';
    panel.style.padding = '16px';
    panel.style.borderRadius = '12px';
    panel.style.background = 'rgba(26, 86, 50, 0.95)';
    panel.style.color = '#ffffff';
    panel.style.boxShadow = '0 12px 32px rgba(0,0,0,0.35)';
    panel.style.fontFamily = 'Segoe UI, Roboto, sans-serif';
    panel.style.zIndex = '5000';
    panel.style.display = 'none';

    const header = document.createElement('div');
    header.style.display = 'flex';
    header.style.alignItems = 'center';
    header.style.justifyContent = 'space-between';
    header.style.marginBottom = '12px';

    const title = document.createElement('div');
    title.textContent = 'Route Instructions';
    title.style.fontSize = '18px';
    title.style.fontWeight = '600';

    const closeButton = document.createElement('button');
    closeButton.textContent = '×';
    closeButton.setAttribute('aria-label', 'Close route instructions');
    closeButton.style.fontSize = '24px';
    closeButton.style.lineHeight = '24px';
    closeButton.style.border = 'none';
    closeButton.style.background = 'transparent';
    closeButton.style.color = '#ffffff';
    closeButton.style.cursor = 'pointer';
    closeButton.addEventListener('click', () => {
        panel.style.display = 'none';
    });

    header.appendChild(title);
    header.appendChild(closeButton);
    panel.appendChild(header);

    const content = document.createElement('div');
    content.id = 'route-instructions-content';
    content.style.maxHeight = '280px';
    content.style.overflowY = 'auto';
    content.style.paddingRight = '4px';
    panel.appendChild(content);

    document.body.appendChild(panel);
    routeInstructionsPanel = panel;
    return panel;
}

function formatDistance(distance) {
    if (!distance || Number.isNaN(distance)) return '';
    const rounded = Math.round(distance);
    if (rounded <= 0) return '';
    return `${rounded} units`;
}

function renderRouteInstructions(route) {
    const panel = ensureRouteInstructionsPanel();
    if (!panel) return;

    const content = panel.querySelector('#route-instructions-content');
    if (!route) {
        panel.style.display = 'none';
        if (content) {
            content.innerHTML = '';
        }
        return;
    }

    panel.style.display = 'block';

    if (!content) {
        return;
    }

    const totalFloors = route.floors ? route.floors.length : 1;
    const totalDistance = formatDistance(route.totalDistance);
    content.innerHTML = '';

    const summary = document.createElement('p');
    summary.style.margin = '0 0 10px 0';
    summary.style.fontSize = '14px';
    summary.style.lineHeight = '1.4';
    let summaryText = 'Single-floor route.';

    if (route.type === 'multi-floor') {
        const stairNames = Array.isArray(route.stairSequence) && route.stairSequence.length
            ? [...new Set(route.stairSequence.map(item => describeStairKey(item.stairKey)))]
            : (route.stairKey ? [describeStairKey(route.stairKey)] : []);

        if (stairNames.length === 1) {
            summaryText = `Route spans ${totalFloors} floors using the ${stairNames[0]}.`;
        } else if (stairNames.length > 1) {
            summaryText = `Route spans ${totalFloors} floors using ${stairNames.join(', ')}.`;
        } else {
            summaryText = `Route spans ${totalFloors} floors.`;
        }
    }
    if (totalDistance) {
        summaryText += ` Total path length ≈ ${totalDistance}.`;
    }
    summary.textContent = summaryText;
    content.appendChild(summary);

    const list = document.createElement('ol');
    list.style.margin = '0';
    list.style.paddingLeft = '20px';
    list.style.fontSize = '14px';
    list.style.lineHeight = '1.5';

    (route.segments || []).forEach(segment => {
        const item = document.createElement('li');
        item.style.marginBottom = '8px';

        const title = document.createElement('div');
        title.style.fontWeight = '600';
        title.textContent = segment.description;
        item.appendChild(title);

        const distanceText = formatDistance(segment.distance);
        if (distanceText) {
            const distanceLine = document.createElement('div');
            distanceLine.style.opacity = '0.85';
            distanceLine.textContent = `Distance ≈ ${distanceText}`;
            item.appendChild(distanceLine);
        }

        if (segment.type === 'stair' && segment.floorSpan) {
            const floorSpanLine = document.createElement('div');
            floorSpanLine.style.opacity = '0.85';
            floorSpanLine.textContent = `Covers ${segment.floorSpan} floor${segment.floorSpan === 1 ? '' : 's'}.`;
            item.appendChild(floorSpanLine);
        }

        list.appendChild(item);
    });

    content.appendChild(list);

    if (route.type === 'multi-floor') {
        const tip = document.createElement('p');
        tip.style.margin = '12px 0 0 0';
        tip.style.fontSize = '13px';
        tip.style.lineHeight = '1.4';
        tip.style.opacity = '0.85';
        tip.textContent = 'Switch floors using the floor selector when you arrive. The highlighted route will update automatically.';
        content.appendChild(tip);
    }
}

if (typeof window !== 'undefined') {
    window.addEventListener(ROUTE_EVENT_NAME, event => {
        renderRouteInstructions(event.detail);
    });
    if (!window.resetActiveRoute) {
        window.resetActiveRoute = () => setActiveRoute(null);
    }
    if (!window.activateRouteBetweenRooms) {
        window.activateRouteBetweenRooms = activateRouteBetweenRooms;
    }
    if (!window.calculateMultiFloorRoute) {
        window.calculateMultiFloorRoute = calculateMultiFloorRoute;
    }
    if (!window.ensureFloorGraphLoaded) {
        window.ensureFloorGraphLoaded = ensureFloorGraphLoaded;
    }
}

// Function to find the nearest point on a path to a given point
function findNearestPointOnPath(point, pathPoints) {
    let nearestPoint = null;
    let minDistance = Infinity;
    
    // Check each line segment in the path
    for (let i = 0; i < pathPoints.length - 1; i++) {
        const start = pathPoints[i];
        const end = pathPoints[i + 1];
        
        // Find the nearest point on this line segment
        const nearest = findNearestPointOnSegment(point, start, end);
        const distance = getDistance(point, nearest);
        
        if (distance < minDistance) {
            minDistance = distance;
            nearestPoint = nearest;
        }
    }
    
    return nearestPoint;
}

// Function to find the nearest point on a line segment
function findNearestPointOnSegment(point, start, end) {
    const dx = end.x - start.x;
    const dy = end.y - start.y;
    
    if (dx === 0 && dy === 0) return start;
    
    const t = ((point.x - start.x) * dx + (point.y - start.y) * dy) / (dx * dx + dy * dy);
    
    if (t < 0) return start;
    if (t > 1) return end;
    
    return {
        x: start.x + t * dx,
        y: start.y + t * dy
    };
}

// Function to get distance between two points
function getDistance(point1, point2) {
    const dx = point2.x - point1.x;
    const dy = point2.y - point1.y;
    return Math.sqrt(dx * dx + dy * dy);
}

function findNearestWalkablePath(graph, point) {
    if (!graph || !Array.isArray(graph.walkablePaths) || !point) {
        return null;
    }

    let bestMatch = null;

    graph.walkablePaths.forEach(path => {
        if (!path || !Array.isArray(path.pathPoints) || !path.pathPoints.length) {
            return;
        }

        const nearestPoint = findNearestPointOnPath(point, path.pathPoints);
        if (!nearestPoint) {
            return;
        }

        const distance = getDistance(point, nearestPoint);
        if (!bestMatch || distance < bestMatch.distance) {
            bestMatch = {
                path,
                pathId: path.id,
                point: nearestPoint,
                distance
            };
        }
    });

    return bestMatch;
}

// Function to reload graph data
function reloadGraphData(floor) {
    const graphFile = getGraphFilePath(floor);
    return fetch(graphFile + '?' + new Date().getTime())
        .then(res => {
            if (!res.ok) {
                throw new Error(`Could not fetch ${graphFile}`);
            }
            return res.json();
        })
        .then(data => {
            const decoratedGraph = decorateFloorGraph(data, floor);
            floorGraphCache[floor] = decoratedGraph;
            floorGraph = decoratedGraph;
            currentFloorNumber = floor;
            if (typeof window !== 'undefined') {
                window.floorGraph = decoratedGraph;
            }
            console.log(`Reloaded floor graph for floor ${floor}:`, floorGraph);
            
            // Clear existing paths first
            const svg = document.querySelector('svg');
            if (svg) {
                const mainGroup = svg.querySelector('g');
                if (mainGroup) {
                    const pathGroup = mainGroup.querySelector('#walkable-path-group');
                    if (pathGroup) {
                        while (pathGroup.firstChild) {
                            pathGroup.removeChild(pathGroup.firstChild);
                        }
                    }
                }
            }
            
            // Draw all walkable paths when data is loaded
            if (floorGraph.walkablePaths && Array.isArray(floorGraph.walkablePaths)) {
                console.log('Drawing', floorGraph.walkablePaths.length, 'paths');
                floorGraph.walkablePaths.forEach((path, index) => {
                    if (path.pathPoints && path.pathPoints.length > 0) {
                        console.log(`Drawing path ${index + 1}:`, path.id);
                        drawWalkablePath(path);
                    }
                });
                
                // Draw entry points for easy adjustment
                if (floorGraph.rooms) {
                    drawEntryPoints(floorGraph.rooms);
                }
                
                // Initialize room selection after paths are drawn
                initRoomSelection();

                renderActiveRouteForFloor(floor);
            }
            return floorGraph;
        })
        .catch(error => {
            console.error('Error reloading floor graph:', error);
        });
}

// Function to draw the walkable path
function drawWalkablePath(walkablePath) {
    console.log('Drawing walkable path:', walkablePath.id);
    
    // Find the SVG container
    const svg = document.querySelector('svg');
    if (!svg) {
        console.error('SVG element not found');
        return;
    }
    console.log('Found SVG container');

    const svgNS = "http://www.w3.org/2000/svg";
    
    // Get or create the defs section for the SVG
    let defs = svg.querySelector('defs');
    if (!defs) {
        defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        svg.insertBefore(defs, svg.firstChild);
    }

    // Create a marker for the path
    const markerId = 'pathMarker';
    let marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
    marker.setAttribute('id', markerId);
    marker.setAttribute('viewBox', '0 0 10 10');
    marker.setAttribute('refX', '5');
    marker.setAttribute('refY', '5');
    marker.setAttribute('markerWidth', '4');
    marker.setAttribute('markerHeight', '4');
    marker.setAttribute('orient', 'auto');
    defs.appendChild(marker);

    // Create or get the path group - make sure it's inside the SVG's main group
    let mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
    if (!mainGroup) {
        mainGroup = document.createElementNS(svgNS, 'g');
        svg.appendChild(mainGroup);
    }
    console.log('Main group found:', mainGroup);

    // Create a separate group for markers to ensure they're on top
    let markerGroup = mainGroup.querySelector('#marker-group');
    if (!markerGroup) {
        markerGroup = document.createElementNS(svgNS, 'g');
        markerGroup.setAttribute('id', 'marker-group');
        mainGroup.appendChild(markerGroup);
    }

    let pathGroup = mainGroup.querySelector('#walkable-path-group');
    if (!pathGroup) {
        pathGroup = document.createElementNS(svgNS, 'g');
        pathGroup.setAttribute('id', 'walkable-path-group');
        mainGroup.appendChild(pathGroup);
        console.log('Created new path group');
    }
    console.log('Using path group:', pathGroup);
    
    // Create the path element
    const pathElement = document.createElementNS(svgNS, 'path');
    pathElement.setAttribute('id', `walkable-path-${walkablePath.id}`);
    
    // Generate the path data
    let pathData = '';
    console.log(`Generating path data for ${walkablePath.pathPoints.length} points`);
    walkablePath.pathPoints.forEach((point, index) => {
        if (index === 0) {
            pathData += `M ${point.x} ${point.y}`;
        } else {
            pathData += ` L ${point.x} ${point.y}`;
        }
    });
    console.log('Path data:', pathData);
    
    // Set the path attributes
    pathElement.setAttribute('d', pathData);
    pathElement.setAttribute('stroke', walkablePath.style.color);
    pathElement.setAttribute('vector-effect', 'non-scaling-stroke'); // This ensures stroke width remains constant
    pathElement.setAttribute('stroke-width', walkablePath.style.width);
    pathElement.setAttribute('fill', 'none');
    pathElement.setAttribute('opacity', walkablePath.style.opacity);
    if (walkablePath.style.highlight) {
        pathElement.setAttribute('stroke-dasharray', '5,5');
        pathElement.setAttribute('class', 'highlighted-path');
    }
    
    // Add the path to the group
    pathGroup.appendChild(pathElement);

    // Add point markers if defined in style
    if (walkablePath.style.pointMarker) {
        const points = walkablePath.pathPoints;
        
        // Create marker group if it doesn't exist
        let markerGroup = mainGroup.querySelector('#marker-group');
        if (!markerGroup) {
            markerGroup = document.createElementNS(svgNS, 'g');
            markerGroup.setAttribute('id', 'marker-group');
            mainGroup.appendChild(markerGroup);
        }

        // Add markers for each point
        points.forEach((point, index) => {
            // Only create a clickable marker if the point is a panorama point
            if (point.isPano) {
                // Create camera icon group
                const marker = document.createElementNS(svgNS, 'g');
                marker.classList.add('panorama-marker');
                marker.setAttribute('data-path-id', walkablePath.id);
                marker.setAttribute('data-point-index', index);

                // Create background circle
                const bgCircle = document.createElementNS(svgNS, 'circle');
                bgCircle.setAttribute('cx', point.x);
                bgCircle.setAttribute('cy', point.y);
                bgCircle.setAttribute('r', '12'); // Match explore.php marker radius
                bgCircle.setAttribute('fill', '#2563eb'); // Blue background
                bgCircle.setAttribute('stroke', '#ffffff');
                bgCircle.setAttribute('stroke-width', '1.5');
                bgCircle.setAttribute('class', 'camera-bg');
                bgCircle.setAttribute('vector-effect', 'non-scaling-stroke');

                // Create camera icon path
                const cameraIcon = document.createElementNS(svgNS, 'path');
                cameraIcon.setAttribute('d', 'M14 4h-1l-2-2h-2l-2 2h-1c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-8c0-1.1-.9-2-2-2zm-4 7c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3z');
                cameraIcon.setAttribute('fill', '#ffffff');
                // Adjust transform to match explore.php marker sizing
                cameraIcon.setAttribute('transform', `translate(${point.x - 8}, ${point.y - 8}) scale(0.8)`);
                cameraIcon.setAttribute('class', 'camera-icon');
                cameraIcon.style.pointerEvents = 'none'; // Make icon non-interactive

                marker.appendChild(bgCircle);
                marker.appendChild(cameraIcon);

                // Add a click listener for the admin to edit the panorama
                marker.addEventListener('click', (event) => {
                    event.stopPropagation(); // Prevent room click handler from firing
                    console.log('Clicked panorama point:', point);

                    // Deactivate any other active markers
                    document.querySelectorAll('.panorama-marker.active').forEach(activeMarker => {
                        activeMarker.classList.remove('active');
                        const activeBg = activeMarker.querySelector('.camera-bg');
                        if (activeBg) {
                            activeBg.setAttribute('fill', '#2563eb');
                            activeBg.setAttribute('r', '12');
                        }
                    });

                    // Activate the clicked marker and style it as active (yellow + larger)
                    marker.classList.add('active');
                    const thisBg = marker.querySelector('.camera-bg');
                    if (thisBg) {
                        thisBg.setAttribute('fill', '#fbbf24'); // Yellow active color
                        thisBg.setAttribute('r', '15');
                    }
                    
                    // Call function to open the editor modal
                    openPanoramaEditor(walkablePath.id, index, point.panoImage || '');
                });

                // Hover effects - change to lighter blue but do not animate
                marker.addEventListener('mouseenter', () => {
                    if (!marker.classList.contains('active')) {
                        const bg = marker.querySelector('.camera-bg');
                        if (bg) bg.setAttribute('fill', '#3b82f6');
                    }
                });
                marker.addEventListener('mouseleave', () => {
                    if (!marker.classList.contains('active')) {
                        const bg = marker.querySelector('.camera-bg');
                        if (bg) bg.setAttribute('fill', '#2563eb');
                    }
                });
                
                markerGroup.appendChild(marker);
            }
        });
    }
}

// Function to draw entry points for easy adjustment
function drawEntryPoints(rooms) {
    console.log('Drawing entry points for adjustment...', rooms);
    const svg = document.querySelector('svg');
    if (!svg) {
        console.error('SVG element not found');
        return;
    }
    const svgNS = "http://www.w3.org/2000/svg";
    let mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
    if (!mainGroup) {
        mainGroup = document.createElementNS(svgNS, 'g');
        svg.appendChild(mainGroup);
    }

    let entryPointGroup = mainGroup.querySelector('#entry-point-group');
    if (!entryPointGroup) {
        entryPointGroup = document.createElementNS(svgNS, 'g');
        entryPointGroup.setAttribute('id', 'entry-point-group');
        mainGroup.appendChild(entryPointGroup);
    }
    // Clear existing entry points to avoid duplicates on reload
    entryPointGroup.innerHTML = '';

    for (const roomId in rooms) {
        const room = rooms[roomId];
        console.log(`Checking room ${roomId}:`, room);

        // Accept both entryPoints (new) and doorPoints (legacy) to keep older data compatible.
        const entryPoints = getEntryPointsForRoom(room);

        if (entryPoints.length > 0 && room.style && room.style.pointMarker) {
            const style = room.style.pointMarker;

            entryPoints.forEach((entryPoint, index) => {
                console.log(`Drawing entry point for ${roomId} at (${entryPoint.x}, ${entryPoint.y})`);

                const marker = document.createElementNS(svgNS, 'circle');
                marker.setAttribute('cx', entryPoint.x);
                marker.setAttribute('cy', entryPoint.y);
                marker.setAttribute('r', style.radius || 8);
                marker.setAttribute('fill', style.color || 'red');
                marker.setAttribute('stroke', style.strokeColor || '#000');
                marker.setAttribute('stroke-width', style.strokeWidth || 2);
                marker.setAttribute('vector-effect', 'non-scaling-stroke');
                marker.setAttribute('class', 'entry-point-marker-highlight');
                marker.setAttribute('id', `entry-point-${roomId}-${index}`);

                // Add hover effect for better interaction
                marker.addEventListener('mouseenter', () => {
                    marker.setAttribute('fill', style.hoverColor || '#FF0000');
                    marker.setAttribute('r', (style.radius || 8) * 1.2);
                });

                marker.addEventListener('mouseleave', () => {
                    marker.setAttribute('fill', style.color || 'red');
                    marker.setAttribute('r', style.radius || 8);
                });

                entryPointGroup.appendChild(marker);
                console.log(`Successfully added entry point marker for ${roomId}`);
            });
        } else {
            console.log(`Skipping room ${roomId} - no entry points or style found`);
        }
    }
    console.log('Finished drawing entry points');
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Skip automatic desktop-style initialization when running in MOBILE_MODE
    if (window.MOBILE_MODE) {
        console.log('[Pathfinding] MOBILE_MODE detected – skipping auto initPathfinding. Mobile layer drives path loading.');
        return; // Mobile flow will manage when/if to invoke pathfinding helpers explicitly
    }

    console.log('Initializing pathfinding and room selection...');

    // Listen for floor changes (desktop/admin context)
    window.addEventListener('floorMapLoaded', (event) => {
        const floor = event.detail.floor;
        console.log(`Floor map loaded for floor ${floor}, initializing pathfinding...`);
        initPathfinding(floor);
    });

    // Initial load for the default floor (floor 1) in desktop context
    initPathfinding(1);
});

// Initialize pathfinding for a specific floor
const initPathfinding = (floor) => {
    const basePath = window.FLOOR_GRAPH_BASE_PATH || '';
    let graphFile = `${basePath}floor_graph.json`; // Default to 1st floor
    if (floor === 2) {
        graphFile = `${basePath}floor_graph_2.json`;
    } else if (floor === 3) {
        graphFile = `${basePath}floor_graph_3.json`; // Example for 3rd floor
    }

    fetch(graphFile + '?' + new Date().getTime())
        .then(res => {
            if (!res.ok) throw new Error(`Failed to load ${graphFile}`);
            return res.json();
        })
        .then(data => {
            const decoratedGraph = decorateFloorGraph(data, floor);
            floorGraphCache[floor] = decoratedGraph;
            floorGraph = decoratedGraph;
            currentFloorNumber = floor;
            if (typeof window !== 'undefined') {
                window.floorGraph = decoratedGraph;
            }
            console.log(`Loaded floor graph for floor ${floor}:`, floorGraph);
            if (floorGraph.walkablePaths && Array.isArray(floorGraph.walkablePaths)) {
                console.log(`Found ${floorGraph.walkablePaths.length} paths to draw`);
                
                // Clear previous paths before drawing new ones
                const svg = document.querySelector('svg');
                if (svg) {
                    const pathGroup = svg.querySelector('#walkable-path-group');
                    if (pathGroup) pathGroup.innerHTML = ''; // Clear old paths
                }

                floorGraph.walkablePaths.forEach((path, index) => {
                    if (path.pathPoints && path.pathPoints.length > 0) {
                        console.log(`Drawing path ${index + 1}:`, path.id);
                        drawWalkablePath(path);
                    }
                });
                
                // Draw entry points for easy adjustment
                if (floorGraph.rooms) {
                    drawEntryPoints(floorGraph.rooms);
                }
                
                // Initialize room selection after paths are drawn
                initRoomSelection();

                // If a multi-floor route is active, render the segment for this floor
                renderActiveRouteForFloor(floor);
            }
        })
        .catch(error => {
            console.error(`Error loading floor graph for floor ${floor}:`, error);
        });
};

// Initialize room selection - moved outside DOMContentLoaded so it can be called after floor changes
function initRoomSelection() {
    console.log('Initializing room selection...');
    document.querySelectorAll('[id^="room-"]').forEach(el => {
        // Remove existing click listeners to avoid duplicates
        el.removeEventListener('click', roomClickHandler);
        el.addEventListener('click', roomClickHandler);
    });
}

function getEntryPointsForRoom(room) {
    if (!room) return [];
    if (Array.isArray(room.entryPoints) && room.entryPoints.length) {
        return room.entryPoints;
    }
    if (Array.isArray(room.doorPoints) && room.doorPoints.length) {
        return room.doorPoints;
    }
    if (room.doorPoint) {
        return [room.doorPoint];
    }
    return [];
}

/**
 * Check if a room has restricted access rules
 * @param {Object} graph - The floor graph
 * @param {string} roomId - The room ID to check
 * @returns {Object|null} - The restriction rule or null
 */
function getRestrictedAccessRule(graph, roomId) {
    if (!graph || !graph.restrictedAccessRules || !roomId) {
        return null;
    }
    return graph.restrictedAccessRules[roomId] || null;
}

/**
 * Get the mandatory entry point for a restricted room
 * @param {Object} graph - The floor graph
 * @param {string} roomId - The room ID
 * @returns {Object|null} - The entry point room object or null
 */
function getMandatoryEntryPoint(graph, roomId) {
    const rule = getRestrictedAccessRule(graph, roomId);
    if (!rule || !rule.mandatoryEntryPoint) {
        return null;
    }
    
    const entryPointId = rule.mandatoryEntryPoint;
    
    // Check if entry point is on a different floor
    if (rule.entryPointFloor && rule.entryPointFloor !== graph.floorNumber) {
        const entryFloorGraph = floorGraphCache[rule.entryPointFloor];
        if (!entryFloorGraph) {
            console.warn(`Entry point floor ${rule.entryPointFloor} not loaded for room ${roomId}`);
            return null;
        }
        
        const entryRoom = entryFloorGraph.rooms[entryPointId];
        if (!entryRoom) {
            console.warn(`Mandatory entry point ${entryPointId} not found on floor ${rule.entryPointFloor} for room ${roomId}`);
            return null;
        }
        
        return {
            roomId: entryPointId,
            room: entryRoom,
            floor: rule.entryPointFloor
        };
    }
    
    // Entry point is on the same floor
    const entryRoom = graph.rooms[entryPointId];
    
    if (!entryRoom) {
        console.warn(`Mandatory entry point ${entryPointId} not found for room ${roomId}`);
        return null;
    }
    
    return {
        roomId: entryPointId,
        room: entryRoom,
        floor: graph.floorNumber
    };
}

/**
 * Check if both rooms have the same mandatory entry point
 * @param {Object} graph - The floor graph
 * @param {string} startRoomId - Start room ID
 * @param {string} endRoomId - End room ID
 * @returns {boolean} - True if both have the same mandatory entry point
 */
function haveSameMandatoryEntry(graph, startRoomId, endRoomId) {
    const startRule = getRestrictedAccessRule(graph, startRoomId);
    const endRule = getRestrictedAccessRule(graph, endRoomId);
    
    if (!startRule || !endRule) {
        return false;
    }
    
    // Check if they point to the same entry point ID
    if (startRule.mandatoryEntryPoint !== endRule.mandatoryEntryPoint) {
        return false;
    }
    
    // Also check if they use the same floor for entry
    const startFloor = startRule.entryPointFloor || graph.floorNumber;
    const endFloor = endRule.entryPointFloor || graph.floorNumber;
    
    return startFloor === endFloor;
}

function addFloorToRoute(route, floor) {
    if (!route || floor == null) return;
    if (!Array.isArray(route.floors)) {
        route.floors = [];
    }
    if (!route.floors.includes(floor)) {
        route.floors.push(floor);
        route.floors.sort((a, b) => a - b);
    }
}

function getRepresentativePointsForRoom(graph, roomId, limit = 6) {
    if (!graph || !graph.rooms) return [];
    const room = graph.rooms[roomId];
    if (!room) return [];

    const points = [];
    const pathId = getPrimaryPathIdForRoom(room);

    if (pathId && Array.isArray(graph.walkablePaths)) {
        const path = graph.walkablePaths.find(p => p.id === pathId);
        if (path && Array.isArray(path.pathPoints)) {
            path.pathPoints.slice(0, limit).forEach(pt => {
                if (pt && typeof pt.x === 'number' && typeof pt.y === 'number') {
                    points.push({ x: pt.x, y: pt.y });
                }
            });
        }
    }

    const entryPoints = getEntryPointsForRoom(room);
    entryPoints.slice(0, limit).forEach(pt => {
        if (pt && typeof pt.x === 'number' && typeof pt.y === 'number') {
            const exists = points.some(existing => existing.x === pt.x && existing.y === pt.y);
            if (!exists) {
                points.push({ x: pt.x, y: pt.y });
            }
        }
    });

    if (!points.length && typeof room.x === 'number' && typeof room.y === 'number') {
        points.push({ x: room.x, y: room.y });
    }

    return points;
}

function augmentRouteWithRestrictedStart({ route, startRoomId, startFloor, entryPoint }) {
    if (!route) return null;

    const startGraph = floorGraphCache[startFloor];
    const startRoom = startGraph && startGraph.rooms ? startGraph.rooms[startRoomId] : null;
    const entryLabel = entryPoint.room?.label || entryPoint.roomId;
    const roomLabel = startRoom?.label || startRoomId;
    const points = getRepresentativePointsForRoom(startGraph, startRoomId);

    const segment = {
        type: points.length ? 'walk' : 'restricted',
        floor: startFloor,
        description: `Restricted access: Exit ${roomLabel} via ${entryLabel} on Floor ${entryPoint.floor}.`,
        distance: null,
        points,
        startRoomId,
        endRoomId: entryPoint.roomId,
        mandatoryEntry: entryPoint.roomId,
        restricted: true,
        restrictionContext: 'start'
    };

    const existingSegments = Array.isArray(route.segments) ? route.segments.slice() : [];
    route.segments = [segment, ...existingSegments];
    route.startRoomId = startRoomId;
    route.type = 'multi-floor';
    addFloorToRoute(route, startFloor);
    route.restrictedStart = {
        roomId: startRoomId,
        entryPointId: entryPoint.roomId,
        entryFloor: entryPoint.floor
    };

    const entryParsed = parseStairId(entryPoint.roomId);
    const stairKey = entryPoint.room?.stairKey || entryParsed?.key || null;
    if (stairKey && entryPoint.floor != null && startFloor != null && entryPoint.floor !== startFloor) {
        const stairUsage = {
            stairKey,
            fromFloor: startFloor,
            toFloor: entryPoint.floor,
            startRoomId,
            endRoomId: entryPoint.roomId,
            variant: entryParsed?.variant || null
        };
        route.stairSequence = Array.isArray(route.stairSequence) ? route.stairSequence.slice() : [];
        const alreadyRecorded = route.stairSequence.some(seq =>
            seq.stairKey === stairUsage.stairKey &&
            seq.fromFloor === stairUsage.fromFloor &&
            seq.toFloor === stairUsage.toFloor
        );
        if (!alreadyRecorded) {
            route.stairSequence.unshift(stairUsage);
        }
    }

    return route;
}

function augmentRouteWithRestrictedEnd({ route, endRoomId, endGraph, entryPoint, forcedStair }) {
    if (!route) return null;

    const endFloor = endGraph.floorNumber;
    const endRoom = endGraph.rooms ? endGraph.rooms[endRoomId] : null;
    const entryLabel = entryPoint.room?.label || entryPoint.roomId;
    const roomLabel = endRoom?.label || endRoomId;
    const entryParsed = parseStairId(entryPoint.roomId);
    const stairKey = forcedStair?.stairKey || entryPoint.room?.stairKey || entryParsed?.key || null;
    const stairName = stairKey ? describeStairKey(stairKey) : null;

    const description = stairName
        ? `Restricted destination: From ${entryLabel}, take the ${stairName} to Floor ${endFloor} and continue to ${roomLabel}.`
        : `Restricted destination: From ${entryLabel}, use the authorized access route to reach ${roomLabel} on Floor ${endFloor}.`;

    const points = getRepresentativePointsForRoom(endGraph, endRoomId);

    const segment = {
        type: points.length ? 'walk' : 'restricted',
        floor: endFloor,
        description,
        distance: null,
        points,
        startRoomId: entryPoint.roomId,
        endRoomId: endRoomId,
        mandatoryEntry: entryPoint.roomId,
        restricted: true,
        restrictionContext: 'end'
    };

    const existingSegments = Array.isArray(route.segments) ? route.segments.slice() : [];
    route.segments = [...existingSegments, segment];
    route.endRoomId = endRoomId;
    route.type = 'multi-floor';
    addFloorToRoute(route, endFloor);
    route.restrictedEnd = {
        entryPointId: entryPoint.roomId,
        entryFloor: entryPoint.floor,
        targetRoomId: endRoomId
    };

    if (stairKey && entryPoint.floor != null && endFloor != null && entryPoint.floor !== endFloor) {
        const stairUsage = {
            stairKey,
            fromFloor: entryPoint.floor,
            toFloor: endFloor,
            startRoomId: entryPoint.roomId,
            endRoomId,
            variant: forcedStair?.variant || entryParsed?.variant || null
        };
        route.stairSequence = Array.isArray(route.stairSequence) ? route.stairSequence.slice() : [];
        const alreadyRecorded = route.stairSequence.some(seq =>
            seq.stairKey === stairUsage.stairKey &&
            seq.fromFloor === stairUsage.fromFloor &&
            seq.toFloor === stairUsage.toFloor
        );
        if (!alreadyRecorded) {
            route.stairSequence.push(stairUsage);
        }
    }

    return route;
}

function calculateSingleFloorRoute(graph, startRoomId, endRoomId) {
    if (!graph || !graph.rooms) {
        return null;
    }

    const startRoom = graph.rooms[startRoomId];
    const endRoom = graph.rooms[endRoomId];

    if (!startRoom || !endRoom) {
        console.warn('Single-floor route failed due to missing room data', {
            startRoomId,
            endRoomId
        });
        return null;
    }

    // Check for restricted access rules
    const startRestriction = getRestrictedAccessRule(graph, startRoomId);
    const endRestriction = getRestrictedAccessRule(graph, endRoomId);
    
    console.log(`[calculateSingleFloorRoute] Floor ${graph.floorNumber}: ${startRoomId} -> ${endRoomId}`, {
        startRestricted: !!startRestriction,
        endRestricted: !!endRestriction
    });
    
    // Special case: Both rooms share the same mandatory entry point
    if (startRestriction && endRestriction && 
        haveSameMandatoryEntry(graph, startRoomId, endRoomId)) {
        const entryPoint = getMandatoryEntryPoint(graph, startRoomId);
        if (entryPoint) {
            console.log(`Both rooms use same mandatory entry: ${entryPoint.roomId} on floor ${entryPoint.floor}`);
            
            // If entry point is on a different floor, return minimal route indicating cross-floor requirement
            if (entryPoint.floor !== graph.floorNumber) {
                console.log(`Entry point on different floor (${entryPoint.floor}), cannot route on same floor`);
                // Return a placeholder that indicates both rooms need cross-floor routing
                return null; // This will trigger multi-floor routing
            }
            
            // Route from entry point to entry point (essentially just the entry point)
            const entryDoors = getEntryPointsForRoom(entryPoint.room);
            if (entryDoors.length > 0) {
                return {
                    startDoor: entryDoors[0],
                    endDoor: entryDoors[0],
                    startPathPoint: entryDoors[0],
                    endPathPoint: entryDoors[0],
                    pathBetween: [],
                    points: [entryDoors[0]],
                    distance: 0,
                    startPathId: entryPoint.room.nearestPathId,
                    endPathId: entryPoint.room.nearestPathId,
                    restrictedRoute: true,
                    mandatoryEntry: entryPoint.roomId
                };
            }
        }
    }
    
    // Case: Start room is restricted - route from mandatory entry to destination
    if (startRestriction && !endRestriction) {
        const entryPoint = getMandatoryEntryPoint(graph, startRoomId);
        if (entryPoint) {
            console.log(`Start room ${startRoomId} restricted, routing from ${entryPoint.roomId} on floor ${entryPoint.floor} to ${endRoomId}`);
            
            // If entry point is on a different floor, return null to trigger multi-floor routing
            if (entryPoint.floor !== graph.floorNumber) {
                console.log(`Entry point on different floor (${entryPoint.floor}), triggering multi-floor route`);
                return null;
            }
            
            const routeFromEntry = calculateSingleFloorRoute(graph, entryPoint.roomId, endRoomId);
            if (routeFromEntry) {
                routeFromEntry.restrictedRoute = true;
                routeFromEntry.mandatoryEntry = entryPoint.roomId;
                return routeFromEntry;
            }
        }
    }
    
    // Case: End room is restricted - route from start to mandatory entry
    if (!startRestriction && endRestriction) {
        const entryPoint = getMandatoryEntryPoint(graph, endRoomId);
        if (entryPoint) {
            console.log(`End room ${endRoomId} restricted, routing from ${startRoomId} to ${entryPoint.roomId} on floor ${entryPoint.floor}`);
            
            // If entry point is on a different floor, return null to trigger multi-floor routing
            if (entryPoint.floor !== graph.floorNumber) {
                console.log(`Entry point on different floor (${entryPoint.floor}), triggering multi-floor route`);
                return null;
            }
            
            const routeToEntry = calculateSingleFloorRoute(graph, startRoomId, entryPoint.roomId);
            if (routeToEntry) {
                routeToEntry.restrictedRoute = true;
                routeToEntry.mandatoryEntry = entryPoint.roomId;
                return routeToEntry;
            }
        }
    }

    // Normal routing logic (no restrictions)
    const startDoors = getEntryPointsForRoom(startRoom);
    const endDoors = getEntryPointsForRoom(endRoom);
    
    // Filter out virtual doorPoints for normal routing - virtual points are only for UI selection
    const realStartDoors = startDoors.filter(door => !door.virtual);
    const realEndDoors = endDoors.filter(door => !door.virtual);

    if (!realStartDoors.length || !realEndDoors.length) {
        console.warn(`No real (non-virtual) entry points found for rooms on floor ${graph.floorNumber}`, { 
            startRoomId, 
            endRoomId,
            startDoorsTotal: startDoors.length,
            endDoorsTotal: endDoors.length,
            realStartDoors: realStartDoors.length,
            realEndDoors: realEndDoors.length
        });
        return null;
    }

    let bestOption = null;

    for (const startDoor of realStartDoors) {
        for (const endDoor of realEndDoors) {
            const startPathId = startDoor.nearestPathId || startRoom.nearestPathId;
            const endPathId = endDoor.nearestPathId || endRoom.nearestPathId;

            let startPath = startPathId ? graph.walkablePaths.find(p => p.id === startPathId) : null;
            let endPath = endPathId ? graph.walkablePaths.find(p => p.id === endPathId) : null;

            let startPathPoint = startPath ? findNearestPointOnPath(startDoor, startPath.pathPoints) : null;
            let endPathPoint = endPath ? findNearestPointOnPath(endDoor, endPath.pathPoints) : null;

            if (!startPathPoint) {
                const fallback = findNearestWalkablePath(graph, startDoor);
                if (fallback) {
                    startPath = fallback.path;
                    startPathPoint = fallback.point;
                    if (!startDoor.nearestPathId) {
                        startDoor.nearestPathId = fallback.pathId;
                    }
                }
            }

            if (!endPathPoint) {
                const fallback = findNearestWalkablePath(graph, endDoor);
                if (fallback) {
                    endPath = fallback.path;
                    endPathPoint = fallback.point;
                    if (!endDoor.nearestPathId) {
                        endDoor.nearestPathId = fallback.pathId;
                    }
                }
            }

            if (!startPathPoint || !endPathPoint) {
                continue;
            }

            // Ensure we have valid paths after fallback resolution
            if (!startPath || !endPath) {
                continue;
            }

            const pathBetween = getPathBetweenPoints(startPathPoint, endPathPoint, graph.walkablePaths, { graph });

            if (!pathBetween) {
                continue;
            }

            const combinedPoints = [startDoor, ...pathBetween, endDoor];
            const distance = calculatePolylineLength(combinedPoints);

            if (!bestOption || distance < bestOption.distance) {
                bestOption = {
                    startDoor,
                    endDoor,
                    startPathPoint,
                    endPathPoint,
                    pathBetween,
                    points: combinedPoints,
                    distance,
                    startPathId,
                    endPathId
                };
            }
        }
    }

    if (!bestOption && realStartDoors.length && realEndDoors.length) {
        let closestPair = null;
        let bestDistance = Infinity;

        realStartDoors.forEach(startDoor => {
            realEndDoors.forEach(endDoor => {
                const distance = getDistance(startDoor, endDoor);
                if (!Number.isFinite(distance)) {
                    return;
                }
                if (distance < bestDistance) {
                    closestPair = { startDoor, endDoor };
                    bestDistance = distance;
                }
            });
        });

        if (closestPair) {
            const startPathId = closestPair.startDoor.nearestPathId || startRoom.nearestPathId || null;
            const endPathId = closestPair.endDoor.nearestPathId || endRoom.nearestPathId || null;
            const points = [
                { x: closestPair.startDoor.x, y: closestPair.startDoor.y },
                { x: closestPair.endDoor.x, y: closestPair.endDoor.y }
            ];
            const distance = calculatePolylineLength(points);

            bestOption = {
                startDoor: closestPair.startDoor,
                endDoor: closestPair.endDoor,
                startPathPoint: points[0],
                endPathPoint: points[1],
                pathBetween: [],
                points,
                distance,
                startPathId,
                endPathId,
                synthetic: true
            };

            console.warn('calculateSingleFloorRoute: using synthetic straight-line fallback between rooms.', {
                floor: graph.floorNumber,
                startRoomId,
                endRoomId
            });
        }
    }

    return bestOption;
}

async function calculateConstrainedSameFloorRoute({
    floorNumber,
    graph,
    startRoomId,
    endRoomId,
    startPathId,
    endPathId
}) {
    if (!graph) return null;

    const candidateStairKeys = determineCandidateStairKeys(graph, startPathId, graph, endPathId);
    const stairKeysToConsider = candidateStairKeys.length ? candidateStairKeys : (graph.stairNodes || []).map(node => node.room.stairKey);
    const uniqueStairKeys = [...new Set(stairKeysToConsider.filter(Boolean))];
    if (!uniqueStairKeys.length) {
        console.warn('No candidate stair keys found for constrained route', { startPathId, endPathId });
        return null;
    }

    // Determine required variants for the start and end paths
    const requiredStartVariant = getRequiredStairVariantForPath(graph, startPathId);
    const requiredEndVariant = getRequiredStairVariantForPath(graph, endPathId);

    let bestRoute = null;

    for (const stairKey of uniqueStairKeys) {
        let startStairs = findStairNodesBy(graph, room => room.stairKey === stairKey && getPrimaryPathIdForRoom(room) === startPathId);
        let endStairs = findStairNodesBy(graph, room => room.stairKey === stairKey && getPrimaryPathIdForRoom(room) === endPathId);
        
        // Filter by required variant if specified
        if (requiredStartVariant && stairKey === requiredStartVariant.stairKey) {
            startStairs = startStairs.filter(node => {
                const parsed = parseStairId(node.roomId);
                return parsed && parsed.variant === requiredStartVariant.variant;
            });
        }
        
        if (requiredEndVariant && stairKey === requiredEndVariant.stairKey) {
            endStairs = endStairs.filter(node => {
                const parsed = parseStairId(node.roomId);
                return parsed && parsed.variant === requiredEndVariant.variant;
            });
        }

        if (!startStairs.length || !endStairs.length) {
            continue;
        }

        for (const startStair of startStairs) {
            const startSegment = calculateSingleFloorRoute(graph, startRoomId, startStair.roomId);
            if (!startSegment) {
                continue;
            }

            const startMeta = parseStairId(startStair.roomId);
            const startConnects = Array.isArray(startStair.room.connectsTo) ? startStair.room.connectsTo : [];

            for (const endStair of endStairs) {
                const sharedFloors = intersectArrays(startConnects, Array.isArray(endStair.room.connectsTo) ? endStair.room.connectsTo : []);
                if (!sharedFloors.length) {
                    continue;
                }

                const endSegment = calculateSingleFloorRoute(graph, endStair.roomId, endRoomId);
                if (!endSegment) {
                    continue;
                }

                const endMeta = parseStairId(endStair.roomId);
                if (!startMeta || !endMeta) {
                    continue;
                }

                for (const midFloor of sharedFloors) {
                    const midGraph = await ensureFloorGraphLoaded(midFloor);
                    if (!midGraph) {
                        continue;
                    }

                    const upperStart = findStairNodeByKeyAndVariant(midGraph, stairKey, startMeta.variant);
                    const upperEnd = findStairNodeByKeyAndVariant(midGraph, stairKey, endMeta.variant);

                    if (!upperStart || !upperEnd) {
                        continue;
                    }

                    const middleSegment = calculateSingleFloorRoute(midGraph, upperStart.roomId, upperEnd.roomId);
                    if (!middleSegment) {
                        continue;
                    }

                    const totalDistance = startSegment.distance + middleSegment.distance + endSegment.distance;

                    const route = {
                        type: 'multi-floor',
                        startRoomId,
                        endRoomId,
                        stairKey,
                        floors: [floorNumber, midFloor],
                        totalDistance,
                        segments: [
                            {
                                type: 'walk',
                                floor: floorNumber,
                                description: `Floor ${floorNumber}: Proceed to ${describeStairKey(stairKey)}`,
                                points: startSegment.points,
                                distance: startSegment.distance,
                                startDoor: startSegment.startDoor,
                                endDoor: startSegment.endDoor,
                                via: stairKey
                            },
                            {
                                type: 'stair',
                                stairKey,
                                description: `Use ${describeStairKey(stairKey)} to reach Floor ${midFloor}`,
                                fromFloor: floorNumber,
                                toFloor: midFloor,
                                floors: [floorNumber, midFloor],
                                floorSpan: Math.abs(midFloor - floorNumber) || 1
                            },
                            {
                                type: 'walk',
                                floor: midFloor,
                                description: `Floor ${midFloor}: Transition across ${describeStairKey(stairKey)} landing`,
                                points: middleSegment.points,
                                distance: middleSegment.distance,
                                startDoor: middleSegment.startDoor,
                                endDoor: middleSegment.endDoor,
                                via: stairKey
                            },
                            {
                                type: 'stair',
                                stairKey,
                                description: `Return via ${describeStairKey(stairKey)} to Floor ${floorNumber}`,
                                fromFloor: midFloor,
                                toFloor: floorNumber,
                                floors: [midFloor, floorNumber],
                                floorSpan: Math.abs(midFloor - floorNumber) || 1
                            },
                            {
                                type: 'walk',
                                floor: floorNumber,
                                description: `Floor ${floorNumber}: Continue to destination`,
                                points: endSegment.points,
                                distance: endSegment.distance,
                                startDoor: endSegment.startDoor,
                                endDoor: endSegment.endDoor,
                                via: stairKey
                            }
                        ]
                    };

                    if (!bestRoute || totalDistance < bestRoute.totalDistance) {
                        bestRoute = route;
                    }
                }
            }
        }
    }

    if (!bestRoute) {
        console.warn('No constrained route found between rooms with enforced stair transition', {
            floorNumber,
            startRoomId,
            endRoomId,
            startPathId,
            endPathId
        });
    }

    return bestRoute;
}

async function calculateMultiFloorRoute(startRoomId, endRoomId, options = {}) {
    const forcedStair = options && typeof options === 'object' ? options.forcedStair || null : null;
    const forcedParsed = forcedStair && forcedStair.roomId ? parseStairId(forcedStair.roomId) : null;
    const forcedStairKey = forcedStair?.stairKey || forcedParsed?.key || null;
    const forcedStairVariant = forcedStair?.variant || forcedParsed?.variant || null;
    const forcedAppliesTo = forcedStair?.appliesTo || 'end';
    const forcedContext = forcedStair?.reason || options?.restrictionContext || null;
    const forcedKeyActive = Boolean(forcedStairKey);
    
    // Parse floor numbers from room IDs
    const startFloor = parseFloorFromRoomId(startRoomId);
    const endFloor = parseFloorFromRoomId(endRoomId);

    if (startFloor == null || endFloor == null) {
        console.warn('Unable to determine floors for rooms', { startRoomId, endRoomId });
        return null;
    }

    // Handle restricted rooms for multi-floor routing
    await ensureFloorGraphLoaded(startFloor);
    await ensureFloorGraphLoaded(endFloor);
    
    const startGraph = floorGraphCache[startFloor];
    const endGraph = floorGraphCache[endFloor];
    const skipRestrictedStart = Boolean(options?.skipRestrictedStart);
    const skipRestrictedEnd = Boolean(options?.skipRestrictedEnd);
    
    const startRestriction = skipRestrictedStart ? null : getRestrictedAccessRule(startGraph, startRoomId);
    const endRestriction = skipRestrictedEnd ? null : getRestrictedAccessRule(endGraph, endRoomId);
    
    // CRITICAL: Pre-load Floor 2 if any room has a cross-floor restriction to Floor 2
    // This ensures getMandatoryEntryPoint can access the floor graph
    if (startRestriction) {
        const startRule = getRestrictedAccessRule(startGraph, startRoomId);
        if (startRule && startRule.entryPointFloor && startRule.entryPointFloor !== startFloor) {
            console.log(`[Pre-load] Loading Floor ${startRule.entryPointFloor} for restricted start room ${startRoomId}`);
            await ensureFloorGraphLoaded(startRule.entryPointFloor);
        }
    }
    if (endRestriction) {
        const endRule = getRestrictedAccessRule(endGraph, endRoomId);
        if (endRule && endRule.entryPointFloor && endRule.entryPointFloor !== endFloor) {
            console.log(`[Pre-load] Loading Floor ${endRule.entryPointFloor} for restricted end room ${endRoomId}`);
            await ensureFloorGraphLoaded(endRule.entryPointFloor);
        }
    }
    
    // SMART ROUTING FOR RESTRICTED ROOMS (4-3, 5-3, 6-3)
    // These rooms have virtual doorPoints at stair_east_2-2 (Floor 2)
    // but should use different stairs based on destination path
    // ONLY apply this when the room is on a DIFFERENT floor than its entry point
    
    if (startRestriction && !skipRestrictedStart) {
        const startEntry = getMandatoryEntryPoint(startGraph, startRoomId);
        
        // Only apply smart routing if:
        // 1. Entry point is stair_east_2-2 on Floor 2
        // 2. The start room is on a DIFFERENT floor (Floor 3) than the entry point (Floor 2)
        if (startEntry && startEntry.floor === 2 && startEntry.roomId === 'stair_east_2-2' && startFloor !== 2) {
            // This is one of the restricted rooms (4-3, 5-3, or 6-3) on Floor 3
            console.log(`[Smart Routing] ${startRoomId} (Floor ${startFloor}) starts at ${startEntry.roomId} (Floor 2)`);
            
            // Load Floor 2 to check destination path compatibility
            await ensureFloorGraphLoaded(2);
            const floor2Graph = floorGraphCache[2];
            
            // Get the destination's path - check even if on same floor because virtual location is on Floor 2
            // Need to check if destination is NOT also a restricted room (to avoid conflicts)
            const endRoom = endGraph.rooms[endRoomId];
            const endPathId = getPrimaryPathIdForRoom(endRoom);
            const destinationIsRestricted = endRestriction && !skipRestrictedEnd;
            
            console.log(`[Smart Routing] Destination ${endRoomId} on Floor ${endFloor}, path: ${endPathId}, isRestricted: ${destinationIsRestricted}`);
            
            // Only apply smart routing if destination is NOT restricted (to avoid conflicts with shared entry point logic)
            if (!destinationIsRestricted && (endFloor !== startFloor || endFloor === 3)) {
                // Changed condition: apply if different floor OR if same floor is Floor 3 (to route between restricted and non-restricted)
                
                console.log(`[Smart Routing] Processing route with endFloor=${endFloor}, startFloor=${startFloor}`);
                
                // Check if destination uses west or central paths - if so, use those exclusive stairs
                if (endFloor === 3 && endPathId) {
                    if (endPathId.includes('path1') || endPathId.includes('path3') || endPathId.includes('lobby_vertical_1')) {
                        // Use west stair (stair_thirdFloor_1-2 on Floor 2)
                        console.log(`[Smart Routing] Destination uses west path, routing via stair_thirdFloor_1-2`);
                        
                        // First, create Floor 2 route from stair_east_2-2 to stair_thirdFloor_1-2
                        const floor2Route = calculateSingleFloorRoute(floor2Graph, 'stair_east_2-2', 'stair_thirdFloor_1-2');
                        
                        // Then route from stair_thirdFloor_1-2 to destination on Floor 3
                        const downstreamRoute = await calculateMultiFloorRoute('stair_thirdFloor_1-2', endRoomId, {
                            ...options,
                            skipRestrictedStart: true,
                            restrictionContext: 'smart-routing-west'
                        });
                        
                        if (downstreamRoute && floor2Route) {
                            // Add Floor 2 segment to the beginning
                            const floor2Segment = {
                                type: 'walk',
                                floor: 2,
                                description: `Floor 2: Navigate to West Stair`,
                                points: floor2Route.points,
                                distance: floor2Route.distance,
                                startDoor: floor2Route.startDoor,
                                endDoor: floor2Route.endDoor
                            };
                            downstreamRoute.segments = [floor2Segment, ...downstreamRoute.segments];
                            addFloorToRoute(downstreamRoute, 2);
                            downstreamRoute.totalDistance = (downstreamRoute.totalDistance || 0) + floor2Route.distance;
                            
                            return augmentRouteWithRestrictedStart({
                                route: downstreamRoute,
                                startRoomId,
                                startFloor: 3,
                                entryPoint: { roomId: 'stair_thirdFloor_1-2', floor: 2, room: floor2Graph.rooms['stair_thirdFloor_1-2'] }
                            });
                        }
                        return null;
                    } else if (endPathId.includes('path2') || endPathId.includes('lobby_vertical_2') || endPathId.includes('lobby_vertical_3')) {
                        // Use central stair (stair_thirdFloor_2-2 on Floor 2)
                        console.log(`[Smart Routing] Destination uses central path, routing via stair_thirdFloor_2-2`);
                        
                        // First, create Floor 2 route from stair_east_2-2 to stair_thirdFloor_2-2
                        const floor2Route = calculateSingleFloorRoute(floor2Graph, 'stair_east_2-2', 'stair_thirdFloor_2-2');
                        
                        // Then route from stair_thirdFloor_2-2 to destination on Floor 3
                        const downstreamRoute = await calculateMultiFloorRoute('stair_thirdFloor_2-2', endRoomId, {
                            ...options,
                            skipRestrictedStart: true,
                            restrictionContext: 'smart-routing-central'
                        });
                        
                        if (downstreamRoute && floor2Route) {
                            // Add Floor 2 segment to the beginning
                            const floor2Segment = {
                                type: 'walk',
                                floor: 2,
                                description: `Floor 2: Navigate to Central Stair`,
                                points: floor2Route.points,
                                distance: floor2Route.distance,
                                startDoor: floor2Route.startDoor,
                                endDoor: floor2Route.endDoor
                            };
                            downstreamRoute.segments = [floor2Segment, ...downstreamRoute.segments];
                            addFloorToRoute(downstreamRoute, 2);
                            downstreamRoute.totalDistance = (downstreamRoute.totalDistance || 0) + floor2Route.distance;
                            
                            return augmentRouteWithRestrictedStart({
                                route: downstreamRoute,
                                startRoomId,
                                startFloor: 3,
                                entryPoint: { roomId: 'stair_thirdFloor_2-2', floor: 2, room: floor2Graph.rooms['stair_thirdFloor_2-2'] }
                            });
                        }
                        return null;
                    }
                }
                
                // For Floor 2 destinations, route from stair_east_2-2 to destination on Floor 2
                if (endFloor === 2) {
                    console.log(`[Smart Routing] Destination on Floor 2, routing via stair_east_2-2 on Floor 2`);
                    // Route from stair_east_2-2 (Floor 2) to destination on Floor 2 (same floor)
                    const downstreamRoute = await calculateMultiFloorRoute('stair_east_2-2', endRoomId, {
                        ...options,
                        skipRestrictedStart: true,
                        restrictionContext: 'smart-routing-floor2'
                    });
                    
                    if (downstreamRoute) {
                        console.log(`[Smart Routing] Successfully created Floor 2 route, augmenting with restricted start`);
                        return augmentRouteWithRestrictedStart({
                            route: downstreamRoute,
                            startRoomId,
                            startFloor: 3,
                            entryPoint: startEntry
                        });
                    } else {
                        console.error(`[Smart Routing] Failed to create downstream route from stair_east_2-2 to ${endRoomId} on Floor 2`);
                        return null;
                    }
                }
                
                // For Floor 1 destinations or Floor 3 east-side destinations, use stair_east_2-2 → stair_east_2-1
                if (endFloor === 1) {
                    console.log(`[Smart Routing] Destination on Floor 1, routing via stair_east_2-2 → stair_east_2-1`);
                    // Route from stair_east_2-2 (Floor 2) to destination
                    // The system will naturally use stair_east_2-1 to go down to Floor 1
                    const downstreamRoute = await calculateMultiFloorRoute('stair_east_2-2', endRoomId, {
                        ...options,
                        skipRestrictedStart: true,
                        restrictionContext: 'smart-routing-floor1'
                    });
                    
                    if (downstreamRoute) {
                        console.log(`[Smart Routing] Successfully created route, augmenting with restricted start`);
                        return augmentRouteWithRestrictedStart({
                            route: downstreamRoute,
                            startRoomId,
                            startFloor: 3,
                            entryPoint: startEntry
                        });
                    } else {
                        console.error(`[Smart Routing] Failed to create downstream route from stair_east_2-2 to ${endRoomId}`);
                        return null;
                    }
                }
                
                // If we reach here and haven't returned, it means we couldn't match any special routing
                // This could be routing between two restricted rooms (handled by shared entry point logic below)
                console.log(`[Smart Routing] No matching condition, continuing with normal flow`);
            } else {
                console.log(`[Smart Routing] Destination is restricted or same restricted floor, skipping smart routing (shared entry point logic will handle)`);
            }
        } else {
            console.log(`[Smart Routing] Start entry point is not stair_east_2-2, continuing with normal flow`);
        }
    } else {
        console.log(`[Smart Routing] No start restriction or already skipped, continuing with normal flow`);
    }

// Similar smart routing for when destination is a restricted room
    // ONLY apply this when the room is on a DIFFERENT floor than its entry point
    if (endRestriction && !skipRestrictedEnd) {
        const endEntry = getMandatoryEntryPoint(endGraph, endRoomId);
        
        // Only apply smart routing if:
        // 1. Entry point is stair_east_2-2 on Floor 2
        // 2. The end room is on a DIFFERENT floor (Floor 3) than the entry point (Floor 2)
        if (endEntry && endEntry.floor === 2 && endEntry.roomId === 'stair_east_2-2' && endFloor !== 2) {
            console.log(`[Smart Routing] ${endRoomId} (Floor ${endFloor}) ends at ${endEntry.roomId} (Floor 2)`);
            
            // Load Floor 2
            await ensureFloorGraphLoaded(2);
            const floor2Graph = floorGraphCache[2];
            
            // Check if start room uses west or central paths - check even if on same floor
            const startRoom = startGraph.rooms[startRoomId];
            const startPathId = getPrimaryPathIdForRoom(startRoom);
            const originIsRestricted = startRestriction && !skipRestrictedStart;
            
            console.log(`[Smart Routing] Origin ${startRoomId} on Floor ${startFloor}, path: ${startPathId}, isRestricted: ${originIsRestricted}`);
            
            // Only apply smart routing if origin is NOT restricted (to avoid conflicts with shared entry point logic)
            if (!originIsRestricted && (startFloor !== endFloor || startFloor === 3)) {
                // Changed condition: apply if different floor OR if same floor is Floor 3
                
                console.log(`[Smart Routing] Processing reverse route with startFloor=${startFloor}, endFloor=${endFloor}`);
                
                if (startFloor === 3 && startPathId) {
                    if (startPathId.includes('path1') || startPathId.includes('path3') || startPathId.includes('lobby_vertical_1')) {
                        // Route via west stair
                        console.log(`[Smart Routing] Origin uses west path, routing via stair_thirdFloor_1-2`);
                        const downstreamRoute = await calculateMultiFloorRoute(startRoomId, 'stair_thirdFloor_1-2', {
                            ...options,
                            skipRestrictedEnd: true,
                            restrictionContext: 'smart-routing-west-reverse'
                        });
                        
                        if (downstreamRoute) {
                            // Add Floor 2 route from stair_thirdFloor_1-2 to stair_east_2-2
                            const floor2Route = calculateSingleFloorRoute(floor2Graph, 'stair_thirdFloor_1-2', 'stair_east_2-2');
                            
                            if (floor2Route) {
                                const floor2Segment = {
                                    type: 'walk',
                                    floor: 2,
                                    description: `Floor 2: From West Stair to restricted room access point`,
                                    points: floor2Route.points,
                                    distance: floor2Route.distance,
                                    startDoor: floor2Route.startDoor,
                                    endDoor: floor2Route.endDoor
                                };
                                downstreamRoute.segments.push(floor2Segment);
                                addFloorToRoute(downstreamRoute, 2);
                                downstreamRoute.totalDistance = (downstreamRoute.totalDistance || 0) + floor2Route.distance;
                            }
                            
                            return augmentRouteWithRestrictedEnd({
                                route: downstreamRoute,
                                endRoomId,
                                endGraph,
                                entryPoint: { roomId: 'stair_thirdFloor_1-2', floor: 2, room: floor2Graph.rooms['stair_thirdFloor_1-2'] },
                                forcedStair: null
                            });
                        }
                        return null;
                    } else if (startPathId.includes('path2') || startPathId.includes('lobby_vertical_2') || startPathId.includes('lobby_vertical_3')) {
                        // Route via central stair
                        console.log(`[Smart Routing] Origin uses central path, routing via stair_thirdFloor_2-2`);
                        const downstreamRoute = await calculateMultiFloorRoute(startRoomId, 'stair_thirdFloor_2-2', {
                            ...options,
                            skipRestrictedEnd: true,
                            restrictionContext: 'smart-routing-central-reverse'
                        });
                        
                        if (downstreamRoute) {
                            // Add Floor 2 route from stair_thirdFloor_2-2 to stair_east_2-2
                            const floor2Route = calculateSingleFloorRoute(floor2Graph, 'stair_thirdFloor_2-2', 'stair_east_2-2');
                            
                            if (floor2Route) {
                                const floor2Segment = {
                                    type: 'walk',
                                    floor: 2,
                                    description: `Floor 2: From Central Stair to restricted room access point`,
                                    points: floor2Route.points,
                                    distance: floor2Route.distance,
                                    startDoor: floor2Route.startDoor,
                                    endDoor: floor2Route.endDoor
                                };
                                downstreamRoute.segments.push(floor2Segment);
                                addFloorToRoute(downstreamRoute, 2);
                                downstreamRoute.totalDistance = (downstreamRoute.totalDistance || 0) + floor2Route.distance;
                            }
                            
                            return augmentRouteWithRestrictedEnd({
                                route: downstreamRoute,
                                endRoomId,
                                endGraph,
                                entryPoint: { roomId: 'stair_thirdFloor_2-2', floor: 2, room: floor2Graph.rooms['stair_thirdFloor_2-2'] },
                                forcedStair: null
                            });
                        }
                        return null;
                    }
                }
                
                // For Floor 2 origins, route from origin to stair_east_2-2 on Floor 2
                if (startFloor === 2) {
                    console.log(`[Smart Routing] Origin on Floor 2, routing from ${startRoomId} to stair_east_2-2`);
                    const downstreamRoute = await calculateMultiFloorRoute(startRoomId, 'stair_east_2-2', {
                        ...options,
                        skipRestrictedEnd: true,
                        restrictionContext: 'smart-routing-floor2-reverse'
                    });
                    
                    if (downstreamRoute) {
                        console.log(`[Smart Routing] Successfully created Floor 2 route, augmenting with restricted end`);
                        return augmentRouteWithRestrictedEnd({
                            route: downstreamRoute,
                            endRoomId,
                            endGraph,
                            entryPoint: endEntry,
                            forcedStair: null
                        });
                    } else {
                        console.error(`[Smart Routing] Failed to create downstream route from ${startRoomId} to stair_east_2-2 on Floor 2`);
                        return null;
                    }
                }
                
                // For Floor 1 origins, use stair_east_2-1 → stair_east_2-2
                if (startFloor === 1) {
                    console.log(`[Smart Routing] Origin on Floor 1, routing via stair_east_2-1 → stair_east_2-2`);
                    const downstreamRoute = await calculateMultiFloorRoute(startRoomId, 'stair_east_2-2', {
                        ...options,
                        skipRestrictedEnd: true,
                        restrictionContext: 'smart-routing-floor1-reverse'
                    });
                    
                    if (downstreamRoute) {
                        console.log(`[Smart Routing] Successfully created route, augmenting with restricted end`);
                        return augmentRouteWithRestrictedEnd({
                            route: downstreamRoute,
                            endRoomId,
                            endGraph,
                            entryPoint: endEntry,
                            forcedStair: null
                        });
                    } else {
                        console.error(`[Smart Routing] Failed to create downstream route from ${startRoomId} to stair_east_2-2`);
                        return null;
                    }
                }
                
                console.log(`[Smart Routing] No matching condition for reverse route, continuing with normal flow`);
            } else {
                console.log(`[Smart Routing] Origin is restricted or same restricted floor, skipping smart routing (shared entry point logic will handle)`);
            }
        } else {
            console.log(`[Smart Routing] End entry point is not stair_east_2-2, continuing with normal flow`);
        }
    } else {
        console.log(`[Smart Routing] No end restriction or already skipped, continuing with normal flow`);
    }

    // SPECIAL CASE: Both rooms share the same cross-floor mandatory entry point
    // In this case, treat the entry point as the effective location for both rooms
    // and route on the entry point's floor only (e.g., room-4-3 to room-5-3 routes on Floor 2)
    if (!skipRestrictedStart && !skipRestrictedEnd && startRestriction && endRestriction) {
        const startEntry = getMandatoryEntryPoint(startGraph, startRoomId);
        const endEntry = getMandatoryEntryPoint(endGraph, endRoomId);
        
        // Check if both use the same entry point on the same floor
        if (startEntry && endEntry && 
            startEntry.roomId === endEntry.roomId && 
            startEntry.floor === endEntry.floor) {
            
            console.log(`Both ${startRoomId} and ${endRoomId} use same entry point ${startEntry.roomId} on Floor ${startEntry.floor} - routing on entry floor only`);
            
            await ensureFloorGraphLoaded(startEntry.floor);
            const entryFloorGraph = floorGraphCache[startEntry.floor];
            
            if (entryFloorGraph && entryFloorGraph.rooms[startEntry.roomId]) {
                const entryRoom = entryFloorGraph.rooms[startEntry.roomId];
                const entryDoors = getEntryPointsForRoom(entryRoom);
                
                if (entryDoors.length > 0) {
                    // Create a minimal route on the entry point's floor
                    // Both start and end effectively share the same physical location
                    const door = entryDoors[0];
                    
                    return {
                        type: startFloor === endFloor ? 'single-floor' : 'multi-floor',
                        startRoomId,
                        endRoomId,
                        floors: [startEntry.floor],
                        totalDistance: 0,
                        segments: [{
                            type: 'walk',
                            floor: startEntry.floor,
                            description: `Both rooms accessible via ${startEntry.roomId} on Floor ${startEntry.floor}`,
                            points: [door],
                            distance: 0,
                            startDoor: door,
                            endDoor: door,
                            restrictedRoute: true,
                            sharedMandatoryEntry: startEntry.roomId
                        }],
                        restrictedRoute: true,
                        mandatoryEntry: startEntry.roomId,
                        sharedEntryPoint: true
                    };
                }
            }
        }
    }
    
    // Check if start room has restricted access (already loaded earlier)
    // IMPORTANT: Skip this if smart routing already handled it
    if (startRestriction && !skipRestrictedStart) {
        const entryPoint = getMandatoryEntryPoint(startGraph, startRoomId);
        
        // Skip if this is a smart-routed restricted room (rooms 4-3, 5-3, 6-3)
        if (entryPoint && entryPoint.floor === 2 && entryPoint.roomId === 'stair_east_2-2') {
            console.log(`[Skip] ${startRoomId} already handled by smart routing`);
            // Don't process here - smart routing above should have handled it
            // If we reach here, it means smart routing decided not to handle it (same floor case)
        } else if (entryPoint) {
            console.log(`Multi-floor: Start room ${startRoomId} restricted, using entry point ${entryPoint.roomId} on floor ${entryPoint.floor}`);
            
            // If entry point is on a different floor, we need to handle it specially
            if (entryPoint.floor !== startFloor) {
                // Build route: startRoom's floor -> entry point floor -> destination
                // For now, just use the entry point as the effective start
                // The system will naturally route through the entry point floor
                
                // Load the entry point floor if needed
                await ensureFloorGraphLoaded(entryPoint.floor);
                
                const downstreamRoute = await calculateMultiFloorRoute(entryPoint.roomId, endRoomId, {
                    ...(options || {}),
                    skipRestrictedStart: true
                });

                if (!downstreamRoute) {
                    return null;
                }

                return augmentRouteWithRestrictedStart({
                    route: downstreamRoute,
                    startRoomId,
                    startFloor,
                    entryPoint
                });
            } else {
                // Entry point is on same floor as start room (normal case)
                return calculateMultiFloorRoute(entryPoint.roomId, endRoomId, {
                    ...(options || {}),
                    skipRestrictedStart: true
                });
            }
        }
    }
    
    // Check if end room has restricted access (already loaded earlier)
    // IMPORTANT: Skip this if smart routing already handled it
    if (endRestriction && !skipRestrictedEnd) {
        const entryPoint = getMandatoryEntryPoint(endGraph, endRoomId);
        
        // Skip if this is a smart-routed restricted room (rooms 4-3, 5-3, 6-3)
        if (entryPoint && entryPoint.floor === 2 && entryPoint.roomId === 'stair_east_2-2') {
            console.log(`[Skip] ${endRoomId} already handled by smart routing`);
            // Don't process here - smart routing above should have handled it
        } else if (entryPoint) {
            console.log(`Multi-floor: End room ${endRoomId} restricted, using entry point ${entryPoint.roomId} on floor ${entryPoint.floor}`);
            const entryStairInfo = parseStairId(entryPoint.roomId);
            const forcedOptions = {
                ...(options || {}),
                forcedStair: {
                    roomId: entryPoint.roomId,
                    floor: entryPoint.floor,
                    stairKey: entryPoint.room?.stairKey || entryStairInfo?.key || null,
                    variant: entryStairInfo?.variant || null,
                    appliesTo: 'end',
                    reason: 'mandatory-entry'
                },
                skipRestrictedEnd: true
            };

            await ensureFloorGraphLoaded(entryPoint.floor);

            const routeToEntry = await calculateMultiFloorRoute(startRoomId, entryPoint.roomId, forcedOptions);
            if (!routeToEntry) {
                return null;
            }

            return augmentRouteWithRestrictedEnd({
                route: routeToEntry,
                endRoomId,
                endGraph,
                entryPoint,
                forcedStair: forcedOptions.forcedStair
            });
        }
    }

    if (startFloor === endFloor) {
        const graph = startGraph;
        if (!graph) return null;

        // Check for restricted access that requires cross-floor routing (use existing variables)
        // startRestriction and endRestriction already loaded at top of function
        
        // CRITICAL: Check if either room requires cross-floor entry BEFORE attempting same-floor routing
        const startEntry = startRestriction ? getMandatoryEntryPoint(graph, startRoomId) : null;
        const endEntry = endRestriction ? getMandatoryEntryPoint(graph, endRoomId) : null;
        const startRequiresCrossFloor = startEntry && startEntry.floor !== startFloor;
        const endRequiresCrossFloor = endEntry && endEntry.floor !== endFloor;
        
        // If ANY room has cross-floor restriction, skip same-floor routing entirely
        if (startRequiresCrossFloor || endRequiresCrossFloor) {
            console.log(`Cross-floor restriction detected for same-floor rooms:`, {
                startRoomId,
                endRoomId,
                floor: startFloor,
                startRequiresCrossFloor,
                endRequiresCrossFloor,
                startEntryFloor: startEntry?.floor,
                endEntryFloor: endEntry?.floor
            });
            // Skip all same-floor logic and fall through to multi-floor routing below
            // (The multi-floor logic after line 2163 will handle this correctly)
        } else if (startRestriction) {
            // Start is restricted but entry is on same floor
            const startEntry = getMandatoryEntryPoint(graph, startRoomId);
            if (!endRestriction) {
                // Start is restricted but on same floor, end is not restricted
                const route = calculateSingleFloorRoute(graph, startRoomId, endRoomId);
                if (route) {
                    return {
                        type: 'single-floor',
                        startRoomId,
                        endRoomId,
                        floors: [startFloor],
                        totalDistance: route.distance,
                        segments: [{
                            type: 'walk',
                            floor: startFloor,
                            description: `Floor ${startFloor}: Route from ${startRoomId} to ${endRoomId}`,
                            points: route.points,
                            distance: route.distance,
                            startDoor: route.startDoor,
                            endDoor: route.endDoor
                        }]
                    };
                }
            }
        } else if (endRestriction) {
            // End is restricted but entry is on same floor
            const endEntry = getMandatoryEntryPoint(graph, endRoomId);
            if (!startRestriction) {
                // End is restricted but on same floor, start is not restricted
                const route = calculateSingleFloorRoute(graph, startRoomId, endRoomId);
                if (route) {
                    return {
                        type: 'single-floor',
                        startRoomId,
                        endRoomId,
                        floors: [startFloor],
                        totalDistance: route.distance,
                        segments: [{
                            type: 'walk',
                            floor: startFloor,
                            description: `Floor ${startFloor}: Route from ${startRoomId} to ${endRoomId}`,
                            points: route.points,
                            distance: route.distance,
                            startDoor: route.startDoor,
                            endDoor: route.endDoor
                        }]
                    };
                }
            }
        } else if (startRestriction && endRestriction) {
            // Both restricted with same-floor entries
            const startEntry = getMandatoryEntryPoint(graph, startRoomId);
            const endEntry = getMandatoryEntryPoint(graph, endRoomId);
            
            if (startEntry && endEntry && 
                startEntry.floor === startFloor && 
                endEntry.floor === endFloor) {
                // Both on same floor with same-floor entries
                const route = calculateSingleFloorRoute(graph, startRoomId, endRoomId);
                if (route) {
                    return {
                        type: 'single-floor',
                        startRoomId,
                        endRoomId,
                        floors: [startFloor],
                        totalDistance: route.distance,
                        segments: [{
                            type: 'walk',
                            floor: startFloor,
                            description: `Floor ${startFloor}: Route from ${startRoomId} to ${endRoomId}`,
                            points: route.points,
                            distance: route.distance,
                            startDoor: route.startDoor,
                            endDoor: route.endDoor
                        }]
                    };
                }
            }
        }
        
        // No restrictions or restrictions don't require cross-floor - try normal same-floor route
        if (!startRestriction && !endRestriction) {
            const startRoom = graph.rooms ? graph.rooms[startRoomId] : null;
            const endRoom = graph.rooms ? graph.rooms[endRoomId] : null;

            if (startRoom && endRoom) {
                const startPathId = getPrimaryPathIdForRoom(startRoom);
                const endPathId = getPrimaryPathIdForRoom(endRoom);

                if (shouldForceStairTransition(graph, startPathId, endPathId)) {
                    const constrainedRoute = await calculateConstrainedSameFloorRoute({
                        floorNumber: startFloor,
                        graph,
                        startRoomId,
                        endRoomId,
                        startPathId,
                        endPathId
                    });

                    if (constrainedRoute) {
                        return constrainedRoute;
                    }
                }
            }

            const route = calculateSingleFloorRoute(graph, startRoomId, endRoomId);
            if (route) {
                return {
                    type: 'single-floor',
                    startRoomId,
                    endRoomId,
                    floors: [startFloor],
                    totalDistance: route.distance,
                    segments: [{
                        type: 'walk',
                        floor: startFloor,
                        description: `Floor ${startFloor}: Route from ${startRoomId} to ${endRoomId}`,
                        points: route.points,
                        distance: route.distance,
                        startDoor: route.startDoor,
                        endDoor: route.endDoor
                    }]
                };
            }
        }
        
        // If we reach here and have cross-floor restrictions, fall through to multi-floor logic below
        // Otherwise, return null as we couldn't find a route
        const hasCrossFloorRestriction = 
            (startRestriction && getMandatoryEntryPoint(graph, startRoomId)?.floor !== startFloor) ||
            (endRestriction && getMandatoryEntryPoint(graph, endRoomId)?.floor !== endFloor);
        
        if (!hasCrossFloorRestriction) {
            return null;
        }
        // Fall through to multi-floor routing logic
    }

    const floorRange = [];
    const step = startFloor < endFloor ? 1 : -1;
    for (let f = startFloor; step > 0 ? f <= endFloor : f >= endFloor; f += step) {
        floorRange.push(f);
    }

    await Promise.all([...new Set(floorRange)].map(ensureFloorGraphLoaded));

    // Use the already declared startGraph and endGraph from above
    const startRoom = startGraph && startGraph.rooms ? startGraph.rooms[startRoomId] : null;
    const endRoom = endGraph && endGraph.rooms ? endGraph.rooms[endRoomId] : null;
    const startPathId = getPrimaryPathIdForRoom(startRoom);
    const endPathId = getPrimaryPathIdForRoom(endRoom);

    const transitionsPerStep = [];
    for (let i = 0; i < floorRange.length - 1; i++) {
        const floorA = floorRange[i];
        const floorB = floorRange[i + 1];
        const transitions = findStairTransitionsBetweenFloors(floorA, floorB);
        if (!transitions.length) {
            console.warn('No stair transitions available between floors', floorA, floorB);
            return null;
        }
        transitionsPerStep.push(transitions);
    }

    let constrainedStairKeys = determineCandidateStairKeys(startGraph, startPathId, endGraph, endPathId);
    if (!constrainedStairKeys || !constrainedStairKeys.length) {
        constrainedStairKeys = null;
    }
    const requiredStartVariant = getRequiredStairVariantForPath(startGraph, startPathId);
    const rawRequiredEndVariant = getRequiredStairVariantForPath(endGraph, endPathId);
    const effectiveEndVariant = forcedStairVariant
        ? { stairKey: forcedStairKey, variant: forcedStairVariant }
        : rawRequiredEndVariant;

    if (forcedKeyActive && forcedStairKey) {
        if (constrainedStairKeys && !constrainedStairKeys.includes(forcedStairKey)) {
            constrainedStairKeys = [...constrainedStairKeys, forcedStairKey];
        } else if (!constrainedStairKeys) {
            // Leave unconstrained when we have no existing restrictions; forcing here would block valid transitions.
            console.log('Forced stair provided without existing constraints; keeping transitions unconstrained.', {
                forcedStairKey,
                context: forcedContext || forcedAppliesTo
            });
        }
    }

    console.log('Multi-floor pathfinding constraints:', {
        startRoomId,
        endRoomId,
        startPathId,
        endPathId,
        constrainedStairKeys,
        requiredStartVariant,
        requiredEndVariant: rawRequiredEndVariant,
        effectiveEndVariant,
        forcedStairKey,
        forcedStairVariant,
        forcedContext: forcedKeyActive ? (forcedContext || forcedAppliesTo) : null
    });
    
    if (constrainedStairKeys && constrainedStairKeys.length) {
        for (let i = 0; i < transitionsPerStep.length; i++) {
            const isFirstTransition = i === 0;
            const isLastTransition = i === transitionsPerStep.length - 1;
            const enforceStartVariant = !(forcedKeyActive && forcedAppliesTo !== 'start' && requiredStartVariant && requiredStartVariant.stairKey !== forcedStairKey);
            
            transitionsPerStep[i] = transitionsPerStep[i].filter(transition => {
                // Must match allowed stair keys
                if (!constrainedStairKeys.includes(transition.stairKey)) {
                    console.log(`Rejecting transition ${transition.startNode.roomId} -> ${transition.endNode.roomId}: stairKey ${transition.stairKey} not in allowed keys`, constrainedStairKeys);
                    return false;
                }
                
                // For first transition, enforce start room's required variant
                if (isFirstTransition && enforceStartVariant && requiredStartVariant && transition.stairKey === requiredStartVariant.stairKey) {
                    const startParsed = parseStairId(transition.startNode.roomId);
                    if (!startParsed || startParsed.variant !== requiredStartVariant.variant) {
                        console.log(`Rejecting first transition ${transition.startNode.roomId}: variant ${startParsed?.variant} doesn't match required ${requiredStartVariant.variant}`);
                        return false;
                    }
                }

                // For last transition, enforce destination room's required variant
                if (isLastTransition && effectiveEndVariant && transition.stairKey === effectiveEndVariant.stairKey) {
                    const endParsed = parseStairId(transition.endNode.roomId);
                    if (!endParsed || endParsed.variant !== effectiveEndVariant.variant) {
                        console.log(`Rejecting last transition ${transition.endNode.roomId}: variant ${endParsed?.variant} doesn't match required ${effectiveEndVariant.variant}`);
                        return false;
                    }
                }
                
                // ENHANCED: For multi-step transitions, enforce variant consistency throughout
                // If we have a required variant from the start, use it for ALL transitions
                if (enforceStartVariant && requiredStartVariant && transition.stairKey === requiredStartVariant.stairKey) {
                    const startParsed = parseStairId(transition.startNode.roomId);
                    const endParsed = parseStairId(transition.endNode.roomId);
                    
                    // Both ends of the transition should match the required variant
                    if (startParsed && startParsed.variant !== requiredStartVariant.variant) {
                        console.log(`Rejecting transition start ${transition.startNode.roomId}: variant ${startParsed.variant} doesn't match required ${requiredStartVariant.variant}`);
                        return false;
                    }
                    if (endParsed && endParsed.variant !== requiredStartVariant.variant) {
                        console.log(`Rejecting transition end ${transition.endNode.roomId}: variant ${endParsed.variant} doesn't match required ${requiredStartVariant.variant}`);
                        return false;
                    }
                }
                
                // Similarly for end variant if different from start
                if (effectiveEndVariant && effectiveEndVariant.stairKey !== requiredStartVariant?.stairKey && transition.stairKey === effectiveEndVariant.stairKey) {
                    const startParsed = parseStairId(transition.startNode.roomId);
                    const endParsed = parseStairId(transition.endNode.roomId);
                    
                    if (startParsed && startParsed.variant !== effectiveEndVariant.variant) {
                        console.log(`Rejecting transition start ${transition.startNode.roomId}: variant ${startParsed.variant} doesn't match end required ${effectiveEndVariant.variant}`);
                        return false;
                    }
                    if (endParsed && endParsed.variant !== effectiveEndVariant.variant) {
                        console.log(`Rejecting transition end ${transition.endNode.roomId}: variant ${endParsed.variant} doesn't match end required ${effectiveEndVariant.variant}`);
                        return false;
                    }
                }
                
                console.log(`Accepting transition ${transition.startNode.roomId} -> ${transition.endNode.roomId}`);
                return true;
            });
            
            if (!transitionsPerStep[i].length) {
                console.warn('No allowable transitions remain after applying path access constraints', {
                    floorRange,
                    constrainedStairKeys,
                    requiredStartVariant,
                    requiredEndVariant: rawRequiredEndVariant,
                    effectiveEndVariant,
                    startPathId,
                    endPathId
                });
                return null;
            }
        }
    }

    const createWalkSegment = (floor, description, route, stairKey) => ({
        type: 'walk',
        floor,
        description,
        points: route.points,
        distance: route.distance,
        startDoor: route.startDoor,
        endDoor: route.endDoor,
        via: stairKey || null,
        startPathId: route.startPathId,
        endPathId: route.endPathId
    });

    const evaluateTransitions = (stepIndex, currentFloor, currentRoomId, segments, distanceSoFar, stairUsages) => {
        if (stepIndex >= transitionsPerStep.length) {
            if (currentFloor !== endFloor) {
                return null;
            }

            const finalGraph = floorGraphCache[currentFloor];
            if (!finalGraph) {
                return null;
            }

            const finalRoute = calculateSingleFloorRoute(finalGraph, currentRoomId, endRoomId);
            if (!finalRoute) {
                return null;
            }

            const finalSegments = [...segments];
            let totalDistance = distanceSoFar;

            if (finalRoute.distance > 0) {
                finalSegments.push(createWalkSegment(currentFloor, `Floor ${currentFloor}: Continue to ${endRoomId}`, finalRoute));
                totalDistance += finalRoute.distance;
            }

            return {
                segments: finalSegments,
                totalDistance,
                stairUsages: [...stairUsages]
            };
        }

        const transitions = transitionsPerStep[stepIndex];
        let bestResult = null;

        transitions.forEach(transition => {
            const graph = floorGraphCache[currentFloor];
            if (!graph) {
                return;
            }

            const approachRoute = calculateSingleFloorRoute(graph, currentRoomId, transition.startNode.roomId);
            if (!approachRoute) {
                return;
            }

            const updatedSegments = [...segments];
            let updatedDistance = distanceSoFar;

            if (approachRoute.distance > 0) {
                const stairLabel = transition.startNode.label || transition.startNode.roomId;
                updatedSegments.push(createWalkSegment(currentFloor, `Floor ${currentFloor}: Proceed to ${stairLabel}`, approachRoute, transition.stairKey));
                updatedDistance += approachRoute.distance;
            }

            const stairName = describeStairKey(transition.stairKey);
            updatedSegments.push({
                type: 'stair',
                stairKey: transition.stairKey,
                description: `Take ${stairName} to Floor ${transition.toFloor}`,
                fromFloor: transition.fromFloor,
                toFloor: transition.toFloor,
                floors: [transition.fromFloor, transition.toFloor],
                floorSpan: Math.abs(transition.toFloor - transition.fromFloor),
                startRoomId: transition.startNode.roomId,
                endRoomId: transition.endNode.roomId
            });

            const result = evaluateTransitions(
                stepIndex + 1,
                transition.toFloor,
                transition.endNode.roomId,
                updatedSegments,
                updatedDistance,
                [...stairUsages, transition]
            );

            if (result) {
                if (!bestResult || result.totalDistance < bestResult.totalDistance) {
                    bestResult = result;
                }
            }
        });

        return bestResult;
    };

    const evaluatedRoute = evaluateTransitions(0, startFloor, startRoomId, [], 0, []);

    if (!evaluatedRoute) {
        console.warn('Unable to evaluate a viable multi-floor route', { startRoomId, endRoomId, floorRange });
        return null;
    }

    const stairSequence = evaluatedRoute.stairUsages.map(transition => ({
        stairKey: transition.stairKey,
        fromFloor: transition.fromFloor,
        toFloor: transition.toFloor,
        startRoomId: transition.startNode.roomId,
        endRoomId: transition.endNode.roomId
    }));

    const route = {
        type: 'multi-floor',
        startRoomId,
        endRoomId,
        floors: floorRange,
        totalDistance: evaluatedRoute.totalDistance,
        segments: evaluatedRoute.segments,
        stairSequence,
        stairKeys: stairSequence.map(item => item.stairKey)
    };

    if (stairSequence.length === 1) {
        route.stairKey = stairSequence[0].stairKey;
        route.stairName = describeStairKey(stairSequence[0].stairKey);
    }

    return route;
}

async function activateRouteBetweenRooms(startRoomId, endRoomId) {
    const route = await calculateMultiFloorRoute(startRoomId, endRoomId);
    if (!route) {
        return null;
    }
    setActiveRoute(route);
    renderActiveRouteForFloor(currentFloorNumber);
    broadcastRoute(route);
    return route;
}

// Room click handler - separated for easier management
async function roomClickHandler(event) {
    // Use the global floorGraph if in mobile mode, otherwise use the local one
    const graph = window.MOBILE_MODE ? window.floorGraph : floorGraph;

    const roomId = this.id;
    console.log('Room clicked:', roomId);
    console.log('Current graph available:', !!graph);
    console.log('Graph has rooms:', !!graph && !!graph.rooms);
    console.log('Available room IDs:', graph && graph.rooms ? Object.keys(graph.rooms) : 'No rooms');

    // Check if graph data is available
    if (!graph || !graph.rooms) {
        console.error('Floor graph data not available. Graph:', graph);
        alert('Navigation data not loaded yet. Please wait a moment and try again.');
        return;
    }

    // If clicking the same room that's already selected, ignore
    if (selectedRooms.includes(roomId)) {
        console.log('Room already selected');
        return;
    }
    
    // If we already have 2 rooms selected, clear the selection
    if (selectedRooms.length >= 2) {
        selectedRooms.forEach(id => {
            let el = document.getElementById(id);
            if (el) el.classList.remove('selected-room');
        });
        selectedRooms = [];
        clearAllPaths();
        setActiveRoute(null);
    }

    // Add the clicked room to selection
    selectedRooms.push(roomId);
    this.classList.add('selected-room');
    console.log('Selected rooms so far:', selectedRooms);

    // Get the clicked room's data
    const clickedRoom = graph.rooms[roomId];
    if (!clickedRoom) {
        console.error('Room data not found for room ID:', roomId);
        console.error('Available rooms in graph:', Object.keys(graph.rooms || {}));
        console.error('Full graph structure:', graph);
        alert(`Room "${roomId}" not found in navigation data. Please try a different room.`);
        return;
    }

    const entryPoints = getEntryPointsForRoom(clickedRoom);

    // For restricted rooms with no entry points on this floor, allow selection
    // The routing logic will handle them via their mandatory entry point
    const isRestrictedRoom = getRestrictedAccessRule(graph, roomId) !== null;
    
    if (entryPoints.length === 0 && !isRestrictedRoom) {
        console.error('No entry points found for this room and no restriction rules');
        return;
    }
    
    // Filter out virtual doorPoints - these are for UI selection only, not actual routing
    const realEntryPoints = entryPoints.filter(ep => !ep.virtual);
    
    // For restricted rooms, we may have no real entry points on this floor
    // The routing will use the mandatory entry point from another floor
    const entryPointsToUse = realEntryPoints.length > 0 ? realEntryPoints : entryPoints;

    // Only process entry point selection if we have entry points on this floor
    let nearestEntryPoint = null;
    let clickedPathPoint = null;
    
    if (entryPointsToUse.length > 0) {
        // Find the nearest entry point on the clicked room to the click event
        const rect = this.getBoundingClientRect();
        const svg = this.closest('svg');
        const pt = svg.createSVGPoint();
        pt.x = event.clientX;
        pt.y = event.clientY;
        const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());
        
        nearestEntryPoint = entryPointsToUse[0];
        if (entryPointsToUse.length > 1) {
            let minDistance = Infinity;
            entryPointsToUse.forEach(entryPoint => {
                const distance = getDistance({x: svgP.x, y: svgP.y}, entryPoint);
                if (distance < minDistance) {
                    minDistance = distance;
                    nearestEntryPoint = entryPoint;
                }
            });
        }

        // Find nearest point on the path for clicked room (if path exists)
        if (clickedRoom.nearestPathId) {
            const clickedPath = graph.walkablePaths.find(p => p.id === clickedRoom.nearestPathId);
            if (clickedPath && nearestEntryPoint) {
                clickedPathPoint = findNearestPointOnPath(nearestEntryPoint, clickedPath.pathPoints);
            }
        }
    }

    // Clear existing paths
    clearAllPaths();

    if (selectedRooms.length === 1) {
        // For rooms with real entry points and valid paths, show connection
        if (clickedPathPoint && nearestEntryPoint && !nearestEntryPoint.virtual) {
            drawCompletePath([
                nearestEntryPoint,
                clickedPathPoint
            ]);
        } else {
            console.log('First room selected (restricted or no path on this floor):', roomId);
            // Don't draw anything for virtual entry points - wait for second selection
        }
    } else if (selectedRooms.length === 2) {
        const [startRoomId, endRoomId] = selectedRooms;
        console.log('Attempting to compute route between rooms:', startRoomId, '->', endRoomId);

        try {
            const route = await activateRouteBetweenRooms(startRoomId, endRoomId);

            if (!route) {
                console.error('No route could be calculated between the selected rooms.');
                alert('No available route between the selected rooms. Please verify that stair data exists for both floors.');
                return;
            }

            if (route.type === 'multi-floor') {
                const stairNames = Array.isArray(route.stairSequence) && route.stairSequence.length
                    ? [...new Set(route.stairSequence.map(item => describeStairKey(item.stairKey)))]
                    : (route.stairKey ? [describeStairKey(route.stairKey)] : []);
                if (stairNames.length) {
                    console.log(`Multi-floor route computed using ${stairNames.join(', ')}.`);
                } else {
                    console.log('Multi-floor route computed.');
                }
            }
        } catch (error) {
            console.error('Error while calculating multi-floor route:', error);
            alert('Unable to calculate a route between the selected rooms. Please try again.');
        }
    }
}

// Heuristic: Euclidean distance
function heuristic(a, b) {
  const graph = window.MOBILE_MODE ? window.floorGraph : floorGraph;
  
  // Safety check for graph and rooms
  if (!graph || !graph.rooms || !graph.rooms[a] || !graph.rooms[b]) {
    console.error('Heuristic calculation failed - missing room data:', {
      graphExists: !!graph,
      roomsExists: !!graph?.rooms,
      roomAExists: !!graph?.rooms?.[a],
      roomBExists: !!graph?.rooms?.[b],
      roomA: a,
      roomB: b
    });
    return Infinity; // Return a high value to avoid using invalid paths
  }
  
  const dx = graph.rooms[a].x - graph.rooms[b].x;
  const dy = graph.rooms[a].y - graph.rooms[b].y;
  return Math.sqrt(dx * dx + dy * dy);
}

// A* algorithm
function aStar(start, goal) {
  const graph = window.MOBILE_MODE ? window.floorGraph : floorGraph;
  console.log('A* starting with:', start, '->', goal);
  
  // Safety check - ensure graph and rooms exist
  if (!graph || !graph.rooms) {
    console.error('A* failed - no graph or rooms data available');
    return null;
  }
  
  // Safety check - ensure both nodes exist in graph
  if (!graph.rooms[start] || !graph.rooms[goal]) {
    console.error('A* failed - start or goal node not found in graph:', {
      start: start,
      goal: goal,
      startExists: !!graph.rooms[start],
      goalExists: !!graph.rooms[goal],
      availableRooms: Object.keys(graph.rooms)
    });
    return null;
  }
  
  let openSet = [start];
  let cameFrom = {};
  let gScore = { [start]: 0 };
  let fScore = { [start]: heuristic(start, goal) };
  let iterations = 0;
  const maxIterations = 1000; // Safety limit

  while (openSet.length > 0 && iterations < maxIterations) {
    iterations++;
    
    // Node in openSet with lowest fScore
    let current = openSet.reduce((a, b) => fScore[a] < fScore[b] ? a : b);
    
    console.log(`Iteration ${iterations}: Current node = ${current}, Open set size = ${openSet.length}`);

    if (current === goal) {
      console.log('Goal reached! Reconstructing path...');
      console.log('cameFrom object:', cameFrom);
      
      // Reconstruct path
      let path = [current];
      let pathLength = 0;
      const maxPathLength = 100; // Safety limit for path reconstruction
      
      while (cameFrom[current] && pathLength < maxPathLength) {
        pathLength++;
        current = cameFrom[current];
        path.unshift(current);
        console.log(`Path reconstruction step ${pathLength}: ${current}`);
      }
      
      if (pathLength >= maxPathLength) {
        console.error('Path reconstruction exceeded maximum length - possible circular reference');
        return null;
      }
      
      console.log('Path found:', path);
      return path;
    }

    openSet = openSet.filter(n => n !== current);
    
    // Check neighbors
    if (!graph.rooms[current] || !graph.rooms[current].neighbors) {
      console.log(`No neighbors found for ${current}`);
      continue;
    }
    
    for (let neighbor of Object.keys(graph.rooms[current].neighbors)) {
      // Safety check - ensure neighbor exists
      if (!graph.rooms[neighbor]) {
        console.warn(`Neighbor ${neighbor} not found in graph, skipping.`);
        continue;
      }
      
      let tentative_gScore = gScore[current] + heuristic(current, neighbor);
      if (tentative_gScore < (gScore[neighbor] || Infinity)) {
        // Only set cameFrom if this is a better path AND it's not the start node
        if (neighbor !== start) {
          cameFrom[neighbor] = current;
        }
        gScore[neighbor] = tentative_gScore;
        fScore[neighbor] = gScore[neighbor] + heuristic(neighbor, goal);
        if (!openSet.includes(neighbor)) {
          openSet.push(neighbor);
          console.log(`Added ${neighbor} to open set`);
        }
      }
    }
  }
  
  if (iterations >= maxIterations) {
    console.log('A* algorithm exceeded maximum iterations');
  } else {
    console.log('No path found - open set empty');
  }
  return null; // No path found
}

// Highlight path on SVG
function highlightPath(path) {
  console.log('Starting to highlight path:', path);
  
  // Remove previous highlights and path lines
  const previousHighlights = document.querySelectorAll('.path-highlight');
  console.log('Removing', previousHighlights.length, 'previous highlights');
  previousHighlights.forEach(el => {
    el.classList.remove('path-highlight');
  });
  
  // Remove previous path lines
  const previousPathLines = document.querySelectorAll('.path-line');
  previousPathLines.forEach(el => el.remove());
  
  // Add highlight to each room in the path
  path.forEach((roomId, index) => {
    console.log(`Highlighting room ${index + 1}/${path.length}:`, roomId);
    
    // Try to find the room element
    let el = document.getElementById(roomId);
    if (el) {
      console.log(`Found element for ${roomId}, adding highlight`);
      console.log('Element details:', {
        tagName: el.tagName,
        id: el.id,
        className: el.className,
        style: el.style.cssText
      });
      
      // If it's a group element, find the path inside it
      if (el.tagName === 'G') {
        const pathElement = el.querySelector('path');
        if (pathElement) {
          console.log('Found path element inside group, highlighting path instead');
          pathElement.classList.add('path-highlight');
          console.log('After adding class to path:', pathElement.className);
        } else {
          console.log('No path found inside group, highlighting group');
          el.classList.add('path-highlight');
          console.log('After adding class to group:', el.className);
        }
      } else {
        el.classList.add('path-highlight');
        console.log('After adding class:', el.className);
      }
    } else {
      console.warn(`Element not found for room: ${roomId}`);
      
      // Try alternative selectors
      const alternativeSelectors = [
        `path[id="${roomId}"]`,
        `rect[id="${roomId}"]`,
        `g[id="${roomId}"] path`,
        `g[id="${roomId}"] rect`
      ];
      
      for (let selector of alternativeSelectors) {
        el = document.querySelector(selector);
        if (el) {
          console.log(`Found element using selector: ${selector}`);
          el.classList.add('path-highlight');
          break;
        }
      }
      
      if (!el) {
        console.error(`Could not find any element for room: ${roomId}`);
      }
    }
  });
  
  // Draw path lines connecting the rooms
  drawPathLines(path);
  
  console.log('Path highlighting completed');
}

// Draw lines connecting the rooms in the path
function clearAllPaths() {
    const svg = document.querySelector('svg');
    if (!svg) return;

    const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
    if (!mainGroup) return;

    const pathGroup = mainGroup.querySelector('#path-highlight-group');
    if (pathGroup) {
        while (pathGroup.firstChild) {
            pathGroup.removeChild(pathGroup.firstChild);
        }
    }
}

function getPathBetweenPoints(start, end, walkablePaths, options = {}) {
    const graphContext = options.graph || (window.MOBILE_MODE ? window.floorGraph : floorGraph);
    console.log('Finding path between', start, 'and', end);
    
    // Convert walkable paths into a graph structure for A*
    const g = {};
    const processedPoints = new Set(); // Track unique points
    
    // First, create all points in the graph and build connections
    walkablePaths.forEach(path => {
        let previousPoint = null;
        
        path.pathPoints.forEach((point) => {
            const roundedX = Math.round(point.x * 100) / 100; // Round to 2 decimal places
            const roundedY = Math.round(point.y * 100) / 100;
            const pointId = `${roundedX},${roundedY}`;
            
            // Skip if this point is too close to the previous point (within 1 pixel)
            if (previousPoint && 
                Math.abs(previousPoint.x - point.x) < 1 && 
                Math.abs(previousPoint.y - point.y) < 1) {
                return;
            }

            // Initialize the point in the graph if it doesn't exist
            if (!g[pointId]) {
                g[pointId] = {
                    x: roundedX,
                    y: roundedY,
                    neighbors: {}
                };
            }

            // Connect to the previous point in this path (bidirectional)
            if (previousPoint) {
                const prevPointId = `${Math.round(previousPoint.x * 100) / 100},${Math.round(previousPoint.y * 100) / 100}`;
                const distance = Math.sqrt(Math.pow(point.x - previousPoint.x, 2) + Math.pow(point.y - previousPoint.y, 2));
                
                g[pointId].neighbors[prevPointId] = distance;
                if (g[prevPointId]) {
                    g[prevPointId].neighbors[pointId] = distance;
                }
            }

            previousPoint = point;
        });
    });

    // Add connections from pathConnections if they exist in the floor graph
    if (graphContext && graphContext.pathConnections) {
        Object.keys(graphContext.pathConnections).forEach(intersectionPoint => {
            const connections = graphContext.pathConnections[intersectionPoint];
            
            // Find the actual point in the graph that corresponds to the intersection
            connections.forEach((connection, index) => {
                if (index === 0) return; // Skip first connection (no previous to connect to)
                
                const prevConnection = connections[index - 1];
                
                // Create a bidirectional connection between this point and the previous one
                if (g[intersectionPoint] && g[prevConnection]) {
                    const distance = getDistance(g[intersectionPoint], g[prevConnection]);
                    g[intersectionPoint].neighbors[prevConnection] = distance;
                    g[prevConnection].neighbors[intersectionPoint] = distance;
                }
            });
        });
    }

    // Add start and end points to the graph
    const startId = `${Math.round(start.x * 100) / 100},${Math.round(start.y * 100) / 100}`;
    const endId = `${Math.round(end.x * 100) / 100},${Math.round(end.y * 100) / 100}`;
    
    console.log('Finding nearest points for start and end');
    
    // Find closest points on paths for start and end
    let startNearestId = null;
    let endNearestId = null;
    let minStartDist = Infinity;
    let minEndDist = Infinity;
    
    // Find nearest points from the graph
    Object.keys(g).forEach(pointId => {
        const point = g[pointId];
        const startDist = getDistance(start, point);
        const endDist = getDistance(end, point);
        
        if (startDist < minStartDist) {
            minStartDist = startDist;
            startNearestId = pointId;
        }
        if (endDist < minEndDist) {
            minEndDist = endDist;
            endNearestId = pointId;
        }
    });
    
    console.log('Nearest points found:', {
        start: startNearestId,
        end: endNearestId,
        startDist: minStartDist,
        endDist: minEndDist
    });
    
    // Add start and end to graph with connections to nearest path points
    if (!g[startId]) {
        g[startId] = {
            x: start.x,
            y: start.y,
            neighbors: {}
        };
    }
    if (!g[endId]) {
        g[endId] = {
            x: end.x,
            y: end.y,
            neighbors: {}
        };
    }
    
    // Connect start and end to their nearest points
    if (startNearestId) {
        g[startId].neighbors[startNearestId] = minStartDist;
        g[startNearestId].neighbors[startId] = minStartDist;
    }
    
    if (endNearestId) {
        g[endId].neighbors[endNearestId] = minEndDist;
        g[endNearestId].neighbors[endId] = minEndDist;
    }
    
    // Use A* to find path through the graph
    const path = aStarSearch(g, startId, endId);
    
    // Convert path IDs back to points
    if (path) {
        return path.map(id => ({
            x: g[id].x,
            y: g[id].y
        }));
    }
    
    // If no path found, return direct line
    return [start, end];
}

function aStarSearch(graph, startId, endId) {
    console.log('Starting A* search from', startId, 'to', endId);
    
    // If start and end are the same, return immediately
    if (startId === endId) {
        return [startId];
    }
    
    const openSet = new Set([startId]);
    const closedSet = new Set(); // Track visited nodes
    const cameFrom = {};
    const gScore = { [startId]: 0 };
    const fScore = { [startId]: getDistance(graph[startId], graph[endId]) };
    
    let iterations = 0;
    const maxIterations = 100; // Reduced limit for safety
    
    while (openSet.size > 0 && iterations < maxIterations) {
        iterations++;
        
        // Find node in openSet with lowest fScore
        let current = null;
        let lowestF = Infinity;
        openSet.forEach(nodeId => {
            if (fScore[nodeId] < lowestF) {
                lowestF = fScore[nodeId];
                current = nodeId;
            }
        });
        
        if (!current) {
            console.log('No current node found in open set');
            break;
        }
        
        console.log(`Iteration ${iterations}: Current node = ${current}`);
        
        if (current === endId) {
            console.log('Found path!');
            // Reconstruct path
            const path = [current];
            while (cameFrom[current]) {
                current = cameFrom[current];
                path.unshift(current);
            }
            console.log('Path found:', path);
            return path;
        }
        
        openSet.delete(current);
        closedSet.add(current); // Mark as visited
        
        // Check each neighbor
        const neighbors = graph[current]?.neighbors || {};
        console.log(`Checking neighbors of ${current}:`, Object.keys(neighbors));
        
        for (const [neighborId, distance] of Object.entries(neighbors)) {
            // Skip if neighbor has been visited
            if (closedSet.has(neighborId)) {
                continue;
            }
            
            const tentativeG = gScore[current] + distance;
            
            if (!openSet.has(neighborId)) {
                openSet.add(neighborId);
            } else if (tentativeG >= gScore[neighborId]) {
                continue;
            }
            
            cameFrom[neighborId] = current;
            gScore[neighborId] = tentativeG;
            fScore[neighborId] = tentativeG + getDistance(graph[neighborId], graph[endId]);
            console.log(`Updated node ${neighborId} with g=${tentativeG}, f=${fScore[neighborId]}`);
        }
    }
    
    console.log('No path found');
    return null; // No path found
}

function drawCompletePath(points, options = {}) {
    const { clearExisting = true } = options || {};
    console.log('Drawing complete path:', points);
    console.log('Number of points to draw:', points.length);
    
    // Validate points array
    if (!points || points.length === 0) {
        console.error('No points provided to drawCompletePath');
        return;
    }
    
    // Check if all points have valid coordinates
    const validPoints = points.filter(point => 
        point && typeof point.x === 'number' && typeof point.y === 'number' && 
        !isNaN(point.x) && !isNaN(point.y)
    );
    
    if (validPoints.length !== points.length) {
        console.warn('Some points have invalid coordinates, using only valid points:', validPoints);
    }
    
    if (validPoints.length === 0) {
        console.error('No valid points to draw');
        return;
    }
    
    // Filter out redundant points (points that form a straight line)
    const filteredPoints = validPoints.filter((point, index, arr) => {
        if (index === 0 || index === arr.length - 1) return true; // Keep start and end points
        
        // Check if point is collinear with previous and next points
        const prev = arr[index - 1];
        const next = arr[index + 1];
        
        // Calculate slopes
        const slope1 = Math.abs(point.y - prev.y) < 0.1 ? Infinity : (point.x - prev.x) / (point.y - prev.y);
        const slope2 = Math.abs(next.y - point.y) < 0.1 ? Infinity : (next.x - point.x) / (next.y - point.y);
        
        // If slopes are nearly equal (within tolerance), point is unnecessary
        return Math.abs(slope1 - slope2) > 0.1;
    });
    
    console.log('Points after filtering redundant ones:', filteredPoints.length);
    
    // Find the SVG container
    const svg = document.querySelector('svg');
    if (!svg) {
        console.error('SVG element not found');
        return;
    }
    console.log('Found SVG element');
    
    // Get the main group
    const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
    if (!mainGroup) {
        console.error('Main group not found');
        return;
    }
    console.log('Found main group:', mainGroup);
    
    // Create or get the path group
    let pathGroup = mainGroup.querySelector('#path-highlight-group');
    if (!pathGroup) {
        pathGroup = document.createElementNS("http://www.w3.org/2000/svg", 'g');
        pathGroup.setAttribute('id', 'path-highlight-group');
        mainGroup.appendChild(pathGroup);
        console.log('Created new path highlight group');
    } else {
        console.log('Using existing path highlight group');
    }
    
    if (clearExisting) {
        while (pathGroup.firstChild) {
            pathGroup.removeChild(pathGroup.firstChild);
        }
        console.log('Cleared existing paths from group');
    }
    
    // Create the path element
    const pathElement = document.createElementNS("http://www.w3.org/2000/svg", 'path');
    
    // Generate the path data
    let pathData = '';
    filteredPoints.forEach((point, index) => {
        if (index === 0) {
            pathData += `M ${point.x} ${point.y}`;
        } else {
            pathData += ` L ${point.x} ${point.y}`;
        }
    });
    
    console.log('Generated path data:', pathData);
    
    // Set path attributes
    pathElement.setAttribute('d', pathData);
    pathElement.setAttribute('stroke', '#FF4444');
    pathElement.setAttribute('stroke-width', '4');
    pathElement.setAttribute('fill', 'none');
    pathElement.setAttribute('stroke-dasharray', '10,5');
    pathElement.setAttribute('vector-effect', 'non-scaling-stroke');

    ensurePathFlowAnimationStyles();

    const dashArrayValue = pathElement.getAttribute('stroke-dasharray') || '10,5';
    const dashSegments = dashArrayValue
        .split(/[ ,]+/)
        .map(segment => parseFloat(segment))
        .filter(value => Number.isFinite(value) && value > 0);
    const dashCycleDistance = dashSegments.reduce((sum, value) => sum + value, 0) || 15;
    const defaultDurationSeconds = 1.2;

    pathElement.classList.add(PATH_FLOW_ANIMATION_CLASS);
    pathElement.style.setProperty('--dash-cycle', `${dashCycleDistance}px`);
    pathElement.style.setProperty('--path-flow-duration', `${defaultDurationSeconds}s`);
    pathElement.style.strokeDashoffset = '0px';
    pathElement.style.animationDuration = `${defaultDurationSeconds}s`;
    
    console.log('Path element created with attributes:', {
        d: pathData,
        stroke: '#FF4444',
        strokeWidth: '4',
        fill: 'none'
    });
    
    // Add the path to the group
    pathGroup.appendChild(pathElement);
    console.log('Path element added to SVG group');

    try {
        // Force layout to ensure animations behave consistently across path lengths
        const totalLength = pathElement.getTotalLength();
        if (Number.isFinite(totalLength) && totalLength > 0) {
            const minDurationSeconds = 0.75;
            const maxDurationSeconds = 6;
            const flowSpeedUnitsPerSecond = 1000;
            const durationSeconds = Math.min(
                Math.max(totalLength / flowSpeedUnitsPerSecond, minDurationSeconds),
                maxDurationSeconds
            );
            const roundedDuration = Math.round(durationSeconds * 100) / 100;
            pathElement.style.setProperty('--path-flow-duration', `${roundedDuration}s`);
            pathElement.style.animationDuration = `${roundedDuration}s`;
            console.log('Adjusted path animation duration based on length', {
                totalLength,
                durationSeconds: roundedDuration
            });
        }
    } catch (error) {
        console.warn('Unable to compute total length for animated path', error);
    }
    
    // Verify the path was actually added
    const addedPaths = pathGroup.querySelectorAll('path');
    console.log('Number of paths in group after adding:', addedPaths.length);
}

function drawPathLines(path) {
  const graph = window.MOBILE_MODE ? window.floorGraph : floorGraph;
  console.log('Drawing path lines for:', path);
  
  // Find the SVG container
  const svg = document.querySelector('svg');
  if (!svg) {
    console.error('SVG element not found');
    return;
  }
  
  // Create a group for path lines if it doesn't exist
  let mainGroup = svg.querySelector('g'); // Get the main SVG group
  if (!mainGroup) {
    mainGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    svg.appendChild(mainGroup);
  }
  
  let pathGroup = mainGroup.querySelector('#path-lines-group');
  if (!pathGroup) {
    pathGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    pathGroup.setAttribute('id', 'path-lines-group');
    mainGroup.appendChild(pathGroup);
  } else {
    // Clear existing paths
    while (pathGroup.firstChild) {
      pathGroup.removeChild(pathGroup.firstChild);
    }
  }
  
  // Draw lines between consecutive rooms
  for (let i = 0; i < path.length - 1; i++) {
    const currentRoomId = path[i];
    const nextRoomId = path[i + 1];
    
    // Use coordinates from floor_graph.json instead of SVG bounding boxes
    if (graph.rooms[currentRoomId] && graph.rooms[nextRoomId]) {
        const currentRoom = graph.rooms[currentRoomId];
        const nextRoom = graph.rooms[nextRoomId];
        
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', currentRoom.x);
        line.setAttribute('y1', currentRoom.y);
        line.setAttribute('x2', nextRoom.x);
        line.setAttribute('y2', nextRoom.y);
        line.setAttribute('class', 'path-line');
        line.setAttribute('stroke', '#FF0000');
        line.setAttribute('stroke-width', '4');
        line.setAttribute('stroke-dasharray', '10,5');
        line.setAttribute('opacity', '0.8');
        pathGroup.appendChild(line);
    } else {
        console.warn(`Could not find room data for ${currentRoomId} or ${nextRoomId}`);
    }
  }
  
  console.log('Path lines drawn');
}
