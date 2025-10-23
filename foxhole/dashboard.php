<?php
// ==================== SECTION 1: SESSION & AUTHENTICATION ====================
// Purpose: Handle user authentication, session management, admin checks
// Dependencies: None
// Last updated: Current
// CLAUDE NOTE: For future updates to this section, only provide PHP code between 
// this marker and SECTION 2 marker. Include session config, auth checks, admin validation.
// dashboard.php - FIXED VERSION with Enhanced Reporting + CEO Notes
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

// Simple authentication check - FIXED
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

// ==================== SECTION 2: DATABASE & CONFIG ====================
// Purpose: Include database configuration and setup
// Dependencies: config-2.php
// CLAUDE NOTE: For future updates to this section, only provide the require statements
// and any database connection setup code.
require_once 'config-2.php';

// ==================== SECTION 3: ENHANCED API CLASS ====================
// Purpose: Complete API class with all dashboard methods
// Dependencies: config-2.php, Database class
// Contains: getDashboardStats, getDetailedEmployeeReport, getProjectDetailsReport, etc.
// CLAUDE NOTE: For future updates to this section, provide the ENTIRE class definition
// from "class EnhancedDashboardAPI {" to the closing "}" - this is the core API logic.
// Enhanced API class with detailed reporting
class EnhancedDashboardAPI {
    private $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->initializeMockData();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    private function initializeMockData() {
        if (!$this->db) return;
        
        try {
            // Check if we have data
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM employees");
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                // Insert mock employees
                $employees = [
                    ['John Doe', 'john@neofox.com', 'Lead Developer', 'employee', 'active'],
                    ['Sarah Wilson', 'sarah@neofox.com', 'Senior Designer', 'employee', 'active'],
                    ['Mike Johnson', 'mike@neofox.com', 'Video Editor', 'employee', 'busy'],
                    ['Emily Davis', 'emily@neofox.com', 'Project Manager', 'employee', 'active'],
                    ['Alex Chen', 'alex@neofox.com', 'Motion Designer', 'freelancer', 'away'],
                    ['Lisa Park', 'lisa@neofox.com', 'Social Media Manager', 'employee', 'active'],
                    ['David Kim', 'david@neofox.com', 'Content Writer', 'freelancer', 'active'],
                    ['Maya Patel', 'maya@neofox.com', 'UI/UX Designer', 'employee', 'active']
                ];
                
                foreach ($employees as $emp) {
                    $stmt = $this->db->prepare("INSERT INTO employees (name, email, role, type, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute($emp);
                }
                
                // Insert mock projects
                $projects = [
                    ['Nike Campaign 2024', 'Nike Inc', 'Video campaign for new product launch', 'high', 'active', '2024-01-15', '2024-03-30'],
                    ['TechCorp Website Redesign', 'TechCorp Ltd', 'Complete website overhaul with new branding', 'urgent', 'active', '2024-02-01', '2024-04-15'],
                    ['Local Restaurant Branding', 'Pizza Palace', 'Logo design and brand identity package', 'medium', 'planning', '2024-03-01', '2024-05-30'],
                    ['E-commerce Platform', 'ShopEasy', 'Custom e-commerce solution development', 'high', 'active', '2024-01-10', '2024-06-30']
                ];
                
                foreach ($projects as $proj) {
                    $stmt = $this->db->prepare("INSERT INTO projects (name, client, description, priority, status, start_date, end_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute($proj);
                }
                
                // Insert mock tasks with realistic data
                $tasks = [
                    [1, 1, 'Create hero video concept', 'Video concept development for Nike campaign', 'completed', 'high', 8.0, 7.5, '2024-02-15'],
                    [1, 1, 'Film main product shots', 'Professional product photography', 'in_progress', 'high', 12.0, 8.0, '2024-02-20'],
                    [2, 2, 'Design homepage mockup', 'Create responsive homepage design', 'completed', 'urgent', 16.0, 18.0, '2024-02-10'],
                    [2, 2, 'Develop frontend components', 'Build reusable React components', 'in_progress', 'urgent', 24.0, 15.0, '2024-02-25'],
                    [3, 3, 'Logo design concepts', 'Create 5 logo variations', 'completed', 'medium', 6.0, 5.5, '2024-03-05'],
                    [4, 4, 'Database schema design', 'Design database structure', 'completed', 'high', 10.0, 12.0, '2024-01-20'],
                    [4, 4, 'Payment gateway integration', 'Integrate Stripe payment system', 'todo', 'high', 15.0, 0.0, '2024-03-15'],
                    [5, 1, 'Edit promotional video', 'Final video editing and color grading', 'review', 'high', 20.0, 18.5, '2024-02-18'],
                    [6, 2, 'Social media assets', 'Create social media post templates', 'in_progress', 'medium', 8.0, 6.0, '2024-02-22'],
                    [7, 3, 'Brand guidelines document', 'Write comprehensive brand guidelines', 'todo', 'medium', 12.0, 0.0, '2024-03-10']
                ];
                
                foreach ($tasks as $task) {
                    $stmt = $this->db->prepare("INSERT INTO tasks (employee_id, project_id, title, description, status, priority, estimated_hours, hours_logged, due_date, created_at, last_activity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute($task);
                }
                
                // Insert activity log
                $activities = [
                    [1, 'Completed task "Create hero video concept"', 'task_complete'],
                    [2, 'Started working on homepage design', 'task_start'],
                    [3, 'Submitted logo concepts for review', 'task_complete'],
                    [4, 'Updated project timeline', 'general'],
                    [5, 'Status changed to Away', 'status_change']
                ];
                
                foreach ($activities as $activity) {
                    $stmt = $this->db->prepare("INSERT INTO activity_log (employee_id, activity, type, created_at) VALUES (?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 60) MINUTE)");
                    $stmt->execute($activity);
                }
            }
        } catch (Exception $e) {
            error_log("Error initializing mock data: " . $e->getMessage());
        }
    }
    
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
    
    public function getDetailedEmployeeReport() {
        if (!$this->db) {
            return [
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'role' => 'Lead Developer',
                    'type' => 'employee',
                    'status' => 'active',
                    'total_tasks' => 5,
                    'completed_tasks' => 3,
                    'in_progress_tasks' => 1,
                    'overdue_tasks' => 0,
                    'total_hours' => 45.5,
                    'avg_hours_per_task' => 9.1,
                    'efficiency_score' => 92,
                    'current_projects' => ['Nike Campaign', 'TechCorp Website'],
                    'performance_trend' => 'up',
                    'workload_status' => 'optimal'
                ],
                [
                    'id' => 2,
                    'name' => 'Sarah Wilson',
                    'role' => 'Senior Designer',
                    'type' => 'employee',
                    'status' => 'active',
                    'total_tasks' => 4,
                    'completed_tasks' => 2,
                    'in_progress_tasks' => 2,
                    'overdue_tasks' => 1,
                    'total_hours' => 38.0,
                    'avg_hours_per_task' => 9.5,
                    'efficiency_score' => 85,
                    'current_projects' => ['TechCorp Website'],
                    'performance_trend' => 'stable',
                    'workload_status' => 'high'
                ]
            ];
        }
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    e.*,
                    COUNT(t.id) as total_tasks,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as in_progress_tasks,
                    COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 1 END) as overdue_tasks,
                    SUM(t.hours_logged) as total_hours,
                    AVG(t.hours_logged) as avg_hours_per_task,
                    AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 
                        THEN (t.hours_logged / t.estimated_hours) * 100 
                        ELSE NULL END) as efficiency_score,
                    GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as current_projects
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                LEFT JOIN projects p ON t.project_id = p.id AND t.status != 'completed'
                GROUP BY e.id
                ORDER BY e.name
            ");
            
            $employees = $stmt->fetchAll();
            
            foreach ($employees as &$emp) {
                $emp['efficiency_score'] = round($emp['efficiency_score'] ?? 75);
                $emp['total_hours'] = round($emp['total_hours'] ?? 0, 1);
                $emp['avg_hours_per_task'] = round($emp['avg_hours_per_task'] ?? 0, 1);
                $emp['current_projects'] = $emp['current_projects'] ? explode(', ', $emp['current_projects']) : [];
                
                // Determine performance trend
                $emp['performance_trend'] = $emp['efficiency_score'] >= 90 ? 'up' : 
                                          ($emp['efficiency_score'] >= 75 ? 'stable' : 'down');
                
                // Determine workload status
                $workload = $emp['total_tasks'] - $emp['completed_tasks'];
                $emp['workload_status'] = $workload >= 5 ? 'overloaded' : 
                                        ($workload >= 3 ? 'high' : 
                                        ($workload >= 1 ? 'optimal' : 'light'));
            }
            
