(function(){
  // Make sure THREE is defined
  if (typeof THREE === 'undefined') {
    console.error('THREE is not defined. Make sure the library is loaded first.');
    const loadingElem = document.getElementById('loading');
    if (loadingElem) {
      loadingElem.innerText = 'Error: THREE is not defined';
      loadingElem.style.color = 'red';
    }
    return;
  }

  const container = document.getElementById('canvas-container');
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(60, container.clientWidth / container.clientHeight, 0.1, 1000);
  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
  renderer.setSize(container.clientWidth, container.clientHeight);
  renderer.setPixelRatio(window.devicePixelRatio || 1);
  container.appendChild(renderer.domElement);

  // Get or create loading indicator
  let loadingElem = document.getElementById('loading');
  if (!loadingElem) {
    loadingElem = document.createElement('div');
    loadingElem.id = 'loading';
    loadingElem.style.position = 'absolute';
    loadingElem.style.top = '50%';
    loadingElem.style.left = '50%';
    loadingElem.style.transform = 'translate(-50%, -50%)';
    loadingElem.style.color = 'white';
    loadingElem.style.fontSize = '24px';
    loadingElem.innerText = 'Loading 3D model...';
    container.appendChild(loadingElem);
  } else {
    loadingElem.innerText = 'Loading 3D model...';
  }

  const controls = new window.OrbitControls(camera, renderer.domElement);
  controls.target.set(0,1,0);
  controls.update();

  camera.position.set(0, 2, 5);

  // Set a lighter background color
  scene.background = new THREE.Color(0x404040); // Dark gray instead of black

  // Much brighter lighting setup
  const ambient = new THREE.AmbientLight(0xffffff, 1.2); // Increased from 0.8
  scene.add(ambient);
  
  // Add multiple directional lights for better coverage
  const dir1 = new THREE.DirectionalLight(0xffffff, 0.8); // Increased from 0.6
  dir1.position.set(5, 10, 5);
  scene.add(dir1);
  
  const dir2 = new THREE.DirectionalLight(0xffffff, 0.6);
  dir2.position.set(-5, 8, -3);
  scene.add(dir2);
  
  const dir3 = new THREE.DirectionalLight(0xffffff, 0.4);
  dir3.position.set(0, -5, 0); // Light from below
  scene.add(dir3);

  // Brighter ground helper
  const grid = new THREE.GridHelper(10, 10, 0x888888, 0x444444); // Brighter grid
  scene.add(grid);

  // Try multiple paths for the model file
  const possiblePaths = [
    '../assets/3d/Capitol_1st_floor_layout_.glb',
    './assets/3d/Capitol_1st_floor_layout_.glb',
    '/FinalDev/assets/3d/Capitol_1st_floor_layout_.glb',
    '../assets/3d/Capitol_1st_floor_layout.glb', // fallback without underscore
    '../SVG/Capitol_1st_floor_layout.glb' // Try SVG directory since floor plans are there
  ];
  
  // Function to try loading from multiple paths
  function tryLoadModel(paths, index) {
    if (index >= paths.length) {
      console.error('Failed to load model from all possible paths');
      loadingElem.innerHTML = 'Error: Model not found.<br>Check console for details.';
      loadingElem.style.color = 'red';
      return;
    }
    
    const currentPath = paths[index];
    console.log(`Attempting to load model from: ${currentPath}`);
    loadingElem.innerText = `Loading from: ${currentPath}`;
    
    const loader = new window.GLTFLoader();
    loader.load(currentPath, 
      // Success callback
      gltf => {
        if (loadingElem.parentNode) {
          loadingElem.parentNode.removeChild(loadingElem);
        }
        
        const model = gltf.scene || gltf.scenes[0];
        model.name = 'capitolModel';
        scene.add(model);

        // compute bounding box and frame
        const bbox = new THREE.Box3().setFromObject(model);
        const size = bbox.getSize(new THREE.Vector3());
        const center = bbox.getCenter(new THREE.Vector3());

        console.log('Model loaded successfully from ' + currentPath);

        // reposition model so center is at origin
        model.position.x += (model.position.x - center.x);
        model.position.y += (model.position.y - center.y);
        model.position.z += (model.position.z - center.z);

        // adjust camera distance
        const maxDim = Math.max(size.x, size.y, size.z);
        const fov = camera.fov * (Math.PI/180);
        let cameraZ = Math.abs(maxDim / 2 / Math.tan(fov / 2)) * 1.4; // padding
        camera.position.set(0, maxDim * 0.6, cameraZ);
        controls.target.set(0, maxDim * 0.2, 0);
        controls.update();
      },
      // Progress callback
      xhr => {
        const percent = (xhr.loaded / xhr.total * 100).toFixed(2);
        console.log(`Loading ${currentPath}: ${percent}%`);
        loadingElem.innerText = `Loading 3D model... ${percent}%`;
      },
      // Error callback
      error => {
        console.error(`Error loading from ${currentPath}:`, error);
        // Try next path
        tryLoadModel(paths, index + 1);
      }
    );
  }
  
  // Start trying paths
  tryLoadModel(possiblePaths, 0);

  // Add basic error handling
  window.addEventListener('error', function(e) {
    console.error('Global error:', e.message);
    if (loadingElem) {
      loadingElem.innerText = `Error: ${e.message}`;
      loadingElem.style.color = 'red';
    }
  });

  function onWindowResize(){
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
  }
  window.addEventListener('resize', onWindowResize);

  function animate(){
    requestAnimationFrame(animate);
    renderer.render(scene, camera);
  }
  animate();
})();