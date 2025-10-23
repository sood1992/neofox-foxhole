<?php
// simple_dashboard_test.php - Test if dashboard would work
session_start();

echo "<!DOCTYPE html>";
echo "<html><head><title>Dashboard Test</title></head><body>";
echo "<h1>🎯 Dashboard Test</h1>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>🔐 Session Check</h2>";

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    echo "<p style='color: green; font-size: 18px;'>✅ USER IS AUTHENTICATED!</p>";
    echo "<p><strong>User Role:</strong> " . ($_SESSION['user_role'] ?? 'unknown') . "</p>";
    echo "<p><strong>User Name:</strong> " . ($_SESSION['user_name'] ?? 'unknown') . "</p>";
    
    echo "<h3>✅ Dashboard would load successfully!</h3>";
    echo "<p>This user would have access to the dashboard.</p>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎉 SUCCESS! Dashboard Access Granted</h3>";
    echo "<p>The authentication system is working correctly.</p>";
    echo "<p><a href='dashboard.php' style='background: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Real Dashboard</a></p>";
    echo "</div>";
    
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ USER NOT AUTHENTICATED</p>";
    echo "<p>User would be redirected to login page.</p>";
    
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Access Denied</h3>";
    echo "<p>User is not logged in. Dashboard would redirect to login.</p>";
    echo "<p><a href='index.php' style='background: #DC2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Login</a></p>";
    echo "</div>";
}

echo "<h2>📋 Current Session Data</h2>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 4px;'>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>🔗 Quick Actions</h2>";
echo "<p><a href='super_simple_test.php'>← Back to Login Test</a></p>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";

// Test database connection
echo "<h2>🗄️ Database Test</h2>";
try {
    if (file_exists('config-2.php')) {
        require_once 'config-2.php';
        $db = new Database();
        echo "<p style='color: green;'>✅ Database connection successful</p>";
    } else {
        echo "<p style='color: red;'>❌ config-2.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
}
a {
    color: #4F46E5;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>

</body>
</html>