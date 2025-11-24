// Get all room elements - UPDATED VERSION
// Only get elements with room-X-1 format
const allGroups = document.querySelectorAll('g[id^="room-"]');
const allPaths = document.querySelectorAll(
  'path[id^="room-"], rect[id^="room-"]'
);

// Function to identify real rooms
function identifyRooms() {
  console.log("Identifying room elements...");
  let roomsIdentified = 0;

  // First, check if any groups already have data-office-id attributes
  const groupsWithOfficeId = document.querySelectorAll("g[data-office-id]");
  if (groupsWithOfficeId.length > 0) {
    console.log(
      `Found ${groupsWithOfficeId.length} groups with data-office-id attributes`
    );
    groupsWithOfficeId.forEach((group) => {
      if (group.id.match(/^room-\d+(?:-\d+)?$/)) {
        group.setAttribute("data-room", "true");
        console.log(`Marked ${group.id} as a room based on data-office-id`);
        roomsIdentified++;
      }
    });
  }

  // Second, check for direct room paths/rects with format room-X-1
  const roomElements = document.querySelectorAll(
    'path[id^="room-"], rect[id^="room-"]'
  );
  if (roomElements.length > 0) {
    console.log(`Found ${roomElements.length} elements with room- prefix IDs`);
    roomElements.forEach((element) => {
      if (element.id.match(/^room-\d+(?:-\d+)?$/)) {
        const parentGroup = element.closest("g");
        if (parentGroup) {
          parentGroup.setAttribute("data-room", "true");
          // If the element has an office ID, copy it to the parent group
          if (element.dataset.officeId) {
            parentGroup.dataset.officeId = element.dataset.officeId;
          }
          // Extract room number from element ID
          const roomMatch = element.id.match(/room-(\d+)(?:-(\d+))?/);
          if (roomMatch && roomMatch[1]) {
            parentGroup.dataset.roomNumber = roomMatch[1];
            if (roomMatch[2]) {
              parentGroup.dataset.floorNumber = roomMatch[2];
              element.dataset.floorNumber = roomMatch[2];
            }
            element.dataset.roomNumber = roomMatch[1];
            // Ensure parent group has correct ID format
            parentGroup.id = element.id;
          }
          console.log(
            `Marked ${parentGroup.id} as a room based on element with id ${element.id}`
          );
          roomsIdentified++;
        }
      }
    });
  }

  // Log all identified rooms
  const allRoomGroups = document.querySelectorAll('g[data-room="true"]');
  console.log(
    `Total rooms identified: ${roomsIdentified} (${allRoomGroups.length} groups)`
  );

  // Log each identified room
  allRoomGroups.forEach((group) => {
    const roomMatch = group.id.match(/room-(\d+)(?:-(\d+))?/);
    if (roomMatch) {
      console.log(
        `Identified room ${roomMatch[1]}${
          roomMatch[2] ? ` (floor ${roomMatch[2]})` : ""
        }: ${group.tagName} #${group.id}`
      );
    }
  });

  return [...allRoomGroups];
}

// Identify rooms and get only the room elements
let rooms = [];

function refreshDragDropRooms() {
  rooms = identifyRooms();
  console.log(`Drag/drop room cache refreshed: ${rooms.length} rooms detected`);
  if (isEditMode) {
    console.log('Edit mode active during refresh, reinitializing drag/drop listeners');
    disableDragAndDrop();
    enableDragAndDrop();
  }
}

let editButton = null;
let cancelButton = null;
const floorPlanContainer = document.querySelector(".floor-plan-container");
let isEditMode = false;
let isDragging = false;
let isOverRoom = false;
let draggedElement = null;
let startX = 0;
let startY = 0;

console.log("Drag Drop Setup script loaded - UPDATED VERSION");
refreshDragDropRooms();

console.log(
  `Found ${rooms.length} draggable room elements after classification`
);

// Log each room ID for debugging
rooms.forEach((room, index) => {
  console.log(`Room ${index + 1}: ${room.id}`);
});

