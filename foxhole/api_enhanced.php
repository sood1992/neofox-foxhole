<?php
// api_enhanced.php - Enhanced Dashboard API with Employee Stats
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config-2.php';

class DashboardAPI {
    private $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            
            // Test the connection immediately
            $this->db->query("SELECT 1");
            error_log("DashboardAPI: Database connection successful");
        } catch (Exception $e) {
            error_log("DashboardAPI: Database connection failed: " . $e->getMessage());
            $this->db = null;
        }
    }

    // Get all employees with their stats
    public function getEmployees() {
        if (!$this->db) {
            error_log("getEmployees: No database connection");
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, 
                       COALESCE(COUNT(DISTINCT t.id), 0) as task_count,
                       COALESCE(COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END), 0) as completed_tasks,
                       COALESCE(COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END), 0) as active_tasks,
                       COALESCE(COUNT(DISTINCT CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN t.id END), 0) as overdue_tasks,
                       COALESCE(SUM(t.hours_logged), 0) as total_hours_logged,
                       COALESCE(SUM(CASE WHEN DATE(t.last_activity) = CURDATE() THEN t.hours_logged ELSE 0 END), 0) as hours_today,
                       COALESCE(AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 THEN (t.hours_logged / t.estimated_hours) * 100 END), 0) as efficiency_ratio,
                       MAX(COALESCE(t.last_activity, e.last_activity)) as last_activity,
                       COALESCE(COUNT(DISTINCT CASE WHEN t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN t.id END), 0) as upcoming_deadlines,
                       CASE 
                           WHEN e.clickup_user_id IS NOT NULL THEN 'ClickUp'
                           WHEN e.notion_user_id IS NOT NULL THEN 'Notion'
                           ELSE 'Manual'
                       END as data_source
                FROM employees e 
                LEFT JOIN tasks t ON e.id = t.employee_id 
                GROUP BY e.id
                ORDER BY 
                    CASE e.status 
                        WHEN 'active' THEN 1 
                        WHEN 'busy' THEN 2 
                        WHEN 'away' THEN 3 
                        ELSE 4 
                    END,
                    e.name
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getEmployees: Retrieved " . count($result) . " employees");
            return $result;
        } catch (Exception $e) {
            error_log("getEmployees: Error - " . $e->getMessage());
            return [];
        }
    }
    
    // Get detailed stats for a specific employee - UPDATED METHOD
    public function getEmployeeDetailedStats($employee_id) {
        if (!$this->db) {
            error_log("getEmployeeDetailedStats: No database connection");
            return $this->getMockEmployeeStats($employee_id);
        }
        
        try {
            // Get employee basic stats
            $stmt = $this->db->prepare("
                SELECT 
                    e.*,
                    COUNT(DISTINCT t.id) as total_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END) as active_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'todo' THEN t.id END) as pending_tasks,
                    COUNT(DISTINCT CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN t.id END) as overdue_tasks,
                    COALESCE(SUM(t.hours_logged), 0) as total_hours,
                    COALESCE(SUM(t.estimated_hours), 0) as estimated_hours,
                    COALESCE(AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 THEN (t.hours_logged / t.estimated_hours) * 100 END), 0) as avg_efficiency,
                    COALESCE(SUM(CASE WHEN WEEK(t.created_at) = WEEK(CURDATE()) THEN t.hours_logged ELSE 0 END), 0) as week_hours,
                    COUNT(DISTINCT CASE WHEN WEEK(t.created_at) = WEEK(CURDATE()) AND t.status = 'completed' THEN t.id END) as week_completed,
                    COUNT(DISTINCT CASE WHEN t.priority = 'urgent' AND t.status != 'completed' THEN t.id END) as urgent_tasks,
                    COUNT(DISTINCT CASE WHEN t.priority = 'high' AND t.status != 'completed' THEN t.id END) as high_priority_tasks,
                    COUNT(DISTINCT t.project_id) as projects_involved
                FROM employees e 
                LEFT JOIN tasks t ON e.id = t.employee_id 
                WHERE e.id = ?
                GROUP BY e.id
            ");
            $stmt->execute([$employee_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stats) {
                error_log("getEmployeeDetailedStats: No employee found with ID: $employee_id");
                return $this->getMockEmployeeStats($employee_id);
            }
            
            // Get all tasks for this employee - THIS IS THE IMPORTANT PART
            $stmt = $this->db->prepare("
                SELECT 
                    t.*,
                    p.name as project_name,
                    DATEDIFF(t.due_date, CURDATE()) as days_until_due
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.employee_id = ?
                ORDER BY 
                    CASE t.status 
                        WHEN 'in_progress' THEN 1 
                        WHEN 'todo' THEN 2 
                        WHEN 'completed' THEN 3 
                    END,
                    t.due_date ASC
            ");
            $stmt->execute([$employee_id]);
            $stats['tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get recent activity
            $stmt = $this->db->prepare("
                SELECT al.*, 
                       CONCAT(
                           CASE 
                               WHEN TIMESTAMPDIFF(MINUTE, al.created_at, NOW()) < 60 
                               THEN CONCAT(TIMESTAMPDIFF(MINUTE, al.created_at, NOW()), 'm ago')
                               WHEN TIMESTAMPDIFF(HOUR, al.created_at, NOW()) < 24 
                               THEN CONCAT(TIMESTAMPDIFF(HOUR, al.created_at, NOW()), 'h ago')
                               ELSE CONCAT(TIMESTAMPDIFF(DAY, al.created_at, NOW()), 'd ago')
                           END
                       ) as time_formatted
                FROM activity_log al 
                WHERE al.employee_id = ?
                ORDER BY al.created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$employee_id]);
            $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("getEmployeeDetailedStats: Retrieved stats for employee $employee_id - Tasks: " . count($stats['tasks']));
            
            // If no tasks found, return mock data for testing
            if (empty($stats['tasks']) && isset($_GET['test_mode'])) {
                return $this->getMockEmployeeStats($employee_id);
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("getEmployeeDetailedStats: Error - " . $e->getMessage());
            return $this->getMockEmployeeStats($employee_id);
        }
    }
    
    // Mock data for testing
    private function getMockEmployeeStats($employee_id) {
        return [
            'id' => $employee_id,
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'role' => 'Developer',
            'status' => 'active',
            'total_tasks' => 5,
            'completed_tasks' => 2,
            'active_tasks' => 2,
            'pending_tasks' => 1,
            'overdue_tasks' => 1,
            'total_hours' => 40,
            'estimated_hours' => 50,
            'avg_efficiency' => 85,
            'week_hours' => 10,
            'week_completed' => 3,
            'urgent_tasks' => 1,
            'high_priority_tasks' => 2,
            'projects_involved' => 2,
            'tasks' => [
                [
                    'id' => 1,
                    'title' => 'Complete API Integration',
                    'description' => 'Integrate payment gateway API',
                    'status' => 'in_progress',
                    'priority' => 'high',
                    'due_date' => date('Y-m-d', strtotime('+2 days')),
                    'project_name' => 'E-commerce Platform',
                    'hours_logged' => 5,
                    'estimated_hours' => 10,
                    'last_activity' => date('Y-m-d H:i:s'),
                    'days_until_due' => 2
                ],
                [
                    'id' => 2,
                    'title' => 'Fix Login Bug',
                    'description' => 'Users unable to login with special characters',
                    'status' => 'todo',
                    'priority' => 'urgent',
                    'due_date' => date('Y-m-d', strtotime('-1 day')),
                    'project_name' => 'Main Website',
                    'hours_logged' => 0,
                    'estimated_hours' => 3,
                    'last_activity' => date('Y-m-d H:i:s'),
                    'days_until_due' => -1
                ],
                [
                    'id' => 3,
                    'title' => 'Update Documentation',
                    'description' => 'Update API documentation for v2.0',
                    'status' => 'completed',
                    'priority' => 'medium',
                    'due_date' => date('Y-m-d'),
                    'project_name' => 'Documentation',
                    'hours_logged' => 8,
                    'estimated_hours' => 8,
                    'last_activity' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'days_until_due' => 0
                ]
            ],
            'recent_activity' => [
                ['activity' => 'Started working on API Integration', 'time_formatted' => '2h ago'],
                ['activity' => 'Completed Documentation Update', 'time_formatted' => '1d ago'],
                ['activity' => 'Assigned to Fix Login Bug', 'time_formatted' => '2d ago']
            ]
        ];
    }
    
    // Add employee
    public function addEmployee($name, $email, $role, $type = 'employee') {
        if (!$this->db) {
            error_log("addEmployee: No database connection");
            return false;
        }
        
        try {
            error_log("addEmployee: Adding $name ($email) as $role");
            
            $stmt = $this->db->prepare("
                INSERT INTO employees (name, email, role, type, status, created_at, last_activity) 
                VALUES (?, ?, ?, ?, 'offline', NOW(), NOW())
            ");
            $result = $stmt->execute([$name, $email, $role, $type]);
            
            if ($result) {
                $employee_id = $this->db->lastInsertId();
                error_log("addEmployee: Successfully added employee with ID: $employee_id");
                $this->logActivity($employee_id, "New {$type} added to team", 'general');
                return true;
            } else {
                error_log("addEmployee: Failed to execute insert statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("addEmployee: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Update employee
    public function updateEmployee($employee_id, $name, $email, $role, $type = 'employee') {
        if (!$this->db) {
            error_log("updateEmployee: No database connection");
            return false;
        }
        
        try {
            error_log("updateEmployee: Updating employee ID $employee_id");
            
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET name = ?, email = ?, role = ?, type = ?, last_activity = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([$name, $email, $role, $type, $employee_id]);
            
            if ($result) {
                error_log("updateEmployee: Successfully updated employee ID: $employee_id");
                $this->logActivity($employee_id, "Employee information updated", 'profile_update');
                return true;
            } else {
                error_log("updateEmployee: Failed to execute update statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("updateEmployee: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Update employee status
    public function updateEmployeeStatus($employee_id, $status) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET status = ?, last_activity = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $employee_id]);
            
            if ($result) {
                $this->logActivity($employee_id, "Status changed to {$status}", 'status_change');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("updateEmployeeStatus: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Delete employee
    public function deleteEmployee($employee_id) {
        if (!$this->db) {
            error_log("deleteEmployee: No database connection");
            return false;
        }
        
        try {
            error_log("deleteEmployee: Deleting employee ID $employee_id");
            
            // First, unassign all tasks from this employee
            $stmt = $this->db->prepare("
                UPDATE tasks 
                SET employee_id = NULL 
                WHERE employee_id = ?
            ");
            $stmt->execute([$employee_id]);
            
            // Delete activity log entries
            $stmt = $this->db->prepare("
                DELETE FROM activity_log 
                WHERE employee_id = ?
            ");
            $stmt->execute([$employee_id]);
            
            // Delete the employee
            $stmt = $this->db->prepare("
                DELETE FROM employees 
                WHERE id = ?
            ");
            $result = $stmt->execute([$employee_id]);
            
            if ($result) {
                error_log("deleteEmployee: Successfully deleted employee ID: $employee_id");
                return true;
            } else {
                error_log("deleteEmployee: Failed to execute delete statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("deleteEmployee: Error - " . $e->getMessage());
            return false;
        }
    }

    // Get all projects
    public function getProjects() {
        if (!$this->db) {
            error_log("getProjects: No database connection");
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       COALESCE(COUNT(DISTINCT t.id), 0) as total_tasks,
                       COALESCE(COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END), 0) as completed_tasks,
                       COALESCE(COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END), 0) as active_tasks,
                       COALESCE(COUNT(DISTINCT CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN t.id END), 0) as overdue_tasks,
                       CASE 
                           WHEN COUNT(DISTINCT t.id) > 0 
                           THEN ROUND((COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) / COUNT(DISTINCT t.id)) * 100, 0)
                           ELSE 0 
                       END as progress,
                       COALESCE(SUM(t.hours_logged), 0) as total_hours_logged,
                       COALESCE(SUM(t.estimated_hours), 0) as total_estimated_hours,
                       CASE 
                           WHEN p.clickup_project_id IS NOT NULL THEN 'ClickUp'
                           WHEN p.notion_page_id IS NOT NULL THEN 'Notion'
                           ELSE 'Manual'
                       END as data_source,
                       GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR ', ') as team_members,
                       MIN(CASE WHEN t.status != 'completed' AND t.due_date IS NOT NULL THEN t.due_date END) as next_deadline,
                       COUNT(DISTINCT t.employee_id) as team_size
                FROM projects p 
                LEFT JOIN tasks t ON p.id = t.project_id 
                LEFT JOIN employees e ON t.employee_id = e.id
                WHERE p.status != 'completed'
                GROUP BY p.id
                ORDER BY 
                    CASE p.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        ELSE 4 
                    END,
                    p.name
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getProjects: Retrieved " . count($result) . " projects");
            return $result;
        } catch (Exception $e) {
            error_log("getProjects: Error - " . $e->getMessage());
            return [];
        }
    }
    
    // Add project
    public function addProject($name, $description, $client, $priority = 'medium', $start_date = null, $end_date = null) {
        if (!$this->db) {
            error_log("addProject: No database connection");
            return false;
        }
        
        try {
            error_log("addProject: Adding project $name for client $client");
            
            $stmt = $this->db->prepare("
                INSERT INTO projects (name, description, client, priority, status, start_date, end_date, created_at) 
                VALUES (?, ?, ?, ?, 'planning', ?, ?, NOW())
            ");
            $result = $stmt->execute([$name, $description, $client, $priority, $start_date, $end_date]);
            
            if ($result) {
                $project_id = $this->db->lastInsertId();
                error_log("addProject: Successfully added project with ID: $project_id");
                return true;
            } else {
                error_log("addProject: Failed to execute insert statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("addProject: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Update project
    public function updateProject($project_id, $name, $description, $client, $priority, $start_date, $end_date) {
        if (!$this->db) {
            error_log("updateProject: No database connection");
            return false;
        }
        
        try {
            error_log("updateProject: Updating project ID $project_id");
            
            $stmt = $this->db->prepare("
                UPDATE projects 
                SET name = ?, description = ?, client = ?, priority = ?, 
                    start_date = ?, end_date = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([$name, $description, $client, $priority, $start_date, $end_date, $project_id]);
            
            if ($result) {
                error_log("updateProject: Successfully updated project ID: $project_id");
                return true;
            } else {
                error_log("updateProject: Failed to execute update statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("updateProject: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Delete project
    public function deleteProject($project_id) {
        if (!$this->db) {
            error_log("deleteProject: No database connection");
            return false;
        }
        
        try {
            error_log("deleteProject: Deleting project ID $project_id");
            
            // First, delete all tasks associated with this project
            $stmt = $this->db->prepare("
                DELETE FROM tasks 
                WHERE project_id = ?
            ");
            $stmt->execute([$project_id]);
            
            // Delete the project
            $stmt = $this->db->prepare("
                DELETE FROM projects 
                WHERE id = ?
            ");
            $result = $stmt->execute([$project_id]);
            
            if ($result) {
                error_log("deleteProject: Successfully deleted project ID: $project_id");
                return true;
            } else {
                error_log("deleteProject: Failed to execute delete statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("deleteProject: Error - " . $e->getMessage());
            return false;
        }
    }

    // Get tasks with filters
    public function getTasks($filters = []) {
        if (!$this->db) {
            error_log("getTasks: No database connection");
            return [];
        }
        
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (isset($filters['status'])) {
                $whereClause .= " AND t.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['employee_id'])) {
                $whereClause .= " AND t.employee_id = ?";
                $params[] = $filters['employee_id'];
            }
            
            if (isset($filters['project_id'])) {
                $whereClause .= " AND t.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (isset($filters['priority'])) {
                $whereClause .= " AND t.priority = ?";
                $params[] = $filters['priority'];
            }
            
            if (isset($filters['overdue']) && $filters['overdue']) {
                $whereClause .= " AND t.due_date < CURDATE() AND t.status != 'completed'";
            }
            
            $stmt = $this->db->prepare("
                SELECT t.*, 
                       e.name as employee_name,
                       p.name as project_name,
                       CASE 
                           WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 'overdue'
                           WHEN t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due_soon'
                           ELSE 'normal'
                       END as urgency_status,
                       DATEDIFF(t.due_date, CURDATE()) as days_until_due,
                       CASE 
                           WHEN t.clickup_task_id IS NOT NULL THEN 'ClickUp'
                           ELSE 'Manual'
                       END as data_source
                FROM tasks t
                LEFT JOIN employees e ON t.employee_id = e.id
                LEFT JOIN projects p ON t.project_id = p.id
                {$whereClause}
                ORDER BY 
                    CASE t.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        ELSE 4 
                    END,
                    t.due_date ASC,
                    t.created_at DESC
            ");
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getTasks: Retrieved " . count($result) . " tasks");
            return $result;
        } catch (Exception $e) {
            error_log("getTasks: Error - " . $e->getMessage());
            return [];
        }
    }
    
    // Add task
    public function addTask($employee_id, $project_id, $title, $description = '', $priority = 'medium', $due_date = null, $estimated_hours = null) {
        if (!$this->db) {
            error_log("addTask: No database connection");
            return false;
        }
        
        try {
            error_log("addTask: Adding task '$title' for employee $employee_id");
            
            // Convert empty strings to null for foreign keys
            $employee_id = $employee_id ?: null;
            $project_id = $project_id ?: null;
            $due_date = $due_date ?: null;
            $estimated_hours = $estimated_hours ?: null;
            
            $stmt = $this->db->prepare("
                INSERT INTO tasks (employee_id, project_id, title, description, status, priority, estimated_hours, due_date, created_at, last_activity) 
                VALUES (?, ?, ?, ?, 'todo', ?, ?, ?, NOW(), NOW())
            ");
            $result = $stmt->execute([$employee_id, $project_id, $title, $description, $priority, $estimated_hours, $due_date]);
            
            if ($result) {
                $task_id = $this->db->lastInsertId();
                error_log("addTask: Successfully added task with ID: $task_id");
                
                if ($employee_id) {
                    $this->logActivity($employee_id, "New task assigned: {$title}", 'task_start');
                }
                return true;
            } else {
                error_log("addTask: Failed to execute insert statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("addTask: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Update task
    public function updateTask($task_id, $title, $description, $employee_id, $project_id, $priority, $status, $due_date, $estimated_hours) {
        if (!$this->db) {
            error_log("updateTask: No database connection");
            return false;
        }
        
        try {
            error_log("updateTask: Updating task ID $task_id");
            
            // Convert empty strings to null for foreign keys
            $employee_id = $employee_id ?: null;
            $project_id = $project_id ?: null;
            $due_date = $due_date ?: null;
            $estimated_hours = $estimated_hours ?: null;
            
            $stmt = $this->db->prepare("
                UPDATE tasks 
                SET title = ?, description = ?, employee_id = ?, project_id = ?, 
                    priority = ?, status = ?, due_date = ?, estimated_hours = ?,
                    last_activity = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $title, $description, $employee_id, $project_id, 
                $priority, $status, $due_date, $estimated_hours, $task_id
            ]);
            
            if ($result) {
                error_log("updateTask: Successfully updated task ID: $task_id");
                
                if ($employee_id) {
                    $this->logActivity($employee_id, "Task '{$title}' was updated", 'task_update');
                }
                return true;
            } else {
                error_log("updateTask: Failed to execute update statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("updateTask: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Update task status
    public function updateTaskStatus($task_id, $status) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE tasks 
                SET status = ?, last_activity = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $task_id]);
            
            if ($result) {
                $stmt = $this->db->prepare("SELECT employee_id, title FROM tasks WHERE id = ?");
                $stmt->execute([$task_id]);
                $task = $stmt->fetch();
                
                if ($task && $task['employee_id']) {
                    $this->logActivity($task['employee_id'], "Task '{$task['title']}' status changed to {$status}", 'task_update');
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("updateTaskStatus: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Update task hours
    public function updateTaskHours($task_id, $hours_logged) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE tasks 
                SET hours_logged = ?, last_activity = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$hours_logged, $task_id]);
        } catch (Exception $e) {
            error_log("updateTaskHours: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Delete task
    public function deleteTask($task_id) {
        if (!$this->db) {
            error_log("deleteTask: No database connection");
            return false;
        }
        
        try {
            // Get task info for logging
            $stmt = $this->db->prepare("
                SELECT employee_id, title 
                FROM tasks 
                WHERE id = ?
            ");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            
            error_log("deleteTask: Deleting task ID $task_id");
            
            // Delete the task
            $stmt = $this->db->prepare("
                DELETE FROM tasks 
                WHERE id = ?
            ");
            $result = $stmt->execute([$task_id]);
            
            if ($result) {
                error_log("deleteTask: Successfully deleted task ID: $task_id");
                
                if ($task && $task['employee_id']) {
                    $this->logActivity($task['employee_id'], "Task '{$task['title']}' was deleted", 'task_delete');
                }
                
                return true;
            } else {
                error_log("deleteTask: Failed to execute delete statement");
                return false;
            }
        } catch (Exception $e) {
            error_log("deleteTask: Error - " . $e->getMessage());
            return false;
        }
    }

    // Log activity
    public function logActivity($employee_id, $activity, $type = 'general') {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (employee_id, activity, type, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$employee_id, $activity, $type]);
        } catch (Exception $e) {
            error_log("logActivity: Error - " . $e->getMessage());
            return false;
        }
    }
    
    // Get recent activity
    public function getRecentActivity($limit = 20) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT al.*, e.name as employee_name,
                       CONCAT(
                           CASE 
                               WHEN TIMESTAMPDIFF(MINUTE, al.created_at, NOW()) < 60 
                               THEN CONCAT(TIMESTAMPDIFF(MINUTE, al.created_at, NOW()), 'm ago')
                               WHEN TIMESTAMPDIFF(HOUR, al.created_at, NOW()) < 24 
                               THEN CONCAT(TIMESTAMPDIFF(HOUR, al.created_at, NOW()), 'h ago')
                               ELSE CONCAT(TIMESTAMPDIFF(DAY, al.created_at, NOW()), 'd ago')
                           END
                       ) as time_formatted
                FROM activity_log al 
                LEFT JOIN employees e ON al.employee_id = e.id 
                ORDER BY al.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getRecentActivity: Error - " . $e->getMessage());
            return [];
        }
    }
    
    // Get dashboard stats
    public function getDashboardStats() {
        if (!$this->db) {
            return [
                'total_employees' => 0,
                'active_employees' => 0,
                'total_projects' => 0,
                'completed_tasks' => 0,
                'avg_productivity' => 0,
                'overdue_tasks' => 0,
                'urgent_tasks' => 0,
                'total_hours_logged' => 0
            ];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT e.id) as total_employees,
                    COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) as active_employees,
                    COUNT(DISTINCT p.id) as total_projects,
                    COUNT(DISTINCT t.id) as total_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                    COUNT(DISTINCT CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN t.id END) as overdue_tasks,
                    COUNT(DISTINCT CASE WHEN t.priority = 'urgent' AND t.status != 'completed' THEN t.id END) as urgent_tasks,
                    COALESCE(SUM(t.hours_logged), 0) as total_hours_logged,
                    ROUND(AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 THEN (t.hours_logged / t.estimated_hours) * 100 END), 1) as avg_productivity
                FROM employees e 
                LEFT JOIN tasks t ON e.id = t.employee_id 
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE p.status != 'completed' OR p.id IS NULL
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_employees' => $stats['total_employees'] ?: 0,
                'active_employees' => $stats['active_employees'] ?: 0,
                'total_projects' => $stats['total_projects'] ?: 0,
                'completed_tasks' => $stats['completed_tasks'] ?: 0,
                'avg_productivity' => $stats['avg_productivity'] ?: 0,
                'overdue_tasks' => $stats['overdue_tasks'] ?: 0,
                'urgent_tasks' => $stats['urgent_tasks'] ?: 0,
                'total_hours_logged' => $stats['total_hours_logged'] ?: 0
            ];
        } catch (Exception $e) {
            error_log("getDashboardStats: Error - " . $e->getMessage());
            return [
                'total_employees' => 0,
                'active_employees' => 0,
                'total_projects' => 0,
                'completed_tasks' => 0,
                'avg_productivity' => 0,
                'overdue_tasks' => 0,
                'urgent_tasks' => 0,
                'total_hours_logged' => 0
            ];
        }
    }
    
    // Get productivity insights
    public function getProductivityInsights() {
        if (!$this->db) return [];
        
        try {
            $insights = [];
            
            // Team Overview
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT e.id) as total_employees,
                    COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) as active_employees,
                    COUNT(DISTINCT t.id) as total_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                    COUNT(DISTINCT CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN t.id END) as overdue_tasks,
                    ROUND(AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 THEN (t.hours_logged / t.estimated_hours) * 100 END), 1) as avg_efficiency
                FROM employees e 
                LEFT JOIN tasks t ON e.id = t.employee_id
            ");
            $stmt->execute();
            $insights['team_overview'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Top Performers
            $stmt = $this->db->prepare("
                SELECT e.name, e.role, e.status,
                       COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                       ROUND(AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 THEN (t.hours_logged / t.estimated_hours) * 100 END), 1) as efficiency_score
                FROM employees e 
                LEFT JOIN tasks t ON e.id = t.employee_id 
                GROUP BY e.id
                HAVING completed_tasks > 0
                ORDER BY efficiency_score DESC, completed_tasks DESC
                LIMIT 5
            ");
            $stmt->execute();
            $insights['top_performers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Project Health
            $stmt = $this->db->prepare("
                SELECT p.name, p.priority, p.status,
                       COUNT(DISTINCT t.id) as total_tasks,
                       COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                       CASE 
                           WHEN COUNT(DISTINCT t.id) > 0 
                           THEN ROUND((COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) / COUNT(DISTINCT t.id)) * 100, 0)
                           ELSE 0 
                       END as progress_percentage
                FROM projects p 
                LEFT JOIN tasks t ON p.id = t.project_id 
                WHERE p.status != 'completed'
                GROUP BY p.id
                ORDER BY progress_percentage ASC
                LIMIT 5
            ");
            $stmt->execute();
            $insights['project_health'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $insights;
        } catch (Exception $e) {
            error_log("getProductivityInsights: Error - " . $e->getMessage());
            return [];
        }
    }

    // Handle GET requests
    public function handleGetRequest($action, $params = []) {
        error_log("API: Handling GET request for action: $action");
        
        switch ($action) {
            case 'test':
                return [
                    'status' => 'success',
                    'message' => 'Dashboard API is working!',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'database' => $this->db ? 'connected' : 'disconnected',
                    'version' => '1.0'
                ];
                
            case 'employees':
                return $this->getEmployees();
                
            case 'employee_details':
                if (isset($params['employee_id'])) {
                    return $this->getEmployeeDetailedStats($params['employee_id']);
                } else {
                    return ['error' => 'employee_id required'];
                }
                
            case 'projects':
                return $this->getProjects();
                
            case 'tasks':
                $filters = [];
                if (isset($params['status'])) $filters['status'] = $params['status'];
                if (isset($params['employee_id'])) $filters['employee_id'] = $params['employee_id'];
                if (isset($params['project_id'])) $filters['project_id'] = $params['project_id'];
                if (isset($params['priority'])) $filters['priority'] = $params['priority'];
                if (isset($params['overdue'])) $filters['overdue'] = $params['overdue'] === 'true';
                return $this->getTasks($filters);
                
            case 'activity':
                $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
                return $this->getRecentActivity($limit);
                
            case 'stats':
                return $this->getDashboardStats();
                
            case 'productivity_insights':
                return $this->getProductivityInsights();
                
            default:
                return ['error' => 'Invalid GET action: ' . $action];
        }
    }

    // Handle POST requests
    public function handlePostRequest($input) {
        error_log("API: Handling POST request with data: " . print_r($input, true));
        
        if (!$input || !isset($input['action'])) {
            return ['success' => false, 'error' => 'No action specified'];
        }
        
        switch ($input['action']) {
            case 'add_employee':
                if (isset($input['name']) && isset($input['email']) && isset($input['role'])) {
                    $result = $this->addEmployee(
                        $input['name'], 
                        $input['email'], 
                        $input['role'], 
                        $input['type'] ?? 'employee'
                    );
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Employee added successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to add employee to database'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing required fields: name, email, role'];
                }
                
            case 'update_employee':
                if (isset($input['employee_id']) && isset($input['name']) && isset($input['email']) && isset($input['role'])) {
                    $result = $this->updateEmployee(
                        $input['employee_id'],
                        $input['name'], 
                        $input['email'], 
                        $input['role'], 
                        $input['type'] ?? 'employee'
                    );
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Employee updated successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to update employee'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing required fields'];
                }
                
            case 'delete_employee':
                if (isset($input['employee_id'])) {
                    $result = $this->deleteEmployee($input['employee_id']);
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Employee deleted successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to delete employee'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing employee_id'];
                }
                
            case 'add_project':
                if (isset($input['name'])) {
                    $result = $this->addProject(
                        $input['name'],
                        $input['description'] ?? '',
                        $input['client'] ?? '',
                        $input['priority'] ?? 'medium',
                        $input['start_date'] ?? null,
                        $input['end_date'] ?? null
                    );
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Project created successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to create project'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing required field: name'];
                }
                
            case 'update_project':
                if (isset($input['project_id']) && isset($input['name'])) {
                    $result = $this->updateProject(
                        $input['project_id'],
                        $input['name'],
                        $input['description'] ?? '',
                        $input['client'] ?? '',
                        $input['priority'] ?? 'medium',
                        $input['start_date'] ?? null,
                        $input['end_date'] ?? null
                    );
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Project updated successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to update project'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing required fields'];
                }
                
            case 'delete_project':
                if (isset($input['project_id'])) {
                    $result = $this->deleteProject($input['project_id']);
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Project deleted successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to delete project'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing project_id'];
                }
                
            case 'add_task':
                if (isset($input['title'])) {
                    $result = $this->addTask(
                        $input['employee_id'] ?? null,
                        $input['project_id'] ?? null,
                        $input['title'],
                        $input['description'] ?? '',
                        $input['priority'] ?? 'medium',
                        $input['due_date'] ?? null,
                        $input['estimated_hours'] ?? null
                    );
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Task assigned successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to assign task'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing required field: title'];
                }
                
            case 'update_task':
                if (isset($input['task_id']) && isset($input['title'])) {
                    $result = $this->updateTask(
                        $input['task_id'],
                        $input['title'],
                        $input['description'] ?? '',
                        $input['employee_id'] ?? null,
                        $input['project_id'] ?? null,
                        $input['priority'] ?? 'medium',
                        $input['status'] ?? 'todo',
                        $input['due_date'] ?? null,
                        $input['estimated_hours'] ?? null
                    );
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Task updated successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to update task'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing required fields'];
                }
                
            case 'delete_task':
                if (isset($input['task_id'])) {
                    $result = $this->deleteTask($input['task_id']);
                    
                    if ($result) {
                        return ['success' => true, 'message' => 'Task deleted successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to delete task'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Missing task_id'];
                }
                
            case 'update_employee_status':
                if (isset($input['employee_id']) && isset($input['status'])) {
                    $result = $this->updateEmployeeStatus($input['employee_id'], $input['status']);
                    return ['success' => $result];
                } else {
                    return ['success' => false, 'error' => 'Missing employee_id or status'];
                }
                
            case 'update_task_status':
                if (isset($input['task_id']) && isset($input['status'])) {
                    $result = $this->updateTaskStatus($input['task_id'], $input['status']);
                    return ['success' => $result];
                } else {
                    return ['success' => false, 'error' => 'Missing task_id or status'];
                }
                
            case 'update_task_hours':
                if (isset($input['task_id']) && isset($input['hours'])) {
                    $result = $this->updateTaskHours($input['task_id'], $input['hours']);
                    return ['success' => $result];
                } else {
                    return ['success' => false, 'error' => 'Missing task_id or hours'];
                }
                
            case 'log_activity':
                if (isset($input['employee_id']) && isset($input['activity'])) {
                    $type = $input['type'] ?? 'general';
                    $result = $this->logActivity($input['employee_id'], $input['activity'], $type);
                    return ['success' => $result];
                } else {
                    return ['success' => false, 'error' => 'Missing employee_id or activity'];
                }
                
            default:
                return ['success' => false, 'error' => 'Invalid POST action: ' . $input['action']];
        }
    }
}

// ONLY run API endpoint logic if this file is accessed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'api_enhanced.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    try {
        $api = new DashboardAPI();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            // Handle GET requests
            $result = $api->handleGetRequest($_GET['action'], $_GET);
            echo json_encode($result);
            
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle POST requests - Enhanced input handling
            $input = null;
            
            // Try JSON input first
            $raw_input = file_get_contents('php://input');
            if ($raw_input) {
                $input = json_decode($raw_input, true);
            }
            
            // Fallback to POST data
            if (!$input && !empty($_POST)) {
                $input = $_POST;
            }
            
            if (!$input) {
                echo json_encode(['success' => false, 'error' => 'No input data received']);
                exit;
            }
            
            $result = $api->handlePostRequest($input);
            echo json_encode($result);
            
        } else {
            echo json_encode([
                'error' => 'Invalid request method', 
                'allowed_methods' => ['GET', 'POST'],
                'api_info' => 'Foxhole Dashboard API v1.0'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("API Fatal Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'message' => 'Check server logs for details'
        ]);
    }
}
?>