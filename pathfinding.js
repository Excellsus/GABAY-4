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
    const walkSegment = segments.find(segment => segment.type === 'walk' && segment.points && segment.points.length);
    if (walkSegment) {
        drawCompletePath(walkSegment.points);
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
    let summaryText = route.type === 'multi-floor'
        ? `Route spans ${totalFloors} floors using the ${describeStairKey(route.stairKey)}.`
        : 'Single-floor route.';
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

    const startDoors = getEntryPointsForRoom(startRoom);
    const endDoors = getEntryPointsForRoom(endRoom);

    if (!startDoors.length || !endDoors.length) {
        console.warn('No entry points found for rooms', { startRoomId, endRoomId });
        return null;
    }

    let bestOption = null;

    for (const startDoor of startDoors) {
        for (const endDoor of endDoors) {
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

    return bestOption;
}

async function calculateMultiFloorRoute(startRoomId, endRoomId) {
    const startFloor = parseFloorFromRoomId(startRoomId);
    const endFloor = parseFloorFromRoomId(endRoomId);

    if (startFloor == null || endFloor == null) {
        console.warn('Unable to determine floors for rooms', { startRoomId, endRoomId });
        return null;
    }

    if (startFloor === endFloor) {
        const graph = await ensureFloorGraphLoaded(startFloor);
        const route = calculateSingleFloorRoute(graph, startRoomId, endRoomId);
        if (!route) return null;
        return {
            type: 'single-floor',
            startRoomId,
            endRoomId,
            floors: [startFloor],
            totalDistance: route.distance,
            segments: [
                {
                    type: 'walk',
                    floor: startFloor,
                    description: `Floor ${startFloor}: Route from ${startRoomId} to ${endRoomId}`,
                    points: route.points,
                    distance: route.distance,
                    startDoor: route.startDoor,
                    endDoor: route.endDoor
                }
            ]
        };
    }

    const floorRange = [];
    const step = startFloor < endFloor ? 1 : -1;
    for (let f = startFloor; step > 0 ? f <= endFloor : f >= endFloor; f += step) {
        floorRange.push(f);
    }

    await Promise.all([...new Set(floorRange)].map(ensureFloorGraphLoaded));

    const sharedStairKeys = getSharedStairKeys(floorRange);
    if (!sharedStairKeys.length) {
        console.warn('No shared stair connectors found for floors', floorRange);
        return null;
    }

    let bestRoute = null;

    sharedStairKeys.forEach(stairKey => {
        const startGraph = floorGraphCache[startFloor];
        const endGraph = floorGraphCache[endFloor];

        const startStair = (startGraph.stairNodes || []).find(node => node.stairKey === stairKey);
        const endStair = (endGraph.stairNodes || []).find(node => node.stairKey === stairKey);

        if (!startStair || !endStair) {
            return;
        }

        const startRoute = calculateSingleFloorRoute(startGraph, startRoomId, startStair.roomId);
        const endRoute = calculateSingleFloorRoute(endGraph, endStair.roomId, endRoomId);

        if (!startRoute || !endRoute) {
            return;
        }

        const totalDistance = startRoute.distance + endRoute.distance;
        const verticalSpan = Math.abs(endFloor - startFloor);
        const stairName = describeStairKey(stairKey);

        const candidateRoute = {
            type: 'multi-floor',
            startRoomId,
            endRoomId,
            stairKey,
            stairName,
            floors: floorRange,
            totalDistance,
            segments: [
                {
                    type: 'walk',
                    floor: startFloor,
                    description: `Floor ${startFloor}: Proceed to ${stairName}`,
                    points: startRoute.points,
                    distance: startRoute.distance,
                    startDoor: startRoute.startDoor,
                    endDoor: startRoute.endDoor,
                    via: stairKey
                },
                {
                    type: 'stair',
                    stairKey,
                    description: `Take ${stairName} to Floor ${endFloor}`,
                    fromFloor: startFloor,
                    toFloor: endFloor,
                    floors: floorRange,
                    floorSpan: verticalSpan
                },
                {
                    type: 'walk',
                    floor: endFloor,
                    description: `Floor ${endFloor}: Exit ${stairName} and continue to destination`,
                    points: endRoute.points,
                    distance: endRoute.distance,
                    startDoor: endRoute.startDoor,
                    endDoor: endRoute.endDoor,
                    via: stairKey
                }
            ]
        };

        if (!bestRoute || totalDistance < bestRoute.totalDistance) {
            bestRoute = candidateRoute;
        }
    });

    return bestRoute;
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

    if (entryPoints.length === 0) {
        console.error('No entry points found for this room');
        return;
    }

    // Find the nearest entry point on the clicked room to the click event
    const rect = this.getBoundingClientRect();
    const svg = this.closest('svg');
    const pt = svg.createSVGPoint();
    pt.x = event.clientX;
    pt.y = event.clientY;
    const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());
    
    let nearestEntryPoint = entryPoints[0];
    if (entryPoints.length > 1) {
        let minDistance = Infinity;
        entryPoints.forEach(entryPoint => {
            const distance = getDistance({x: svgP.x, y: svgP.y}, entryPoint);
            if (distance < minDistance) {
                minDistance = distance;
                nearestEntryPoint = entryPoint;
            }
        });
    }

    // Find nearest point on the path for clicked room
    const clickedPath = graph.walkablePaths.find(p => p.id === clickedRoom.nearestPathId);
    if (!clickedPath) {
        console.error('Path not found');
        return;
    }

    const clickedPathPoint = findNearestPointOnPath(nearestEntryPoint, clickedPath.pathPoints);

    // Clear existing paths
    clearAllPaths();

    if (selectedRooms.length === 1) {
        // Just show connection to nearest path for first room
        drawCompletePath([
            nearestEntryPoint,
            clickedPathPoint
        ]);
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

            if (route.type === 'multi-floor' && route.stairKey) {
                const stairName = describeStairKey(route.stairKey);
                console.log(`Multi-floor route computed using ${stairName}.`);
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

function drawCompletePath(points) {
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
    
    // Clear existing paths
    while (pathGroup.firstChild) {
        pathGroup.removeChild(pathGroup.firstChild);
    }
    console.log('Cleared existing paths from group');
    
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
    
    console.log('Path element created with attributes:', {
        d: pathData,
        stroke: '#FF4444',
        strokeWidth: '4',
        fill: 'none'
    });
    
    // Add the path to the group
    pathGroup.appendChild(pathElement);
    console.log('Path element added to SVG group');
    
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
