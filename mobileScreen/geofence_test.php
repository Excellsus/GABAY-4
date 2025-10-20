<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geofencing Test - GABAY</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-weight: bold;
        }
        .status.checking {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.allowed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #00b894;
        }
        .status.denied {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #e74c3c;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px 5px 10px 0;
        }
        button:hover {
            background: #0056b3;
        }
        .info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .back-btn {
            background: #6c757d;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #545b62;
        }
    </style>
    
    <!-- GABAY Geofencing System -->
    <script src="js/geofencing.js"></script>
</head>
<body>
    <button class="back-btn" onclick="window.location.href='explore.php'">← Back to Explore</button>
    
    <h1>🛡️ GABAY Geofencing Test</h1>
    <p>This page tests if the geofencing system is working correctly across all mobile screens.</p>
    
    <div id="status" class="status checking">
        🔄 Initializing geofencing system...
    </div>
    
    <div class="info">
        <h3>📍 Location Information</h3>
        <p><strong>Last Position:</strong> <span id="last-position">Not available</span></p>
        <p><strong>Check Interval:</strong> <span id="check-interval">30 seconds</span></p>
        <p><strong>Page:</strong> <span id="current-page">geofence_test.php</span></p>
        <p><strong>Status:</strong> <span id="geofence-status">Checking...</span></p>
    </div>
    
    <div>
        <button onclick="manualCheck()">🔄 Manual Check</button>
        <button onclick="stopGeofencing()">⏹️ Stop Monitoring</button>
        <button onclick="startGeofencing()">▶️ Start Monitoring</button>
        <button onclick="showDebugInfo()">🐛 Debug Info</button>
    </div>
    
    <div id="debug-info" style="display: none;" class="info">
        <h3>🐛 Debug Information</h3>
        <pre id="debug-output">Loading...</pre>
    </div>

    <script>
        let geofenceInstance = null;
        
        // Initialize geofencing with custom callbacks
        document.addEventListener('DOMContentLoaded', function() {
            geofenceInstance = initializeGeofencing({
                checkInterval: 30000, // 30 seconds
                onAccessGranted: function() {
                    updateStatus('allowed', '✅ Access Granted - You are inside the allowed area');
                    updateGeofenceStatus('Inside Geofence');
                },
                onAccessDenied: function(message) {
                    updateStatus('denied', '❌ Access Denied - ' + message);
                    updateGeofenceStatus('Outside Geofence');
                },
                onLocationUpdate: function(lat, lng, allowed) {
                    updateLastPosition(lat, lng);
                    updateGeofenceStatus(allowed ? 'Inside Geofence' : 'Outside Geofence');
                    console.log('Location updated:', lat, lng, 'Allowed:', allowed);
                }
            });
        });
        
        function updateStatus(type, message) {
            const statusEl = document.getElementById('status');
            statusEl.className = 'status ' + type;
            statusEl.innerHTML = message;
        }
        
        function updateLastPosition(lat, lng) {
            document.getElementById('last-position').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
        
        function updateGeofenceStatus(status) {
            document.getElementById('geofence-status').textContent = status;
        }
        
        function manualCheck() {
            updateStatus('checking', '🔄 Performing manual location check...');
            if (geofenceInstance) {
                geofenceInstance.performLocationCheck()
                    .then(result => {
                        console.log('Manual check result:', result);
                    })
                    .catch(error => {
                        console.error('Manual check failed:', error);
                        updateStatus('denied', '❌ Manual check failed: ' + error.message);
                    });
            }
        }
        
        function stopGeofencing() {
            if (geofenceInstance) {
                geofenceInstance.stop();
                updateStatus('checking', '⏹️ Geofencing monitoring stopped');
                updateGeofenceStatus('Stopped');
            }
        }
        
        function startGeofencing() {
            if (geofenceInstance) {
                updateStatus('checking', '▶️ Starting geofencing monitoring...');
                geofenceInstance.initialize();
            }
        }
        
        function showDebugInfo() {
            const debugDiv = document.getElementById('debug-info');
            const debugOutput = document.getElementById('debug-output');
            
            if (debugDiv.style.display === 'none') {
                const info = {
                    geofenceInstance: !!geofenceInstance,
                    isInitialized: geofenceInstance ? geofenceInstance.isInitialized : false,
                    lastPosition: geofenceInstance ? geofenceInstance.getLastPosition() : null,
                    checkInterval: geofenceInstance ? geofenceInstance.checkInterval : null,
                    retryCount: geofenceInstance ? geofenceInstance.retryCount : null,
                    userAgent: navigator.userAgent,
                    geolocationSupported: !!navigator.geolocation,
                    currentURL: window.location.href,
                    timestamp: new Date().toISOString()
                };
                
                debugOutput.textContent = JSON.stringify(info, null, 2);
                debugDiv.style.display = 'block';
            } else {
                debugDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>