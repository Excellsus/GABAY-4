document.addEventListener("DOMContentLoaded", function () {
  console.log("labelSetup.js loaded and DOM ready.");

  // Cache DOM elements once to avoid repeated queries
  const domCache = {
    officeDetailsModal: document.getElementById("office-details-modal"),
    panelOfficeName: document.getElementById("panel-office-name"),
    officeActiveToggle: document.getElementById("office-active-toggle"),
    officeStatusText: document.getElementById("office-status-text"),
    closePanelBtn: document.getElementById("close-panel-btn"),
    tooltip: document.getElementById("floorplan-tooltip"),
    // Cache SVG elements for better performance
    svg: null,
    svgViewport: null
  };

  let currentSelectedOffice = null;
  let officeActiveStates = {};
  let roomElementCache = new Map(); // Cache room elements to avoid repeated DOM queries

  function labelBelongsToRoom(tspanEl, roomNumber) {
    if (!tspanEl) return false;
    const parentText = tspanEl.closest('text');
    if (!parentText || !parentText.id) return false;
    const parentId = parentText.id.trim();
    if (!parentId.startsWith('text-')) return false;
    if (parentId === `text-${roomNumber}`) return true;
    return parentId.startsWith(`text-${roomNumber}-`);
  }

  function findLabelTspanForRoom(roomNumber) {
    const directMatch = document.getElementById(`roomlabel-${roomNumber}`);
    if (labelBelongsToRoom(directMatch, roomNumber)) {
      return directMatch;
    }

    const textMatches = document.querySelectorAll(`text[id^="text-${roomNumber}"]`);
    for (const textNode of textMatches) {
      const tspanCandidate = textNode.querySelector('tspan');
      if (tspanCandidate) {
        return tspanCandidate;
      }
    }

    const allLabels = document.querySelectorAll('tspan[id^="roomlabel-"]');
    for (const candidate of allLabels) {
      if (labelBelongsToRoom(candidate, roomNumber)) {
        return candidate;
      }
    }

    return null;
  }

  // Log elements found for debugging
  console.log("Office details modal found:", !!officeDetailsModal);
  console.log("Panel office name found:", !!panelOfficeName);
  console.log("Office active toggle found:", !!officeActiveToggle);
  console.log("Office status text found:", !!officeStatusText);
  console.log("Close panel button found:", !!closePanelBtn);
  console.log("Tooltip found:", !!tooltip);

  function updateRoomLabel(group, officeName) {
    if (!group) return;

    const roomElement = group.querySelector("path, rect");
    if (!roomElement || !roomElement.id) return;

    const roomMatch = roomElement.id.match(/room-(\d+)(?:-(\d+))?/);
    if (!roomMatch) return;

    const roomNumber = roomMatch[1];
    let labelId = `roomlabel-${roomNumber}`;

    let textEl = group.querySelector("text");
    let originalX;
    let originalY;

    let tspanEl = findLabelTspanForRoom(roomNumber);

    if (!tspanEl && textEl) {
      const existingTspan = textEl.querySelector('tspan');
      if (existingTspan) {
        tspanEl = existingTspan;
      }
    }

    if (!tspanEl) {
      const groupTspan = group.querySelector('text tspan');
      if (groupTspan) {
        tspanEl = groupTspan;
      }
    }

    if (tspanEl && tspanEl.tagName === 'tspan') {
      labelId = tspanEl.id || labelId;
      const parentText = tspanEl.closest('text');
      if (parentText) {
        textEl = parentText;
        const referenceTspan = parentText.querySelector('tspan') || tspanEl;
        originalX = parseFloat(referenceTspan.getAttribute('x')) || parseFloat(parentText.getAttribute('x'));
        originalY = parseFloat(referenceTspan.getAttribute('y')) || parseFloat(parentText.getAttribute('y'));
      } else {
        textEl = tspanEl.parentElement;
        originalX = parseFloat(tspanEl.getAttribute('x'));
        originalY = parseFloat(tspanEl.getAttribute('y'));
      }
    } else if (textEl) {
      originalX = parseFloat(textEl.getAttribute("x"));
      originalY = parseFloat(textEl.getAttribute("y"));
    }

    if (!textEl) {
      const bbox = roomElement.getBBox();
      originalX = bbox.x + bbox.width / 2;
      originalY = bbox.y + bbox.height / 2;

      textEl = document.createElementNS("http://www.w3.org/2000/svg", "text");
      textEl.setAttribute("class", "room-label");
      textEl.setAttribute("id", labelId);
      textEl.setAttribute("x", originalX);
      textEl.setAttribute("y", originalY);

      group.appendChild(textEl);
    }

    textEl.setAttribute("text-anchor", "middle");
    textEl.setAttribute("dominant-baseline", "central");

    textEl.style.fontFamily = "'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif";
    textEl.style.fontWeight = "600";
    textEl.style.fontSize = "14px";
    textEl.style.fill = "#1a1a1a";
    textEl.style.stroke = "#ffffff";
    textEl.style.strokeWidth = "3px";
    textEl.style.strokeLinejoin = "round";
    textEl.style.paintOrder = "stroke fill";
    textEl.style.vectorEffect = "non-scaling-stroke";

    textEl.textContent = "";
    while (textEl.firstChild) {
      textEl.removeChild(textEl.firstChild);
    }

    const lineHeight = "1.2em";
    const words = officeName.split(" ");

    words.forEach((word, index) => {
      const newTspan = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "tspan"
      );
      newTspan.textContent = word;
      newTspan.setAttribute("x", originalX);
      newTspan.style.fontFamily = "'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif";
      newTspan.style.fontWeight = "600";
      newTspan.style.fontSize = "14px";

      if (index > 0) {
        newTspan.setAttribute("dy", lineHeight);
      }
      if (index === 0) {
        newTspan.setAttribute("id", labelId);
      }
      textEl.appendChild(newTspan);
    });

    textEl.setAttribute("x", originalX);
    textEl.setAttribute("y", originalY);
  }

  // Optimized room finding with caching
  function findRoomElements(office) {
    const cacheKey = `${office.id}-${office.location}`;
    
    // Return cached result if available
    if (roomElementCache.has(cacheKey)) {
      return roomElementCache.get(cacheKey);
    }

    const locationStr = office.location || "";
    let roomGroup = null;
    let roomElement = null;

    // Optimized search order: try most common patterns first
    const searchPatterns = [
      () => document.getElementById(locationStr), // Direct location match (e.g., "room-18-2")
      () => {
        // Try to find room on any floor by extracting room number and floor
        const roomMatch = locationStr.match(/room-(\d+)(?:-(\d+))?/);
        if (!roomMatch) return null;
        const roomNum = roomMatch[1];
        const floorNum = roomMatch[2] || '1'; // Default to floor 1 if not specified
        return document.getElementById(`room-${roomNum}-${floorNum}`);
      },
      () => document.getElementById(`room-${office.id}-1`), // Office ID pattern (legacy)
      () => document.getElementById(`g${office.id}`) // Legacy pattern
    ];

    for (const searchFn of searchPatterns) {
      roomElement = searchFn();
      if (roomElement) {
        roomGroup = roomElement.closest("g") || roomElement.parentElement || roomElement;
        break;
      }
    }

    const result = { roomGroup, roomElement };
    roomElementCache.set(cacheKey, result); // Cache the result
    return result;
  }

  function updateRoomAppearanceById(officeId, isActive) {
    // Look up the office in officesData to get its location
    const office = officesData.find(o => o.id.toString() === officeId.toString());
    if (!office) {
      console.warn(`Office with ID ${officeId} not found in officesData`);
      return;
    }

    console.log(`Updating room appearance for office ${officeId} (${office.name}), active: ${isActive}`);

    // Use optimized room finder
    const { roomGroup, roomElement } = findRoomElements(office);

    if (!roomGroup) {
      console.warn(`Room group not found for office ${officeId} with location ${office.location}`);
      return;
    }

    const textEl = roomGroup.querySelector("text");

    if (roomElement) {
      console.log(
        `Updating appearance for room element ${roomElement.id} in group ${roomGroup.id}`
      );
      roomElement.classList.toggle("room-inactive", !isActive);
      roomElement.classList.add("interactive-room");
      roomElement.style.cursor = "pointer";
      roomElement.style.pointerEvents = "auto";
    }

    if (textEl) {
      console.log(
        `Updating appearance for text element in group ${roomGroup.id}`
      );
      textEl.classList.toggle("text-label-inactive", !isActive);
      // Update the label text
      updateRoomLabel(roomGroup, office.name);
    }

    console.log(
      `Updated appearance for room ${officeId} (${roomGroup.id}), active: ${isActive}`
    );
  }

  function updateOfficeStatusInDB(officeId, newStatus) {
    console.log(`Updating office status in DB: ${officeId} -> ${newStatus}`);
    fetch("api/update_office_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ office_id: officeId, status: newStatus }),
    })
      .then((res) => {
        if (!res.ok) {
          return res.text().then((text) => {
            throw new Error(`Server error ${res.status}: ${text}`);
          });
        }
        return res.json();
      })
      .then((data) => {
        if (!data.success) {
          alert("Failed to update office status.");
          console.error("Server error:", data.message);
        }
      })
      .catch((err) => {
        console.error("Update error:", err);
        alert("An error occurred while updating office status.");
      });
  }

  // Use cached DOM elements
  const { officeDetailsModal, panelOfficeName, officeActiveToggle, officeStatusText, closePanelBtn, tooltip } = domCache;

  if (!officeDetailsModal || !panelOfficeName || !officeActiveToggle || !officeStatusText || !closePanelBtn) {
    console.error("Missing one or more essential panel elements.");
    return;
  }

  if (!tooltip) console.warn("Tooltip element not found.");

  if (typeof officesData !== "undefined" && officesData) {
    console.log(`Processing ${officesData.length} offices`);

    officesData.forEach((office) => {
      const officeId = office.id.toString();
      officeActiveStates[officeId] = office.status === "active";
    });

    officeActiveToggle.addEventListener("change", function () {
      if (currentSelectedOffice) {
        const idStr = currentSelectedOffice.id.toString();
        const isActive = officeActiveToggle.checked;

        officeActiveStates[idStr] = isActive;
        officeStatusText.textContent = isActive ? "Active" : "Inactive";
        officeStatusText.style.color = isActive ? "#4CAF50" : "#f44336";
        updateRoomAppearanceById(currentSelectedOffice.id, isActive);
        updateOfficeStatusInDB(
          currentSelectedOffice.id,
          isActive ? "active" : "inactive"
        );
      }
    });

    // Cache SVG elements for better performance
    domCache.svg = document.querySelector('svg');
    domCache.svgViewport = domCache.svg?.querySelector('.svg-pan-zoom_viewport');

    // Create office lookup map for faster access
    const officeMap = new Map(officesData.map(office => [office.id.toString(), office]));

    // Map office data to room groups
    officesData.forEach((office) => {
      const id = office.id;
      const idStr = id.toString();
      const officeName = office.name || `Office ${id}`;

      // Use optimized room finder
      const { roomGroup, roomElement } = findRoomElements(office);

      if (!roomGroup) {
        console.warn(`Room not found for office ${officeName} (ID: ${id}, Location: ${office.location})`);
        return;
      }

      console.log(`Processing room for office ${officeName}, using element: ${roomGroup.id}`);

      // Update room label with optimized function
      updateRoomLabel(roomGroup, officeName);

      // Set office ID on both group and element
      roomGroup.dataset.officeId = id;
      if (roomElement) {
        roomElement.dataset.officeId = id;
        roomElement.classList.add("interactive-room");
        roomElement.style.cursor = "pointer";
        roomElement.style.pointerEvents = "auto";
      }

      // Define click handler for this office
      const handleRoomClick = function (e) {
        if (document.body.classList.contains("edit-mode-active")) return;
        
        e.stopPropagation();
        currentSelectedOffice = office;
        
        // Mobile: Use drawer if available
        if (typeof window.populateAndShowDrawerWithData === "function") {
          window.populateAndShowDrawerWithData(office);
          return;
        }
        
        // Desktop: Use panel if available
        if (panelOfficeName) panelOfficeName.textContent = office.name || "N/A";
        if (officeActiveToggle) {
          const isActive = officeActiveStates[idStr];
          officeActiveToggle.checked = isActive;
        }
        if (officeStatusText) {
          const isActive = officeActiveStates[idStr];
          officeStatusText.textContent = isActive ? "Active" : "Inactive";
          officeStatusText.style.color = isActive ? "#4CAF50" : "#f44336";
        }
        if (officeDetailsModal) openModal();
      };

      // Add click handler to both the group and the element
      roomGroup.addEventListener("click", handleRoomClick, true);
      if (roomElement) {
        roomElement.addEventListener("click", handleRoomClick, true);
      }

      // Find and configure text element
      let textEl = roomGroup.querySelector("text");
      if (textEl) {
        // Apply active/inactive class
        textEl.classList.toggle("text-label-inactive", !officeActiveStates[idStr]);
        textEl.style.cursor = "pointer";
        textEl.style.pointerEvents = "auto";

        // Add click listener to textEl
        textEl.addEventListener("click", handleRoomClick);

        // Add tooltip to textEl
        if (tooltip) {
          textEl.addEventListener("mousemove", function (e) {
            if (document.body.classList.contains("edit-mode-active")) return;
            tooltip.innerHTML = officeName;
            tooltip.style.display = "block";
            tooltip.style.left = e.pageX + 15 + "px";
            tooltip.style.top = e.pageY + 15 + "px";
          });
          textEl.addEventListener("mouseout", function () {
            tooltip.style.display = "none";
          });
        }
      }

      if (roomElement) {
        roomElement.classList.add("interactive-room");
        roomElement.style.cursor = "pointer";

        // Apply active/inactive styling
        if (!officeActiveStates[idStr]) {
          roomElement.classList.add("room-inactive");
          if (textEl) textEl.classList.add("text-label-inactive");
        }

        if (tooltip) {
          roomElement.addEventListener("mousemove", function (e) {
            // Don't show tooltip if in edit mode
            if (document.body.classList.contains("edit-mode-active")) {
              return;
            }

            tooltip.innerHTML = officeName;
            tooltip.style.display = "block";
            tooltip.style.left = e.pageX + 15 + "px";
            tooltip.style.top = e.pageY + 15 + "px";
          });

          roomElement.addEventListener("mouseout", function () {
            tooltip.style.display = "none";
          });
        }
      }
    });
  } else {
    console.error("Offices data (officesData) is missing.");
  }

  // Function to open modal
  function openModal() {
    officeDetailsModal.classList.add("active");
  }

  // Function to close modal
  function closeModal() {
    officeDetailsModal.classList.remove("active");
    currentSelectedOffice = null;
  }

  // Close modal when clicking close button
  closePanelBtn.addEventListener("click", closeModal);

  // Close modal when clicking outside
  window.addEventListener("click", function (e) {
    if (e.target === officeDetailsModal) {
      closeModal();
    }
  });

  // Handle escape key to close modal
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && officeDetailsModal.classList.contains("active")) {
      closeModal();
    }
  });

  console.log("Label setup complete");
});
