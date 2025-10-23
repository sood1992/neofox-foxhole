<?php
// dashboard.php - Fox Admin Panel (CEO Dashboard) - Complete Fixed Version

// Fix session path first
$sessionPath = dirname(__FILE__) . '/temp_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple authentication check (without including auth.php)
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'config-2.php';
require_once 'api.php';

// Initialize API
$api = new DashboardAPI();

// Get dashboard data
$stats = $api->getDashboardStats();
$employees = $api->getEmployees();
$projects = $api->getProjects();
$recentActivity = $api->getRecentActivity(10);
$insights = $api->getProductivityInsights();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_employee_status':
            $employeeId = $_POST['employee_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            $result = $api->updateEmployeeStatus($employeeId, $status);
            echo json_encode(['success' => $result]);
            break;
            
        case 'refresh_data':
            echo json_encode([
                'success' => true,
                'stats' => $api->getDashboardStats(),
                'employees' => $api->getEmployees()
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole - Fox Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* ADHD-Optimized Color System */
            --primary: #4F46E5;
            --primary-light: #6366F1;
            --secondary: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --success: #10B981;
            --info: #3B82F6;
            
            /* Status Colors (High Contrast) */
            --status-active: #16A34A;
            --status-busy: #DC2626;
            --status-away: #EA580C;
            --status-offline: #6B7280;
            
            /* Capacity Colors */
            --capacity-available: #22C55E;
            --capacity-light: #84CC16;
            --capacity-moderate: #EAB308;
            --capacity-heavy: #F97316;
            --capacity-overloaded: #EF4444;
            
            /* Neutrals */
            --dark: #0F172A;
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
            
            /* Backgrounds */
            --bg-primary: #FAFAFB;
            --bg-secondary: #FFFFFF;
            --bg-tertiary: #F8FAFC;
            
            /* ADHD-Specific Design */
            --focus-ring: 0 0 0 3px rgba(79, 70, 229, 0.3);
            --shadow-focus: 0 0 0 3px rgba(79, 70, 229, 0.1);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            
            /* Transitions */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--gray-900);
            line-height: 1.5;
            font-size: 14px;
            overflow-x: hidden;
        }

        /* ==================== LAYOUT ==================== */

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            border-right: 2px solid var(--gray-200);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            background: var(--bg-primary);
            min-height: 100vh;
        }

        /* ==================== SIDEBAR ==================== */

        .sidebar-header {
            padding: 0 2rem 2rem 2rem;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 28px;
            font-weight: 900;
            color: var(--primary);
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: var(--shadow-md);
        }

        .nav-menu {
            list-style: none;
            padding: 0 1rem;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            color: var(--gray-600);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: var(--transition);
            font-weight: 600;
            font-size: 15px;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            transform: translateX(8px);
            box-shadow: var(--shadow-md);
        }

        .nav-link i {
            width: 24px;
            text-align: center;
            font-size: 18px;
        }

        .nav-badge {
            background: var(--danger);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            margin-left: auto;
        }

        /* ==================== TOP BAR ==================== */

        .top-bar {
            background: var(--bg-secondary);
            border-bottom: 2px solid var(--gray-200);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .page-header {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.2;
        }

        .page-subtitle {
            font-size: 16px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            border: 2px solid var(--gray-200);
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }

        /* ==================== BUTTONS ==================== */

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 14px;
            border: 2px solid transparent;
        }

        .btn:focus {
            outline: none;
            box-shadow: var(--focus-ring);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        /* ==================== CONTENT AREA ==================== */

        .content-area {
            padding: 2rem;
            max-width: 100%;
        }

        /* ==================== METRICS GRID ==================== */

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--gray-200);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .metric-icon {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            box-shadow: var(--shadow-md);
        }

        .metric-number {
            font-size: 48px;
            font-weight: 900;
            color: var(--gray-900);
            margin-bottom: 8px;
            line-height: 1;
        }

        .metric-label {
            font-size: 18px;
            color: var(--gray-600);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .metric-change {
            font-size: 14px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .metric-change.positive { 
            color: var(--success); 
            background: rgba(16, 185, 129, 0.1);
        }

        .metric-change.negative { 
            color: var(--danger); 
            background: rgba(239, 68, 68, 0.1);
        }

        /* ==================== MAIN GRID ==================== */

        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--gray-200);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .section-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ==================== EMPLOYEE CARDS ==================== */

        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .employee-card {
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border: 3px solid transparent;
            transition: var(--transition);
            position: relative;
            cursor: pointer;
        }

        .employee-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .employee-card.active {
            border-color: var(--status-active);
            background: rgba(22, 163, 74, 0.05);
        }

        .employee-card.busy {
            border-color: var(--status-busy);
            background: rgba(220, 38, 38, 0.05);
        }

        .employee-card.away {
            border-color: var(--status-away);
            background: rgba(234, 88, 12, 0.05);
        }

        .employee-card.offline {
            border-color: var(--status-offline);
            background: var(--gray-100);
            opacity: 0.7;
        }

        .employee-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 1rem;
        }

        .employee-avatar {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 20px;
            position: relative;
            box-shadow: var(--shadow-md);
        }

        .status-dot-mini {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 3px solid white;
            background: var(--status-offline);
        }

        .employee-card.active .status-dot-mini {
            background: var(--status-active);
            animation: pulse-green 2s infinite;
        }

        .employee-card.busy .status-dot-mini {
            background: var(--status-busy);
            animation: pulse-red 2s infinite;
        }

        .employee-card.away .status-dot-mini {
            background: var(--status-away);
        }

        @keyframes pulse-green {
            0%, 100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
            50% { box-shadow: 0 0 0 8px rgba(22, 163, 74, 0); }
        }

        @keyframes pulse-red {
            0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
            50% { box-shadow: 0 0 0 8px rgba(220, 38, 38, 0); }
        }

        .employee-info {
            flex: 1;
        }

        .employee-name {
            font-weight: 800;
            font-size: 18px;
            margin-bottom: 4px;
            color: var(--gray-900);
        }

        .employee-role {
            font-size: 14px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .employee-metrics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 1rem;
        }

        .metric-mini {
            text-align: center;
            padding: 12px 8px;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
        }

        .metric-mini-value {
            font-weight: 800;
            font-size: 16px;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .metric-mini-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
            font-weight: 600;
        }

        .capacity-indicator {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .capacity-available { background: var(--capacity-available); color: white; }
        .capacity-light { background: var(--capacity-light); color: white; }
        .capacity-moderate { background: var(--capacity-moderate); color: white; }
        .capacity-heavy { background: var(--capacity-heavy); color: white; }
        .capacity-overloaded { background: var(--capacity-overloaded); color: white; }

        /* ==================== ANALYTICS WIDGETS ==================== */

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-50);
            border-radius: var(--radius-md);
            color: var(--gray-500);
            font-weight: 600;
        }

        /* ==================== ACTIVITY FEED ==================== */

        .activity-feed {
            max-height: 500px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-200);
            transition: var(--transition);
        }

        .activity-item:hover {
            background: var(--gray-50);
            border-radius: var(--radius-sm);
            padding-left: 12px;
            padding-right: 12px;
        }

        .activity-icon {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--success);
            margin-right: 16px;
            animation: pulse 3s infinite;
        }

        .activity-text {
            font-size: 14px;
            color: var(--gray-700);
            flex: 1;
            font-weight: 500;
        }

        .activity-time {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 600;
        }

        /* ==================== TABS ==================== */

        .tabs {
            display: flex;
            border-bottom: 3px solid var(--gray-200);
            margin-bottom: 2rem;
            gap: 4px;
        }

        .tab {
            padding: 16px 24px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 700;
            color: var(--gray-600);
            border-bottom: 4px solid transparent;
            transition: var(--transition);
            font-size: 16px;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
        }

        .tab:hover {
            background: var(--gray-50);
            color: var(--gray-800);
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: var(--bg-secondary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ==================== RESPONSIVE DESIGN ==================== */

        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: var(--transition);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .employee-grid {
                grid-template-columns: 1fr;
            }
            
            .top-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .content-area {
                padding: 1rem;
            }
        }

        /* ==================== ACCESSIBILITY ==================== */

        :focus {
            outline: none;
            box-shadow: var(--focus-ring);
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* ==================== CUSTOM SCROLLBAR ==================== */

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* ==================== LOADING STATES ==================== */

        .loading {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-500);
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid var(--gray-300);
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                    <span>Foxhole</span>
                </div>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active" onclick="showTab('overview')">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showTab('team')">
                            <i class="fas fa-users"></i>
                            <span>Team</span>
                            <?php if ($stats['overdue_tasks'] > 0): ?>
                            <span class="nav-badge"><?= $stats['overdue_tasks'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showTab('productivity')">
                            <i class="fas fa-chart-line"></i>
                            <span>Productivity</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showTab('capacity')">
                            <i class="fas fa-battery-three-quarters"></i>
                            <span>Capacity</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showTab('projects')">
                            <i class="fas fa-project-diagram"></i>
                            <span>Projects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showTab('reports')">
                            <i class="fas fa-chart-pie"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="project_manager.php" class="nav-link">
                            <i class="fas fa-cogs"></i>
                            <span>Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="analytics.php" class="nav-link">
                            <i class="fas fa-brain"></i>
                            <span>AI Analytics</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div style="padding: 2rem; margin-top: auto;">
                <button class="btn btn-danger" style="width: 100%;" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="page-header">
                    <h1 class="page-title">Fox Admin Panel</h1>
                    <p class="page-subtitle">Real-time team analytics & productivity insights</p>
                </div>
                <div class="top-actions">
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span>Live Updates</span>
                    </div>
                    <button class="btn btn-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                    <button class="btn btn-primary" onclick="generateReport()">
                        <i class="fas fa-download"></i>
                        Export Report
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <!-- Key Metrics -->
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header">
                                <div class="metric-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="metric-number"><?= $stats['active_employees'] ?></div>
                            <div class="metric-label">Active Today</div>
                            <div class="metric-change <?= $stats['active_employees'] > 0 ? 'positive' : 'negative' ?>">
                                <i class="fas fa-<?= $stats['active_employees'] > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= $stats['active_employees'] ?> of <?= $stats['total_employees'] ?> online
                            </div>
                        </div>
                        
                        <div class="metric-card">
                            <div class="metric-header">
                                <div class="metric-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="metric-number"><?= $stats['completed_tasks'] ?></div>
                            <div class="metric-label">Tasks Completed</div>
                            <div class="metric-change positive">
                                <i class="fas fa-arrow-up"></i>
                                +15% from last week
                            </div>
                        </div>
                        
                        <div class="metric-card">
                            <div class="metric-header">
                                <div class="metric-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="metric-number"><?= $stats['urgent_tasks'] + $stats['overdue_tasks'] ?></div>
                            <div class="metric-label">Urgent Items</div>
                            <div class="metric-change negative">
                                <i class="fas fa-exclamation"></i>
                                <?= $stats['urgent_tasks'] ?> urgent, <?= $stats['overdue_tasks'] ?> overdue
                            </div>
                        </div>
                        
                        <div class="metric-card">
                            <div class="metric-header">
                                <div class="metric-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                            </div>
                            <div class="metric-number"><?= round($stats['avg_productivity']) ?>%</div>
                            <div class="metric-label">Team Efficiency</div>
                            <div class="metric-change <?= $stats['avg_productivity'] >= 80 ? 'positive' : 'negative' ?>">
                                <i class="fas fa-<?= $stats['avg_productivity'] >= 80 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= $stats['avg_productivity'] >= 80 ? 'Above' : 'Below' ?> target
                            </div>
                        </div>
                    </div>

                    <!-- Main Grid -->
                    <div class="main-grid">
                        <!-- Team Overview -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-users"></i>
                                    Team Status
                                </h2>
                                <button class="btn btn-secondary" onclick="showTab('team')">
                                    View All
                                </button>
                            </div>
                            
                            <div class="employee-grid">
                                <?php foreach (array_slice($employees, 0, 6) as $emp): ?>
                                <div class="employee-card <?= $emp['status'] ?>" onclick="showEmployeeDetails(<?= $emp['id'] ?>)">
                                    <?php 
                                    $activeTasks = $emp['task_count'] - $emp['completed_tasks'];
                                    $capacity = $activeTasks == 0 ? 'available' : 
                                               ($activeTasks <= 3 ? 'light' : 
                                               ($activeTasks <= 7 ? 'moderate' : 
                                               ($activeTasks <= 12 ? 'heavy' : 'overloaded')));
                                    ?>
                                    <div class="capacity-indicator capacity-<?= $capacity ?>"><?= ucfirst($capacity) ?></div>
                                    <div class="employee-header">
                                        <div class="employee-avatar">
                                            <?= substr($emp['name'], 0, 2) ?>
                                            <div class="status-dot-mini"></div>
                                        </div>
                                        <div class="employee-info">
                                            <div class="employee-name"><?= htmlspecialchars($emp['name']) ?></div>
                                            <div class="employee-role"><?= htmlspecialchars($emp['role']) ?></div>
                                        </div>
                                    </div>
                                    <div class="employee-metrics">
                                        <div class="metric-mini">
                                            <div class="metric-mini-value"><?= $emp['task_count'] ?></div>
                                            <div class="metric-mini-label">Tasks</div>
                                        </div>
                                        <div class="metric-mini">
                                            <div class="metric-mini-value"><?= $emp['completed_tasks'] ?></div>
                                            <div class="metric-mini-label">Done</div>
                                        </div>
                                        <div class="metric-mini">
                                            <div class="metric-mini-value"><?= $emp['overdue_tasks'] ?></div>
                                            <div class="metric-mini-label">Overdue</div>
                                        </div>
                                        <div class="metric-mini">
                                            <div class="metric-mini-value"><?= round($emp['efficiency_ratio']) ?>%</div>
                                            <div class="metric-mini-label">Efficiency</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Quick Insights -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-bolt"></i>
                                    Quick Insights
                                </h2>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 16px;">
                                <?php if ($stats['overdue_tasks'] > 0): ?>
                                <div style="padding: 16px; background: rgba(239, 68, 68, 0.1); border-radius: 12px; border-left: 4px solid var(--danger);">
                                    <h4 style="margin-bottom: 8px; color: var(--danger);">
                                        ⚠️ Overdue Items
                                    </h4>
                                    <p style="color: var(--gray-700);"><?= $stats['overdue_tasks'] ?> tasks are overdue and need immediate attention.</p>
                                </div>
                                <?php else: ?>
                                <div style="padding: 16px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; border-left: 4px solid var(--success);">
                                    <h4 style="margin-bottom: 8px; color: var(--success);">
                                        ✅ On Track
                                    </h4>
                                    <p style="color: var(--gray-700);">No overdue tasks. Team is performing well!</p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($stats['urgent_tasks'] > 0): ?>
                                <div style="padding: 16px; background: rgba(245, 158, 11, 0.1); border-radius: 12px; border-left: 4px solid var(--warning);">
                                    <h4 style="margin-bottom: 8px; color: var(--warning);">
                                        🔥 Urgent Tasks
                                    </h4>
                                    <p style="color: var(--gray-700);"><?= $stats['urgent_tasks'] ?> urgent tasks require prioritization.</p>
                                </div>
                                <?php endif; ?>
                                
                                <div style="padding: 16px; background: var(--gray-50); border-radius: 12px; border-left: 4px solid var(--info);">
                                    <h4 style="margin-bottom: 8px; color: var(--info);">📊 Team Efficiency</h4>
                                    <p style="color: var(--gray-700);">Average efficiency: <?= round($stats['avg_productivity']) ?>% | Total hours: <?= round($stats['total_hours_logged']) ?>h</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Feed & Charts -->
                    <div class="analytics-grid">
                        <div class="section-card">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-chart-line"></i>
                                    Productivity Trends
                                </h2>
                            </div>
                            <div class="chart-container">
                                📈 Productivity charts will be displayed here
                            </div>
                        </div>

                        <div class="section-card">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-clock"></i>
                                    Recent Activity
                                </h2>
                            </div>
                            <div class="activity-feed">
                                <?php foreach ($recentActivity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon"></div>
                                    <div class="activity-text"><?= htmlspecialchars($activity['activity']) ?></div>
                                    <div class="activity-time"><?= $activity['time_formatted'] ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Tab -->
                <div id="team" class="tab-content">
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-users"></i>
                                Team Management
                            </h2>
                            <div>
                                <button class="btn btn-secondary" onclick="filterTeam('all')">All</button>
                                <button class="btn btn-secondary" onclick="filterTeam('active')">Active</button>
                                <button class="btn btn-secondary" onclick="filterTeam('overloaded')">Overloaded</button>
                            </div>
                        </div>
                        
                        <div class="employee-grid">
                            <?php foreach ($employees as $emp): ?>
                            <div class="employee-card <?= $emp['status'] ?>" id="emp-<?= $emp['id'] ?>" data-status="<?= $emp['status'] ?>">
                                <?php 
                                $activeTasks = $emp['task_count'] - $emp['completed_tasks'];
                                $capacity = $activeTasks == 0 ? 'available' : 
                                           ($activeTasks <= 3 ? 'light' : 
                                           ($activeTasks <= 7 ? 'moderate' : 
                                           ($activeTasks <= 12 ? 'heavy' : 'overloaded')));
                                ?>
                                <div class="capacity-indicator capacity-<?= $capacity ?>"><?= ucfirst($capacity) ?></div>
                                <div class="employee-header">
                                    <div class="employee-avatar">
                                        <?= substr($emp['name'], 0, 2) ?>
                                        <div class="status-dot-mini"></div>
                                    </div>
                                    <div class="employee-info">
                                        <div class="employee-name"><?= htmlspecialchars($emp['name']) ?></div>
                                        <div class="employee-role"><?= htmlspecialchars($emp['role']) ?> • <?= ucfirst($emp['type']) ?></div>
                                    </div>
                                </div>
                                <div class="employee-metrics">
                                    <div class="metric-mini">
                                        <div class="metric-mini-value"><?= $emp['task_count'] ?></div>
                                        <div class="metric-mini-label">Tasks</div>
                                    </div>
                                    <div class="metric-mini">
                                        <div class="metric-mini-value"><?= $emp['completed_tasks'] ?></div>
                                        <div class="metric-mini-label">Done</div>
                                    </div>
                                    <div class="metric-mini">
                                        <div class="metric-mini-value"><?= $emp['overdue_tasks'] ?></div>
                                        <div class="metric-mini-label">Overdue</div>
                                    </div>
                                    <div class="metric-mini">
                                        <div class="metric-mini-value"><?= round($emp['efficiency_ratio']) ?>%</div>
                                        <div class="metric-mini-label">Efficiency</div>
                                    </div>
                                </div>
                                <div style="margin-top: 1rem;">
                                    <select class="btn btn-secondary" style="width: 100%;" onchange="updateEmployeeStatus(<?= $emp['id'] ?>, this.value)">
                                        <option value="active" <?= $emp['status'] === 'active' ? 'selected' : '' ?>>🟢 Active</option>
                                        <option value="busy" <?= $emp['status'] === 'busy' ? 'selected' : '' ?>>🔴 Busy</option>
                                        <option value="away" <?= $emp['status'] === 'away' ? 'selected' : '' ?>>🟡 Away</option>
                                        <option value="offline" <?= $emp['status'] === 'offline' ? 'selected' : '' ?>>⚪ Offline</option>
                                    </select>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Productivity Tab -->
                <div id="productivity" class="tab-content">
                    <div class="section-card">
                        <h2 class="section-title">
                            <i class="fas fa-chart-line"></i>
                            Productivity Analytics
                        </h2>
                        
                        <?php if ($insights && isset($insights['top_performers'])): ?>
                        <div style="margin-top: 2rem;">
                            <h3 style="margin-bottom: 1rem;">🏆 Top Performers</h3>
                            <div class="employee-grid">
                                <?php foreach ($insights['top_performers'] as $performer): ?>
                                <div style="padding: 1rem; background: var(--bg-tertiary); border-radius: 12px; border-left: 4px solid var(--success);">
                                    <h4><?= htmlspecialchars($performer['name']) ?></h4>
                                    <p style="color: var(--gray-600);"><?= htmlspecialchars($performer['role']) ?></p>
                                    <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                                        <span><strong><?= $performer['completed_tasks'] ?></strong> tasks</span>
                                        <span><strong><?= round($performer['efficiency_score']) ?>%</strong> efficiency</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Projects Tab -->
                <div id="projects" class="tab-content">
                    <div class="section-card">
                        <h2 class="section-title">
                            <i class="fas fa-project-diagram"></i>
                            Active Projects
                        </h2>
                        
                        <div style="display: grid; gap: 1rem; margin-top: 2rem;">
                            <?php foreach ($projects as $project): ?>
                            <div style="padding: 1.5rem; background: var(--bg-tertiary); border-radius: 12px; border-left: 4px solid <?= 
                                $project['priority'] === 'urgent' ? 'var(--danger)' : 
                                ($project['priority'] === 'high' ? 'var(--warning)' : 
                                ($project['priority'] === 'medium' ? 'var(--info)' : 'var(--success)')) ?>;">
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <h3><?= htmlspecialchars($project['name']) ?></h3>
                                    <span style="padding: 4px 12px; background: rgba(79, 70, 229, 0.1); color: var(--primary); border-radius: 20px; font-size: 12px; font-weight: 700;">
                                        <?= $project['progress'] ?>% Complete
                                    </span>
                                </div>
                                
                                <div style="display: flex; gap: 2rem; color: var(--gray-600); font-size: 14px;">
                                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($project['client'] ?? 'Internal') ?></span>
                                    <span><i class="fas fa-tasks"></i> <?= $project['total_tasks'] ?> tasks</span>
                                    <span><i class="fas fa-users"></i> <?= $project['team_size'] ?> members</span>
                                    <?php if ($project['next_deadline']): ?>
                                    <span><i class="fas fa-calendar"></i> Next: <?= date('M d', strtotime($project['next_deadline'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="margin-top: 1rem; background: var(--gray-200); height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; background: linear-gradient(90deg, var(--success), #34D399); width: <?= $project['progress'] ?>%;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Other tabs -->
                <div id="capacity" class="tab-content">
                    <h2>Capacity Planning</h2>
                    <p>Team capacity analysis and workload distribution.</p>
                </div>

                <div id="reports" class="tab-content">
                    <h2>Reports & Analytics</h2>
                    <p>Comprehensive reports and data exports.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab management
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            document.getElementById(tabId).classList.add('active');
            event.target.closest('.nav-link').classList.add('active');
        }

        // Update employee status
        async function updateEmployeeStatus(employeeId, status) {
            try {
                const response = await fetch('dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_employee_status&employee_id=${employeeId}&status=${status}`
                });
                
                const result = await response.json();
                if (result.success) {
                    // Update the card's visual status
                    const card = document.getElementById(`emp-${employeeId}`);
                    card.className = `employee-card ${status}`;
                    card.dataset.status = status;
                } else {
                    alert('Error updating status');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating status');
            }
        }

        // Refresh dashboard data
        async function refreshDashboard() {
            location.reload();
        }

        // Generate report
        function generateReport() {
            alert('📊 Generating comprehensive productivity report...\n\nThis will include:\n• Team performance metrics\n• Individual productivity scores\n• Project completion rates\n• Capacity utilization\n• Trend analysis');
        }

        // Show employee details
        function showEmployeeDetails(employeeId) {
            alert(`👤 Employee details for ID: ${employeeId}\n\nThis will open a detailed view with:\n• Individual performance metrics\n• Task breakdown\n• Recent activity\n• Productivity trends`);
        }

        // Filter team
        function filterTeam(filter) {
            const cards = document.querySelectorAll('.employee-card');
            
            cards.forEach(card => {
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'active') {
                    card.style.display = card.dataset.status === 'active' ? 'block' : 'none';
                } else if (filter === 'overloaded') {
                    const capacity = card.querySelector('.capacity-indicator');
                    card.style.display = capacity && capacity.classList.contains('capacity-overloaded') ? 'block' : 'none';
                }
            });
        }

        // Logout
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'auth.php?action=logout';
            }
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            if (document.getElementById('overview').classList.contains('active')) {
                // Could implement AJAX refresh here
                console.log('Auto-refresh check');
            }
        }, 30000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case '1': e.preventDefault(); showTab('overview'); break;
                    case '2': e.preventDefault(); showTab('team'); break;
                    case '3': e.preventDefault(); showTab('productivity'); break;
                    case '4': e.preventDefault(); showTab('capacity'); break;
                    case 'r': e.preventDefault(); refreshDashboard(); break;
                }
            }
        });
    </script>
</body>
</html>