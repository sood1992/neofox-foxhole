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
    <title>Foxhole - Team Management Portal</title>
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
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        .login-container {
            width: 100%;
            max-width: 1200px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 1rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            box-shadow: var(--shadow-xl);
        }

        .logo-text {
            font-size: 48px;
            font-weight: 900;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .tagline {
            font-size: 20px;
            opacity: 0.9;
            font-weight: 500;
        }

        .login-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            box-shadow: var(--shadow-xl);
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 40px rgba(0,0,0,0.2);
        }

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .card-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            box-shadow: var(--shadow-md);
        }

        .card-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .card-subtitle {
            font-size: 16px;
            color: var(--gray-600);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn {
            width: 100%;
            padding: 16px 24px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
            border: 2px solid var(--gray-200);
            margin-top: 1rem;
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .error-message {
            display: none;
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .success-message {
            display: none;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        @media (max-width: 768px) {
            .login-options {
                grid-template-columns: 1fr;
            }
            
            .logo-text {
                font-size: 36px;
            }
        }

        /* Loading animation */
        .loading {
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

        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            text-align: center;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--gray-200);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading" id="loadingScreen">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Logging you in...</p>
        </div>
    </div>

    <div class="login-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="logo-text">Foxhole</div>
            </div>
            <p class="tagline">Team Management Portal - Neofox Media</p>
        </div>

        <!-- Login Options -->
        <div class="login-options">
            <!-- Admin Login -->
            <div class="login-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h2 class="card-title">Fox Admin Panel</h2>
                    <p class="card-subtitle"></p>
                </div>
                
                <form id="adminLoginForm" onsubmit="handleAdminLogin(event)">
                    <div class="success-message" id="adminSuccess"></div>
                    <div class="error-message" id="adminError"></div>
                    
                    <div class="form-group">
                        <label class="form-label">Admin Password</label>
                        <input type="password" class="form-input" id="adminPassword" 
                               placeholder="Enter admin password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-lock"></i>
                        Access Admin Panel
                    </button>
                </form>
            </div>

            <!-- Project Manager Login -->
            <div class="login-card">
                <div class="card-header">
                    <div class="card-icon" style="background: linear-gradient(135deg, var(--warning), #FBBF24);">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h2 class="card-title">Project Manager</h2>
                    <p class="card-subtitle">Team & Project Management</p>
                </div>
                
                <form id="pmLoginForm" onsubmit="handlePMLogin(event)">
                    <div class="success-message" id="pmSuccess"></div>
                    <div class="error-message" id="pmError"></div>
                    
                    <div class="form-group">
                        <label class="form-label">Manager Password</label>
                        <input type="password" class="form-input" id="pmPassword" 
                               placeholder="Enter manager password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--warning), #FBBF24);">
                        <i class="fas fa-unlock"></i>
                        Access Manager Panel
                    </button>
                </form>
            </div>

            <!-- Employee Login -->
            <div class="login-card">
                <div class="card-header">
                    <div class="card-icon" style="background: linear-gradient(135deg, var(--success), #34D399);">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="card-title">Team Member</h2>
                    <p class="card-subtitle">View your tasks & updates</p>
                </div>
                
                <form id="employeeLoginForm" onsubmit="handleEmployeeLogin(event)">
                    <div class="success-message" id="employeeSuccess"></div>
                    <div class="error-message" id="employeeError"></div>
                    
                    <div class="form-group">
                        <label class="form-label">Your First Name</label>
                        <input type="text" class="form-input" id="employeeName" 
                               placeholder="Enter your first name" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--success), #34D399);">
                        <i class="fas fa-sign-in-alt"></i>
                        View My Dashboard
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showLoading() {
            document.getElementById('loadingScreen').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingScreen').style.display = 'none';
        }

        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            const successElement = document.getElementById(elementId.replace('Error', 'Success'));
            
            // Hide success message
            successElement.style.display = 'none';
            
            // Show error message
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            
            setTimeout(() => {
                errorElement.style.display = 'none';
            }, 5000);
        }

        function showSuccess(elementId, message) {
            const successElement = document.getElementById(elementId);
            const errorElement = document.getElementById(elementId.replace('Success', 'Error'));
            
            // Hide error message
            errorElement.style.display = 'none';
            
            // Show success message
            successElement.textContent = message;
            successElement.style.display = 'block';
        }

        async function handleAdminLogin(event) {
            event.preventDefault();
            const password = document.getElementById('adminPassword').value;
            
            showLoading();
            
            try {
                console.log('Sending admin login request...');
                
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=admin_login&password=${encodeURIComponent(password)}`
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);
                
                const text = await response.text();
                console.log('Raw response:', text);
                
                try {
                    const result = JSON.parse(text);
                    console.log('Parsed result:', result);
                    
                    if (result.success) {
                        showSuccess('adminSuccess', '✅ Login successful! Redirecting...');
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 1000);
                    } else {
                        hideLoading();
                        showError('adminError', result.error || 'Login failed');
                        document.getElementById('adminPassword').value = '';
                    }
                } catch (parseError) {
                    hideLoading();
                    console.error('JSON parse error:', parseError);
                    showError('adminError', 'Server error. Please try again.');
                }
                
            } catch (error) {
                hideLoading();
                console.error('Network error:', error);
                showError('adminError', 'Connection error. Please try again.');
            }
        }

        async function handlePMLogin(event) {
            event.preventDefault();
            const password = document.getElementById('pmPassword').value;
            
            showLoading();
            
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=pm_login&password=${encodeURIComponent(password)}`
                });
                
                const text = await response.text();
                const result = JSON.parse(text);
                
                if (result.success) {
                    showSuccess('pmSuccess', '✅ Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                } else {
                    hideLoading();
                    showError('pmError', result.error || 'Login failed');
                    document.getElementById('pmPassword').value = '';
                }
            } catch (error) {
                hideLoading();
                console.error('Login error:', error);
                showError('pmError', 'Connection error. Please try again.');
            }
        }

        async function handleEmployeeLogin(event) {
            event.preventDefault();
            const name = document.getElementById('employeeName').value.trim();
            
            if (name.length < 2) {
                showError('employeeError', 'Please enter a valid name.');
                return;
            }
            
            showLoading();
            
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=employee_login&name=${encodeURIComponent(name)}`
                });
                
                const text = await response.text();
                const result = JSON.parse(text);
                
                if (result.success) {
                    showSuccess('employeeSuccess', '✅ Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                } else {
                    hideLoading();
                    showError('employeeError', result.error || 'Employee not found');
                    document.getElementById('employeeName').value = '';
                }
            } catch (error) {
                hideLoading();
                console.error('Login error:', error);
                showError('employeeError', 'Connection error. Please try again.');
            }
        }

        // Auto-focus first input
        window.addEventListener('load', () => {
            document.querySelector('.form-input').focus();
        });
    </script>
</body>
</html>