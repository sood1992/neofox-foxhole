<?php
// employee_dashboard.php - Debug Version
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
$sessionPath = dirname(__FILE__) . '/temp_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Force authentication for testing
if (!isset($_SESSION['authenticated'])) {
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'employee';
    $_SESSION['user_name'] = 'Test Employee';
}

// Get employee data
$employeeId = $_SESSION['user_id'];
$employeeName = $_SESSION['user_name'];

// Initialize variables with default values
$todayAttendance = null;
$officeLocations = [
    [
        'id' => 1,
        'name' => 'Main Office',
        'latitude' => '28.5383',
        'longitude' => '77.3489',
        'radius_meters' => 200,
        'is_active' => 1
    ]
];
$monthlyReport = null;
$leaveRequests = [];
$employeeStats = [
    'tasks' => [],
    'avg_efficiency' => 0,
    'overdue_tasks' => 0,
    'recent_activity' => []
];

// Try to include required files if they exist
$config_exists = file_exists('config-2.php');
$api_exists = file_exists('api_enhanced.php');
$attendance_exists = file_exists('attendance_functions.php');

if ($config_exists) {
    @include_once 'config-2.php';
}
if ($api_exists) {
    @include_once 'api_enhanced.php';
}
if ($attendance_exists) {
    @include_once 'attendance_functions.php';
}

// Try to get data if classes exist
if (class_exists('DashboardAPI')) {
    $api = new DashboardAPI();
    $employeeStats = $api->getEmployeeDetailedStats($employeeId) ?: $employeeStats;
}

if (class_exists('AttendanceManager')) {
    $attendanceManager = new AttendanceManager();
    $todayAttendance = $attendanceManager->getTodayAttendance($employeeId);
    $officeLocations = $attendanceManager->getOfficeLocations() ?: $officeLocations;
    $currentMonth = date('n');
    $currentYear = date('Y');
    $monthlyReport = $attendanceManager->getMonthlyReport($employeeId, $currentMonth, $currentYear);
    $leaveRequests = $attendanceManager->getEmployeeLeaveRequests($employeeId) ?: [];
}

