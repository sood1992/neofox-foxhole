<?php
// minimal_login.php - Simplified login that should definitely work
session_start();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    
    if ($action === 'admin_login') {
        if ($password === '838838') {
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_name'] = 'Administrator';
            $_SESSION['authenticated'] = true;
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid admin password';
        }
    }
    
    if ($action === 'pm_login') {
        if ($password === 'ZinX1234!@#$') {
            $_SESSION['user_role'] = 'project_manager';
            $_SESSION['user_name'] = 'Project Manager';
            $_SESSION['authenticated'] = true;
            header('Location: project_manager.php');
            exit;
        } else {
            $error = 'Invalid PM password';
        }
    }
    
    if ($action === 'employee_login') {
        if (!empty($name)) {
            // For now, accept any name
            $_SESSION['user_role'] = 'employee';
            $_SESSION['user_name'] = $name;
            $_SESSION['authenticated'] = true;
            header('Location: employee_dashboard.php');
            exit;
        } else {
            $error = 'Please enter your name';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole - Minimal Login</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #4F46E5;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #4F46E5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 10px;
        }
        button:hover {
            background: #3730A3;
        }
        .pm-btn {
            background: #F59E0B;
        }
        .pm-btn:hover {
            background: #D97706;
        }
        .emp-btn {
            background: #10B981;
        }
        .emp-btn:hover {
            background: #059669;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            font-weight: 600;
        }
        .tab.active {
            color: #4F46E5;
            border-bottom-color: #4F46E5;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🦊 Foxhole Login</h1>
        
        <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('admin')">Admin</button>
            <button class="tab" onclick="showTab('pm')">PM</button>
            <button class="tab" onclick="showTab('employee')">Employee</button>
        </div>
        
        <!-- Admin Login -->
        <div id="admin" class="tab-content active">
            <form method="POST">
                <input type="hidden" name="action" value="admin_login">
                <div class="form-group">
                    <label>Admin Password:</label>
                    <input type="password" name="password" placeholder="Enter admin password" required>
                </div>
                <button type="submit">Login as Admin</button>
            </form>
        </div>
        
        <!-- PM Login -->
        <div id="pm" class="tab-content">
            <form method="POST">
                <input type="hidden" name="action" value="pm_login">
                <div class="form-group">
                    <label>Project Manager Password:</label>
                    <input type="password" name="password" placeholder="Enter PM password" required>
                </div>
                <button type="submit" class="pm-btn">Login as Project Manager</button>
            </form>
        </div>
        
        <!-- Employee Login -->
        <div id="employee" class="tab-content">
            <form method="POST">
                <input type="hidden" name="action" value="employee_login">
                <div class="form-group">
                    <label>Your Name:</label>
                    <input type="text" name="name" placeholder="Enter your first name" required>
                </div>
                <button type="submit" class="emp-btn">Login as Employee</button>
            </form>
        </div>
        
        <p style="text-align: center; margin-top: 20px; font-size: 14px; color: #666;">
            This is a simplified login to test basic functionality.
        </p>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>