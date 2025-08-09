// Script to ensure all room paths have interactive-room class
document.addEventListener("DOMContentLoaded", function () {
    console.log("roomClassFix.js loaded - applying interactive-room class to all room paths");
    
    // Function to apply the class to all room paths
    function applyInteractiveRoomClass() {
        // Target paths with room IDs (both with and without dash)
        const roomPaths = document.querySelectorAll('path[id^="room-"], path[id^="room"]');
        console.log(`Found ${roomPaths.length} room paths to apply interactive-room class to`);
        
        roomPaths.forEach(path => {
            path.classList.add("interactive-room");
            path.style.cursor = "pointer";
            console.log(`Added interactive-room class to ${path.id}`);
            
            // Get parent group
            const parentGroup = path.closest('g');
            if (parentGroup) {
                parentGroup.setAttribute('data-room', 'true');
                console.log(`Marked parent group ${parentGroup.id} as room for ${path.id}`);
            }
        });
        
        // Also apply to all paths within room groups
        const roomGroups = document.querySelectorAll('g[data-room="true"]');
        roomGroups.forEach(group => {
            const paths = group.querySelectorAll('path');
            paths.forEach(path => {
                path.classList.add("interactive-room");
                path.style.cursor = "pointer";
                console.log(`Added interactive-room class to path in room group ${group.id}`);
            });
        });
        
        // Add click events to show path IDs when clicked - only in test mode
        const isTestMode = true; // Set to false in production
        if (isTestMode) {
            roomPaths.forEach(path => {
                path.addEventListener('click', function() {
                    console.log(`Clicked on room path: ${path.id}`);
                    // Extract room number from ID
                    let roomNumber = '';
                    if (path.id.includes('room-')) {
                        // Format: room-X-1
                        roomNumber = path.id.split('-')[1];
                    } else if (path.id.includes('room')) {
                        // Format: roomX-1
                        const match = path.id.match(/room(\d+)/);
                        if (match) roomNumber = match[1];
                    }
                    console.log(`Room number: ${roomNumber}`);
                });
            });
        }
    }
    
    // Run immediately
    applyInteractiveRoomClass();
    
    // Also run after a small delay to ensure it runs after other scripts
    setTimeout(applyInteractiveRoomClass, 1000);
}); 