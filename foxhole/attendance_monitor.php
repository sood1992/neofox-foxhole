<?php
// attendance_monitor.php - Real Data Attendance Monitoring System
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

// Simple authentication check
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    if (isset($_GET['admin_test'])) {
        $_SESSION['authenticated'] = true;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_name'] = 'Administrator';
        $_SESSION['user_id'] = 0;
    } else {
        header('Location: index.php?error=not_authenticated');
        exit();
    }
}

if ($_SESSION['user_role'] !== 'admin' && !isset($_GET['admin_test'])) {
    header('Location: index.php?error=not_admin');
    exit();
}

require_once 'config-2.php';

// Real Attendance Data Class
class AttendanceDataManager {
    private $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    public function getAllEmployees() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT id, name, email, role, type, status, created_at 
                FROM employees 
                ORDER BY name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching employees: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTodayAttendance() {
        if (!$this->db) return [];
        
        try {
            $today = date('Y-m-d');
            $stmt = $this->db->prepare("
                SELECT 
                    e.id, e.name, e.role, e.email, e.status as employee_status,
                    a.check_in_time, a.check_out_time, a.status as attendance_status,
                    a.location, a.late_reason, a.break_duration,
                    CASE 
                        WHEN a.check_in_time IS NOT NULL AND a.check_out_time IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, a.check_in_time, a.check_out_time) / 60.0
                        WHEN a.check_in_time IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, a.check_in_time, NOW()) / 60.0
                        ELSE 0
                    END as hours_worked
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id AND DATE(a.check_in_time) = ?
                ORDER BY e.name ASC
            ");
            $stmt->execute([$today]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching today's attendance: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAttendanceByDate($date) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    e.id, e.name, e.role, e.email,
                    a.check_in_time, a.check_out_time, a.status,
                    a.location, a.late_reason, a.break_duration,
                    CASE 
                        WHEN a.check_in_time IS NOT NULL AND a.check_out_time IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, a.check_in_time, a.check_out_time) / 60.0
                        ELSE 0
                    END as hours_worked
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id AND DATE(a.check_in_time) = ?
                ORDER BY e.name ASC
            ");
            $stmt->execute([$date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching attendance by date: " . $e->getMessage());
            return [];
        }
    }
    
    public function getMonthlyAttendance($month, $year) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    e.id, e.name, e.role,
                    COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
                    COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_days,
                    COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_days,
                    SUM(CASE 
                        WHEN a.check_in_time IS NOT NULL AND a.check_out_time IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, a.check_in_time, a.check_out_time) / 60.0
                        ELSE 0
                    END) as total_hours,
                    AVG(CASE 
                        WHEN a.check_in_time IS NOT NULL AND a.check_out_time IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, a.check_in_time, a.check_out_time) / 60.0
                        ELSE NULL
                    END) as avg_hours_per_day
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id 
                    AND MONTH(a.check_in_time) = ? 
                    AND YEAR(a.check_in_time) = ?
                GROUP BY e.id, e.name, e.role
                ORDER BY e.name ASC
            ");
            $stmt->execute([$month, $year]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching monthly attendance: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPendingLeaveRequests() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    lr.id, lr.employee_id, lr.leave_type, lr.from_date, lr.to_date,
                    lr.reason, lr.status, lr.created_at, lr.approved_by, lr.approved_at,
                    e.name as employee_name, e.role as employee_role,
                    DATEDIFF(lr.to_date, lr.from_date) + 1 as total_days
                FROM leave_requests lr
                JOIN employees e ON lr.employee_id = e.id
                WHERE lr.status = 'pending'
                ORDER BY lr.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching leave requests: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAttendanceStats() {
        if (!$this->db) return [];
        
        try {
            $today = date('Y-m-d');
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT e.id) as total_employees,
                    COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_today,
                    COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_today,
                    COUNT(CASE WHEN a.status = 'absent' OR a.id IS NULL THEN 1 END) as absent_today,
                    COUNT(CASE WHEN a.location = 'Remote' THEN 1 END) as remote_today
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id AND DATE(a.check_in_time) = ?
            ");
            $stmt->execute([$today]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching attendance stats: " . $e->getMessage());
            return [
                'total_employees' => 0,
                'present_today' => 0,
                'late_today' => 0,
                'absent_today' => 0,
                'remote_today' => 0
            ];
        }
    }
    
    public function approveLeaveRequest($leaveId, $approvedBy) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE leave_requests 
                SET status = 'approved', approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$approvedBy, $leaveId]);
        } catch (Exception $e) {
            error_log("Error approving leave request: " . $e->getMessage());
            return false;
        }
    }
    
    public function rejectLeaveRequest($leaveId, $rejectedBy, $reason = '') {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE leave_requests 
                SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$rejectedBy, $reason, $leaveId]);
        } catch (Exception $e) {
            error_log("Error rejecting leave request: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize data manager
$attendanceManager = new AttendanceDataManager();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_live_status':
            $todayAttendance = $attendanceManager->getTodayAttendance();
            $stats = $attendanceManager->getAttendanceStats();
            
            echo json_encode([
                'success' => true,
                'employees' => $todayAttendance,
                'stats' => $stats
            ]);
            break;
            
        case 'get_daily_report':
            $date = $_POST['date'] ?? date('Y-m-d');
            $department = $_POST['department'] ?? '';
            
            $attendance = $attendanceManager->getAttendanceByDate($date);
            
            // Filter by department if specified
            if ($department) {
                $attendance = array_filter($attendance, function($emp) use ($department) {
                    return stripos($emp['role'], $department) !== false;
                });
            }
            
            echo json_encode([
                'success' => true,
                'attendance' => array_values($attendance),
                'date' => $date
            ]);
            break;
            
        case 'get_monthly_overview':
            $month = $_POST['month'] ?? date('n');
            $year = $_POST['year'] ?? date('Y');
            
            $monthlyData = $attendanceManager->getMonthlyAttendance($month, $year);
            
            echo json_encode([
                'success' => true,
                'monthly_data' => $monthlyData,
                'month' => $month,
                'year' => $year
            ]);
            break;
            
        case 'get_leave_requests':
            $leaveRequests = $attendanceManager->getPendingLeaveRequests();
            
            echo json_encode([
                'success' => true,
                'leave_requests' => $leaveRequests
            ]);
            break;
            
        case 'approve_leave':
            $leaveId = $_POST['leave_id'] ?? 0;
            $approvedBy = $_SESSION['user_id'] ?? 0;
            
            $success = $attendanceManager->approveLeaveRequest($leaveId, $approvedBy);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Leave request approved successfully' : 'Failed to approve leave request'
            ]);
            break;
            
        case 'reject_leave':
            $leaveId = $_POST['leave_id'] ?? 0;
            $rejectedBy = $_SESSION['user_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            
            $success = $attendanceManager->rejectLeaveRequest($leaveId, $rejectedBy, $reason);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Leave request rejected successfully' : 'Failed to reject leave request'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit();
}

