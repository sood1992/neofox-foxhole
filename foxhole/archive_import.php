<?php
// archive_import.php - Import historical data into Foxhole
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config-2.php';
require_once 'api_enhanced.php';

// Initialize API
$api = new DashboardAPI();

// Get current employees and projects for dropdowns
$employees = $api->getEmployees();
$projects = $api->getProjects();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $importType = $_POST['import_type'] ?? '';
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    switch ($importType) {
        case 'bulk_employees':
            $employeeData = $_POST['bulk_employees'] ?? '';
            $lines = explode("\n", trim($employeeData));
            
            foreach ($lines as $line) {
                $parts = str_getcsv($line);
                if (count($parts) >= 3) {
                    $name = trim($parts[0]);
                    $email = trim($parts[1]);
                    $role = trim($parts[2]);
                    $type = isset($parts[3]) ? trim($parts[3]) : 'employee';
                    
                    if ($name && $email && $role) {
                        if ($api->addEmployee($name, $email, $role, $type)) {
                            $successCount++;
                        } else {
                            $errorCount++;
                            $errors[] = "Failed to add: $name";
                        }
                    }
                }
            }
            
            // Refresh employees list after adding
            $employees = $api->getEmployees();
            
            $message = "Employees Import: $successCount added successfully";
            if ($errorCount > 0) {
                $message .= ", $errorCount failed";
                if (!empty($errors)) {
                    $message .= " (" . implode(", ", array_slice($errors, 0, 3)) . ")";
                }
            }
            $messageType = $errorCount > 0 ? 'warning' : 'success';
            break;
            
        case 'bulk_projects':
            $projectData = $_POST['bulk_projects'] ?? '';
            $lines = explode("\n", trim($projectData));
            
            foreach ($lines as $line) {
                $parts = str_getcsv($line);
                if (count($parts) >= 1) {
                    $name = trim($parts[0]);
                    $client = isset($parts[1]) ? trim($parts[1]) : '';
                    $priority = isset($parts[2]) ? trim($parts[2]) : 'medium';
                    $startDate = isset($parts[3]) ? trim($parts[3]) : null;
                    $endDate = isset($parts[4]) ? trim($parts[4]) : null;
                    $description = isset($parts[5]) ? trim($parts[5]) : '';
                    
                    if ($name) {
                        if ($api->addProject($name, $description, $client, $priority, $startDate, $endDate)) {
                            $successCount++;
                        } else {
                            $errorCount++;
                            $errors[] = "Failed to add: $name";
                        }
                    }
                }
            }
            
            // Refresh projects list after adding
            $projects = $api->getProjects();
            
            $message = "Projects Import: $successCount added successfully";
            if ($errorCount > 0) {
                $message .= ", $errorCount failed";
            }
            $messageType = $errorCount > 0 ? 'warning' : 'success';
            break;
            
        case 'bulk_tasks':
            $tasksData = $_POST['bulk_tasks'] ?? '';
            $lines = explode("\n", trim($tasksData));
            
            foreach ($lines as $line) {
                $parts = str_getcsv($line);
                if (count($parts) >= 1) {
                    $title = trim($parts[0]);
                    $employeeId = isset($parts[1]) && $parts[1] ? trim($parts[1]) : null;
                    $projectId = isset($parts[2]) && $parts[2] ? trim($parts[2]) : null;
                    $priority = isset($parts[3]) ? trim($parts[3]) : 'medium';
                    $status = isset($parts[4]) ? trim($parts[4]) : 'completed';
                    $dueDate = isset($parts[5]) ? trim($parts[5]) : null;
                    $estimatedHours = isset($parts[6]) ? trim($parts[6]) : null;
                    $hoursLogged = isset($parts[7]) ? trim($parts[7]) : null;
                    $description = isset($parts[8]) ? trim($parts[8]) : '';
                    
                    if ($title) {
                        // Add the task
                        if ($api->addTask($employeeId, $projectId, $title, $description, $priority, $dueDate, $estimatedHours)) {
                            $successCount++;
                            
                            // If task was added and we have hours logged or status to update
                            if ($hoursLogged || $status !== 'todo') {
                                // Get the newly created task
                                $tasks = $api->getTasks();
                                $newTaskId = null;
                                foreach ($tasks as $task) {
                                    if ($task['title'] === $title) {
                                        $newTaskId = $task['id'];
                                        break;
                                    }
                                }
                                
                                if ($newTaskId) {
                                    // Update status if not todo
                                    if ($status !== 'todo') {
                                        $api->updateTaskStatus($newTaskId, $status);
                                    }
                                    
                                    // Update hours if provided
                                    if ($hoursLogged) {
                                        $api->updateTaskHours($newTaskId, $hoursLogged);
                                    }
                                }
                            }
                        } else {
                            $errorCount++;
                            $errors[] = "Failed to add task: $title";
                        }
                    }
                }
            }
            
            $message = "Tasks Import: $successCount added successfully";
            if ($errorCount > 0) {
                $message .= ", $errorCount failed";
            }
            $messageType = $errorCount > 0 ? 'warning' : 'success';
            break;
            
        case 'complete_project':
            // Complete project with tasks
            $projectName = $_POST['project_name'] ?? '';
            $projectClient = $_POST['project_client'] ?? '';
            $projectPriority = $_POST['project_priority'] ?? 'medium';
            $projectStatus = $_POST['project_status'] ?? 'active';
            $projectStartDate = $_POST['project_start_date'] ?? null;
            $projectEndDate = $_POST['project_end_date'] ?? null;
            $projectDescription = $_POST['project_description'] ?? '';
            $tasksData = $_POST['project_tasks'] ?? '';
            
            if ($projectName) {
                if ($api->addProject($projectName, $projectDescription, $projectClient, $projectPriority, $projectStartDate, $projectEndDate)) {
                    $successCount++;
                    
                    // Get the newly created project ID
                    $projects = $api->getProjects();
                    $newProjectId = null;
                    foreach ($projects as $proj) {
                        if ($proj['name'] === $projectName) {
                            $newProjectId = $proj['id'];
                            break;
                        }
                    }
                    
                    // Add tasks if project was created successfully
                    if ($newProjectId && $tasksData) {
                        $taskLines = explode("\n", trim($tasksData));
                        foreach ($taskLines as $taskLine) {
                            $taskParts = str_getcsv($taskLine);
                            if (count($taskParts) >= 1) {
                                $taskTitle = trim($taskParts[0]);
                                $taskEmployeeId = isset($taskParts[1]) && $taskParts[1] ? trim($taskParts[1]) : null;
                                $taskPriority = isset($taskParts[2]) ? trim($taskParts[2]) : 'medium';
                                $taskStatus = isset($taskParts[3]) ? trim($taskParts[3]) : 'completed';
                                $taskDueDate = isset($taskParts[4]) ? trim($taskParts[4]) : null;
                                $taskEstimatedHours = isset($taskParts[5]) ? trim($taskParts[5]) : null;
                                $taskHoursLogged = isset($taskParts[6]) ? trim($taskParts[6]) : null;
                                
                                if ($taskTitle) {
                                    if ($api->addTask($taskEmployeeId, $newProjectId, $taskTitle, '', $taskPriority, $taskDueDate, $taskEstimatedHours)) {
                                        // Get the task ID and update status/hours
                                        $tasks = $api->getTasks(['project_id' => $newProjectId]);
                                        foreach ($tasks as $task) {
                                            if ($task['title'] === $taskTitle) {
                                                if ($taskStatus !== 'todo') {
                                                    $api->updateTaskStatus($task['id'], $taskStatus);
                                                }
                                                if ($taskHoursLogged) {
                                                    $api->updateTaskHours($task['id'], $taskHoursLogged);
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    $message = "Project '$projectName' created successfully with tasks";
                    $messageType = 'success';
                } else {
                    $message = "Failed to create project";
                    $messageType = 'error';
                }
            } else {
                $message = "Project name is required";
                $messageType = 'error';
            }
            break;
    }
}

// Get current stats
$tasks = $api->getTasks();
$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, function($t) { return $t['status'] === 'completed'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole - Archive Data Import</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
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
            
            --radius-md: 12px;
            --radius-lg: 16px;
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
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--gray-600);
            font-size: 16px;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--gray-200);
            padding-bottom: 0;
            flex-wrap: wrap;
        }

        .tab {
            padding: 1rem 2rem;
            background: none;
            border: none;
            font-weight: 600;
            color: var(--gray-600);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .import-section {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-textarea {
            min-height: 200px;
            resize: vertical;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .help-text {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 6px;
        }

        .example-box {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-top: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            white-space: pre-wrap;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 2px solid rgba(16, 185, 129, 0.3);
        }

        .message.warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 2px solid rgba(245, 158, 11, 0.3);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 2px solid rgba(239, 68, 68, 0.3);
        }

        .stats-box {
            background: var(--gray-50);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 3rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray-600);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .employee-list {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-top: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .employee-ref {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .employee-id {
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-family: monospace;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            .tabs {
                gap: 0.5rem;
            }
            .tab {
                padding: 0.8rem 1rem;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-archive"></i> Archive Data Import</h1>
            <p>Import historical data with complete employee assignments and task details</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="project_manager.php" class="btn btn-secondary">
                    <i class="fas fa-tasks"></i> Project Manager
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="stats-box">
            <div class="stat">
                <div class="stat-number"><?= count($employees) ?></div>
                <div class="stat-label">Current Employees</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= count($projects) ?></div>
                <div class="stat-label">Current Projects</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $totalTasks ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $completedTasks ?></div>
                <div class="stat-label">Completed Tasks</div>
            </div>
        </div>

        <!-- Employee Reference List -->
        <?php if (!empty($employees)): ?>
        <div class="import-section" style="background: var(--gray-50);">
            <h3 style="font-size: 16px; margin-bottom: 1rem;">
                <i class="fas fa-info-circle"></i> Employee ID Reference
            </h3>
            <div class="employee-list">
                <?php foreach ($employees as $emp): ?>
                <div class="employee-ref">
                    <span class="employee-id"><?= $emp['id'] ?></span>
                    <span><?= htmlspecialchars($emp['name']) ?> - <?= htmlspecialchars($emp['role']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="help-text" style="margin-top: 0.5rem;">
                Use these employee IDs when assigning tasks in the CSV format
            </div>
        </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="showTab('bulk-employees')">
                <i class="fas fa-users"></i> Bulk Employees
            </button>
            <button class="tab" onclick="showTab('bulk-projects')">
                <i class="fas fa-project-diagram"></i> Bulk Projects
            </button>
            <button class="tab" onclick="showTab('bulk-tasks')">
                <i class="fas fa-tasks"></i> Bulk Tasks
            </button>
            <button class="tab" onclick="showTab('complete-project')">
                <i class="fas fa-rocket"></i> Complete Project
            </button>
        </div>

        <!-- Bulk Employees Tab -->
        <div id="bulk-employees" class="tab-content active">
            <div class="import-section">
                <h2 class="section-title">
                    <i class="fas fa-users"></i>
                    Bulk Import Employees
                </h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="import_type" value="bulk_employees">
                    
                    <div class="form-group">
                        <label class="form-label">Employee Data (CSV Format)</label>
                        <textarea name="bulk_employees" class="form-textarea" placeholder="Name, Email, Role, Type (optional)
John Doe, john@company.com, Senior Developer, employee
Jane Smith, jane@company.com, Project Manager, employee
Bob Wilson, bob@freelance.com, UI Designer, freelancer" required></textarea>
                        <div class="help-text">
                            Enter one employee per line in CSV format: Name, Email, Role, Type (employee/freelancer)
                        </div>
                        <div class="example-box">Example:
Sarah Johnson, sarah@company.com, Marketing Director, employee
Mike Chen, mike@company.com, Video Editor, employee
Lisa Park, lisa@freelance.com, Content Writer, freelancer</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import Employees
                    </button>
                </form>
            </div>
        </div>

        <!-- Bulk Projects Tab -->
        <div id="bulk-projects" class="tab-content">
            <div class="import-section">
                <h2 class="section-title">
                    <i class="fas fa-project-diagram"></i>
                    Bulk Import Projects
                </h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="import_type" value="bulk_projects">
                    
                    <div class="form-group">
                        <label class="form-label">Project Data (CSV Format)</label>
                        <textarea name="bulk_projects" class="form-textarea" placeholder="Project Name, Client, Priority, Start Date, End Date, Description
Website Redesign, ABC Corp, high, 2024-01-15, 2024-03-30, Complete website overhaul
Marketing Campaign, XYZ Inc, urgent, 2024-02-01, 2024-02-28, Q1 marketing push" required></textarea>
                        <div class="help-text">
                            Enter one project per line: Name, Client, Priority (low/medium/high/urgent), Start Date (YYYY-MM-DD), End Date, Description
                        </div>
                        <div class="example-box">Example:
E-commerce Platform, ShopEasy, high, 2023-06-01, 2023-12-31, Build complete e-commerce solution
Brand Identity, StartupX, medium, 2023-09-15, 2023-10-30, Logo and brand guidelines
Video Campaign, Nike, urgent, 2023-11-01, 2023-11-30, Product launch videos</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import Projects
                    </button>
                </form>
            </div>
        </div>

        <!-- Bulk Tasks Tab -->
        <div id="bulk-tasks" class="tab-content">
            <div class="import-section">
                <h2 class="section-title">
                    <i class="fas fa-tasks"></i>
                    Bulk Import Tasks
                </h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="import_type" value="bulk_tasks">
                    
                    <div class="form-group">
                        <label class="form-label">Task Data (CSV Format)</label>
                        <textarea name="bulk_tasks" class="form-textarea" placeholder="Task Title, Employee ID, Project ID, Priority, Status, Due Date, Est Hours, Logged Hours, Description
Design homepage mockup, 1, 2, high, completed, 2023-02-15, 8, 7.5, Create responsive mockup
Implement payment gateway, 2, 1, urgent, completed, 2023-03-20, 16, 18, Stripe integration
Write content strategy, 3, 2, medium, in_progress, 2023-04-10, 6, 3, Content planning doc" required></textarea>
                        <div class="help-text">
                            Format: Title, Employee ID (optional), Project ID (optional), Priority, Status, Due Date, Estimated Hours, Logged Hours, Description
                            <br>Status options: todo, in_progress, review, completed
                            <br>Priority options: low, medium, high, urgent
                        </div>
                        <div class="example-box">Example with employee assignments:
Create database schema, <?= !empty($employees) ? $employees[0]['id'] : '1' ?>, <?= !empty($projects) ? $projects[0]['id'] : '1' ?>, high, completed, 2023-01-10, 4, 4.5, Design MySQL schema
Design UI components, <?= !empty($employees) && count($employees) > 1 ? $employees[1]['id'] : '2' ?>, <?= !empty($projects) ? $projects[0]['id'] : '1' ?>, medium, completed, 2023-01-15, 12, 11, React components
API development, <?= !empty($employees) ? $employees[0]['id'] : '1' ?>, <?= !empty($projects) ? $projects[0]['id'] : '1' ?>, high, completed, 2023-01-20, 20, 22, REST API endpoints</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import Tasks
                    </button>
                </form>
            </div>
        </div>

        <!-- Complete Project Tab -->
        <div id="complete-project" class="tab-content">
            <div class="import-section">
                <h2 class="section-title">
                    <i class="fas fa-rocket"></i>
                    Import Complete Project with Tasks
                </h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="import_type" value="complete_project">
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Project Name *</label>
                            <input type="text" name="project_name" class="form-input" placeholder="e.g., Q4 Marketing Campaign" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <input type="text" name="project_client" class="form-input" placeholder="e.g., ABC Corporation">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select name="project_priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="project_status" class="form-select">
                                <option value="planning">Planning</option>
                                <option value="active">Active</option>
                                <option value="completed" selected>Completed</option>
                                <option value="on_hold">On Hold</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="project_start_date" class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="date" name="project_end_date" class="form-input">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Project Description</label>
                        <textarea name="project_description" class="form-input" rows="3" placeholder="Brief project description..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Project Tasks (CSV Format)</label>
                        <textarea name="project_tasks" class="form-textarea" placeholder="Task Title, Employee ID, Priority, Status, Due Date, Est Hours, Logged Hours
Create project brief, <?= !empty($employees) ? $employees[0]['id'] : '1' ?>, high, completed, 2023-02-05, 4, 4.5
Design mockups, <?= !empty($employees) && count($employees) > 1 ? $employees[1]['id'] : '2' ?>, high, completed, 2023-02-10, 8, 9
Client review meeting, <?= !empty($employees) ? $employees[0]['id'] : '1' ?>, medium, completed, 2023-02-12, 2, 2"></textarea>
                        <div class="help-text">
                            Format: Title, Employee ID (optional), Priority, Status, Due Date, Estimated Hours, Logged Hours
                            <br>Tasks will be automatically linked to this project
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Complete Project
                    </button>
                </form>
            </div>
        </div>

        <!-- Sample Data Generator -->
        <div class="import-section" style="background: var(--gray-50); border: 2px dashed var(--gray-300);">
            <h2 class="section-title">
                <i class="fas fa-magic"></i>
                Generate Sample Data
            </h2>
            <p style="margin-bottom: 1rem;">Use these templates to quickly generate test data with proper employee assignments:</p>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="btn btn-secondary" onclick="generateSampleEmployees()">
                    <i class="fas fa-users"></i> Sample Employees
                </button>
                <button class="btn btn-secondary" onclick="generateSampleProjects()">
                    <i class="fas fa-project-diagram"></i> Sample Projects
                </button>
                <button class="btn btn-secondary" onclick="generateSampleTasks()">
                    <i class="fas fa-tasks"></i> Sample Tasks
                </button>
                <button class="btn btn-secondary" onclick="generateCompleteProject()">
                    <i class="fas fa-rocket"></i> Complete Project
                </button>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }

        function generateSampleEmployees() {
            const sampleData = `John Doe, john.doe@company.com, Senior Developer, employee
Sarah Wilson, sarah.wilson@company.com, Project Manager, employee
Mike Johnson, mike.j@company.com, UI/UX Designer, employee
Emily Chen, emily.chen@company.com, Marketing Manager, employee
David Kim, david.kim@freelance.com, Video Editor, freelancer
Lisa Rodriguez, lisa.r@company.com, Content Strategist, employee
James Brown, james.brown@company.com, DevOps Engineer, employee
Maria Garcia, maria.g@freelance.com, Graphic Designer, freelancer
Robert Taylor, rob.taylor@company.com, Sales Director, employee
Anna Lee, anna.lee@company.com, HR Manager, employee`;
            
            document.querySelector('textarea[name="bulk_employees"]').value = sampleData;
            showTab('bulk-employees');
        }

        function generateSampleProjects() {
            const sampleData = `Website Redesign 2023, TechCorp Inc, high, 2023-01-15, 2023-06-30, Complete overhaul of corporate website with new branding
Mobile App Development, StartupXYZ, urgent, 2023-03-01, 2023-09-30, iOS and Android app for e-commerce platform
Marketing Campaign Q2, Fashion Brand Co, medium, 2023-04-01, 2023-06-30, Social media and content marketing campaign
Video Production Series, Education Platform, high, 2023-05-15, 2023-08-31, 10-part educational video series
Brand Identity Package, New Restaurant, medium, 2023-02-01, 2023-03-15, Logo design and brand guidelines`;
            
            document.querySelector('textarea[name="bulk_projects"]').value = sampleData;
            showTab('bulk-projects');
        }

        function generateSampleTasks() {
            // Get employee IDs from the page if available
            const employees = <?= json_encode($employees) ?>;
            const projects = <?= json_encode($projects) ?>;
            
            let sampleData = '';
            if (employees.length > 0 && projects.length > 0) {
                const emp1 = employees[0].id;
                const emp2 = employees.length > 1 ? employees[1].id : emp1;
                const emp3 = employees.length > 2 ? employees[2].id : emp1;
                const proj1 = projects[0].id;
                const proj2 = projects.length > 1 ? projects[1].id : proj1;
                
                sampleData = `Database architecture design, ${emp1}, ${proj1}, high, completed, 2023-01-10, 8, 8.5, Design and document database schema
Frontend components development, ${emp2}, ${proj1}, high, completed, 2023-01-15, 24, 26, Build React component library
API endpoint development, ${emp1}, ${proj1}, urgent, completed, 2023-01-20, 16, 18, REST API with authentication
User testing sessions, ${emp3}, ${proj2}, medium, completed, 2023-02-01, 6, 5.5, Conduct user testing and gather feedback
Performance optimization, ${emp1}, ${proj1}, medium, completed, 2023-02-15, 12, 14, Optimize database queries and caching
Documentation writing, ${emp3}, ${proj2}, low, completed, 2023-02-20, 8, 7, Write user and API documentation
Deployment preparation, ${emp1}, ${proj1}, high, completed, 2023-03-01, 4, 5, Setup CI/CD pipeline
Client presentation, ${emp2}, ${proj2}, high, completed, 2023-03-05, 3, 3, Present final deliverables`;
            } else {
                sampleData = `Database architecture design, 1, 1, high, completed, 2023-01-10, 8, 8.5, Design and document database schema
Frontend components development, 2, 1, high, completed, 2023-01-15, 24, 26, Build React component library
API endpoint development, 1, 1, urgent, completed, 2023-01-20, 16, 18, REST API with authentication`;
            }
            
            document.querySelector('textarea[name="bulk_tasks"]').value = sampleData;
            showTab('bulk-tasks');
        }

        function generateCompleteProject() {
            const employees = <?= json_encode($employees) ?>;
            
            let taskData = '';
            if (employees.length > 0) {
                const emp1 = employees[0].id;
                const emp2 = employees.length > 1 ? employees[1].id : emp1;
                const emp3 = employees.length > 2 ? employees[2].id : emp1;
                
                taskData = `Initial research and planning, ${emp1}, high, completed, 2023-10-01, 8, 9
Create wireframes, ${emp2}, high, completed, 2023-10-05, 12, 11
Design high-fidelity mockups, ${emp2}, high, completed, 2023-10-10, 16, 18
Frontend development sprint 1, ${emp1}, urgent, completed, 2023-10-15, 40, 42
Backend API development, ${emp3}, urgent, completed, 2023-10-20, 32, 35
Integration testing, ${emp1}, medium, completed, 2023-10-25, 8, 10
Client review and feedback, ${emp2}, high, completed, 2023-10-28, 4, 4
Final adjustments, ${emp1}, medium, completed, 2023-10-30, 8, 6`;
            } else {
                taskData = `Initial research and planning, 1, high, completed, 2023-10-01, 8, 9
Create wireframes, 2, high, completed, 2023-10-05, 12, 11
Design high-fidelity mockups, 2, high, completed, 2023-10-10, 16, 18`;
            }
            
            document.querySelector('input[name="project_name"]').value = 'E-commerce Platform Redesign';
            document.querySelector('input[name="project_client"]').value = 'MegaStore Inc';
            document.querySelector('select[name="project_priority"]').value = 'high';
            document.querySelector('select[name="project_status"]').value = 'completed';
            document.querySelector('input[name="project_start_date"]').value = '2023-10-01';
            document.querySelector('input[name="project_end_date"]').value = '2023-10-31';
            document.querySelector('textarea[name="project_description"]').value = 'Complete redesign of the e-commerce platform including new UI/UX, improved performance, and mobile optimization.';
            document.querySelector('textarea[name="project_tasks"]').value = taskData;
            
            showTab('complete-project');
        }

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    </script>
</body>
</html>