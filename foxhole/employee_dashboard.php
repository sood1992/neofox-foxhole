<?php
/**
 * FOXHOLE - Employee Dashboard
 * Personal workspace for team members
 * Time tracking, tasks, attendance, and performance
 */

// Session configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Include dependencies
require_once 'config-2.php';
require_once 'api_enhanced.php';
require_once 'attendance_functions.php';

// Initialize
$api = new DashboardAPI();
$attendanceManager = new AttendanceManager();

// Get employee data
$employeeId = $isTestMode ? 1 : ($_SESSION['user_id'] ?? 0);
$employeeName = $isTestMode ? 'Test Employee' : ($_SESSION['user_name'] ?? 'Employee');

// Get attendance data
$todayAttendance = $attendanceManager->getTodayAttendance($employeeId);
$officeLocations = $attendanceManager->getOfficeLocations();
$currentMonth = date('n');
$currentYear = date('Y');
$monthlyReport = $attendanceManager->getMonthlyReport($employeeId, $currentMonth, $currentYear);
$leaveRequests = $attendanceManager->getEmployeeLeaveRequests($employeeId);

// Get employee stats
$employeeStats = $api->getEmployeeDetailedStats($employeeId);
$employeeStats = array_merge([
    'tasks' => [],
    'avg_efficiency' => 0,
    'overdue_tasks' => 0,
    'recent_activity' => [],
    'total_hours' => 0,
    'week_hours' => 0
], $employeeStats ?: []);

// Categorize tasks
$currentTasks = array_filter($employeeStats['tasks'] ?? [], fn($t) => in_array($t['status'] ?? '', ['todo', 'in_progress']));
$completedTasks = array_filter($employeeStats['tasks'] ?? [], fn($t) => ($t['status'] ?? '') === 'completed');
$overdueTasks = array_filter($currentTasks, fn($t) => !empty($t['due_date']) && strtotime($t['due_date']) < time());

// Calculate stats
$completedThisMonth = count(array_filter($completedTasks, fn($t) =>
    !empty($t['last_activity']) && date('Y-m', strtotime($t['last_activity'])) === date('Y-m')
));

$hoursThisWeek = array_sum(array_map(fn($t) => $t['hours_logged'] ?? 0,
    array_filter($employeeStats['tasks'] ?? [], fn($t) =>
        !empty($t['last_activity']) && date('W', strtotime($t['last_activity'])) === date('W')
    )
));

