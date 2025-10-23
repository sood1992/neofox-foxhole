<?php
// check_attendance.php - Simple attendance check page for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Consistent session configuration
$sessionPath = dirname(__FILE__) . '/temp_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate logged in user for testing
if (!isset($_SESSION['authenticated'])) {
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'employee';
    $_SESSION['user_name'] = 'Test Employee';
}

$employeeId = $_SESSION['user_id'];
$employeeName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Attendance Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .status-box {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-checkin {
            background: #4CAF50;
            color: white;
        }
        .btn-checkin:hover {
            background: #45a049;
        }
        .btn-checkout {
            background: #f44336;
            color: white;
        }
        .btn-checkout:hover {
            background: #da190b;
        }
        .btn-logout {
            background: #666;
            color: white;
        }
        .btn-logout:hover {
            background: #444;
        }
        .debug-info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 14px;
        }
        .error {
            color: red;
            margin: 10px 0;
        }
        .success {
            color: green;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple Attendance System</h1>
        <p style="text-align: center;">Welcome, <?= htmlspecialchars($employeeName) ?> (ID: <?= $employeeId ?>)</p>
        
        <div class="status-box">
            <h2>Today's Attendance Status</h2>
            <p id="attendance-status">Loading...</p>
        </div>
        
        <div style="text-align: center;">
            <button type="button" class="btn btn-checkin" id="checkinBtn" onclick="doCheckIn()">
                Check In
            </button>
            <button type="button" class="btn btn-checkout" id="checkoutBtn" onclick="doCheckOut()">
                Check Out
            </button>
            <button type="button" class="btn btn-logout" onclick="doLogout()">
                Logout
            </button>
        </div>
        
        <div id="message"></div>
        
        <div class="debug-info">
            <h3>Debug Information:</h3>
            <p>Session ID: <?= session_id() ?></p>
            <p>Employee ID: <?= $employeeId ?></p>
            <p>API Endpoint: <?= $_SERVER['HTTP_HOST'] ?>/attendance_api.php</p>
            <p>Current Time: <?= date('Y-m-d H:i:s') ?></p>
            <p>Session Save Path: <?= ini_get('session.save_path') ?></p>
        </div>
    </div>

    <script>
        // Simple, direct functions without complex dependencies
        
        function showMessage(message, type = 'info') {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="${type}">${message}</div>`;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        function doCheckIn() {
            console.log('Check-in button clicked');
            showMessage('Processing check-in...', 'info');
            
            const btn = document.getElementById('checkinBtn');
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            // Simple check-in without geolocation for testing
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
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.success) {
                        showMessage('Check-in successful!', 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showMessage('Check-in failed: ' + (data.message || 'Unknown error'), 'error');
                        btn.disabled = false;
                        btn.textContent = 'Check In';
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showMessage('Invalid response from server: ' + text, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Check In';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showMessage('Network error: ' + error.message, 'error');
                btn.disabled = false;
                btn.textContent = 'Check In';
            });
        }
        
        function doCheckOut() {
            console.log('Check-out button clicked');
            showMessage('Processing check-out...', 'info');
            
            const btn = document.getElementById('checkoutBtn');
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            const formData = new FormData();
            formData.append('action', 'check_out');
            formData.append('lat', '28.5383');
            formData.append('lng', '77.3489');
            formData.append('office', 'Test Office');
            
            fetch('attendance_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.success) {
                        showMessage('Check-out successful! Hours: ' + (data.hours || 'N/A'), 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showMessage('Check-out failed: ' + (data.message || 'Unknown error'), 'error');
                        btn.disabled = false;
                        btn.textContent = 'Check Out';
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showMessage('Invalid response from server: ' + text, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Check Out';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showMessage('Network error: ' + error.message, 'error');
                btn.disabled = false;
                btn.textContent = 'Check Out';
            });
        }
        
        function doLogout() {
            console.log('Logout button clicked');
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'auth.php?action=logout';
            }
        }
        
        // Check attendance status on load
        function checkAttendanceStatus() {
            console.log('Checking attendance status...');
            
            fetch('attendance_api.php?action=get_today_status')
                .then(response => response.text())
                .then(text => {
                    console.log('Status response:', text);
                    try {
                        const data = JSON.parse(text);
                        const statusDiv = document.getElementById('attendance-status');
                        
                        if (data.success && data.attendance) {
                            if (data.attendance.check_in_time) {
                                statusDiv.innerHTML = `
                                    <strong>Checked in at:</strong> ${data.attendance.check_in_time}<br>
                                    ${data.attendance.check_out_time ? 
                                        `<strong>Checked out at:</strong> ${data.attendance.check_out_time}` : 
                                        '<em>Not checked out yet</em>'}
                                `;
                                
                                // Update button visibility
                                document.getElementById('checkinBtn').style.display = 'none';
                                document.getElementById('checkoutBtn').style.display = 
                                    data.attendance.check_out_time ? 'none' : 'inline-block';
                            } else {
                                statusDiv.innerHTML = '<em>Not checked in today</em>';
                                document.getElementById('checkinBtn').style.display = 'inline-block';
                                document.getElementById('checkoutBtn').style.display = 'none';
                            }
                        } else {
                            statusDiv.innerHTML = '<em>Not checked in today</em>';
                            document.getElementById('checkinBtn').style.display = 'inline-block';
                            document.getElementById('checkoutBtn').style.display = 'none';
                        }
                    } catch (e) {
                        console.error('Error parsing status:', e);
                        document.getElementById('attendance-status').innerHTML = 
                            '<span style="color: red;">Error loading status</span>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching status:', error);
                    document.getElementById('attendance-status').innerHTML = 
                        '<span style="color: red;">Error loading status</span>';
                });
        }
        
        // Initialize on page load
        window.onload = function() {
            console.log('Page loaded, initializing...');
            checkAttendanceStatus();
        };
        
        // Test API connection
        console.log('Testing API connection...');
        fetch('attendance_api.php?action=test')
            .then(response => response.text())
            .then(text => console.log('API test response:', text))
            .catch(error => console.error('API test error:', error));
    </script>
</body>
</html>