function bindEditButton(newButton) {
  if (editButton && editButton !== newButton) {
    editButton.removeEventListener("click", handleEditButtonClick);
  }

  editButton = newButton;

  if (!editButton) {
    console.warn("Edit button not found during bind attempt");
    return;
  }

  editButton.textContent = isEditMode ? "Save" : "Edit";
  editButton.removeEventListener("click", handleEditButtonClick);
  editButton.addEventListener("click", handleEditButtonClick);
  console.log("Edit button bound for drag/drop controls");
}

function bindCancelButton(newButton) {
  if (cancelButton && cancelButton !== newButton) {
    cancelButton.removeEventListener("click", handleCancelButtonClick);
  }

  cancelButton = newButton;

  if (!cancelButton) {
    console.warn("Cancel button not found during bind attempt");
    return;
  }

  cancelButton.style.display = isEditMode ? "block" : "none";
  cancelButton.removeEventListener("click", handleCancelButtonClick);
  cancelButton.addEventListener("click", handleCancelButtonClick);
  console.log("Cancel button bound for drag/drop controls");
}

bindEditButton(document.getElementById("edit-floorplan-btn"));
bindCancelButton(document.getElementById("cancel-edit-floorplan-btn"));
console.log(`Edit button bound: ${editButton !== null}`);
console.log(`Cancel button bound: ${cancelButton !== null}`);

// Function to create a ghost image for dragging
function createGhostImage(element) {
  const ghost = element.cloneNode(true);
  ghost.style.position = "absolute";
  ghost.style.pointerEvents = "none";
  ghost.style.opacity = "0.5";
  ghost.style.fill = "#1A5632";
  ghost.style.stroke = "#1A5632";
  ghost.style.strokeWidth = "2";
  ghost.style.filter = "drop-shadow(0 0 4px rgba(0,0,0,0.3))";
  document.body.appendChild(ghost);
  return ghost;
}

// Function to enable drag and drop
function enableDragAndDrop() {
  console.log("Enabling drag and drop - edit mode activated");

  // Add visual indicator for edit mode
  document.body.classList.add("edit-mode-active");
  floorPlanContainer.classList.add("edit-mode-active");

  if (cancelButton) {
    cancelButton.style.display = "block";
  }

  // Display a message to the user
  const editModeMsg = document.createElement("div");
  editModeMsg.id = "edit-mode-message";
  editModeMsg.className = "edit-mode-message";
  editModeMsg.textContent = "Edit Mode: Drag rooms to reposition them";
  floorPlanContainer.appendChild(editModeMsg);

  // Make each room draggable
  if (!rooms || rooms.length === 0) {
    rooms = identifyRooms();
  }

  rooms.forEach((room) => {
    // Find the path or rect element inside the group
    const roomElement = room.querySelector("path, rect");
    if (!roomElement) {
      console.warn(`No path or rect element found in room group ${room.id}`);
      return;
    }

    room.classList.add("draggable");

    // Store original data for later use
    if (!room.dataset.originalId) {
      room.dataset.originalId = room.id;
    }

    // Add mouse event listeners
    room.addEventListener("mousedown", handleMouseDown, true);
    room.addEventListener("mouseenter", handleRoomMouseEnter, true);
    room.addEventListener("mouseleave", handleRoomMouseLeave, true);

    // Also add event listeners to the room element
    roomElement.addEventListener("mousedown", handleMouseDown, true);
    roomElement.addEventListener("mouseenter", handleRoomMouseEnter, true);
    roomElement.addEventListener("mouseleave", handleRoomMouseLeave, true);

    console.log(`Added event listeners to room ${room.id} and its element`);
  });

  // Disable pan and zoom initially - we'll re-enable it when not over a room
  if (window.panZoom) {
    window.panZoom.disablePan();
    window.panZoom.disableZoom();
    console.log("Pan and zoom temporarily disabled in edit mode");
  }
}