// Attendance status
$isCheckedIn = !empty($todayAttendance) && empty($todayAttendance['check_out_time']);
$checkInTime = $isCheckedIn ? $todayAttendance['check_in_time'] : null;
$workingHours = 0;
if ($isCheckedIn && $checkInTime) {
    $workingHours = (time() - strtotime($checkInTime)) / 3600;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    switch ($_POST['ajax_action']) {
        case 'update_task_status':
            $taskId = intval($_POST['task_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if ($taskId && in_array($status, ['todo', 'in_progress', 'completed'])) {
                $result = $api->updateTaskStatus($taskId, $status);
                echo json_encode(['success' => true, 'message' => 'Task status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit;

        case 'log_hours':
            $taskId = intval($_POST['task_id'] ?? 0);
            $hours = floatval($_POST['hours'] ?? 0);
            if ($taskId && $hours > 0) {
                $result = $api->updateTaskHours($taskId, $hours);
                echo json_encode(['success' => true, 'message' => 'Hours logged successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit;

        case 'check_in':
            $location = $_POST['location'] ?? '';
            $latitude = floatval($_POST['latitude'] ?? 0);
            $longitude = floatval($_POST['longitude'] ?? 0);

            if ($location && $latitude && $longitude) {
                $result = $attendanceManager->checkIn($employeeId, $employeeName, $location, $latitude, $longitude);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Location data required']);
            }
            exit;

        case 'check_out':
            $result = $attendanceManager->checkOut($employeeId);
            echo json_encode($result);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Foxhole</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">

    <style>
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl);
            color: white;
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
        }

        .welcome-time {
            font-size: var(--font-size-sm);
            opacity: 0.9;
            margin-bottom: var(--spacing-sm);
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
        }

        .welcome-subtitle {
            font-size: var(--font-size-base);
            opacity: 0.9;
        }

        .attendance-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-sm);
            margin-bottom: var(--spacing-xl);
        }

        .attendance-status {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .status-indicator {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            animation: pulse 2s infinite;
        }

        .status-indicator.checked-in {
            background: var(--success-light);
            color: var(--success);
        }

        .status-indicator.checked-out {
            background: var(--gray-200);
            color: var(--gray-600);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .status-info h3 {
            font-size: var(--font-size-xl);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .status-time {
            font-size: var(--font-size-lg);
            color: var(--text-secondary);
            font-weight: 500;
        }

        .working-hours {
            font-size: var(--font-size-sm);
            color: var(--primary);
            font-weight: 600;
            margin-top: 4px;
        }

        .attendance-actions {
            display: flex;
            gap: var(--spacing-md);
        }

        .location-select {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-family: inherit;
        }

        .task-list {
            list-style: none;
        }

        .task-item {
            background: white;
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            border-left: 4px solid var(--task-color, var(--gray-300));
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
        }

        .task-item:hover {
            box-shadow: var(--shadow-md);
            transform: translateX(4px);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-sm);
        }

        .task-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: var(--font-size-base);
        }

        .task-meta {
            display: flex;
            gap: var(--spacing-md);
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            margin-bottom: var(--spacing-md);
        }

        .task-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
        }

        .performance-ring {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto var(--spacing-lg);
        }

        .performance-ring svg {
            transform: rotate(-90deg);
        }

        .performance-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin-top: var(--spacing-md);
        }

        .calendar-day {
            aspect-ratio: 1;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xs);
            font-weight: 600;
        }

        .calendar-day.present {
            background: var(--success-light);
            color: var(--success);
        }

        .calendar-day.absent {
            background: var(--danger-light);
            color: var(--danger);
        }

        .calendar-day.half-day {
            background: var(--warning-light);
            color: var(--warning);
        }

        .calendar-day.today {
            border: 2px solid var(--primary);
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include 'components/header.php'; ?>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-time">
                        <?php
                        $hour = date('G');
                        if ($hour < 12) echo 'Good Morning';
                        elseif ($hour < 18) echo 'Good Afternoon';
                        else echo 'Good Evening';
                        ?>, <?= htmlspecialchars($employeeName) ?>
                    </div>
                    <h1 class="welcome-title">Welcome back to your workspace!</h1>
                    <p class="welcome-subtitle">
                        <?php if ($isCheckedIn): ?>
                            You're currently checked in. Keep up the great work!
                        <?php else: ?>
                            Ready to start your day? Check in to begin tracking your time.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Quick Stats -->
                <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
                    <div class="metric-card" style="--metric-color: var(--primary);">
                        <div class="metric-header">
                            <span class="metric-label">Active Tasks</span>
                            <div class="metric-icon"><i class="fas fa-tasks"></i></div>
                        </div>
                        <div class="metric-value"><?= count($currentTasks) ?></div>
                        <div class="metric-change <?= count($overdueTasks) > 0 ? 'negative' : 'positive' ?>">
                            <?php if (count($overdueTasks) > 0): ?>
                                <i class="fas fa-exclamation-triangle"></i> <?= count($overdueTasks) ?> overdue
                            <?php else: ?>
                                <i class="fas fa-check"></i> All on track
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="metric-card" style="--metric-color: var(--success);">
                        <div class="metric-header">
                            <span class="metric-label">Completed (Month)</span>
                            <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <div class="metric-value"><?= $completedThisMonth ?></div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up"></i> Tasks completed
                        </div>
                    </div>

                    <div class="metric-card" style="--metric-color: var(--info);">
                        <div class="metric-header">
                            <span class="metric-label">Hours (Week)</span>
                            <div class="metric-icon"><i class="fas fa-clock"></i></div>
                        </div>
                        <div class="metric-value"><?= number_format($hoursThisWeek, 1) ?></div>
                        <div class="metric-change positive">
                            <i class="fas fa-calendar-week"></i> This week
                        </div>
                    </div>

                    <div class="metric-card" style="--metric-color: var(--warning);">
                        <div class="metric-header">
                            <span class="metric-label">Efficiency</span>
                            <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
                        </div>
                        <div class="metric-value"><?= round($employeeStats['avg_efficiency'] ?? 0) ?>%</div>
                        <div class="metric-change positive">
                            <i class="fas fa-fire"></i> Performance
                        </div>
                    </div>
                </div>

                <!-- Attendance Check-in/out -->
                <div class="attendance-card">
                    <h2 style="font-size: var(--font-size-xl); font-weight: 700; margin-bottom: var(--spacing-lg);">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </h2>

                    <div class="attendance-status">
                        <div class="status-indicator <?= $isCheckedIn ? 'checked-in' : 'checked-out' ?>">
                            <i class="fas fa-<?= $isCheckedIn ? 'check' : 'clock' ?>"></i>
                        </div>
                        <div class="status-info">
                            <h3><?= $isCheckedIn ? 'Checked In' : 'Not Checked In' ?></h3>
                            <?php if ($isCheckedIn): ?>
                                <div class="status-time">Since <?= date('g:i A', strtotime($checkInTime)) ?></div>
                                <div class="working-hours">
                                    <i class="fas fa-hourglass-half"></i> Working for <?= floor($workingHours) ?>h <?= round(($workingHours - floor($workingHours)) * 60) ?>m
                                </div>
                            <?php else: ?>
                                <div class="status-time">Start your workday</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="attendance-actions">
                        <?php if (!$isCheckedIn): ?>
                            <select id="locationSelect" class="location-select">
                                <option value="">Select Office Location</option>
                                <?php foreach ($officeLocations as $location): ?>
                                    <option value="<?= htmlspecialchars($location['name']) ?>"
                                            data-lat="<?= $location['latitude'] ?>"
                                            data-lng="<?= $location['longitude'] ?>">
                                        <?= htmlspecialchars($location['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-success btn-lg" onclick="checkIn()">
                                <i class="fas fa-sign-in-alt"></i> Check In
                            </button>
                        <?php else: ?>
                            <button class="btn btn-danger btn-lg" onclick="checkOut()" style="margin-left: auto;">
                                <i class="fas fa-sign-out-alt"></i> Check Out
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="row">
                    <!-- My Tasks -->
                    <div class="col-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">My Tasks</h3>
                                <div style="display: flex; gap: var(--spacing-sm);">
                                    <button class="btn btn-sm btn-secondary" onclick="filterTasks('all')">All</button>
                                    <button class="btn btn-sm btn-secondary" onclick="filterTasks('in_progress')">In Progress</button>
                                    <button class="btn btn-sm btn-secondary" onclick="filterTasks('todo')">To Do</button>
                                </div>
                            </div>
                            <div class="card-body" style="padding: var(--spacing-lg);">
                                <ul class="task-list">
                                    <?php if (!empty($currentTasks)): ?>
                                        <?php foreach ($currentTasks as $task):
                                            $isOverdue = !empty($task['due_date']) && strtotime($task['due_date']) < time();
                                            $priorityColors = [
                                                'urgent' => 'var(--danger)',
                                                'high' => 'var(--warning)',
                                                'medium' => 'var(--info)',
                                                'low' => 'var(--success)'
                                            ];
                                            $taskColor = $isOverdue ? 'var(--danger)' : ($priorityColors[$task['priority'] ?? 'medium'] ?? 'var(--info)');
                                        ?>
                                            <li class="task-item" style="--task-color: <?= $taskColor ?>;" data-status="<?= htmlspecialchars($task['status']) ?>">
                                                <div class="task-header">
                                                    <div class="task-title"><?= htmlspecialchars($task['title'] ?? 'Untitled Task') ?></div>
                                                    <span class="badge" style="background: <?= $taskColor ?>; color: white;">
                                                        <?= ucfirst($task['priority'] ?? 'medium') ?>
                                                    </span>
                                                </div>
                                                <div class="task-meta">
                                                    <span><i class="fas fa-project-diagram"></i> <?= htmlspecialchars($task['project_name'] ?? 'No Project') ?></span>
                                                    <?php if (!empty($task['due_date'])): ?>
                                                        <span class="<?= $isOverdue ? 'text-danger' : '' ?>">
                                                            <i class="fas fa-calendar"></i>
                                                            <?= $isOverdue ? 'Overdue: ' : 'Due: ' ?>
                                                            <?= date('M j, Y', strtotime($task['due_date'])) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span><i class="fas fa-clock"></i> <?= number_format($task['hours_logged'] ?? 0, 1) ?>h logged</span>
                                                </div>
                                                <?php if (!empty($task['description'])): ?>
                                                    <p style="font-size: var(--font-size-sm); color: var(--text-secondary); margin: var(--spacing-sm) 0;">
                                                        <?= htmlspecialchars(substr($task['description'], 0, 100)) ?><?= strlen($task['description']) > 100 ? '...' : '' ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="task-actions">
                                                    <?php if ($task['status'] === 'todo'): ?>
                                                        <button class="btn btn-sm btn-primary" onclick="updateTaskStatus(<?= $task['id'] ?>, 'in_progress')">
                                                            <i class="fas fa-play"></i> Start
                                                        </button>
                                                    <?php elseif ($task['status'] === 'in_progress'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="updateTaskStatus(<?= $task['id'] ?>, 'completed')">
                                                            <i class="fas fa-check"></i> Complete
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-secondary" onclick="logHours(<?= $task['id'] ?>)">
                                                        <i class="fas fa-clock"></i> Log Hours
                                                    </button>
                                                    <button class="btn btn-sm btn-outline" onclick="viewTask(<?= $task['id'] ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success); margin-bottom: 1rem;"></i>
                                            <p style="font-weight: 600; color: var(--text-secondary);">No active tasks</p>
                                            <p>You're all caught up! Great job!</p>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="card" style="margin-top: var(--spacing-xl);">
                            <div class="card-header">
                                <h3 class="card-title">Recent Activity</h3>
                            </div>
                            <div class="card-body" style="padding: 0;">
                                <ul class="activity-list">
                                    <?php if (!empty($employeeStats['recent_activity'])): ?>
                                        <?php foreach (array_slice($employeeStats['recent_activity'], 0, 5) as $activity): ?>
                                            <li class="activity-item">
                                                <div class="activity-icon info">
                                                    <i class="fas fa-info-circle"></i>
                                                </div>
                                                <div class="activity-content">
                                                    <div class="activity-text"><?= htmlspecialchars($activity['activity'] ?? 'Activity') ?></div>
                                                    <div class="activity-time">
                                                        <?php
                                                        $time = strtotime($activity['created_at'] ?? 'now');
                                                        $diff = time() - $time;
                                                        if ($diff < 3600) echo floor($diff / 60) . ' minutes ago';
                                                        elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                                                        else echo floor($diff / 86400) . ' days ago';
                                                        ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="activity-item">
                                            <div class="activity-content" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                                No recent activity
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-4">
                        <!-- Performance -->
                        <div class="card" style="margin-bottom: var(--spacing-lg);">
                            <div class="card-header">
                                <h3 class="card-title">My Performance</h3>
                            </div>
                            <div class="card-body" style="text-align: center;">
                                <div class="performance-ring">
                                    <svg width="150" height="150">
                                        <circle cx="75" cy="75" r="65" fill="none" stroke="var(--gray-200)" stroke-width="12"/>
                                        <circle cx="75" cy="75" r="65" fill="none" stroke="var(--primary)" stroke-width="12"
                                                stroke-dasharray="<?= round($employeeStats['avg_efficiency'] ?? 0) * 4.08 ?> 408.2"
                                                stroke-linecap="round"/>
                                    </svg>
                                    <div class="performance-value"><?= round($employeeStats['avg_efficiency'] ?? 0) ?>%</div>
                                </div>
                                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-lg);">Overall Efficiency</p>

                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-md); text-align: center;">
                                    <div>
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">
                                            <?= count($completedTasks) ?>
                                        </div>
                                        <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">Completed</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning);">
                                            <?= count($currentTasks) ?>
                                        </div>
                                        <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">Active</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">
                                            <?= number_format($employeeStats['total_hours'] ?? 0) ?>h
                                        </div>
                                        <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">Total Hours</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--info);">
                                            <?= $employeeStats['projects_involved'] ?? 0 ?>
                                        </div>
                                        <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">Projects</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Summary -->
                        <div class="card" style="margin-bottom: var(--spacing-lg);">
                            <div class="card-header">
                                <h3 class="card-title">Attendance (<?= date('F') ?>)</h3>
                            </div>
                            <div class="card-body">
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-md); margin-bottom: var(--spacing-lg); text-align: center;">
                                    <div>
                                        <div style="font-size: 1.25rem; font-weight: 700; color: var(--success);">
                                            <?= $monthlyReport['present_days'] ?? 0 ?>
                                        </div>
                                        <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">Present</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 1.25rem; font-weight: 700; color: var(--danger);">
                                            <?= $monthlyReport['absent_days'] ?? 0 ?>
                                        </div>
                                        <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">Absent</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 1.25rem; font-weight: 700; color: var(--warning);">
                                            <?= $monthlyReport['half_days'] ?? 0 ?>
                                        </div>
                                        <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">Half Day</div>
                                    </div>
                                </div>

                                <div class="calendar-grid">
                                    <?php
                                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
                                    for ($day = 1; $day <= $daysInMonth; $day++):
                                        $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                        $isToday = $date === date('Y-m-d');
                                        $status = ''; // Would check from attendance records
                                        $class = 'calendar-day ' . ($isToday ? 'today ' : '') . $status;
                                    ?>
                                        <div class="<?= $class ?>"><?= $day ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Requests -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Leave Requests</h3>
                                <button class="btn btn-sm btn-primary" onclick="requestLeave()">
                                    <i class="fas fa-plus"></i> Request
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($leaveRequests)): ?>
                                    <?php foreach (array_slice($leaveRequests, 0, 3) as $leave): ?>
                                        <div style="padding: var(--spacing-md); background: var(--gray-50); border-radius: var(--radius-md); margin-bottom: var(--spacing-sm);">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                <strong><?= htmlspecialchars($leave['type'] ?? 'Leave') ?></strong>
                                                <span class="badge badge-<?= ($leave['status'] ?? '') === 'approved' ? 'success' : 'warning' ?>">
                                                    <?= ucfirst($leave['status'] ?? 'pending') ?>
                                                </span>
                                            </div>
                                            <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">
                                                <?= date('M j', strtotime($leave['start_date'] ?? 'now')) ?> - <?= date('M j, Y', strtotime($leave['end_date'] ?? 'now')) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="text-align: center; padding: 2rem; color: var(--text-muted); font-size: var(--font-size-sm);">
                                        No leave requests
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Attendance Functions
        function checkIn() {
            const locationSelect = document.getElementById('locationSelect');
            const selectedOption = locationSelect.options[locationSelect.selectedIndex];

            if (!selectedOption.value) {
                alert('Please select an office location');
                return;
            }

            const location = selectedOption.value;
            const latitude = parseFloat(selectedOption.dataset.lat);
            const longitude = parseFloat(selectedOption.dataset.lng);

            // Get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        fetch('employee_dashboard.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `ajax_action=check_in&location=${encodeURIComponent(location)}&latitude=${latitude}&longitude=${longitude}`
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert('Checked in successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Check-in failed'));
                            }
                        });
                    },
                    (error) => {
                        alert('Please enable location access to check in');
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser');
            }
        }

        function checkOut() {
            if (confirm('Are you sure you want to check out?')) {
                fetch('employee_dashboard.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'ajax_action=check_out'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Checked out successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Check-out failed'));
                    }
                });
            }
        }

        // Task Functions
        function updateTaskStatus(taskId, status) {
            fetch('employee_dashboard.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ajax_action=update_task_status&task_id=${taskId}&status=${status}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Task status updated!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Update failed'));
                }
            });
        }

        function logHours(taskId) {
            const hours = prompt('How many hours did you work on this task?');
            if (hours && !isNaN(hours) && hours > 0) {
                fetch('employee_dashboard.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `ajax_action=log_hours&task_id=${taskId}&hours=${hours}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Hours logged successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Logging failed'));
                    }
                });
            }
        }

        function viewTask(taskId) {
            window.location.href = 'task_details.php?id=' + taskId;
        }

        function filterTasks(status) {
            const tasks = document.querySelectorAll('.task-item');
            tasks.forEach(task => {
                if (status === 'all' || task.dataset.status === status) {
                    task.style.display = 'block';
                } else {
                    task.style.display = 'none';
                }
            });
        }

        function requestLeave() {
            alert('Leave request form coming soon!');
        }

        // Update working hours every minute
        <?php if ($isCheckedIn): ?>
        setInterval(() => {
            location.reload();
        }, 60000);
        <?php endif; ?>

        console.log('Employee Dashboard Loaded');
    </script>
</body>
</html>
