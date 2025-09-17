// Camera hotspot component for panorama navigation
AFRAME.registerComponent('camera-hotspot', {
  schema: {
    pathId: {type: 'string'},
    pointIndex: {type: 'number'},
    floorNumber: {type: 'number'}
  },
  init: function () {
    this.el.addEventListener('click', () => {
      console.log('Camera hotspot clicked!');
      const newUrl = `pano.html?path_id=${this.data.pathId}&point_index=${this.data.pointIndex}&floor_number=${this.data.floorNumber}`;
      window.location.href = newUrl;
    });
  }
});

// Function to reset all camera hotspots to default state
function resetCameraHotspots() {
  document.querySelectorAll('.camera-hotspot').forEach(h => {
    const body = h.querySelector('a-box');
    const lens = h.querySelector('a-cylinder');
    if (body && lens) {
      body.setAttribute('material', 'color: #2563eb; opacity: 0.9');
      lens.setAttribute('material', 'color: #1e40af; opacity: 0.9');
      h.setAttribute('scale', '1 1 1');
    }
  });
}

// Initialize camera hotspots when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
  console.log('Camera hotspot system initialized');
}); 