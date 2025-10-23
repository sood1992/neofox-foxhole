<?php
// attendance_api.php - Employee attendance API endpoints
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FIXED: Proper session configuration matching other files
$sessionPath = dirname(__FILE__) . '/temp_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config-2.php';
require_once 'attendance_functions.php';

header('Content-Type: application/json');

// Debug mode for testing
$debugMode = isset($_GET['debug']) || isset($_POST['debug']);

if ($debugMode) {
    // Return debug information
    echo json_encode([
        'debug' => true,
        'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'post_data' => $_POST,
        'get_data' => $_GET
    ]);
    exit;
}

// Check authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated', 'session_id' => session_id()]);
    exit;
}

$attendanceManager = new AttendanceManager();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$employeeId = $_SESSION['user_id'] ?? null;

// Ensure we have a valid employee ID
if (!$employeeId && !in_array($action, ['test'])) {
    echo json_encode(['success' => false, 'message' => 'No employee ID in session']);
    exit;
}

try {
    switch ($action) {
        case 'test':
            echo json_encode([
                'success' => true,
                'message' => 'API is working',
                'authenticated' => true,
                'employee_id' => $employeeId,
                'session_id' => session_id()
            ]);
            break;
            
        case 'check_in':
            $lat = floatval($_POST['lat'] ?? 0);
            $lng = floatval($_POST['lng'] ?? 0);
            $office = $_POST['office'] ?? '';
            $lateReason = $_POST['late_reason'] ?? '';
            
            // Allow check-in even without valid coordinates for testing
            if (empty($office)) {
                $office = 'Office'; // Default office name
            }
            
            $result = $attendanceManager->checkIn($employeeId, $lat, $lng, $office, $lateReason);
            echo json_encode($result);
            break;
            
        case 'check_out':
            $lat = floatval($_POST['lat'] ?? 0);
            $lng = floatval($_POST['lng'] ?? 0);
            $office = $_POST['office'] ?? '';
            
            // Allow check-out even without valid coordinates for testing
            if (empty($office)) {
                $office = 'Office'; // Default office name
            }
            
            $result = $attendanceManager->checkOut($employeeId, $lat, $lng, $office);
            echo json_encode($result);
            break;
            
        case 'auto_checkout':
            $result = $attendanceManager->autoCheckOut($employeeId);
            echo json_encode($result);
            break;
            
        case 'request_leave':
            $leaveType = $_POST['leave_type'] ?? '';
            $fromDate = $_POST['from_date'] ?? '';
            $toDate = $_POST['to_date'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            // Validate inputs
            if (!$leaveType || !$fromDate || !$toDate || !$reason) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                break;
            }
            
            // Validate dates
            $from = DateTime::createFromFormat('Y-m-d', $fromDate);
            $to = DateTime::createFromFormat('Y-m-d', $toDate);
            
            if (!$from || !$to) {
                echo json_encode(['success' => false, 'message' => 'Invalid date format']);
                break;
            }
            
            if ($from < new DateTime('today')) {
                echo json_encode(['success' => false, 'message' => 'Cannot request leave for past dates']);
                break;
            }
            
            $result = $attendanceManager->requestLeave($employeeId, $leaveType, $fromDate, $toDate, $reason);
            echo json_encode($result);
            break;
            
        case 'get_attendance':
            $month = intval($_GET['month'] ?? date('n'));
            $year = intval($_GET['year'] ?? date('Y'));
            
            $report = $attendanceManager->getMonthlyReport($employeeId, $month, $year);
            
            if ($report) {
                echo json_encode(['success' => true, 'data' => $report]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to get attendance data']);
            }
            break;
            
        case 'get_leave_requests':
            $status = $_GET['status'] ?? null;
            $requests = $attendanceManager->getEmployeeLeaveRequests($employeeId, $status);
            echo json_encode(['success' => true, 'data' => $requests]);
            break;
            
        case 'get_today_status':
            $todayAttendance = $attendanceManager->getTodayAttendance($employeeId);
            $officeLocations = $attendanceManager->getOfficeLocations();
            
            echo json_encode([
                'success' => true,
                'attendance' => $todayAttendance,
                'locations' => $officeLocations
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    error_log("Attendance API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>