// Function to disable drag and drop
function disableDragAndDrop() {
  console.log("Disabling drag and drop - returning to view mode");

  // Remove edit mode indicator
  document.body.classList.remove("edit-mode-active");
  floorPlanContainer.classList.remove("edit-mode-active");

  if (cancelButton) {
    cancelButton.style.display = "none";
  }

  // Remove edit mode message if it exists
  const editModeMsg = document.getElementById("edit-mode-message");
  if (editModeMsg) {
    editModeMsg.remove();
  }

  rooms.forEach((room) => {
    room.classList.remove("draggable");

    // Remove event listeners
    room.removeEventListener("mousedown", handleMouseDown, true);
    room.removeEventListener("mouseenter", handleRoomMouseEnter, true);
    room.removeEventListener("mouseleave", handleRoomMouseLeave, true);

    const roomElement = room.querySelector("path, rect");
    if (roomElement) {
      roomElement.removeEventListener("mousedown", handleMouseDown, true);
      roomElement.removeEventListener("mouseenter", handleRoomMouseEnter, true);
      roomElement.removeEventListener("mouseleave", handleRoomMouseLeave, true);
    }
  });

  // Re-enable panning and zooming
  if (window.panZoom) {
    window.panZoom.enablePan();
    window.panZoom.enableZoom();
    console.log("Pan and zoom re-enabled in view mode");
  }
}

// Mouse enter/leave handlers for rooms
function handleRoomMouseEnter(e) {
  if (isEditMode) {
    e.stopPropagation();
    isOverRoom = true;
    console.log(`Mouse entered room: ${e.currentTarget.id}`);

    // In edit mode, disable pan/zoom when hovering over a room
    if (window.panZoom) {
      window.panZoom.disablePan();
      window.panZoom.disableZoom();
      console.log("Disabled pan/zoom - mouse over room in edit mode");
    } else {
      console.warn("panZoom instance not found on window object");
    }

    // Highlight the room to indicate it can be dragged
    e.currentTarget.classList.add("room-hover");
  }
}

function handleRoomMouseLeave(e) {
  if (isEditMode) {
    e.stopPropagation();
    isOverRoom = false;
    console.log(`Mouse left room: ${e.currentTarget.id}`);

    // Remove hover highlight
    e.currentTarget.classList.remove("room-hover");

    // Only re-enable pan/zoom if we're not currently dragging
    if (window.panZoom && !isDragging) {
      window.panZoom.enablePan();
      window.panZoom.enableZoom();
      console.log("Enabled pan/zoom - mouse left room in edit mode");
    }
  }
}

// Mouse event handlers
function handleMouseDown(e) {
  if (!isEditMode) return;

  e.stopPropagation();
  e.preventDefault();

  isDragging = true;
  window.isDragging = true; // Set global dragging state
  draggedElement = e.currentTarget; // Use currentTarget to get the room group, not just the path

  console.log(`Started dragging room: ${draggedElement.id}`);

  // Add dragging class for visual feedback
  draggedElement.classList.add("dragging");

  // Get the initial mouse position
  startX = e.clientX;
  startY = e.clientY;

  // Make the dragged element semi-transparent
  draggedElement.style.opacity = "0.5";

  // Add event listeners for dragging
  document.addEventListener("mousemove", handleMouseMove, true);
  document.addEventListener("mouseup", handleMouseUp, true);

  // Ensure panning is disabled while dragging
  if (window.panZoom) {
    window.panZoom.disablePan();
    window.panZoom.disableZoom();
    console.log("Disabled pan/zoom for dragging");
  } else {
    console.warn("panZoom instance not found on window object");
  }
}

function handleMouseMove(e) {
  if (!isDragging || !draggedElement) return;

  e.stopPropagation();
  e.preventDefault();

  // Find room element under cursor
  const elemUnderCursor = document.elementFromPoint(e.clientX, e.clientY);
  const roomUnderCursor = elemUnderCursor
    ? elemUnderCursor.closest('g[data-room="true"]')
    : null;

  // Remove previous drag-target class from all rooms
  rooms.forEach((room) => {
    if (room !== draggedElement) {
      room.classList.remove("drag-target");
    }
  });

  // Add visual feedback for potential drop targets
  if (roomUnderCursor && roomUnderCursor !== draggedElement) {
    roomUnderCursor.classList.add("drag-target");
    console.log(`Potential drop target: ${roomUnderCursor.id}`);
  }
}

