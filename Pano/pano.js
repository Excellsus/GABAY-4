// Independent Camera hotspot component for panorama navigation
AFRAME.registerComponent('independent-camera-hotspot', {
  schema: {
    pathId: {type: 'string'},
    pointIndex: {type: 'number'},
    floorNumber: {type: 'number'},
    label: {type: 'string', default: 'Camera'},
    hasData: {type: 'boolean', default: false}
  },
  
  init: function () {
    const el = this.el;
    const data = this.data;
    
    // Store panorama data on element
    el.panoramaConfig = {
      path_id: data.pathId,
      point_index: data.pointIndex,
      floor_number: data.floorNumber,
      label: data.label,
      hasData: data.hasData
    };
    
    // Add click handler
    el.addEventListener('click', () => {
      console.log(`Independent camera clicked: ${data.label}`);
      
      if (!data.hasData) {
        this.showMessage('No panorama available for this viewpoint');
        return;
      }
      
      // Navigate to panorama
      const newUrl = `pano.html?path_id=${data.pathId}&point_index=${data.pointIndex}&floor_number=${data.floorNumber}`;
      console.log(`Navigating to: ${newUrl}`);
      window.location.href = newUrl;
    });
  },
  
  showMessage: function(message) {
    const messageEl = document.createElement('div');
    messageEl.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 20px;
      border-radius: 8px;
      z-index: 2000;
      font-family: Arial, sans-serif;
      text-align: center;
    `;
    messageEl.textContent = message;
    document.body.appendChild(messageEl);
    
    setTimeout(() => {
      if (document.body.contains(messageEl)) {
        document.body.removeChild(messageEl);
      }
    }, 2000);
  }
});

// Enhanced arrow hotspot management utilities
const ArrowHotspotManager = {
  // Reset all arrow hotspots to default state
  resetAll: function() {
    document.querySelectorAll('.camera-hotspot').forEach(hotspot => {
      const shaft = hotspot.querySelector('a-box');
      const head = hotspot.querySelector('a-cone');
      const hasData = hotspot.panoramaData?.hasData || false;
      
      if (shaft && head) {
        const shaftColor = hasData ? '#2563eb' : '#6b7280';
        const headColor = hasData ? '#1e40af' : '#4b5563';
        const opacity = hasData ? 0.9 : 0.7;
        
        shaft.setAttribute('material', `color: ${shaftColor}; opacity: ${opacity}`);
        head.setAttribute('material', `color: ${headColor}; opacity: ${opacity}`);
        hotspot.setAttribute('scale', '1 1 1');
      }
    });
  },
  
  // Highlight a specific arrow as active
  setActive: function(hotspot) {
    this.resetAll();
    
    const shaft = hotspot.querySelector('a-box');
    const head = hotspot.querySelector('a-cone');
    
    if (shaft && head) {
      shaft.setAttribute('material', 'color: #fbbf24; opacity: 1');
      head.setAttribute('material', 'color: #f59e0b; opacity: 1');
      hotspot.setAttribute('scale', '1.3 1.3 1.3');
    }
  },
  
  // Create an arrow configuration object
  createConfig: function(pathId, pointIndex, position, label, floorNumber = 1) {
    return {
      path_id: pathId,
      point_index: pointIndex,
      position: position,
      label: label,
      floor_number: floorNumber
    };
  },
  
  // Validate arrow configuration
  validateConfig: function(config) {
    const required = ['path_id', 'point_index', 'position', 'label'];
    return required.every(field => config.hasOwnProperty(field));
  }
};

// Function to reset all arrow hotspots to default state (legacy support)
function resetArrowHotspots() {
  ArrowHotspotManager.resetAll();
}

// Enhanced initialization with error handling
document.addEventListener('DOMContentLoaded', function () {
  console.log('Enhanced independent arrow hotspot system initialized');
  
  // Check if A-Frame is loaded
  if (typeof AFRAME !== 'undefined') {
    console.log('A-Frame detected, arrow hotspot component registered');
  } else {
    console.warn('A-Frame not detected, some features may not work');
  }
  
  // Add global error handler for arrow hotspots
  window.addEventListener('error', function(e) {
    if (e.message.includes('camera') || e.message.includes('panorama') || e.message.includes('arrow')) {
      console.error('Arrow hotspot error:', e.message);
    }
  });
});

// Export utilities for external use
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    ArrowHotspotManager,
    resetArrowHotspots
  };
} else {
  // Make available globally
  window.ArrowHotspotManager = ArrowHotspotManager;
} 