<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Public Door Status API</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .status-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .success { color: #4caf50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .door-item {
            padding: 10px;
            margin: 5px 0;
            background: #f9f9f9;
            border-left: 4px solid #2196F3;
            border-radius: 4px;
        }
        .door-inactive {
            border-left-color: #9ca3af;
            background: #f0f0f0;
        }
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #1976D2;
        }
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🚪 Public Door Status API Test</h1>
    
    <div class="status-box">
        <h2>Test Instructions:</h2>
        <ol>
            <li>Click "Load Door Statuses" button below</li>
            <li>This simulates what your phone does when loading the map</li>
            <li>You should see all door statuses without needing to login</li>
            <li>Grey doors = inactive, Blue doors = active</li>
        </ol>
        <button onclick="testDoorStatusAPI()">🔄 Load Door Statuses</button>
    </div>

    <div class="status-box">
        <h2>API Response:</h2>
        <div id="api-status">Click button to load...</div>
    </div>

    <div class="status-box">
        <h2>Door Status Details:</h2>
        <div id="door-details">No data loaded yet</div>
    </div>

    <div class="status-box">
        <h2>Raw JSON Response:</h2>
        <pre id="raw-json">No data loaded yet</pre>
    </div>

    <script>
        async function testDoorStatusAPI() {
            const statusDiv = document.getElementById('api-status');
            const detailsDiv = document.getElementById('door-details');
            const rawJsonDiv = document.getElementById('raw-json');
            
            statusDiv.innerHTML = '⏳ Loading door statuses from public API...';
            detailsDiv.innerHTML = '⏳ Processing...';
            rawJsonDiv.textContent = 'Loading...';
            
            try {
                const response = await fetch('public_door_status_api.php?action=get_all');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Show status
                if (data.success) {
                    statusDiv.innerHTML = `
                        <span class="success">✅ SUCCESS!</span><br>
                        <strong>Total Offices:</strong> ${data.total_offices}<br>
                        <strong>Total Doors:</strong> ${data.total_doors}<br>
                        <strong>Public Access:</strong> ${data.public_access ? 'Yes ✓' : 'No ✗'}
                    `;
                } else {
                    statusDiv.innerHTML = `<span class="error">❌ FAILED: ${data.error}</span>`;
                }
                
                // Show door details
                if (data.doors && Object.keys(data.doors).length > 0) {
                    let html = '';
                    let totalActive = 0;
                    let totalInactive = 0;
                    
                    for (const officeId in data.doors) {
                        html += `<h3>Office ID: ${officeId}</h3>`;
                        const officeDoors = data.doors[officeId];
                        
                        for (const doorId in officeDoors) {
                            const isActive = officeDoors[doorId];
                            const statusText = isActive ? '🟢 Active' : '⚫ Inactive';
                            const className = isActive ? 'door-item' : 'door-item door-inactive';
                            
                            html += `<div class="${className}">${doorId}: ${statusText}</div>`;
                            
                            if (isActive) totalActive++;
                            else totalInactive++;
                        }
                    }
                    
                    html = `
                        <div style="margin-bottom: 15px; padding: 10px; background: #e3f2fd; border-radius: 4px;">
                            <strong>Summary:</strong> ${totalActive} active doors, ${totalInactive} inactive doors
                        </div>
                    ` + html;
                    
                    detailsDiv.innerHTML = html;
                } else {
                    detailsDiv.innerHTML = '<p>No door data found</p>';
                }
                
                // Show raw JSON
                rawJsonDiv.textContent = JSON.stringify(data, null, 2);
                
            } catch (error) {
                statusDiv.innerHTML = `<span class="error">❌ ERROR: ${error.message}</span>`;
                detailsDiv.innerHTML = `<span class="error">Failed to load door data</span>`;
                rawJsonDiv.textContent = `Error: ${error.message}`;
                console.error('API Test Error:', error);
            }
        }
        
        // Auto-run test on page load
        window.addEventListener('DOMContentLoaded', () => {
            console.log('Page loaded, ready to test public door status API');
        });
    </script>
</body>
</html>