function handleMouseUp(e) {
  if (!isDragging || !draggedElement) return;

  e.stopPropagation();
  e.preventDefault();

  isDragging = false;
  window.isDragging = false;

  // Reset opacity and remove dragging class
  draggedElement.style.opacity = "1";
  draggedElement.classList.remove("dragging");

  // Remove drag-target class from all rooms
  rooms.forEach((room) => {
    room.classList.remove("drag-target");
  });

  // Remove event listeners
  document.removeEventListener("mousemove", handleMouseMove, true);
  document.removeEventListener("mouseup", handleMouseUp, true);

  // Find room element under cursor
  const elemUnderCursor = document.elementFromPoint(e.clientX, e.clientY);
  const dropTarget = elemUnderCursor
    ? elemUnderCursor.closest('g[data-room="true"]')
    : null;

  console.log(`Drop target: ${dropTarget ? dropTarget.id : "none"}`);

  if (dropTarget && dropTarget !== draggedElement) {
    console.log(`Dropping on target: ${dropTarget.id}`);

    // Find the path elements to swap styles
    const draggedPath = draggedElement.querySelector("path, rect");
    const targetPath = dropTarget.querySelector("path, rect");

    if (draggedPath && targetPath) {
      // Get office IDs
      const draggedOfficeId =
        draggedElement.dataset.officeId || draggedPath.dataset.officeId;
      const targetOfficeId =
        dropTarget.dataset.officeId || targetPath.dataset.officeId;
      
      // Check if BOTH rooms have offices (swap scenario)
      if (draggedOfficeId && targetOfficeId) {
        // Find office data
        const draggedOffice = officesData.find(o => o.id.toString() === draggedOfficeId);
        const targetOffice = officesData.find(o => o.id.toString() === targetOfficeId);
        
        if (draggedOffice && targetOffice) {
          // Show confirmation modal
          if (typeof window.showSwapConfirmation === 'function') {
            window.showSwapConfirmation(
              draggedOffice,
              targetOffice,
              draggedElement.id,
              dropTarget.id,
              // onConfirm callback
              function() {
                performSwap(draggedElement, dropTarget, draggedPath, targetPath, draggedOfficeId, targetOfficeId);
              },
              // onCancel callback
              function() {
                console.log('Swap cancelled by user');
              }
            );
          } else {
            // Fallback if modal not available
            performSwap(draggedElement, dropTarget, draggedPath, targetPath, draggedOfficeId, targetOfficeId);
          }
          return; // Exit early, swap will be performed in callback
        }
      }
      
      // If not a swap scenario (one or both rooms empty), perform move directly
      performMove(draggedElement, dropTarget, draggedPath, targetPath, draggedOfficeId, targetOfficeId);
    }
  }

  // Re-enable panning if not over a room
  if (window.panZoom && !isOverRoom) {
    window.panZoom.enablePan();
    window.panZoom.enableZoom();
  }

  draggedElement = null;
}