// Process data
$currentTasks = array_filter($employeeStats['tasks'] ?? [], function($task) {
    return isset($task['status']) && in_array($task['status'], ['todo', 'in_progress']);
});
$completedTasks = array_filter($employeeStats['tasks'] ?? [], function($task) {
    return isset($task['status']) && $task['status'] === 'completed';
});
$completedThisMonth = count(array_filter($completedTasks, function($task) {
    return isset($task['last_activity']) && date('Y-m', strtotime($task['last_activity'])) === date('Y-m');
}));
$totalHoursThisMonth = array_sum(array_map(function($task) {
    return $task['hours_logged'] ?? 0;
}, array_filter($employeeStats['tasks'] ?? [], function($task) {
    return isset($task['last_activity']) && date('Y-m', strtotime($task['last_activity'])) === date('Y-m');
})));
$upcomingDeadlines = array_filter($currentTasks, function($task) {
    return !empty($task['due_date']) && strtotime($task['due_date']) >= time();
});
usort($upcomingDeadlines, function($a, $b) {
    return strtotime($a['due_date'] ?? '') - strtotime($b['due_date'] ?? '');
});
$totalTasks = count($employeeStats['tasks'] ?? []);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax_action']) {
        case 'update_task_status':
            echo json_encode(['success' => true, 'message' => 'Task update simulated']);
            exit();
            
        case 'log_time':
            echo json_encode(['success' => true, 'message' => 'Time log simulated']);
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - <?= htmlspecialchars($employeeName) ?> (Debug Mode)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Base Styles */
        :root {
            --primary: #667eea;
            --primary-light: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Debug Info Box */
        .debug-info {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 1rem;
            margin: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.875rem;
        }

        .debug-info h4 {
            margin-bottom: 0.5rem;
            color: #92400e;
        }

        .debug-info p {
            margin: 0.25rem 0;
        }

        .debug-success {
            color: #065f46;
        }

        .debug-error {
            color: #991b1b;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Attendance Widget */
        .attendance-widget {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .attendance-status {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .status-display p {
            margin: 0.5rem 0;
            font-size: 1.1rem;
        }

        .not-checked-in {
            color: rgba(255,255,255,0.8);
            font-style: italic;
        }

        .attendance-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .attendance-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .attendance-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .attendance-btn.check-in {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .attendance-btn.check-out {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .attendance-complete {
            text-align: center;
            font-size: 1.2rem;
            color: #10b981;
            background: rgba(255,255,255,0.9);
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Response Display */
        .response-display {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.875rem;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }

        .response-display.show {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Debug Info -->
    <div class="debug-info">
        <h4>🐛 Debug Information</h4>
        <p><strong>Session ID:</strong> <?= session_id() ?></p>
        <p><strong>Employee ID:</strong> <?= $employeeId ?></p>
        <p><strong>Session Save Path:</strong> <?= ini_get('session.save_path') ?></p>
        <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
        <p><strong>Files Status:</strong></p>
        <p class="<?= $config_exists ? 'debug-success' : 'debug-error' ?>">
            config-2.php: <?= $config_exists ? '✓ Found' : '✗ Not Found' ?>
        </p>
        <p class="<?= $api_exists ? 'debug-success' : 'debug-error' ?>">
            api_enhanced.php: <?= $api_exists ? '✓ Found' : '✗ Not Found' ?>
        </p>
        <p class="<?= $attendance_exists ? 'debug-success' : 'debug-error' ?>">
            attendance_functions.php: <?= $attendance_exists ? '✓ Found' : '✗ Not Found' ?>
        </p>
        <p><strong>Classes Status:</strong></p>
        <p class="<?= class_exists('DashboardAPI') ? 'debug-success' : 'debug-error' ?>">
            DashboardAPI: <?= class_exists('DashboardAPI') ? '✓ Loaded' : '✗ Not Loaded' ?>
        </p>
        <p class="<?= class_exists('AttendanceManager') ? 'debug-success' : 'debug-error' ?>">
            AttendanceManager: <?= class_exists('AttendanceManager') ? '✓ Loaded' : '✗ Not Loaded' ?>
        </p>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Attendance Widget -->
        <div class="attendance-widget">
            <h3 style="text-align: center; margin-bottom: 1rem;">
                <i class="fas fa-calendar-check"></i> Today's Attendance (Debug Mode)
            </h3>
            
            <!-- Attendance Status -->
            <div class="attendance-status">
                <?php if ($todayAttendance && isset($todayAttendance['check_in_time']) && $todayAttendance['check_in_time']): ?>
                    <div class="status-display">
                        <p><strong>Checked in at:</strong> <?= date('h:i A', strtotime($todayAttendance['check_in_time'])) ?></p>
                        <?php if (isset($todayAttendance['check_out_time']) && $todayAttendance['check_out_time']): ?>
                            <p><strong>Checked out at:</strong> <?= date('h:i A', strtotime($todayAttendance['check_out_time'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="not-checked-in">Not checked in yet today</p>
                <?php endif; ?>
            </div>
            
            <!-- Check In/Out Buttons -->
            <div class="attendance-controls">
                <?php if (!$todayAttendance || !isset($todayAttendance['check_in_time']) || !$todayAttendance['check_in_time']): ?>
                    <button type="button" class="attendance-btn check-in" onclick="debugCheckIn()">
                        <i class="fas fa-sign-in-alt"></i> Check In (Debug)
                    </button>
                <?php elseif (!isset($todayAttendance['check_out_time']) || !$todayAttendance['check_out_time']): ?>
                    <button type="button" class="attendance-btn check-out" onclick="debugCheckOut()">
                        <i class="fas fa-sign-out-alt"></i> Check Out (Debug)
                    </button>
                <?php else: ?>
                    <div class="attendance-complete">
                        <i class="fas fa-check-circle"></i> Attendance marked for today
                    </div>
                <?php endif; ?>
            </div>

            <!-- Response Display -->
            <div id="responseDisplay" class="response-display"></div>
        </div>

        <!-- Simple Attendance Checker -->
        <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 1rem;">Simple Attendance Test</h3>
            <p>Use these buttons to test the attendance API directly:</p>
            <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button onclick="simpleCheckIn()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Simple Check In
                </button>
                <button onclick="simpleCheckOut()" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Simple Check Out
                </button>
                <button onclick="testAPI()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Test API Connection
                </button>
            </div>
        </div>
    </div>

    <script>
        // Debug mode - simplified functions
        
        function showResponse(response, isError = false) {
            const display = document.getElementById('responseDisplay');
            display.innerHTML = `<strong>${isError ? 'Error' : 'Response'}:</strong><br><pre>${JSON.stringify(response, null, 2)}</pre>`;
            display.classList.add('show');
            display.style.background = isError ? '#fee2e2' : '#d1fae5';
            display.style.borderColor = isError ? '#f87171' : '#34d399';
        }

        // Simple test functions
        function simpleCheckIn() {
            console.log('Simple check-in test...');
            
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
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    showResponse(data);
                    if (data.success) {
                        alert('Check-in successful!');
                        setTimeout(() => location.reload(), 2000);
                    }
                } catch (e) {
                    showResponse({ error: 'Invalid JSON', raw: text }, true);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showResponse({ error: error.message }, true);
            });
        }

        function simpleCheckOut() {
            console.log('Simple check-out test...');
            
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
                    showResponse(data);
                    if (data.success) {
                        alert('Check-out successful!');
                        setTimeout(() => location.reload(), 2000);
                    }
                } catch (e) {
                    showResponse({ error: 'Invalid JSON', raw: text }, true);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showResponse({ error: error.message }, true);
            });
        }

        function testAPI() {
            console.log('Testing API connection...');
            
            // Test GET request
            fetch('attendance_api.php?action=test')
                .then(response => response.text())
                .then(text => {
                    console.log('API test response:', text);
                    showResponse({ test: 'GET request', response: text });
                })
                .catch(error => {
                    console.error('API test error:', error);
                    showResponse({ error: 'API test failed', details: error.message }, true);
                });
        }

        // Debug versions with more logging
        function debugCheckIn() {
            console.log('=== DEBUG CHECK-IN START ===');
            console.log('Current URL:', window.location.href);
            console.log('API URL:', window.location.origin + '/attendance_api.php');
            
            const btn = document.querySelector('.attendance-btn.check-in');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
            
            const formData = new FormData();
            formData.append('action', 'check_in');
            formData.append('lat', '28.5383');
            formData.append('lng', '77.3489');
            formData.append('office', 'Debug Test Office');
            formData.append('late_reason', '');
            
            // Log FormData contents
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            
            fetch('attendance_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received:');
                console.log('  Status:', response.status);
                console.log('  Status Text:', response.statusText);
                console.log('  Headers:', Object.fromEntries(response.headers.entries()));
                return response.text();
            })
            .then(text => {
                console.log('Raw response text:', text);
                console.log('Response length:', text.length);
                
                // Check if response is HTML (error page)
                if (text.includes('<!DOCTYPE') || text.includes('<html')) {
                    console.error('Received HTML instead of JSON - likely an error page');
                    showResponse({ 
                        error: 'Received HTML instead of JSON', 
                        hint: 'Check if attendance_api.php exists and is accessible',
                        preview: text.substring(0, 200) + '...'
                    }, true);
                    return;
                }
                
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed JSON:', data);
                    showResponse(data);
                    
                    if (data.success) {
                        alert('Check-in successful!');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert('Check-in failed: ' + (data.message || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showResponse({ 
                        error: 'JSON parse failed', 
                        details: e.message, 
                        raw: text 
                    }, true);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showResponse({ 
                    error: 'Network request failed', 
                    details: error.message,
                    stack: error.stack 
                }, true);
            })
            .finally(() => {
                console.log('=== DEBUG CHECK-IN END ===');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In (Debug)';
                }
            });
        }

        function debugCheckOut() {
            console.log('=== DEBUG CHECK-OUT START ===');
            
            const btn = document.querySelector('.attendance-btn.check-out');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
            
            const formData = new FormData();
            formData.append('action', 'check_out');
            formData.append('lat', '28.5383');
            formData.append('lng', '77.3489');
            formData.append('office', 'Debug Test Office');
            
            fetch('attendance_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    showResponse(data);
                    
                    if (data.success) {
                        alert('Check-out successful!');
                        setTimeout(() => location.reload(), 2000);
                    }
                } catch (e) {
                    showResponse({ error: 'JSON parse failed', raw: text }, true);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showResponse({ error: error.message }, true);
            })
            .finally(() => {
                console.log('=== DEBUG CHECK-OUT END ===');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Check Out (Debug)';
                }
            });
        }

        // Test on page load
        window.addEventListener('load', function() {
            console.log('Debug Dashboard loaded');
            console.log('Testing API connection...');
            testAPI();
        });
    </script>
</body>
</html>