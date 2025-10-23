<?php
// Clear any potential output
ob_start();

// Start session properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any existing sessions
$_SESSION = array();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Foxhole - Team Management Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #17a2b8;
            --primary-dark: #138496;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
            --gray-800: #343a40;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1300px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header Section */
        .login-header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 1rem;
        }

        .brand-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .brand-name {
            font-size: 3rem;
            font-weight: 900;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .login-subtitle {
            font-size: 1.125rem;
            opacity: 0.95;
            font-weight: 500;
        }

        /* Login Cards Grid */
        .login-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color, var(--primary)), var(--card-color-light, var(--primary)));
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.2);
        }

        .login-card.admin {
            --card-color: var(--primary);
            --card-color-light: #5bc0de;
        }

        .login-card.manager {
            --card-color: var(--warning);
            --card-color-light: #ffd54f;
        }

        .login-card.employee {
            --card-color: var(--success);
            --card-color-light: #4caf50;
        }

        .card-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .card-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            background: linear-gradient(135deg, var(--card-color), var(--card-color-light));
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-800);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.9375rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--card-color);
            box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1);
        }

        .form-control::placeholder {
            color: var(--gray-600);
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--card-color), var(--card-color-light));
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: none;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.875rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .brand-name {
                font-size: 2rem;
            }

            .login-grid {
                grid-template-columns: 1fr;
            }

            .login-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Logging you in...</p>
        </div>
    </div>

    <div class="login-wrapper">
        <!-- Header -->
        <div class="login-header">
            <div class="brand">
                <div class="brand-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="brand-name">Foxhole</div>
            </div>
            <p class="login-subtitle">Team Management Portal - Neofox Media</p>
        </div>

        <!-- Login Cards -->
        <div class="login-grid">
            <!-- Admin Card -->
            <div class="login-card admin">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h2 class="card-title">Admin Panel</h2>
                    <p class="card-subtitle">Full system access & oversight</p>
                </div>

                <form id="adminForm" onsubmit="handleLogin(event, 'admin')">
                    <div class="alert alert-error" id="adminError"></div>
                    <div class="alert alert-success" id="adminSuccess"></div>

                    <div class="form-group">
                        <label class="form-label">Admin Password</label>
                        <input type="password"
                               class="form-control"
                               id="adminPassword"
                               placeholder="Enter admin password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-lock"></i>
                        <span>Access Admin Panel</span>
                    </button>
                </form>
            </div>

            <!-- Project Manager Card -->
            <div class="login-card manager">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h2 class="card-title">Project Manager</h2>
                    <p class="card-subtitle">Team & project management</p>
                </div>

                <form id="pmForm" onsubmit="handleLogin(event, 'pm')">
                    <div class="alert alert-error" id="pmError"></div>
                    <div class="alert alert-success" id="pmSuccess"></div>

                    <div class="form-group">
                        <label class="form-label">Manager Password</label>
                        <input type="password"
                               class="form-control"
                               id="pmPassword"
                               placeholder="Enter manager password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-unlock"></i>
                        <span>Access Manager Panel</span>
                    </button>
                </form>
            </div>

            <!-- Employee Card -->
            <div class="login-card employee">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="card-title">Team Member</h2>
                    <p class="card-subtitle">Your tasks & attendance</p>
                </div>

                <form id="employeeForm" onsubmit="handleLogin(event, 'employee')">
                    <div class="alert alert-error" id="employeeError"></div>
                    <div class="alert alert-success" id="employeeSuccess"></div>

                    <div class="form-group">
                        <label class="form-label">Your First Name</label>
                        <input type="text"
                               class="form-control"
                               id="employeeName"
                               placeholder="Enter your first name"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>View My Dashboard</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; 2024 Neofox Media. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Show loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }

        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        // Show error message
        function showError(type, message) {
            const errorEl = document.getElementById(`${type}Error`);
            const successEl = document.getElementById(`${type}Success`);

            successEl.classList.remove('show');
            errorEl.textContent = message;
            errorEl.classList.add('show');

            setTimeout(() => {
                errorEl.classList.remove('show');
            }, 5000);
        }

        // Show success message
        function showSuccess(type, message) {
            const errorEl = document.getElementById(`${type}Error`);
            const successEl = document.getElementById(`${type}Success`);

            errorEl.classList.remove('show');
            successEl.textContent = message;
            successEl.classList.add('show');
        }

        // Handle login
        async function handleLogin(event, type) {
            event.preventDefault();

            let action, credential;

            if (type === 'admin') {
                action = 'admin_login';
                credential = document.getElementById('adminPassword').value;
            } else if (type === 'pm') {
                action = 'pm_login';
                credential = document.getElementById('pmPassword').value;
            } else {
                action = 'employee_login';
                credential = document.getElementById('employeeName').value.trim();

                if (credential.length < 2) {
                    showError('employee', 'Please enter a valid name');
                    return;
                }
            }

            showLoading();

            try {
                const formData = new URLSearchParams();
                formData.append('action', action);

                if (type === 'employee') {
                    formData.append('name', credential);
                } else {
                    formData.append('password', credential);
                }

                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                });

                const text = await response.text();
                const result = JSON.parse(text);

                if (result.success) {
                    showSuccess(type, 'Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                } else {
                    hideLoading();
                    showError(type, result.error || 'Login failed. Please try again.');

                    // Clear password fields
                    if (type !== 'employee') {
                        event.target.querySelector('input[type="password"]').value = '';
                    }
                }
            } catch (error) {
                hideLoading();
                console.error('Login error:', error);
                showError(type, 'Connection error. Please try again.');
            }
        }

        // Auto-focus first input on page load
        window.addEventListener('load', () => {
            document.querySelector('input').focus();
        });
    </script>
</body>
</html>
