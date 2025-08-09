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
      if (group.id.match(/^room-\d+-1$/)) {
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
      if (element.id.match(/^room-\d+-1$/)) {
        const parentGroup = element.closest("g");
        if (parentGroup) {
          parentGroup.setAttribute("data-room", "true");
          // If the element has an office ID, copy it to the parent group
          if (element.dataset.officeId) {
            parentGroup.dataset.officeId = element.dataset.officeId;
          }
          // Extract room number from element ID
          const roomMatch = element.id.match(/room-(\d+)-1/);
          if (roomMatch && roomMatch[1]) {
            parentGroup.dataset.roomNumber = roomMatch[1];
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
    const roomMatch = group.id.match(/room-(\d+)-1/);
    if (roomMatch) {
      console.log(
        `Identified room ${roomMatch[1]}: ${group.tagName} #${group.id}`
      );
    }
  });

  return [...allRoomGroups];
}

// Identify rooms and get only the room elements
const rooms = identifyRooms();

const editButton = document.getElementById("edit-floorplan-btn");
const floorPlanContainer = document.querySelector(".floor-plan-container");
let isEditMode = false;
let isDragging = false;
let isOverRoom = false;
let draggedElement = null;
let startX = 0;
let startY = 0;

console.log("Drag Drop Setup script loaded - UPDATED VERSION");
console.log(
  `Found ${rooms.length} draggable room elements after classification`
);
console.log(`Edit button found: ${editButton !== null}`);

// Log each room ID for debugging
rooms.forEach((room, index) => {
  console.log(`Room ${index + 1}: ${room.id}`);
});

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

  // Display a message to the user
  const editModeMsg = document.createElement("div");
  editModeMsg.id = "edit-mode-message";
  editModeMsg.className = "edit-mode-message";
  editModeMsg.textContent = "Edit Mode: Drag rooms to reposition them";
  floorPlanContainer.appendChild(editModeMsg);

  // Make each room draggable
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
      // Get the current styles
      const draggedStyle = window.getComputedStyle(draggedPath);
      const targetStyle = window.getComputedStyle(targetPath);

      const draggedFill = draggedStyle.fill;
      const targetFill = targetStyle.fill;

      // Get office IDs
      const draggedOfficeId =
        draggedElement.dataset.officeId || draggedPath.dataset.officeId;
      const targetOfficeId =
        dropTarget.dataset.officeId || targetPath.dataset.officeId;

      console.log("Before swap - Office IDs:", {
        draggedElement: {
          id: draggedElement.id,
          officeId: draggedOfficeId,
        },
        dropTarget: {
          id: dropTarget.id,
          officeId: targetOfficeId,
        },
      });

      // Swap fills
      draggedPath.style.fill = targetFill;
      targetPath.style.fill = draggedFill;

      // Handle office data swapping
      if (draggedOfficeId && targetOfficeId) {
        // Both rooms have office data - swap them
        draggedPath.dataset.officeId = targetOfficeId;
        targetPath.dataset.officeId = draggedOfficeId;
        draggedElement.dataset.officeId = targetOfficeId;
        dropTarget.dataset.officeId = draggedOfficeId;

        // Find the corresponding office data
        const draggedOffice = officesData.find(
          (o) => o.id.toString() === draggedOfficeId
        );
        const targetOffice = officesData.find(
          (o) => o.id.toString() === targetOfficeId
        );

        if (draggedOffice && targetOffice) {
          // Update the labels with the swapped office names
          const draggedText = draggedElement.querySelector("text");
          const targetText = dropTarget.querySelector("text");

          if (draggedText && targetText) {
            // Update labels
            updateRoomLabel(draggedText, targetOffice.name);
            updateRoomLabel(targetText, draggedOffice.name);
          }
        }
      } else if (draggedOfficeId) {
        // Only dragged element has office data - move it to target
        draggedPath.dataset.officeId = "";
        targetPath.dataset.officeId = draggedOfficeId;
        draggedElement.dataset.officeId = "";
        dropTarget.dataset.officeId = draggedOfficeId;

        // Update labels
        const draggedText = draggedElement.querySelector("text");
        const targetText = dropTarget.querySelector("text");
        const draggedOffice = officesData.find(
          (o) => o.id.toString() === draggedOfficeId
        );

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
        const targetOffice = officesData.find(
          (o) => o.id.toString() === targetOfficeId
        );

        if (draggedText && targetText && targetOffice) {
          updateRoomLabel(draggedText, targetOffice.name);
          updateRoomLabel(targetText, "Unassigned");
        }
      }
      // If neither has office data, no need to update anything

      console.log("After swap - Office IDs:", {
        draggedElement: {
          id: draggedElement.id,
          officeId: draggedElement.dataset.officeId,
        },
        dropTarget: {
          id: dropTarget.id,
          officeId: dropTarget.dataset.officeId,
        },
      });
    }
  }

  // Re-enable panning if not over a room
  if (window.panZoom && !isOverRoom) {
    window.panZoom.enablePan();
    window.panZoom.enableZoom();
  }

  draggedElement = null;
}

// Helper function to update room labels
function updateRoomLabel(textElement, officeName) {
  // Clear existing content
  textElement.textContent = "";

  // Get original x position
  const x = textElement.getAttribute("x") || 0;
  const lineHeight = "1.2em";

  if (officeName.includes(" ")) {
    const words = officeName.split(" ");
    words.forEach((word, index) => {
      const tspan = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "tspan"
      );
      tspan.textContent = word;
      tspan.setAttribute("x", x);
      if (index > 0) tspan.setAttribute("dy", lineHeight);
      textElement.appendChild(tspan);
    });
  } else {
    const tspan = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "tspan"
    );
    tspan.textContent = officeName;
    tspan.setAttribute("x", x);
    textElement.appendChild(tspan);
  }
}

// Toggle edit mode
editButton.addEventListener("click", (e) => {
  console.log("Edit button clicked");
  if (isDragging) {
    e.preventDefault();
    return;
  }

  isEditMode = !isEditMode;
  if (isEditMode) {
    editButton.textContent = "Save";
    enableDragAndDrop();
  } else {
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
      if (!group.id.match(/^room-\d+-1$/)) {
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
});

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
