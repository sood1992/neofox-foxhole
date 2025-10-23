<?php
/**
 * Foxhole Project Manager Dashboard - Modern UI
 * Complete redesign with intuitive, productivity-focused interface
 */

// Start session and check authentication
session_start();

// Check if user is logged in and has project manager or admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'project_manager' && $_SESSION['user_role'] !== 'admin')) {
    header('Location: index.php');
    exit;
}

// Check for required files
if (!file_exists('api_enhanced.php')) {
    die('<div style="background: #fee; color: #c33; padding: 20px; border-radius: 8px; margin: 20px;">
        <h3>⚠️ API File Missing</h3>
        <p>The <code>api_enhanced.php</code> file is missing. Please create it first.</p>
        </div>');
}

require_once 'config-2.php';

// Test database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    $db->query("SELECT 1");
    $dbStatus = "Connected";
} catch (Exception $e) {
    $dbStatus = "Failed: " . $e->getMessage();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Project Manager';
$user_role = $_SESSION['user_role'];

// Get current time for greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Good Morning';
    $greeting_icon = 'fa-sun';
} elseif ($hour < 18) {
    $greeting = 'Good Afternoon';
    $greeting_icon = 'fa-cloud-sun';
} else {
    $greeting = 'Good Evening';
    $greeting_icon = 'fa-moon';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Manager Dashboard - Foxhole</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        /* Project Manager Specific Styles */
        .pm-welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
        }

        .pm-welcome-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pm-welcome-text h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .pm-welcome-text p {
            font-size: 1.125rem;
            opacity: 0.9;
        }

        .pm-quick-actions {
            display: flex;
            gap: 1rem;
        }

        .pm-quick-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-fast);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pm-quick-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Stats Grid */
        .pm-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .pm-stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary);
            transition: var(--transition-fast);
        }

        .pm-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .pm-stat-card.success { border-left-color: var(--success); }
        .pm-stat-card.warning { border-left-color: var(--warning); }
        .pm-stat-card.danger { border-left-color: var(--danger); }
        .pm-stat-card.info { border-left-color: var(--info); }

        .pm-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .pm-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .pm-stat-card.primary .pm-stat-icon {
            background: var(--primary-light);
            color: var(--primary);
        }

        .pm-stat-card.success .pm-stat-icon {
            background: var(--success-light);
            color: var(--success);
        }

        .pm-stat-card.warning .pm-stat-icon {
            background: var(--warning-light);
            color: var(--warning);
        }

        .pm-stat-card.danger .pm-stat-icon {
            background: var(--danger-light);
            color: var(--danger);
        }

        .pm-stat-card.info .pm-stat-icon {
            background: var(--info-light);
            color: var(--info);
        }

        .pm-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .pm-stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        .pm-stat-change {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .pm-stat-change.up {
            background: var(--success-light);
            color: var(--success);
        }

        .pm-stat-change.down {
            background: var(--danger-light);
            color: var(--danger);
        }

        /* Tabs */
        .pm-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: var(--spacing-xl);
            border-bottom: 2px solid var(--gray-200);
            overflow-x: auto;
        }

        .pm-tab {
            background: none;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--gray-600);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: var(--transition-fast);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pm-tab:hover {
            color: var(--primary);
            background: var(--gray-50);
        }

        .pm-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: var(--primary-light);
        }

        .pm-tab-content {
            display: none;
        }

        .pm-tab-content.active {
            display: block;
        }

        /* Section Cards */
        .pm-section-grid {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

        .pm-section-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .pm-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .pm-section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .pm-section-title i {
            color: var(--primary);
        }

        /* Form Styles */
        .pm-form-group {
            margin-bottom: 1.25rem;
        }

        .pm-form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .pm-form-input,
        .pm-form-select,
        .pm-form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            transition: var(--transition-fast);
            font-family: inherit;
        }

        .pm-form-input:focus,
        .pm-form-select:focus,
        .pm-form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .pm-form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Data List */
        .pm-data-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .pm-data-item {
            padding: 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            margin-bottom: 0.75rem;
            transition: var(--transition-fast);
            cursor: pointer;
        }

        .pm-data-item:hover {
            border-color: var(--primary);
            background: var(--gray-50);
        }

        .pm-data-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .pm-data-item-title {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 1rem;
        }

        .pm-data-item-meta {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .pm-data-item-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .pm-data-item-actions button {
            flex: 1;
        }

        /* Empty State */
        .pm-empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-500);
        }

        .pm-empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .pm-empty-state p {
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }

        /* Status Badges */
        .pm-status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .pm-status-badge.active {
            background: var(--success-light);
            color: var(--success);
        }

        .pm-status-badge.busy {
            background: var(--danger-light);
            color: var(--danger);
        }

        .pm-status-badge.away {
            background: var(--warning-light);
            color: var(--warning);
        }

        .pm-status-badge.planning {
            background: var(--info-light);
            color: var(--info);
        }

        .pm-status-badge.in-progress {
            background: var(--primary-light);
            color: var(--primary);
        }

        .pm-status-badge.completed {
            background: var(--success-light);
            color: var(--success);
        }

        .pm-status-badge.on-hold {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        /* Priority Badges */
        .pm-priority-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .pm-priority-badge.urgent {
            background: #7c2d12;
            color: white;
        }

        .pm-priority-badge.high {
            background: var(--danger);
            color: white;
        }

        .pm-priority-badge.medium {
            background: var(--warning);
            color: white;
        }

        .pm-priority-badge.low {
            background: var(--success);
            color: white;
        }

        /* Bulk Actions */
        .pm-bulk-actions {
            background: var(--gray-100);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .pm-bulk-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .pm-bulk-select {
            flex: 1;
            max-width: 200px;
        }

        /* Analytics Charts */
        .pm-chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: var(--spacing-lg);
        }

        .pm-chart-header {
            margin-bottom: 1.5rem;
        }

        .pm-chart-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .pm-progress-bar {
            height: 8px;
            background: var(--gray-200);
            border-radius: var(--radius-full);
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .pm-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            transition: width 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .pm-section-grid {
                grid-template-columns: 1fr;
            }

            .pm-quick-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .pm-stats-grid {
                grid-template-columns: 1fr;
            }

            .pm-welcome-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <main class="main-content">
            <!-- Welcome Banner -->
            <div class="pm-welcome-banner">
                <div class="pm-welcome-content">
                    <div class="pm-welcome-text">
                        <h1><i class="fas <?= $greeting_icon ?>"></i> <?= $greeting ?>, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>!</h1>
                        <p>Manage your projects, coordinate your team, and drive results</p>
                    </div>
                    <div class="pm-quick-actions">
                        <button class="pm-quick-btn" onclick="showTab('project-management')">
                            <i class="fas fa-plus"></i> New Project
                        </button>
                        <button class="pm-quick-btn" onclick="showTab('task-management')">
                            <i class="fas fa-tasks"></i> New Task
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="pm-stats-grid">
                <div class="pm-stat-card primary">
                    <div class="pm-stat-header">
                        <div>
                            <div class="pm-stat-value" id="stat-team-count">0</div>
                            <div class="pm-stat-label">Team Members</div>
                        </div>
                        <div class="pm-stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <span class="pm-stat-change up" id="stat-team-active">0 active</span>
                </div>

                <div class="pm-stat-card info">
                    <div class="pm-stat-header">
                        <div>
                            <div class="pm-stat-value" id="stat-projects-count">0</div>
                            <div class="pm-stat-label">Active Projects</div>
                        </div>
                        <div class="pm-stat-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                    </div>
                    <span class="pm-stat-change up" id="stat-projects-progress">0 in progress</span>
                </div>

                <div class="pm-stat-card success">
                    <div class="pm-stat-header">
                        <div>
                            <div class="pm-stat-value" id="stat-tasks-count">0</div>
                            <div class="pm-stat-label">Total Tasks</div>
                        </div>
                        <div class="pm-stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <span class="pm-stat-change up" id="stat-tasks-completed">0% complete</span>
                </div>

                <div class="pm-stat-card warning">
                    <div class="pm-stat-header">
                        <div>
                            <div class="pm-stat-value" id="stat-efficiency">0%</div>
                            <div class="pm-stat-label">Team Efficiency</div>
                        </div>
                        <div class="pm-stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <span class="pm-stat-change up" id="stat-efficiency-trend">On target</span>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="pm-tabs">
                <button class="pm-tab active" onclick="showTab('team-management')">
                    <i class="fas fa-users"></i> Team Management
                </button>
                <button class="pm-tab" onclick="showTab('project-management')">
                    <i class="fas fa-project-diagram"></i> Projects
                </button>
                <button class="pm-tab" onclick="showTab('task-management')">
                    <i class="fas fa-tasks"></i> Tasks
                </button>
                <button class="pm-tab" onclick="showTab('analytics')">
                    <i class="fas fa-chart-bar"></i> Analytics
                </button>
            </div>

            <!-- Team Management Tab -->
            <div id="team-management" class="pm-tab-content active">
                <div class="pm-section-grid">
                    <!-- Add Team Member Form -->
                    <div class="pm-section-card">
                        <div class="pm-section-header">
                            <h2 class="pm-section-title">
                                <i class="fas fa-user-plus"></i> Add Team Member
                            </h2>
                        </div>

                        <form id="add-employee-form" onsubmit="handleAddEmployee(event)">
                            <div class="pm-form-group">
                                <label class="pm-form-label">Full Name *</label>
                                <input type="text" name="name" class="pm-form-input" required
                                       placeholder="John Doe">
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Email Address *</label>
                                <input type="email" name="email" class="pm-form-input" required
                                       placeholder="john@neofoxmedia.com">
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Role/Position *</label>
                                <input type="text" name="role" class="pm-form-input" required
                                       placeholder="e.g., Video Editor, Designer">
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Employment Type *</label>
                                <select name="type" class="pm-form-select" required>
                                    <option value="employee">Full-time Employee</option>
                                    <option value="freelancer">Freelancer/Contractor</option>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Status</label>
                                <select name="status" class="pm-form-select">
                                    <option value="active">Active</option>
                                    <option value="busy">Busy</option>
                                    <option value="away">Away</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Add Team Member
                            </button>
                        </form>
                    </div>

                    <!-- Team List -->
                    <div class="pm-section-card">
                        <div class="pm-section-header">
                            <h2 class="pm-section-title">
                                <i class="fas fa-users"></i> Team Overview
                            </h2>
                            <button class="btn btn-secondary" onclick="exportTeamData()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>

                        <!-- Bulk Actions -->
                        <div class="pm-bulk-actions">
                            <span class="pm-bulk-label">Bulk Actions:</span>
                            <select id="bulk-status" class="pm-form-select pm-bulk-select">
                                <option value="active">Set Active</option>
                                <option value="busy">Set Busy</option>
                                <option value="away">Set Away</option>
                                <option value="offline">Set Offline</option>
                            </select>
                            <button class="btn btn-warning btn-sm" onclick="bulkUpdateStatus()">
                                <i class="fas fa-edit"></i> Update Selected
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="selectAllEmployees()">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                        </div>

                        <div class="pm-data-list" id="employee-list">
                            <div class="pm-empty-state">
                                <i class="fas fa-users"></i>
                                <p>No team members yet</p>
                                <small>Add your first team member to get started</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Management Tab -->
            <div id="project-management" class="pm-tab-content">
                <div class="pm-section-grid">
                    <!-- Create Project Form -->
                    <div class="pm-section-card">
                        <div class="pm-section-header">
                            <h2 class="pm-section-title">
                                <i class="fas fa-folder-plus"></i> Create Project
                            </h2>
                        </div>

                        <form id="add-project-form" onsubmit="handleAddProject(event)">
                            <div class="pm-form-group">
                                <label class="pm-form-label">Project Name *</label>
                                <input type="text" name="name" class="pm-form-input" required
                                       placeholder="Website Redesign">
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Description</label>
                                <textarea name="description" class="pm-form-textarea"
                                          placeholder="Project description and objectives..."></textarea>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Assigned Team *</label>
                                <select name="assigned_to" class="pm-form-select" required id="project-team-select">
                                    <option value="">Select team member...</option>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Priority</label>
                                <select name="priority" class="pm-form-select">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Status</label>
                                <select name="status" class="pm-form-select">
                                    <option value="planning">Planning</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="on-hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Deadline</label>
                                <input type="date" name="deadline" class="pm-form-input">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Create Project
                            </button>
                        </form>
                    </div>

                    <!-- Project List -->
                    <div class="pm-section-card">
                        <div class="pm-section-header">
                            <h2 class="pm-section-title">
                                <i class="fas fa-project-diagram"></i> Active Projects
                            </h2>
                            <button class="btn btn-secondary" onclick="exportProjectData()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>

                        <div class="pm-data-list" id="project-list">
                            <div class="pm-empty-state">
                                <i class="fas fa-project-diagram"></i>
                                <p>No projects yet</p>
                                <small>Create your first project to get started</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Management Tab -->
            <div id="task-management" class="pm-tab-content">
                <div class="pm-section-grid">
                    <!-- Create Task Form -->
                    <div class="pm-section-card">
                        <div class="pm-section-header">
                            <h2 class="pm-section-title">
                                <i class="fas fa-plus-square"></i> Create Task
                            </h2>
                        </div>

                        <form id="add-task-form" onsubmit="handleAddTask(event)">
                            <div class="pm-form-group">
                                <label class="pm-form-label">Task Title *</label>
                                <input type="text" name="title" class="pm-form-input" required
                                       placeholder="Design homepage mockup">
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Description</label>
                                <textarea name="description" class="pm-form-textarea"
                                          placeholder="Task details and requirements..."></textarea>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Assigned To *</label>
                                <select name="assigned_to" class="pm-form-select" required id="task-employee-select">
                                    <option value="">Select team member...</option>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Project</label>
                                <select name="project_id" class="pm-form-select" id="task-project-select">
                                    <option value="">Select project...</option>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Priority</label>
                                <select name="priority" class="pm-form-select">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Status</label>
                                <select name="status" class="pm-form-select">
                                    <option value="pending">Pending</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <div class="pm-form-group">
                                <label class="pm-form-label">Due Date</label>
                                <input type="date" name="due_date" class="pm-form-input">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Create Task
                            </button>
                        </form>
                    </div>

                    <!-- Task List -->
                    <div class="pm-section-card">
                        <div class="pm-section-header">
                            <h2 class="pm-section-title">
                                <i class="fas fa-tasks"></i> All Tasks
                            </h2>
                            <button class="btn btn-secondary" onclick="exportTaskData()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>

                        <div class="pm-data-list" id="task-list">
                            <div class="pm-empty-state">
                                <i class="fas fa-tasks"></i>
                                <p>No tasks yet</p>
                                <small>Create your first task to get started</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div id="analytics" class="pm-tab-content">
                <div class="pm-chart-container">
                    <div class="pm-chart-header">
                        <h3 class="pm-chart-title">Team Performance Overview</h3>
                    </div>
                    <div id="analytics-content">
                        <div class="pm-empty-state">
                            <i class="fas fa-chart-bar"></i>
                            <p>No analytics data available</p>
                            <small>Add projects and tasks to see analytics</small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Global Variables
        let employees = [];
        let projects = [];
        let tasks = [];
        let currentTab = 'team-management';

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Project Manager Dashboard initialized');
            loadEmployees();
            updateStats();
        });

        // Tab Management
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.pm-tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.pm-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }

            // Add active class to clicked tab
            event.target.closest('.pm-tab').classList.add('active');

            currentTab = tabId;
            loadTabData(tabId);
        }

        function loadTabData(tabId) {
            switch (tabId) {
                case 'team-management':
                    loadEmployees();
                    break;
                case 'project-management':
                    loadProjects();
                    loadEmployeesForDropdown();
                    break;
                case 'task-management':
                    loadTasks();
                    loadEmployeesForDropdown();
                    loadProjectsForDropdown();
                    break;
                case 'analytics':
                    loadAnalytics();
                    break;
            }
        }

        // Load Employees
        async function loadEmployees() {
            try {
                const response = await fetch(`api_enhanced.php?action=employees&_t=${Date.now()}`);
                if (!response.ok) throw new Error('Failed to load employees');

                const data = await response.json();
                if (Array.isArray(data)) {
                    employees = data;
                    updateEmployeeList();
                    updateStats();
                }
            } catch (error) {
                console.error('Error loading employees:', error);
                employees = [];
                updateEmployeeList();
            }
        }

        function updateEmployeeList() {
            const listContainer = document.getElementById('employee-list');

            if (employees.length === 0) {
                listContainer.innerHTML = `
                    <div class="pm-empty-state">
                        <i class="fas fa-users"></i>
                        <p>No team members yet</p>
                        <small>Add your first team member to get started</small>
                    </div>
                `;
                return;
            }

            listContainer.innerHTML = employees.map(emp => `
                <div class="pm-data-item" data-id="${emp.id}">
                    <div class="pm-data-item-header">
                        <div>
                            <div class="pm-data-item-title">${emp.name}</div>
                            <div class="pm-data-item-meta">${emp.role || 'No role'} • ${emp.email}</div>
                        </div>
                        <span class="pm-status-badge ${emp.status || 'active'}">${emp.status || 'Active'}</span>
                    </div>
                    <div class="pm-data-item-meta">
                        Type: ${emp.type || 'employee'} • Added: ${emp.created_at ? new Date(emp.created_at).toLocaleDateString() : 'N/A'}
                    </div>
                    <div class="pm-data-item-actions">
                        <button class="btn btn-sm btn-primary" onclick="editEmployee(${emp.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteEmployee(${emp.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Load Projects
        async function loadProjects() {
            try {
                const response = await fetch(`api_enhanced.php?action=projects&_t=${Date.now()}`);
                if (!response.ok) throw new Error('Failed to load projects');

                const data = await response.json();
                if (Array.isArray(data)) {
                    projects = data;
                    updateProjectList();
                    updateStats();
                }
            } catch (error) {
                console.error('Error loading projects:', error);
                projects = [];
                updateProjectList();
            }
        }

        function updateProjectList() {
            const listContainer = document.getElementById('project-list');

            if (projects.length === 0) {
                listContainer.innerHTML = `
                    <div class="pm-empty-state">
                        <i class="fas fa-project-diagram"></i>
                        <p>No projects yet</p>
                        <small>Create your first project to get started</small>
                    </div>
                `;
                return;
            }

            listContainer.innerHTML = projects.map(proj => `
                <div class="pm-data-item" data-id="${proj.id}">
                    <div class="pm-data-item-header">
                        <div>
                            <div class="pm-data-item-title">${proj.name}</div>
                            <div class="pm-data-item-meta">${proj.description || 'No description'}</div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span class="pm-status-badge ${proj.status || 'planning'}">${proj.status || 'Planning'}</span>
                            <span class="pm-priority-badge ${proj.priority || 'medium'}">${proj.priority || 'Medium'}</span>
                        </div>
                    </div>
                    <div class="pm-data-item-meta">
                        Assigned to: ${proj.assigned_to || 'Unassigned'} • Deadline: ${proj.deadline || 'Not set'}
                    </div>
                    ${proj.progress ? `
                        <div class="pm-progress-bar">
                            <div class="pm-progress-fill" style="width: ${proj.progress}%"></div>
                        </div>
                    ` : ''}
                    <div class="pm-data-item-actions">
                        <button class="btn btn-sm btn-primary" onclick="editProject(${proj.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProject(${proj.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Load Tasks
        async function loadTasks() {
            try {
                const response = await fetch(`api_enhanced.php?action=tasks&_t=${Date.now()}`);
                if (!response.ok) throw new Error('Failed to load tasks');

                const data = await response.json();
                if (Array.isArray(data)) {
                    tasks = data;
                    updateTaskList();
                    updateStats();
                }
            } catch (error) {
                console.error('Error loading tasks:', error);
                tasks = [];
                updateTaskList();
            }
        }

        function updateTaskList() {
            const listContainer = document.getElementById('task-list');

            if (tasks.length === 0) {
                listContainer.innerHTML = `
                    <div class="pm-empty-state">
                        <i class="fas fa-tasks"></i>
                        <p>No tasks yet</p>
                        <small>Create your first task to get started</small>
                    </div>
                `;
                return;
            }

            listContainer.innerHTML = tasks.map(task => `
                <div class="pm-data-item" data-id="${task.id}">
                    <div class="pm-data-item-header">
                        <div>
                            <div class="pm-data-item-title">${task.title}</div>
                            <div class="pm-data-item-meta">${task.description || 'No description'}</div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span class="pm-status-badge ${task.status || 'pending'}">${task.status || 'Pending'}</span>
                            <span class="pm-priority-badge ${task.priority || 'medium'}">${task.priority || 'Medium'}</span>
                        </div>
                    </div>
                    <div class="pm-data-item-meta">
                        Assigned to: ${task.assigned_to || 'Unassigned'} • Due: ${task.due_date || 'Not set'}${task.project_name ? ` • Project: ${task.project_name}` : ''}
                    </div>
                    <div class="pm-data-item-actions">
                        <button class="btn btn-sm btn-primary" onclick="editTask(${task.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTask(${task.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Load Analytics
        async function loadAnalytics() {
            const container = document.getElementById('analytics-content');

            if (projects.length === 0 && tasks.length === 0) {
                container.innerHTML = `
                    <div class="pm-empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <p>No analytics data available</p>
                        <small>Add projects and tasks to see analytics</small>
                    </div>
                `;
                return;
            }

            // Calculate analytics
            const totalProjects = projects.length;
            const activeProjects = projects.filter(p => p.status === 'in-progress').length;
            const completedProjects = projects.filter(p => p.status === 'completed').length;

            const totalTasks = tasks.length;
            const completedTasks = tasks.filter(t => t.status === 'completed').length;
            const inProgressTasks = tasks.filter(t => t.status === 'in-progress').length;

            const completionRate = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

            container.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <h4 style="color: var(--gray-700); margin-bottom: 1rem;">Project Status</h4>
                        <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-md);">
                            <p>Total: ${totalProjects}</p>
                            <p>Active: ${activeProjects}</p>
                            <p>Completed: ${completedProjects}</p>
                        </div>
                    </div>

                    <div>
                        <h4 style="color: var(--gray-700); margin-bottom: 1rem;">Task Overview</h4>
                        <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-md);">
                            <p>Total: ${totalTasks}</p>
                            <p>Completed: ${completedTasks}</p>
                            <p>In Progress: ${inProgressTasks}</p>
                        </div>
                    </div>

                    <div>
                        <h4 style="color: var(--gray-700); margin-bottom: 1rem;">Completion Rate</h4>
                        <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-md);">
                            <div style="font-size: 3rem; font-weight: 800; color: var(--primary);">${completionRate}%</div>
                            <div class="pm-progress-bar" style="margin-top: 1rem;">
                                <div class="pm-progress-fill" style="width: ${completionRate}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Update Stats
        function updateStats() {
            const activeEmployees = employees.filter(e => e.status === 'active').length;
            const activeProjects = projects.filter(p => p.status === 'in-progress').length;
            const completedTasks = tasks.filter(t => t.status === 'completed').length;
            const totalTasks = tasks.length;
            const completionRate = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

            document.getElementById('stat-team-count').textContent = employees.length;
            document.getElementById('stat-team-active').textContent = `${activeEmployees} active`;

            document.getElementById('stat-projects-count').textContent = projects.length;
            document.getElementById('stat-projects-progress').textContent = `${activeProjects} in progress`;

            document.getElementById('stat-tasks-count').textContent = totalTasks;
            document.getElementById('stat-tasks-completed').textContent = `${completionRate}% complete`;

            document.getElementById('stat-efficiency').textContent = `${completionRate}%`;
            document.getElementById('stat-efficiency-trend').textContent = completionRate >= 75 ? 'Excellent' : completionRate >= 50 ? 'Good' : 'Needs attention';
        }

        // Load Dropdowns
        function loadEmployeesForDropdown() {
            const selects = [
                document.getElementById('project-team-select'),
                document.getElementById('task-employee-select')
            ];

            selects.forEach(select => {
                if (select) {
                    select.innerHTML = '<option value="">Select team member...</option>' +
                        employees.map(emp => `<option value="${emp.name}">${emp.name}</option>`).join('');
                }
            });
        }

        function loadProjectsForDropdown() {
            const select = document.getElementById('task-project-select');
            if (select) {
                select.innerHTML = '<option value="">Select project...</option>' +
                    projects.map(proj => `<option value="${proj.id}">${proj.name}</option>`).join('');
            }
        }

        // Form Handlers
        async function handleAddEmployee(event) {
            event.preventDefault();
            const formData = new FormData(event.target);

            try {
                const response = await fetch('api_enhanced.php?action=add_employee', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showMessage('Team member added successfully!', 'success');
                    event.target.reset();
                    loadEmployees();
                } else {
                    showMessage('Error: ' + (result.error || 'Failed to add team member'), 'error');
                }
            } catch (error) {
                showMessage('Error adding team member', 'error');
                console.error(error);
            }
        }

        async function handleAddProject(event) {
            event.preventDefault();
            const formData = new FormData(event.target);

            try {
                const response = await fetch('api_enhanced.php?action=add_project', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showMessage('Project created successfully!', 'success');
                    event.target.reset();
                    loadProjects();
                } else {
                    showMessage('Error: ' + (result.error || 'Failed to create project'), 'error');
                }
            } catch (error) {
                showMessage('Error creating project', 'error');
                console.error(error);
            }
        }

        async function handleAddTask(event) {
            event.preventDefault();
            const formData = new FormData(event.target);

            try {
                const response = await fetch('api_enhanced.php?action=add_task', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showMessage('Task created successfully!', 'success');
                    event.target.reset();
                    loadTasks();
                } else {
                    showMessage('Error: ' + (result.error || 'Failed to create task'), 'error');
                }
            } catch (error) {
                showMessage('Error creating task', 'error');
                console.error(error);
            }
        }

        // Action Functions
        function editEmployee(id) {
            showMessage('Edit functionality coming soon!', 'info');
        }

        async function deleteEmployee(id) {
            if (!confirm('Are you sure you want to delete this team member?')) return;

            try {
                const response = await fetch(`api_enhanced.php?action=delete_employee&id=${id}`, {
                    method: 'POST'
                });

                const result = await response.json();
                if (result.success) {
                    showMessage('Team member deleted successfully!', 'success');
                    loadEmployees();
                } else {
                    showMessage('Error deleting team member', 'error');
                }
            } catch (error) {
                showMessage('Error deleting team member', 'error');
                console.error(error);
            }
        }

        function editProject(id) {
            showMessage('Edit functionality coming soon!', 'info');
        }

        async function deleteProject(id) {
            if (!confirm('Are you sure you want to delete this project?')) return;

            try {
                const response = await fetch(`api_enhanced.php?action=delete_project&id=${id}`, {
                    method: 'POST'
                });

                const result = await response.json();
                if (result.success) {
                    showMessage('Project deleted successfully!', 'success');
                    loadProjects();
                } else {
                    showMessage('Error deleting project', 'error');
                }
            } catch (error) {
                showMessage('Error deleting project', 'error');
                console.error(error);
            }
        }

        function editTask(id) {
            showMessage('Edit functionality coming soon!', 'info');
        }

        async function deleteTask(id) {
            if (!confirm('Are you sure you want to delete this task?')) return;

            try {
                const response = await fetch(`api_enhanced.php?action=delete_task&id=${id}`, {
                    method: 'POST'
                });

                const result = await response.json();
                if (result.success) {
                    showMessage('Task deleted successfully!', 'success');
                    loadTasks();
                } else {
                    showMessage('Error deleting task', 'error');
                }
            } catch (error) {
                showMessage('Error deleting task', 'error');
                console.error(error);
            }
        }

        // Bulk Actions
        function selectAllEmployees() {
            showMessage('Bulk selection coming soon!', 'info');
        }

        function bulkUpdateStatus() {
            showMessage('Bulk update coming soon!', 'info');
        }

        // Export Functions
        function exportTeamData() {
            showMessage('Export functionality coming soon!', 'info');
        }

        function exportProjectData() {
            showMessage('Export functionality coming soon!', 'info');
        }

        function exportTaskData() {
            showMessage('Export functionality coming soon!', 'info');
        }

        // Message Display
        function showMessage(message, type = 'info') {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--info)'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-md);
                box-shadow: var(--shadow-lg);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>

    <style>
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    </style>
</body>
</html>
