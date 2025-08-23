document.addEventListener("DOMContentLoaded", function () {
  console.log("labelSetup.js loaded and DOM ready.");

  const officeDetailsModal = document.getElementById("office-details-modal");
  const panelOfficeName = document.getElementById("panel-office-name");
  const officeActiveToggle = document.getElementById("office-active-toggle");
  const officeStatusText = document.getElementById("office-status-text");
  const closePanelBtn = document.getElementById("close-panel-btn");
  const tooltip = document.getElementById("floorplan-tooltip");

  let currentSelectedOffice = null;
  let officeActiveStates = {};

  // Log elements found for debugging
  console.log("Office details modal found:", !!officeDetailsModal);
  console.log("Panel office name found:", !!panelOfficeName);
  console.log("Office active toggle found:", !!officeActiveToggle);
  console.log("Office status text found:", !!officeStatusText);
  console.log("Close panel button found:", !!closePanelBtn);
  console.log("Tooltip found:", !!tooltip);

  function updateRoomLabel(group, officeName) {
    let textEl = group.querySelector("text");
    if (!textEl) {
      const roomElement = group.querySelector("path, rect");
      if(!roomElement) return;

      const roomMatch = roomElement.id.match(/room-(\d+)/);
      if (!roomMatch) return;
      const roomNumber = roomMatch[1];

      // Remove any existing duplicate elsewhere
      const dup = document.querySelector(`#roomlabel-${roomNumber}`);
      if (dup && !group.contains(dup)) dup.remove();

      textEl = document.createElementNS("http://www.w3.org/2000/svg", "text");
      textEl.setAttribute("class", "room-label");
      textEl.setAttribute("id", `roomlabel-${roomNumber}`);
      
      const bbox = roomElement.getBBox();
      textEl.setAttribute("x", bbox.x + bbox.width / 2);
      textEl.setAttribute("y", bbox.y + bbox.height / 2);
      
      // Always append to the group so transforms align
      group.appendChild(textEl);
    }

    // Store original x coordinate for centering
    const originalX = parseFloat(textEl.getAttribute("x")) || 0;

    // Set text-anchor to middle for automatic centering
    textEl.setAttribute("text-anchor", "middle");

    // Clear existing content
    textEl.textContent = "";
    while (textEl.firstChild) {
      textEl.removeChild(textEl.firstChild);
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
            textEl.appendChild(newTspan);
        });
    }
  }

  function updateRoomAppearanceById(officeId, isActive) {
    // Look up the office in officesData to get its location
    const office = officesData.find(
      (o) => o.id.toString() === officeId.toString()
    );
    if (!office) {
      console.warn(`Office with ID ${officeId} not found in officesData`);
      return;
    }

    const locationStr = office.location || "";
    console.log(
      `Updating room appearance for office ${officeId} (${office.name}), location: ${locationStr}, active: ${isActive}`
    );

    // Try multiple ways to find the room group
    let roomGroup = null;
    let roomElement = null;

    // First try to find by room-X-1 pattern
    let roomNum = null;
    if (locationStr) {
      // Try to extract room number from location string
      const roomMatch = locationStr.match(/room-(\d+)/);
      if (roomMatch && roomMatch[1]) {
        roomNum = roomMatch[1];
        // Try new format first
        roomElement = document.getElementById(`room-${roomNum}-1`);
        if (!roomElement) {
          // If not found, try old format
          roomElement = document.getElementById(`g${roomNum}`);
          if (roomElement) {
            // Convert old format to new format
            console.log(
              `Converting old format g${roomNum} to room-${roomNum}-1`
            );
            roomElement.id = `room-${roomNum}-1`;
          }
        }
        if (roomElement) {
          console.log(
            `Found element with ID ${roomElement.id} for office ${office.name}`
          );
          roomGroup = roomElement.closest("g");
          if (roomGroup) {
            console.log(
              `Found parent group ${roomGroup.id} for room ${roomNum}`
            );
            // Set data attributes to help with identification
            roomGroup.dataset.roomNumber = roomNum;
            roomElement.dataset.roomNumber = roomNum;
          }
        }
      }
    }

    // If not found by room-X-1, try direct group ID match using locationStr
    if (!roomGroup && locationStr) {
      // Try new format first
      if (locationStr.startsWith("g")) {
        const gNum = locationStr.substring(1);
        roomGroup = document.getElementById(`room-${gNum}-1`);
        if (!roomGroup) {
          // If not found, try old format
          roomGroup = document.getElementById(locationStr);
          if (roomGroup) {
            // Convert old format to new format
            console.log(
              `Converting old format ${locationStr} to room-${gNum}-1`
            );
            roomGroup.id = `room-${gNum}-1`;
          }
        }
      } else {
        roomGroup = document.getElementById(locationStr);
      }
      if (roomGroup) {
        console.log(`Found group by location string: ${locationStr}`);
      }
    }

    // If still not found, try group by office ID (legacy fallback)
    if (!roomGroup) {
      // Try new format first
      roomGroup = document.getElementById(`room-${id}-1`);
      if (!roomGroup) {
        // If not found, try old format
        roomGroup = document.getElementById(`g${id}`);
        if (roomGroup) {
          // Convert old format to new format
          console.log(`Converting old format g${id} to room-${id}-1`);
          roomGroup.id = `room-${id}-1`;
        }
      }
      if (roomGroup) {
        console.log(`Found group by ID ${roomGroup.id}`);
      }
    }

    if (!roomGroup) {
      console.warn(
        `Room group not found for office ${officeId} with location ${locationStr}`
      );
      return;
    }

    // Find the path or rect element inside the group if we haven't already
    if (!roomElement) {
      roomElement = roomGroup.querySelector("path, rect");
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

  if (
    !officeDetailsModal ||
    !panelOfficeName ||
    !officeActiveToggle ||
    !officeStatusText ||
    !closePanelBtn
  ) {
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

    // Map office data to room groups
    officesData.forEach((office) => {
      const id = office.id;
      const idStr = id.toString();
      const officeName = office.name || `Office ${id}`;

      // Get location from office data
      const locationStr = office.location || "";

      // Find room elements using multiple methods
      let roomGroup = null;
      let roomElement = null;

      // First try to find by room-X-1 pattern
      let roomNum = null;
      if (locationStr) {
        // Try to extract room number from location string
        const roomMatch = locationStr.match(/room-(\d+)/);
        if (roomMatch && roomMatch[1]) {
          roomNum = roomMatch[1];
          // Try new format first
          roomElement = document.getElementById(`room-${roomNum}-1`);
          if (!roomElement) {
            // If not found, try old format
            roomElement = document.getElementById(`g${roomNum}`);
            if (roomElement) {
              // Convert old format to new format
              console.log(
                `Converting old format g${roomNum} to room-${roomNum}-1`
              );
              roomElement.id = `room-${roomNum}-1`;
            }
          }
          if (roomElement) {
            console.log(
              `Found element with ID ${roomElement.id} for office ${officeName}`
            );
            roomGroup = roomElement.closest("g");
            if (roomGroup) {
              console.log(
                `Found parent group ${roomGroup.id} for room ${roomNum}`
              );
              // Set data attributes to help with identification
              roomGroup.dataset.roomNumber = roomNum;
              roomElement.dataset.roomNumber = roomNum;
            }
          }
        }
      }

      // If not found by room-X-1, try direct group ID match using locationStr
      if (!roomGroup && locationStr) {
        // Try new format first
        if (locationStr.startsWith("g")) {
          const gNum = locationStr.substring(1);
          roomGroup = document.getElementById(`room-${gNum}-1`);
          if (!roomGroup) {
            // If not found, try old format
            roomGroup = document.getElementById(locationStr);
            if (roomGroup) {
              // Convert old format to new format
              console.log(
                `Converting old format ${locationStr} to room-${gNum}-1`
              );
              roomGroup.id = `room-${gNum}-1`;
            }
          }
        } else {
          roomGroup = document.getElementById(locationStr);
        }
        if (roomGroup) {
          console.log(`Found group by location string: ${locationStr}`);
        }
      }

      // If still not found, try group by office ID (legacy fallback)
      if (!roomGroup) {
        // Try new format first
        roomGroup = document.getElementById(`room-${id}-1`);
        if (!roomGroup) {
          // If not found, try old format
          roomGroup = document.getElementById(`g${id}`);
          if (roomGroup) {
            // Convert old format to new format
            console.log(`Converting old format g${id} to room-${id}-1`);
            roomGroup.id = `room-${id}-1`;
          }
        }
        if (roomGroup) {
          console.log(`Found group by ID ${roomGroup.id}`);
        }
      }

      if (!roomGroup) {
        console.warn(
          `Room not found for office ${officeName} (ID: ${id}, Location: ${locationStr})`
        );
        return;
      }

      console.log(
        `Processing room for office ${officeName}, using element: ${roomGroup.id}`
      );

      // Find the path or rect element inside the group if we haven't already
      if (!roomElement) {
        roomElement = roomGroup.querySelector("path, rect");
      }

      // Find text element for the label
      let textEl = roomGroup.querySelector("text");
      // Only update the existing <text> element, do not create new ones
      if (textEl) {
        // Save original style and position
        const originalX = parseFloat(textEl.getAttribute("x")) || 0;
        const originalY = parseFloat(textEl.getAttribute("y")) || 0;
        const originalFill = textEl.getAttribute("fill");
        const originalFontSize = textEl.getAttribute("font-size");
        const originalFontWeight = textEl.getAttribute("font-weight");
        const originalTextAnchor = textEl.getAttribute("text-anchor");
        const originalAlignmentBaseline =
          textEl.getAttribute("alignment-baseline");
        // Clear existing tspans or text content
        textEl.textContent = "";
        while (textEl.firstChild) {
          textEl.removeChild(textEl.firstChild);
        }
        if (officeName.includes(" ")) {
          const words = officeName.split(" ");
          words.forEach((word, index) => {
            const newTspan = document.createElementNS(
              "http://www.w3.org/2000/svg",
              "tspan"
            );
            newTspan.textContent = word;
            newTspan.setAttribute("x", originalX);
            if (index > 0) newTspan.setAttribute("dy", "1.2em");
            textEl.appendChild(newTspan);
          });
        } else {
          const newTspan = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "tspan"
          );
          newTspan.textContent = officeName;
          newTspan.setAttribute("x", originalX);
          textEl.appendChild(newTspan);
        }

        // Center the text by calculating bounding box and adjusting position
        try {
          // Force layout update
          textEl.getBBox();
          
          // Get the bounding box of the updated text
          const bbox = textEl.getBBox();
          
          // Calculate offset to center the text horizontally around original X
          const centerOffset = bbox.width / 2;
          const centeredX = originalX - centerOffset;
          
          // Update all tspan x positions to center the text
          const tspans = textEl.querySelectorAll("tspan");
          tspans.forEach((tspan) => {
            tspan.setAttribute("x", centeredX);
          });
          
          // Update the text element's x attribute for consistency
          textEl.setAttribute("x", centeredX);
          
        } catch (error) {
          console.warn("Could not center text, using original position:", error);
        }

        // Restore original style and position (y remains unchanged)
        textEl.setAttribute("y", originalY);
        if (originalFill) textEl.setAttribute("fill", originalFill);
        if (originalFontSize)
          textEl.setAttribute("font-size", originalFontSize);
        if (originalFontWeight)
          textEl.setAttribute("font-weight", originalFontWeight);
        if (originalTextAnchor)
          textEl.setAttribute("text-anchor", originalTextAnchor);
        if (originalAlignmentBaseline)
          textEl.setAttribute("alignment-baseline", originalAlignmentBaseline);
        // Style for interactivity
        textEl.style.cursor = "pointer";
        textEl.style.pointerEvents = "auto";
      }
      // Log what we found for debugging
      console.log(
        `  Room element found: ${roomElement ? roomElement.id : "none"}`
      );
      console.log(`  Text element found: ${!!textEl}`);
      // Set office ID on both group and element
      roomGroup.dataset.officeId = id;
      if (roomElement) {
        roomElement.dataset.officeId = id;
        roomElement.classList.add("interactive-room"); // Add interactive class to element
        roomElement.style.cursor = "pointer";
        roomElement.style.pointerEvents = "auto"; // Ensure click events are captured
      }

      // Add click event to both the group and the element
      // --- MOBILE-OPTIMIZED CLICK HANDLER ---
      const handleRoomClick = function (e) {
        // Check if we're in edit mode - if so, don't show the dialog
        if (document.body.classList.contains("edit-mode-active")) {
          return;
        }

        // Prevent default and stop propagation for mobile
        e.stopPropagation();
        // Do not call preventDefault to allow pan/zoom
        currentSelectedOffice = office;
        // --- MOBILE: Use drawer if available ---
        if (typeof window.populateAndShowDrawerWithData === "function") {
          window.populateAndShowDrawerWithData(office);
          return;
        }
        // --- END MOBILE ---
        // --- DESKTOP: Use panel if available ---
        if (typeof panelOfficeName !== "undefined" && panelOfficeName) {
          panelOfficeName.textContent = office.name || "N/A";
        }
        if (typeof officeActiveToggle !== "undefined" && officeActiveToggle) {
          const isActive = officeActiveStates[id.toString()];
          officeActiveToggle.checked = isActive;
        }
        if (typeof officeStatusText !== "undefined" && officeStatusText) {
          const isActive = officeActiveStates[id.toString()];
          officeStatusText.textContent = isActive ? "Active" : "Inactive";
          officeStatusText.style.color = isActive ? "#4CAF50" : "#f44336";
        }
  // Do not update/recreate the label on click to avoid duplicate/misaligned tspans
        if (typeof officeDetailsModal !== "undefined" && officeDetailsModal) {
          openModal();
        }
      };

      // Add click handler to both the group and the element
      roomGroup.addEventListener("click", handleRoomClick, true);
      if (roomElement) {
        console.log(
          `Adding click handler to room element: ${roomElement.id} (${roomElement.tagName})`
        );
        roomElement.addEventListener("click", handleRoomClick, true);
      }

      if (textEl) {
        // Apply styling from mobileLabelSetup.js
        textEl.style.fill = "white";
        textEl.style.stroke = "black";
        textEl.style.strokeWidth = "0.5px";
        textEl.style.fontWeight = "bold";
        textEl.style.cursor = "pointer"; // Make text clickable

        // Apply line-breaking logic from mobileLabelSetup.js
        const originalX = textEl.getAttribute("x") || 0;
        const lineHeight = "1.2em"; // Consistent with mobile

        // Clear existing tspans or text content
        textEl.textContent = ""; // Clear direct text content
        while (textEl.firstChild) {
          // Remove existing tspan children if any
          textEl.removeChild(textEl.firstChild);
        }

        if (officeName.includes(" ")) {
          const words = officeName.split(" "); // Split into words
          words.forEach((word, index) => {
            const newTspan = document.createElementNS(
              "http://www.w3.org/2000/svg",
              "tspan"
            );
            newTspan.textContent = word;
            newTspan.setAttribute("x", originalX); // Reset x for each line
            if (index > 0) {
              newTspan.setAttribute("dy", lineHeight); // Apply vertical shift for subsequent lines
            }
            textEl.appendChild(newTspan);
          });
        } else {
          // If no space, use a single tspan
          const newTspan = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "tspan"
          );
          newTspan.textContent = officeName;
          newTspan.setAttribute("x", originalX);
          textEl.appendChild(newTspan);
        }

        // Apply active/inactive class to the textEl itself
        textEl.classList.toggle(
          "text-label-inactive",
          !officeActiveStates[idStr]
        );

        // Add click listener to textEl
        textEl.addEventListener("click", handleRoomClick);

        // Add tooltip to textEl
        if (tooltip) {
          textEl.addEventListener("mousemove", function (e) {
            // Don't show tooltip if in edit mode
            if (document.body.classList.contains("edit-mode-active")) {
              return;
            }

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
