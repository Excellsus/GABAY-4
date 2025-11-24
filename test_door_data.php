<!DOCTYPE html>
<html>
<head>
    <title>Door Management Test</title>
</head>
<body>
    <h1>Door Management System Test</h1>
    
    <div id="output"></div>
    
    <script>
        const output = document.getElementById('output');
        
        // Test 1: Check if floor graph loads
        output.innerHTML += '<h2>Test 1: Loading Floor Graph</h2>';
        fetch('floor_graph.json')
            .then(r => r.json())
            .then(data => {
                output.innerHTML += '<p style="color: green;">✅ Floor graph loaded successfully</p>';
                output.innerHTML += '<p>Total rooms: ' + Object.keys(data.rooms).length + '</p>';
                
                // Find rooms with door points
                let roomsWithDoors = 0;
                let totalDoors = 0;
                for (let roomId in data.rooms) {
                    if (data.rooms[roomId].doorPoints && data.rooms[roomId].doorPoints.length > 0) {
                        roomsWithDoors++;
                        totalDoors += data.rooms[roomId].doorPoints.length;
                        output.innerHTML += '<p>Room ' + roomId + ' has ' + data.rooms[roomId].doorPoints.length + ' door(s)</p>';
                    }
                }
                output.innerHTML += '<p><strong>Total rooms with doors: ' + roomsWithDoors + '</strong></p>';
                output.innerHTML += '<p><strong>Total door points: ' + totalDoors + '</strong></p>';
                
                // Test 2: Check offices
                output.innerHTML += '<h2>Test 2: Checking Offices</h2>';
                return fetch('floorPlan.php');
            })
            .then(r => r.text())
            .then(html => {
                // Extract offices data from PHP
                const match = html.match(/const officesData = (\[.*?\]);/s);
                if (match) {
                    const officesData = JSON.parse(match[1]);
                    output.innerHTML += '<p style="color: green;">✅ Offices data found: ' + officesData.length + ' offices</p>';
                    
                    officesData.forEach(office => {
                        output.innerHTML += '<p>Office "' + office.name + '" at location: ' + (office.location || 'NO LOCATION') + '</p>';
                    });
                } else {
                    output.innerHTML += '<p style="color: red;">❌ Could not extract offices data</p>';
                }
            })
            .catch(err => {
                output.innerHTML += '<p style="color: red;">❌ Error: ' + err.message + '</p>';
            });
    </script>
</body>
</html>
