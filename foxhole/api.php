<?php
// api_enhanced.php - Enhanced API for employee dashboard
require_once 'config-2.php';

class DashboardAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Get employee detailed stats
    public function getEmployeeDetailedStats($employeeId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    e.*,
                    COUNT(t.id) as total_tasks,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN t.status IN ('todo', 'in_progress', 'review') THEN 1 END) as active_tasks,
                    COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 1 END) as overdue_tasks,
                    SUM(t.hours_logged) as total_hours,
                    AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 
                        THEN (t.hours_logged / t.estimated_hours) * 100 
                        ELSE NULL END) as efficiency_ratio
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                WHERE e.id = ?
                GROUP BY e.id
            ");
            $stmt->execute([$employeeId]);
            $result = $stmt->fetch();
            
            if ($result) {
                // Get recent activity
                $activityStmt = $this->db->prepare("
                    SELECT activity, created_at,
                           CASE 
                               WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), 'm ago')
                               WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), 'h ago')
                               ELSE DATE_FORMAT(created_at, '%M %d')
                           END as time_formatted
                    FROM activity_log 
                    WHERE employee_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                $activityStmt->execute([$employeeId]);
                $result['recent_activity'] = $activityStmt->fetchAll();
                
                return $result;
            }
            return null;
        } catch (Exception $e) {
            error_log("Error getting employee stats: " . $e->getMessage());
            return null;
        }
    }
    
    // Get tasks for employee
    public function getTasks($filters = []) {
        try {
            $sql = "
                SELECT t.*, p.name as project_name,
                       CASE 
                           WHEN t.due_date IS NULL THEN NULL
                           ELSE DATEDIFF(t.due_date, CURDATE())
                       END as days_until_due
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (isset($filters['employee_id'])) {
                $sql .= " AND t.employee_id = ?";
                $params[] = $filters['employee_id'];
            }
            
            if (isset($filters['status'])) {
                $sql .= " AND t.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['priority'])) {
                $sql .= " AND t.priority = ?";
                $params[] = $filters['priority'];
            }
            
            $sql .= " ORDER BY 
                      CASE t.priority 
                          WHEN 'urgent' THEN 1 
                          WHEN 'high' THEN 2 
                          WHEN 'medium' THEN 3 
                          WHEN 'low' THEN 4 
                      END,
                      t.due_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting tasks: " . $e->getMessage());
            return [];
        }
    }
    
    // Update task status
    public function updateTaskStatus($taskId, $status) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tasks 
                SET status = ?, last_activity = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $taskId]);
            
            if ($result) {
                // Log activity
                $this->logTaskActivity($taskId, "Task status changed to: $status");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error updating task status: " . $e->getMessage());
            return false;
        }
    }
    
    // Update task hours
    public function updateTaskHours($taskId, $additionalHours) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tasks 
                SET hours_logged = hours_logged + ?, last_activity = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$additionalHours, $taskId]);
            
            if ($result) {
                // Log activity
                $this->logTaskActivity($taskId, "Logged $additionalHours hours");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error updating task hours: " . $e->getMessage());
            return false;
        }
    }
    
    // Log task activity
    private function logTaskActivity($taskId, $activity) {
        try {
            // Get task info
            $stmt = $this->db->prepare("SELECT employee_id, title FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            
            if ($task) {
                $stmt = $this->db->prepare("
                    INSERT INTO activity_log (employee_id, activity, type, created_at)
                    VALUES (?, ?, 'task_update', NOW())
                ");
                $stmt->execute([$task['employee_id'], $activity . " - " . $task['title']]);
            }
        } catch (Exception $e) {
            error_log("Error logging task activity: " . $e->getMessage());
        }
    }
    
    // Get employees (basic method for compatibility)
    public function getEmployees() {
        try {
            $stmt = $this->db->query("
                SELECT e.*, 
                       COUNT(t.id) as task_count,
                       COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                       COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 1 END) as overdue_tasks,
                       SUM(t.hours_logged) as hours_logged,
                       AVG(CASE WHEN t.status = 'completed' AND t.estimated_hours > 0 
                           THEN (t.hours_logged / t.estimated_hours) * 100 
                           ELSE NULL END) as efficiency_ratio
                FROM employees e
                LEFT JOIN tasks t ON e.id = t.employee_id
                GROUP BY e.id
                ORDER BY e.name
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting employees: " . $e->getMessage());
            return [];
        }
    }
    
    // Log general activity
    public function logActivity($employeeId, $activity, $type = 'general') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (employee_id, activity, type, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$employeeId, $activity, $type]);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }
    
    // Get dashboard stats (for compatibility)
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Active employees today
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM employees 
                WHERE status = 'active' OR last_activity >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            $stats['active_employees'] = $stmt->fetch()['count'];
            
            // Total employees
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM employees");
            $stats['total_employees'] = $stmt->fetch()['count'];
            
            // Completed tasks this week
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM tasks 
                WHERE status = 'completed' AND last_activity >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
            ");
            $stats['completed_tasks'] = $stmt->fetch()['count'];
            
            // Urgent tasks
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM tasks 
                WHERE priority = 'urgent' AND status != 'completed'
            ");
            $stats['urgent_tasks'] = $stmt->fetch()['count'];
            
            // Overdue tasks
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM tasks 
                WHERE due_date < CURDATE() AND status != 'completed'
            ");
            $stats['overdue_tasks'] = $stmt->fetch()['count'];
            
            // Average productivity
            $stmt = $this->db->query("
                SELECT AVG(CASE WHEN estimated_hours > 0 
                    THEN (hours_logged / estimated_hours) * 100 
                    ELSE 75 END) as avg_productivity
                FROM tasks 
                WHERE status = 'completed'
            ");
            $result = $stmt->fetch();
            $stats['avg_productivity'] = $result['avg_productivity'] ?? 75;
            
            // Total hours logged
            $stmt = $this->db->query("
                SELECT SUM(hours_logged) as total_hours 
                FROM tasks 
                WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            ");
            $stats['total_hours_logged'] = $stmt->fetch()['total_hours'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting dashboard stats: " . $e->getMessage());
            return [
                'active_employees' => 0,
                'total_employees' => 0,
                'completed_tasks' => 0,
                'urgent_tasks' => 0,
                'overdue_tasks' => 0,
                'avg_productivity' => 75,
                'total_hours_logged' => 0
            ];
        }
    }
    
    // Get projects (for compatibility)
    public function getProjects() {
        try {
            $stmt = $this->db->query("
                SELECT p.*,
                       COUNT(t.id) as total_tasks,
                       COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                       CASE 
                           WHEN COUNT(t.id) = 0 THEN 0
                           ELSE ROUND((COUNT(CASE WHEN t.status = 'completed' THEN 1 END) / COUNT(t.id)) * 100)
                       END as progress,
                       COUNT(DISTINCT t.employee_id) as team_size,
                       MIN(CASE WHEN t.due_date > CURDATE() THEN t.due_date END) as next_deadline
                FROM projects p
                LEFT JOIN tasks t ON p.id = t.project_id
                GROUP BY p.id
                ORDER BY p.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting projects: " . $e->getMessage());
            return [];
        }
    }
    
    // Get recent activity (for compatibility)
    public function getRecentActivity($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT al.*, e.name as employee_name,
                       CASE 
                           WHEN al.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN CONCAT(TIMESTAMPDIFF(MINUTE, al.created_at, NOW()), 'm ago')
                           WHEN al.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN CONCAT(TIMESTAMPDIFF(HOUR, al.created_at, NOW()), 'h ago')
                           ELSE DATE_FORMAT(al.created_at, '%M %d at %h:%i%p')
                       END as time_formatted
                FROM activity_log al
                LEFT JOIN employees e ON al.employee_id = e.id
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
?>