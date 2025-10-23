<?php
// final_test.php - Final working test with session fix
ob_start();

// Fix session path
$sessionPath = dirname(__FILE__) . '/temp_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Login Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 { color: #333; text-align: center; }
        .form-group { margin: 20px 0; }
        input { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { width: 100%; padding: 15px; background: #4F46E5; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #3730A3; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🦊 Final Login Test</h1>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php
            $action = $_POST['action'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($action === 'admin_login' && $password === '838838') {
                $_SESSION['user_role'] = 'admin';
                $_SESSION['user_name'] = 'Administrator';
                $_SESSION['authenticated'] = true;
                $_SESSION['login_time'] = time();
                
                echo '<div class="result success">';
                echo '<h3>✅ LOGIN SUCCESSFUL!</h3>';
                echo '<p>You are now logged in as Administrator.</p>';
                echo '<p><strong>Session ID:</strong> ' . session_id() . '</p>';
                echo '<p><strong>Session Data:</strong></p>';
                echo '<pre>' . print_r($_SESSION, true) . '</pre>';
                echo '<p><a href="dashboard.php" style="background: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Go to Dashboard</a></p>';
                echo '</div>';
            } else {
                echo '<div class="result error">';
                echo '<h3>❌ LOGIN FAILED</h3>';
                echo '<p>Invalid password. Please try again.</p>';
                echo '</div>';
            }
            ?>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="admin_login">
            
            <div class="form-group">
                <label>Admin Password:</label>
                <input type="password" name="password" placeholder="Enter admin password" required>
            </div>
            
            <button type="submit">Login as Admin</button>
        </form>
        
        <div class="result info">
            <h3>🔍 Session Information</h3>
            <p><strong>Session Status:</strong> <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?></p>
            <p><strong>Session ID:</strong> <?= session_id() ?></p>
            <p><strong>Session Path:</strong> <?= session_save_path() ?></p>
            <p><strong>Current Session:</strong></p>
            <pre><?= empty($_SESSION) ? 'No session data' : print_r($_SESSION, true) ?></pre>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p><strong>Test Password:</strong> 838838</p>
            <p><a href="index.php">← Back to Main Login</a></p>
        </div>
    </div>
</body>
</html>