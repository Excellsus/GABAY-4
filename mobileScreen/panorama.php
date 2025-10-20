<?php
// Mobile Panorama Viewer - Redirect to Photo Sphere Version
session_start();
include __DIR__ . '/../connect_db.php';

// Get panorama data from URL parameters
$pathId = $_GET['path_id'] ?? '';
$pointIndex = $_GET['point_index'] ?? '';
$floorNumber = $_GET['floor'] ?? '1';
$qrId = $_GET['qr_id'] ?? '';

// Redirect to new Photo Sphere viewer with all parameters
$redirectUrl = 'panorama_photosphere.php?' . http_build_query([
    'path_id' => $pathId,
    'point_index' => $pointIndex,
    'floor' => $floorNumber,
    'qr_id' => $qrId
]);

header("Location: $redirectUrl");
exit();

// Legacy code below - now handled by panorama_photosphere.php
// This redirect ensures all existing QR codes and links continue to work

// Log QR scan if qr_id is provided
if ($qrId) {
    try {
        $logStmt = $connect->prepare("INSERT INTO panorama_qr_scans (qr_id, user_agent, ip_address) VALUES (?, ?, ?)");
        $logStmt->execute([
            $qrId,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("QR scan logging failed: " . $e->getMessage());
    }
}

// Fetch panorama data
$panoramaData = null;
if ($pathId && $pointIndex !== '') {
    try {
        $stmt = $connect->prepare("SELECT * FROM panorama_image WHERE path_id = ? AND point_index = ? AND floor_number = ?");
        $stmt->execute([$pathId, $pointIndex, $floorNumber]);
        $panoramaData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Panorama fetch failed: " . $e->getMessage());
    }
}

// If no panorama found, show error
if (!$panoramaData) {
    $errorMessage = "Panorama not found or no longer available.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>360° Panorama View - GABAY</title>
    
    <!-- A-Frame for VR -->
    <script src="https://aframe.io/releases/1.5.0/aframe.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow: hidden;
        }

        .panorama-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }

        .panorama-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }

        .header-info {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            padding: 15px;
            border-radius: 12px;
            pointer-events: all;
            backdrop-filter: blur(10px);
        }

        .header-info h1 {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: #4ade80;
        }

        .header-info .subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 10px;
        }

        .location-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }

        .controls {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
            pointer-events: all;
        }

        .control-btn {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .control-btn:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: translateY(-2px);
        }

        .control-btn:active {
            transform: translateY(0);
        }

        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            padding: 30px;
        }

        .error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .error-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .error-message {
            font-size: 1rem;
            opacity: 0.8;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Loading animation */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            animation: spin 1s linear infinite;
            z-index: 20;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* VR Scene styling */
        a-scene {
            width: 100%;
            height: 100%;
        }

        /* Mobile optimizations */
        @media (max-width: 480px) {
            .header-info {
                top: 10px;
                left: 10px;
                right: 10px;
                padding: 12px;
            }

            .header-info h1 {
                font-size: 1.1rem;
            }

            .controls {
                bottom: 10px;
                left: 10px;
                right: 10px;
                gap: 10px;
            }

            .control-btn {
                padding: 10px 16px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($errorMessage)): ?>
        <!-- Error State -->
        <div class="error-container">
            <div class="error-icon">🚫</div>
            <h1 class="error-title">Panorama Not Available</h1>
            <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
            <a href="../" class="back-btn">← Go Back to Directory</a>
        </div>
    <?php else: ?>
        <!-- Panorama Viewer -->
        <div class="panorama-container">
            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loading-spinner"></div>

            <!-- A-Frame VR Scene -->
            <a-scene 
                id="panorama-scene"
                vr-mode-ui="enabled: false"
                device-orientation-permission-ui="enabled: false"
                background="color: #000"
                inspector="url: https://cdn.aframe.io/releases/1.5.0/aframe-inspector.min.js"
                style="display: none;">
                
                <!-- Assets -->
                <a-assets>
                    <img id="panorama-image" src="../Pano/<?php echo htmlspecialchars($panoramaData['image_filename']); ?>" crossorigin="anonymous">
                </a-assets>

                <!-- 360 Image -->
                <a-sky 
                    id="panorama-sky" 
                    src="#panorama-image" 
                    rotation="0 0 0">
                </a-sky>

                <!-- Camera with look controls -->
                <a-camera 
                    id="panorama-camera"
                    look-controls="enabled: true; touchEnabled: true; mouseEnabled: true"
                    wasd-controls="enabled: false"
                    position="0 0 0">
                    
                    <!-- Cursor for mobile interaction -->
                    <a-cursor
                        id="panorama-cursor"
                        geometry="primitive: circle; radius: 0.02"
                        material="color: rgba(255, 255, 255, 0.8)"
                        raycaster="objects: .hotspot"
                        animation__mouseenter="property: scale; to: 2 2 1; startEvents: mouseenter; dur: 150"
                        animation__mouseleave="property: scale; to: 1 1 1; startEvents: mouseleave; dur: 150">
                    </a-cursor>
                </a-camera>

                <!-- Lighting -->
                <a-light type="ambient" color="#404040" intensity="0.8"></a-light>
            </a-scene>

            <!-- Overlay UI -->
            <div class="panorama-overlay">
                <div class="header-info">
                    <h1><?php echo htmlspecialchars($panoramaData['title'] ?? "360° Panorama View"); ?></h1>
                    <div class="subtitle"><?php echo htmlspecialchars($panoramaData['description'] ?? "Interactive panoramic view"); ?></div>
                    <div class="location-info">
                        <span>📍</span>
                        <span>Floor <?php echo htmlspecialchars($floorNumber); ?> • Path <?php echo htmlspecialchars($pathId); ?> • Point <?php echo htmlspecialchars($pointIndex); ?></span>
                    </div>
                </div>

                <div class="controls">
                    <button class="control-btn" onclick="resetView()">
                        🔄 <span>Reset View</span>
                    </button>
                    <button class="control-btn" onclick="toggleFullscreen()">
                        📱 <span>Fullscreen</span>
                    </button>
                    <a href="../" class="control-btn" style="text-decoration: none;">
                        ← <span>Back</span>
                    </a>
                </div>
            </div>
        </div>

        <script>
            // Scene and camera references
            let scene, camera, sky;
            let hotspotEntities = [];

            // Initialize panorama when A-Frame is ready
            document.addEventListener('DOMContentLoaded', function() {
                // Wait for A-Frame to load
                const sceneEl = document.getElementById('panorama-scene');
                
                if (sceneEl.hasLoaded) {
                    initializePanorama();
                } else {
                    sceneEl.addEventListener('loaded', initializePanorama);
                }
            });

            function initializePanorama() {
                scene = document.getElementById('panorama-scene');
                camera = document.getElementById('panorama-camera');
                sky = document.getElementById('panorama-sky');

                // Load hotspots
                loadHotspots();

                // Hide loading spinner and show scene
                setTimeout(() => {
                    document.getElementById('loading-spinner').style.display = 'none';
                    scene.style.display = 'block';
                }, 1000);
            }

            // Load hotspots for this panorama
            function loadHotspots() {
                const params = new URLSearchParams({
                    action: 'get_hotspots',
                    path_id: '<?php echo $pathId; ?>',
                    point_index: '<?php echo $pointIndex; ?>',
                    floor_number: '<?php echo $floorNumber; ?>'
                });

                fetch('../panorama_api.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.hotspots) {
                            data.hotspots.forEach(hotspot => createHotspotEntity(hotspot));
                        }
                    })
                    .catch(error => {
                        console.warn('Failed to load hotspots:', error);
                    });
            }

            // Create hotspot entity in the scene
            function createHotspotEntity(hotspot) {
                const hotspotEl = document.createElement('a-entity');
                
                // Apply transforms from database
                const rotationX = parseFloat(hotspot.rotation_x || 0);
                const rotationY = parseFloat(hotspot.rotation_y || 0);  
                const rotationZ = parseFloat(hotspot.rotation_z || 0);
                const scaleX = parseFloat(hotspot.scale_x || 1);
                const scaleY = parseFloat(hotspot.scale_y || 1);
                const scaleZ = parseFloat(hotspot.scale_z || 1);

                hotspotEl.setAttribute('class', 'hotspot');
                hotspotEl.setAttribute('position', `${hotspot.position_x} ${hotspot.position_y} ${hotspot.position_z}`);
                hotspotEl.setAttribute('rotation', `${rotationX} ${rotationY} ${rotationZ}`);
                hotspotEl.setAttribute('scale', `${scaleX} ${scaleY} ${scaleZ}`);
                
                // Hotspot sphere geometry
                hotspotEl.setAttribute('geometry', {
                    primitive: 'sphere',
                    radius: 0.15
                });
                
                // Hotspot material with glow effect
                hotspotEl.setAttribute('material', {
                    color: '#00ff88',
                    emissive: '#004422',
                    transparent: true,
                    opacity: 0.8
                });

                // Pulsing animation
                hotspotEl.setAttribute('animation__pulse', {
                    property: 'scale',
                    to: `${scaleX * 1.2} ${scaleY * 1.2} ${scaleZ * 1.2}`,
                    dur: 1500,
                    dir: 'alternate',
                    loop: true,
                    easing: 'easeInOutSine'
                });

                // Click interaction
                hotspotEl.addEventListener('click', function() {
                    if (hotspot.target_office_id) {
                        // Navigate to office details
                        window.location.href = `office_details.php?office_id=${hotspot.target_office_id}`;
                    } else if (hotspot.target_path_id && hotspot.target_point_index !== null) {
                        // Navigate to another panorama
                        window.location.href = `panorama.php?path_id=${hotspot.target_path_id}&point_index=${hotspot.target_point_index}&floor=${hotspot.target_floor || <?php echo $floorNumber; ?>}`;
                    } else {
                        // Show info popup
                        alert(hotspot.info_text || 'Interactive hotspot');
                    }
                });

                // Add hover effects for mobile
                hotspotEl.addEventListener('mouseenter', function() {
                    this.setAttribute('material', 'color', '#ffff00');
                });

                hotspotEl.addEventListener('mouseleave', function() {
                    this.setAttribute('material', 'color', '#00ff88');
                });

                scene.appendChild(hotspotEl);
                hotspotEntities.push(hotspotEl);
            }

            // Control functions
            function resetView() {
                if (camera) {
                    camera.setAttribute('rotation', '0 0 0');
                    camera.setAttribute('position', '0 0 0');
                }
            }

            function toggleFullscreen() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(err => {
                        console.log('Fullscreen not supported:', err);
                    });
                } else {
                    document.exitFullscreen();
                }
            }

            // Handle device orientation for mobile
            if (window.DeviceOrientationEvent) {
                window.addEventListener('deviceorientation', function(event) {
                    // Optional: Use device orientation for camera control
                }, false);
            }

            // Prevent page scrolling on mobile
            document.addEventListener('touchmove', function(e) {
                e.preventDefault();
            }, { passive: false });

            // Handle page visibility
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    // Pause animations when page is hidden
                    hotspotEntities.forEach(entity => {
                        entity.pause();
                    });
                } else {
                    // Resume animations when page is visible
                    hotspotEntities.forEach(entity => {
                        entity.play();
                    });
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>