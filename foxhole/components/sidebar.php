<?php
/**
 * Reusable Sidebar Component
 * Usage: include 'components/sidebar.php';
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'User';
?>

<aside class="sidebar" id="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-paw"></i>
        </div>
        <div class="sidebar-title">Foxhole</div>
    </div>

    <!-- Sidebar Navigation -->
    <ul class="sidebar-menu">
        <?php if ($user_role === 'admin'): ?>
            <!-- Admin Navigation -->
            <li class="sidebar-menu-item">
                <a href="dashboard.php" class="sidebar-menu-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="dashboard.php#employees" class="sidebar-menu-link">
                    <i class="fas fa-users sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Team Members</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="dashboard.php#projects" class="sidebar-menu-link">
                    <i class="fas fa-project-diagram sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Projects</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="dashboard.php#attendance" class="sidebar-menu-link">
                    <i class="fas fa-calendar-check sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Attendance</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="analytics.php" class="sidebar-menu-link <?= $current_page === 'analytics' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Analytics</span>
                </a>
            </li>

        <?php elseif ($user_role === 'project_manager'): ?>
            <!-- Project Manager Navigation -->
            <li class="sidebar-menu-item">
                <a href="project_manager.php" class="sidebar-menu-link <?= $current_page === 'project_manager' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="project_manager.php#projects" class="sidebar-menu-link">
                    <i class="fas fa-tasks sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Projects</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="project_manager.php#team" class="sidebar-menu-link">
                    <i class="fas fa-users sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Team</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="project_manager.php#tasks" class="sidebar-menu-link">
                    <i class="fas fa-clipboard-list sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Task Management</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="project_manager.php#reports" class="sidebar-menu-link">
                    <i class="fas fa-file-alt sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Reports</span>
                </a>
            </li>

        <?php elseif ($user_role === 'employee'): ?>
            <!-- Employee Navigation -->
            <li class="sidebar-menu-item">
                <a href="employee_dashboard.php" class="sidebar-menu-link <?= $current_page === 'employee_dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">My Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="employee_dashboard.php#tasks" class="sidebar-menu-link">
                    <i class="fas fa-tasks sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">My Tasks</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="employee_dashboard.php#attendance" class="sidebar-menu-link">
                    <i class="fas fa-calendar-check sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Attendance</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="employee_dashboard.php#timesheet" class="sidebar-menu-link">
                    <i class="fas fa-clock sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Timesheet</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="employee_dashboard.php#leave" class="sidebar-menu-link">
                    <i class="fas fa-calendar-times sidebar-menu-icon"></i>
                    <span class="sidebar-menu-text">Leave Requests</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Common Links -->
        <li class="sidebar-menu-item" style="margin-top: auto;">
            <a href="#" class="sidebar-menu-link" onclick="toggleDarkMode()">
                <i class="fas fa-moon sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Dark Mode</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="auth.php?action=logout" class="sidebar-menu-link">
                <i class="fas fa-sign-out-alt sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Logout</span>
            </a>
        </li>
    </ul>
</aside>

<script>
    // Sidebar toggle for mobile
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }

    // Dark mode toggle (placeholder - can be implemented later)
    function toggleDarkMode() {
        alert('Dark mode coming soon!');
    }
</script>
