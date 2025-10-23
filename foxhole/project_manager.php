<?php
// ==================== SECTION 1: FILE VALIDATION & SETUP ====================
// Purpose: Check for required files and setup error reporting
// Dependencies: None
// Last updated: Current
// CLAUDE NOTE: For future updates to this section, only provide PHP code between 
// this marker and SECTION 2 marker. Include file checks, error reporting setup.

// project_manager.php - Enhanced Project Manager Dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if API file exists
if (!file_exists('api_enhanced.php')) {
    die('<div style="background: #fee; color: #c33; padding: 20px; border-radius: 8px; margin: 20px;">
        <h3>⚠️ API File Missing</h3>
        <p>The <code>api_enhanced.php</code> file is missing. Please create it first.</p>
        <p>Forms will not work without this file.</p>
        </div>');
}

// ==================== SECTION 2: DATABASE CONNECTION TEST ====================
// Purpose: Test database connection and show status
// Dependencies: config-2.php
// CLAUDE NOTE: For future updates to this section, provide the database connection
// test logic and status reporting code.

require_once 'config-2.php';

// Test database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    $db->query("SELECT 1");
    $dbStatus = "✅ Connected";
} catch (Exception $e) {
    $dbStatus = "❌ Failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ==================== SECTION 3: HTML HEAD & META TAGS ==================== -->
    <!-- Purpose: HTML structure, meta tags, external resources -->
    <!-- Dependencies: None -->
    <!-- CLAUDE NOTE: For future updates to this section, provide the complete <head> -->
    <!-- section including meta tags, title, and external resource links (fonts, icons). -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole - Project Manager Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* ==================== SECTION 4: CSS VARIABLES & ROOT STYLES ==================== */
        /* Purpose: CSS custom properties, color system, base styles */
        /* Dependencies: None */
        /* CLAUDE NOTE: For future updates to this section, provide the :root variable */
        /* definitions and base element styles (*, body, html). Color system updates go here. */
        :root {
            /* Color System */
            --primary: #4F46E5;
            --primary-light: #6366F1;
            --secondary: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --success: #10B981;
            --info: #3B82F6;
            
            /* Status Colors */
            --status-active: #16A34A;
            --status-busy: #DC2626;
            --status-away: #EA580C;
            --status-offline: #6B7280;
            
            /* Priority Colors */
            --priority-urgent: #7C2D12;
            --priority-high: #DC2626;
            --priority-medium: #F59E0B;
            --priority-low: #16A34A;
            
            /* Neutrals */
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
            --bg-tertiary: #F8FAFC;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            font-size: 14px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* ==================== SECTION 5: HEADER STYLES ==================== */
        /* Purpose: Top header and navigation styling */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS rules */
        /* for .header, .header-content, .logo, .header-actions, etc. */

        .header {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--gray-200);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 32px;
            font-weight: 900;
            color: var(--primary);
        }

        .logo-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: var(--shadow-md);
        }

        .header-info h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .header-info p {
            font-size: 16px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        /* ==================== SECTION 6: BUTTON STYLES ==================== */
        /* Purpose: Button components and variations */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all button CSS */
        /* including .btn, .btn-primary, .btn-secondary, hover states, etc. */

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
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
            transform: translateY(-1px);
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* ==================== SECTION 7: STATS GRID STYLES ==================== */
        /* Purpose: Dashboard statistics cards */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* .stats-grid, .stat-card, .stat-number, .stat-label, .stat-change, etc. */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--gray-200);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .stat-number {
            font-size: 48px;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 8px;
            line-height: 1;
        }

        .stat-label {
            font-size: 16px;
            color: var(--gray-600);
            font-weight: 600;
            margin-bottom: 12px;
        }

        .stat-change {
            font-size: 14px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .stat-change.positive { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-change.negative { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        /* ==================== SECTION 8: TAB STYLES ==================== */
        /* Purpose: Tab navigation and content areas */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* .tabs, .tab, .tab-content, active states, and transitions. */

        .tabs {
            display: flex;
            border-bottom: 3px solid var(--gray-200);
            margin-bottom: 2rem;
            gap: 4px;
            background: var(--bg-secondary);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: 0 2rem;
            box-shadow: var(--shadow-sm);
        }

        .tab {
            padding: 20px 28px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 700;
            color: var(--gray-600);
            border-bottom: 4px solid transparent;
            transition: var(--transition);
            font-size: 16px;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab:hover {
            background: var(--gray-50);
            color: var(--gray-800);
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: var(--bg-primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ==================== SECTION 9: LAYOUT & GRID STYLES ==================== */
        /* Purpose: Main layout grids and section containers */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* .main-grid, .section-card, .section-header, .section-title, etc. */

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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

        /* ==================== SECTION 10: FORM STYLES ==================== */
        /* Purpose: Form inputs, labels, and layouts */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* forms including .form-grid, .form-group, .form-input, .form-select, etc. */

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--gray-700);
            font-size: 14px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: var(--transition);
            font-family: inherit;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* ==================== SECTION 11: LIST & DATA DISPLAY STYLES ==================== */
        /* Purpose: Data lists, employee items, task items */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* .data-list, .list-item, .employee-item, .task-item, and related components. */

        .data-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .list-item {
            display: flex;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--gray-200);
            transition: var(--transition);
            cursor: pointer;
        }

        .list-item:hover {
            background: var(--gray-50);
            border-radius: var(--radius-sm);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        /* Employee Items */
        .employee-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 2px solid var(--gray-200);
            transition: var(--transition);
        }

        .employee-item:hover {
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }

        .employee-checkbox {
            margin-right: 16px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .employee-avatar {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 20px;
            margin-right: 20px;
            box-shadow: var(--shadow-md);
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
            margin-bottom: 8px;
        }

        .employee-stats {
            display: flex;
            gap: 16px;
            margin-top: 8px;
        }

        .stat-mini {
            text-align: center;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            min-width: 60px;
        }

        .stat-mini-value {
            font-weight: 800;
            font-size: 16px;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        .stat-mini-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
            font-weight: 600;
        }

        .status-dropdown {
            margin-left: 20px;
        }

        .status-select {
            border: 2px solid var(--gray-300);
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
            cursor: pointer;
        }

        /* ==================== SECTION 12: STATUS & PRIORITY BADGES ==================== */
        /* Purpose: Status badges, priority indicators, and color coding */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* status badges, priority badges, and their color variations. */

        .status-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active { background: rgba(22, 163, 74, 0.1); color: var(--status-active); }
        .status-busy { background: rgba(220, 38, 38, 0.1); color: var(--status-busy); }
        .status-away { background: rgba(234, 88, 12, 0.1); color: var(--status-away); }
        .status-offline { background: var(--gray-200); color: var(--status-offline); }

        .priority-urgent { background: rgba(124, 45, 18, 0.1); color: var(--priority-urgent); border-left: 4px solid var(--priority-urgent); }
        .priority-high { background: rgba(220, 38, 38, 0.1); color: var(--priority-high); border-left: 4px solid var(--priority-high); }
        .priority-medium { background: rgba(245, 158, 11, 0.1); color: var(--priority-medium); border-left: 4px solid var(--priority-medium); }
        .priority-low { background: rgba(22, 163, 74, 0.1); color: var(--priority-low); border-left: 4px solid var(--priority-low); }

        /* ==================== SECTION 13: TASK & PROJECT CARD STYLES ==================== */
        /* Purpose: Task items, project cards, and their layouts */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* .task-item, .task-info, .task-meta, .task-actions, and related components. */

        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            margin-bottom: 16px;
            transition: var(--transition);
        }

        .task-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .task-info {
            flex: 1;
        }

        .task-info h4 {
            margin-bottom: 8px;
            color: var(--gray-900);
            font-weight: 700;
            font-size: 16px;
        }

        .task-meta {
            font-size: 13px;
            color: var(--gray-600);
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .task-actions {
            display: flex;
            gap: 8px;
            margin-left: 20px;
        }

        /* ==================== SECTION 14: BULK ACTIONS & UTILITIES ==================== */
        /* Purpose: Bulk action controls and utility components */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* .bulk-actions, .bulk-select, .empty-state, and utility components. */

        .bulk-actions {
            background: var(--gray-50);
            padding: 20px;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            border: 2px solid var(--gray-200);
        }

        .bulk-actions-content {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .bulk-label {
            font-weight: 700;
            color: var(--gray-700);
            font-size: 16px;
        }

        .bulk-select {
            min-width: 160px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* ==================== SECTION 15: MESSAGE & MODAL STYLES ==================== */
        /* Purpose: Messages, modals, and overlay components */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* .message, .modal, .modal-content, and overlay components. */

        .message {
            margin-bottom: 2rem;
            padding: 20px 24px;
            border-radius: var(--radius-lg);
            font-weight: 600;
            border: 2px solid transparent;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: block;
        }

        .modal-content {
            background: var(--bg-secondary);
            margin: 5% auto;
            padding: 2rem;
            border-radius: var(--radius-xl);
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gray-200);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .modal-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
        }

        .close {
            font-size: 32px;
            cursor: pointer;
            color: var(--gray-400);
            transition: var(--transition);
            border: none;
            background: none;
            padding: 8px;
            border-radius: var(--radius-sm);
        }

        .close:hover {
            color: var(--gray-600);
            background: var(--gray-100);
        }

        .modal-body {
            padding: 1rem 0;
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid var(--gray-200);
        }

        /* Action buttons for edit/delete */
        .action-buttons {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .btn-icon {
            padding: 8px 12px;
            font-size: 14px;
            min-width: auto;
        }

        .btn-edit {
            background: var(--info);
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* ==================== SECTION 16: RESPONSIVE STYLES ==================== */
        /* Purpose: Mobile and tablet responsive design */
        /* Dependencies: All previous CSS */
        /* CLAUDE NOTE: For future updates to this section, provide all @media queries */
        /* and responsive design rules for mobile, tablet, and desktop breakpoints. */

        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-wrap: wrap;
                padding: 0 1rem;
            }
            
            .employee-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .employee-stats {
                justify-content: space-between;
                width: 100%;
            }
        }

        /* ==================== SECTION 17: LOADING & ANIMATION STYLES ==================== */
        /* Purpose: Loading states, spinners, and animations */
        /* Dependencies: CSS variables */
        /* CLAUDE NOTE: For future updates to this section, provide all CSS for */
        /* loading states, spinners, animations, and transition effects. */

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--gray-500);
            font-weight: 600;
            padding: 40px;
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid var(--gray-300);
            border-top: 3px solid var(--primary);
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
    <!-- ==================== SECTION 18: MAIN CONTAINER & HEADER ==================== -->
    <!-- Purpose: Main page container and header section -->
    <!-- Dependencies: PHP variables ($dbStatus) -->
    <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
    <!-- container div, header section with logo, title, and action buttons. -->
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <div class="logo">
                        <div class="logo-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <div class="header-info">
                        <h1>Project Manager Hub</h1>
                        <p>Team & Project Data Management Center</p>
                        <small style="color: var(--gray-500);">Database: <?= $dbStatus ?></small>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-chart-pie"></i>
                        Admin Dashboard
                    </a>
                    <button class="btn btn-primary" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Message Display -->
        <div id="message-container"></div>

        <!-- ==================== SECTION 19: STATS GRID DISPLAY ==================== -->
        <!-- Purpose: Dashboard statistics overview -->
        <!-- Dependencies: JavaScript variables (employees, projects, tasks) -->
        <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
        <!-- stats grid with all metric cards and their dynamic content. -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="total-employees">0</div>
                <div class="stat-label">Total Team Members</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    All hands on deck
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="active-employees">0</div>
                <div class="stat-label">Active Today</div>
                <div class="stat-change positive">
                    <i class="fas fa-fire"></i>
                    Ready to work
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-projects">0</div>
                <div class="stat-label">Active Projects</div>
                <div class="stat-change positive">
                    <i class="fas fa-rocket"></i>
                    In progress
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-tasks">0</div>
                <div class="stat-label">Total Tasks</div>
                <div class="stat-change positive">
                    <i class="fas fa-check"></i>
                    Getting done
                </div>
            </div>
        </div>

        <!-- ==================== SECTION 20: TAB NAVIGATION ==================== -->
        <!-- Purpose: Main navigation tabs -->
        <!-- Dependencies: JavaScript showTab() function -->
        <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
        <!-- tab navigation structure with all tab buttons and their onclick handlers. -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('team-management')">
                <i class="fas fa-users"></i> Team Management
            </button>
            <button class="tab" onclick="showTab('project-management')">
                <i class="fas fa-project-diagram"></i> Project Management
            </button>
            <button class="tab" onclick="showTab('task-management')">
                <i class="fas fa-tasks"></i> Task Management
            </button>
            <button class="tab" onclick="showTab('analytics')">
                <i class="fas fa-chart-bar"></i> Analytics
            </button>
        </div>

        <!-- ==================== SECTION 21: TEAM MANAGEMENT TAB ==================== -->
        <!-- Purpose: Employee management interface -->
        <!-- Dependencies: JavaScript loadEmployees(), form submission handlers -->
        <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
        <!-- team management tab including add employee form and employee list display. -->
        <div id="team-management" class="tab-content active">
            <div class="main-grid">
                <!-- Add Employee Form -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-user-plus"></i>
                            Add Team Member
                        </h2>
                    </div>
                    
                    <form id="add-employee-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-input" required 
                                       placeholder="John Doe">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-input" required 
                                       placeholder="john@neofoxmedia.com">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role/Position *</label>
                                <input type="text" name="role" class="form-input" required 
                                       placeholder="e.g., Video Editor, Designer">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Employment Type</label>
                                <select name="type" class="form-select" required>
                                    <option value="employee">Full-time Employee</option>
                                    <option value="freelancer">Freelancer/Contractor</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-plus"></i>
                            Add Team Member
                        </button>
                    </form>
                </div>

                <!-- Team List & Management -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-users"></i>
                            Team Overview
                        </h2>
                        <button class="btn btn-secondary" onclick="exportTeamData()">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div class="bulk-actions">
                        <div class="bulk-actions-content">
                            <span class="bulk-label">Bulk Actions:</span>
                            <select id="bulk-status" class="form-select bulk-select">
                                <option value="active">Set Active</option>
                                <option value="busy">Set Busy</option>
                                <option value="away">Set Away</option>
                                <option value="offline">Set Offline</option>
                            </select>
                            <button class="btn btn-warning" onclick="bulkUpdateStatus()">
                                <i class="fas fa-edit"></i>
                                Update Selected
                            </button>
                            <button class="btn btn-secondary" onclick="selectAllEmployees()">
                                <i class="fas fa-check-square"></i>
                                Select All
                            </button>
                        </div>
                    </div>
                    
                    <div class="data-list" id="employee-list">
                        <div class="loading">
                            <div class="spinner"></div>
                            Loading team data...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SECTION 22: PROJECT MANAGEMENT TAB ==================== -->
        <!-- Purpose: Project creation and management interface -->
        <!-- Dependencies: JavaScript loadProjects(), form submission handlers -->
        <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
        <!-- project management tab including add project form and project list display. -->
        <div id="project-management" class="tab-content">
            <div class="main-grid">
                <!-- Add Project Form -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-plus-circle"></i>
                            Create New Project
                        </h2>
                    </div>
                    
                    <form id="add-project-form">
                        <div class="form-group">
                            <label class="form-label">Project Name *</label>
                            <input type="text" name="name" class="form-input" required 
                                   placeholder="e.g., Brand Redesign for ABC Corp">
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Client/Company</label>
                                <input type="text" name="client" class="form-input" 
                                       placeholder="Client name or internal project">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Priority Level</label>
                                <select name="priority" class="form-select" required>
                                    <option value="low">Low Priority</option>
                                    <option value="medium" selected>Medium Priority</option>
                                    <option value="high">High Priority</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Target End Date</label>
                                <input type="date" name="end_date" class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Project Description</label>
                            <textarea name="description" class="form-textarea" 
                                      placeholder="Brief description of the project scope and objectives..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-plus"></i>
                            Create Project
                        </button>
                    </form>
                </div>

                <!-- Projects List -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-project-diagram"></i>
                            Active Projects
                        </h2>
                        <button class="btn btn-secondary" onclick="exportProjectData()">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                    </div>
                    
                    <div class="data-list" id="project-list">
                        <div class="loading">
                            <div class="spinner"></div>
                            Loading projects...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SECTION 23: TASK MANAGEMENT TAB ==================== -->
        <!-- Purpose: Task assignment and management interface -->
        <!-- Dependencies: JavaScript loadTasks(), form submission handlers, dropdown loading -->
        <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
        <!-- task management tab including add task form and task list display. -->
        <div id="task-management" class="tab-content">
            <div class="main-grid">
                <!-- Add Task Form -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-tasks"></i>
                            Assign New Task
                        </h2>
                    </div>
                    
                    <form id="add-task-form">
                        <div class="form-group">
                            <label class="form-label">Task Title *</label>
                            <input type="text" name="title" class="form-input" required 
                                   placeholder="e.g., Create logo concepts">
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Assign to Employee</label>
                                <select name="employee_id" class="form-select" id="task-employee-select">
                                    <option value="">Unassigned</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" id="task-project-select">
                                    <option value="">No Project</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estimated Hours</label>
                            <input type="number" name="estimated_hours" class="form-input" 
                                   step="0.5" min="0" placeholder="e.g., 4.5">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Task Description</label>
                            <textarea name="description" class="form-textarea" 
                                      placeholder="Detailed task description and requirements..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-plus"></i>
                            Assign Task
                        </button>
                    </form>
                </div>

                <!-- Tasks Overview -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-list"></i>
                            Recent Tasks
                        </h2>
                        <div>
                            <button class="btn btn-secondary" onclick="filterTasks('all')">All</button>
                            <button class="btn btn-secondary" onclick="filterTasks('urgent')">Urgent</button>
                            <button class="btn btn-secondary" onclick="filterTasks('overdue')">Overdue</button>
                        </div>
                    </div>
                    
                    <div class="data-list" id="task-list">
                        <div class="loading">
                            <div class="spinner"></div>
                            Loading tasks...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SECTION 24: ANALYTICS TAB ==================== -->
        <!-- Purpose: Analytics and reporting interface -->
        <!-- Dependencies: JavaScript loadAnalytics() function -->
        <!-- CLAUDE NOTE: For future updates to this section, provide the complete -->
        <!-- analytics tab including charts, reports, and data visualization. -->
        <div id="analytics" class="tab-content">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-chart-bar"></i>
                        Team Analytics & Reports
                    </h2>
                    <button class="btn btn-primary" onclick="generateFullReport()">
                        <i class="fas fa-file-excel"></i>
                        Generate Full Report
                    </button>
                </div>
                
                <div id="analytics-content">
                    <div class="loading">
                        <div class="spinner"></div>
                        Loading analytics...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODALS ==================== -->
    <!-- Edit Employee Modal -->
    <div id="editEmployeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Employee</h2>
                <button class="close" onclick="closeModal('editEmployeeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="edit-employee-form">
                    <input type="hidden" id="edit-employee-id" name="employee_id">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" id="edit-employee-name" name="name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" id="edit-employee-email" name="email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role/Position *</label>
                            <input type="text" id="edit-employee-role" name="role" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Employment Type</label>
                            <select id="edit-employee-type" name="type" class="form-select">
                                <option value="employee">Full-time Employee</option>
                                <option value="freelancer">Freelancer/Contractor</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('editEmployeeModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateEmployee()">Update Employee</button>
            </div>
        </div>
    </div>

    <!-- Edit Project Modal -->
    <div id="editProjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Project</h2>
                <button class="close" onclick="closeModal('editProjectModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="edit-project-form">
                    <input type="hidden" id="edit-project-id" name="project_id">
                    <div class="form-group">
                        <label class="form-label">Project Name *</label>
                        <input type="text" id="edit-project-name" name="name" class="form-input" required>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Client/Company</label>
                            <input type="text" id="edit-project-client" name="client" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Priority Level</label>
                            <select id="edit-project-priority" name="priority" class="form-select">
                                <option value="low">Low Priority</option>
                                <option value="medium">Medium Priority</option>
                                <option value="high">High Priority</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="edit-project-start-date" name="start_date" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target End Date</label>
                            <input type="date" id="edit-project-end-date" name="end_date" class="form-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Project Description</label>
                        <textarea id="edit-project-description" name="description" class="form-textarea"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('editProjectModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateProject()">Update Project</button>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div id="editTaskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Task</h2>
                <button class="close" onclick="closeModal('editTaskModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="edit-task-form">
                    <input type="hidden" id="edit-task-id" name="task_id">
                    <div class="form-group">
                        <label class="form-label">Task Title *</label>
                        <input type="text" id="edit-task-title" name="title" class="form-input" required>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Assign to Employee</label>
                            <select id="edit-task-employee" name="employee_id" class="form-select">
                                <option value="">Unassigned</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Project</label>
                            <select id="edit-task-project" name="project_id" class="form-select">
                                <option value="">No Project</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select id="edit-task-priority" name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select id="edit-task-status" name="status" class="form-select">
                                <option value="todo">To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="review">Review</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Due Date</label>
                            <input type="date" id="edit-task-due-date" name="due_date" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Hours</label>
                            <input type="number" id="edit-task-estimated-hours" name="estimated_hours" class="form-input" step="0.5" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Task Description</label>
                        <textarea id="edit-task-description" name="description" class="form-textarea"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('editTaskModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateTask()">Update Task</button>
            </div>
        </div>
    </div>

    <!-- ==================== SECTION 25: CORE JAVASCRIPT FUNCTIONS ==================== -->
    <!-- Purpose: Main application logic, data management, UI interactions -->
    <!-- Dependencies: api_enhanced.php endpoints -->
    <!-- CLAUDE NOTE: For future updates to this section, provide the complete JavaScript -->
    <!-- code including all core functions, data loading, form handling, and UI updates. -->
    <script>
        // ==================== GLOBAL VARIABLES ====================
        
        let employees = [];
        let projects = [];
        let tasks = [];
        let currentTab = 'team-management';
        let refreshInterval;

        // ==================== DEBUGGING & ERROR HANDLING ====================
        
        function logError(message, error = null) {
            console.error('🔥 FOXHOLE ERROR:', message, error);
            showMessage(`Error: ${message}`, 'error');
        }

        function logSuccess(message) {
            console.log('✅ FOXHOLE SUCCESS:', message);
            showMessage(message, 'success');
        }

        // ==================== TAB MANAGEMENT ====================
        
        function showTab(tabId) {
            console.log('🔄 Switching to tab:', tabId);
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.classList.add('active');
                console.log('✅ Tab activated:', tabId);
            } else {
                logError(`Tab not found: ${tabId}`);
                return;
            }
            
            // Add active class to clicked tab
            const clickedTab = event?.target?.closest('.tab');
            if (clickedTab) {
                clickedTab.classList.add('active');
            }
            
            currentTab = tabId;
            
            // Load tab-specific data
            loadTabData(tabId);
        }

        function loadTabData(tabId) {
            console.log('📊 Loading data for tab:', tabId);
            
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
                default:
                    logError(`Unknown tab: ${tabId}`);
            }
        }

        // ==================== DATA LOADING FUNCTIONS ====================
        
        async function loadEmployees() {
            console.log('👥 Loading employees...');
            
            try {
                // Add timestamp to prevent caching
                const response = await fetch(`api_enhanced.php?action=employees&_t=${Date.now()}`);
                console.log('👥 Employee response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('👥 Employee data received:', data);
                
                if (Array.isArray(data)) {
                    employees = data;
                    updateEmployeeList();
                    updateStats();
                    console.log(`✅ Loaded ${employees.length} employees`);
                } else {
                    console.error('Invalid employee data format:', data);
                    throw new Error('Invalid response format - expected array');
                }
                
            } catch (error) {
                logError('Failed to load employees', error);
                // Show empty state instead of breaking
                employees = [];
                updateEmployeeList();
            }
        }

        async function loadProjects() {
            console.log('🎯 Loading projects...');
            
            try {
                // Add timestamp to prevent caching
                const response = await fetch(`api_enhanced.php?action=projects&_t=${Date.now()}`);
                console.log('🎯 Project response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('🎯 Project data received:', data);
                
                if (Array.isArray(data)) {
                    projects = data;
                    updateProjectList();
                    updateStats();
                    console.log(`✅ Loaded ${projects.length} projects`);
                } else {
                    console.error('Invalid project data format:', data);
                    throw new Error('Invalid response format - expected array');
                }
                
            } catch (error) {
                logError('Failed to load projects', error);
                projects = [];
                updateProjectList();
            }
        }

        async function loadTasks() {
            console.log('📋 Loading tasks...');
            
            try {
                // Add timestamp to prevent caching
                const response = await fetch(`api_enhanced.php?action=tasks&_t=${Date.now()}`);
                console.log('📋 Task response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('📋 Task data received:', data);
                
                if (Array.isArray(data)) {
                    tasks = data;
                    updateTaskList();
                    updateStats();
                    console.log(`✅ Loaded ${tasks.length} tasks`);
                } else {
                    console.error('Invalid task data format:', data);
                    throw new Error('Invalid response format - expected array');
                }
                
            } catch (error) {
                logError('Failed to load tasks', error);
                tasks = [];
                updateTaskList();
            }
        }

        async function loadEmployeesForDropdown() {
            console.log('📋 Loading employees for dropdown...');
            
            if (employees.length === 0) {
                await loadEmployees();
            }
            
            const select = document.getElementById('task-employee-select');
            if (select) {
                select.innerHTML = '<option value="">Unassigned</option>' +
                    employees.map(emp => `<option value="${emp.id}">${emp.name} (${emp.role})</option>`).join('');
                console.log('✅ Employee dropdown updated');
            }
        }

        async function loadProjectsForDropdown() {
            console.log('📋 Loading projects for dropdown...');
            
            if (projects.length === 0) {
                await loadProjects();
            }
            
            const select = document.getElementById('task-project-select');
            if (select) {
                select.innerHTML = '<option value="">No Project</option>' +
                    projects.map(proj => `<option value="${proj.id}">${proj.name}</option>`).join('');
                console.log('✅ Project dropdown updated');
            }
        }

        // ==================== UI UPDATE FUNCTIONS ====================
        
        function updateStats() {
            const statsElements = {
                'total-employees': employees.length || 0,
                'active-employees': employees.filter(emp => emp.status === 'active').length || 0,
                'total-projects': projects.length || 0,
                'total-tasks': tasks.length || 0
            };
            
            Object.entries(statsElements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = value;
                }
            });
            
            console.log('📊 Stats updated:', statsElements);
        }

        function updateEmployeeList() {
            const container = document.getElementById('employee-list');
            
            if (!container) {
                logError('Employee list container not found');
                return;
            }
            
            if (employees.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-users" style="font-size: 48px; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <p style="color: var(--gray-600); font-size: 16px; margin-bottom: 1rem;">No employees found</p>
                        <p style="color: var(--gray-500); font-size: 14px;">Add your first team member using the form above!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = employees.map(emp => `
                <div class="employee-item" data-employee-id="${emp.id}">
                    <input type="checkbox" class="employee-checkbox" value="${emp.id}">
                    <div class="employee-avatar">
                        ${getInitials(emp.name)}
                    </div>
                    <div class="employee-info">
                        <div class="employee-name">${escapeHtml(emp.name)}</div>
                        <div class="employee-role">${escapeHtml(emp.role)} • ${emp.type ? emp.type.charAt(0).toUpperCase() + emp.type.slice(1) : 'Employee'}</div>
                        <div class="employee-stats">
                            <div class="stat-mini">
                                <div class="stat-mini-value">${emp.task_count || 0}</div>
                                <div class="stat-mini-label">Tasks</div>
                            </div>
                            <div class="stat-mini">
                                <div class="stat-mini-value">${emp.completed_tasks || 0}</div>
                                <div class="stat-mini-label">Done</div>
                            </div>
                            <div class="stat-mini">
                                <div class="stat-mini-value">${emp.overdue_tasks || 0}</div>
                                <div class="stat-mini-label">Overdue</div>
                            </div>
                            <div class="stat-mini">
                                <div class="stat-mini-value">${Math.round(emp.hours_today || 0)}h</div>
                                <div class="stat-mini-label">Today</div>
                            </div>
                        </div>
                    </div>
                    <div class="status-dropdown">
                        <span class="status-badge status-${emp.status || 'offline'}">${emp.status || 'offline'}</span>
                        <select class="status-select" onchange="updateEmployeeStatus(${emp.id}, this.value)">
                            <option value="active" ${(emp.status === 'active') ? 'selected' : ''}>🟢 Active</option>
                            <option value="busy" ${(emp.status === 'busy') ? 'selected' : ''}>🔴 Busy</option>
                            <option value="away" ${(emp.status === 'away') ? 'selected' : ''}>🟡 Away</option>
                            <option value="offline" ${(emp.status === 'offline' || !emp.status) ? 'selected' : ''}>⚪ Offline</option>
                        </select>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-edit btn-icon" onclick="editEmployee(${emp.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-delete btn-icon" onclick="deleteEmployee(${emp.id}, '${escapeHtml(emp.name).replace(/'/g, "\\'")}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            
            console.log(`✅ Employee list updated with ${employees.length} employees`);
        }

        function updateProjectList() {
            const container = document.getElementById('project-list');
            
            if (!container) {
                logError('Project list container not found');
                return;
            }
            
            if (projects.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-project-diagram" style="font-size: 48px; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <p style="color: var(--gray-600); font-size: 16px; margin-bottom: 1rem;">No projects found</p>
                        <p style="color: var(--gray-500); font-size: 14px;">Create your first project using the form above!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = projects.map(project => `
                <div class="task-item priority-${project.priority}" data-project-id="${project.id}">
                    <div class="task-info">
                        <h4>${escapeHtml(project.name)}</h4>
                        <div class="task-meta">
                            <span>Client: ${escapeHtml(project.client || 'Internal')}</span>
                            <span>Priority: ${project.priority ? project.priority.charAt(0).toUpperCase() + project.priority.slice(1) : 'Medium'}</span>
                            <span>Progress: ${project.progress || 0}%</span>
                            <span>Tasks: ${project.total_tasks || 0}</span>
                            <span>Team: ${project.team_size || 0} members</span>
                        </div>
                    </div>
                    <div class="task-actions">
                        <span class="status-badge priority-${project.priority || 'medium'}">${project.priority || 'medium'}</span>
                        <button class="btn btn-secondary btn-icon" onclick="viewProject(${project.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-edit btn-icon" onclick="editProject(${project.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-delete btn-icon" onclick="deleteProject(${project.id}, '${escapeHtml(project.name).replace(/'/g, "\\'")}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            
            console.log(`✅ Project list updated with ${projects.length} projects`);
        }

        function updateTaskList() {
            const container = document.getElementById('task-list');
            
            if (!container) {
                logError('Task list container not found');
                return;
            }
            
            if (tasks.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-tasks" style="font-size: 48px; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <p style="color: var(--gray-600); font-size: 16px; margin-bottom: 1rem;">No tasks found</p>
                        <p style="color: var(--gray-500); font-size: 14px;">Assign your first task using the form above!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = tasks.slice(0, 20).map(task => `
                <div class="task-item priority-${task.priority || 'medium'}" data-task-id="${task.id}">
                    <div class="task-info">
                        <h4>${escapeHtml(task.title)}</h4>
                        <div class="task-meta">
                            <span>${task.employee_name ? `Assigned to: ${escapeHtml(task.employee_name)}` : 'Unassigned'}</span>
                            <span>${task.project_name ? `Project: ${escapeHtml(task.project_name)}` : 'No Project'}</span>
                            <span>Priority: ${task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Medium'}</span>
                            <span>Status: ${task.status ? task.status.replace('_', ' ').charAt(0).toUpperCase() + task.status.replace('_', ' ').slice(1) : 'Todo'}</span>
                            ${task.due_date ? `<span>Due: ${new Date(task.due_date).toLocaleDateString()}</span>` : ''}
                        </div>
                    </div>
                    <div class="task-actions">
                        <span class="status-badge priority-${task.priority || 'medium'}">${task.priority || 'medium'}</span>
                        <button class="btn btn-edit btn-icon" onclick="editTask(${task.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-delete btn-icon" onclick="deleteTask(${task.id}, '${escapeHtml(task.title).replace(/'/g, "\\'")}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            
            console.log(`✅ Task list updated with ${tasks.length} tasks`);
        }

        // ==================== CORRECTED FORM SUBMISSION HANDLERS ====================

        async function submitForm(formData, action, successMessage) {
            console.log(`📤 Submitting ${action}:`, formData);
            
            try {
                // Send as JSON as the API expects
                const response = await fetch('api_enhanced.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action, ...formData })
                });
                
                console.log('📤 Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const responseText = await response.text();
                console.log('📥 Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid response format from server');
                }
                
                console.log('📥 Parsed response:', result);
                
                if (result.success) {
                    logSuccess(successMessage);
                    return true;
                } else {
                    throw new Error(result.error || 'Unknown server error');
                }
                
            } catch (error) {
                logError(`Failed to ${action.replace('_', ' ')}`, error);
                return false;
            }
        }

        // ==================== ENHANCED EVENT LISTENERS SETUP ====================
        
        function setupFormListeners() {
            console.log('🔧 Setting up form listeners...');
            
            // Add Employee Form
            const addEmployeeForm = document.getElementById('add-employee-form');
            if (addEmployeeForm) {
                addEmployeeForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    console.log('👤 Adding employee...');
                    
                    const formData = new FormData(this);
                    const data = {
                        name: formData.get('name'),
                        email: formData.get('email'),
                        role: formData.get('role'),
                        type: formData.get('type')
                    };
                    
                    console.log('👤 Employee data to submit:', data);
                    
                    const success = await submitForm(data, 'add_employee', 'Employee added successfully!');
                    if (success) {
                        this.reset();
                        // Immediately refresh data without delay
                        await loadEmployees();
                        await loadEmployeesForDropdown();
                        updateStats();
                    }
                });
                console.log('✅ Employee form listener added');
            }

            // Add Project Form
            const addProjectForm = document.getElementById('add-project-form');
            if (addProjectForm) {
                addProjectForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    console.log('🎯 Adding project...');
                    
                    const formData = new FormData(this);
                    const data = {
                        name: formData.get('name'),
                        description: formData.get('description'),
                        client: formData.get('client'),
                        priority: formData.get('priority'),
                        start_date: formData.get('start_date') || null,
                        end_date: formData.get('end_date') || null
                    };
                    
                    console.log('🎯 Project data to submit:', data);
                    
                    const success = await submitForm(data, 'add_project', 'Project created successfully!');
                    if (success) {
                        this.reset();
                        // Immediately refresh data without delay
                        await loadProjects();
                        await loadProjectsForDropdown();
                        updateStats();
                    }
                });
                console.log('✅ Project form listener added');
            }

            // Add Task Form
            const addTaskForm = document.getElementById('add-task-form');
            if (addTaskForm) {
                addTaskForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    console.log('📋 Adding task...');
                    
                    const formData = new FormData(this);
                    const data = {
                        employee_id: formData.get('employee_id') || null,
                        project_id: formData.get('project_id') || null,
                        title: formData.get('title'),
                        description: formData.get('description'),
                        priority: formData.get('priority'),
                        due_date: formData.get('due_date') || null,
                        estimated_hours: formData.get('estimated_hours') || null
                    };
                    
                    console.log('📋 Task data to submit:', data);
                    
                    const success = await submitForm(data, 'add_task', 'Task assigned successfully!');
                    if (success) {
                        this.reset();
                        // Immediately refresh data without delay
                        await loadTasks();
                        updateStats();
                        // Also refresh employees to update their task counts
                        await loadEmployees();
                    }
                });
                console.log('✅ Task form listener added');
            }
        }

        // ==================== UTILITY FUNCTIONS ====================
        
        function getInitials(name) {
            if (!name) return '??';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showMessage(text, type) {
            const container = document.getElementById('message-container');
            if (!container) {
                console.warn('Message container not found');
                return;
            }
            
            const messageClass = type === 'error' ? 'error' : 'success';
            container.innerHTML = `<div class="message ${messageClass}">${text}</div>`;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        // ==================== ENHANCED STATUS UPDATE FUNCTION ====================

        async function updateEmployeeStatus(employeeId, status) {
            console.log(`🔄 Updating employee ${employeeId} status to ${status}`);
            
            try {
                const response = await fetch('api_enhanced.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_employee_status',
                        employee_id: employeeId,
                        status: status
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    logSuccess('Status updated successfully!');
                    // Immediately refresh the employee list
                    await loadEmployees();
                    updateStats();
                } else {
                    throw new Error(result.error || 'Failed to update status');
                }
                
            } catch (error) {
                logError('Failed to update employee status', error);
            }
        }

        function refreshData() {
            console.log('🔄 Refreshing all data...');
            loadTabData(currentTab);
            logSuccess('Data refreshed!');
        }

        // ==================== API TEST FUNCTION ====================
        
        async function testAPIConnection() {
            console.log('🔌 Testing API connection...');
            
            try {
                // Test the basic API endpoint
                const response = await fetch('api_enhanced.php?action=test');
                const data = await response.json();
                console.log('🔌 API Test Response:', data);
                
                if (data.status === 'success') {
                    logSuccess('API connection successful!');
                    return true;
                } else {
                    throw new Error('API test failed');
                }
            } catch (error) {
                logError('API connection failed', error);
                return false;
            }
        }

        // ==================== EDIT/DELETE FUNCTIONS ====================
        
        // Modal Management
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Click outside modal to close
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        // ==================== EMPLOYEE EDIT/DELETE ====================
        
        async function editEmployee(employeeId) {
            const employee = employees.find(emp => emp.id == employeeId);
            if (!employee) {
                logError('Employee not found');
                return;
            }

            // Populate the edit form
            document.getElementById('edit-employee-id').value = employee.id;
            document.getElementById('edit-employee-name').value = employee.name || '';
            document.getElementById('edit-employee-email').value = employee.email || '';
            document.getElementById('edit-employee-role').value = employee.role || '';
            document.getElementById('edit-employee-type').value = employee.type || 'employee';

            openModal('editEmployeeModal');
        }

        async function updateEmployee() {
            const form = document.getElementById('edit-employee-form');
            const formData = new FormData(form);
            
            const data = {
                employee_id: formData.get('employee_id'),
                name: formData.get('name'),
                email: formData.get('email'),
                role: formData.get('role'),
                type: formData.get('type')
            };

            try {
                const response = await fetch('api_enhanced.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_employee',
                        ...data
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    logSuccess('Employee updated successfully!');
                    closeModal('editEmployeeModal');
                    await loadEmployees();
                    await loadEmployeesForDropdown();
                    updateStats();
                } else {
                    throw new Error(result.error || 'Failed to update employee');
                }
            } catch (error) {
                logError('Failed to update employee', error);
            }
        }

        async function deleteEmployee(employeeId, employeeName) {
            if (!confirm(`Are you sure you want to delete ${employeeName}?\n\nThis will also remove all their task assignments.`)) {
                return;
            }

            try {
                const response = await fetch('api_enhanced.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_employee',
                        employee_id: employeeId
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    logSuccess('Employee deleted successfully!');
                    await loadEmployees();
                    await loadEmployeesForDropdown();
                    await loadTasks(); // Refresh tasks as assignments may have changed
                    updateStats();
                } else {
                    throw new Error(result.error || 'Failed to delete employee');
                }
            } catch (error) {
                logError('Failed to delete employee', error);
            }
        }

        // ==================== PROJECT EDIT/DELETE ====================
        
        async function editProject(projectId) {
            const project = projects.find(proj => proj.id == projectId);
            if (!project) {
                logError('Project not found');
                return;
            }

            // Populate the edit form
            document.getElementById('edit-project-id').value = project.id;
            document.getElementById('edit-project-name').value = project.name || '';
            document.getElementById('edit-project-client').value = project.client || '';
            document.getElementById('edit-project-priority').value = project.priority || 'medium';
            document.getElementById('edit-project-start-date').value = project.start_date || '';
            document.getElementById('edit-project-end-date').value = project.end_date || '';
            document.getElementById('edit-project-description').value = project.description || '';

            openModal('editProjectModal');
        }

        async function updateProject() {
            const form = document.getElementById('edit-project-form');
            const formData = new FormData(form);
            
            const data = {
                project_id: formData.get('project_id'),
                name: formData.get('name'),
                client: formData.get('client'),
                priority: formData.get('priority'),
                start_date: formData.get('start_date') || null,
                end_date: formData.get('end_date') || null,
                description: formData.get('description')
            };

            try {
                const response = await fetch('api_enhanced.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_project',
                        ...data
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    logSuccess('Project updated successfully!');
                    closeModal('editProjectModal');
                    await loadProjects();
                    await loadProjectsForDropdown();
                    updateStats();
                } else {
                    throw new Error(result.error || 'Failed to update project');
                }
            } catch (error) {
                logError('Failed to update project', error);
            }
        }

        async function deleteProject(projectId, projectName) {
            if (!confirm(`Are you sure you want to delete "${projectName}"?\n\nThis will also delete all tasks associated with this project.`)) {
                return;
            }

            try {
                const response = await fetch('api_enhanced.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_project',
                        project_id: projectId
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    logSuccess('Project deleted successfully!');
                    await loadProjects();
                    await loadProjectsForDropdown();
                    await loadTasks(); // Refresh tasks as some may have been deleted
                    updateStats();
                } else {
                    throw new Error(result.error || 'Failed to delete project');
                }
            } catch (error) {
                logError('Failed to delete project', error);
            }
        }

        // ==================== TASK EDIT/DELETE ====================
        
        async function editTask(taskId) {
            const task = tasks.find(t => t.id == taskId);
            if (!task) {
                logError('Task not found');
                return;
            }

            // Load dropdowns first
            await loadEmployeesForEditDropdown();
            await loadProjectsForEditDropdown();

            // Populate the edit form
            document.getElementById('edit-task-id').value = task.id;
            document.getElementById('edit-task-title').value = task.title || '';
            document.getElementById('edit-task-employee').value = task.employee_id || '';
            document.getElementById('edit-task-project').value = task.project_id || '';
            document.getElementById('edit-task-priority').value = task.priority || 'medium';
            document.getElementById('edit-task-status').value = task.status || 'todo';
            document.getElementById('edit-task-due-date').value = task.due_date || '';
            document.getElementById('edit-task-estimated-hours').value = task.estimated_hours || '';
            document.getElementById('edit-task-description').value = task.description || '';

            openModal('editTaskModal');
        }

        async function loadEmployeesForEditDropdown() {
            if (employees.length === 0) {
                await loadEmployees();
            }
            
            const select = document.getElementById('edit-task-employee');
            if (select) {
                select.innerHTML = '<option value="">Unassigned</option>' +
                    employees.map(emp => `<option value="${emp.id}">${emp.name} (${emp.role})</option>`).join('');
            }
        }

        async function loadProjectsForEditDropdown() {
            if (projects.length === 0) {
                await loadProjects();
            }
            
            const select = document.getElementById('edit-task-project');
            if (select) {
                select.innerHTML = '<option value="">No Project</option>' +
                    projects.map(proj => `<option value="${proj.id}">${proj.name}</option>`).join('');
            }
        }

        async function updateTask() {
            const form = document.getElementById('edit-task-form');
            const formData = new FormData(form);
            
            const data = {
                task_id: formData.get('task_id'),
                title: formData.get('title'),
                employee_id: formData.get('employee_id') || null,
                project_id: formData.get('project_id') || null,
                priority: formData.get('priority'),
                status: formData.get('status'),
                due_date: formData.get('due_date') || null,
                estimated_hours: formData.get('estimated_hours') || null,
                description: formData.get('description')
            };

            try {
                const response = await fetch('api_enhanced.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_task',
                        ...data
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    logSuccess('Task updated successfully!');
                    closeModal('editTaskModal');
                    await loadTasks();
                    await loadEmployees(); // Update employee task counts
                    updateStats();
                } else {
                    throw new Error(result.error || 'Failed to update task');
                }
            } catch (error) {
                logError('Failed to update task', error);
            }
        }

        async function deleteTask(taskId, taskTitle) {
            if (!confirm(`Are you sure you want to delete "${taskTitle}"?`)) {
                return;
            }

            try {
                const response = await fetch('api_enhanced.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_task',
                        task_id: taskId
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    logSuccess('Task deleted successfully!');
                    await loadTasks();
                    await loadEmployees(); // Update employee task counts
                    updateStats();
                } else {
                    throw new Error(result.error || 'Failed to delete task');
                }
            } catch (error) {
                logError('Failed to delete task', error);
            }
        }

        // ==================== PLACEHOLDER FUNCTIONS ====================
        
        function bulkUpdateStatus() {
            const selectedEmployees = Array.from(document.querySelectorAll('.employee-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedEmployees.length === 0) {
                alert('Please select at least one employee');
                return;
            }
            
            const newStatus = document.getElementById('bulk-status').value;
            
            if (confirm(`Update status to "${newStatus}" for ${selectedEmployees.length} employee(s)?`)) {
                selectedEmployees.forEach(empId => {
                    updateEmployeeStatus(empId, newStatus);
                });
            }
        }

        function selectAllEmployees() {
            const checkboxes = document.querySelectorAll('.employee-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
        }

        function exportTeamData() {
            alert('📊 Exporting team data as CSV...\n\nThis would generate a comprehensive report with:\n• Employee details\n• Current status\n• Task assignments\n• Performance metrics');
        }

        function exportProjectData() {
            alert('📊 Exporting project data as CSV...\n\nThis would include:\n• Project details\n• Progress status\n• Team assignments\n• Timeline information');
        }

        function generateFullReport() {
            alert('📊 Generating comprehensive analytics report...\n\nThis would include:\n• Team productivity metrics\n• Project performance\n• Individual efficiency scores\n• Trend analysis\n• Capacity utilization');
        }

        function filterTasks(filter) {
            console.log('🔍 Filtering tasks by:', filter);
            // Implementation for task filtering
        }

        function viewProject(projectId) {
            alert(`📋 Opening project details for ID: ${projectId}\n\nThis would show:\n• Project overview\n• Task breakdown\n• Team assignments\n• Timeline & milestones`);
        }

        async function loadAnalytics() {
            const container = document.getElementById('analytics-content');
            if (!container) return;
            
            try {
                const response = await fetch(`api_enhanced.php?action=productivity_insights&_t=${Date.now()}`);
                const insights = await response.json();
                
                container.innerHTML = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <div class="section-card">
                            <h3>📊 Team Overview</h3>
                            <p>Active Employees: ${insights.team_overview?.active_employees || 0}</p>
                            <p>Total Tasks: ${insights.team_overview?.total_tasks || 0}</p>
                            <p>Completed: ${insights.team_overview?.completed_tasks || 0}</p>
                            <p>Overdue: ${insights.team_overview?.overdue_tasks || 0}</p>
                        </div>
                        
                        <div class="section-card">
                            <h3>🏆 Top Performers</h3>
                            ${(insights.top_performers || []).slice(0, 3).map(performer => `
                                <p><strong>${performer.name}</strong> - ${performer.completed_tasks} tasks (${performer.efficiency_score}% efficiency)</p>
                            `).join('')}
                        </div>
                        
                        <div class="section-card">
                            <h3>📈 Project Health</h3>
                            ${(insights.project_health || []).slice(0, 3).map(project => `
                                <p><strong>${project.name}</strong> - ${project.progress_percentage}% complete</p>
                            `).join('')}
                        </div>
                    </div>
                `;
                
            } catch (error) {
                logError('Failed to load analytics', error);
                container.innerHTML = '<p style="color: var(--gray-500); text-align: center; padding: 2rem;">Error loading analytics data.</p>';
            }
        }

        // ==================== AUTO-REFRESH FOR TAB VISIBILITY ====================
        
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Stop refreshing when tab is hidden
                clearInterval(refreshInterval);
            } else {
                // Refresh data when tab becomes visible
                refreshData();
                // Set up periodic refresh every 30 seconds
                refreshInterval = setInterval(refreshData, 30000);
            }
        });

        // ==================== INITIALIZATION ====================
        
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('🦊 Foxhole Project Manager initializing...');
            
            // Test API connection first
            const apiWorking = await testAPIConnection();
            
            if (!apiWorking) {
                logError('API connection failed - check api_enhanced.php file exists and is working');
                return;
            }
            
            // Setup form listeners
            setupFormListeners();
            
            // Load initial data
            console.log('🔄 Loading initial data...');
            await loadTabData(currentTab);
            
            // Set up periodic refresh every 30 seconds when tab is active
            refreshInterval = setInterval(refreshData, 30000);
            
            console.log('✅ Foxhole Project Manager initialized successfully!');
        });

        // Global error handler
        window.addEventListener('error', function(e) {
            logError('JavaScript Error', e.error);
        });

        window.addEventListener('unhandledrejection', function(e) {
            logError('Unhandled Promise Rejection', e.reason);
        });
    </script>
</body>
</html>