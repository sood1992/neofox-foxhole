<?php
// debug_attendance.php - Debug attendance system
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config-2.php';

$database = new Database();
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Attendance System</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; background: #e6ffe6; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .error { color: red; background: #ffe6e6; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .warning { color: orange; background: #fff3cd; padding: 10px; margin: 5px 0; border-radius: 4px; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .button { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .button:hover { background: #5a67d8; }
    </style>
</head>
<body>
    <h1>🔍 Attendance System Debug</h1>
    
    <div class="section">
        <h2>1. Session Check</h2>
        <?php
        echo "<pre>";
        echo "Session ID: " . session_id() . "\n";
        echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
        echo "User Role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
        echo "Authenticated: " . (isset($_SESSION['authenticated']) ? 'YES' : 'NO') . "\n";
        echo "</pre>";
        ?>
    </div>
    
    <div class="section">
        <h2>2. File Check</h2>
        <?php
        $files = [
            'attendance_api.php',
            'attendance_functions.php',
            'attendance_admin_api.php',
            'setup_attendance.php'
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                echo "<div class='success'>✓ $file exists</div>";
            } else {
                echo "<div class='error'>✗ $file is MISSING!</div>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. Database Tables Check</h2>
        <?php
        // Check attendance table
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✓ 'attendance' table exists</div>";
                
                // Check table structure
                $stmt = $db->query("DESCRIBE attendance");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<p>Columns: " . implode(', ', $columns) . "</p>";
            } else {
                echo "<div class='error'>✗ 'attendance' table is MISSING!</div>";
                echo "<p><a href='setup_attendance.php' class='button'>Run Setup Script</a></p>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Error checking attendance table: " . $e->getMessage() . "</div>";
        }
        
        // Check office_locations table
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'office_locations'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✓ 'office_locations' table exists</div>";
                
                // Show office locations
                $stmt = $db->query("SELECT * FROM office_locations");
                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($locations) > 0) {
                    echo "<h3>Office Locations:</h3>";
                    echo "<pre>";
                    print_r($locations);
                    echo "</pre>";
                } else {
                    echo "<div class='warning'>⚠️ No office locations configured!</div>";
                }
            } else {
                echo "<div class='error'>✗ 'office_locations' table is MISSING!</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Error checking office_locations table: " . $e->getMessage() . "</div>";
        }
        
        // Check leave_requests table
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'leave_requests'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✓ 'leave_requests' table exists</div>";
            } else {
                echo "<div class='error'>✗ 'leave_requests' table is MISSING!</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Error checking leave_requests table: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. Test Attendance API</h2>
        <button onclick="testCheckIn()" class="button">Test Check-In API</button>
        <button onclick="testLocation()" class="button">Test Browser Location</button>
        <div id="apiResult" style="margin-top: 10px;"></div>
    </div>
    
    <div class="section">
        <h2>5. Create Missing Tables</h2>
        <?php if (isset($_GET['create_tables'])): ?>
            <?php
            try {
                // Create attendance table
                $sql = "CREATE TABLE IF NOT EXISTS attendance (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT NOT NULL,
                    check_in_time TIMESTAMP NULL,
                    check_out_time TIMESTAMP NULL,
                    check_in_location VARCHAR(255) NULL,
                    check_out_location VARCHAR(255) NULL,
                    check_in_lat DECIMAL(10, 8) NULL,
                    check_in_lng DECIMAL(11, 8) NULL,
                    check_out_lat DECIMAL(10, 8) NULL,
                    check_out_lng DECIMAL(11, 8) NULL,
                    late_reason TEXT NULL,
                    status ENUM('present', 'late', 'half-day', 'absent', 'holiday', 'leave') DEFAULT 'present',
                    date DATE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id),
                    UNIQUE KEY unique_attendance (employee_id, date)
                )";
                $db->exec($sql);
                echo "<div class='success'>✓ Created attendance table</div>";
                
                // Create leave_requests table
                $sql = "CREATE TABLE IF NOT EXISTS leave_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT NOT NULL,
                    leave_type ENUM('sick', 'casual', 'annual', 'unpaid', 'other') NOT NULL,
                    from_date DATE NOT NULL,
                    to_date DATE NOT NULL,
                    reason TEXT NOT NULL,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    approved_by INT NULL,
                    approved_at TIMESTAMP NULL,
                    comments TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id),
                    FOREIGN KEY (approved_by) REFERENCES employees(id)
                )";
                $db->exec($sql);
                echo "<div class='success'>✓ Created leave_requests table</div>";
                
                // Create office_locations table
                $sql = "CREATE TABLE IF NOT EXISTS office_locations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    address TEXT NOT NULL,
                    latitude DECIMAL(10, 8) NOT NULL,
                    longitude DECIMAL(11, 8) NOT NULL,
                    radius_meters INT DEFAULT 100,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $db->exec($sql);
                echo "<div class='success'>✓ Created office_locations table</div>";
                
                // Insert CR Park location
                $stmt = $db->query("SELECT COUNT(*) as count FROM office_locations");
                $result = $stmt->fetch();
                
                if ($result['count'] == 0) {
                    $sql = "INSERT INTO office_locations (name, address, latitude, longitude, radius_meters) 
                            VALUES ('CR Park Office', 'Chittaranjan Park, New Delhi, Delhi 110019', 28.5374, 77.2497, 200)";
                    $db->exec($sql);
                    echo "<div class='success'>✓ Added CR Park office location</div>";
                }
                
                echo "<p><a href='debug_attendance.php' class='button'>Refresh Page</a></p>";
            } catch (Exception $e) {
                echo "<div class='error'>Error creating tables: " . $e->getMessage() . "</div>";
            }
            ?>
        <?php else: ?>
            <a href="?create_tables=1" class="button">Create Missing Tables</a>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>6. Quick Actions</h2>
        <a href="employee_dashboard.php" class="button">Employee Dashboard</a>
        <a href="test_employee_data.php" class="button">Test Employee Data</a>
        <a href="setup_attendance.php" class="button">Full Setup Script</a>
    </div>

    <script>
    function testCheckIn() {
        const resultDiv = document.getElementById('apiResult');
        resultDiv.innerHTML = '<div class="warning">Testing API...</div>';
        
        // Test with dummy location data
        fetch('attendance_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=check_in&lat=28.5374&lng=77.2497&office=Test&late_reason='
        })
        .then(response => response.text())
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    resultDiv.innerHTML = '<div class="success">✓ API is working! Response: ' + JSON.stringify(data) + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="error">✗ API error: ' + data.message + '</div>';
                }
            } catch (e) {
                resultDiv.innerHTML = '<div class="error">✗ Invalid JSON response: ' + text + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">✗ Network error: ' + error + '</div>';
        });
    }
    
    function testLocation() {
        const resultDiv = document.getElementById('apiResult');
        
        if (!navigator.geolocation) {
            resultDiv.innerHTML = '<div class="error">✗ Geolocation not supported by browser</div>';
            return;
        }
        
        resultDiv.innerHTML = '<div class="warning">Getting location...</div>';
        
        navigator.geolocation.getCurrentPosition(
            position => {
                resultDiv.innerHTML = '<div class="success">✓ Location access working!<br>' +
                    'Latitude: ' + position.coords.latitude + '<br>' +
                    'Longitude: ' + position.coords.longitude + '</div>';
            },
            error => {
                resultDiv.innerHTML = '<div class="error">✗ Location error: ' + error.message + '</div>';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }
    </script>
</body>
</html>