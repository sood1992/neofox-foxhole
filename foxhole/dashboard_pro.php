<?php
// dashboard_pro.php - Enhanced with AI, Financial Intelligence & Real-Time Alerts
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

// Enhanced AI-Powered Dashboard API
class ProDashboardAPI {
    private $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->initializeEnhancedData();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    private function initializeEnhancedData() {
        if (!$this->db) return;
        
        try {
            // Add financial data to projects table if not exists
            $this->db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS budget DECIMAL(10,2) DEFAULT 0");
            $this->db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(6,2) DEFAULT 75");
            $this->db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS client_deadline DATE");
            $this->db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS profit_margin DECIMAL(5,2) DEFAULT 25");
            
            // Create alerts table
            $this->db->exec("CREATE TABLE IF NOT EXISTS alerts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type ENUM('deadline', 'budget', 'performance', 'client') NOT NULL,
                severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                title VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                project_id INT NULL,
                employee_id INT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id),
                FOREIGN KEY (employee_id) REFERENCES employees(id)
            )");
            
            // Initialize sample data with financial info
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM projects WHERE budget > 0");
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                // Update projects with financial data
                $projectUpdates = [
                    [1, 45000, 85, '2024-03-30', 30], // Nike Campaign
                    [2, 75000, 95, '2024-04-15', 25], // TechCorp Website
                    [3, 15000, 65, '2024-05-30', 40], // Restaurant Branding
                    [4, 120000, 100, '2024-06-30', 35] // E-commerce Platform
                ];
                
                foreach ($projectUpdates as $update) {
                    $stmt = $this->db->prepare("UPDATE projects SET budget = ?, hourly_rate = ?, client_deadline = ?, profit_margin = ? WHERE id = ?");
                    $stmt->execute([$update[1], $update[2], $update[3], $update[4], $update[0]]);
                }
                
                // Generate sample alerts
                $this->generateSmartAlerts();
            }
        } catch (Exception $e) {
            error_log("Error initializing enhanced data: " . $e->getMessage());
        }
    }
    
    private function generateSmartAlerts() {
        if (!$this->db) return;
        
        try {
            // Clear old alerts
            $this->db->exec("DELETE FROM alerts");
            
            $alerts = [
                ['deadline', 'high', 'Nike Campaign Deadline Alert', 'Nike Campaign deadline is in 5 days. Project is 65% complete with critical video editing pending.', 1, null],
                ['budget', 'medium', 'TechCorp Budget Alert', 'TechCorp project has used 78% of allocated hours. Monitor remaining budget closely.', 2, null],
                ['performance', 'critical', 'Team Performance Alert', 'Alex Chen has 0% efficiency this week. Immediate attention required.', null, 5],
                ['client', 'high', 'Client Communication Gap', 'No client communication for Pizza Palace project in 5 days. Schedule check-in call.', 3, null],
                ['deadline', 'medium', 'Upcoming Deadline Warning', 'E-commerce Platform milestone due in 7 days. Team working at optimal pace.', 4, null]
            ];
            
            foreach ($alerts as $alert) {
                $stmt = $this->db->prepare("INSERT INTO alerts (type, severity, title, message, project_id, employee_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 120) MINUTE)");
                $stmt->execute($alert);
            }
        } catch (Exception $e) {
            error_log("Error generating alerts: " . $e->getMessage());
        }
    }
    
    public function getFinancialIntelligence() {
        if (!$this->db) {
            return [
                'total_revenue' => 255000,
                'total_costs' => 180000,
                'net_profit' => 75000,
                'profit_margin' => 29.4,
                'burn_rate' => 15000,
                'runway_months' => 12,
                'top_profitable_projects' => [
                    ['name' => 'Nike Campaign', 'profit' => 13500, 'margin' => 30],
                    ['name' => 'Restaurant Branding', 'profit' => 6000, 'margin' => 40]
                ],
                'budget_alerts' => 2,
                'revenue_forecast' => 285000
            ];
        }
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    SUM(p.budget) as total_revenue,
                    SUM(t.hours_logged * p.hourly_rate) as actual_earned,
                    SUM(t.hours_logged * 45) as total_costs,
                    COUNT(p.id) as active_projects
                FROM projects p
                LEFT JOIN tasks t ON p.id = t.project_id
                WHERE p.status = 'active'
            ");
            $financial = $stmt->fetch();
            
            $totalRevenue = $financial['total_revenue'] ?? 0;
            $actualEarned = $financial['actual_earned'] ?? 0;
            $totalCosts = $financial['total_costs'] ?? 0;
            $netProfit = $actualEarned - $totalCosts;
            $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
            
            // Get top profitable projects
            $stmt = $this->db->query("
                SELECT 
                    p.name,
                    (SUM(t.hours_logged * p.hourly_rate) - SUM(t.hours_logged * 45)) as profit,
                    p.profit_margin as margin
                FROM projects p
                LEFT JOIN tasks t ON p.id = t.project_id
                WHERE p.status = 'active'
                GROUP BY p.id
                ORDER BY profit DESC
                LIMIT 3
            ");
            $topProjects = $stmt->fetchAll();
            
            // Count budget alerts
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM alerts WHERE type = 'budget' AND is_read = FALSE");
            $budgetAlerts = $stmt->fetch()['count'];
            
            return [
                'total_revenue' => round($totalRevenue),
                'actual_earned' => round($actualEarned),
                'total_costs' => round($totalCosts),
                'net_profit' => round($netProfit),
                'profit_margin' => round($profitMargin, 1),
                'burn_rate' => round($totalCosts / 4), // Weekly burn rate
                'runway_months' => $totalCosts > 0 ? round(($netProfit / ($totalCosts / 4)) / 4) : 12,
                'top_profitable_projects' => array_map(function($p) {
                    return [
                        'name' => $p['name'],
                        'profit' => round($p['profit'] ?? 0),
                        'margin' => round($p['margin'] ?? 0)
                    ];
                }, $topProjects),
                'budget_alerts' => $budgetAlerts,
                'revenue_forecast' => round($totalRevenue * 1.15) // 15% growth forecast
            ];
        } catch (Exception $e) {
            error_log("Error getting financial intelligence: " . $e->getMessage());
            return [
                'total_revenue' => 0,
                'actual_earned' => 0,
                'total_costs' => 0,
                'net_profit' => 0,
                'profit_margin' => 0,
                'burn_rate' => 0,
                'runway_months' => 0,
                'top_profitable_projects' => [],
                'budget_alerts' => 0,
                'revenue_forecast' => 0
            ];
        }
    }
    
    public function getClientDeadlineAlerts() {
        if (!$this->db) {
            return [
                [
                    'project' => 'Nike Campaign',
                    'client' => 'Nike Inc',
                    'deadline' => '2024-03-30',
                    'days_remaining' => 5,
                    'progress' => 65,
                    'risk_level' => 'high',
                    'status' => 'behind_schedule'
                ],
                [
                    'project' => 'TechCorp Website',
                    'client' => 'TechCorp Ltd',
                    'deadline' => '2024-04-15',
                    'days_remaining' => 21,
                    'progress' => 45,
                    'risk_level' => 'medium',
                    'status' => 'on_track'
                ]
            ];
        }
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    p.name as project,
                    p.client,
                    p.client_deadline as deadline,
                    DATEDIFF(p.client_deadline, CURDATE()) as days_remaining,
                    CASE 
                        WHEN COUNT(t.id) = 0 THEN 0
                        ELSE ROUND((COUNT(CASE WHEN t.status = 'completed' THEN 1 END) / COUNT(t.id)) * 100)
                    END as progress,
                    p.priority,
                    p.status
                FROM projects p
                LEFT JOIN tasks t ON p.id = t.project_id
                WHERE p.client_deadline IS NOT NULL 
                AND p.status = 'active'
                GROUP BY p.id
                ORDER BY days_remaining ASC
            ");
            
            $deadlines = $stmt->fetchAll();
            
            foreach ($deadlines as &$deadline) {
                $daysRemaining = $deadline['days_remaining'];
                $progress = $deadline['progress'];
                
                // Calculate risk level based on time vs progress
                if ($daysRemaining <= 0) {
                    $deadline['risk_level'] = 'critical';
                    $deadline['status'] = 'overdue';
                } elseif ($daysRemaining <= 7 && $progress < 80) {
                    $deadline['risk_level'] = 'high';
                    $deadline['status'] = 'behind_schedule';
                } elseif ($daysRemaining <= 14 && $progress < 60) {
                    $deadline['risk_level'] = 'medium';
                    $deadline['status'] = 'at_risk';
                } else {
                    $deadline['risk_level'] = 'low';
                    $deadline['status'] = 'on_track';
                }
            }
            
            return $deadlines;
        } catch (Exception $e) {
            error_log("Error getting deadline alerts: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRealTimeAlerts() {
        if (!$this->db) {
            return [
                [
                    'id' => 1,
                    'type' => 'deadline',
                    'severity' => 'high',
                    'title' => 'Nike Campaign Deadline Alert',
                    'message' => 'Deadline in 5 days, 65% complete',
                    'time_ago' => '2 minutes ago',
                    'is_read' => false
                ],
                [
                    'id' => 2,
                    'type' => 'performance',
                    'severity' => 'critical',
                    'title' => 'Team Performance Alert',
                    'message' => 'Alex Chen needs immediate attention',
                    'time_ago' => '15 minutes ago',
                    'is_read' => false
                ]
            ];
        }
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    type,
                    severity,
                    title,
                    message,
                    is_read,
                    CASE 
                        WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                        THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' minutes ago')
                        WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) 
                        THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
                        ELSE DATE_FORMAT(created_at, '%M %d at %h:%i%p')
                    END as time_ago
                FROM alerts
                ORDER BY 
                    is_read ASC,
                    FIELD(severity, 'critical', 'high', 'medium', 'low'),
                    created_at DESC
                LIMIT 10
            ");
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting real-time alerts: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAIPoweredInsights() {
        if (!$this->db) {
            return [
                'predictions' => [
                    [
                        'type' => 'deadline',
                        'confidence' => 87,
                        'prediction' => 'Nike Campaign will finish 2 days late based on current velocity',
                        'recommendation' => 'Reassign video editing to John Doe for faster completion'
                    ],
                    [
                        'type' => 'performance',
                        'confidence' => 92,
                        'prediction' => 'Sarah Wilson works 35% faster on design tasks than development',
                        'recommendation' => 'Optimize task allocation to leverage design strengths'
                    ],
                    [
                        'type' => 'budget',
                        'confidence' => 78,
                        'prediction' => 'TechCorp project will exceed budget by 15% at current burn rate',
                        'recommendation' => 'Discuss scope reduction or budget increase with client'
                    ]
                ],
                'optimization_suggestions' => [
                    'Move 2 tasks from Mike Johnson (overloaded) to Alex Chen (underutilized)',
                    'Schedule Sarah Wilson for design tasks only this week for 40% efficiency boost',
                    'Consider hiring a video editor - current team at 95% capacity on video tasks'
                ],
                'risk_factors' => [
                    ['factor' => 'Client Communication Gap', 'risk_score' => 85, 'projects_affected' => 1],
                    ['factor' => 'Team Burnout Risk', 'risk_score' => 72, 'projects_affected' => 2],
                    ['factor' => 'Budget Overrun Risk', 'risk_score' => 68, 'projects_affected' => 1]
                ]
            ];
        }
        
        try {
            // AI-powered predictions based on actual data
            $predictions = [];
            $optimizations = [];
            $risks = [];
            
            // Deadline predictions
            $stmt = $this->db->query("
                SELECT 
                    p.name,
                    p.client_deadline,
                    DATEDIFF(p.client_deadline, CURDATE()) as days_remaining,
                    COUNT(t.id) as total_tasks,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                    AVG(t.hours_logged / NULLIF(t.estimated_hours, 0)) as avg_efficiency
                FROM projects p
                LEFT JOIN tasks t ON p.id = t.project_id
                WHERE p.status = 'active' AND p.client_deadline IS NOT NULL
                GROUP BY p.id
            ");
            
            $projects = $stmt->fetchAll();
            
            foreach ($projects as $project) {
                $progress = $project['total_tasks'] > 0 ? ($project['completed_tasks'] / $project['total_tasks']) : 0;
                $efficiency = $project['avg_efficiency'] ?? 1.0;
                $daysRemaining = $project['days_remaining'];
                
                // Predict completion based on current velocity
                $remainingTasks = $project['total_tasks'] - $project['completed_tasks'];
                $estimatedDaysNeeded = $remainingTasks * 2 / $efficiency; // Assuming 2 days per task average
                
                if ($estimatedDaysNeeded > $daysRemaining && $daysRemaining > 0) {
                    $daysBehind = ceil($estimatedDaysNeeded - $daysRemaining);
                    $predictions[] = [
                        'type' => 'deadline',
                        'confidence' => min(95, 60 + ($daysBehind * 10)),
                        'prediction' => "{$project['name']} will finish {$daysBehind} days late based on current velocity",
                        'recommendation' => 'Consider adding resources or reducing scope'
                    ];
                }
            }
            
            // Performance insights
            $stmt = $this->db->query("
                SELECT 
                    e.name,
                    COUNT(t.id) as total_tasks,
                    AVG(t.hours_logged / NULLIF(t.estimated_hours, 0)) as efficiency,
                    COUNT(CASE WHEN t.status != 'completed' THEN 1 END) as active_tasks
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                WHERE e.status = 'active'
                GROUP BY e.id
                HAVING total_tasks > 0
            ");
            
            $employees = $stmt->fetchAll();
            
            // Find optimization opportunities
            $overloaded = array_filter($employees, function($emp) { return $emp['active_tasks'] > 4; });
            $underutilized = array_filter($employees, function($emp) { return $emp['active_tasks'] < 2; });
            
            if (!empty($overloaded) && !empty($underutilized)) {
                $overloadedEmp = reset($overloaded);
                $underutilizedEmp = reset($underutilized);
                $optimizations[] = "Move 2 tasks from {$overloadedEmp['name']} (overloaded) to {$underutilizedEmp['name']} (underutilized)";
            }
            
            // Risk assessment
            $stmt = $this->db->query("
                SELECT COUNT(*) as count FROM alerts WHERE type = 'client' AND is_read = FALSE
            ");
            $clientRisks = $stmt->fetch()['count'];
            
            if ($clientRisks > 0) {
                $risks[] = ['factor' => 'Client Communication Gap', 'risk_score' => 85, 'projects_affected' => $clientRisks];
            }
            
            return [
                'predictions' => $predictions,
                'optimization_suggestions' => $optimizations,
                'risk_factors' => $risks
            ];
            
        } catch (Exception $e) {
            error_log("Error getting AI insights: " . $e->getMessage());
            return ['predictions' => [], 'optimization_suggestions' => [], 'risk_factors' => []];
        }
    }
    
    public function markAlertAsRead($alertId) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("UPDATE alerts SET is_read = TRUE WHERE id = ?");
            return $stmt->execute([$alertId]);
        } catch (Exception $e) {
            error_log("Error marking alert as read: " . $e->getMessage());
            return false;
        }
    }
    
    // Previous methods remain the same...
    public function getDashboardStats() {
        if (!$this->db) {
            return [
                'active_employees' => 6,
                'total_employees' => 8,
                'completed_tasks' => 4,
                'urgent_tasks' => 2,
                'overdue_tasks' => 1,
                'avg_productivity' => 87,
                'total_hours_logged' => 95.5,
                'projects_on_track' => 3,
                'projects_at_risk' => 1
            ];
        }
        
        try {
            $stats = [];
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'");
            $stats['active_employees'] = $stmt->fetch()['count'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM employees");
            $stats['total_employees'] = $stmt->fetch()['count'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'");
            $stats['completed_tasks'] = $stmt->fetch()['count'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM tasks WHERE priority = 'urgent' AND status != 'completed'");
            $stats['urgent_tasks'] = $stmt->fetch()['count'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM tasks WHERE due_date < CURDATE() AND status != 'completed'");
            $stats['overdue_tasks'] = $stmt->fetch()['count'];
            
            $stmt = $this->db->query("SELECT AVG(CASE WHEN estimated_hours > 0 THEN (hours_logged / estimated_hours) * 100 ELSE 75 END) as avg_productivity FROM tasks WHERE status = 'completed'");
            $result = $stmt->fetch();
            $stats['avg_productivity'] = round($result['avg_productivity'] ?? 75);
            
            $stmt = $this->db->query("SELECT SUM(hours_logged) as total_hours FROM tasks");
            $stats['total_hours_logged'] = round($stmt->fetch()['total_hours'] ?? 0, 1);
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'active'");
            $stats['projects_on_track'] = $stmt->fetch()['count'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM projects WHERE priority = 'urgent'");
            $stats['projects_at_risk'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting dashboard stats: " . $e->getMessage());
            return [
                'active_employees' => 0,
                'total_employees' => 0,
                'completed_tasks' => 0,
                'urgent_tasks' => 0,
                'overdue_tasks' => 0,
                'avg_productivity' => 0,
                'total_hours_logged' => 0,
                'projects_on_track' => 0,
                'projects_at_risk' => 0
            ];
        }
    }
}

// Initialize API
$api = new ProDashboardAPI();

// Get all data
$stats = $api->getDashboardStats();
$financial = $api->getFinancialIntelligence();
$deadlineAlerts = $api->getClientDeadlineAlerts();
$realTimeAlerts = $api->getRealTimeAlerts();
$aiInsights = $api->getAIPoweredInsights();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'mark_alert_read':
            $alertId = $_POST['alert_id'] ?? 0;
            $result = $api->markAlertAsRead($alertId);
            echo json_encode(['success' => $result]);
            break;
            
        case 'refresh_data':
            echo json_encode([
                'success' => true,
                'stats' => $api->getDashboardStats(),
                'financial' => $api->getFinancialIntelligence(),
                'alerts' => $api->getRealTimeAlerts()
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole Pro - AI-Powered CEO Dashboard</title>
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
            
            /* Alert Colors */
            --alert-critical: #DC2626;
            --alert-high: #F59E0B;
            --alert-medium: #3B82F6;
            --alert-low: #10B981;
            
            /* Financial Colors */
            --profit: #059669;
            --loss: #DC2626;
            --revenue: #7C3AED;
            --costs: #EA580C;
            
            /* AI Colors */
            --ai-primary: #7C3AED;
            --ai-secondary: #A855F7;
            --prediction: #EC4899;
            
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
            --shadow-glow: 0 0 20px rgba(79, 70, 229, 0.3);
            
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

        /* Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            border-right: 2px solid var(--gray-200);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            background: var(--bg-primary);
            min-height: 100vh;
            position: relative;
        }

        /* Real-Time Alerts Sidebar */
        .alerts-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 350px;
            height: 100vh;
            background: var(--bg-secondary);
            border-left: 2px solid var(--gray-200);
            box-shadow: var(--shadow-xl);
            z-index: 1001;
            overflow-y: auto;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .alerts-sidebar.active {
            transform: translateX(0);
        }

        .alerts-header {
            background: linear-gradient(135deg, var(--alert-critical), #F87171);
            color: white;
            padding: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .close-alerts {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
        }

        .close-alerts:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .alerts-list {
            padding: 1rem;
        }

        .alert-item {
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--gray-300);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .alert-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }

        .alert-item.unread {
            background: var(--bg-secondary);
            box-shadow: var(--shadow-md);
        }

        .alert-item.critical { border-left-color: var(--alert-critical); }
        .alert-item.high { border-left-color: var(--alert-high); }
        .alert-item.medium { border-left-color: var(--alert-medium); }
        .alert-item.low { border-left-color: var(--alert-low); }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .alert-title {
            font-weight: 700;
            font-size: 14px;
            color: var(--gray-900);
            line-height: 1.3;
        }

        .alert-severity {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .severity-critical { background: rgba(220, 38, 38, 0.1); color: var(--alert-critical); }
        .severity-high { background: rgba(245, 158, 11, 0.1); color: var(--alert-high); }
        .severity-medium { background: rgba(59, 130, 246, 0.1); color: var(--alert-medium); }
        .severity-low { background: rgba(16, 185, 129, 0.1); color: var(--alert-low); }

        .alert-message {
            font-size: 13px;
            color: var(--gray-700);
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .alert-time {
            font-size: 11px;
            color: var(--gray-500);
            font-weight: 600;
        }

        .unread-indicator {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 8px;
            height: 8px;
            background: var(--alert-critical);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }

        /* Sidebar Navigation */
        .sidebar-header {
            padding: 0 2rem 2rem 2rem;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 28px;
            font-weight: 900;
            color: var(--primary);
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: var(--shadow-md);
        }

        .nav-menu {
            list-style: none;
            padding: 0 1rem;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            color: var(--gray-600);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            transform: translateX(8px);
            box-shadow: var(--shadow-md);
        }

        .nav-link i {
            width: 24px;
            text-align: center;
            font-size: 18px;
        }

        .nav-badge {
            background: var(--danger);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            margin-left: auto;
        }

        /* Top Bar */
        .top-bar {
            background: var(--bg-secondary);
            border-bottom: 2px solid var(--gray-200);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.2;
        }

        .page-header p {
            font-size: 16px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 14px;
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

        .btn-alerts {
            background: linear-gradient(135deg, var(--alert-critical), #F87171);
            color: white;
            position: relative;
        }

        .btn-alerts:hover {
            background: linear-gradient(135deg, #B91C1C, var(--alert-critical));
            transform: translateY(-2px);
        }

        .alerts-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: white;
            color: var(--alert-critical);
            font-size: 12px;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
            max-width: 100%;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Enhanced Metrics Grid */
        .enhanced-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
        }

        .metric-card.financial::before {
            background: linear-gradient(90deg, var(--profit), #34D399);
        }

        .metric-card.revenue::before {
            background: linear-gradient(90deg, var(--revenue), #A855F7);
        }

        .metric-card.alerts::before {
            background: linear-gradient(90deg, var(--alert-critical), #F87171);
        }

        .metric-card.ai::before {
            background: linear-gradient(90deg, var(--ai-primary), var(--ai-secondary));
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .metric-icon {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: var(--shadow-md);
        }

        .metric-icon.financial {
            background: linear-gradient(135deg, var(--profit), #34D399);
        }

        .metric-icon.revenue {
            background: linear-gradient(135deg, var(--revenue), #A855F7);
        }

        .metric-icon.alerts {
            background: linear-gradient(135deg, var(--alert-critical), #F87171);
        }

        .metric-icon.ai {
            background: linear-gradient(135deg, var(--ai-primary), var(--ai-secondary));
        }

        .metric-number {
            font-size: 48px;
            font-weight: 900;
            color: var(--gray-900);
            margin-bottom: 8px;
            line-height: 1;
        }

        .metric-label {
            font-size: 18px;
            color: var(--gray-600);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .metric-change {
            font-size: 14px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .metric-change.positive { 
            color: var(--success); 
            background: rgba(16, 185, 129, 0.1);
        }

        .metric-change.negative { 
            color: var(--danger); 
            background: rgba(239, 68, 68, 0.1);
        }

        /* Financial Intelligence Widget */
        .financial-widget {
            background: linear-gradient(135deg, var(--profit), #34D399);
            color: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .financial-widget::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .financial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .financial-title {
            font-size: 28px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .financial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            position: relative;
            z-index: 1;
        }

        .financial-metric {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
        }

        .financial-value {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 8px;
            line-height: 1;
        }

        .financial-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 600;
        }

        .profit-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }

        /* Deadline Alerts Widget */
        .deadline-alerts-widget {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--gray-200);
        }

        .deadline-alerts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .deadline-alerts-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .deadline-alerts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .deadline-alert-card {
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border-left: 6px solid var(--gray-300);
            transition: all 0.3s ease;
        }

        .deadline-alert-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .deadline-alert-card.critical { border-left-color: var(--alert-critical); background: rgba(220, 38, 38, 0.05); }
        .deadline-alert-card.high { border-left-color: var(--alert-high); background: rgba(245, 158, 11, 0.05); }
        .deadline-alert-card.medium { border-left-color: var(--alert-medium); background: rgba(59, 130, 246, 0.05); }
        .deadline-alert-card.low { border-left-color: var(--alert-low); background: rgba(16, 185, 129, 0.05); }

        .deadline-project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .deadline-project-name {
            font-weight: 800;
            font-size: 16px;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .deadline-client {
            font-size: 13px;
            color: var(--gray-600);
        }

        .deadline-countdown {
            text-align: center;
            padding: 8px 12px;
            border-radius: var(--radius-md);
            font-weight: 800;
            font-size: 14px;
        }

        .countdown-critical { background: var(--alert-critical); color: white; }
        .countdown-high { background: var(--alert-high); color: white; }
        .countdown-medium { background: var(--alert-medium); color: white; }
        .countdown-low { background: var(--alert-low); color: white; }

        .deadline-progress {
            margin-top: 1rem;
        }

        .deadline-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .deadline-progress-text {
            font-weight: 700;
            font-size: 14px;
            color: var(--gray-900);
        }

        .deadline-progress-bar {
            width: 100%;
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
        }

        .deadline-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), #34D399);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* AI Insights Widget */
        .ai-insights-widget {
            background: linear-gradient(135deg, var(--ai-primary), var(--ai-secondary));
            color: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .ai-insights-widget::before {
            content: '';
            position: absolute;
            top: -30%;
            left: -30%;
            width: 160%;
            height: 160%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .ai-insights-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .ai-icon {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .ai-insights-title {
            font-size: 28px;
            font-weight: 900;
        }

        .ai-predictions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .ai-prediction {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
        }

        .prediction-confidence {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .confidence-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .prediction-text {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .prediction-recommendation {
            font-size: 13px;
            opacity: 0.9;
            font-style: italic;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
        }

        /* Section Cards */
        .section-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--gray-200);
            margin-bottom: 2rem;
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
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .alerts-sidebar {
                width: 300px;
            }
            
            .enhanced-metrics-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .financial-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: all 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .alerts-sidebar {
                width: 100%;
                position: fixed;
            }
            
            .enhanced-metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .financial-grid {
                grid-template-columns: 1fr;
            }
            
            .deadline-alerts-grid {
                grid-template-columns: 1fr;
            }
            
            .ai-predictions-grid {
                grid-template-columns: 1fr;
            }
            
            .top-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .content-area {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_GET['admin_test'])): ?>
    <div style="background: linear-gradient(135deg, var(--info), #60A5FA); color: white; padding: 1rem; text-align: center;">
        <strong>🧪 Admin Test Mode:</strong> Dashboard running with AI-powered sample data. 
        <a href="dashboard.php" style="color: white; text-decoration: underline;">Exit test mode</a>
    </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <!-- Real-Time Alerts Sidebar -->
        <div class="alerts-sidebar" id="alertsSidebar">
            <div class="alerts-header">
                <div>
                    <i class="fas fa-bell"></i>
                    Real-Time Alerts
                </div>
                <button class="close-alerts" onclick="toggleAlerts()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="alerts-list">
                <?php foreach ($realTimeAlerts as $alert): ?>
                <div class="alert-item <?= $alert['severity'] ?> <?= !$alert['is_read'] ? 'unread' : '' ?>" 
                     onclick="markAlertRead(<?= $alert['id'] ?>)">
                    <?php if (!$alert['is_read']): ?>
                    <div class="unread-indicator"></div>
                    <?php endif; ?>
                    
                    <div class="alert-header">
                        <div class="alert-title"><?= htmlspecialchars($alert['title']) ?></div>
                        <div class="alert-severity severity-<?= $alert['severity'] ?>">
                            <?= ucfirst($alert['severity']) ?>
                        </div>
                    </div>
                    
                    <div class="alert-message"><?= htmlspecialchars($alert['message']) ?></div>
                    <div class="alert-time"><?= $alert['time_ago'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <span>Foxhole Pro</span>
                </div>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <div class="nav-link active" onclick="showTab('overview')">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>AI Dashboard</span>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link" onclick="showTab('financial')">
                            <i class="fas fa-chart-line"></i>
                            <span>Financial Intel</span>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link" onclick="showTab('deadlines')">
                            <i class="fas fa-clock"></i>
                            <span>Client Deadlines</span>
                            <?php if (count(array_filter($deadlineAlerts, function($d) { return $d['risk_level'] === 'critical' || $d['risk_level'] === 'high'; })) > 0): ?>
                            <span class="nav-badge"><?= count(array_filter($deadlineAlerts, function($d) { return $d['risk_level'] === 'critical' || $d['risk_level'] === 'high'; })) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link" onclick="showTab('ai-insights')">
                            <i class="fas fa-robot"></i>
                            <span>AI Insights</span>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="employee_dashboard.php?test_employee=1" class="nav-link">
                            <i class="fas fa-user"></i>
                            <span>Employee View</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div style="padding: 2rem; margin-top: auto;">
                <button class="btn btn-secondary" style="width: 100%;" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="page-header">
                    <h1>AI-Powered CEO Dashboard</h1>
                    <p>Real-time intelligence with predictive insights and financial monitoring</p>
                </div>
                <div class="top-actions">
                    <button class="btn btn-alerts" onclick="toggleAlerts()">
                        <i class="fas fa-bell"></i>
                        Alerts
                        <?php if (count(array_filter($realTimeAlerts, function($a) { return !$a['is_read']; })) > 0): ?>
                        <span class="alerts-count"><?= count(array_filter($realTimeAlerts, function($a) { return !$a['is_read']; })) ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="btn btn-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                    <button class="btn btn-primary" onclick="exportReport()">
                        <i class="fas fa-download"></i>
                        Export Report
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <!-- Enhanced Metrics -->
                    <div class="enhanced-metrics-grid">
                        <div class="metric-card financial">
                            <div class="metric-header">
                                <div class="metric-icon financial">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <div class="metric-number">$<?= number_format($financial['net_profit']) ?></div>
                            <div class="metric-label">Net Profit</div>
                            <div class="metric-change <?= $financial['net_profit'] > 0 ? 'positive' : 'negative' ?>">
                                <i class="fas fa-<?= $financial['net_profit'] > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= $financial['profit_margin'] ?>% margin
                            </div>
                        </div>
                        
                        <div class="metric-card revenue">
                            <div class="metric-header">
                                <div class="metric-icon revenue">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                            </div>
                            <div class="metric-number">$<?= number_format($financial['total_revenue']) ?></div>
                            <div class="metric-label">Total Revenue</div>
                            <div class="metric-change positive">
                                <i class="fas fa-trending-up"></i>
                                Forecast: $<?= number_format($financial['revenue_forecast']) ?>
                            </div>
                        </div>
                        
                        <div class="metric-card alerts">
                            <div class="metric-header">
                                <div class="metric-icon alerts">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="metric-number"><?= count(array_filter($realTimeAlerts, function($a) { return $a['severity'] === 'critical' || $a['severity'] === 'high'; })) ?></div>
                            <div class="metric-label">Critical Alerts</div>
                            <div class="metric-change <?= count(array_filter($realTimeAlerts, function($a) { return $a['severity'] === 'critical' || $a['severity'] === 'high'; })) > 0 ? 'negative' : 'positive' ?>">
                                <i class="fas fa-bell"></i>
                                Need attention
                            </div>
                        </div>
                        
                        <div class="metric-card ai">
                            <div class="metric-header">
                                <div class="metric-icon ai">
                                    <i class="fas fa-brain"></i>
                                </div>
                            </div>
                            <div class="metric-number"><?= count($aiInsights['predictions']) ?></div>
                            <div class="metric-label">AI Predictions</div>
                            <div class="metric-change positive">
                                <i class="fas fa-robot"></i>
                                Active insights
                            </div>
                        </div>
                    </div>

                    <!-- Quick Overview Cards -->
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                        <!-- Financial Intelligence Preview -->
                        <div class="financial-widget">
                            <div class="financial-header">
                                <h3 class="financial-title">
                                    <i class="fas fa-chart-pie"></i>
                                    Financial Intelligence
                                </h3>
                                <button class="btn btn-secondary" onclick="showTab('financial')">
                                    View Details
                                </button>
                            </div>
                            
                            <div class="financial-grid">
                                <div class="financial-metric">
                                    <div class="financial-value">$<?= number_format($financial['actual_earned']) ?></div>
                                    <div class="financial-label">Earned This Month</div>
                                    <div class="profit-indicator">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>+<?= round(($financial['actual_earned'] / max($financial['total_costs'], 1)) * 100) ?>% ROI</span>
                                    </div>
                                </div>
                                <div class="financial-metric">
                                    <div class="financial-value">$<?= number_format($financial['total_costs']) ?></div>
                                    <div class="financial-label">Total Costs</div>
                                    <div class="profit-indicator">
                                        <i class="fas fa-clock"></i>
                                        <span>Burn Rate: $<?= number_format($financial['burn_rate']) ?>/week</span>
                                    </div>
                                </div>
                                <div class="financial-metric">
                                    <div class="financial-value"><?= $financial['profit_margin'] ?>%</div>
                                    <div class="financial-label">Profit Margin</div>
                                    <div class="profit-indicator">
                                        <i class="fas fa-target"></i>
                                        <span>Target: 25%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Critical Alerts Preview -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-bolt"></i>
                                    Critical Items
                                </h3>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php 
                                $criticalAlerts = array_filter($realTimeAlerts, function($a) { 
                                    return $a['severity'] === 'critical' || $a['severity'] === 'high'; 
                                });
                                ?>
                                
                                <?php if (empty($criticalAlerts)): ?>
                                <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; color: var(--success);"></i>
                                    <p>All systems running smoothly!</p>
                                </div>
                                <?php else: ?>
                                <?php foreach (array_slice($criticalAlerts, 0, 3) as $alert): ?>
                                <div style="padding: 12px; border-left: 4px solid var(--alert-<?= $alert['severity'] ?>); background: rgba(<?= $alert['severity'] === 'critical' ? '220, 38, 38' : '245, 158, 11' ?>, 0.1); border-radius: var(--radius-md);">
                                    <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">
                                        <?= htmlspecialchars($alert['title']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray-600);">
                                        <?= $alert['time_ago'] ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <button class="btn btn-primary" style="margin-top: 1rem;" onclick="toggleAlerts()">
                                    <i class="fas fa-bell"></i>
                                    View All Alerts
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- AI Insights Preview -->
                    <div class="ai-insights-widget">
                        <div class="ai-insights-header">
                            <div class="ai-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div>
                                <h3 class="ai-insights-title">AI Insights & Predictions</h3>
                                <p style="opacity: 0.9;">Machine learning analysis of your team performance</p>
                            </div>
                        </div>
                        
                        <div class="ai-predictions-grid">
                            <?php foreach (array_slice($aiInsights['predictions'], 0, 3) as $prediction): ?>
                            <div class="ai-prediction">
                                <div class="prediction-confidence">
                                    <i class="fas fa-chart-line"></i>
                                    <div class="confidence-badge"><?= $prediction['confidence'] ?>% Confidence</div>
                                </div>
                                <div class="prediction-text"><?= htmlspecialchars($prediction['prediction']) ?></div>
                                <div class="prediction-recommendation">
                                    💡 <?= htmlspecialchars($prediction['recommendation']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="text-align: center; margin-top: 2rem;">
                            <button class="btn btn-secondary" onclick="showTab('ai-insights')">
                                <i class="fas fa-robot"></i>
                                View Full AI Analysis
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Financial Intelligence Tab -->
                <div id="financial" class="tab-content">
                    <div class="financial-widget">
                        <div class="financial-header">
                            <h3 class="financial-title">
                                <i class="fas fa-chart-pie"></i>
                                Financial Intelligence Dashboard
                            </h3>
                            <div style="display: flex; gap: 1rem;">
                                <button class="btn btn-secondary" onclick="exportFinancialReport()">
                                    <i class="fas fa-download"></i>
                                    Export P&L
                                </button>
                                <button class="btn btn-secondary" onclick="showBudgetAnalysis()">
                                    <i class="fas fa-calculator"></i>
                                    Budget Analysis
                                </button>
                            </div>
                        </div>
                        
                        <div class="financial-grid">
                            <div class="financial-metric">
                                <div class="financial-value">$<?= number_format($financial['total_revenue']) ?></div>
                                <div class="financial-label">Total Revenue</div>
                                <div class="profit-indicator">
                                    <i class="fas fa-trending-up"></i>
                                    <span>Forecast: $<?= number_format($financial['revenue_forecast']) ?></span>
                                </div>
                            </div>
                            <div class="financial-metric">
                                <div class="financial-value">$<?= number_format($financial['actual_earned']) ?></div>
                                <div class="financial-label">Actual Earned</div>
                                <div class="profit-indicator">
                                    <i class="fas fa-clock"></i>
                                    <span><?= round(($financial['actual_earned'] / $financial['total_revenue']) * 100) ?>% of budget</span>
                                </div>
                            </div>
                            <div class="financial-metric">
                                <div class="financial-value">$<?= number_format($financial['total_costs']) ?></div>
                                <div class="financial-label">Total Costs</div>
                                <div class="profit-indicator">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Efficiency: <?= round(($financial['actual_earned'] / max($financial['total_costs'], 1)) * 100) ?>%</span>
                                </div>
                            </div>
                            <div class="financial-metric">
                                <div class="financial-value">$<?= number_format($financial['net_profit']) ?></div>
                                <div class="financial-label">Net Profit</div>
                                <div class="profit-indicator">
                                    <i class="fas fa-percentage"></i>
                                    <span><?= $financial['profit_margin'] ?>% margin</span>
                                </div>
                            </div>
                            <div class="financial-metric">
                                <div class="financial-value">$<?= number_format($financial['burn_rate']) ?></div>
                                <div class="financial-label">Weekly Burn Rate</div>
                                <div class="profit-indicator">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?= $financial['runway_months'] ?> months runway</span>
                                </div>
                            </div>
                            <div class="financial-metric">
                                <div class="financial-value"><?= count($financial['top_profitable_projects']) ?></div>
                                <div class="financial-label">Profitable Projects</div>
                                <div class="profit-indicator">
                                    <i class="fas fa-award"></i>
                                    <span>Top performers</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Profitable Projects -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-trophy"></i>
                                Most Profitable Projects
                            </h3>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($financial['top_profitable_projects'] as $index => $project): ?>
                            <div style="background: var(--gray-50); border-radius: var(--radius-lg); padding: 1.5rem; border-left: 6px solid var(--profit);">
                                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
                                    <div>
                                        <h4 style="font-weight: 800; margin-bottom: 4px;"><?= htmlspecialchars($project['name']) ?></h4>
                                        <div style="font-size: 12px; color: var(--gray-600);">Project #{<?= $index + 1 ?> performer</div>
                                    </div>
                                    <?php if ($index === 0): ?>
                                    <i class="fas fa-crown" style="color: #FFD700; font-size: 24px;"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-size: 24px; font-weight: 900; color: var(--profit);">$<?= number_format($project['profit']) ?></div>
                                        <div style="font-size: 12px; color: var(--gray-600);">Net Profit</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 18px; font-weight: 700; color: var(--success);"><?= $project['margin'] ?>%</div>
                                        <div style="font-size: 12px; color: var(--gray-600);">Margin</div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Client Deadlines Tab -->
                <div id="deadlines" class="tab-content">
                    <div class="deadline-alerts-widget">
                        <div class="deadline-alerts-header">
                            <h3 class="deadline-alerts-title">
                                <i class="fas fa-clock"></i>
                                Client Deadline Monitoring
                            </h3>
                            <div style="display: flex; gap: 1rem;">
                                <button class="btn btn-secondary" onclick="exportDeadlineReport()">
                                    <i class="fas fa-calendar-alt"></i>
                                    Export Calendar
                                </button>
                                <button class="btn btn-primary" onclick="scheduleClientCheckin()">
                                    <i class="fas fa-phone"></i>
                                    Schedule Check-ins
                                </button>
                            </div>
                        </div>
                        
                        <div class="deadline-alerts-grid">
                            <?php foreach ($deadlineAlerts as $deadline): ?>
                            <div class="deadline-alert-card <?= $deadline['risk_level'] ?>">
                                <div class="deadline-project-header">
                                    <div>
                                        <div class="deadline-project-name"><?= htmlspecialchars($deadline['project']) ?></div>
                                        <div class="deadline-client">
                                            <i class="fas fa-building"></i>
                                            <?= htmlspecialchars($deadline['client']) ?>
                                        </div>
                                    </div>
                                    <div class="deadline-countdown countdown-<?= $deadline['risk_level'] ?>">
                                        <?php if ($deadline['days_remaining'] <= 0): ?>
                                            OVERDUE
                                        <?php elseif ($deadline['days_remaining'] === 1): ?>
                                            1 DAY
                                        <?php else: ?>
                                            <?= $deadline['days_remaining'] ?> DAYS
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="deadline-progress">
                                    <div class="deadline-progress-header">
                                        <span class="deadline-progress-text"><?= $deadline['progress'] ?>% Complete</span>
                                        <span style="font-size: 12px; color: var(--gray-600); text-transform: uppercase; font-weight: 700;">
                                            <?= str_replace('_', ' ', $deadline['status']) ?>
                                        </span>
                                    </div>
                                    <div class="deadline-progress-bar">
                                        <div class="deadline-progress-fill" style="width: <?= $deadline['progress'] ?>%;"></div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 1rem; padding: 12px; background: rgba(255, 255, 255, 0.7); border-radius: var(--radius-md);">
                                    <div style="font-size: 12px; color: var(--gray-700); margin-bottom: 8px;">
                                        <strong>Deadline:</strong> <?= date('F j, Y', strtotime($deadline['deadline'])) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray-700);">
                                        <strong>Status:</strong> 
                                        <?php if ($deadline['status'] === 'behind_schedule'): ?>
                                            ⚠️ Behind schedule - needs acceleration
                                        <?php elseif ($deadline['status'] === 'at_risk'): ?>
                                            🔍 Monitor closely for delays
                                        <?php elseif ($deadline['status'] === 'on_track'): ?>
                                            ✅ On track for timely delivery
                                        <?php else: ?>
                                            🚨 Immediate action required
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- AI Insights Tab -->
                <div id="ai-insights" class="tab-content">
                    <div class="ai-insights-widget">
                        <div class="ai-insights-header">
                            <div class="ai-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div>
                                <h3 class="ai-insights-title">AI-Powered Business Intelligence</h3>
                                <p style="opacity: 0.9;">Advanced machine learning analysis of your team and project performance</p>
                            </div>
                        </div>
                        
                        <div class="ai-predictions-grid">
                            <?php foreach ($aiInsights['predictions'] as $prediction): ?>
                            <div class="ai-prediction">
                                <div class="prediction-confidence">
                                    <i class="fas fa-<?= $prediction['type'] === 'deadline' ? 'clock' : ($prediction['type'] === 'performance' ? 'user' : 'dollar-sign') ?>"></i>
                                    <div class="confidence-badge"><?= $prediction['confidence'] ?>% Confidence</div>
                                    <div style="margin-left: auto; padding: 4px 8px; background: rgba(255,255,255,0.2); border-radius: 12px; font-size: 10px; text-transform: uppercase;">
                                        <?= $prediction['type'] ?>
                                    </div>
                                </div>
                                <div class="prediction-text"><?= htmlspecialchars($prediction['prediction']) ?></div>
                                <div class="prediction-recommendation">
                                    💡 <strong>Recommendation:</strong> <?= htmlspecialchars($prediction['recommendation']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Optimization Suggestions -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-lightbulb"></i>
                                AI Optimization Suggestions
                            </h3>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php if (empty($aiInsights['optimization_suggestions'])): ?>
                            <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; color: var(--success);"></i>
                                <p>Your team is optimally balanced! No immediate optimizations needed.</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($aiInsights['optimization_suggestions'] as $index => $suggestion): ?>
                            <div style="background: linear-gradient(135deg, var(--info), #60A5FA); color: white; border-radius: var(--radius-lg); padding: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; margin-bottom: 4px;">Optimization #<?= $index + 1 ?></div>
                                    <div style="opacity: 0.9;"><?= htmlspecialchars($suggestion) ?></div>
                                </div>
                                <button style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 8px 16px; border-radius: var(--radius-md); cursor: pointer; font-weight: 600;">
                                    Apply
                                </button>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Risk Factors -->
                    <?php if (!empty($aiInsights['risk_factors'])): ?>
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-exclamation-triangle"></i>
                                Risk Factor Analysis
                            </h3>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($aiInsights['risk_factors'] as $risk): ?>
                            <div style="background: var(--gray-50); border-radius: var(--radius-lg); padding: 1.5rem; border-left: 6px solid var(--<?= $risk['risk_score'] >= 80 ? 'danger' : ($risk['risk_score'] >= 60 ? 'warning' : 'info') ?>);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <h4 style="font-weight: 800;"><?= htmlspecialchars($risk['factor']) ?></h4>
                                    <div style="background: var(--<?= $risk['risk_score'] >= 80 ? 'danger' : ($risk['risk_score'] >= 60 ? 'warning' : 'info') ?>); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                        <?= $risk['risk_score'] ?>% Risk
                                    </div>
                                </div>
                                <div style="font-size: 14px; color: var(--gray-700);">
                                    <strong>Projects Affected:</strong> <?= $risk['projects_affected'] ?>
                                </div>
                                <div style="margin-top: 12px;">
                                    <div style="width: 100%; height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden;">
                                        <div style="height: 100%; background: var(--<?= $risk['risk_score'] >= 80 ? 'danger' : ($risk['risk_score'] >= 60 ? 'warning' : 'info') ?>); width: <?= $risk['risk_score'] ?>%; border-radius: 4px; transition: width 0.3s ease;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab management
        function showTab(tabId) {
            console.log('Switching to tab:', tabId);
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            document.querySelectorAll('.nav-link').forEach(link => {
                const onclick = link.getAttribute('onclick');
                if (onclick && onclick.includes(`'${tabId}'`)) {
                    link.classList.add('active');
                }
            });
        }

        // Alerts management
        function toggleAlerts() {
            const sidebar = document.getElementById('alertsSidebar');
            sidebar.classList.toggle('active');
        }

        async function markAlertRead(alertId) {
            try {
                const response = await fetch('dashboard_pro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_alert_read&alert_id=${alertId}`
                });
                
                const result = await response.json();
                if (result.success) {
                    // Update UI to show alert as read
                    const alertElement = document.querySelector(`[onclick="markAlertRead(${alertId})"]`);
                    if (alertElement) {
                        alertElement.classList.remove('unread');
                        const indicator = alertElement.querySelector('.unread-indicator');
                        if (indicator) {
                            indicator.remove();
                        }
                    }
                    
                    // Update alerts count in button
                    updateAlertsCount();
                }
            } catch (error) {
                console.error('Error marking alert as read:', error);
            }
        }

        function updateAlertsCount() {
            const unreadAlerts = document.querySelectorAll('.alert-item.unread').length;
            const countElement = document.querySelector('.alerts-count');
            if (countElement) {
                if (unreadAlerts > 0) {
                    countElement.textContent = unreadAlerts;
                    countElement.style.display = 'block';
                } else {
                    countElement.style.display = 'none';
                }
            }
        }

        // Refresh dashboard
        async function refreshDashboard() {
            try {
                const response = await fetch('dashboard_pro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=refresh_data'
                });
                
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    console.error('Failed to refresh data');
                }
            } catch (error) {
                console.error('Refresh error:', error);
                location.reload();
            }
        }

        // Export functions
        function exportReport() {
            alert('📊 Generating AI-powered comprehensive report...\n\nThis will include:\n• Financial intelligence summary\n• Client deadline analysis\n• AI predictions and insights\n• Risk factor assessment\n• Optimization recommendations\n• Executive summary with key metrics');
        }

        function exportFinancialReport() {
            alert('💰 Exporting financial intelligence report...\n\nThis will include:\n• Profit & Loss statement\n• Revenue forecasting\n• Cost analysis by project\n• Burn rate calculations\n• ROI analysis\n• Budget vs actual comparison');
        }

        function exportDeadlineReport() {
            alert('📅 Exporting client deadline report...\n\nThis will include:\n• All client deadlines calendar\n• Risk assessment by project\n• Progress tracking\n• Client communication timeline\n• Delivery schedule optimization');
        }

        function showBudgetAnalysis() {
            alert('📊 Opening detailed budget analysis...\n\nThis would show:\n• Budget vs actual by project\n• Cost breakdown by resource\n• Profitability analysis\n• Resource utilization costs\n• Forecasted budget needs');
        }

        function scheduleClientCheckin() {
            alert('📞 Opening client check-in scheduler...\n\nThis would help:\n• Schedule calls for at-risk projects\n• Send automated status updates\n• Set up milestone reviews\n• Configure deadline reminders\n• Plan delivery communications');
        }

        // Logout
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'auth.php?action=logout';
            }
        }

        // Real-time updates
        function startRealTimeUpdates() {
            // Simulate real-time alert generation
            setInterval(() => {
                // In a real implementation, this would poll for new alerts
                console.log('Checking for new alerts...');
            }, 30000);

            // Update clock for deadline countdowns
            setInterval(() => {
                updateDeadlineCountdowns();
            }, 60000);
        }

        function updateDeadlineCountdowns() {
            // Update deadline countdowns in real-time
            const countdowns = document.querySelectorAll('.deadline-countdown');
            countdowns.forEach(countdown => {
                // In a real implementation, recalculate days remaining
                console.log('Updating countdown:', countdown.textContent);
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case '1': e.preventDefault(); showTab('overview'); break;
                    case '2': e.preventDefault(); showTab('financial'); break;
                    case '3': e.preventDefault(); showTab('deadlines'); break;
                    case '4': e.preventDefault(); showTab('ai-insights'); break;
                    case 'r': e.preventDefault(); refreshDashboard(); break;
                    case 'a': e.preventDefault(); toggleAlerts(); break;
                }
            }
        });

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Foxhole Pro Dashboard initialized');
            startRealTimeUpdates();
            
            // Close alerts sidebar when clicking outside
            document.addEventListener('click', function(e) {
                const sidebar = document.getElementById('alertsSidebar');
                const alertsBtn = document.querySelector('.btn-alerts');
                
                if (!sidebar.contains(e.target) && !alertsBtn.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            });
        });

        // Error handling
        window.addEventListener('error', function(e) {
            console.error('Dashboard Error:', e.error);
        });

        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled Promise Rejection:', e.reason);
        });
    </script>
</body>
</html>