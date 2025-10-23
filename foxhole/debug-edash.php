<?php
// employee_dashboard.php - Enhanced with Attendance System (DEBUG VERSION)
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

// Authentication check
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php?error=not_authenticated');
    exit();
}

// Allow admin to test employee view
$isTestMode = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' && isset($_GET['test_employee']);

if ($_SESSION['user_role'] !== 'employee' && !$isTestMode) {
    header('Location: index.php?error=not_employee');
    exit();
}

// Include required files
require_once 'config-2.php';
require_once 'api_enhanced.php';
require_once 'attendance_functions.php';

// Initialize API and Attendance Manager
$api = new DashboardAPI();
$attendanceManager = new AttendanceManager();

// Get employee data
$employeeId = $isTestMode ? 1 : $_SESSION['user_id'];
$employeeName = $isTestMode ? 'Test Employee' : $_SESSION['user_name'];

// Get attendance data
$todayAttendance = $attendanceManager->getTodayAttendance($employeeId);
$officeLocations = $attendanceManager->getOfficeLocations();
$currentMonth = date('n');
$currentYear = date('Y');
$monthlyReport = $attendanceManager->getMonthlyReport($employeeId, $currentMonth, $currentYear);
$leaveRequests = $attendanceManager->getEmployeeLeaveRequests($employeeId);

// Get employee stats
$employeeStats = $api->getEmployeeDetailedStats($employeeId);

// Ensure we have default values for all expected keys
$employeeStats = array_merge([
    'tasks' => [],
    'avg_efficiency' => 0,
    'overdue_tasks' => 0,
    'recent_activity' => []
], $employeeStats ?: []);

$currentTasks = array_filter($employeeStats['tasks'] ?? [], function($task) {
    return isset($task['status']) && in_array($task['status'], ['todo', 'in_progress']);
});
$completedTasks = array_filter($employeeStats['tasks'] ?? [], function($task) {
    return isset($task['status']) && $task['status'] === 'completed';
});

// Calculate monthly stats
$completedThisMonth = count(array_filter($completedTasks, function($task) {
    return isset($task['last_activity']) && date('Y-m', strtotime($task['last_activity'])) === date('Y-m');
}));
$totalHoursThisMonth = array_sum(array_map(function($task) {
    return $task['hours_logged'] ?? 0;
}, array_filter($employeeStats['tasks'] ?? [], function($task) {
    return isset($task['last_activity']) && date('Y-m', strtotime($task['last_activity'])) === date('Y-m');
})));

