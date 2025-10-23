<?php
// test_employee_data.php - Test script to check employee data and add test tasks
session_start();
require_once 'config-2.php';
require_once 'api_enhanced.php';

// Allow testing without login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default to employee ID 1 for testing
    $_SESSION['user_name'] = 'Test User';
}

$employeeId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Employee Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        pre {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #10b981;
            background: #d1fae5;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #ef4444;
            background: #fee2e2;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .button:hover {
            background: #5a67d8;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #fbbf24;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Employee Data Test - Employee ID: <?= $employeeId ?></h1>
    
    <?php
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Test 1: Check if employee exists
        echo "<div class='section'>";
        echo "<h2>1. Employee Check</h2>";
        
        $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee) {
            echo "<div class='success'>✓ Employee found!</div>";
            echo "<pre>";
            print_r($employee);
            echo "</pre>";
        } else {
            echo "<div class='error'>✗ Employee NOT found with ID: $employeeId</div>";
            
            // Try to create the employee
            if (isset($_GET['create_employee'])) {
                $stmt = $db->prepare("INSERT INTO employees (id, name, email, role, type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$employeeId, 'Test Employee', 'test@example.com', 'Developer', 'employee', 'active']);
                echo "<div class='success'>Created test employee!</div>";
                echo "<script>window.location.href='test_employee_data.php';</script>";
            } else {
                echo "<a href='?create_employee=1' class='button'>Create Test Employee</a>";
            }
        }
        echo "</div>";
        
        // Test 2: Check tasks
        echo "<div class='section'>";
        echo "<h2>2. Tasks Check</h2>";
        
        $stmt = $db->prepare("SELECT * FROM tasks WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Found " . count($tasks) . " tasks</p>";
        
        if (count($tasks) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Due Date</th></tr>";
            foreach ($tasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>{$task['title']}</td>";
                echo "<td>{$task['status']}</td>";
                echo "<td>{$task['priority']}</td>";
                echo "<td>{$task['due_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>No tasks found. Would you like to add test tasks?</div>";
            echo "<a href='?add_tasks=1' class='button'>Add Test Tasks</a>";
        }
        echo "</div>";
        
        // Add test tasks if requested
        if (isset($_GET['add_tasks'])) {
            echo "<div class='section'>";
            echo "<h2>Adding Test Tasks...</h2>";
            
            $testTasks = [
                ['Complete Dashboard Testing', 'Test all dashboard features and fix bugs', 'in_progress', 'high', date('Y-m-d', strtotime('+3 days'))],
                ['Fix Attendance Bug', 'Location verification not working properly', 'todo', 'urgent', date('Y-m-d', strtotime('-1 day'))],
                ['Update Documentation', 'Update API documentation for new endpoints', 'completed', 'medium', date('Y-m-d')],
                ['Review Code', 'Review pull requests from team members', 'todo', 'medium', date('Y-m-d', strtotime('+5 days'))],
                ['Client Meeting Preparation', 'Prepare presentation for client meeting', 'in_progress', 'high', date('Y-m-d', strtotime('+2 days'))]
            ];
            
            $stmt = $db->prepare("
                INSERT INTO tasks (employee_id, title, description, status, priority, due_date, created_at, last_activity, hours_logged, estimated_hours) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)
            ");
            
            foreach ($testTasks as $task) {
                $hoursLogged = rand(0, 8);
                $estimatedHours = rand(4, 16);
                
                try {
                    $stmt->execute([
                        $employeeId, 
                        $task[0], 
                        $task[1], 
                        $task[2], 
                        $task[3], 
                        $task[4],
                        $hoursLogged,
                        $estimatedHours
                    ]);
                    echo "<div class='success'>✓ Added task: {$task[0]}</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>✗ Failed to add task: {$task[0]} - " . $e->getMessage() . "</div>";
                }
            }
            
            echo "<p><a href='test_employee_data.php' class='button'>Refresh Page</a></p>";
            echo "</div>";
        }
        
        // Test 3: Test API
        echo "<div class='section'>";
        echo "<h2>3. API Test</h2>";
        
        $api = new DashboardAPI();
        $stats = $api->getEmployeeDetailedStats($employeeId);
        
        echo "<h3>API Response Summary:</h3>";
        echo "<ul>";
        echo "<li>Total Tasks: " . ($stats['total_tasks'] ?? 0) . "</li>";
        echo "<li>Active Tasks: " . ($stats['active_tasks'] ?? 0) . "</li>";
        echo "<li>Completed Tasks: " . ($stats['completed_tasks'] ?? 0) . "</li>";
        echo "<li>Overdue Tasks: " . ($stats['overdue_tasks'] ?? 0) . "</li>";
        echo "<li>Tasks Array Count: " . count($stats['tasks'] ?? []) . "</li>";
        echo "</ul>";
        
        if (isset($_GET['show_full_api'])) {
            echo "<h3>Full API Response:</h3>";
            echo "<pre>";
            print_r($stats);
            echo "</pre>";
        } else {
            echo "<a href='?show_full_api=1' class='button'>Show Full API Response</a>";
        }
        echo "</div>";
        
        // Test 4: Check attendance tables
        echo "<div class='section'>";
        echo "<h2>4. Attendance Tables Check</h2>";
        
        // Check if attendance table exists
        $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✓ Attendance table exists</div>";
        } else {
            echo "<div class='error'>✗ Attendance table missing!</div>";
            echo "<p>Run the setup script: <a href='setup_attendance.php' class='button'>Setup Attendance</a></p>";
        }
        
        // Check office locations
        $stmt = $db->query("SELECT * FROM office_locations");
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($locations) > 0) {
            echo "<div class='success'>✓ Office locations configured</div>";
            echo "<pre>";
            print_r($locations);
            echo "</pre>";
        } else {
            echo "<div class='error'>✗ No office locations configured</div>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='section'>";
        echo "<div class='error'>Database Error: " . $e->getMessage() . "</div>";
        echo "</div>";
    }
    ?>
    
    <div class="section">
        <h2>Quick Actions</h2>
        <a href="employee_dashboard.php" class="button">Go to Employee Dashboard</a>
        <a href="?add_tasks=1" class="button">Add Test Tasks</a>
        <a href="test_employee_data.php" class="button">Refresh</a>
        <a href="setup_attendance.php" class="button">Setup Attendance System</a>
    </div>
    
    <div class="warning">
        <h3>⚠️ Important Notes:</h3>
        <ul>
            <li>Make sure you're logged in as an employee (not admin) to see the employee dashboard</li>
            <li>The attendance system requires the attendance tables to be created first</li>
            <li>Office locations must be configured with CR Park coordinates</li>
            <li>Your browser must allow location access for check-in to work</li>
        </ul>
    </div>
</body>
</html>