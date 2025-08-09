// javascript/panZoomSetup.js - UPDATED VERSION

document.addEventListener("DOMContentLoaded", function () {
  console.log("UPDATED VERSION - Initializing SVG Pan & Zoom and Click Events...");

  const svg = document.getElementById("svg1");
  // Updated selector to match the actual SVG structure (g elements with IDs starting with g)
  const roomGroups = svg ? svg.querySelectorAll('g[id^="g"]') : [];

  // --- Basic Checks ---
  if (!svg) {
    console.error("SVG container (#svg1) not found!");
    return; // Stop script execution if SVG is missing
  } else {
    console.log("SVG container (#svg1) found.");
  }

  if (roomGroups.length === 0) {
    console.warn(
      "No room groups found with selector 'g[id^=\"g\"]'. Check group IDs in the SVG."
    );
  } else {
    console.log(`Found ${roomGroups.length} room groups - UPDATED VERSION RUNNING`);
  }
  // --- End Basic Checks ---

  // --- Add Click Listener to Rooms ---
  if (roomGroups.length > 0) {
    roomGroups.forEach((group) => {
      // Add the 'click' event listener to each room group
      group.addEventListener("click", (event) => {
        // Don't add simple click listeners here as they're handled in labelSetup.js
        // This would conflict with the office panel opening
        console.log("Group clicked:", group.id);
      });
    });
    console.log("Click listeners added to room groups.");
  }
  // --- End Click Listener Setup ---

  // --- Initialize svg-pan-zoom ---
  if (typeof svgPanZoom === "function") {
    console.log("Initializing svg-pan-zoom...");
    try {
      // Create the pan-zoom instance
      const panZoomInstance = svgPanZoom("#svg1", {
        zoomEnabled: true,
        controlIconsEnabled: true,
        fit: true,
        center: true,
        panEnabled: true,
        minZoom: 0.5,
        maxZoom: 10,
        beforePan: function(oldPan, newPan) {
          // This function can be used to limit panning if needed
          return newPan;
        }
      });
      
      console.log("svg-pan-zoom initialized successfully.");
      
      // Make the instance available globally so other scripts can access it
      window.panZoom = panZoomInstance;

      // Make the pan/zoom instance responsive to window resizing
      window.addEventListener("resize", () => {
        console.log("Window resized, adjusting SVG pan/zoom.");
        panZoomInstance.resize();
        panZoomInstance.fit();
        panZoomInstance.center();
      });
    } catch (e) {
      console.error("Error initializing svg-pan-zoom:", e);
    }
  } else {
    console.error(
      "svg-pan-zoom library not found or loaded. Make sure the script tag for svg-pan-zoom.min.js is included BEFORE this file in the HTML."
    );
  }

  console.log(
    "SVG Pan & Zoom and Click Events script finished initialization - UPDATED VERSION"
  );
});
