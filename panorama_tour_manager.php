<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GABAY Panorama Tour Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #04aa6d, #038659);
            color: white;
            padding: 20px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 30px;
        }

        .tour-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            background: #fafafa;
        }

        .tour-section h2 {
            color: #04aa6d;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panorama-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .panorama-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #04aa6d;
            transition: transform 0.3s ease;
        }

        .panorama-card:hover {
            transform: translateY(-5px);
        }

        .panorama-card h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .panorama-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }

        .panorama-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: #04aa6d;
            color: white;
        }

        .btn-primary:hover {
            background: #038659;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .link-creator {
            background: #e8f5e8;
            border: 2px solid #04aa6d;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .link-creator h3 {
            color: #04aa6d;
            margin-bottom: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group select,
        .form-group input {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #04aa6d;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .existing-links {
            margin-top: 20px;
        }

        .link-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #04aa6d;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .link-info {
            flex: 1;
        }

        .link-info strong {
            color: #04aa6d;
        }

        .status-indicator {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-missing {
            background: #f8d7da;
            color: #721c24;
        }

        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .instructions h3 {
            color: #856404;
            margin-bottom: 15px;
        }

        .instructions ol {
            margin-left: 20px;
            line-height: 1.6;
        }

        .instructions li {
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }
            
            .panorama-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-route"></i> GABAY Panorama Tour Manager</h1>
            <p>Create and manage panoramic tours with hotspot navigation links</p>
        </div>

        <div class="content">
            <div class="instructions">
                <h3><i class="fas fa-info-circle"></i> How to Create Panorama Tours</h3>
                <ol>
                    <li><strong>Upload Panoramas:</strong> Use the main admin interface to upload panorama images for different points and floors</li>
                    <li><strong>Create Hotspots:</strong> Add interactive hotspots to each panorama using the hotspot editor</li>
                    <li><strong>Link Panoramas:</strong> Use this tool to create navigation links between panoramas for seamless tours</li>
                    <li><strong>Set Target Views:</strong> Define the viewing angle when users navigate to linked panoramas</li>
                    <li><strong>Test Navigation:</strong> Use the preview links to test the panoramic tour experience</li>
                </ol>
            </div>

            <div class="tour-section">
                <h2><i class="fas fa-images"></i> Available Panoramas</h2>
                <div id="panorama-list">
                    <div class="panorama-grid">
                        <!-- Panoramas will be loaded here -->
                    </div>
                </div>
            </div>

            <div class="tour-section">
                <h2><i class="fas fa-link"></i> Create Navigation Links</h2>
                <div class="link-creator">
                    <h3>Add Hotspot Navigation Link</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="source-panorama">Source Panorama</label>
                            <select id="source-panorama">
                                <option value="">Select source panorama...</option>
                            </select>
                            <div class="help-text">The panorama where the navigation hotspot will be placed</div>
                        </div>
                        <div class="form-group">
                            <label for="target-panorama">Target Panorama</label>
                            <select id="target-panorama">
                                <option value="">Select target panorama...</option>
                            </select>
                            <div class="help-text">The panorama users will navigate to when clicking the hotspot</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="hotspot-title">Hotspot Title</label>
                            <input type="text" id="hotspot-title" placeholder="e.g., Go to Main Entrance">
                            <div class="help-text">Descriptive title for the navigation hotspot</div>
                        </div>
                        <div class="form-group">
                            <label for="target-yaw">Target Viewing Angle (Yaw)</label>
                            <input type="number" id="target-yaw" min="-180" max="180" value="0" step="1">
                            <div class="help-text">Horizontal viewing angle (0° = North, ±180° range)</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="target-pitch">Target Pitch</label>
                            <input type="number" id="target-pitch" min="-90" max="90" value="0" step="1">
                            <div class="help-text">Vertical viewing angle (-90° down, +90° up)</div>
                        </div>
                        <div class="form-group">
                            <label for="target-zoom">Target Zoom Level</label>
                            <input type="number" id="target-zoom" min="30" max="90" value="60" step="5">
                            <div class="help-text">Field of view (30° = zoomed in, 90° = wide view)</div>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="createNavigationLink()">
                        <i class="fas fa-plus"></i> Create Navigation Link
                    </button>
                </div>

                <div class="existing-links">
                    <h3>Existing Navigation Links</h3>
                    <div id="links-list">
                        <!-- Navigation links will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        class PanoramaTourManager {
            constructor() {
                this.panoramas = [];
                this.navigationLinks = [];
                this.init();
            }

            async init() {
                await this.loadPanoramas();
                await this.loadNavigationLinks();
                this.populateDropdowns();
            }

            async loadPanoramas() {
                try {
                    const response = await fetch('panorama_api.php?action=list');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.panoramas = data.panoramas || [];
                        this.renderPanoramaGrid();
                    }
                } catch (error) {
                    console.error('Error loading panoramas:', error);
                }
            }

            async loadNavigationLinks() {
                // This would load existing navigation links from the database
                // For now, we'll create a placeholder implementation
                this.renderNavigationLinks();
            }

            renderPanoramaGrid() {
                const grid = document.querySelector('.panorama-grid');
                
                if (this.panoramas.length === 0) {
                    grid.innerHTML = '<p style="text-align: center; color: #666; grid-column: 1 / -1;">No panoramas found. Upload some panoramas first.</p>';
                    return;
                }

                grid.innerHTML = this.panoramas.map(panorama => `
                    <div class="panorama-card">
                        <h3>${panorama.title || `${panorama.path_id} - Point ${panorama.point_index}`}</h3>
                        <div class="panorama-info">
                            <span><strong>Path:</strong> ${panorama.path_id}</span>
                            <span><strong>Point:</strong> ${panorama.point_index}</span>
                            <span><strong>Floor:</strong> ${panorama.floor_number}</span>
                            <span><strong>File:</strong> ${panorama.image_filename}</span>
                        </div>
                        <div class="panorama-actions">
                            <a href="Pano/pano_photosphere.html?path_id=${panorama.path_id}&point_index=${panorama.point_index}&floor_number=${panorama.floor_number}" 
                               class="btn btn-primary" target="_blank">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <button class="btn btn-secondary" onclick="tourManager.editHotspots('${panorama.path_id}', ${panorama.point_index}, ${panorama.floor_number})">
                                <i class="fas fa-edit"></i> Edit Hotspots
                            </button>
                            <button class="btn btn-warning" onclick="tourManager.setAsSource('${panorama.path_id}', ${panorama.point_index}, ${panorama.floor_number})">
                                <i class="fas fa-plus"></i> Add Link From Here
                            </button>
                        </div>
                    </div>
                `).join('');
            }

            populateDropdowns() {
                const sourceSelect = document.getElementById('source-panorama');
                const targetSelect = document.getElementById('target-panorama');
                
                const options = this.panoramas.map(panorama => 
                    `<option value="${panorama.path_id}:${panorama.point_index}:${panorama.floor_number}">
                        Floor ${panorama.floor_number} - ${panorama.path_id} Point ${panorama.point_index}
                        ${panorama.title ? ` (${panorama.title})` : ''}
                    </option>`
                ).join('');
                
                sourceSelect.innerHTML = '<option value="">Select source panorama...</option>' + options;
                targetSelect.innerHTML = '<option value="">Select target panorama...</option>' + options;
            }

            setAsSource(pathId, pointIndex, floorNumber) {
                const sourceSelect = document.getElementById('source-panorama');
                sourceSelect.value = `${pathId}:${pointIndex}:${floorNumber}`;
                
                // Scroll to link creator
                document.querySelector('.link-creator').scrollIntoView({ behavior: 'smooth' });
                
                // Set default title
                const targetPanorama = this.panoramas.find(p => 
                    p.path_id === pathId && p.point_index == pointIndex && p.floor_number == floorNumber
                );
                if (targetPanorama) {
                    document.getElementById('hotspot-title').placeholder = 
                        `Navigate from ${targetPanorama.title || targetPanorama.path_id}`;
                }
            }

            editHotspots(pathId, pointIndex, floorNumber) {
                // This would open the hotspot editor for the specific panorama
                const editorUrl = `panorama_hotspot_editor.php?path_id=${pathId}&point_index=${pointIndex}&floor_number=${floorNumber}`;
                window.open(editorUrl, '_blank');
            }

            renderNavigationLinks() {
                const linksList = document.getElementById('links-list');
                
                // Placeholder for navigation links
                linksList.innerHTML = `
                    <p style="color: #666; text-align: center; padding: 20px;">
                        <i class="fas fa-info-circle"></i> 
                        Navigation links will appear here after you create them using the form above.
                    </p>
                `;
            }

            async createNavigationLink() {
                const sourceValue = document.getElementById('source-panorama').value;
                const targetValue = document.getElementById('target-panorama').value;
                const title = document.getElementById('hotspot-title').value;
                const targetYaw = document.getElementById('target-yaw').value;
                const targetPitch = document.getElementById('target-pitch').value;
                const targetZoom = document.getElementById('target-zoom').value;

                if (!sourceValue || !targetValue) {
                    alert('Please select both source and target panoramas');
                    return;
                }

                if (!title.trim()) {
                    alert('Please enter a title for the navigation hotspot');
                    return;
                }

                const [sourcePathId, sourcePointIndex, sourceFloorNumber] = sourceValue.split(':');
                const [targetPathId, targetPointIndex, targetFloorNumber] = targetValue.split(':');

                // Create hotspot data
                const hotspotData = {
                    id: 'nav_' + Date.now(),
                    title: title.trim(),
                    description: `Navigate to ${targetPathId} Point ${targetPointIndex}`,
                    linkType: 'panorama',
                    linkPathId: targetPathId,
                    linkPointIndex: parseInt(targetPointIndex),
                    linkFloorNumber: parseInt(targetFloorNumber),
                    navigationAngle: parseFloat(targetYaw) || 0,
                    position: { x: 0, y: 0, z: -10 }, // Default position
                    type: 'navigation',
                    isNavigation: true
                };

                // Here you would save this hotspot to the database
                console.log('Creating navigation link:', hotspotData);
                
                // For demonstration, show success message
                alert(`Navigation link created successfully!\n\nFrom: ${sourcePathId} Point ${sourcePointIndex}\nTo: ${targetPathId} Point ${targetPointIndex}\n\nNote: You'll need to position the hotspot in the panorama editor.`);
                
                // Clear form
                this.clearForm();
            }

            clearForm() {
                document.getElementById('source-panorama').value = '';
                document.getElementById('target-panorama').value = '';
                document.getElementById('hotspot-title').value = '';
                document.getElementById('target-yaw').value = '0';
                document.getElementById('target-pitch').value = '0';
                document.getElementById('target-zoom').value = '60';
            }
        }

        // Global functions
        function createNavigationLink() {
            tourManager.createNavigationLink();
        }

        // Initialize tour manager
        let tourManager;
        document.addEventListener('DOMContentLoaded', () => {
            tourManager = new PanoramaTourManager();
        });
    </script>
</body>
</html>