// Get upcoming deadlines
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
            $taskId = intval($_POST['task_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if ($taskId && in_array($status, ['todo', 'in_progress', 'completed'])) {
                $result = $api->updateTaskStatus($taskId, $status);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit();
            
        case 'log_time':
            $taskId = intval($_POST['task_id'] ?? 0);
            $hours = floatval($_POST['hours'] ?? 0);
            
            if ($taskId && $hours > 0) {
                $result = $api->updateTaskHours($taskId, $hours);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - <?= htmlspecialchars($employeeName) ?> (DEBUG MODE)</title>
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

        /* CRITICAL FIX - Attendance widget overlay */
        .attendance-widget::before {
            pointer-events: none !important;
        }

        /* Navbar Styles */
        .navbar {
            background: var(--bg-primary);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 900;
            font-size: 20px;
        }

        .nav-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .nav-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-btn {
            padding: 0.5rem 1rem;
            background: var(--gray-100);
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn:hover {
            background: var(--gray-200);
            transform: translateY(-1px);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.tasks {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
        }

        .stat-icon.completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .stat-icon.hours {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .stat-icon.deadline {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* Section Styles */
        .section {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Task List */
        .task-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .task-item {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .task-item:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .task-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .task-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .task-priority {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .priority-high, .priority-urgent {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .priority-medium {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .priority-low {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .task-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .task-btn {
            padding: 8px 16px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .task-btn-primary {
            background: var(--primary);
            color: white;
        }

        .task-btn-primary:hover {
            background: var(--primary-light);
        }

        .task-btn-secondary {
            background: var(--gray-200);
            color: var(--text-primary);
        }

        .task-btn-secondary:hover {
            background: var(--gray-300);
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

        .attendance-widget::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
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
            position: relative;
            z-index: 10;
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
            position: relative;
            z-index: 20;
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

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            color: #333;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-content h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            justify-content: flex-end;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modal-buttons button:first-child {
            background: #3b82f6;
            color: white;
        }

        .modal-buttons button:first-child:hover {
            background: #2563eb;
        }

        .modal-buttons button:last-child {
            background: #e5e7eb;
            color: #374151;
        }

        .modal-buttons button:last-child:hover {
            background: #d1d5db;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--success);
            transition: width 0.3s ease;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Leave Request Table */
        .leave-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leave-table th,
        .leave-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .leave-table th {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        .leave-status {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Attendance Summary */
        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--gray-100);
            border-radius: var(--radius-md);
        }

        .summary-item {
            text-align: center;
        }

        .summary-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .summary-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .nav-container {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 1.5rem;
            }

            .attendance-widget {
                padding: 1.5rem;
            }

            .attendance-controls {
                flex-direction: column;
            }

            .attendance-btn {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                padding: 1.5rem;
                margin: 1rem;
            }

            .attendance-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Test Mode Notice */
        .test-notice {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid var(--warning);
        }

        /* Debug Mode Notice */
        .debug-notice {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid var(--danger);
        }
    </style>
</head>
<body>
    <!-- Debug Mode Notice -->
    <div class="debug-notice">
        🔧 <strong>DEBUG MODE ACTIVE</strong> - Extra debugging information is visible
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="logo">F</div>
                <div>
                    <div class="nav-title">Employee Dashboard</div>
                    <div class="nav-subtitle">Welcome, <?= htmlspecialchars($employeeName) ?></div>
                </div>
            </div>
            
            <div class="nav-right">
                <?php if ($isTestMode): ?>
                <a href="dashboard.php" class="nav-btn">
                    <i class="fas fa-tachometer-alt"></i>
                    Admin Dashboard
                </a>
                <?php endif; ?>
                
                <button class="nav-btn" style="position: relative;" onclick="showNotifications()">
                    <i class="fas fa-bell"></i>
                    <?php 
                    $overdueCount = 0;
                    if (isset($employeeStats['overdue_tasks'])) {
                        $overdueCount = is_numeric($employeeStats['overdue_tasks']) ? intval($employeeStats['overdue_tasks']) : 0;
                    }
                    if ($overdueCount > 0): 
                    ?>
                    <span style="position: absolute; top: -4px; right: -4px; background: var(--danger); color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; min-width: 18px; text-align: center;">
                        <?= $overdueCount ?>
                    </span>
                    <?php endif; ?>
                </button>
                
                <button class="nav-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <?php if ($isTestMode): ?>
        <div class="test-notice">
            <strong>⚠️ Testing Mode:</strong> You're viewing the employee dashboard in test mode. 
            <a href="dashboard.php" style="color: var(--warning); text-decoration: underline;">Return to admin dashboard</a>
        </div>
        <?php endif; ?>

        <!-- Attendance Widget with Debug Console -->
        <div class="attendance-widget">
            <h3 style="text-align: center; margin-bottom: 1rem; position: relative; z-index: 10;">
                <i class="fas fa-calendar-check"></i> Today's Attendance
            </h3>
            
            <!-- Debug Console -->
            <div style="background: rgba(0,0,0,0.2); padding: 10px; margin-bottom: 10px; border-radius: 5px; font-size: 12px; font-family: monospace;">
                <strong>Debug Console:</strong>
                <div id="debugLog" style="max-height: 150px; overflow-y: auto; margin-top: 5px;"></div>
            </div>
            
            <!-- Attendance Status -->
            <div class="attendance-status" style="position: relative; z-index: 10;">
                <div id="statusMessage">
                    <p class="not-checked-in">Loading status...</p>
                </div>
            </div>
            
            <!-- Check In/Out Buttons - Make them visible by default for testing -->
            <div class="attendance-controls">
                <button type="button" 
                        id="checkInButton" 
                        class="attendance-btn check-in" 
                        onclick="checkIn()">
                    <i class="fas fa-sign-in-alt"></i> Check In
                </button>
                
                <button type="button" 
                        id="checkOutButton" 
                        class="attendance-btn check-out" 
                        onclick="checkOut()" 
                        style="display: none;">
                    <i class="fas fa-sign-out-alt"></i> Check Out
                </button>
                
                <div id="attendanceComplete" class="attendance-complete" style="display: none;">
                    <i class="fas fa-check-circle"></i> Attendance marked for today
                </div>
            </div>
            
            <!-- Manual Actions for Testing -->
            <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                <strong>Manual Actions:</strong>
                <div style="margin-top: 10px;">
                    <button onclick="testAPI()" style="margin: 5px; padding: 8px 15px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Test API Connection
                    </button>
                    <button onclick="forceLoadStatus()" style="margin: 5px; padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Force Load Status
                    </button>
                    <button onclick="showAllButtons()" style="margin: 5px; padding: 8px 15px; background: #FF9800; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Show All Buttons
                    </button>
                    <button onclick="testGeolocation()" style="margin: 5px; padding: 8px 15px; background: #9C27B0; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Test Geolocation
                    </button>
                </div>
            </div>
            
            <!-- Late Reason Modal -->
            <div id="lateReasonModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h3>Late Check-in Reason</h3>
                    <p>You're checking in after 10:30 AM. Please provide a reason:</p>
                    <textarea id="lateReason" rows="3" placeholder="Enter reason for late check-in..." required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-top: 10px;"></textarea>
                    <div class="modal-buttons">
                        <button onclick="submitLateCheckIn()">Submit</button>
                        <button onclick="closeLateModal()">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon tasks">
                        <i class="fas fa-tasks"></i>
                    </div>
                </div>
                <div class="stat-number"><?= count($currentTasks) ?></div>
                <div class="stat-label">Active Tasks</div>
                <?php if ($totalTasks > 0): ?>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= round((count($completedTasks) / $totalTasks) * 100) ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $completedThisMonth ?></div>
                <div class="stat-label">Completed This Month</div>
                <?php if (isset($employeeStats['avg_efficiency']) && $employeeStats['avg_efficiency'] > 0): ?>
                <div style="font-size: 12px; color: var(--success); margin-top: 8px;">
                    <i class="fas fa-arrow-up"></i> <?= round($employeeStats['avg_efficiency']) ?>% efficiency
                </div>
                <?php endif; ?>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon hours">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
                <div class="stat-number"><?= round($totalHoursThisMonth) ?></div>
                <div class="stat-label">Hours Logged</div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 8px;">
                    This month
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon deadline">
                        <i class="fas fa-calendar-exclamation"></i>
                    </div>
                </div>
                <div class="stat-number"><?= count($upcomingDeadlines) ?></div>
                <div class="stat-label">Upcoming Deadlines</div>
                <?php if (!empty($upcomingDeadlines)): ?>
                <?php $nextDeadline = reset($upcomingDeadlines); ?>
                <div style="font-size: 12px; color: var(--danger); margin-top: 8px;">
                    Next: <?= isset($nextDeadline['days_until_due']) && $nextDeadline['days_until_due'] == 0 ? 'Today' : (isset($nextDeadline['days_until_due']) && $nextDeadline['days_until_due'] == 1 ? 'Tomorrow' : 'In ' . ($nextDeadline['days_until_due'] ?? 'N/A') . ' days') ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Current Tasks -->
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Current Tasks
                </h3>
                <div>
                    <button class="task-btn task-btn-secondary" onclick="filterTasks('all')">All</button>
                    <button class="task-btn task-btn-secondary" onclick="filterTasks('urgent')">Urgent</button>
                </div>
            </div>
            
            <div class="task-list">
                <?php if (empty($currentTasks)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-double"></i>
                    <p>No active tasks. Great job!</p>
                </div>
                <?php else: ?>
                    <?php foreach ($currentTasks as $task): ?>
                    <div class="task-item">
                        <div class="task-header">
                            <div>
                                <div class="task-title"><?= htmlspecialchars($task['title'] ?? 'Untitled Task') ?></div>
                                <div class="task-meta">
                                    <?php if (!empty($task['project_name'])): ?>
                                    <span><i class="fas fa-folder"></i> <?= htmlspecialchars($task['project_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($task['due_date'])): ?>
                                    <span><i class="fas fa-calendar"></i> Due <?= date('M d', strtotime($task['due_date'])) ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-clock"></i> <?= $task['hours_logged'] ?? 0 ?>h logged</span>
                                </div>
                            </div>
                            <span class="task-priority priority-<?= $task['priority'] ?? 'medium' ?>"><?= ucfirst($task['priority'] ?? 'medium') ?></span>
                        </div>
                        
                        <div class="task-actions">
                            <?php if (isset($task['status']) && $task['status'] === 'todo'): ?>
                            <button class="task-btn task-btn-primary" onclick="updateTaskStatus(<?= $task['id'] ?>, 'in_progress')">
                                <i class="fas fa-play"></i>
                                Start Task
                            </button>
                            <?php else: ?>
                            <button class="task-btn task-btn-primary" onclick="updateTaskStatus(<?= $task['id'] ?>, 'completed')">
                                <i class="fas fa-check"></i>
                                Mark Complete
                            </button>
                            <?php endif; ?>
                            <button class="task-btn task-btn-secondary" onclick="viewDetails(<?= $task['id'] ?>)">
                                <i class="fas fa-info-circle"></i>
                                Details
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Leave Request Section -->
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-calendar-times"></i>
                    Leave Management
                </h3>
                <button class="task-btn task-btn-primary" onclick="showLeaveRequestForm()">
                    <i class="fas fa-plus"></i> Request Leave
                </button>
            </div>
            
            <!-- Leave Request Form Modal -->
            <div id="leaveRequestModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h3>Request Leave</h3>
                    <form id="leaveRequestForm">
                        <div class="form-group">
                            <label>Leave Type</label>
                            <select id="leaveType" required>
                                <option value="">Select type</option>
                                <option value="sick">Sick Leave</option>
                                <option value="casual">Casual Leave</option>
                                <option value="annual">Annual Leave</option>
                                <option value="unpaid">Unpaid Leave</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" id="fromDate" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" id="toDate" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Reason</label>
                            <textarea id="leaveReason" rows="3" required placeholder="Please provide a reason for your leave request"></textarea>
                        </div>
                        <div class="modal-buttons">
                            <button type="submit">Submit Request</button>
                            <button type="button" onclick="closeLeaveModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Leave Requests Table -->
            <?php if (!empty($leaveRequests)): ?>
            <table class="leave-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Requested On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaveRequests as $leave): ?>
                    <tr>
                        <td><?= ucfirst(str_replace('_', ' ', $leave['leave_type'])) ?></td>
                        <td><?= date('M d, Y', strtotime($leave['from_date'])) ?></td>
                        <td><?= date('M d, Y', strtotime($leave['to_date'])) ?></td>
                        <td>
                            <span class="leave-status status-<?= $leave['status'] ?>">
                                <?= ucfirst($leave['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($leave['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <p>No leave requests found</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Monthly Attendance Summary -->
        <?php if ($monthlyReport && isset($monthlyReport['stats'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Attendance Summary - <?= date('F Y') ?>
                </h3>
            </div>
            
            <div class="attendance-summary">
                <div class="summary-item">
                    <div class="summary-number"><?= $monthlyReport['stats']['present_days'] ?></div>
                    <div class="summary-label">Present Days</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number"><?= $monthlyReport['stats']['late_days'] ?></div>
                    <div class="summary-label">Late Days</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number"><?= $monthlyReport['stats']['calculated_half_days'] ?></div>
                    <div class="summary-label">Half Days</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number"><?= round($monthlyReport['stats']['total_hours']) ?></div>
                    <div class="summary-label">Total Hours</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <?php if (!empty($employeeStats['recent_activity'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Activity
                </h3>
            </div>
            
            <div class="task-list">
                <?php foreach ($employeeStats['recent_activity'] as $activity): ?>
                <div style="padding: 12px; border-left: 3px solid var(--gray-300); margin-bottom: 8px;">
                    <div style="font-weight: 600; margin-bottom: 4px;"><?= htmlspecialchars($activity['activity']) ?></div>
                    <div style="font-size: 12px; color: var(--text-secondary);">
                        <i class="fas fa-clock"></i> <?= $activity['time_formatted'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Office locations from PHP
        const OFFICE_LOCATIONS = <?= !empty($officeLocations) ? json_encode($officeLocations) : '[{"id":1,"name":"Main Office","latitude":"28.5383","longitude":"77.3489","radius_meters":200,"is_active":1}]' ?>;

        // Debug logging function
        function debugLog(message, data = null) {
            const debugDiv = document.getElementById('debugLog');
            const timestamp = new Date().toLocaleTimeString();
            let logEntry = `<div>[${timestamp}] ${message}`;
            if (data) {
                logEntry += `: ${typeof data === 'object' ? JSON.stringify(data) : data}`;
            }
            logEntry += '</div>';
            debugDiv.innerHTML += logEntry;
            debugDiv.scrollTop = debugDiv.scrollHeight;
            console.log(message, data);
        }

        // Test API connection
        function testAPI() {
            debugLog('Testing API connection...');
            
            fetch('attendance_api.php?action=test')
                .then(response => {
                    debugLog('API response status', response.status);
                    return response.text();
                })
                .then(text => {
                    debugLog('API raw response', text);
                    try {
                        const data = JSON.parse(text);
                        debugLog('API parsed data', data);
                    } catch (e) {
                        debugLog('API parse error', e.message);
                    }
                })
                .catch(error => {
                    debugLog('API fetch error', error.message);
                });
        }

        // Force load status
        function forceLoadStatus() {
            debugLog('Force loading status...');
            loadAttendanceStatus();
        }

        // Show all buttons for testing
        function showAllButtons() {
            debugLog('Showing all buttons');
            document.getElementById('checkInButton').style.display = 'inline-flex';
            document.getElementById('checkOutButton').style.display = 'inline-flex';
            document.getElementById('attendanceComplete').style.display = 'none';
        }

        // Test geolocation
        function testGeolocation() {
            debugLog('Testing geolocation...');
            getCurrentLocation()
                .then(location => {
                    debugLog('Location obtained', location);
                    const nearestOffice = findNearestOffice(location.lat, location.lng);
                    if (nearestOffice) {
                        alert(`Nearest office: ${nearestOffice.name}\nDistance: ${nearestOffice.distance}m\nWithin radius: ${nearestOffice.distance <= nearestOffice.radius_meters ? 'Yes' : 'No'}`);
                    }
                })
                .catch(error => {
                    debugLog('Geolocation error', error.message);
                    alert('Geolocation error: ' + error.message);
                });
        }

        // Geolocation functions
        function getCurrentLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation not supported'));
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    position => {
                        console.log('Got location:', position.coords);
                        resolve({
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        });
                    },
                    error => {
                        console.error('Geolocation error:', error);
                        reject(error);
                    },
                    { 
                        enableHighAccuracy: true, 
                        timeout: 10000,
                        maximumAge: 0 
                    }
                );
            });
        }

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

        function findNearestOffice(lat, lng) {
            let nearestOffice = null;
            let minDistance = Infinity;
            
            for (let office of OFFICE_LOCATIONS) {
                if (!office.is_active) continue;
                
                const distance = calculateDistance(
                    lat, lng,
                    parseFloat(office.latitude), 
                    parseFloat(office.longitude)
                );
                
                if (distance < minDistance) {
                    minDistance = distance;
                    nearestOffice = {
                        ...office,
                        distance: Math.round(distance)
                    };
                }
            }
            
            return nearestOffice;
        }

        // Modified loadAttendanceStatus with debugging
        function loadAttendanceStatus() {
            debugLog('Starting loadAttendanceStatus');
            
            const statusDiv = document.getElementById('statusMessage');
            const checkInBtn = document.getElementById('checkInButton');
            const checkOutBtn = document.getElementById('checkOutButton');
            const completeDiv = document.getElementById('attendanceComplete');
            
            debugLog('Elements found', {
                statusDiv: !!statusDiv,
                checkInBtn: !!checkInBtn,
                checkOutBtn: !!checkOutBtn,
                completeDiv: !!completeDiv
            });
            
            fetch('attendance_api.php?action=get_today_status')
                .then(response => {
                    debugLog('Status API response', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    debugLog('Status API raw text', text.substring(0, 200));
                    
                    // Check if response is HTML (error page)
                    if (text.includes('<!DOCTYPE') || text.includes('<html')) {
                        throw new Error('Received HTML instead of JSON - API might be returning an error page');
                    }
                    
                    const data = JSON.parse(text);
                    debugLog('Status API parsed data', data);
                    
                    if (!data.success || !data.attendance || !data.attendance.check_in_time) {
                        debugLog('No check-in found, showing check-in button');
                        statusDiv.innerHTML = '<p class="not-checked-in">Not checked in yet today</p>';
                        checkInBtn.style.display = 'inline-flex';
                        checkOutBtn.style.display = 'none';
                        completeDiv.style.display = 'none';
                    } else if (data.attendance.check_in_time && !data.attendance.check_out_time) {
                        debugLog('Checked in but not out');
                        const checkInTime = new Date(data.attendance.check_in_time);
                        const timeStr = checkInTime.toLocaleTimeString('en-US', { 
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: true 
                        });
                        statusDiv.innerHTML = `<div class="status-display"><p><strong>Checked in at:</strong> ${timeStr}</p></div>`;
                        checkInBtn.style.display = 'none';
                        checkOutBtn.style.display = 'inline-flex';
                        completeDiv.style.display = 'none';
                    } else {
                        debugLog('Attendance complete');
                        statusDiv.innerHTML = '<div class="status-display"><p><strong>Attendance complete for today</strong></p></div>';
                        checkInBtn.style.display = 'none';
                        checkOutBtn.style.display = 'none';
                        completeDiv.style.display = 'flex';
                    }
                })
                .catch(error => {
                    debugLog('Status error', error.message);
                    statusDiv.innerHTML = '<p class="not-checked-in">Error loading status - Check console</p>';
                    // Show check-in button as fallback
                    checkInBtn.style.display = 'inline-flex';
                    checkOutBtn.style.display = 'none';
                    completeDiv.style.display = 'none';
                });
        }

        // Check-in function with geolocation
        async function checkIn() {
            debugLog('Check-in button clicked!');
            
            const btn = document.getElementById('checkInButton');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
            
            try {
                // Try to get current location
                const location = await getCurrentLocation();
                debugLog('Location obtained', location);
                
                // Find nearest office
                const nearestOffice = findNearestOffice(location.lat, location.lng);
                debugLog('Nearest office', nearestOffice);
                
                // Check if within allowed radius
                let officeName = 'Remote';
                let isWithinOffice = false;
                
                if (nearestOffice && nearestOffice.distance <= nearestOffice.radius_meters) {
                    officeName = nearestOffice.name;
                    isWithinOffice = true;
                    debugLog('Within office radius');
                } else if (nearestOffice) {
                    debugLog(`Outside office radius: ${nearestOffice.distance}m (limit: ${nearestOffice.radius_meters}m)`);
                    if (!confirm(`You are ${nearestOffice.distance}m away from ${nearestOffice.name} (limit: ${nearestOffice.radius_meters}m). Check in as remote?`)) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
                        return;
                    }
                }
                
                // Store location for late check-in
                window.pendingCheckIn = {
                    lat: location.lat,
                    lng: location.lng,
                    office: officeName
                };
                
                // Check if late
                const now = new Date();
                const lateTime = new Date();
                lateTime.setHours(10, 30, 0);
                
                if (now > lateTime) {
                    // Show late reason modal
                    document.getElementById('lateReasonModal').style.display = 'flex';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
                } else {
                    // Direct check in
                    performCheckIn(location.lat, location.lng, officeName, '');
                }
                
            } catch (error) {
                debugLog('Location error', error.message);
                
                // Offer manual check-in if geolocation fails
                if (confirm('Unable to get your location. Would you like to check in manually?\n\nNote: This will be recorded as a remote check-in.')) {
                    window.pendingCheckIn = {
                        lat: 0,
                        lng: 0,
                        office: 'Manual Check-in'
                    };
                    
                    const now = new Date();
                    const lateTime = new Date();
                    lateTime.setHours(10, 30, 0);
                    
                    if (now > lateTime) {
                        document.getElementById('lateReasonModal').style.display = 'flex';
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
                    } else {
                        performCheckIn(0, 0, 'Manual Check-in', '');
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
                }
            }
        }

        function submitLateCheckIn() {
            const reason = document.getElementById('lateReason').value.trim();
            if (!reason) {
                alert('Please provide a reason for late check-in');
                return;
            }
            
            performCheckIn(
                window.pendingCheckIn.lat,
                window.pendingCheckIn.lng,
                window.pendingCheckIn.office,
                reason
            );
            
            closeLateModal();
        }

        function closeLateModal() {
            document.getElementById('lateReasonModal').style.display = 'none';
            document.getElementById('lateReason').value = '';
            window.pendingCheckIn = null;
        }

        function performCheckIn(lat, lng, office, lateReason) {
            debugLog('Performing check-in', {lat, lng, office, lateReason});
            
            const btn = document.getElementById('checkInButton');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking in...';
            
            const formData = new FormData();
            formData.append('action', 'check_in');
            formData.append('lat', lat.toString());
            formData.append('lng', lng.toString());
            formData.append('office', office);
            formData.append('late_reason', lateReason);
            
            debugLog('Sending check-in request');
            
            fetch('attendance_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                debugLog('Check-in response status', response.status);
                return response.text();
            })
            .then(text => {
                debugLog('Check-in response', text);
                const data = JSON.parse(text);
                if (data.success) {
                    alert(`Successfully checked in at ${office}!`);
                    location.reload();
                } else {
                    alert('Check-in failed: ' + (data.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
                }
            })
            .catch(error => {
                debugLog('Check-in error', error.message);
                alert('Error during check-in');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
            });
        }

        // Check-out function with geolocation
        async function checkOut() {
            debugLog('Check-out button clicked!');
            
            const btn = document.getElementById('checkOutButton');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
            
            try {
                // Try to get current location
                const location = await getCurrentLocation();
                const nearestOffice = findNearestOffice(location.lat, location.lng);
                
                let officeName = 'Remote';
                if (nearestOffice && nearestOffice.distance <= nearestOffice.radius_meters) {
                    officeName = nearestOffice.name;
                }
                
                performCheckOut(location.lat, location.lng, officeName);
                
            } catch (error) {
                debugLog('Location error', error.message);
                
                // Proceed with check-out even if location fails
                if (confirm('Unable to get your location. Proceed with check-out?')) {
                    performCheckOut(0, 0, 'Manual Check-out');
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Check Out';
                }
            }
        }

        function performCheckOut(lat, lng, office) {
            debugLog('Performing check-out', {lat, lng, office});
            
            const btn = document.getElementById('checkOutButton');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking out...';
            
            const formData = new FormData();
            formData.append('action', 'check_out');
            formData.append('lat', lat.toString());
            formData.append('lng', lng.toString());
            formData.append('office', office);
            
            fetch('attendance_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                debugLog('Check-out response status', response.status);
                return response.text();
            })
            .then(text => {
                debugLog('Check-out response', text);
                const data = JSON.parse(text);
                if (data.success) {
                    alert(`Successfully checked out from ${office}! Hours: ${data.hours || 'N/A'}`);
                    location.reload();
                } else {
                    alert('Check-out failed: ' + (data.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Check Out';
                }
            })
            .catch(error => {
                debugLog('Check-out error', error.message);
                alert('Error during check-out');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Check Out';
            });
        }

        // Task management functions
        function updateTaskStatus(taskId, status) {
            fetch('employee_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_action=update_task_status&task_id=${taskId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating task status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating task status');
            });
        }

        function viewDetails(taskId) {
            alert(`Task details feature coming soon for task ${taskId}`);
        }

        function filterTasks(filter) {
            console.log('Filtering tasks by:', filter);
            // Implement task filtering
        }

        function showNotifications() {
            <?php 
            $overdueCount = 0;
            if (isset($employeeStats['overdue_tasks'])) {
                $overdueCount = is_numeric($employeeStats['overdue_tasks']) ? intval($employeeStats['overdue_tasks']) : 0;
            }
            if ($overdueCount > 0): 
            ?>
            alert('You have <?= $overdueCount ?> overdue tasks that need attention!');
            <?php else: ?>
            alert('No new notifications');
            <?php endif; ?>
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'auth.php?action=logout';
            }
        }

        // Leave request functions
        function showLeaveRequestForm() {
            document.getElementById('leaveRequestModal').style.display = 'flex';
        }

        function closeLeaveModal() {
            document.getElementById('leaveRequestModal').style.display = 'none';
            document.getElementById('leaveRequestForm').reset();
        }

        document.getElementById('leaveRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'request_leave');
            formData.append('leave_type', document.getElementById('leaveType').value);
            formData.append('from_date', document.getElementById('fromDate').value);
            formData.append('to_date', document.getElementById('toDate').value);
            formData.append('reason', document.getElementById('leaveReason').value);
            
            // Validate dates
            if (new Date(document.getElementById('fromDate').value) > new Date(document.getElementById('toDate').value)) {
                alert('End date must be after start date');
                return;
            }
            
            fetch('attendance_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Leave request submitted successfully!');
                    closeLeaveModal();
                    location.reload();
                } else {
                    alert('Failed to submit leave request: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error submitting leave request. Please try again.');
                console.error('Leave request error:', error);
            });
        });

        // Initialize on load
        window.addEventListener('DOMContentLoaded', function() {
            debugLog('DOM loaded, initializing...');
            debugLog('Office locations', OFFICE_LOCATIONS);
            debugLog('Session info', {
                employeeId: <?= $employeeId ?>,
                employeeName: '<?= $employeeName ?>'
            });
            
            // Wait a bit then load status
            setTimeout(() => {
                debugLog('Loading attendance status after delay...');
                loadAttendanceStatus();
            }, 1000);
        });

        // Also try loading immediately
        debugLog('Script loaded, attempting immediate status load...');
        loadAttendanceStatus();
    </script>
</body>
</html>