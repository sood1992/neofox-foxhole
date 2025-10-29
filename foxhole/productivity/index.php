<?php
require_once __DIR__ . '/includes/functions.php';

if (is_authenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($email && $password && $role) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND role = :role');
        $stmt->execute(['email' => $email, 'role' => $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            header('Location: dashboard.php');
            exit;
        }

        if ($user) {
            $upgradedUser = maybe_upgrade_demo_password($pdo, $user, $password);
            if ($upgradedUser) {
                unset($upgradedUser['password_hash']);
                $_SESSION['user'] = $upgradedUser;
                header('Location: dashboard.php');
                exit;
            }
        }
    }
    $error = 'Invalid credentials. Double-check your email, password, and role selection.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole Productivity Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="brand">
            <h1>Foxhole Productivity</h1>
            <p>Laser-focused visibility for ADHD-friendly leadership.</p>
        </div>
        <div class="login-panel">
            <div class="tab-switcher" role="tablist">
                <button class="tab-button active" data-role="admin" type="button">Admin</button>
                <button class="tab-button" data-role="manager" type="button">Project Manager</button>
                <button class="tab-button" data-role="employee" type="button">Employee</button>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" class="login-form" autocomplete="off">
                <input type="hidden" name="role" id="role" value="admin">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@neofox.io" required>
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
                <button type="submit" class="primary-btn">Sign in</button>
                <p class="demo-tip">Demo logins:<br>Admin admin@neofox.io • PM pm@neofox.io • Employee employee@neofox.io<br>Password: <?= htmlspecialchars(FOXHOLE_DEMO_PASSWORD) ?></p>
            </form>
        </div>
    </div>
    <script src="assets/js/login.js"></script>
</body>
</html>
