<?php
// test_geolocation.php - Test geolocation attendance system
require_once 'config-2.php';
require_once 'attendance_functions.php';

// Get office locations
$attendanceManager = new AttendanceManager();
$officeLocations = $attendanceManager->getOfficeLocations();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Geolocation Attendance Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary { background: #4CAF50; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn:hover { opacity: 0.8; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .office-list {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        #map { height: 400px; margin: 20px 0; border-radius: 8px; }
        .location-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .info-card h3 { margin: 0 0 10px 0; color: #495057; }
        .info-card p { margin: 5px 0; }
    </style>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <div class="container">
        <h1>🌍 Geolocation Attendance Testing</h1>
        
        <!-- Office Locations -->
        <div class="test-section">
            <h2>📍 Configured Office Locations</h2>
            <div class="office-list">
                <?php if (empty($officeLocations)): ?>
                    <p>No office locations configured in database.</p>
                <?php else: ?>
                    <?php foreach ($officeLocations as $office): ?>
                        <div style="margin-bottom: 10px;">
                            <strong><?= htmlspecialchars($office['name']) ?></strong><br>
                            Coordinates: <?= $office['latitude'] ?>, <?= $office['longitude'] ?><br>
                            Radius: <?= $office['radius_meters'] ?> meters
                            <?= $office['is_active'] ? '<span style="color: green;">✓ Active</span>' : '<span style="color: red;">✗ Inactive</span>' ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Current Location Test -->
        <div class="test-section">
            <h2>📱 Test Your Current Location</h2>
            <button class="btn btn-primary" onclick="testCurrentLocation()">Get My Location</button>
            <button class="btn btn-warning" onclick="testLocationVerification()">Test Office Verification</button>
            <div id="locationResult"></div>
        </div>

        <!-- Location Spoofing Test -->
        <div class="test-section">
            <h2>🧪 Test Different Locations</h2>
            <p>Simulate check-in from different locations:</p>
            <button class="btn btn-primary" onclick="testLocation(28.5374, 77.2497, 'CR Park Office')">Test CR Park Office</button>
            <button class="btn btn-warning" onclick="testLocation(28.6139, 77.2090, 'Connaught Place')">Test CP (Outside Office)</button>
            <button class="btn btn-danger" onclick="testLocation(28.7041, 77.1025, 'Rohini')">Test Rohini (Far Away)</button>
        </div>

        <!-- Manual Location Test -->
        <div class="test-section">
            <h2>🎯 Test Custom Coordinates</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="number" id="testLat" placeholder="Latitude" step="0.0001" style="padding: 10px;">
                <input type="number" id="testLng" placeholder="Longitude" step="0.0001" style="padding: 10px;">
                <button class="btn btn-primary" onclick="testCustomLocation()">Test Location</button>
            </div>
        </div>

        <!-- Map Display -->
        <div class="test-section">
            <h2>🗺️ Office Locations Map</h2>
            <div id="map"></div>
        </div>

        <!-- Results Display -->
        <div id="results"></div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Office locations from PHP
        const OFFICE_LOCATIONS = <?= json_encode($officeLocations) ?>;
        
        // Initialize map
        let map;
        let userMarker;
        
        function initMap() {
            // Default center on Delhi
            map = L.map('map').setView([28.6139, 77.2090], 11);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // Add office markers
            OFFICE_LOCATIONS.forEach(office => {
                const circle = L.circle([parseFloat(office.latitude), parseFloat(office.longitude)], {
                    color: 'blue',
                    fillColor: '#4CAF50',
                    fillOpacity: 0.3,
                    radius: parseInt(office.radius_meters)
                }).addTo(map);
                
                circle.bindPopup(`<strong>${office.name}</strong><br>Radius: ${office.radius_meters}m`);
                
                // Add center marker
                L.marker([parseFloat(office.latitude), parseFloat(office.longitude)])
                    .addTo(map)
                    .bindPopup(office.name);
            });
        }
        
        // Calculate distance using Haversine formula
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Earth's radius in meters
            const φ1 = lat1 * Math.PI/180;
            const φ2 = lat2 * Math.PI/180;
            const Δφ = (lat2-lat1) * Math.PI/180;
            const Δλ = (lon2-lon1) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            return R * c; // Distance in meters
        }
        
        // Check if location is within any office
        function isInOfficeLocation(lat, lng) {
            for (let office of OFFICE_LOCATIONS) {
                const distance = calculateDistance(
                    lat, lng,
                    parseFloat(office.latitude), 
                    parseFloat(office.longitude)
                );
                
                if (distance <= parseInt(office.radius_meters)) {
                    return { 
                        valid: true, 
                        office: office.name, 
                        distance: Math.round(distance) 
                    };
                }
            }
            
            // Find nearest office
            let nearest = null;
            let minDistance = Infinity;
            
            OFFICE_LOCATIONS.forEach(office => {
                const distance = calculateDistance(
                    lat, lng,
                    parseFloat(office.latitude), 
                    parseFloat(office.longitude)
                );
                
                if (distance < minDistance) {
                    minDistance = distance;
                    nearest = office;
                }
            });
            
            return { 
                valid: false, 
                nearestOffice: nearest?.name || 'Unknown',
                distance: Math.round(minDistance) 
            };
        }
        
        // Test current location
        function testCurrentLocation() {
            const resultDiv = document.getElementById('locationResult');
            resultDiv.innerHTML = '<div class="status info">Getting your location...</div>';
            
            if (!navigator.geolocation) {
                resultDiv.innerHTML = '<div class="status error">Geolocation not supported!</div>';
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    resultDiv.innerHTML = `
                        <div class="status success">
                            <strong>Your Location:</strong><br>
                            Latitude: ${lat.toFixed(6)}<br>
                            Longitude: ${lng.toFixed(6)}<br>
                            Accuracy: ${position.coords.accuracy.toFixed(0)} meters
                        </div>
                    `;
                    
                    // Update map
                    if (userMarker) map.removeLayer(userMarker);
                    userMarker = L.marker([lat, lng], {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                        })
                    }).addTo(map);
                    map.setView([lat, lng], 14);
                },
                error => {
                    resultDiv.innerHTML = `
                        <div class="status error">
                            <strong>Location Error:</strong><br>
                            ${error.message}<br>
                            Code: ${error.code}
                        </div>
                    `;
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
        
        // Test location verification
        function testLocationVerification() {
            navigator.geolocation.getCurrentPosition(
                position => {
                    testLocation(position.coords.latitude, position.coords.longitude, 'Your Current Location');
                },
                error => {
                    alert('Unable to get location: ' + error.message);
                }
            );
        }
        
        // Test specific location
        function testLocation(lat, lng, locationName) {
            const check = isInOfficeLocation(lat, lng);
            const resultDiv = document.getElementById('results');
            
            const html = `
                <div class="test-section">
                    <h3>Test Result: ${locationName}</h3>
                    <div class="location-info">
                        <div class="info-card">
                            <h3>📍 Coordinates</h3>
                            <p>Latitude: ${lat.toFixed(6)}</p>
                            <p>Longitude: ${lng.toFixed(6)}</p>
                        </div>
                        <div class="info-card">
                            <h3>${check.valid ? '✅ Verification' : '❌ Verification'}</h3>
                            <p>Status: ${check.valid ? 'INSIDE OFFICE' : 'OUTSIDE OFFICE'}</p>
                            <p>Distance: ${check.distance}m</p>
                        </div>
                        <div class="info-card">
                            <h3>🏢 Office</h3>
                            <p>${check.valid ? check.office : 'Nearest: ' + check.nearestOffice}</p>
                            <p>${check.valid ? 'Can check-in ✓' : 'Cannot check-in ✗'}</p>
                        </div>
                    </div>
                    ${check.valid 
                        ? '<div class="status success">✅ Attendance allowed from this location</div>'
                        : '<div class="status error">❌ Attendance NOT allowed - too far from office</div>'
                    }
                </div>
            `;
            
            resultDiv.innerHTML = html + resultDiv.innerHTML;
            
            // Update map
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.marker([lat, lng], {
                icon: L.icon({
                    iconUrl: check.valid 
                        ? 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png'
                        : 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                })
            }).addTo(map).bindPopup(locationName).openPopup();
            map.setView([lat, lng], 14);
        }
        
        // Test custom location
        function testCustomLocation() {
            const lat = parseFloat(document.getElementById('testLat').value);
            const lng = parseFloat(document.getElementById('testLng').value);
            
            if (isNaN(lat) || isNaN(lng)) {
                alert('Please enter valid coordinates');
                return;
            }
            
            testLocation(lat, lng, 'Custom Location');
        }
        
        // Initialize map on load
        window.onload = function() {
            initMap();
            
            // Add click handler to map
            map.on('click', function(e) {
                testLocation(e.latlng.lat, e.latlng.lng, 'Map Click Location');
            });
        };
    </script>
</body>
</html>