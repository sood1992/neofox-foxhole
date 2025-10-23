<?php
// debug_attendance.php - Debug attendance system issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FIXED: Match session configuration from other files
$sessionPath = dirname(__FILE__) . '/temp_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If not authenticated, set test employee session
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'employee';
    $_SESSION['user_name'] = 'Test Employee';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Attendance System</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        pre { background: #333; color: #fff; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Attendance System Debug</h1>
    
    <div class="test">
        <h2>1. Session Check</h2>
        <?php
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo '<p class="success">✓ Session is active</p>';
            echo '<p>Session ID: ' . session_id() . '</p>';
            echo '<p>Session Save Path: ' . ini_get('session.save_path') . '</p>';
            echo '<p>Session data:</p>';
            echo '<pre>' . print_r($_SESSION, true) . '</pre>';
        } else {
            echo '<p class="error">✗ Session is not active</p>';
        }
        ?>
    </div>
    
    <div class="test">
        <h2>2. File Check</h2>
        <?php
        $files = ['attendance_api.php', 'attendance_functions.php', 'config-2.php'];
        foreach ($files as $file) {
            if (file_exists($file)) {
                echo '<p class="success">✓ ' . $file . ' exists</p>';
            } else {
                echo '<p class="error">✗ ' . $file . ' is missing!</p>';
            }
        }
        ?>
    </div>
    
    <div class="test">
        <h2>3. API Connection Test</h2>
        <button onclick="testAPIDebug()">Test API Debug Mode</button>
        <button onclick="testAPIAuth()">Test API Auth</button>
        <button onclick="testCheckIn()">Test Check-In</button>
        <div id="apiResult"></div>
    </div>
    
    <div class="test">
        <h2>4. Check-In Form Test</h2>
        <form id="checkInForm">
            <input type="hidden" name="action" value="check_in">
            <input type="hidden" name="lat" value="28.5383">
            <input type="hidden" name="lng" value="77.3489">
            <input type="hidden" name="office" value="Test Office">
            <input type="hidden" name="late_reason" value="">
            <button type="submit">Submit Check-In Form</button>
        </form>
        <div id="formResult"></div>
    </div>
    
    <script>
        function testAPIDebug() {
            console.log('Testing API debug mode...');
            fetch('attendance_api.php?debug=1')
                .then(response => response.json())
                .then(data => {
                    console.log('Debug response:', data);
                    document.getElementById('apiResult').innerHTML = 
                        '<h3>Debug Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    console.error('Debug error:', error);
                    document.getElementById('apiResult').innerHTML = 
                        '<p class="error">Debug error: ' + error + '</p>';
                });
        }
        
        function testAPIAuth() {
            console.log('Testing API authentication...');
            fetch('attendance_api.php?action=test')
                .then(response => response.json())
                .then(data => {
                    console.log('Auth test response:', data);
                    document.getElementById('apiResult').innerHTML = 
                        '<h3>Auth Test Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    console.error('Auth test error:', error);
                    document.getElementById('apiResult').innerHTML = 
                        '<p class="error">Auth test error: ' + error + '</p>';
                });
        }
        
        function testCheckIn() {
            console.log('Testing check-in...');
            
            const formData = new FormData();
            formData.append('action', 'check_in');
            formData.append('lat', '28.5383');
            formData.append('lng', '77.3489');
            formData.append('office', 'Test Office');
            formData.append('late_reason', '');
            
            fetch('attendance_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Check-in response:', data);
                document.getElementById('apiResult').innerHTML = 
                    '<h3>Check-In Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                console.error('Check-in error:', error);
                document.getElementById('apiResult').innerHTML = 
                    '<p class="error">Check-in error: ' + error + '</p>';
            });
        }
        
        // Form submission test
        document.getElementById('checkInForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('attendance_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Form response:', data);
                document.getElementById('formResult').innerHTML = 
                    '<h3>Form Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                console.error('Form error:', error);
                document.getElementById('formResult').innerHTML = 
                    '<p class="error">Form error: ' + error + '</p>';
            });
        });
        
        // Test on page load
        window.onload = function() {
            console.log('Debug page loaded');
            console.log('Current URL:', window.location.href);
            console.log('API URL:', window.location.origin + '/attendance_api.php');
        };
    </script>
</body>
</html>