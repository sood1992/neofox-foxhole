<?php
/**
 * Reusable Header Component
 * Usage: include 'components/header.php';
 */

$user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';

// Get user initials
$initials = '';
$name_parts = explode(' ', $user_name);
foreach ($name_parts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper($part[0]);
    }
}
if (empty($initials)) {
    $initials = strtoupper(substr($user_name, 0, 2));
}

// Get role display name
$role_display = [
    'admin' => 'Administrator',
    'project_manager' => 'Project Manager',
    'employee' => 'Team Member'
][$user_role] ?? 'User';
?>

<header class="top-header">
    <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="header-search">
            <i class="fas fa-search header-search-icon"></i>
            <input type="text"
                   class="header-search-input"
                   placeholder="Search tasks, projects, employees..."
                   id="globalSearch">
        </div>
    </div>

    <div class="header-right">
        <!-- Notifications -->
        <button class="header-icon-btn" title="Notifications" onclick="showNotifications()">
            <i class="fas fa-bell"></i>
            <span class="badge"></span>
        </button>

        <!-- Messages -->
        <button class="header-icon-btn" title="Messages" onclick="showMessages()">
            <i class="fas fa-envelope"></i>
        </button>

        <!-- Settings -->
        <button class="header-icon-btn" title="Settings" onclick="showSettings()">
            <i class="fas fa-cog"></i>
        </button>

        <!-- User Profile -->
        <div class="header-user" onclick="toggleUserMenu()">
            <div class="header-user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="header-user-info">
                <div class="header-user-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="header-user-role"><?= htmlspecialchars($role_display) ?></div>
            </div>
            <i class="fas fa-chevron-down" style="font-size: 12px; color: #6c757d;"></i>
        </div>
    </div>
</header>

<!-- User Menu Dropdown (Hidden by default) -->
<div class="user-menu-dropdown" id="userMenu" style="display: none;">
    <div class="user-menu-header">
        <div class="header-user-avatar" style="width: 50px; height: 50px; font-size: 1.2rem;"><?= htmlspecialchars($initials) ?></div>
        <div style="margin-left: 12px;">
            <div style="font-weight: 600; color: #212529;"><?= htmlspecialchars($user_name) ?></div>
            <div style="font-size: 0.875rem; color: #6c757d;"><?= htmlspecialchars($role_display) ?></div>
            <?php if ($user_email): ?>
                <div style="font-size: 0.75rem; color: #adb5bd;"><?= htmlspecialchars($user_email) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="user-menu-divider"></div>
    <a href="#" class="user-menu-item">
        <i class="fas fa-user"></i> My Profile
    </a>
    <a href="#" class="user-menu-item">
        <i class="fas fa-cog"></i> Account Settings
    </a>
    <a href="#" class="user-menu-item">
        <i class="fas fa-question-circle"></i> Help & Support
    </a>
    <div class="user-menu-divider"></div>
    <a href="auth.php?action=logout" class="user-menu-item">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<style>
    .user-menu-dropdown {
        position: absolute;
        top: calc(var(--header-height) + 10px);
        right: 20px;
        width: 280px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        animation: dropdownFadeIn 0.2s ease;
    }

    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .user-menu-header {
        padding: 20px;
        display: flex;
        align-items: center;
    }

    .user-menu-divider {
        height: 1px;
        background: #e9ecef;
        margin: 0;
    }

    .user-menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        color: #495057;
        text-decoration: none;
        transition: background 0.15s ease;
        font-size: 0.9375rem;
    }

    .user-menu-item:hover {
        background: #f8f9fa;
    }

    .user-menu-item i {
        width: 20px;
        color: #6c757d;
    }

    .user-menu-item:last-child {
        border-radius: 0 0 12px 12px;
        color: #dc3545;
    }

    .user-menu-item:last-child i {
        color: #dc3545;
    }
</style>

<script>
    // Toggle user menu
    function toggleUserMenu() {
        const menu = document.getElementById('userMenu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }

    // Close user menu when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('userMenu');
        const headerUser = document.querySelector('.header-user');

        if (userMenu && !userMenu.contains(event.target) && !headerUser.contains(event.target)) {
            userMenu.style.display = 'none';
        }
    });

    // Placeholder functions
    function showNotifications() {
        alert('Notifications feature coming soon!');
    }

    function showMessages() {
        alert('Messages feature coming soon!');
    }

    function showSettings() {
        alert('Settings feature coming soon!');
    }

    // Global search
    document.getElementById('globalSearch')?.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const query = this.value;
            if (query.trim()) {
                alert('Search functionality coming soon! Searching for: ' + query);
            }
        }
    });
</script>