// Helper function to perform swap (both rooms have offices)
function performSwap(draggedElement, dropTarget, draggedPath, targetPath, draggedOfficeId, targetOfficeId) {
  console.log('Performing swap:', { draggedOfficeId, targetOfficeId });
  
  // Get the current styles
  const draggedStyle = window.getComputedStyle(draggedPath);
  const targetStyle = window.getComputedStyle(targetPath);
  const draggedFill = draggedStyle.fill;
  const targetFill = targetStyle.fill;
  
  // Swap fills
  draggedPath.style.fill = targetFill;
  targetPath.style.fill = draggedFill;
  
  // Swap office IDs
  draggedPath.dataset.officeId = targetOfficeId;
  targetPath.dataset.officeId = draggedOfficeId;
  draggedElement.dataset.officeId = targetOfficeId;
  dropTarget.dataset.officeId = draggedOfficeId;
  
  // Find the corresponding office data
  const draggedOffice = officesData.find(o => o.id.toString() === draggedOfficeId);
  const targetOffice = officesData.find(o => o.id.toString() === targetOfficeId);
  
  if (draggedOffice && targetOffice) {
    // Update the labels with the swapped office names using the robust updateRoomLabelMain function
    // This function handles proper centering, multi-line text, and all edge cases
    if (typeof window.updateRoomLabelMain === 'function') {
      // Use the main label update function for proper formatting
      window.updateRoomLabelMain(draggedElement, targetOffice.name);
      window.updateRoomLabelMain(dropTarget, draggedOffice.name);
      console.log('Labels updated using updateRoomLabelMain');
    } else {
      // Fallback to simple label update if main function not available
      const draggedText = draggedElement.querySelector("text");
      const targetText = dropTarget.querySelector("text");
      
      if (draggedText && targetText) {
        updateRoomLabel(draggedText, targetOffice.name);
        updateRoomLabel(targetText, draggedOffice.name);
      }
      console.log('Labels updated using fallback updateRoomLabel');
    }
  }
  
  console.log('Swap completed');
}

// Helper function to perform move (one or both rooms empty)
function performMove(draggedElement, dropTarget, draggedPath, targetPath, draggedOfficeId, targetOfficeId) {
  console.log('Performing move:', { draggedOfficeId, targetOfficeId });
  
  // Get the current styles
  const draggedStyle = window.getComputedStyle(draggedPath);
  const targetStyle = window.getComputedStyle(targetPath);
  const draggedFill = draggedStyle.fill;
  const targetFill = targetStyle.fill;
  
  // Swap fills
  draggedPath.style.fill = targetFill;
  targetPath.style.fill = draggedFill;
  
  if (draggedOfficeId) {
    // Only dragged element has office data - move it to target
    draggedPath.dataset.officeId = "";
    targetPath.dataset.officeId = draggedOfficeId;
    draggedElement.dataset.officeId = "";
    dropTarget.dataset.officeId = draggedOfficeId;
    
    // Update labels
    const draggedText = draggedElement.querySelector("text");
    const targetText = dropTarget.querySelector("text");
    const draggedOffice = officesData.find(o => o.id.toString() === draggedOfficeId);
    
    if (draggedText && targetText && draggedOffice) {
      updateRoomLabel(draggedText, "Unassigned");
      updateRoomLabel(targetText, draggedOffice.name);
    }
  } else if (targetOfficeId) {
    // Only target has office data - move it to dragged element
    draggedPath.dataset.officeId = targetOfficeId;
    targetPath.dataset.officeId = "";
    draggedElement.dataset.officeId = targetOfficeId;
    dropTarget.dataset.officeId = "";
    
    // Update labels
    const draggedText = draggedElement.querySelector("text");
    const targetText = dropTarget.querySelector("text");
    const targetOffice = officesData.find(o => o.id.toString() === targetOfficeId);
    
    if (draggedText && targetText && targetOffice) {
      updateRoomLabel(draggedText, targetOffice.name);
      updateRoomLabel(targetText, "Unassigned");
    }
  }
  
  console.log('Move completed');
}

// Helper function to update room labels
function updateRoomLabel(textElement, officeName) {
  if (!textElement) return;

  // Store original x coordinate for centering
  const originalX = parseFloat(textElement.getAttribute("x")) || 0;

  // Set text-anchor to middle for automatic centering
  textElement.setAttribute("text-anchor", "middle");

  // Clear existing content
  textElement.textContent = "";
  while (textElement.firstChild) {
    textElement.removeChild(textElement.firstChild);
  }

  const lineHeight = "1.2em";
  const words = officeName.split(" ");

  if (words.length > 0) {
    words.forEach((word, index) => {
      const newTspan = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "tspan"
      );
      newTspan.textContent = word;
      newTspan.setAttribute("x", originalX); // Set x for each tspan
      if (index > 0) {
        newTspan.setAttribute("dy", lineHeight);
      }
      textElement.appendChild(newTspan);
    });
  }
}