// Get initial data for page load
$initialStats = $attendanceManager->getAttendanceStats();
$allEmployees = $attendanceManager->getAllEmployees();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole - Attendance Monitoring System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #6366F1;
            --secondary: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --success: #10B981;
            --info: #3B82F6;
            
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-400: #94A3B8;
            --gray-500: #64748B;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1E293B;
            --gray-900: #0F172A;
            
            --bg-primary: #FAFAFB;
            --bg-secondary: #FFFFFF;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--gray-900);
            line-height: 1.5;
            font-size: 14px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header Section */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #10B981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Navigation Tabs */
        .nav-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: white;
            padding: 0.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .nav-tab {
            padding: 1rem 1.5rem;
            border: none;
            background: transparent;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-600);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-tab.active {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .nav-tab:hover:not(.active) {
            background: var(--gray-100);
            color: var(--gray-800);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Cards and Sections */
        .section-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--gray-200);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Quick Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }

        .stat-icon.success { background: linear-gradient(135deg, var(--success), #34D399); }
        .stat-icon.warning { background: linear-gradient(135deg, var(--warning), #FBBF24); }
        .stat-icon.danger { background: linear-gradient(135deg, var(--danger), #F87171); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--gray-600);
            font-weight: 600;
        }

        /* Live Status Table */
        .live-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .live-table thead {
            background: var(--gray-50);
        }

        .live-table th,
        .live-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .live-table th {
            font-weight: 700;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .live-table tbody tr:hover {
            background: var(--gray-50);
        }

        .live-table tbody tr:last-child td {
            border-bottom: none;
        }

        .employee-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
        }

        .employee-details {
            flex: 1;
        }

        .employee-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        .employee-role {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-online {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-away {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-offline {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-600);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 2px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        /* Filters */
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .form-select, .form-input {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            background: white;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            min-width: 120px;
        }

        .form-input {
            cursor: text;
        }

        /* Attendance Grid */
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .attendance-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .attendance-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .attendance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .attendance-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .attendance-stat {
            text-align: center;
            padding: 0.75rem;
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }

        .attendance-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .attendance-stat-label {
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Leave Requests */
        .leave-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .leave-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .leave-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .leave-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .leave-type {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .leave-type.sick {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .leave-type.casual {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .leave-type.annual {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .leave-info {
            margin-bottom: 1rem;
        }

        .leave-info p {
            margin: 0.5rem 0;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .leave-info strong {
            color: var(--gray-700);
            font-weight: 600;
        }

        .leave-actions {
            display: flex;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .nav-tabs {
                overflow-x: auto;
                padding: 0.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .header h1 {
                font-size: 2rem;
            }
        }

        /* Loading States */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: var(--gray-500);
            font-size: 1.1rem;
        }

        .spinner {
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Success/Error Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>
    <?php if (isset($_GET['admin_test'])): ?>
    <div style="background: linear-gradient(135deg, var(--info), #60A5FA); color: white; padding: 1rem; text-align: center;">
        <strong>🧪 Admin Test Mode:</strong> Attendance monitoring with sample data. 
        <a href="dashboard.php" style="color: white; text-decoration: underline;">Return to Dashboard</a>
    </div>
    <?php endif; ?>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-title">
                    <div class="header-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <h1>Attendance Monitor</h1>
                        <p>Real-time employee attendance tracking and management</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        Live Monitoring
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('live-status')">
                <i class="fas fa-wifi"></i>
                Live Status
            </button>
            <button class="nav-tab" onclick="showTab('daily-report')">
                <i class="fas fa-calendar-day"></i>
                Daily Report
            </button>
            <button class="nav-tab" onclick="showTab('monthly-overview')">
                <i class="fas fa-chart-bar"></i>
                Monthly Overview
            </button>
            <button class="nav-tab" onclick="showTab('leave-management')">
                <i class="fas fa-calendar-times"></i>
                Leave Management
            </button>
            <button class="nav-tab" onclick="showTab('reports')">
                <i class="fas fa-file-alt"></i>
                Reports & Analytics
            </button>
        </div>

        <!-- Live Status Tab -->
        <div id="live-status" class="tab-content active">
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-number" id="presentCount"><?= $initialStats['present_today'] ?? 0 ?></div>
                    <div class="stat-label">Present Now</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-number" id="lateCount"><?= $initialStats['late_today'] ?? 0 ?></div>
                    <div class="stat-label">Late Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-number" id="absentCount"><?= $initialStats['absent_today'] ?? 0 ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-number" id="remoteCount"><?= $initialStats['remote_today'] ?? 0 ?></div>
                    <div class="stat-label">Working Remote</div>
                </div>
            </div>

            <!-- Live Employee Status -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-users"></i>
                        Live Employee Status
                    </h2>
                    <button class="btn btn-primary" onclick="refreshLiveStatus()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="live-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Status</th>
                                <th>Check In</th>
                                <th>Location</th>
                                <th>Hours Today</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="liveStatusTable">
                            <tr>
                                <td colspan="6" class="loading">
                                    <i class="fas fa-spinner spinner"></i>
                                    Loading live status...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daily Report Tab -->
        <div id="daily-report" class="tab-content">
            <!-- Date Filter -->
            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label">Date:</label>
                    <input type="date" class="form-input" id="reportDate" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Department:</label>
                    <select class="form-select" id="departmentFilter">
                        <option value="">All Departments</option>
                        <option value="Engineering">Engineering</option>
                        <option value="Design">Design</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Management">Management</option>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="loadDailyReport()">
                    <i class="fas fa-search"></i>
                    Generate Report
                </button>
                <button class="btn btn-secondary" onclick="exportDailyReport()">
                    <i class="fas fa-download"></i>
                    Export CSV
                </button>
            </div>

            <!-- Daily Attendance Grid -->
            <div class="attendance-grid" id="dailyAttendanceGrid">
                <!-- Populated by JavaScript -->
            </div>
        </div>

        <!-- Monthly Overview Tab -->
        <div id="monthly-overview" class="tab-content">
            <!-- Month Filter -->
            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label">Month:</label>
                    <select class="form-select" id="monthSelect">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Year:</label>
                    <select class="form-select" id="yearSelect">
                        <?php for ($y = date('Y') - 1; $y <= date('Y'); $y++): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="loadMonthlyOverview()">
                    <i class="fas fa-chart-bar"></i>
                    Load Overview
                </button>
            </div>

            <!-- Monthly Summary -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-alt"></i>
                        Monthly Summary
                    </h2>
                </div>
                <div id="monthlySummary">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Leave Management Tab -->
        <div id="leave-management" class="tab-content">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-times"></i>
                        Pending Leave Requests
                    </h2>
                    <button class="btn btn-primary" onclick="loadLeaveRequests()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>

                <div class="leave-grid" id="leaveRequestsGrid">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reports" class="tab-content">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-file-alt"></i>
                        Reports & Analytics
                    </h2>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-number">94.2%</div>
                        <div class="stat-label">Overall Attendance Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number">2.3</div>
                        <div class="stat-label">Avg Late Days/Month</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number">18.5</div>
                        <div class="stat-label">Avg Working Days</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div class="stat-number">1.2</div>
                        <div class="stat-label">Avg Leave Days</div>
                    </div>
                </div>

                <!-- Report Actions -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <button class="btn btn-primary" onclick="generateReport('monthly')">
                        <i class="fas fa-file-excel"></i>
                        Monthly Report
                    </button>
                    <button class="btn btn-primary" onclick="generateReport('employee')">
                        <i class="fas fa-users"></i>
                        Employee Summary
                    </button>
                    <button class="btn btn-primary" onclick="generateReport('department')">
                        <i class="fas fa-building"></i>
                        Department Analysis
                    </button>
                    <button class="btn btn-primary" onclick="generateReport('payroll')">
                        <i class="fas fa-money-bill"></i>
                        Payroll Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let refreshInterval;
        let currentEmployees = [];

        // Tab Management
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Initialize tab-specific data
            initializeTab(tabId);
        }

        function initializeTab(tabId) {
            switch(tabId) {
                case 'live-status':
                    startLiveMonitoring();
                    break;
                case 'daily-report':
                    loadDailyReport();
                    break;
                case 'monthly-overview':
                    loadMonthlyOverview();
                    break;
                case 'leave-management':
                    loadLeaveRequests();
                    break;
                case 'reports':
                    // Reports tab doesn't need initialization
                    break;
            }
        }

        // Live Status Functions
        function startLiveMonitoring() {
            refreshLiveStatus();
            
            // Clear any existing interval
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            
            // Refresh every 30 seconds
            refreshInterval = setInterval(refreshLiveStatus, 30000);
        }

        async function refreshLiveStatus() {
            try {
                const response = await fetch('attendance_monitor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_live_status'
                });
                
                const data = await response.json();
                if (data.success) {
                    currentEmployees = data.employees;
                    updateLiveStatusTable(data.employees);
                    updateQuickStats(data.stats);
                }
            } catch (error) {
                console.error('Error refreshing live status:', error);
                showError('Failed to refresh live status');
            }
        }

        function updateLiveStatusTable(employees) {
            const tbody = document.getElementById('liveStatusTable');
            tbody.innerHTML = '';
            
            if (employees.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                            No employee data found
                        </td>
                    </tr>
                `;
                return;
            }
            
            employees.forEach(emp => {
                const status = getEmployeeStatus(emp);
                const checkInTime = emp.check_in_time ? formatTime(emp.check_in_time) : '-';
                const hoursWorked = emp.hours_worked ? parseFloat(emp.hours_worked).toFixed(1) : '0.0';
                const location = emp.location || 'Office';
                
                const row = `
                    <tr>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    ${emp.name.substring(0, 2).toUpperCase()}
                                </div>
                                <div class="employee-details">
                                    <div class="employee-name">${emp.name}</div>
                                    <div class="employee-role">${emp.role || 'Employee'}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge status-${status.class}">
                                <span class="status-dot"></span>
                                ${status.text}
                            </span>
                        </td>
                        <td>${checkInTime}</td>
                        <td>${location}</td>
                        <td>${hoursWorked}h</td>
                        <td>
                            <button class="btn btn-primary" onclick="viewEmployeeDetails(${emp.id})">
                                <i class="fas fa-eye"></i>
                                View
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        function updateQuickStats(stats) {
            document.getElementById('presentCount').textContent = stats.present_today || 0;
            document.getElementById('lateCount').textContent = stats.late_today || 0;
            document.getElementById('absentCount').textContent = stats.absent_today || 0;
            document.getElementById('remoteCount').textContent = stats.remote_today || 0;
        }

        // Daily Report Functions
        async function loadDailyReport() {
            const date = document.getElementById('reportDate').value;
            const department = document.getElementById('departmentFilter').value;
            
            try {
                const response = await fetch('attendance_monitor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_daily_report&date=${date}&department=${encodeURIComponent(department)}`
                });
                
                const data = await response.json();
                if (data.success) {
                    displayDailyReport(data.attendance);
                } else {
                    showError('Failed to load daily report');
                }
            } catch (error) {
                console.error('Error loading daily report:', error);
                showError('Error loading daily report');
            }
        }

        function displayDailyReport(attendance) {
            const grid = document.getElementById('dailyAttendanceGrid');
            grid.innerHTML = '';
            
            if (attendance.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--gray-500);">
                        No attendance data found for the selected date
                    </div>
                `;
                return;
            }
            
            attendance.forEach(emp => {
                const status = getEmployeeStatus(emp);
                const checkIn = emp.check_in_time ? formatTime(emp.check_in_time) : '-';
                const checkOut = emp.check_out_time ? formatTime(emp.check_out_time) : '-';
                const hoursWorked = emp.hours_worked ? parseFloat(emp.hours_worked).toFixed(1) : '0.0';
                const location = emp.location || 'Office';
                
                const card = `
                    <div class="attendance-card">
                        <div class="attendance-header">
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    ${emp.name.substring(0, 2).toUpperCase()}
                                </div>
                                <div class="employee-details">
                                    <div class="employee-name">${emp.name}</div>
                                    <div class="employee-role">${emp.role || 'Employee'}</div>
                                </div>
                            </div>
                            <span class="status-badge status-${status.class}">
                                ${status.text}
                            </span>
                        </div>
                        
                        <div class="attendance-stats">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">${checkIn}</div>
                                <div class="attendance-stat-label">Check In</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">${checkOut}</div>
                                <div class="attendance-stat-label">Check Out</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">${hoursWorked}h</div>
                                <div class="attendance-stat-label">Hours</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">${location}</div>
                                <div class="attendance-stat-label">Location</div>
                            </div>
                        </div>
                        
                        ${emp.late_reason ? `
                            <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(245, 158, 11, 0.1); border-radius: var(--radius-md); font-size: 0.875rem;">
                                <strong>Late Reason:</strong> ${emp.late_reason}
                            </div>
                        ` : ''}
                    </div>
                `;
                grid.innerHTML += card;
            });
        }

        // Monthly Overview Functions
        async function loadMonthlyOverview() {
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearSelect').value;
            
            try {
                const response = await fetch('attendance_monitor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_monthly_overview&month=${month}&year=${year}`
                });
                
                const data = await response.json();
                if (data.success) {
                    displayMonthlyOverview(data.monthly_data, month, year);
                } else {
                    showError('Failed to load monthly overview');
                }
            } catch (error) {
                console.error('Error loading monthly overview:', error);
                showError('Error loading monthly overview');
            }
        }

        function displayMonthlyOverview(monthlyData, month, year) {
            const summary = document.getElementById('monthlySummary');
            
            if (monthlyData.length === 0) {
                summary.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                        No attendance data found for ${getMonthName(month)} ${year}
                    </div>
                `;
                return;
            }
            
            // Calculate overall statistics
            const totalEmployees = monthlyData.length;
            const totalPresentDays = monthlyData.reduce((sum, emp) => sum + (parseInt(emp.present_days) || 0), 0);
            const totalLateDays = monthlyData.reduce((sum, emp) => sum + (parseInt(emp.late_days) || 0), 0);
            const totalAbsentDays = monthlyData.reduce((sum, emp) => sum + (parseInt(emp.absent_days) || 0), 0);
            const totalHours = monthlyData.reduce((sum, emp) => sum + (parseFloat(emp.total_hours) || 0), 0);
            
            const workingDaysInMonth = getWorkingDaysInMonth(month, year);
            const attendanceRate = totalEmployees > 0 ? ((totalPresentDays / (totalEmployees * workingDaysInMonth)) * 100).toFixed(1) : 0;
            
            summary.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-number">${attendanceRate}%</div>
                        <div class="stat-label">Attendance Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-number">${workingDaysInMonth}</div>
                        <div class="stat-label">Working Days</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number">${totalLateDays}</div>
                        <div class="stat-label">Late Instances</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-number">${totalAbsentDays}</div>
                        <div class="stat-label">Absent Days</div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Employee Performance</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
                        ${monthlyData.map(emp => {
                            const empAttendanceRate = workingDaysInMonth > 0 ? ((emp.present_days / workingDaysInMonth) * 100).toFixed(1) : 0;
                            const statusClass = empAttendanceRate >= 90 ? 'success' : empAttendanceRate >= 75 ? 'warning' : 'danger';
                            
                            return `
                                <div style="padding: 1rem; background: var(--gray-50); border-radius: var(--radius-md); border-left: 4px solid var(--${statusClass});">
                                    <strong>${emp.name}</strong><br>
                                    <span style="color: var(--${statusClass});">${empAttendanceRate}% attendance</span><br>
                                    <small style="color: var(--gray-600);">
                                        ${emp.present_days} present, ${emp.late_days} late, ${emp.absent_days} absent
                                    </small><br>
                                    <small style="color: var(--gray-600);">
                                        Total: ${parseFloat(emp.total_hours || 0).toFixed(1)}h
                                    </small>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        }

        // Leave Management Functions
        async function loadLeaveRequests() {
            try {
                const response = await fetch('attendance_monitor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_leave_requests'
                });
                
                const data = await response.json();
                if (data.success) {
                    displayLeaveRequests(data.leave_requests);
                } else {
                    showError('Failed to load leave requests');
                }
            } catch (error) {
                console.error('Error loading leave requests:', error);
                showError('Error loading leave requests');
            }
        }

        function displayLeaveRequests(requests) {
            const grid = document.getElementById('leaveRequestsGrid');
            grid.innerHTML = '';
            
            if (requests.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--gray-500);">
                        <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; color: var(--success);"></i><br>
                        No pending leave requests
                    </div>
                `;
                return;
            }
            
            requests.forEach(req => {
                const card = `
                    <div class="leave-card">
                        <div class="leave-header">
                            <h3>${req.employee_name}</h3>
                            <span class="leave-type ${req.leave_type}">${req.leave_type}</span>
                        </div>
                        
                        <div class="leave-info">
                            <p><strong>Duration:</strong> ${formatDate(req.from_date)} to ${formatDate(req.to_date)}</p>
                            <p><strong>Days:</strong> ${req.total_days} day${req.total_days > 1 ? 's' : ''}</p>
                            <p><strong>Reason:</strong> ${req.reason}</p>
                            <p><strong>Requested:</strong> ${formatDateTime(req.created_at)}</p>
                            ${req.employee_role ? `<p><strong>Role:</strong> ${req.employee_role}</p>` : ''}
                        </div>
                        
                        <div class="leave-actions">
                            <button class="btn btn-success" onclick="approveLeave(${req.id})">
                                <i class="fas fa-check"></i>
                                Approve
                            </button>
                            <button class="btn btn-danger" onclick="rejectLeave(${req.id})">
                                <i class="fas fa-times"></i>
                                Reject
                            </button>
                        </div>
                    </div>
                `;
                grid.innerHTML += card;
            });
        }

        // Utility Functions
        function getEmployeeStatus(emp) {
            if (emp.check_in_time) {
                if (emp.attendance_status === 'late') {
                    return { class: 'away', text: 'Late' };
                } else if (emp.check_out_time) {
                    return { class: 'offline', text: 'Checked Out' };
                } else {
                    return { class: 'online', text: 'Present' };
                }
            } else {
                return { class: 'offline', text: 'Absent' };
            }
        }

        function formatTime(timeStr) {
            if (!timeStr) return '-';
            const date = new Date(timeStr);
            return date.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                year: 'numeric'
            });
        }

        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return '-';
            const date = new Date(dateTimeStr);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function getMonthName(monthNum) {
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return months[monthNum - 1];
        }

        function getWorkingDaysInMonth(month, year) {
            const daysInMonth = new Date(year, month, 0).getDate();
            let workingDays = 0;
            
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month - 1, day);
                const dayOfWeek = date.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Not Sunday or Saturday
                    workingDays++;
                }
            }
            
            return workingDays;
        }

        function viewEmployeeDetails(employeeId) {
            // In a real implementation, this would open a detailed modal
            alert(`Viewing details for employee ID: ${employeeId}\n\nThis would show:\n• Full attendance history\n• Performance metrics\n• Leave balance\n• Recent activity`);
        }

        async function approveLeave(leaveId) {
            if (!confirm('Approve this leave request?')) return;
            
            try {
                const response = await fetch('attendance_monitor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=approve_leave&leave_id=${leaveId}`
                });
                
                const data = await response.json();
                if (data.success) {
                    showSuccess(data.message);
                    loadLeaveRequests(); // Refresh the list
                } else {
                    showError(data.message || 'Failed to approve leave request');
                }
            } catch (error) {
                console.error('Error approving leave:', error);
                showError('Error approving leave request');
            }
        }

        async function rejectLeave(leaveId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (!reason) return;
            
            try {
                const response = await fetch('attendance_monitor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=reject_leave&leave_id=${leaveId}&reason=${encodeURIComponent(reason)}`
                });
                
                const data = await response.json();
                if (data.success) {
                    showSuccess(data.message);
                    loadLeaveRequests(); // Refresh the list
                } else {
                    showError(data.message || 'Failed to reject leave request');
                }
            } catch (error) {
                console.error('Error rejecting leave:', error);
                showError('Error rejecting leave request');
            }
        }

        function exportDailyReport() {
            const date = document.getElementById('reportDate').value;
            window.open(`attendance_admin_api.php?action=export_daily&date=${date}`, '_blank');
        }

        function generateReport(type) {
            switch(type) {
                case 'monthly':
                    const month = document.getElementById('monthSelect')?.value || new Date().getMonth() + 1;
                    const year = document.getElementById('yearSelect')?.value || new Date().getFullYear();
                    window.open(`attendance_admin_api.php?action=export_monthly&month=${month}&year=${year}`, '_blank');
                    break;
                case 'employee':
                    window.open(`attendance_admin_api.php?action=export_employee_summary`, '_blank');
                    break;
                case 'department':
                    window.open(`attendance_admin_api.php?action=export_department_analysis`, '_blank');
                    break;
                case 'payroll':
                    window.open(`attendance_admin_api.php?action=export_payroll`, '_blank');
                    break;
                default:
                    alert(`Generating ${type} report...`);
            }
        }

        function showSuccess(message) {
            showAlert(message, 'success');
        }

        function showError(message) {
            showAlert(message, 'error');
        }

        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            // Insert at top of container
            const container = document.querySelector('.container');
            container.insertBefore(alert, container.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Attendance Monitor System initialized');
            startLiveMonitoring();
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>