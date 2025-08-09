let floorGraph = {};
let selectedRooms = [];
let pathResult = [];

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

// Function to reload graph data
function reloadGraphData() {
    return fetch('floor_graph.json?' + new Date().getTime())
        .then(res => res.json())
        .then(data => {
            floorGraph = data;
            console.log("Reloaded floor graph:", floorGraph);
            
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
            const marker = document.createElementNS(svgNS, 'circle');
            marker.setAttribute('cx', point.x);
            marker.setAttribute('cy', point.y);
            marker.setAttribute('r', walkablePath.style.pointMarker.radius);
            marker.setAttribute('fill', walkablePath.style.pointMarker.color);
            marker.setAttribute('stroke', walkablePath.style.pointMarker.strokeColor);
            marker.setAttribute('stroke-width', walkablePath.style.pointMarker.strokeWidth);
            marker.setAttribute('vector-effect', 'non-scaling-stroke');
            marker.setAttribute('class', `path-marker point-marker point-${index}`);
            marker.setAttribute('data-path-id', walkablePath.id);
            marker.setAttribute('data-point-index', index);
            
            // Make markers interactive
            marker.style.cursor = 'pointer';
            marker.style.pointerEvents = 'all';
            
            // Add hover effect
            marker.addEventListener('mouseenter', () => {
                marker.setAttribute('fill', walkablePath.style.pointMarker.hoverColor);
                marker.setAttribute('r', walkablePath.style.pointMarker.radius * 1.5);
            });
            
            marker.addEventListener('mouseleave', () => {
                marker.setAttribute('fill', walkablePath.style.pointMarker.color);
                marker.setAttribute('r', walkablePath.style.pointMarker.radius);
            });
            
            markerGroup.appendChild(marker);
        });
    }
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing pathfinding and room selection...');

    // Initialize pathfinding
    const initPathfinding = () => {
        fetch('floor_graph.json?' + new Date().getTime())
            .then(res => res.json())
            .then(data => {
                floorGraph = data;
                console.log("Loaded floor graph:", floorGraph);
                if (floorGraph.walkablePaths && Array.isArray(floorGraph.walkablePaths)) {
                    console.log(`Found ${floorGraph.walkablePaths.length} paths to draw`);
                    floorGraph.walkablePaths.forEach((path, index) => {
                        if (path.pathPoints && path.pathPoints.length > 0) {
                            console.log(`Drawing path ${index + 1}:`, path.id);
                            drawWalkablePath(path);
                        }
                    });
                    
                    // Initialize room selection after paths are drawn
                    initRoomSelection();
                }
            })
            .catch(error => {
                console.error('Error loading floor graph:', error);
            });
    };

    // Start initialization
    if (window.panZoom) {
        initPathfinding();
    } else {
        window.addEventListener('panZoomReady', initPathfinding);
    }
});

// Initialize room selection - moved outside DOMContentLoaded so it can be called after floor changes
function initRoomSelection() {
    console.log('Initializing room selection...');
    document.querySelectorAll('[id^="room-"]').forEach(el => {
        // Remove existing click listeners to avoid duplicates
        el.removeEventListener('click', roomClickHandler);
        el.addEventListener('click', roomClickHandler);
    });
}

// Room click handler - separated for easier management
function roomClickHandler() {
    const roomId = this.id;
    console.log('Room clicked:', roomId);

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
    }

    // Add the clicked room to selection
    selectedRooms.push(roomId);
    this.classList.add('selected-room');
    console.log('Selected rooms so far:', selectedRooms);

    // Get the clicked room's data
    const clickedRoom = floorGraph.rooms[roomId];
    if (!clickedRoom) {
        console.error('Room data not found');
        return;
    }

    // Find nearest point on the path for clicked room
    const clickedPath = floorGraph.walkablePaths.find(p => p.id === clickedRoom.nearestPathId);
    if (!clickedPath) {
        console.error('Path not found');
        return;
    }

    const clickedPathPoint = findNearestPointOnPath(clickedRoom.doorPoint, clickedPath.pathPoints);

    // Clear existing paths
    clearAllPaths();

    if (selectedRooms.length === 1) {
        // Just show connection to nearest path for first room
        drawCompletePath([
            clickedRoom.doorPoint,
            clickedPathPoint
        ]);
    } else if (selectedRooms.length === 2) {
        const [startRoomId, endRoomId] = selectedRooms;
        const startRoom = floorGraph.rooms[startRoomId];
        const endRoom = floorGraph.rooms[endRoomId];
        
        const startPath = floorGraph.walkablePaths.find(p => p.id === startRoom.nearestPathId);
        const endPath = floorGraph.walkablePaths.find(p => p.id === endRoom.nearestPathId);
        
        if (!startPath || !endPath) {
            console.error('Path not found');
            return;
        }
        
        const startPathPoint = findNearestPointOnPath(startRoom.doorPoint, startPath.pathPoints);
        const endPathPoint = findNearestPointOnPath(endRoom.doorPoint, endPath.pathPoints);
        
        // Draw complete path including door connections
        drawCompletePath([
            startRoom.doorPoint,
            startPathPoint,
            ...getPathBetweenPoints(startPathPoint, endPathPoint, floorGraph.walkablePaths),
            endPathPoint,
            endRoom.doorPoint
        ]);
    }
}

