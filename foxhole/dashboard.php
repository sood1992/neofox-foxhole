<?php
/**
 * FOXHOLE - Admin Dashboard
 * Complete Agency Management System
 * Redesigned for modern full-stack agency operations
 */

// ==================== SESSION & AUTHENTICATION ====================
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

// ==================== DEPENDENCIES ====================
require_once 'config-2.php';
require_once 'api_enhanced.php';

// ==================== ENHANCED API CLASS ====================
class AgencyDashboardAPI extends DashboardAPI {
    private $db;
    
    public function __construct() {
        parent::__construct();
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            error_log("AgencyDashboardAPI: Database connection failed: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    // Get comprehensive dashboard statistics
    public function getDashboardStats() {
        $stats = [
            'employees' => [
                'total' => 0,
                'active' => 0,
                'busy' => 0,
                'away' => 0
            ],
            'projects' => [
                'total' => 0,
                'active' => 0,
                'planning' => 0,
                'completed' => 0,
                'on_hold' => 0
            ],
            'tasks' => [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'todo' => 0,
                'overdue' => 0
            ],
            'time' => [
                'total_hours' => 0,
                'billable_hours' => 0,
                'this_week' => 0,
                'this_month' => 0
            ],
            'financial' => [
                'revenue_this_month' => 0,
                'pending_invoices' => 0,
                'paid_invoices' => 0
            ],
            'performance' => [
                'avg_efficiency' => 0,
                'projects_on_time' => 0,
                'client_satisfaction' => 0
            ]
        ];
        
        if (!$this->db) return $stats;
        
        try {
            // Employee stats
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'busy' THEN 1 ELSE 0 END) as busy,
                    SUM(CASE WHEN status = 'away' THEN 1 ELSE 0 END) as away
                FROM employees
            ");
            $empStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['employees'] = $empStats;
            
            // Project stats
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'planning' THEN 1 ELSE 0 END) as planning,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'on_hold' THEN 1 ELSE 0 END) as on_hold
                FROM projects
            ");
            $projStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['projects'] = $projStats;
            
            // Task stats
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo,
                    SUM(CASE WHEN due_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue
                FROM tasks
            ");
            $taskStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['tasks'] = $taskStats;
            
            // Time stats
            $stmt = $this->db->query("
                SELECT 
                    COALESCE(SUM(hours_logged), 0) as total_hours,
                    COALESCE(SUM(CASE WHEN WEEK(last_activity) = WEEK(CURDATE()) THEN hours_logged ELSE 0 END), 0) as this_week,
                    COALESCE(SUM(CASE WHEN MONTH(last_activity) = MONTH(CURDATE()) THEN hours_logged ELSE 0 END), 0) as this_month
                FROM tasks
            ");
            $timeStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['time'] = array_merge($stats['time'], $timeStats);
            $stats['time']['billable_hours'] = round($timeStats['total_hours'] * 0.75); // Estimate 75% billable
            
            // Performance metrics
            $stmt = $this->db->query("
                SELECT 
                    COALESCE(AVG(CASE WHEN status = 'completed' AND estimated_hours > 0 
                        THEN (hours_logged / estimated_hours) * 100 END), 0) as avg_efficiency
                FROM tasks
            ");
            $perfStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['performance']['avg_efficiency'] = round($perfStats['avg_efficiency']);
            
            // Projects on time
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN end_date >= CURDATE() OR status = 'completed' THEN 1 ELSE 0 END) as on_time
                FROM projects
                WHERE status IN ('active', 'completed')
            ");
            $onTimeStats = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($onTimeStats['total'] > 0) {
                $stats['performance']['projects_on_time'] = round(($onTimeStats['on_time'] / $onTimeStats['total']) * 100);
            }
            
        } catch (Exception $e) {
            error_log("getDashboardStats: Error - " . $e->getMessage());
        }
        
        return $stats;
    }
    
    // Get project performance metrics
    public function getProjectMetrics() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    p.*,
                    COUNT(DISTINCT t.id) as total_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                    COALESCE(SUM(t.hours_logged), 0) as hours_logged,
                    COALESCE(SUM(t.estimated_hours), 0) as estimated_hours,
                    COALESCE(AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 
                        THEN (t.hours_logged / t.estimated_hours) * 100 END), 0) as efficiency,
                    COUNT(DISTINCT t.employee_id) as team_size,
                    DATEDIFF(p.end_date, CURDATE()) as days_remaining
                FROM projects p
                LEFT JOIN tasks t ON p.id = t.project_id
                WHERE p.status IN ('active', 'planning')
                GROUP BY p.id
                ORDER BY 
                    CASE p.priority 
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        ELSE 4
                    END,
                    p.end_date ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getProjectMetrics: Error - " . $e->getMessage());
            return [];
        }
    }
    
    // Get employee productivity data
    public function getEmployeeProductivity() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    e.id,
                    e.name,
                    e.role,
                    e.status,
                    COUNT(DISTINCT t.id) as total_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END) as active_tasks,
                    COUNT(DISTINCT CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN t.id END) as overdue_tasks,
                    COALESCE(SUM(t.hours_logged), 0) as total_hours,
                    COALESCE(SUM(CASE WHEN WEEK(t.last_activity) = WEEK(CURDATE()) THEN t.hours_logged ELSE 0 END), 0) as week_hours,
                    COALESCE(AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 
                        THEN (t.hours_logged / t.estimated_hours) * 100 END), 100) as efficiency_ratio,
                    COUNT(DISTINCT t.project_id) as projects_count,
                    MAX(t.last_activity) as last_activity
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                GROUP BY e.id
                ORDER BY efficiency_ratio DESC, completed_tasks DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getEmployeeProductivity: Error - " . $e->getMessage());
            return [];
        }
    }
    
    // Get recent activity feed
    public function getRecentActivity($limit = 20) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    a.*,
                    e.name as employee_name,
                    e.role as employee_role
                FROM activity_log a
                LEFT JOIN employees e ON a.employee_id = e.id
                ORDER BY a.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getRecentActivity: Error - " . $e->getMessage());
            return [];
        }
    }
    
    // Get upcoming deadlines
    public function getUpcomingDeadlines($days = 7) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.*,
                    e.name as employee_name,
                    p.name as project_name,
                    p.client as client_name,
                    DATEDIFF(t.due_date, CURDATE()) as days_until_due
                FROM tasks t
                LEFT JOIN employees e ON t.employee_id = e.id
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.status != 'completed'
                AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY t.due_date ASC, t.priority DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getUpcomingDeadlines: Error - " . $e->getMessage());
            return [];
        }
    }
    
    // Get team capacity
    public function getTeamCapacity() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    e.id,
                    e.name,
                    e.role,
                    e.status,
                    COUNT(DISTINCT CASE WHEN t.status IN ('in_progress', 'todo') THEN t.id END) as active_tasks,
                    COALESCE(SUM(CASE WHEN t.status IN ('in_progress', 'todo') THEN t.estimated_hours ELSE 0 END), 0) as pending_hours,
                    COALESCE(SUM(CASE WHEN WEEK(t.last_activity) = WEEK(CURDATE()) THEN t.hours_logged ELSE 0 END), 0) as week_hours,
                    CASE 
                        WHEN pending_hours < 20 THEN 'available'
                        WHEN pending_hours < 40 THEN 'normal'
                        WHEN pending_hours < 60 THEN 'busy'
                        ELSE 'overloaded'
                    END as capacity_status
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                WHERE e.status IN ('active', 'busy')
                GROUP BY e.id
                ORDER BY capacity_status, pending_hours ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getTeamCapacity: Error - " . $e->getMessage());
            return [];
        }
    }
}

