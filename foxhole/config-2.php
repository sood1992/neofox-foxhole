<?php
// config-2.php - Enhanced Database configuration with API settings
class Database {
    private $host = 'localhost';
    private $username = 'sunburni_foxhole';
    private $password = 'sunburni_foxhole';
    private $database = 'sunburni_foxhole';
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database}",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// API Configuration Class
class APIConfig {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Store API keys securely in database
    public function setAPIKey($service, $apiKey, $settings = []) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO integrations (service, api_key, settings, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                api_key = VALUES(api_key), 
                settings = VALUES(settings),
                last_sync = NOW()
            ");
            return $stmt->execute([$service, $apiKey, json_encode($settings)]);
        } catch (Exception $e) {
            error_log("Error setting API key: " . $e->getMessage());
            return false;
        }
    }
    
    // Get API key for service
    public function getAPIKey($service) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT api_key, settings FROM integrations WHERE service = ?
            ");
            $stmt->execute([$service]);
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'api_key' => $result['api_key'],
                    'settings' => json_decode($result['settings'], true) ?: []
                ];
            }
            return null;
        } catch (Exception $e) {
            error_log("Error getting API key: " . $e->getMessage());
            return null;
        }
    }
    
    // Check if service is configured
    public function isServiceConfigured($service) {
        $config = $this->getAPIKey($service);
        return $config && !empty($config['api_key']);
    }
    
    // Update last sync time
    public function updateLastSync($service) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                UPDATE integrations SET last_sync = NOW() WHERE service = ?
            ");
            return $stmt->execute([$service]);
        } catch (Exception $e) {
            error_log("Error updating last sync: " . $e->getMessage());
            return false;
        }
    }
}

// Database Setup SQL - Run this once to create/update tables
function setupDatabase() {
    $db = new Database();
    $connection = $db->getConnection();
    
    // Create integrations table if not exists
    $sql = "
    CREATE TABLE IF NOT EXISTS integrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service VARCHAR(50) UNIQUE NOT NULL,
        api_key VARCHAR(500) NOT NULL,
        webhook_url VARCHAR(200) NULL,
        settings JSON NULL,
        last_sync TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Add clickup_user_id and notion_user_id to employees if not exists
    ALTER TABLE employees 
    ADD COLUMN IF NOT EXISTS clickup_user_id VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS notion_user_id VARCHAR(50) NULL;
    
    -- Add external IDs to projects if not exists  
    ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS clickup_project_id VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS notion_page_id VARCHAR(50) NULL;
    
    -- Add external IDs to tasks if not exists
    ALTER TABLE tasks
    ADD COLUMN IF NOT EXISTS clickup_task_id VARCHAR(50) NULL;
    
    -- Add sync status tracking
    CREATE TABLE IF NOT EXISTS sync_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service VARCHAR(50) NOT NULL,
        sync_type VARCHAR(50) NOT NULL,
        status ENUM('success', 'error', 'partial') NOT NULL,
        records_synced INT DEFAULT 0,
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    try {
        $connection->exec($sql);
        return true;
    } catch (Exception $e) {
        error_log("Database setup error: " . $e->getMessage());
        return false;
    }
}

// Run setup if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'config-2.php' && isset($_GET['setup'])) {
    header('Content-Type: application/json');
    $result = setupDatabase();
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Database setup completed successfully!' : 'Database setup failed - check error logs'
    ]);
}
?>