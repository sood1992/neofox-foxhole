<?php
// auth.php - Fixed version with consistent session handling
ob_start();

// Consistent session configuration
$sessionPath = dirname(__FILE__) . '/temp_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class FoxholeAuth {
    private $adminPassword = '838838';
    private $pmPassword = 'ZinX1234!@#$';
    private $sessionTimeout = 3600; // 1 hour
    
    public function __construct() {
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->sessionTimeout)) {
            $this->logout();
        }
        $_SESSION['last_activity'] = time();
    }
    
    // Authenticate admin
    public function authenticateAdmin($password) {
        if ($password === $this->adminPassword) {
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_id'] = 0;
            $_SESSION['user_name'] = 'Administrator';
            $_SESSION['login_time'] = time();
            $_SESSION['authenticated'] = true;
            
            return true;
        }
        return false;
    }
    
    // Authenticate project manager
    public function authenticatePM($password) {
        if ($password === $this->pmPassword) {
            $_SESSION['user_role'] = 'project_manager';
            $_SESSION['user_id'] = 0;
            $_SESSION['user_name'] = 'Project Manager';
            $_SESSION['login_time'] = time();
            $_SESSION['authenticated'] = true;
            
            return true;
        }
        return false;
    }
    
    // Authenticate employee by name
    public function authenticateEmployee($firstName) {
        try {
            // For testing, create mock employees if API is not available
            $employees = [
                ['id' => 1, 'name' => 'John Doe', 'role' => 'Developer'],
                ['id' => 2, 'name' => 'Jane Smith', 'role' => 'Designer'],
                ['id' => 3, 'name' => 'Mike Johnson', 'role' => 'Manager'],
                ['id' => 4, 'name' => 'Sarah Wilson', 'role' => 'Developer'],
                ['id' => 5, 'name' => 'Test User', 'role' => 'Tester']
            ];
            
            // Try to get real employees from API if available
            if (file_exists('api_enhanced.php')) {
                require_once 'config-2.php';
                require_once 'api_enhanced.php';
                try {
                    $api = new DashboardAPI();
                    $apiEmployees = $api->getEmployees();
                    if (!empty($apiEmployees)) {
                        $employees = $apiEmployees;
                    }
                } catch (Exception $e) {
                    // Use mock data if API fails
                }
            }
            
            foreach ($employees as $employee) {
                $empFirstName = explode(' ', $employee['name'])[0];
                if (strcasecmp($empFirstName, $firstName) === 0) {
                    $_SESSION['user_role'] = 'employee';
                    $_SESSION['user_id'] = $employee['id'];
                    $_SESSION['user_name'] = $employee['name'];
                    $_SESSION['employee_data'] = $employee;
                    $_SESSION['login_time'] = time();
                    $_SESSION['authenticated'] = true;
                    
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            error_log("Employee auth error: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if user is authenticated
    public function isAuthenticated() {
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }
    
    // Check user role
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    // Require authentication
    public function requireAuth($allowedRoles = []) {
        if (!$this->isAuthenticated()) {
            header('Location: index.php');
            exit();
        }
        
        if (!empty($allowedRoles) && !in_array($_SESSION['user_role'], $allowedRoles)) {
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied');
        }
    }
    
    // Get current user data
    public function getCurrentUser() {
        if ($this->isAuthenticated()) {
            return [
                'id' => $_SESSION['user_id'] ?? null,
                'name' => $_SESSION['user_name'] ?? null,
                'role' => $_SESSION['user_role'] ?? null,
                'login_time' => $_SESSION['login_time'] ?? null,
                'employee_data' => $_SESSION['employee_data'] ?? null
            ];
        }
        return null;
    }
    
    // Logout
    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    
    // Log user activity (stub for now)
    public function logActivity($activity, $type = 'general') {
        // This is a stub - implement later if needed
        return true;
    }
}

// Handle AJAX authentication requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    $auth = new FoxholeAuth();
    $response = ['success' => false, 'error' => 'Unknown error'];
    
    try {
        switch ($_POST['action']) {
            case 'admin_login':
                $password = $_POST['password'] ?? '';
                if ($auth->authenticateAdmin($password)) {
                    $response = ['success' => true, 'redirect' => 'dashboard.php'];
                } else {
                    $response = ['success' => false, 'error' => 'Invalid password'];
                }
                break;
                
            case 'pm_login':
                $password = $_POST['password'] ?? '';
                if ($auth->authenticatePM($password)) {
                    $response = ['success' => true, 'redirect' => 'project_manager.php'];
                } else {
                    $response = ['success' => false, 'error' => 'Invalid password'];
                }
                break;
                
            case 'employee_login':
                $name = $_POST['name'] ?? '';
                if ($auth->authenticateEmployee($name)) {
                    $response = ['success' => true, 'redirect' => 'employee_dashboard.php'];
                } else {
                    $response = ['success' => false, 'error' => 'Employee not found. Try: John, Jane, Mike, Sarah, or Test'];
                }
                break;
                
            case 'logout':
                $auth->logout();
                $response = ['success' => true, 'redirect' => 'index.php'];
                break;
                
            default:
                $response = ['success' => false, 'error' => 'Invalid action'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'error' => 'Server error: ' . $e->getMessage()];
    }
    
    echo json_encode($response);
    exit();
}

// Handle logout via GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth = new FoxholeAuth();
    $auth->logout();
    header('Location: index.php');
    exit();
}

// If accessed directly via GET (for testing)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename($_SERVER['PHP_SELF']) === 'auth.php') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ready',
        'message' => 'Foxhole Auth System Ready',
        'session_active' => session_status() === PHP_SESSION_ACTIVE,
        'session_id' => session_id(),
        'authenticated' => isset($_SESSION['authenticated']) ? $_SESSION['authenticated'] : false,
        'user' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}
?>