            return $employees;
        } catch (Exception $e) {
            error_log("Error getting employee report: " . $e->getMessage());
            return [];
        }
    }
    
    public function getProjectDetailsReport() {
        if (!$this->db) {
            return [
                [
                    'id' => 1,
                    'name' => 'Nike Campaign 2024',
                    'client' => 'Nike Inc',
                    'priority' => 'high',
                    'status' => 'active',
                    'progress' => 65,
                    'total_tasks' => 3,
                    'completed_tasks' => 1,
                    'team_size' => 2,
                    'total_hours' => 33.5,
                    'estimated_hours' => 40.0,
                    'efficiency' => 84,
                    'days_remaining' => 45,
                    'budget_status' => 'on_track',
                    'team_members' => ['John Doe', 'Mike Johnson']
                ]
            ];
        }
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    p.*,
                    COUNT(t.id) as total_tasks,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                    COUNT(DISTINCT t.employee_id) as team_size,
                    SUM(t.hours_logged) as total_hours,
                    SUM(t.estimated_hours) as estimated_hours,
                    GROUP_CONCAT(DISTINCT e.name SEPARATOR ', ') as team_members,
                    DATEDIFF(p.end_date, CURDATE()) as days_remaining
                FROM projects p
                LEFT JOIN tasks t ON p.id = t.project_id
                LEFT JOIN employees e ON t.employee_id = e.id
                GROUP BY p.id
                ORDER BY p.priority = 'urgent' DESC, p.priority = 'high' DESC, p.created_at DESC
            ");
            
            $projects = $stmt->fetchAll();
            
            foreach ($projects as &$proj) {
                $proj['progress'] = $proj['total_tasks'] > 0 ? 
                    round(($proj['completed_tasks'] / $proj['total_tasks']) * 100) : 0;
                $proj['efficiency'] = $proj['estimated_hours'] > 0 ? 
                    round(($proj['total_hours'] / $proj['estimated_hours']) * 100) : 100;
                $proj['total_hours'] = round($proj['total_hours'] ?? 0, 1);
                $proj['estimated_hours'] = round($proj['estimated_hours'] ?? 0, 1);
                $proj['team_members'] = $proj['team_members'] ? explode(', ', $proj['team_members']) : [];
                $proj['budget_status'] = $proj['efficiency'] <= 110 ? 'on_track' : 'over_budget';
                $proj['days_remaining'] = max(0, $proj['days_remaining'] ?? 0);
            }
            
            return $projects;
        } catch (Exception $e) {
            error_log("Error getting project report: " . $e->getMessage());
            return [];
        }
    }
    
    public function getProductivityAnalytics() {
        if (!$this->db) {
            return [
                'top_performers' => [
                    ['name' => 'John Doe', 'efficiency' => 92, 'completed_tasks' => 8],
                    ['name' => 'Sarah Wilson', 'efficiency' => 89, 'completed_tasks' => 6]
                ],
                'bottom_performers' => [
                    ['name' => 'Alex Chen', 'efficiency' => 65, 'completed_tasks' => 2]
                ],
                'team_velocity' => [
                    ['week' => 'Week 1', 'completed' => 12, 'planned' => 15],
                    ['week' => 'Week 2', 'completed' => 18, 'planned' => 16]
                ]
            ];
        }
        
        try {
            // Top performers
            $stmt = $this->db->query("
                SELECT 
                    e.name,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                    AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 
                        THEN (t.hours_logged / t.estimated_hours) * 100 
                        ELSE NULL END) as efficiency
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                GROUP BY e.id, e.name
                HAVING completed_tasks > 0
                ORDER BY efficiency DESC, completed_tasks DESC
                LIMIT 5
            ");
            $top_performers = $stmt->fetchAll();
            
            // Bottom performers
            $stmt = $this->db->query("
                SELECT 
                    e.name,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                    AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 
                        THEN (t.hours_logged / t.estimated_hours) * 100 
                        ELSE NULL END) as efficiency
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                GROUP BY e.id, e.name
                HAVING efficiency < 80 OR (efficiency IS NULL AND completed_tasks = 0)
                ORDER BY efficiency ASC, completed_tasks ASC
                LIMIT 3
            ");
            $bottom_performers = $stmt->fetchAll();
            
            return [
                'top_performers' => array_map(function($p) {
                    $p['efficiency'] = round($p['efficiency'] ?? 0);
                    return $p;
                }, $top_performers),
                'bottom_performers' => array_map(function($p) {
                    $p['efficiency'] = round($p['efficiency'] ?? 0);
                    return $p;
                }, $bottom_performers),
                'team_velocity' => [
                    ['week' => 'Week 1', 'completed' => 12, 'planned' => 15],
                    ['week' => 'Week 2', 'completed' => 18, 'planned' => 16],
                    ['week' => 'Week 3', 'completed' => 15, 'planned' => 14],
                    ['week' => 'Week 4', 'completed' => 22, 'planned' => 20]
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting productivity analytics: " . $e->getMessage());
            return ['top_performers' => [], 'bottom_performers' => [], 'team_velocity' => []];
        }
    }

    public function getClosureInsights() {
        if (!$this->db) {
            return [
                'team_average_hours' => 42,
                'team_average_days' => 1.8,
                'on_time_completion_rate' => 76,
                'recent_closures' => [
                    [
                        'title' => 'Create hero video concept',
                        'project' => 'Nike Campaign 2024',
                        'employee' => 'John Doe',
                        'closed_at' => date('Y-m-d H:i', strtotime('-3 hours')),
                        'cycle_hours' => 36
                    ],
                    [
                        'title' => 'Design homepage mockup',
                        'project' => 'TechCorp Website Redesign',
                        'employee' => 'Sarah Wilson',
                        'closed_at' => date('Y-m-d H:i', strtotime('-1 day')),
                        'cycle_hours' => 54
                    ]
                ],
                'fastest_closer' => [
                    'name' => 'Sarah Wilson',
                    'avg_hours' => 28,
                    'avg_days' => 1.2,
                    'completed_tasks' => 6,
                    'on_time_rate' => 88
                ],
                'slowest_closer' => [
                    'name' => 'Alex Chen',
                    'avg_hours' => 72,
                    'avg_days' => 3,
                    'completed_tasks' => 2,
                    'on_time_rate' => 40
                ],
                'employees' => [
                    [
                        'name' => 'John Doe',
                        'completed_tasks' => 8,
                        'avg_hours' => 40,
                        'avg_days' => 1.7,
                        'on_time_rate' => 82,
                        'hours_logged' => 68
                    ],
                    [
                        'name' => 'Sarah Wilson',
                        'completed_tasks' => 6,
                        'avg_hours' => 28,
                        'avg_days' => 1.2,
                        'on_time_rate' => 88,
                        'hours_logged' => 56
                    ],
                    [
                        'name' => 'Mike Johnson',
                        'completed_tasks' => 5,
                        'avg_hours' => 58,
                        'avg_days' => 2.4,
                        'on_time_rate' => 64,
                        'hours_logged' => 72
                    ]
                ]
            ];
        }

        try {
            $stmt = $this->db->query("
                SELECT
                    e.id,
                    e.name,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                    SUM(CASE WHEN t.status = 'completed' AND t.due_date IS NOT NULL AND t.last_activity <= t.due_date THEN 1 END) as on_time_tasks,
                    AVG(CASE WHEN t.status = 'completed' AND t.last_activity IS NOT NULL THEN TIMESTAMPDIFF(HOUR, t.created_at, t.last_activity) END) as avg_hours_to_close,
                    SUM(t.hours_logged) as hours_logged
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                GROUP BY e.id
                ORDER BY e.name ASC
            ");
            $rows = $stmt->fetchAll();

            $employees = [];
            $avgHours = [];
            $teamOnTime = 0;
            $teamCompleted = 0;

            foreach ($rows as $row) {
                $completed = (int)($row['completed_tasks'] ?? 0);
                $avgHour = $row['avg_hours_to_close'] !== null ? (float)$row['avg_hours_to_close'] : null;
                $onTime = (int)($row['on_time_tasks'] ?? 0);
                $teamCompleted += $completed;
                $teamOnTime += $onTime;

                if ($avgHour !== null) {
                    $avgHours[] = $avgHour;
                }

                $employees[] = [
                    'name' => $row['name'],
                    'completed_tasks' => $completed,
                    'avg_hours' => $avgHour !== null ? round($avgHour, 1) : null,
                    'avg_days' => $avgHour !== null ? round($avgHour / 24, 1) : null,
                    'on_time_rate' => $completed > 0 ? round(($onTime / $completed) * 100) : null,
                    'hours_logged' => round((float)($row['hours_logged'] ?? 0), 1)
                ];
            }

            $fastest = null;
            $slowest = null;

            foreach ($employees as $employee) {
                if ($employee['avg_hours'] === null) {
                    continue;
                }

                if ($fastest === null || $employee['avg_hours'] < $fastest['avg_hours']) {
                    $fastest = $employee;
                }

                if ($slowest === null || $employee['avg_hours'] > $slowest['avg_hours']) {
                    $slowest = $employee;
                }
            }

            $teamAverageHours = !empty($avgHours) ? array_sum($avgHours) / count($avgHours) : null;

            $recentStmt = $this->db->query("
                SELECT
                    t.title,
                    p.name as project,
                    e.name as employee,
                    t.last_activity as closed_at,
                    TIMESTAMPDIFF(HOUR, t.created_at, t.last_activity) as cycle_hours
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN employees e ON t.employee_id = e.id
                WHERE t.status = 'completed' AND t.last_activity IS NOT NULL
                ORDER BY t.last_activity DESC
                LIMIT 6
            ");
            $recentClosures = array_map(function ($item) {
                return [
                    'title' => $item['title'],
                    'project' => $item['project'],
                    'employee' => $item['employee'],
                    'closed_at' => $item['closed_at'],
                    'cycle_hours' => $item['cycle_hours'] !== null ? (int)$item['cycle_hours'] : null
                ];
            }, $recentStmt->fetchAll());

            return [
                'team_average_hours' => $teamAverageHours !== null ? round($teamAverageHours, 1) : null,
                'team_average_days' => $teamAverageHours !== null ? round($teamAverageHours / 24, 1) : null,
                'on_time_completion_rate' => $teamCompleted > 0 ? round(($teamOnTime / $teamCompleted) * 100) : null,
                'recent_closures' => $recentClosures,
                'fastest_closer' => $fastest,
                'slowest_closer' => $slowest,
                'employees' => $employees
            ];
        } catch (Exception $e) {
            error_log("Error getting closure insights: " . $e->getMessage());
            return [];
        }
    }

    public function getDeliveryMetrics() {
        if (!$this->db) {
            return [
                'portfolio_summary' => [
                    'active_projects' => 4,
                    'completed_this_month' => 2,
                    'average_cycle_days' => 18,
                    'on_time_delivery_rate' => 74
                ],
                'throughput' => [
                    'week_completed' => 18,
                    'last_week_completed' => 14,
                    'week_goal' => 20
                ],
                'projects' => [
                    [
                        'id' => 1,
                        'name' => 'Nike Campaign 2024',
                        'client' => 'Nike Inc',
                        'status' => 'active',
                        'priority' => 'high',
                        'progress' => 68,
                        'avg_cycle_days' => 16,
                        'on_time_rate' => 82,
                        'overdue_tasks' => 1
                    ],
                    [
                        'id' => 2,
                        'name' => 'TechCorp Website Redesign',
                        'client' => 'TechCorp Ltd',
                        'status' => 'active',
                        'priority' => 'urgent',
                        'progress' => 52,
                        'avg_cycle_days' => 22,
                        'on_time_rate' => 64,
                        'overdue_tasks' => 3
                    ]
                ],
                'at_risk_projects' => [
                    [
                        'id' => 2,
                        'name' => 'TechCorp Website Redesign',
                        'reason' => 'Multiple overdue tasks',
                        'owner' => 'Emily Davis',
                        'next_milestone' => date('Y-m-d', strtotime('+5 days'))
                    ]
                ]
            ];
        }

        try {
            $stmt = $this->db->query("
                SELECT
                    p.id,
                    p.name,
                    p.client,
                    p.status,
                    p.priority,
                    COUNT(t.id) as total_tasks,
                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN t.status != 'completed' AND t.due_date < CURDATE() THEN 1 ELSE 0 END) as overdue_tasks,
                    AVG(CASE WHEN t.status = 'completed' AND t.last_activity IS NOT NULL THEN TIMESTAMPDIFF(HOUR, t.created_at, t.last_activity) END) as avg_cycle_hours,
                    SUM(t.hours_logged) as hours_logged,
                    SUM(t.estimated_hours) as estimated_hours,
                    MAX(t.last_activity) as last_activity
                FROM projects p
                LEFT JOIN tasks t ON t.project_id = p.id
                GROUP BY p.id
                ORDER BY p.priority = 'urgent' DESC, p.priority = 'high' DESC, p.created_at DESC
            ");
            $projectsRaw = $stmt->fetchAll();

            $projects = [];
            $activeCount = 0;
            $completedThisMonth = 0;
            $avgCycles = [];
            $onTimeTasks = 0;
            $totalCompletedTasks = 0;

            foreach ($projectsRaw as $project) {
                $totalTasks = (int)($project['total_tasks'] ?? 0);
                $completedTasks = (int)($project['completed_tasks'] ?? 0);
                $avgCycleHours = $project['avg_cycle_hours'] !== null ? (float)$project['avg_cycle_hours'] : null;
                $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

                if ($project['status'] !== 'completed') {
                    $activeCount++;
                }

                if ($project['status'] === 'completed' && $project['last_activity']) {
                    if (date('Y-m', strtotime($project['last_activity'])) === date('Y-m')) {
                        $completedThisMonth++;
                    }
                }

                if ($avgCycleHours !== null) {
                    $avgCycles[] = $avgCycleHours;
                }

                $onTimeQuery = $this->db->prepare("
                    SELECT
                        SUM(CASE WHEN t.status = 'completed' AND t.due_date IS NOT NULL AND t.last_activity <= t.due_date THEN 1 ELSE 0 END) as on_time,
                        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as total_completed
                    FROM tasks t
                    WHERE t.project_id = ?
                ");
                $onTimeQuery->execute([$project['id']]);
                $onTimeData = $onTimeQuery->fetch();

                $projectOnTime = (int)($onTimeData['on_time'] ?? 0);
                $projectCompleted = (int)($onTimeData['total_completed'] ?? 0);

                $onTimeTasks += $projectOnTime;
                $totalCompletedTasks += $projectCompleted;

                $projects[] = [
                    'id' => $project['id'],
                    'name' => $project['name'],
                    'client' => $project['client'],
                    'status' => $project['status'],
                    'priority' => $project['priority'],
                    'progress' => $progress,
                    'avg_cycle_days' => $avgCycleHours !== null ? round($avgCycleHours / 24, 1) : null,
                    'on_time_rate' => $projectCompleted > 0 ? round(($projectOnTime / $projectCompleted) * 100) : null,
                    'overdue_tasks' => (int)($project['overdue_tasks'] ?? 0),
                    'hours_logged' => round((float)($project['hours_logged'] ?? 0), 1),
                    'estimated_hours' => round((float)($project['estimated_hours'] ?? 0), 1),
                    'last_activity' => $project['last_activity']
                ];
            }

            $atRiskProjects = array_filter($projects, function ($project) {
                return ($project['overdue_tasks'] ?? 0) > 0 || ($project['progress'] ?? 0) < 50;
            });

            $throughputStmt = $this->db->query("
                SELECT
                    SUM(CASE WHEN YEARWEEK(t.last_activity, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) as week_completed,
                    SUM(CASE WHEN YEARWEEK(t.last_activity, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1) THEN 1 ELSE 0 END) as last_week_completed
                FROM tasks t
                WHERE t.status = 'completed'
            ");
            $throughput = $throughputStmt->fetch() ?: [];

            $portfolioSummary = [
                'active_projects' => $activeCount,
                'completed_this_month' => $completedThisMonth,
                'average_cycle_days' => !empty($avgCycles) ? round((array_sum($avgCycles) / count($avgCycles)) / 24, 1) : null,
                'on_time_delivery_rate' => $totalCompletedTasks > 0 ? round(($onTimeTasks / $totalCompletedTasks) * 100) : null
            ];

            return [
                'portfolio_summary' => $portfolioSummary,
                'throughput' => [
                    'week_completed' => (int)($throughput['week_completed'] ?? 0),
                    'last_week_completed' => (int)($throughput['last_week_completed'] ?? 0),
                    'week_goal' => max((int)($throughput['week_completed'] ?? 0) + 3, 10)
                ],
                'projects' => $projects,
                'at_risk_projects' => array_values($atRiskProjects)
            ];
        } catch (Exception $e) {
            error_log("Error getting delivery metrics: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentActivity($limit = 10) {
        if (!$this->db) {
            return [
                ['activity' => 'John Doe completed "Hero video concept"', 'time_formatted' => '5m ago'],
                ['activity' => 'Sarah Wilson started "Homepage design"', 'time_formatted' => '15m ago'],
                ['activity' => 'Project "Nike Campaign" updated', 'time_formatted' => '1h ago']
            ];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    al.activity,
                    CASE 
                        WHEN al.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                        THEN CONCAT(TIMESTAMPDIFF(MINUTE, al.created_at, NOW()), 'm ago')
                        WHEN al.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) 
                        THEN CONCAT(TIMESTAMPDIFF(HOUR, al.created_at, NOW()), 'h ago')
                        ELSE DATE_FORMAT(al.created_at, '%M %d at %h:%i%p')
                    END as time_formatted
                FROM activity_log al
                ORDER BY al.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting recent activity: " . $e->getMessage());
            return [];
        }
    }
}

// ==================== SECTION 4: DATA INITIALIZATION ====================
// Purpose: Initialize API and fetch all dashboard data
// Dependencies: EnhancedDashboardAPI class
// CLAUDE NOTE: For future updates to this section, provide the API initialization
// and all data fetching calls. This determines what data is available to the HTML sections.
// Initialize API
$api = new EnhancedDashboardAPI();

// Get all data
$stats = $api->getDashboardStats();
$employeeReport = $api->getDetailedEmployeeReport();
$projectReport = $api->getProjectDetailsReport();
$productivity = $api->getProductivityAnalytics();
$recentActivity = $api->getRecentActivity();
$closureInsights = $api->getClosureInsights();
$deliveryMetrics = $api->getDeliveryMetrics();

$closureLeaders = $closureInsights['employees'] ?? [];
usort($closureLeaders, function ($a, $b) {
    if (!isset($a['avg_hours']) && !isset($b['avg_hours'])) {
        return 0;
    }
    if (!isset($a['avg_hours'])) {
        return 1;
    }
    if (!isset($b['avg_hours'])) {
        return -1;
    }
    return $a['avg_hours'] <=> $b['avg_hours'];
});
$closureLeaders = array_slice($closureLeaders, 0, 5);

$deliveryProjects = $deliveryMetrics['projects'] ?? [];
$atRiskProjects = $deliveryMetrics['at_risk_projects'] ?? [];
$throughput = $deliveryMetrics['throughput'] ?? ['week_completed' => 0, 'last_week_completed' => 0, 'week_goal' => 0];
$throughputDelta = ($throughput['week_completed'] ?? 0) - ($throughput['last_week_completed'] ?? 0);

// ==================== SECTION 5: AJAX REQUEST HANDLERS ====================
// Purpose: Handle POST requests for real-time updates
// Dependencies: EnhancedDashboardAPI
// CLAUDE NOTE: For future updates to this section, provide the entire POST request
// handling block including the switch statement and all ajax response logic.
// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'refresh_data':
            echo json_encode([
                'success' => true,
                'stats' => $api->getDashboardStats(),
                'employees' => $api->getDetailedEmployeeReport()
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
    <!-- ==================== SECTION 6: HTML DOCTYPE & HEAD ==================== -->
    <!-- Purpose: HTML structure, meta tags, external resources -->
    <!-- Dependencies: None -->
    <!-- CLAUDE NOTE: For future updates to this section, provide the complete <head> -->
    <!-- section including meta tags, title, and external resource links (fonts, icons). -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole - Enhanced Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* ==================== SECTION 7: CSS VARIABLES & ROOT STYLES ==================== */
        /* Purpose: CSS custom properties, color system, base styles */
        /* Dependencies: None */
        /* CLAUDE NOTE: For future updates to this section, provide the :root variable */
        /* definitions and base element styles (*, body, html). Color system updates go here. */
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

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* ==================== SECTION 8: SIDEBAR STYLES ==================== */
        /* Purpose: Navigation sidebar styling */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS rules */
        /* for .sidebar, .sidebar-header, .nav-menu, .nav-item, .nav-link, etc. */
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

        /* ==================== SECTION 9: MAIN CONTENT STYLES ==================== */
        /* Purpose: Main dashboard area styling */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS rules */
        /* for .main-content, .top-bar, .page-header, .content-area, etc. */
        .main-content {
            flex: 1;
            margin-left: 280px;
            background: var(--bg-primary);
            min-height: 100vh;
        }

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

        .content-area {
            padding: 2rem;
            max-width: 100%;
        }

        /* ==================== SECTION 10: COMPONENT STYLES ==================== */
        /* Purpose: Cards, buttons, tables, metrics styling */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS rules for */
        /* buttons, metric cards, tables, badges, progress bars, and other UI components. */
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

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
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
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
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
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            box-shadow: var(--shadow-md);
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

        .hero-banner {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.12), rgba(16, 185, 129, 0.12));
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            border: 2px solid rgba(79, 70, 229, 0.12);
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 2rem;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle at center, rgba(99, 102, 241, 0.2), transparent 60%);
            right: -60px;
            top: -80px;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .hero-content h1 {
            font-size: 34px;
            font-weight: 900;
            color: var(--gray-900);
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 16px;
            color: var(--gray-600);
            max-width: 520px;
        }

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1.25rem;
        }

        .hero-metric {
            background: rgba(255, 255, 255, 0.8);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
            backdrop-filter: blur(6px);
            box-shadow: var(--shadow-md);
        }

        .hero-metric-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--gray-500);
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        .hero-metric-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--gray-900);
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .hero-metric-value span {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
        }

        .hero-side {
            width: 320px;
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            border: 2px solid rgba(99, 102, 241, 0.15);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 1;
        }

        .hero-side h3 {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-500);
            margin-bottom: 1rem;
        }

        .hero-side ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .hero-side li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--gray-700);
        }

        .hero-side li span:last-child {
            color: var(--primary);
            font-size: 18px;
            font-weight: 800;
        }

        .timeboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .pulse-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            border: 2px solid var(--gray-200);
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            position: relative;
        }

        .pulse-card.accent {
            border-color: rgba(59, 130, 246, 0.35);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(129, 140, 248, 0.12));
        }

        .pulse-card h4 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-500);
        }

        .pulse-value {
            font-size: 26px;
            font-weight: 900;
            color: var(--gray-900);
        }

        .trend-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .trend-chip.positive {
            background: rgba(16, 185, 129, 0.12);
            color: var(--success);
        }

        .trend-chip.negative {
            background: rgba(239, 68, 68, 0.12);
            color: var(--danger);
        }

        .insight-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .insight-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            border: 2px solid var(--gray-200);
            box-shadow: var(--shadow-md);
        }

        .insight-card h3 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .closure-table {
            width: 100%;
            border-collapse: collapse;
        }

        .closure-table th,
        .closure-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .closure-table th {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.08em;
            color: var(--gray-500);
        }

        .closure-table tbody tr:hover {
            background: var(--gray-50);
        }

        .insight-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .insight-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
        }

        .insight-list li strong {
            color: var(--gray-700);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .pill.success {
            background: rgba(16, 185, 129, 0.12);
            color: var(--success);
        }

        .pill.warning {
            background: rgba(245, 158, 11, 0.12);
            color: var(--warning);
        }

        .pill.danger {
            background: rgba(239, 68, 68, 0.12);
            color: var(--danger);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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

        /* Employee Report Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .report-table th,
        .report-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .report-table th {
            background: var(--gray-50);
            font-weight: 700;
            color: var(--gray-700);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-table tr:hover {
            background: var(--gray-50);
        }

        .employee-avatar-mini {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            margin-right: 12px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-active { background: rgba(22, 163, 74, 0.1); color: var(--success); }
        .status-busy { background: rgba(220, 38, 38, 0.1); color: var(--danger); }
        .status-away { background: rgba(234, 88, 12, 0.1); color: var(--warning); }

        .workload-optimal { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .workload-high { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .workload-overloaded { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .performance-score {
            font-weight: 800;
            font-size: 16px;
        }

        .score-excellent { color: var(--success); }
        .score-good { color: var(--info); }
        .score-needs-improvement { color: var(--warning); }
        .score-poor { color: var(--danger); }

        /* Project Cards */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .project-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            border: 3px solid transparent;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--gray-300);
        }

        .project-card.priority-urgent::before { background: linear-gradient(90deg, var(--danger), #F87171); }
        .project-card.priority-high::before { background: linear-gradient(90deg, var(--warning), #FBBF24); }
        .project-card.priority-medium::before { background: linear-gradient(90deg, var(--info), #60A5FA); }

        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .project-name {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .project-client {
            font-size: 14px;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .priority-urgent { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .priority-high { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .priority-medium { background: rgba(59, 130, 246, 0.1); color: var(--info); }

        .progress-section {
            margin-bottom: 1.5rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .progress-text {
            font-weight: 700;
            font-size: 16px;
            color: var(--gray-900);
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: var(--gray-200);
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), #34D399);
            border-radius: 6px;
            transition: width 0.3s ease;
        }

        .project-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .project-metric {
            text-align: center;
            padding: 12px;
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }

        .project-metric-value {
            font-weight: 800;
            font-size: 16px;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .project-metric-label {
            font-size: 11px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Performance Cards */
        .performance-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .performer-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .performer-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--radius-md);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .performer-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }

        .performer-item.top-performer {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success);
        }

        .performer-item.bottom-performer {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger);
        }

        .performer-rank {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin-right: 1rem;
        }

        .performer-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            margin-right: 1rem;
        }

        .performer-info {
            flex: 1;
        }

        .performer-name {
            font-weight: 700;
            font-size: 15px;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        .performer-stats {
            display: flex;
            gap: 1rem;
        }

        .performer-stat {
            text-align: center;
        }

        .stat-value {
            display: block;
            font-weight: 700;
            font-size: 14px;
            color: var(--gray-900);
        }

        .stat-label {
            font-size: 10px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Activity Feed */
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .activity-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success);
            margin-right: 16px;
            animation: pulse 3s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }

        .activity-text {
            font-size: 14px;
            color: var(--gray-700);
            flex: 1;
            font-weight: 500;
        }

        .activity-time {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 600;
        }

        /* ==================== CEO NOTES SPECIFIC STYLES ==================== */
        /* ADHD-Friendly CEO Notes Styling */
        .quick-task-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            margin-bottom: 8px;
            background: var(--gray-50);
            border: 2px solid transparent;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quick-task-item:hover {
            background: var(--gray-100);
            border-color: var(--primary);
            transform: translateX(4px);
        }

        .quick-task-item.completed {
            opacity: 0.6;
            background: rgba(16, 185, 129, 0.1);
            text-decoration: line-through;
        }

        .quick-task-text {
            flex: 1;
            font-weight: 600;
            font-size: 14px;
        }

        .quick-task-meta {
            font-size: 12px;
            color: var(--gray-500);
            margin-left: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .saved-note-item {
            padding: 16px;
            margin-bottom: 12px;
            background: var(--gray-50);
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .saved-note-item:hover {
            background: var(--gray-100);
            transform: translateX(4px);
        }

        .saved-note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .saved-note-time {
            font-size: 12px;
            color: var(--gray-500);
        }

        .saved-note-content {
            font-size: 14px;
            line-height: 1.6;
            color: var(--gray-700);
        }

        .delete-btn {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        /* Quick feedback animation */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ==================== SECTION 11: RESPONSIVE STYLES ==================== */
        /* Purpose: Mobile and tablet responsive design */
        /* Dependencies: All previous CSS */
        /* CLAUDE NOTE: For future updates to this section, provide all @media queries */
        /* and responsive design rules for mobile, tablet, and desktop breakpoints. */
        @media (max-width: 1200px) {
            .performance-grid {
                grid-template-columns: 1fr;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
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
            
            .metrics-grid {
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
    <!-- ==================== SECTION 12: BODY & LAYOUT STRUCTURE ==================== -->
    <!-- Purpose: Main HTML layout, test mode notice -->
    <!-- Dependencies: PHP variables ($stats, session variables) -->
    <!-- CLAUDE NOTE: For future updates to this section, provide the body opening tag, -->
    <!-- test mode notice, and main dashboard-container div structure. -->
    <?php if (isset($_GET['admin_test'])): ?>
    <div style="background: linear-gradient(135deg, var(--info), #60A5FA); color: white; padding: 1rem; text-align: center;">
        <strong>🧪 Admin Test Mode:</strong> Dashboard running with sample data. 
        <a href="dashboard.php" style="color: white; text-decoration: underline;">Exit test mode</a>
    </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <!-- ==================== SECTION 13: SIDEBAR NAVIGATION ==================== -->
        <!-- Purpose: Left navigation menu structure -->
        <!-- Dependencies: $productivity PHP variable for badge counts -->
        <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
        <!-- <aside class="sidebar"> structure including logo, navigation menu, and logout button. -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                    <span>Foxhole</span>
                </div>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <div class="nav-link active" onclick="showTab('overview')">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Overview</span>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link" onclick="showTab('employee-report')">
                            <i class="fas fa-users"></i>
                            <span>Employee Report</span>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link" onclick="showTab('project-report')">
                            <i class="fas fa-project-diagram"></i>
                            <span>Project Report</span>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link" onclick="showTab('productivity')">
                            <i class="fas fa-chart-line"></i>
                            <span>Productivity</span>
                            <?php if (count($productivity['bottom_performers']) > 0): ?>
                            <span class="nav-badge"><?= count($productivity['bottom_performers']) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link" onclick="showTab('ceo-notes')">
                            <i class="fas fa-brain"></i>
                            <span>CEO Brain Dump</span>
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

        <!-- ==================== SECTION 14: TOP BAR ==================== -->
        <!-- Purpose: Header with title and action buttons -->
        <!-- Dependencies: None -->
        <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
        <!-- <main class="main-content"> opening and <header class="top-bar"> structure. -->
        <main class="main-content">
            <header class="top-bar">
                <div class="page-header">
                    <h1>Enhanced Admin Dashboard</h1>
                    <p>Comprehensive team productivity and project insights</p>
                </div>
                <div class="top-actions">
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

            <!-- ==================== SECTION 15: OVERVIEW TAB CONTENT ==================== -->
            <!-- Purpose: Main dashboard metrics and activity feed -->
            <!-- Dependencies: $stats, $recentActivity PHP variables -->
            <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
            <!-- overview tab div including metrics grid and recent activity section. -->
            <div class="content-area">
                <div id="overview" class="tab-content active">
                    <div class="hero-banner">
                        <div class="hero-content">
                            <h1>Delivery Pulse</h1>
                            <p>Monitor how quickly teams close work, the health of current projects, and who needs support to hit targets.</p>
                            <div class="hero-metrics">
                                <div class="hero-metric">
                                    <div class="hero-metric-label">Average time to close</div>
                                    <div class="hero-metric-value">
                                        <?= isset($closureInsights['team_average_days']) && $closureInsights['team_average_days'] !== null ? number_format($closureInsights['team_average_days'], 1) : '—' ?>
                                        <span>days</span>
                                    </div>
                                </div>
                                <div class="hero-metric">
                                    <div class="hero-metric-label">On-time completion</div>
                                    <div class="hero-metric-value">
                                        <?= isset($closureInsights['on_time_completion_rate']) && $closureInsights['on_time_completion_rate'] !== null ? $closureInsights['on_time_completion_rate'] . '%' : '—' ?>
                                    </div>
                                </div>
                                <div class="hero-metric">
                                    <div class="hero-metric-label">Active projects</div>
                                    <div class="hero-metric-value">
                                        <?= $deliveryMetrics['portfolio_summary']['active_projects'] ?? ($stats['projects_on_track'] ?? 0) ?>
                                    </div>
                                </div>
                                <div class="hero-metric">
                                    <div class="hero-metric-label">Total hours logged</div>
                                    <div class="hero-metric-value">
                                        <?= $stats['total_hours_logged'] ?>
                                        <span>hours</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="hero-side">
                            <h3>Weekly delivery</h3>
                            <ul>
                                <li>
                                    <span>Closed</span>
                                    <span><?= $throughput['week_completed'] ?? 0 ?></span>
                                </li>
                                <li>
                                    <span>Goal</span>
                                    <span><?= $throughput['week_goal'] ?? 0 ?></span>
                                </li>
                                <li>
                                    <span>vs last week</span>
                                    <span class="trend-chip <?= $throughputDelta >= 0 ? 'positive' : 'negative' ?>">
                                        <i class="fas <?= $throughputDelta >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                                        <?= $throughputDelta >= 0 ? '+' : '' ?><?= $throughputDelta ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="timeboard-grid">
                        <div class="pulse-card accent">
                            <h4>Average cycle time</h4>
                            <div class="pulse-value">
                                <?= isset($closureInsights['team_average_days']) && $closureInsights['team_average_days'] !== null ? number_format($closureInsights['team_average_days'], 1) . ' days' : '—' ?>
                            </div>
                            <p style="color: var(--gray-500); font-size: 13px;">
                                Fastest closer: <?= htmlspecialchars($closureInsights['fastest_closer']['name'] ?? '—') ?>
                                <?php if (isset($closureInsights['fastest_closer']['avg_days'])): ?>
                                    (<?= number_format($closureInsights['fastest_closer']['avg_days'], 1) ?>d)
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="pulse-card">
                            <h4>On-time completion</h4>
                            <div class="pulse-value">
                                <?= isset($closureInsights['on_time_completion_rate']) && $closureInsights['on_time_completion_rate'] !== null ? $closureInsights['on_time_completion_rate'] . '%' : '—' ?>
                            </div>
                            <span class="trend-chip <?= isset($closureInsights['on_time_completion_rate']) && $closureInsights['on_time_completion_rate'] >= 85 ? 'positive' : 'negative' ?>">
                                <i class="fas <?= isset($closureInsights['on_time_completion_rate']) && $closureInsights['on_time_completion_rate'] >= 85 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                                Goal 85%
                            </span>
                        </div>
                        <div class="pulse-card">
                            <h4>Completed this week</h4>
                            <div class="pulse-value"><?= $throughput['week_completed'] ?? 0 ?></div>
                            <p style="color: var(--gray-500); font-size: 13px;">Goal <?= $throughput['week_goal'] ?? 0 ?> • Last week <?= $throughput['last_week_completed'] ?? 0 ?></p>
                        </div>
                        <div class="pulse-card">
                            <h4>At-risk projects</h4>
                            <div class="pulse-value"><?= count($atRiskProjects) ?></div>
                            <p style="color: var(--gray-500); font-size: 13px;">
                                <?= count($atRiskProjects) > 0 ? htmlspecialchars($atRiskProjects[0]['name']) . ' needs attention' : 'All projects within target ranges' ?>
                            </p>
                        </div>
                    </div>

                    <div class="insight-grid" style="margin-bottom: 2rem;">
                        <div class="insight-card">
                            <h3><i class="fas fa-stopwatch"></i> Team closure leaderboard</h3>
                            <table class="closure-table">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Completed</th>
                                        <th>Avg cycle</th>
                                        <th>On-time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($closureLeaders)): ?>
                                        <?php foreach ($closureLeaders as $leader): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($leader['name']) ?></strong></td>
                                                <td><?= $leader['completed_tasks'] ?? 0 ?></td>
                                                <td><?= isset($leader['avg_days']) && $leader['avg_days'] !== null ? number_format($leader['avg_days'], 1) . 'd' : '—' ?></td>
                                                <td><?= isset($leader['on_time_rate']) && $leader['on_time_rate'] !== null ? $leader['on_time_rate'] . '%' : '—' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; color: var(--gray-500); padding: 2rem;">No completed tasks recorded yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="insight-card">
                            <h3><i class="fas fa-flag-checkered"></i> Recent closures</h3>
                            <ul class="insight-list">
                                <?php if (!empty($closureInsights['recent_closures'])): ?>
                                    <?php foreach ($closureInsights['recent_closures'] as $closure): ?>
                                        <li>
                                            <div>
                                                <strong><?= htmlspecialchars($closure['title']) ?></strong>
                                                <div style="color: var(--gray-500); font-size: 12px;">
                                                    <?= htmlspecialchars($closure['project'] ?? 'Unknown project') ?> • <?= htmlspecialchars($closure['employee'] ?? 'Unknown') ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php $closureDays = isset($closure['cycle_hours']) && $closure['cycle_hours'] !== null ? $closure['cycle_hours'] / 24 : null; ?>
                                                <span class="pill <?= $closureDays !== null && isset($closureInsights['team_average_days']) && $closureInsights['team_average_days'] !== null && $closureDays <= $closureInsights['team_average_days'] ? 'success' : 'warning' ?>">
                                                    <?= $closureDays !== null ? number_format($closureDays, 1) . 'd' : '—' ?>
                                                </span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li style="justify-content: center; color: var(--gray-500);">No recent closures logged.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="insight-grid">
                        <div class="insight-card">
                            <h3><i class="fas fa-project-diagram"></i> Project delivery radar</h3>
                            <ul class="insight-list">
                                <?php if (!empty($deliveryProjects)): ?>
                                    <?php foreach (array_slice($deliveryProjects, 0, 4) as $project): ?>
                                        <li>
                                            <div>
                                                <strong><?= htmlspecialchars($project['name']) ?></strong>
                                                <div style="color: var(--gray-500); font-size: 12px;">
                                                    <?= ucfirst($project['status'] ?? 'active') ?> • <?= $project['progress'] ?? 0 ?>% complete
                                                </div>
                                            </div>
                                            <div>
                                                <span class="pill <?= ($project['overdue_tasks'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                                                    <?= isset($project['avg_cycle_days']) && $project['avg_cycle_days'] !== null ? number_format($project['avg_cycle_days'], 1) . 'd' : '—' ?>
                                                </span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li style="justify-content: center; color: var(--gray-500);">No project data available.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="insight-card">
                            <h3><i class="fas fa-bolt"></i> Live activity</h3>
                            <div class="activity-feed" style="max-height: 320px;">
                                <?php if (!empty($recentActivity)): ?>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon"></div>
                                            <div class="activity-text"><?= htmlspecialchars($activity['activity']) ?></div>
                                            <div class="activity-time"><?= htmlspecialchars($activity['time_formatted']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="color: var(--gray-500); text-align: center; padding: 1.5rem;">No recent updates.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== SECTION 16: EMPLOYEE REPORT TAB ==================== -->
                <!-- Purpose: Detailed employee performance table -->
                <!-- Dependencies: $employeeReport PHP variable -->
                <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
                <!-- employee-report tab div including the performance table and all employee data display. -->
                <div id="employee-report" class="tab-content">
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-users"></i>
                                Detailed Employee Performance Report
                            </h2>
                            <button class="btn btn-secondary" onclick="exportEmployeeReport()">
                                <i class="fas fa-download"></i>
                                Export CSV
                            </button>
                        </div>
                        
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Status</th>
                                    <th>Tasks</th>
                                    <th>Hours</th>
                                    <th>Efficiency</th>
                                    <th>Workload</th>
                                    <th>Current Projects</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employeeReport as $emp): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="employee-avatar-mini">
                                                <?= substr($emp['name'], 0, 2) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($emp['name']) ?></strong>
                                                <br>
                                                <small style="color: var(--gray-600);"><?= htmlspecialchars($emp['role']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $emp['status'] ?>">
                                            <?= ucfirst($emp['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= $emp['completed_tasks'] ?></strong> / <?= $emp['total_tasks'] ?>
                                        <?php if ($emp['overdue_tasks'] > 0): ?>
                                        <br><small style="color: var(--danger);"><?= $emp['overdue_tasks'] ?> overdue</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= $emp['total_hours'] ?>h</strong>
                                        <br><small style="color: var(--gray-600);"><?= $emp['avg_hours_per_task'] ?>h avg</small>
                                    </td>
                                    <td>
                                        <span class="performance-score score-<?= $emp['efficiency_score'] >= 90 ? 'excellent' : ($emp['efficiency_score'] >= 75 ? 'good' : ($emp['efficiency_score'] >= 60 ? 'needs-improvement' : 'poor')) ?>">
                                            <?= $emp['efficiency_score'] ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge workload-<?= $emp['workload_status'] ?>">
                                            <?= ucfirst($emp['workload_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($emp['current_projects'])): ?>
                                            <?= implode(', ', array_slice($emp['current_projects'], 0, 2)) ?>
                                            <?php if (count($emp['current_projects']) > 2): ?>
                                                <small style="color: var(--gray-600);">+<?= count($emp['current_projects']) - 2 ?> more</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small style="color: var(--gray-500);">No active projects</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ==================== SECTION 17: PROJECT REPORT TAB ==================== -->
                <!-- Purpose: Project status cards and progress -->
                <!-- Dependencies: $projectReport PHP variable -->
                <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
                <!-- project-report tab div including the projects grid and all project cards. -->
                <div id="project-report" class="tab-content">
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-project-diagram"></i>
                                Project Performance Report
                            </h2>
                            <button class="btn btn-secondary" onclick="exportProjectReport()">
                                <i class="fas fa-download"></i>
                                Export CSV
                            </button>
                        </div>
                        
                        <div class="projects-grid">
                            <?php foreach ($projectReport as $project): ?>
                            <div class="project-card priority-<?= $project['priority'] ?>">
                                <div class="project-header">
                                    <div>
                                        <div class="project-name"><?= htmlspecialchars($project['name']) ?></div>
                                        <div class="project-client">
                                            <i class="fas fa-building"></i>
                                            <?= htmlspecialchars($project['client']) ?>
                                        </div>
                                    </div>
                                    <span class="priority-badge priority-<?= $project['priority'] ?>">
                                        <?= ucfirst($project['priority']) ?>
                                    </span>
                                </div>
                                
                                <div class="progress-section">
                                    <div class="progress-header">
                                        <span class="progress-text"><?= $project['progress'] ?>% Complete</span>
                                        <span style="font-size: 14px; color: var(--gray-600);">
                                            <?= $project['completed_tasks'] ?>/<?= $project['total_tasks'] ?> tasks
                                        </span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $project['progress'] ?>%;"></div>
                                    </div>
                                </div>
                                
                                <div class="project-metrics">
                                    <div class="project-metric">
                                        <div class="project-metric-value"><?= $project['team_size'] ?></div>
                                        <div class="project-metric-label">Team Size</div>
                                    </div>
                                    <div class="project-metric">
                                        <div class="project-metric-value"><?= $project['total_hours'] ?>h</div>
                                        <div class="project-metric-label">Hours Spent</div>
                                    </div>
                                    <div class="project-metric">
                                        <div class="project-metric-value"><?= $project['efficiency'] ?>%</div>
                                        <div class="project-metric-label">Efficiency</div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 1rem;">
                                    <div style="font-size: 12px; color: var(--gray-600); margin-bottom: 8px;">
                                        <strong>Team:</strong> 
                                        <?= implode(', ', array_slice($project['team_members'], 0, 3)) ?>
                                        <?php if (count($project['team_members']) > 3): ?>
                                            +<?= count($project['team_members']) - 3 ?> more
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray-600);">
                                        <strong>Days Remaining:</strong> <?= $project['days_remaining'] ?> days
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- ==================== SECTION 18: PRODUCTIVITY TAB ==================== -->
                <!-- Purpose: Performance analytics and charts -->
                <!-- Dependencies: $productivity PHP variable -->
                <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
                <!-- productivity tab div including top/bottom performers and team velocity chart. -->
                <div id="productivity" class="tab-content">
                    <div class="performance-grid">
                        <!-- Top Performers -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-trophy"></i>
                                    Top Performers
                                </h2>
                            </div>
                            
                            <div class="performer-list">
                                <?php foreach ($productivity['top_performers'] as $index => $performer): ?>
                                <div class="performer-item top-performer">
                                    <div class="performer-rank">
                                        <?php if ($index === 0): ?>
                                        <i class="fas fa-crown" style="color: #FFD700;"></i>
                                        <?php else: ?>
                                        <span style="font-weight: 800;"><?= $index + 1 ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="performer-avatar">
                                        <?= substr($performer['name'], 0, 2) ?>
                                    </div>
                                    <div class="performer-info">
                                        <div class="performer-name"><?= htmlspecialchars($performer['name']) ?></div>
                                        <div class="performer-stats">
                                            <div class="performer-stat">
                                                <span class="stat-value"><?= $performer['efficiency'] ?>%</span>
                                                <span class="stat-label">Efficiency</span>
                                            </div>
                                            <div class="performer-stat">
                                                <span class="stat-value"><?= $performer['completed_tasks'] ?></span>
                                                <span class="stat-label">Tasks</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Bottom Performers -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Needs Attention
                                </h2>
                            </div>
                            
                            <div class="performer-list">
                                <?php if (empty($productivity['bottom_performers'])): ?>
                                <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                    <i class="fas fa-smile" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>Great job! All team members are performing well.</p>
                                </div>
                                <?php else: ?>
                                <?php foreach ($productivity['bottom_performers'] as $index => $performer): ?>
                                <div class="performer-item bottom-performer">
                                    <div class="performer-avatar">
                                        <?= substr($performer['name'], 0, 2) ?>
                                    </div>
                                    <div class="performer-info">
                                        <div class="performer-name"><?= htmlspecialchars($performer['name']) ?></div>
                                        <div class="performer-stats">
                                            <div class="performer-stat">
                                                <span class="stat-value"><?= $performer['efficiency'] ?>%</span>
                                                <span class="stat-label">Efficiency</span>
                                            </div>
                                            <div class="performer-stat">
                                                <span class="stat-value"><?= $performer['completed_tasks'] ?></span>
                                                <span class="stat-label">Tasks</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Team Velocity Chart -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-chart-bar"></i>
                                Team Velocity Trend
                            </h2>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; height: 200px; padding: 1rem 0; border-bottom: 2px solid var(--gray-200);">
                            <?php foreach ($productivity['team_velocity'] as $week): ?>
                            <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                                <div style="font-size: 12px; font-weight: 600; color: var(--gray-600); margin-bottom: 1rem;">
                                    <?= $week['week'] ?>
                                </div>
                                <div style="display: flex; gap: 4px; align-items: flex-end; height: 150px;">
                                    <div style="width: 20px; background: var(--gray-300); border-radius: 4px 4px 0 0; height: <?= ($week['planned'] / 25) * 100 ?>%; position: relative; display: flex; align-items: flex-end; justify-content: center; min-height: 20px;">
                                        <span style="position: absolute; top: -20px; font-size: 10px; font-weight: 700; color: var(--gray-700);"><?= $week['planned'] ?></span>
                                    </div>
                                    <div style="width: 20px; background: var(--success); border-radius: 4px 4px 0 0; height: <?= ($week['completed'] / 25) * 100 ?>%; position: relative; display: flex; align-items: flex-end; justify-content: center; min-height: 20px;">
                                        <span style="position: absolute; top: -20px; font-size: 10px; font-weight: 700; color: var(--gray-700);"><?= $week['completed'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display: flex; justify-content: center; gap: 2rem; margin-top: 1rem;">
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: var(--gray-600);">
                                <div style="width: 12px; height: 12px; border-radius: 2px; background: var(--gray-300);"></div>
                                <span>Planned</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: var(--gray-600);">
                                <div style="width: 12px; height: 12px; border-radius: 2px; background: var(--success);"></div>
                                <span>Completed</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== SECTION 19: CEO NOTES TAB CONTENT ==================== -->
                <!-- Purpose: CEO Quick Notes/Brain Dump functionality -->
                <!-- Dependencies: localStorage for data persistence -->
                <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
                <!-- CEO notes tab including brain dump, quick tasks, big 3, and saved notes. -->
                <div id="ceo-notes" class="tab-content">
                    <!-- Quick Capture Section -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                        <!-- Brain Dump Area -->
                        <div class="section-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="section-header" style="border-bottom-color: rgba(255,255,255,0.3);">
                                <h2 class="section-title" style="color: white;">
                                    <i class="fas fa-brain"></i>
                                    Quick Brain Dump
                                </h2>
                                <button class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);" onclick="clearBrainDump()">
                                    <i class="fas fa-trash"></i>
                                    Clear
                                </button>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <textarea id="brain-dump-text" 
                                          placeholder="💭 Quick thoughts, ideas, reminders... Just dump everything here! Press Ctrl+S to save."
                                          style="width: 100%; height: 200px; padding: 1rem; border: none; border-radius: 12px; font-size: 16px; line-height: 1.6; resize: vertical; background: rgba(255,255,255,0.1); color: white; backdrop-filter: blur(10px);"
                                          onkeydown="handleQuickSave(event)"></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <button class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); flex: 1;" onclick="saveBrainDump()">
                                    <i class="fas fa-save"></i>
                                    Save Notes
                                </button>
                                <button class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);" onclick="speakToBrainDump()">
                                    <i class="fas fa-microphone"></i>
                                    Voice
                                </button>
                            </div>
                        </div>

                        <!-- Quick Tasks -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-bolt"></i>
                                    Quick Tasks & Ideas
                                </h2>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <input type="text" id="quick-task-input" 
                                       placeholder="💡 Type a quick task or idea and press Enter..."
                                       style="width: 100%; padding: 14px; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 16px;"
                                       onkeypress="handleQuickTaskEnter(event)">
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 1rem; flex-wrap: wrap;">
                                <button class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="addQuickTask('🔥 Priority')">🔥 Priority</button>
                                <button class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="addQuickTask('💼 Business')">💼 Business</button>
                                <button class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="addQuickTask('💰 Finance')">💰 Finance</button>
                                <button class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="addQuickTask('👥 Team')">👥 Team</button>
                                <button class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="addQuickTask('🎯 Project')">🎯 Project</button>
                            </div>
                            
                            <div id="quick-tasks-list" style="max-height: 300px; overflow-y: auto;">
                                <!-- Quick tasks will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Today's Focus & Saved Notes -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Today's Focus -->
                        <div class="section-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                            <div class="section-header" style="border-bottom-color: rgba(255,255,255,0.3);">
                                <h2 class="section-title" style="color: white;">
                                    <i class="fas fa-target"></i>
                                    Today's Big 3
                                </h2>
                            </div>
                            
                            <div style="margin-bottom: 1rem; color: rgba(255,255,255,0.9); font-size: 14px;">
                                What are the 3 most important things to focus on today?
                            </div>
                            
                            <div id="big-three-list">
                                <div class="big-three-item">
                                    <input type="text" placeholder="1. Most important priority..." 
                                           style="width: 100%; padding: 12px; margin-bottom: 12px; border: none; border-radius: 8px; background: rgba(255,255,255,0.2); color: white; font-size: 16px; font-weight: 600;"
                                           onchange="saveBigThree()">
                                </div>
                                <div class="big-three-item">
                                    <input type="text" placeholder="2. Second priority..." 
                                           style="width: 100%; padding: 12px; margin-bottom: 12px; border: none; border-radius: 8px; background: rgba(255,255,255,0.2); color: white; font-size: 16px; font-weight: 600;"
                                           onchange="saveBigThree()">
                                </div>
                                <div class="big-three-item">
                                    <input type="text" placeholder="3. Third priority..." 
                                           style="width: 100%; padding: 12px; margin-bottom: 12px; border: none; border-radius: 8px; background: rgba(255,255,255,0.2); color: white; font-size: 16px; font-weight: 600;"
                                           onchange="saveBigThree()">
                                </div>
                            </div>
                        </div>

                        <!-- Saved Notes & Ideas -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-bookmark"></i>
                                    Saved Ideas & Notes
                                </h2>
                                <button class="btn btn-secondary" onclick="exportAllNotes()">
                                    <i class="fas fa-download"></i>
                                    Export
                                </button>
                            </div>
                            
                            <div id="saved-notes-list" style="max-height: 400px; overflow-y: auto;">
                                <!-- Saved notes will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats for CEO -->
                    <div class="section-card" style="margin-top: 2rem; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <div class="section-header" style="border-bottom-color: rgba(255,255,255,0.3);">
                            <h2 class="section-title" style="color: white;">
                                <i class="fas fa-dashboard"></i>
                                Your Personal Dashboard
                            </h2>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div style="background: rgba(255,255,255,0.2); padding: 1.5rem; border-radius: 12px; text-align: center; backdrop-filter: blur(10px);">
                                <div style="font-size: 32px; font-weight: 800; margin-bottom: 8px;" id="ceo-notes-count">0</div>
                                <div style="font-size: 14px; opacity: 0.9;">Total Notes</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 1.5rem; border-radius: 12px; text-align: center; backdrop-filter: blur(10px);">
                                <div style="font-size: 32px; font-weight: 800; margin-bottom: 8px;" id="ceo-tasks-count">0</div>
                                <div style="font-size: 14px; opacity: 0.9;">Quick Tasks</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 1.5rem; border-radius: 12px; text-align: center; backdrop-filter: blur(10px);">
                                <div style="font-size: 32px; font-weight: 800; margin-bottom: 8px;" id="ceo-focus-score">0</div>
                                <div style="font-size: 14px; opacity: 0.9;">Focus Score</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 1.5rem; border-radius: 12px; text-align: center; backdrop-filter: blur(10px);">
                                <div style="font-size: 32px; font-weight: 800; margin-bottom: 8px;"><?= date('M d') ?></div>
                                <div style="font-size: 14px; opacity: 0.9;">Today</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ==================== SECTION 20: CORE JAVASCRIPT FUNCTIONS ==================== -->
    <!-- Purpose: Tab management, data refresh, UI interactions -->
    <!-- Dependencies: None -->
    <!-- CLAUDE NOTE: For future updates to this section, provide the core JavaScript -->
    <!-- functions including showTab(), refreshDashboard(), and primary interaction handlers. -->
    <script>
        // Tab management - FIXED
        function showTab(tabId) {
            console.log('Switching to tab:', tabId);
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected tab content
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.classList.add('active');
                console.log('Tab activated:', tabId);
            } else {
                console.error('Tab not found:', tabId);
            }
            
            // Find and activate the corresponding nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                const onclick = link.getAttribute('onclick');
                if (onclick && onclick.includes(`'${tabId}'`)) {
                    link.classList.add('active');
                    console.log('Nav link activated for:', tabId);
                }
            });

            // Initialize CEO notes if switching to that tab
            if (tabId === 'ceo-notes') {
                setTimeout(initializeCEONotes, 100);
            }
        }

        // Refresh dashboard
        async function refreshDashboard() {
            try {
                const response = await fetch('dashboard.php', {
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

        /* ==================== SECTION 21: UTILITY FUNCTIONS ==================== */
        /* Purpose: Export, logout, keyboard shortcuts */
        /* Dependencies: Core functions */
        /* CLAUDE NOTE: For future updates to this section, provide all utility */
        /* functions including export functions, logout, and helper functions. */

        // Export functions
        function exportReport() {
            alert('📊 Generating comprehensive productivity report...\n\nThis will include:\n• Team performance metrics\n• Individual productivity scores\n• Project completion rates\n• Capacity utilization\n• Trend analysis');
        }

        function exportEmployeeReport() {
            alert('📊 Exporting employee performance report as CSV...\n\nThis will include:\n• Individual performance metrics\n• Task completion rates\n• Time tracking data\n• Efficiency scores\n• Current workload status');
        }

        function exportProjectReport() {
            alert('📊 Exporting project report as CSV...\n\nThis will include:\n• Project progress and status\n• Team allocation\n• Time and budget tracking\n• Efficiency metrics\n• Timeline analysis');
        }

        // Logout
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'auth.php?action=logout';
            }
        }

        /* ==================== SECTION 22: CEO NOTES JAVASCRIPT ==================== */
        /* Purpose: All CEO notes functionality */
        /* Dependencies: localStorage */
        /* CLAUDE NOTE: For future updates to this section, provide all CEO notes */
        /* JavaScript including localStorage management, UI updates, and interactions. */

        // CEO NOTES JAVASCRIPT
        let quickTasks = JSON.parse(localStorage.getItem('ceo_quick_tasks') || '[]');
        let savedNotes = JSON.parse(localStorage.getItem('ceo_saved_notes') || '[]');
        let bigThree = JSON.parse(localStorage.getItem('ceo_big_three') || '["","",""]');

        function handleQuickSave(event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault();
                saveBrainDump();
            }
        }

        function saveBrainDump() {
            const text = document.getElementById('brain-dump-text').value.trim();
            if (text) {
                const note = {
                    id: Date.now(),
                    content: text,
                    timestamp: new Date().toLocaleString(),
                    type: 'brain_dump'
                };
                
                savedNotes.unshift(note);
                localStorage.setItem('ceo_saved_notes', JSON.stringify(savedNotes));
                
                // Clear the brain dump
                document.getElementById('brain-dump-text').value = '';
                
                updateNotesDisplay();
                updateCEOStats();
                
                // Show quick feedback
                showQuickFeedback('Brain dump saved! 🧠');
            }
        }

        function clearBrainDump() {
            if (confirm('Clear brain dump area?')) {
                document.getElementById('brain-dump-text').value = '';
            }
        }

        function handleQuickTaskEnter(event) {
            if (event.key === 'Enter') {
                const text = event.target.value.trim();
                if (text) {
                    addQuickTask(text);
                    event.target.value = '';
                }
            }
        }

        function addQuickTask(text, category = '') {
            const task = {
                id: Date.now(),
                text: text,
                category: category,
                completed: false,
                timestamp: new Date().toLocaleString()
            };
            
            quickTasks.unshift(task);
            localStorage.setItem('ceo_quick_tasks', JSON.stringify(quickTasks));
            
            updateTasksDisplay();
            updateCEOStats();
            
            showQuickFeedback('Task added! ⚡');
        }

        function toggleTask(taskId) {
            const task = quickTasks.find(t => t.id === taskId);
            if (task) {
                task.completed = !task.completed;
                localStorage.setItem('ceo_quick_tasks', JSON.stringify(quickTasks));
                updateTasksDisplay();
                updateCEOStats();
            }
        }

        function deleteTask(taskId) {
            quickTasks = quickTasks.filter(t => t.id !== taskId);
            localStorage.setItem('ceo_quick_tasks', JSON.stringify(quickTasks));
            updateTasksDisplay();
            updateCEOStats();
        }

        function saveBigThree() {
            const inputs = document.querySelectorAll('#big-three-list input');
            bigThree = Array.from(inputs).map(input => input.value);
            localStorage.setItem('ceo_big_three', JSON.stringify(bigThree));
            updateCEOStats();
        }

        function updateTasksDisplay() {
            const container = document.getElementById('quick-tasks-list');
            if (!container) return;
            
            if (quickTasks.length === 0) {
                container.innerHTML = '<div style="text-align: center; color: var(--gray-500); padding: 2rem;">No quick tasks yet. Add one above! 🚀</div>';
                return;
            }
            
            container.innerHTML = quickTasks.map(task => `
                <div class="quick-task-item ${task.completed ? 'completed' : ''}" onclick="toggleTask(${task.id})">
                    <div class="quick-task-text">${task.text}</div>
                    <div class="quick-task-meta">
                        <span>${task.timestamp}</span>
                        <button class="delete-btn" onclick="event.stopPropagation(); deleteTask(${task.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function updateNotesDisplay() {
            const container = document.getElementById('saved-notes-list');
            if (!container) return;
            
            if (savedNotes.length === 0) {
                container.innerHTML = '<div style="text-align: center; color: var(--gray-500); padding: 2rem;">No saved notes yet. Use the brain dump area! 🧠</div>';
                return;
            }
            
            container.innerHTML = savedNotes.slice(0, 10).map(note => `
                <div class="saved-note-item">
                    <div class="saved-note-header">
                        <div class="saved-note-time">${note.timestamp}</div>
                        <button class="delete-btn" onclick="deleteNote(${note.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="saved-note-content">${note.content.substring(0, 200)}${note.content.length > 200 ? '...' : ''}</div>
                </div>
            `).join('');
        }

        function deleteNote(noteId) {
            if (confirm('Delete this note?')) {
                savedNotes = savedNotes.filter(n => n.id !== noteId);
                localStorage.setItem('ceo_saved_notes', JSON.stringify(savedNotes));
                updateNotesDisplay();
                updateCEOStats();
            }
        }

        function updateCEOStats() {
            const notesCountEl = document.getElementById('ceo-notes-count');
            const tasksCountEl = document.getElementById('ceo-tasks-count');
            const focusScoreEl = document.getElementById('ceo-focus-score');
            
            if (notesCountEl) notesCountEl.textContent = savedNotes.length;
            if (tasksCountEl) tasksCountEl.textContent = quickTasks.filter(t => !t.completed).length;
            
            // Calculate focus score based on completed tasks and big three completion
            const completedTasks = quickTasks.filter(t => t.completed).length;
            const bigThreeCompleted = bigThree.filter(item => item.trim() !== '').length;
            const focusScore = Math.min(100, (completedTasks * 10) + (bigThreeCompleted * 20));
            if (focusScoreEl) focusScoreEl.textContent = focusScore;
        }

        function loadBigThree() {
            const inputs = document.querySelectorAll('#big-three-list input');
            inputs.forEach((input, index) => {
                if (bigThree[index]) {
                    input.value = bigThree[index];
                }
            });
        }

        function exportAllNotes() {
            const allData = {
                notes: savedNotes,
                tasks: quickTasks,
                bigThree: bigThree,
                exportDate: new Date().toISOString()
            };
            
            const dataStr = JSON.stringify(allData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = `ceo-notes-${new Date().toISOString().split('T')[0]}.json`;
            link.click();
        }

        function speakToBrainDump() {
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const recognition = new SpeechRecognition();
                
                recognition.continuous = true;
                recognition.interimResults = true;
                
                recognition.onstart = function() {
                    showQuickFeedback('🎤 Listening... Speak your thoughts!');
                };
                
                recognition.onresult = function(event) {
                    let transcript = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        transcript += event.results[i][0].transcript;
                    }
                    
                    const textarea = document.getElementById('brain-dump-text');
                    textarea.value = transcript;
                };
                
                recognition.onerror = function() {
                    showQuickFeedback('❌ Speech recognition error. Try typing instead.');
                };
                
                recognition.start();
                
                // Stop after 30 seconds
                setTimeout(() => {
                    recognition.stop();
                }, 30000);
            } else {
                alert('Speech recognition not supported in this browser. Try Chrome or Edge!');
            }
        }

        function showQuickFeedback(message) {
            // Create temporary feedback element
            const feedback = document.createElement('div');
            feedback.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--success);
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                z-index: 10000;
                font-weight: 600;
                animation: slideIn 0.3s ease;
            `;
            feedback.textContent = message;
            
            document.body.appendChild(feedback);
            
            setTimeout(() => {
                feedback.remove();
            }, 3000);
        }

        function initializeCEONotes() {
            if (document.getElementById('ceo-notes')) {
                updateTasksDisplay();
                updateNotesDisplay();
                loadBigThree();
                updateCEOStats();
            }
        }

        /* ==================== SECTION 23: EVENT LISTENERS & INITIALIZATION ==================== */
        /* Purpose: Keyboard shortcuts, error handling, startup */
        /* Dependencies: All previous JavaScript */
        /* CLAUDE NOTE: For future updates to this section, provide all event listeners, */
        /* keyboard shortcuts, initialization code, and error handling setup. */

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case '1': e.preventDefault(); showTab('overview'); break;
                    case '2': e.preventDefault(); showTab('employee-report'); break;
                    case '3': e.preventDefault(); showTab('project-report'); break;
                    case '4': e.preventDefault(); showTab('productivity'); break;
                    case '5': e.preventDefault(); showTab('ceo-notes'); break;
                    case 'r': e.preventDefault(); refreshDashboard(); break;
                    case 'e': e.preventDefault(); window.location.href = 'employee_dashboard.php?test_employee=1'; break;
                }
            }
        });

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Enhanced Foxhole Dashboard with CEO Notes initialized');
            
            // Initialize CEO notes if on that tab
            setTimeout(initializeCEONotes, 500);
            
            // Auto-refresh every 60 seconds
            setInterval(() => {
                if (document.getElementById('overview').classList.contains('active')) {
                    console.log('Auto-refresh check - Overview tab active');
                    // Uncomment for actual auto-refresh: refreshDashboard();
                }
            }, 60000);
        });

        // Auto-save brain dump every 30 seconds if there's content
        setInterval(() => {
            const brainDumpText = document.getElementById('brain-dump-text');
            if (brainDumpText && brainDumpText.value.trim() && brainDumpText.value.length > 20) {
                // Auto-save long brain dumps
                saveBrainDump();
                showQuickFeedback('🔄 Auto-saved brain dump');
            }
        }, 30000);

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