function handleEditButtonClick(e) {
  console.log("Edit button clicked");
  if (isDragging) {
    e.preventDefault();
    return;
  }

  if (!editButton) {
    console.warn("Edit button click received but no button is bound");
    return;
  }

  isEditMode = !isEditMode;

  if (isEditMode) {
    editButton.textContent = "Save";
    enableDragAndDrop();
    return;
  }

  editButton.textContent = "Edit";
  disableDragAndDrop();

  // Collect current room assignments
  const assignments = [];
  const svg = document.getElementById("svg1");
  if (!svg) {
    console.error("SVG element not found");
    return;
  }

  // Get all room groups
  const roomGroups = svg.querySelectorAll('g[data-room="true"]');
  console.log("Found room groups:", roomGroups.length);

  roomGroups.forEach((group) => {
    // Only process rooms with the correct format
    if (!group.id.match(/^room-\d+(?:-\d+)?$/)) {
      console.log("Skipping room with invalid ID format:", group.id);
      return;
    }

    // Try to get office ID from the group first
    let officeId = group.dataset.officeId;

    // If not found on group, try to get from path or rect
    if (!officeId) {
      const element = group.querySelector("path, rect");
      if (element && element.dataset.officeId) {
        officeId = element.dataset.officeId;
      }
    }

    if (officeId) {
      console.log("Found assignment:", {
        roomId: group.id,
        officeId: officeId,
      });
      assignments.push({
        officeId: officeId,
        roomId: group.id,
      });
    } else {
      console.log("No office ID found for room:", group.id);
    }
  });

  console.log("Total assignments found:", assignments.length);

  if (assignments.length === 0) {
    console.error("No valid assignments found");
    alert("No valid room assignments found to save.");
    return;
  }

  console.log("Sending assignments to server:", assignments);

  // Send assignments to the server
  fetch("floorjs/savePositions.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ assignments }),
  })
    .then((res) => {
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.json();
    })
    .then((data) => {
      console.log("Server response:", data);
      if (data.success) {
        alert("Room positions saved successfully!");
        window.location.reload();
      } else {
        alert(
          "Failed to save room positions: " +
            (data.message || "Unknown error")
        );
      }
    })
    .catch((err) => {
      console.error("Save error:", err);
      alert("Error saving room positions: " + err);
    });
}

function forceExitEditMode() {
  if (isEditMode) {
    console.log("Force exiting edit mode due to external trigger");
  }

  isEditMode = false;
  isDragging = false;
  isOverRoom = false;
  draggedElement = null;
  window.isDragging = false;

  document.removeEventListener("mousemove", handleMouseMove, true);
  document.removeEventListener("mouseup", handleMouseUp, true);

  disableDragAndDrop();

  if (editButton) {
    editButton.textContent = "Edit";
  }

  if (cancelButton) {
    cancelButton.style.display = "none";
  }
}

function handleCancelButtonClick(e) {
  if (e) {
    e.preventDefault();
  }

  if (!isEditMode) {
    return;
  }

  console.log("Cancel button clicked - reverting changes");
  forceExitEditMode();
  window.location.reload();
}

// Wait for DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM fully loaded in dragDropSetup.js");

  // Check if panZoom object exists in window scope
  if (!window.panZoom) {
    console.warn(
      "panZoom object not found in window scope. Wait for it to be available."
    );

    // Set a small delay to check again
    setTimeout(() => {
      if (window.panZoom) {
        console.log("panZoom object found after delay");
      } else {
        console.error(
          "panZoom object still not available. Check script load order."
        );
      }
    }, 1000);
  } else {
    console.log("panZoom object found in window scope");
  }
});

window.refreshDragDropRooms = refreshDragDropRooms;
window.initializeDragDropEditButton = bindEditButton;
window.initializeDragDropCancelButton = bindCancelButton;
window.forceExitDragDropEditMode = forceExitEditMode;