// Heuristic: Euclidean distance
function heuristic(a, b) {
  const dx = floorGraph.rooms[a].x - floorGraph.rooms[b].x;
  const dy = floorGraph.rooms[a].y - floorGraph.rooms[b].y;
  return Math.sqrt(dx * dx + dy * dy);
}

// A* algorithm
function aStar(start, goal) {
  console.log('A* starting with:', start, '->', goal);
  
  // Safety check - ensure both nodes exist in graph
  if (!floorGraph.rooms[start] || !floorGraph.rooms[goal]) {
    console.log('Start or goal node not found in graph');
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
    if (!floorGraph.rooms[current] || !floorGraph.rooms[current].neighbors) {
      console.log(`No neighbors found for ${current}`);
      continue;
    }
    
    for (let neighbor of Object.keys(floorGraph.rooms[current].neighbors)) {
      // Safety check - ensure neighbor exists
      if (!floorGraph.rooms[neighbor]) {
        console.log(`Neighbor ${neighbor} not found in graph, skipping`);
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

function getPathBetweenPoints(start, end, walkablePaths) {
    console.log('Finding path between', start, 'and', end);
    
    // Convert walkable paths into a graph structure for A*
    const graph = {};
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
            
            // Create point if it doesn't exist
            if (!graph[pointId]) {
                graph[pointId] = {
                    x: point.x,
                    y: point.y,
                    neighbors: {}
                };
            }
            
            // Connect to previous point if it exists
            if (previousPoint) {
                const prevId = `${Math.round(previousPoint.x)},${Math.round(previousPoint.y)}`;
                const distance = getDistance(point, previousPoint);
                
                // Add bidirectional connections
                graph[pointId].neighbors[prevId] = distance;
                graph[prevId].neighbors[pointId] = distance;
            }
            
            previousPoint = point;
            processedPoints.add(pointId);
        });
    });
    
    // Add start and end points to the graph
    const startId = `${Math.round(start.x)},${Math.round(start.y)}`;
    const endId = `${Math.round(end.x)},${Math.round(end.y)}`;
    
    console.log('Finding nearest points for start and end');
    
    // Find closest points on paths for start and end
    let startNearestId = null;
    let endNearestId = null;
    let minStartDist = Infinity;
    let minEndDist = Infinity;
    
    // Find nearest points from our processed (unique) points
    processedPoints.forEach(pointId => {
        const point = graph[pointId];
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
    if (!graph[startId]) {
        graph[startId] = {
            x: start.x,
            y: start.y,
            neighbors: {}
        };
    }
    if (!graph[endId]) {
        graph[endId] = {
            x: end.x,
            y: end.y,
            neighbors: {}
        };
    }
    
    // Connect start and end to their nearest points
    graph[startId].neighbors[startNearestId] = minStartDist;
    graph[startNearestId].neighbors[startId] = minStartDist;
    
    graph[endId].neighbors[endNearestId] = minEndDist;
    graph[endNearestId].neighbors[endId] = minEndDist;
    
    // Use A* to find path through the graph
    const path = aStarSearch(graph, startId, endId);
    
    // Convert path IDs back to points
    if (path) {
        return path.map(id => ({
            x: graph[id].x,
            y: graph[id].y
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
    
    // Filter out redundant points (points that form a straight line)
    const filteredPoints = points.filter((point, index, arr) => {
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
    
    // Find the SVG container
    const svg = document.querySelector('svg');
    if (!svg) {
        console.error('SVG element not found');
        return;
    }
    
    // Get the main group
    const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
    if (!mainGroup) {
        console.error('Main group not found');
        return;
    }
    
    // Create or get the path group
    let pathGroup = mainGroup.querySelector('#path-highlight-group');
    if (!pathGroup) {
        pathGroup = document.createElementNS("http://www.w3.org/2000/svg", 'g');
        pathGroup.setAttribute('id', 'path-highlight-group');
        mainGroup.appendChild(pathGroup);
    }
    
    // Clear existing paths
    while (pathGroup.firstChild) {
        pathGroup.removeChild(pathGroup.firstChild);
    }
    
    // Create the path element
    const pathElement = document.createElementNS("http://www.w3.org/2000/svg", 'path');
    
    // Generate the path data
    let pathData = '';
    points.forEach((point, index) => {
        if (index === 0) {
            pathData += `M ${point.x} ${point.y}`;
        } else {
            pathData += ` L ${point.x} ${point.y}`;
        }
    });
    
    // Set path attributes
    pathElement.setAttribute('d', pathData);
    pathElement.setAttribute('stroke', '#FF4444');
    pathElement.setAttribute('stroke-width', '4');
    pathElement.setAttribute('fill', 'none');
    pathElement.setAttribute('stroke-dasharray', '10,5');
    pathElement.setAttribute('vector-effect', 'non-scaling-stroke');
    
    // Add the path to the group
    pathGroup.appendChild(pathElement);
}

function drawPathLines(path) {
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
    if (floorGraph.rooms[currentRoomId] && floorGraph.rooms[nextRoomId]) {
      const currentRoom = floorGraph.rooms[currentRoomId];
      const nextRoom = floorGraph.rooms[nextRoomId];
      const waypoints = floorGraph.rooms[currentRoomId].neighbors[nextRoomId]?.waypoints || [];
      
      // Start point (current room)
      let lastX = currentRoom.x;
      let lastY = currentRoom.y;
      
      // Draw lines through waypoints
      waypoints.forEach(waypoint => {
        // Draw line from last point to waypoint
        const waypointLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        waypointLine.setAttribute('x1', lastX);
        waypointLine.setAttribute('y1', lastY);
        waypointLine.setAttribute('x2', waypoint.x);
        waypointLine.setAttribute('y2', waypoint.y);
        waypointLine.setAttribute('class', 'path-line');
        waypointLine.setAttribute('stroke', '#FF0000');
        waypointLine.setAttribute('stroke-width', '4');
        waypointLine.setAttribute('stroke-dasharray', '10,5');
        waypointLine.setAttribute('opacity', '0.8');
        pathGroup.appendChild(waypointLine);
        
        // Update last point
        lastX = waypoint.x;
        lastY = waypoint.y;
      });
      
      // Draw final line to next room using entry point or center
      const finalLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
      finalLine.setAttribute('x1', lastX);
      finalLine.setAttribute('y1', lastY);
      // Use entry point if available, fall back to center, then x,y
      const endX = nextRoom.entry?.x || nextRoom.center?.x || nextRoom.x;
      const endY = nextRoom.entry?.y || nextRoom.center?.y || nextRoom.y;
      finalLine.setAttribute('x2', endX);
      finalLine.setAttribute('y2', endY);
      finalLine.setAttribute('class', 'path-line');
      finalLine.setAttribute('stroke', '#FF0000');
      finalLine.setAttribute('stroke-width', '4');
      finalLine.setAttribute('vector-effect', 'non-scaling-stroke');
      finalLine.setAttribute('stroke-dasharray', '10,5');
      finalLine.setAttribute('opacity', '0.8');
      pathGroup.appendChild(finalLine);
    } else {
      console.warn(`Could not find elements for ${currentRoomId} or ${nextRoomId}`);
    }
  }
  
  console.log('Path lines drawn');
}

// --- Added: Reinitialize pathfinding on floor change ---
window.addEventListener('floorMapLoaded', (e) => {
  const floor = e.detail && e.detail.floor;
  // Only draw for floor 1 (adjust if multi-floor graphs later)
  if (floor === 1 || typeof floor === 'undefined') {
    console.log('[pathfinding] floorMapLoaded received for floor', floor, '- reloading graph data');
    
    // Wait a bit for SVG to be fully loaded
    setTimeout(() => {
      if (typeof reloadGraphData === 'function') {
        reloadGraphData().then(() => {
          // Re-initialize room selection after paths are redrawn
          initRoomSelection();
        });
      }
    }, 200);
  } else {
    // Clear any previous path visuals when leaving floor 1
    const svg = document.querySelector('svg');
    if (svg) {
      const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
      if (mainGroup) {
        const pathGroup = mainGroup.querySelector('#walkable-path-group');
        if (pathGroup) {
          while (pathGroup.firstChild) pathGroup.removeChild(pathGroup.firstChild);
        }
        const linesGroup = mainGroup.querySelector('#path-lines-group');
        if (linesGroup) {
          while (linesGroup.firstChild) linesGroup.removeChild(linesGroup.firstChild);
        }
        const highlightGroup = mainGroup.querySelector('#path-highlight-group');
        if (highlightGroup) {
          while (highlightGroup.firstChild) highlightGroup.removeChild(highlightGroup.firstChild);
        }
      }
    }
    selectedRooms = [];
  }
});
// --- End Added ---