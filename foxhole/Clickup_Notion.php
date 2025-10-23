<?php
// Enhanced ClickUp & Notion Integration
require_once 'config-2.php';

class ClickUpIntegration {
    private $apiKey;
    private $baseUrl = 'https://api.clickup.com/api/v2';
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = new Database();
        $this->config = new APIConfig();
        
        $apiConfig = $this->config->getAPIKey('clickup');
        if ($apiConfig) {
            $this->apiKey = $apiConfig['api_key'];
        }
    }
    
    // Make API request with error handling
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        if (!$this->apiKey) {
            throw new Exception('ClickUp API key not configured');
        }
        
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Authorization: ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception("ClickUp API Error: HTTP $httpCode - " . $response);
        }
        
        return json_decode($response, true);
    }
    
    // Get authenticated user info
    public function getAuthenticatedUser() {
        return $this->makeRequest('/user');
    }
    
    // Get all teams
    public function getTeams() {
        return $this->makeRequest('/team');
    }
    
    // Get spaces for a team
    public function getSpaces($teamId) {
        return $this->makeRequest("/team/{$teamId}/space");
    }
    
    // Get folders in a space
    public function getFolders($spaceId) {
        return $this->makeRequest("/space/{$spaceId}/folder");
    }
    
    // Get lists in a folder or space
    public function getLists($folderId = null, $spaceId = null) {
        if ($folderId) {
            return $this->makeRequest("/folder/{$folderId}/list");
        } elseif ($spaceId) {
            return $this->makeRequest("/space/{$spaceId}/list");
        }
        throw new Exception('Either folderId or spaceId is required');
    }
    
    // Get tasks from a list
    public function getTasks($listId, $params = []) {
        $queryString = http_build_query($params);
        $endpoint = "/list/{$listId}/task" . ($queryString ? "?{$queryString}" : '');
        return $this->makeRequest($endpoint);
    }
    
    // Get team members
    public function getTeamMembers($teamId) {
        return $this->makeRequest("/team/{$teamId}/member");
    }
    
    // Get time tracking for tasks
    public function getTimeTracking($taskId) {
        return $this->makeRequest("/task/{$taskId}/time");
    }
    
    // Sync ClickUp data to database
    public function syncToDatabase($teamId) {
        try {
            $this->logSync('clickup', 'full_sync', 'starting');
            
            // Sync team members first
            $membersSynced = $this->syncTeamMembers($teamId);
            
            // Sync projects (spaces/folders as projects)
            $projectsSynced = $this->syncProjects($teamId);
            
            // Sync tasks
            $tasksSynced = $this->syncTasks($teamId);
            
            $this->config->updateLastSync('clickup');
            $this->logSync('clickup', 'full_sync', 'success', $membersSynced + $projectsSynced + $tasksSynced);
            
            return [
                'success' => true,
                'members_synced' => $membersSynced,
                'projects_synced' => $projectsSynced,
                'tasks_synced' => $tasksSynced
            ];
            
        } catch (Exception $e) {
            $this->logSync('clickup', 'full_sync', 'error', 0, $e->getMessage());
            throw $e;
        }
    }
    
    // Sync team members
    private function syncTeamMembers($teamId) {
        $members = $this->getTeamMembers($teamId);
        $synced = 0;
        
        foreach ($members['members'] as $member) {
            $user = $member['user'];
            
            // Check if employee exists by email
            $stmt = $this->db->getConnection()->prepare("
                SELECT id FROM employees WHERE email = ?
            ");
            $stmt->execute([$user['email']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing employee with ClickUp ID
                $stmt = $this->db->getConnection()->prepare("
                    UPDATE employees 
                    SET clickup_user_id = ?, last_activity = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$user['id'], $existing['id']]);
            } else {
                // Create new employee
                $stmt = $this->db->getConnection()->prepare("
                    INSERT INTO employees (name, email, role, type, status, clickup_user_id, created_at) 
                    VALUES (?, ?, ?, 'employee', 'offline', ?, NOW())
                ");
                $stmt->execute([
                    $user['username'] ?: $user['email'],
                    $user['email'],
                    'Team Member',
                    $user['id']
                ]);
            }
            $synced++;
        }
        
        return $synced;
    }
    
    // Sync projects (ClickUp spaces as projects)
    private function syncProjects($teamId) {
        $spaces = $this->getSpaces($teamId);
        $synced = 0;
        
        foreach ($spaces['spaces'] as $space) {
            // Check if project exists
            $stmt = $this->db->getConnection()->prepare("
                SELECT id FROM projects WHERE clickup_project_id = ?
            ");
            $stmt->execute([$space['id']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing project
                $stmt = $this->db->getConnection()->prepare("
                    UPDATE projects 
                    SET name = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $space['name'],
                    $space['description'] ?: '',
                    $existing['id']
                ]);
            } else {
                // Create new project
                $stmt = $this->db->getConnection()->prepare("
                    INSERT INTO projects (name, description, client, priority, status, clickup_project_id, created_at) 
                    VALUES (?, ?, ?, 'medium', 'active', ?, NOW())
                ");
                $stmt->execute([
                    $space['name'],
                    $space['description'] ?: '',
                    'ClickUp Import',
                    $space['id']
                ]);
            }
            $synced++;
        }
        
        return $synced;
    }
    
    // Sync tasks from all lists
    private function syncTasks($teamId) {
        $spaces = $this->getSpaces($teamId);
        $synced = 0;
        
        foreach ($spaces['spaces'] as $space) {
            // Get lists in this space
            $lists = $this->getLists(null, $space['id']);
            
            foreach ($lists['lists'] as $list) {
                // Get tasks in this list
                $tasks = $this->getTasks($list['id'], [
                    'include_closed' => 'false',
                    'include_subtasks' => 'true'
                ]);
                
                foreach ($tasks['tasks'] as $task) {
                    $synced += $this->syncSingleTask($task, $space['id']);
                }
            }
        }
        
        return $synced;
    }
    
    // Sync a single task
    private function syncSingleTask($task, $spaceId) {
        // Find project by ClickUp space ID
        $stmt = $this->db->getConnection()->prepare("
            SELECT id FROM projects WHERE clickup_project_id = ?
        ");
        $stmt->execute([$spaceId]);
        $project = $stmt->fetch();
        
        if (!$project) return 0;
        
        // Find employee by ClickUp user ID
        $employeeId = null;
        if (!empty($task['assignees'])) {
            $assignee = $task['assignees'][0];
            $stmt = $this->db->getConnection()->prepare("
                SELECT id FROM employees WHERE clickup_user_id = ?
            ");
            $stmt->execute([$assignee['id']]);
            $employee = $stmt->fetch();
            if ($employee) {
                $employeeId = $employee['id'];
            }
        }
        
        // Check if task exists
        $stmt = $this->db->getConnection()->prepare("
            SELECT id FROM tasks WHERE clickup_task_id = ?
        ");
        $stmt->execute([$task['id']]);
        $existing = $stmt->fetch();
        
        $status = $this->mapClickUpStatus($task['status']['status']);
        $priority = $this->mapClickUpPriority($task['priority']);
        
        if ($existing) {
            // Update existing task
            $stmt = $this->db->getConnection()->prepare("
                UPDATE tasks 
                SET title = ?, description = ?, status = ?, priority = ?, 
                    employee_id = ?, due_date = ?, last_activity = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $task['name'],
                $task['description'] ?: '',
                $status,
                $priority,
                $employeeId,
                $task['due_date'] ? date('Y-m-d', $task['due_date'] / 1000) : null,
                $existing['id']
            ]);
        } else {
            // Create new task
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO tasks (employee_id, project_id, title, description, status, priority, 
                                 due_date, clickup_task_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $employeeId,
                $project['id'],
                $task['name'],
                $task['description'] ?: '',
                $status,
                $priority,
                $task['due_date'] ? date('Y-m-d', $task['due_date'] / 1000) : null,
                $task['id']
            ]);
        }
        
        return 1;
    }
    
    // Map ClickUp status to our status
    private function mapClickUpStatus($clickupStatus) {
        $statusMap = [
            'to do' => 'todo',
            'in progress' => 'in_progress',
            'review' => 'review',
            'complete' => 'completed',
            'closed' => 'completed'
        ];
        
        return $statusMap[strtolower($clickupStatus)] ?? 'todo';
    }
    
    // Map ClickUp priority to our priority
    private function mapClickUpPriority($clickupPriority) {
        if (!$clickupPriority) return 'medium';
        
        $priorityMap = [
            'urgent' => 'urgent',
            'high' => 'high',
            'normal' => 'medium',
            'low' => 'low'
        ];
        
        return $priorityMap[strtolower($clickupPriority['priority'])] ?? 'medium';
    }
    
    // Log sync activity
    private function logSync($service, $syncType, $status, $recordsSynced = 0, $errorMessage = null) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO sync_log (service, sync_type, status, records_synced, error_message, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$service, $syncType, $status, $recordsSynced, $errorMessage]);
        } catch (Exception $e) {
            error_log("Error logging sync: " . $e->getMessage());
        }
    }
}

class NotionIntegration {
    private $apiKey;
    private $baseUrl = 'https://api.notion.com/v1';
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = new Database();
        $this->config = new APIConfig();
        
        $apiConfig = $this->config->getAPIKey('notion');
        if ($apiConfig) {
            $this->apiKey = $apiConfig['api_key'];
        }
    }
    
    // Make API request with error handling
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        if (!$this->apiKey) {
            throw new Exception('Notion API key not configured');
        }
        
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception("Notion API Error: HTTP $httpCode - " . $response);
        }
        
        return json_decode($response, true);
    }
    
    // Search for pages/databases
    public function search($query = '', $filter = []) {
        $data = ['query' => $query];
        if (!empty($filter)) {
            $data['filter'] = $filter;
        }
        
        return $this->makeRequest('/search', 'POST', $data);
    }
    
    // Query a database
    public function queryDatabase($databaseId, $filter = null, $sorts = null) {
        $data = [];
        if ($filter) $data['filter'] = $filter;
        if ($sorts) $data['sorts'] = $sorts;
        
        return $this->makeRequest("/databases/{$databaseId}/query", 'POST', $data);
    }
    
    // Get page content
    public function getPage($pageId) {
        return $this->makeRequest("/pages/{$pageId}");
    }
    
    // Get database info
    public function getDatabase($databaseId) {
        return $this->makeRequest("/databases/{$databaseId}");
    }
    
    // Sync Notion data to database
    public function syncToDatabase($projectDatabaseId = null) {
        try {
            $this->logSync('notion', 'full_sync', 'starting');
            
            $synced = 0;
            
            // If specific project database ID provided, sync that
            if ($projectDatabaseId) {
                $synced += $this->syncProjectDatabase($projectDatabaseId);
            } else {
                // Search for databases that might contain projects
                $search = $this->search('', [
                    'property' => 'object',
                    'value' => 'database'
                ]);
                
                foreach ($search['results'] as $database) {
                    // Try to sync each database as potential project source
                    try {
                        $synced += $this->syncProjectDatabase($database['id']);
                    } catch (Exception $e) {
                        // Continue with other databases if one fails
                        error_log("Failed to sync Notion database {$database['id']}: " . $e->getMessage());
                    }
                }
            }
            
            $this->config->updateLastSync('notion');
            $this->logSync('notion', 'full_sync', 'success', $synced);
            
            return [
                'success' => true,
                'projects_synced' => $synced
            ];
            
        } catch (Exception $e) {
            $this->logSync('notion', 'full_sync', 'error', 0, $e->getMessage());
            throw $e;
        }
    }
    
    // Sync a Notion database as projects
    private function syncProjectDatabase($databaseId) {
        $database = $this->getDatabase($databaseId);
        $pages = $this->queryDatabase($databaseId);
        
        $synced = 0;
        
        foreach ($pages['results'] as $page) {
            $synced += $this->syncNotionProject($page);
        }
        
        return $synced;
    }
    
    // Sync a single Notion page as project
    private function syncNotionProject($page) {
        // Extract project info from page properties
        $name = $this->extractTitle($page);
        $description = $this->extractDescription($page);
        $status = $this->extractStatus($page);
        $priority = $this->extractPriority($page);
        
        if (!$name) return 0; // Skip if no title
        
        // Check if project exists
        $stmt = $this->db->getConnection()->prepare("
            SELECT id FROM projects WHERE notion_page_id = ?
        ");
        $stmt->execute([$page['id']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing project
            $stmt = $this->db->getConnection()->prepare("
                UPDATE projects 
                SET name = ?, description = ?, status = ?, priority = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name,
                $description,
                $status,
                $priority,
                $existing['id']
            ]);
        } else {
            // Create new project
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO projects (name, description, client, priority, status, notion_page_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $name,
                $description,
                'Notion Import',
                $priority,
                $status,
                $page['id']
            ]);
        }
        
        return 1;
    }
    
    // Extract title from Notion page
    private function extractTitle($page) {
        foreach ($page['properties'] as $property) {
            if ($property['type'] === 'title' && !empty($property['title'])) {
                return $property['title'][0]['plain_text'] ?? '';
            }
        }
        return '';
    }
    
    // Extract description from Notion page
    private function extractDescription($page) {
        // Look for common description field names
        $descriptionFields = ['Description', 'Summary', 'Details', 'Notes'];
        
        foreach ($page['properties'] as $name => $property) {
            if (in_array($name, $descriptionFields) && 
                $property['type'] === 'rich_text' && 
                !empty($property['rich_text'])) {
                return $property['rich_text'][0]['plain_text'] ?? '';
            }
        }
        return '';
    }
    
    // Extract status from Notion page
    private function extractStatus($page) {
        $statusFields = ['Status', 'State', 'Progress'];
        
        foreach ($page['properties'] as $name => $property) {
            if (in_array($name, $statusFields)) {
                if ($property['type'] === 'select' && $property['select']) {
                    return $this->mapNotionStatus($property['select']['name']);
                }
                if ($property['type'] === 'status' && $property['status']) {
                    return $this->mapNotionStatus($property['status']['name']);
                }
            }
        }
        return 'planning';
    }
    
    // Extract priority from Notion page
    private function extractPriority($page) {
        $priorityFields = ['Priority', 'Importance', 'Urgency'];
        
        foreach ($page['properties'] as $name => $property) {
            if (in_array($name, $priorityFields) && 
                $property['type'] === 'select' && 
                $property['select']) {
                return $this->mapNotionPriority($property['select']['name']);
            }
        }
        return 'medium';
    }
    
    // Map Notion status to our status
    private function mapNotionStatus($notionStatus) {
        $statusMap = [
            'not started' => 'planning',
            'planning' => 'planning',
            'in progress' => 'active',
            'active' => 'active',
            'review' => 'review',
            'completed' => 'completed',
            'done' => 'completed',
            'paused' => 'paused',
            'on hold' => 'paused'
        ];
        
        return $statusMap[strtolower($notionStatus)] ?? 'planning';
    }
    
    // Map Notion priority to our priority
    private function mapNotionPriority($notionPriority) {
        $priorityMap = [
            'urgent' => 'urgent',
            'high' => 'high',
            'medium' => 'medium',
            'normal' => 'medium',
            'low' => 'low'
        ];
        
        return $priorityMap[strtolower($notionPriority)] ?? 'medium';
    }
    
    // Log sync activity
    private function logSync($service, $syncType, $status, $recordsSynced = 0, $errorMessage = null) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO sync_log (service, sync_type, status, records_synced, error_message, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$service, $syncType, $status, $recordsSynced, $errorMessage]);
        } catch (Exception $e) {
            error_log("Error logging sync: " . $e->getMessage());
        }
    }
}

// Sync Manager - Orchestrates syncing from both services
class SyncManager {
    private $clickup;
    private $notion;
    private $config;
    
    public function __construct() {
        $this->clickup = new ClickUpIntegration();
        $this->notion = new NotionIntegration();
        $this->config = new APIConfig();
    }
    
    // Sync all configured services
    public function syncAll($clickupTeamId = null, $notionDatabaseId = null) {
        $results = [];
        
        // Sync ClickUp if configured
        if ($this->config->isServiceConfigured('clickup') && $clickupTeamId) {
            try {
                $results['clickup'] = $this->clickup->syncToDatabase($clickupTeamId);
            } catch (Exception $e) {
                $results['clickup'] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Sync Notion if configured
        if ($this->config->isServiceConfigured('notion')) {
            try {
                $results['notion'] = $this->notion->syncToDatabase($notionDatabaseId);
            } catch (Exception $e) {
                $results['notion'] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    // Get sync status
    public function getSyncStatus() {
        try {
            $stmt = $this->config->db->getConnection()->prepare("
                SELECT service, status, records_synced, error_message, created_at
                FROM sync_log 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>