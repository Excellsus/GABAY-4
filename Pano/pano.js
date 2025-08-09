// This component changes the sky image when the hotspot is clicked
AFRAME.registerComponent('hotspot-listener', {
  schema: {target: {type: 'string'}},
  init: function () {
    this.el.addEventListener('click', () => {
      console.log('Red hotspot clicked!');
      document.querySelector('a-sky').setAttribute('src', this.data.target);
      updateHotspots(this.data.target);
    });
  }
});

// Function to show only the hotspot(s) for the current image
function updateHotspots(currentImage) {
  document.querySelectorAll('.hotspot').forEach(h => {
    h.setAttribute('visible', h.getAttribute('data-image') === currentImage);
  });
}

// On scene load, show only hotspots for the initial image
document.addEventListener('DOMContentLoaded', function () {
  const initialImage = document.querySelector('a-sky').getAttribute('src');
  updateHotspots(initialImage);
}); 