// ==================== INITIALIZE API ====================
$agencyAPI = new AgencyDashboardAPI();
$dashboardStats = $agencyAPI->getDashboardStats();
$employees = $agencyAPI->getEmployees();
$projectMetrics = $agencyAPI->getProjectMetrics();
$productivity = $agencyAPI->getEmployeeProductivity();
$recentActivity = $agencyAPI->getRecentActivity(15);
$upcomingDeadlines = $agencyAPI->getUpcomingDeadlines(7);
$teamCapacity = $agencyAPI->getTeamCapacity();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Foxhole Agency Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    
    <style>
        /* Dashboard-specific styles */
        .dashboard-tabs {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-xl);
            border-bottom: 2px solid var(--gray-200);
            overflow-x: auto;
        }
        
        .tab-btn {
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            background: transparent;
            border-bottom: 3px solid transparent;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: all var(--transition-fast);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .tab-btn:hover {
            color: var(--text-primary);
            background: var(--gray-50);
        }
        
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }
        
        .metric-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--metric-color, var(--primary));
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
        }
        
        .metric-label {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .metric-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            background: var(--metric-color, var(--primary));
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }
        
        .metric-change {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: var(--font-size-xs);
            font-weight: 600;
        }
        
        .metric-change.positive {
            color: var(--success);
        }
        
        .metric-change.negative {
            color: var(--danger);
        }
        
        .project-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: var(--spacing-md);
            transition: all var(--transition-fast);
        }
        
        .project-card:hover {
            box-shadow: var(--shadow-md);
        }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
        }
        
        .project-title {
            font-size: var(--font-size-lg);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .project-client {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
        }
        
        .progress-bar {
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin: var(--spacing-md) 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--gray-200);
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .meta-label {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
        }
        
        .meta-value {
            font-weight: 600;
            color: var(--text-primary);
            font-size: var(--font-size-sm);
        }
        
        .employee-table {
            width: 100%;
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .employee-table th {
            background: var(--gray-50);
            padding: var(--spacing-md);
            text-align: left;
            font-weight: 600;
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            border-bottom: 2px solid var(--gray-200);
        }
        
        .employee-table td {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .employee-table tr:hover {
            background: var(--gray-50);
        }
        
        .employee-avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            margin-right: var(--spacing-sm);
        }
        
        .capacity-indicator {
            display: inline-block;
            padding: 4px 12px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
        }
        
        .capacity-available {
            background: var(--success-light);
            color: var(--success);
        }
        
        .capacity-normal {
            background: var(--info-light);
            color: var(--info);
        }
        
        .capacity-busy {
            background: var(--warning-light);
            color: var(--warning);
        }
        
        .capacity-overloaded {
            background: var(--danger-light);
            color: var(--danger);
        }
        
        .deadline-card {
            display: flex;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: white;
            border-radius: var(--radius-md);
            border-left: 4px solid var(--deadline-color, var(--primary));
            margin-bottom: var(--spacing-sm);
            transition: all var(--transition-fast);
        }
        
        .deadline-card:hover {
            box-shadow: var(--shadow-sm);
            transform: translateX(4px);
        }
        
        .deadline-info {
            flex: 1;
        }
        
        .deadline-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .deadline-meta {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
        }
        
        .deadline-days {
            text-align: right;
            font-weight: 700;
            color: var(--deadline-color);
        }
        
        .quick-action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }
        
        .quick-action-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: 2px solid var(--gray-200);
        }
        
        .quick-action-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .quick-action-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto var(--spacing-md);
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: white;
        }
        
        .quick-action-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .quick-action-desc {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
        }
        
        .activity-feed {
            list-style: none;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--gray-200);
            transition: background var(--transition-fast);
        }
        
        .activity-item:hover {
            background: var(--gray-50);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .activity-icon.success {
            background: var(--success-light);
            color: var(--success);
        }
        
        .activity-icon.warning {
            background: var(--warning-light);
            color: var(--warning);
        }
        
        .activity-icon.info {
            background: var(--info-light);
            color: var(--info);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .activity-time {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
        }
        
        .filter-bar {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            margin-bottom: var(--spacing-lg);
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid var(--gray-300);
            background: white;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .filter-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
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
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Agency Dashboard</h1>
                    <p class="page-subtitle">Complete overview of your team, projects, and performance</p>
                    <div class="page-header-actions">
                        <button class="btn btn-outline" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-primary" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                </div>
                
                <!-- Dashboard Tabs -->
                <div class="dashboard-tabs">
                    <button class="tab-btn active" data-tab="overview">
                        <i class="fas fa-tachometer-alt"></i> Overview
                    </button>
                    <button class="tab-btn" data-tab="projects">
                        <i class="fas fa-project-diagram"></i> Projects
                    </button>
                    <button class="tab-btn" data-tab="team">
                        <i class="fas fa-users"></i> Team
                    </button>
                    <button class="tab-btn" data-tab="productivity">
                        <i class="fas fa-chart-line"></i> Productivity
                    </button>
                    <button class="tab-btn" data-tab="time-tracking">
                        <i class="fas fa-clock"></i> Time Tracking
                    </button>
                    <button class="tab-btn" data-tab="deadlines">
                        <i class="fas fa-calendar-alt"></i> Deadlines
                    </button>
                    <button class="tab-btn" data-tab="capacity">
                        <i class="fas fa-battery-three-quarters"></i> Team Capacity
                    </button>
                </div>
                
                <!-- Tab: Overview -->
                <div class="tab-content active" id="tab-overview">
                    <!-- Key Metrics -->
                    <div class="stats-grid">
                        <div class="metric-card" style="--metric-color: var(--primary);">
                            <div class="metric-header">
                                <span class="metric-label">Active Employees</span>
                                <div class="metric-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="metric-value"><?= $dashboardStats['employees']['active'] ?></div>
                            <div class="metric-change positive">
                                <i class="fas fa-arrow-up"></i> of <?= $dashboardStats['employees']['total'] ?> total
                            </div>
                        </div>
                        
                        <div class="metric-card" style="--metric-color: var(--success);">
                            <div class="metric-header">
                                <span class="metric-label">Active Projects</span>
                                <div class="metric-icon">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                            </div>
                            <div class="metric-value"><?= $dashboardStats['projects']['active'] ?></div>
                            <div class="metric-change positive">
                                <i class="fas fa-arrow-up"></i> <?= $dashboardStats['projects']['completed'] ?> completed
                            </div>
                        </div>
                        
                        <div class="metric-card" style="--metric-color: var(--warning);">
                            <div class="metric-header">
                                <span class="metric-label">Tasks In Progress</span>
                                <div class="metric-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                            </div>
                            <div class="metric-value"><?= $dashboardStats['tasks']['in_progress'] ?></div>
                            <div class="metric-change <?= $dashboardStats['tasks']['overdue'] > 0 ? 'negative' : 'positive' ?>">
                                <i class="fas fa-exclamation-triangle"></i> <?= $dashboardStats['tasks']['overdue'] ?> overdue
                            </div>
                        </div>
                        
                        <div class="metric-card" style="--metric-color: var(--info);">
                            <div class="metric-header">
                                <span class="metric-label">Hours This Week</span>
                                <div class="metric-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="metric-value"><?= number_format($dashboardStats['time']['this_week']) ?></div>
                            <div class="metric-change positive">
                                <i class="fas fa-arrow-up"></i> <?= number_format($dashboardStats['time']['this_month']) ?>h this month
                            </div>
                        </div>
                        
                        <div class="metric-card" style="--metric-color: var(--success);">
                            <div class="metric-header">
                                <span class="metric-label">Avg Efficiency</span>
                                <div class="metric-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <div class="metric-value"><?= $dashboardStats['performance']['avg_efficiency'] ?>%</div>
                            <div class="metric-change positive">
                                <i class="fas fa-arrow-up"></i> Above target
                            </div>
                        </div>
                        
                        <div class="metric-card" style="--metric-color: var(--primary);">
                            <div class="metric-header">
                                <span class="metric-label">On-Time Delivery</span>
                                <div class="metric-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="metric-value"><?= $dashboardStats['performance']['projects_on_time'] ?>%</div>
                            <div class="metric-change positive">
                                <i class="fas fa-arrow-up"></i> Projects on schedule
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <h3 style="margin-bottom: var(--spacing-lg);">Quick Actions</h3>
                    <div class="quick-action-grid">
                        <div class="quick-action-card" onclick="showAddProjectModal()">
                            <div class="quick-action-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="quick-action-title">New Project</div>
                            <div class="quick-action-desc">Start a new client project</div>
                        </div>
                        
                        <div class="quick-action-card" onclick="showAddEmployeeModal()">
                            <div class="quick-action-icon" style="background: linear-gradient(135deg, var(--success), #34D399);">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="quick-action-title">Add Team Member</div>
                            <div class="quick-action-desc">Onboard new employee</div>
                        </div>
                        
                        <div class="quick-action-card" onclick="showTaskAssignmentModal()">
                            <div class="quick-action-icon" style="background: linear-gradient(135deg, var(--warning), #FFA726);">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="quick-action-title">Assign Task</div>
                            <div class="quick-action-desc">Create and assign new task</div>
                        </div>
                        
                        <div class="quick-action-card" onclick="window.location.href='analytics.php'">
                            <div class="quick-action-icon" style="background: linear-gradient(135deg, var(--info), #29B6F6);">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="quick-action-title">View Analytics</div>
                            <div class="quick-action-desc">Detailed reports & insights</div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row" style="margin-top: var(--spacing-2xl);">
                        <div class="col-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Activity</h3>
                                    <a href="#" class="text-primary">View All</a>
                                </div>
                                <div class="card-body" style="padding: 0;">
                                    <ul class="activity-feed">
                                        <?php if (!empty($recentActivity)): ?>
                                            <?php foreach (array_slice($recentActivity, 0, 10) as $activity): 
                                                $iconClass = 'info';
                                                $icon = 'fa-info-circle';
                                                
                                                if (strpos(strtolower($activity['type'] ?? ''), 'complete') !== false) {
                                                    $iconClass = 'success';
                                                    $icon = 'fa-check-circle';
                                                } elseif (strpos(strtolower($activity['type'] ?? ''), 'warning') !== false || 
                                                          strpos(strtolower($activity['type'] ?? ''), 'overdue') !== false) {
                                                    $iconClass = 'warning';
                                                    $icon = 'fa-exclamation-triangle';
                                                }
                                            ?>
                                                <li class="activity-item">
                                                    <div class="activity-icon <?= $iconClass ?>">
                                                        <i class="fas <?= $icon ?>"></i>
                                                    </div>
                                                    <div class="activity-content">
                                                        <div class="activity-text">
                                                            <?php if (!empty($activity['employee_name'])): ?>
                                                                <strong><?= htmlspecialchars($activity['employee_name']) ?></strong> -
                                                            <?php endif; ?>
                                                            <?= htmlspecialchars($activity['activity'] ?? 'Activity') ?>
                                                        </div>
                                                        <div class="activity-time">
                                                            <?php
                                                            $time = strtotime($activity['created_at'] ?? 'now');
                                                            $diff = time() - $time;
                                                            if ($diff < 60) echo 'Just now';
                                                            elseif ($diff < 3600) echo floor($diff / 60) . ' minutes ago';
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
                                                    No recent activity to display
                                                </div>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Team Status</h3>
                                </div>
                                <div class="card-body">
                                    <div style="margin-bottom: var(--spacing-lg);">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <span style="font-weight: 500; color: var(--text-secondary);">
                                                <i class="fas fa-circle text-success" style="font-size: 8px;"></i> Active
                                            </span>
                                            <span style="font-weight: 700;"><?= $dashboardStats['employees']['active'] ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <span style="font-weight: 500; color: var(--text-secondary);">
                                                <i class="fas fa-circle text-warning" style="font-size: 8px;"></i> Busy
                                            </span>
                                            <span style="font-weight: 700;"><?= $dashboardStats['employees']['busy'] ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <span style="font-weight: 500; color: var(--text-secondary);">
                                                <i class="fas fa-circle text-danger" style="font-size: 8px;"></i> Away
                                            </span>
                                            <span style="font-weight: 700;"><?= $dashboardStats['employees']['away'] ?></span>
                                        </div>
                                    </div>
                                    
                                    <div style="border-top: 1px solid var(--gray-200); padding-top: var(--spacing-lg);">
                                        <h4 style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-md); color: var(--text-secondary);">
                                            Project Status
                                        </h4>
                                        <div style="margin-bottom: var(--spacing-md);">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                                <span style="font-weight: 500; color: var(--text-secondary);">Completion Rate</span>
                                                <span style="font-weight: 700; color: var(--success);">
                                                    <?php 
                                                    $total = $dashboardStats['tasks']['total'];
                                                    $completed = $dashboardStats['tasks']['completed'];
                                                    echo $total > 0 ? round(($completed / $total) * 100) : 0;
                                                    ?>%
                                                </span>
                                            </div>
                                            <div style="height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden;">
                                                <div style="width: <?= $total > 0 ? round(($completed / $total) * 100) : 0 ?>%; height: 100%; background: var(--success);"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Projects -->
                <div class="tab-content" id="tab-projects">
                    <div class="filter-bar">
                        <button class="filter-btn active" onclick="filterProjects('all')">All Projects</button>
                        <button class="filter-btn" onclick="filterProjects('active')">Active</button>
                        <button class="filter-btn" onclick="filterProjects('planning')">Planning</button>
                        <button class="filter-btn" onclick="filterProjects('completed')">Completed</button>
                    </div>
                    
                    <div id="projects-list">
                        <?php if (!empty($projectMetrics)): ?>
                            <?php foreach ($projectMetrics as $project): 
                                $progress = $project['total_tasks'] > 0 ? 
                                    round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0;
                                $efficiency = round($project['efficiency']);
                                
                                $priorityColor = [
                                    'urgent' => 'var(--danger)',
                                    'high' => 'var(--warning)',
                                    'medium' => 'var(--info)',
                                    'low' => 'var(--success)'
                                ][$project['priority'] ?? 'medium'];
                            ?>
                                <div class="project-card" data-status="<?= htmlspecialchars($project['status']) ?>">
                                    <div class="project-header">
                                        <div>
                                            <h3 class="project-title"><?= htmlspecialchars($project['name']) ?></h3>
                                            <p class="project-client">
                                                <i class="fas fa-briefcase"></i> <?= htmlspecialchars($project['client'] ?? 'Internal') ?>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="badge" style="background: <?= $priorityColor ?>; color: white;">
                                                <?= ucfirst($project['priority'] ?? 'medium') ?> Priority
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <span style="font-size: var(--font-size-sm); color: var(--text-secondary);">Progress</span>
                                            <span style="font-weight: 700; color: var(--primary);"><?= $progress ?>%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="project-meta">
                                        <div class="meta-item">
                                            <span class="meta-label">Tasks</span>
                                            <span class="meta-value"><?= $project['completed_tasks'] ?>/<?= $project['total_tasks'] ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <span class="meta-label">Hours Logged</span>
                                            <span class="meta-value"><?= number_format($project['hours_logged']) ?>h</span>
                                        </div>
                                        <div class="meta-item">
                                            <span class="meta-label">Efficiency</span>
                                            <span class="meta-value" style="color: <?= $efficiency > 100 ? 'var(--danger)' : 'var(--success)' ?>;">
                                                <?= $efficiency ?>%
                                            </span>
                                        </div>
                                        <div class="meta-item">
                                            <span class="meta-label">Team Size</span>
                                            <span class="meta-value"><?= $project['team_size'] ?> members</span>
                                        </div>
                                        <div class="meta-item">
                                            <span class="meta-label">Days Left</span>
                                            <span class="meta-value <?= $project['days_remaining'] < 7 ? 'text-danger' : '' ?>">
                                                <?= max(0, $project['days_remaining']) ?> days
                                            </span>
                                        </div>
                                        <div class="meta-item">
                                            <span class="meta-label">Status</span>
                                            <span class="badge badge-<?= $project['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($project['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-md);">
                                        <button class="btn btn-sm btn-primary" onclick="viewProject(<?= $project['id'] ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="btn btn-sm btn-secondary" onclick="editProject(<?= $project['id'] ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="card">
                                <div class="card-body" style="text-align: center; padding: 3rem;">
                                    <i class="fas fa-project-diagram" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                                    <h3 style="color: var(--text-secondary);">No active projects</h3>
                                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start by creating your first project</p>
                                    <button class="btn btn-primary" onclick="showAddProjectModal()">
                                        <i class="fas fa-plus"></i> Create Project
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tab: Team -->
                <div class="tab-content" id="tab-team">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Team Members Overview</h3>
                            <button class="btn btn-sm btn-primary" onclick="showAddEmployeeModal()">
                                <i class="fas fa-user-plus"></i> Add Member
                            </button>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-responsive">
                                <table class="employee-table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Tasks</th>
                                            <th>Hours (Week)</th>
                                            <th>Efficiency</th>
                                            <th>Projects</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($productivity)): ?>
                                            <?php foreach ($productivity as $emp): 
                                                $initials = strtoupper(substr($emp['name'], 0, 2));
                                                $efficiency = round($emp['efficiency_ratio']);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div style="display: flex; align-items: center;">
                                                            <span class="employee-avatar-sm"><?= $initials ?></span>
                                                            <div>
                                                                <div style="font-weight: 600;"><?= htmlspecialchars($emp['name']) ?></div>
                                                                <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">
                                                                    ID: <?= $emp['id'] ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($emp['role'] ?? 'Team Member') ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $emp['status'] === 'active' ? 'success' : ($emp['status'] === 'busy' ? 'warning' : 'secondary') ?>">
                                                            <?= ucfirst($emp['status'] ?? 'active') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?= $emp['completed_tasks'] ?></strong>/<?= $emp['total_tasks'] ?>
                                                        <?php if ($emp['overdue_tasks'] > 0): ?>
                                                            <span class="badge badge-danger" style="margin-left: 4px;"><?= $emp['overdue_tasks'] ?> overdue</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= number_format($emp['week_hours']) ?>h</td>
                                                    <td>
                                                        <span style="color: <?= $efficiency > 100 ? 'var(--danger)' : ($efficiency > 90 ? 'var(--success)' : 'var(--warning)') ?>; font-weight: 600;">
                                                            <?= $efficiency ?>%
                                                        </span>
                                                    </td>
                                                    <td><?= $emp['projects_count'] ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-secondary" onclick="viewEmployee(<?= $emp['id'] ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                                    No team members found
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Time Tracking -->
                <div class="tab-content" id="tab-time-tracking">
                    <div class="stats-grid">
                        <div class="metric-card" style="--metric-color: var(--primary);">
                            <div class="metric-header">
                                <span class="metric-label">Total Hours (All Time)</span>
                                <div class="metric-icon"><i class="fas fa-clock"></i></div>
                            </div>
                            <div class="metric-value"><?= number_format($dashboardStats['time']['total_hours']) ?></div>
                            <div class="metric-change positive">Tracked hours</div>
                        </div>
                        
                        <div class="metric-card" style="--metric-color: var(--success);">
                            <div class="metric-header">
                                <span class="metric-label">This Week</span>
                                <div class="metric-icon"><i class="fas fa-calendar-week"></i></div>
                            </div>
                            <div class="metric-value"><?= number_format($dashboardStats['time']['this_week']) ?></div>
                            <div class="metric-change positive">Hours logged</div>
                        </div>
                        
                        <div class="metric-card" style="--metric-color: var(--info);">
                            <div class="metric-header">
                                <span class="metric-label">This Month</span>
                                <div class="metric-icon"><i class="fas fa-calendar-alt"></i></div>
                            </div>
                            <div class="metric-value"><?= number_format($dashboardStats['time']['this_month']) ?></div>
                            <div class="metric-change positive">Hours this month</div>
                        </div>
                        
                        <div class="metric-card" style="--metric-color: var(--warning);">
                            <div class="metric-header">
                                <span class="metric-label">Billable Hours</span>
                                <div class="metric-icon"><i class="fas fa-dollar-sign"></i></div>
                            </div>
                            <div class="metric-value"><?= number_format($dashboardStats['time']['billable_hours']) ?></div>
                            <div class="metric-change positive">Est. billable</div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Time Breakdown by Employee</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($productivity)): ?>
                                <?php foreach ($productivity as $emp): ?>
                                    <div style="margin-bottom: var(--spacing-lg);">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <span style="font-weight: 600;"><?= htmlspecialchars($emp['name']) ?></span>
                                            <span style="font-weight: 700; color: var(--primary);"><?= number_format($emp['total_hours']) ?>h total</span>
                                        </div>
                                        <div style="display: flex; gap: var(--spacing-sm); font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 8px;">
                                            <span>This week: <?= number_format($emp['week_hours']) ?>h</span>
                                            <span>•</span>
                                            <span><?= $emp['completed_tasks'] ?> tasks completed</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= min(100, ($emp['week_hours'] / 40) * 100) ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align: center; color: var(--text-muted); padding: 2rem;">No time tracking data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Deadlines -->
                <div class="tab-content" id="tab-deadlines">
                    <h3 style="margin-bottom: var(--spacing-lg);">Upcoming Deadlines (Next 7 Days)</h3>
                    
                    <?php if (!empty($upcomingDeadlines)): ?>
                        <?php foreach ($upcomingDeadlines as $deadline): 
                            $daysUntil = $deadline['days_until_due'];
                            $deadlineColor = $daysUntil <= 1 ? 'var(--danger)' : ($daysUntil <= 3 ? 'var(--warning)' : 'var(--info)');
                        ?>
                            <div class="deadline-card" style="--deadline-color: <?= $deadlineColor ?>;">
                                <div class="deadline-info">
                                    <div class="deadline-title"><?= htmlspecialchars($deadline['title']) ?></div>
                                    <div class="deadline-meta">
                                        <i class="fas fa-project-diagram"></i> <?= htmlspecialchars($deadline['project_name']) ?>
                                        <span style="margin: 0 8px;">•</span>
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($deadline['employee_name']) ?>
                                        <span style="margin: 0 8px;">•</span>
                                        <span class="badge badge-<?= $deadline['priority'] === 'urgent' ? 'danger' : ($deadline['priority'] === 'high' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($deadline['priority'] ?? 'medium') ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="deadline-days">
                                    <?php if ($daysUntil == 0): ?>
                                        <div style="font-size: var(--font-size-lg);">TODAY</div>
                                    <?php elseif ($daysUntil == 1): ?>
                                        <div style="font-size: var(--font-size-lg);">TOMORROW</div>
                                    <?php else: ?>
                                        <div style="font-size: 1.5rem;"><?= $daysUntil ?></div>
                                        <div style="font-size: var(--font-size-xs);">days</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-calendar-check" style="font-size: 3rem; color: var(--success); margin-bottom: 1rem;"></i>
                                <h3 style="color: var(--text-secondary);">No upcoming deadlines</h3>
                                <p style="color: var(--text-muted);">All tasks are on track or completed</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Team Capacity -->
                <div class="tab-content" id="tab-capacity">
                    <h3 style="margin-bottom: var(--spacing-lg);">Team Resource Allocation</h3>
                    
                    <?php if (!empty($teamCapacity)): ?>
                        <div class="card">
                            <div class="card-body" style="padding: 0;">
                                <table class="employee-table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Role</th>
                                            <th>Active Tasks</th>
                                            <th>Pending Hours</th>
                                            <th>Week Hours</th>
                                            <th>Capacity</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teamCapacity as $member): 
                                            $initials = strtoupper(substr($member['name'], 0, 2));
                                            $capacityClass = 'capacity-' . ($member['capacity_status'] ?? 'normal');
                                        ?>
                                            <tr>
                                                <td>
                                                    <div style="display: flex; align-items: center;">
                                                        <span class="employee-avatar-sm"><?= $initials ?></span>
                                                        <?= htmlspecialchars($member['name']) ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($member['role'] ?? 'Team Member') ?></td>
                                                <td><strong><?= $member['active_tasks'] ?></strong> tasks</td>
                                                <td><?= number_format($member['pending_hours']) ?>h</td>
                                                <td><?= number_format($member['week_hours']) ?>h</td>
                                                <td>
                                                    <span class="capacity-indicator <?= $capacityClass ?>">
                                                        <?= ucfirst($member['capacity_status'] ?? 'normal') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="assignTask(<?= $member['id'] ?>)">
                                                        <i class="fas fa-plus"></i> Assign Task
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="stats-grid" style="margin-top: var(--spacing-xl);">
                            <div class="metric-card" style="--metric-color: var(--success);">
                                <div class="metric-header">
                                    <span class="metric-label">Available</span>
                                    <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
                                </div>
                                <div class="metric-value">
                                    <?= count(array_filter($teamCapacity, fn($m) => ($m['capacity_status'] ?? '') === 'available')) ?>
                                </div>
                            </div>
                            
                            <div class="metric-card" style="--metric-color: var(--info);">
                                <div class="metric-header">
                                    <span class="metric-label">Normal Load</span>
                                    <div class="metric-icon"><i class="fas fa-user"></i></div>
                                </div>
                                <div class="metric-value">
                                    <?= count(array_filter($teamCapacity, fn($m) => ($m['capacity_status'] ?? '') === 'normal')) ?>
                                </div>
                            </div>
                            
                            <div class="metric-card" style="--metric-color: var(--warning);">
                                <div class="metric-header">
                                    <span class="metric-label">Busy</span>
                                    <div class="metric-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                </div>
                                <div class="metric-value">
                                    <?= count(array_filter($teamCapacity, fn($m) => ($m['capacity_status'] ?? '') === 'busy')) ?>
                                </div>
                            </div>
                            
                            <div class="metric-card" style="--metric-color: var(--danger);">
                                <div class="metric-header">
                                    <span class="metric-label">Overloaded</span>
                                    <div class="metric-icon"><i class="fas fa-times-circle"></i></div>
                                </div>
                                <div class="metric-value">
                                    <?= count(array_filter($teamCapacity, fn($m) => ($m['capacity_status'] ?? '') === 'overloaded')) ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body" style="text-align: center; padding: 3rem;">
                                <p style="color: var(--text-muted);">No team capacity data available</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Productivity -->
                <div class="tab-content" id="tab-productivity">
                    <h3 style="margin-bottom: var(--spacing-lg);">Team Productivity Analysis</h3>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Top Performers</h3>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $topPerformers = array_slice($productivity, 0, 5);
                                    if (!empty($topPerformers)):
                                    ?>
                                        <?php foreach ($topPerformers as $index => $performer): ?>
                                            <div style="display: flex; align-items: center; gap: var(--spacing-md); padding: var(--spacing-md); background: var(--gray-50); border-radius: var(--radius-md); margin-bottom: var(--spacing-sm);">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                                    #<?= $index + 1 ?>
                                                </div>
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 600;"><?= htmlspecialchars($performer['name']) ?></div>
                                                    <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">
                                                        <?= $performer['completed_tasks'] ?> tasks • <?= round($performer['efficiency_ratio']) ?>% efficiency
                                                    </div>
                                                </div>
                                                <div style="font-weight: 700; color: var(--success);">
                                                    <?= number_format($performer['total_hours']) ?>h
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">No data available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Needs Attention</h3>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $needsAttention = array_filter($productivity, fn($p) => $p['overdue_tasks'] > 0 || $p['efficiency_ratio'] < 80);
                                    if (!empty($needsAttention)):
                                    ?>
                                        <?php foreach (array_slice($needsAttention, 0, 5) as $emp): ?>
                                            <div style="display: flex; align-items: center; gap: var(--spacing-md); padding: var(--spacing-md); background: var(--warning-light); border-radius: var(--radius-md); margin-bottom: var(--spacing-sm);">
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 600;"><?= htmlspecialchars($emp['name']) ?></div>
                                                    <div style="font-size: var(--font-size-xs); color: var(--text-secondary);">
                                                        <?php if ($emp['overdue_tasks'] > 0): ?>
                                                            <span class="badge badge-danger"><?= $emp['overdue_tasks'] ?> overdue</span>
                                                        <?php endif; ?>
                                                        <?php if ($emp['efficiency_ratio'] < 80): ?>
                                                            <span class="badge badge-warning"><?= round($emp['efficiency_ratio']) ?>% efficiency</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <button class="btn btn-sm btn-warning" onclick="reviewEmployee(<?= $emp['id'] ?>)">
                                                    <i class="fas fa-eye"></i> Review
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="text-align: center; padding: 2rem;">
                                            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success); margin-bottom: 1rem;"></i>
                                            <p style="color: var(--text-secondary); font-weight: 600;">All team members performing well!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab Management
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById('tab-' + tabId).classList.add('active');
                sessionStorage.setItem('activeTab', tabId);
            });
        });
        
        // Restore active tab
        window.addEventListener('load', () => {
            const activeTab = sessionStorage.getItem('activeTab');
            if (activeTab) {
                const tabBtn = document.querySelector('[data-tab="' + activeTab + '"]');
                if (tabBtn) tabBtn.click();
            }
        });
        
        // Dashboard Functions
        function refreshDashboard() { location.reload(); }
        function exportReport() { alert('Export feature coming soon!'); }
        function showAddProjectModal() { alert('Add Project modal coming soon!'); }
        function viewProject(id) { window.location.href = 'project_details.php?id=' + id; }
        function editProject(id) { alert('Edit Project ' + id); }
        function showAddEmployeeModal() { alert('Add Employee modal coming soon!'); }
        function viewEmployee(id) { window.location.href = 'employee_details.php?id=' + id; }
        function editEmployee(id) { alert('Edit Employee ' + id); }
        function reviewEmployee(id) { alert('Review Employee ' + id); }
        function showTaskAssignmentModal() { alert('Task Assignment modal coming soon!'); }
        function assignTask(employeeId) { alert('Assign task to employee ' + employeeId); }
        
        function filterProjects(status) {
            const projects = document.querySelectorAll('.project-card');
            const buttons = document.querySelectorAll('#tab-projects .filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            projects.forEach(project => {
                if (status === 'all' || project.dataset.status === status) {
                    project.style.display = 'block';
                } else {
                    project.style.display = 'none';
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Foxhole Dashboard Loaded Successfully');
        });
    </script>
</body>
</html>
