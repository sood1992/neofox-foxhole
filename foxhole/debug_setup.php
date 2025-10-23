<?php
// debug_setup.php - Standalone setup with error checking
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug Setup Started -->\n";

// Test basic PHP functionality
if (!function_exists('curl_init')) {
    die('CURL is not available. Please contact your hosting provider.');
}

if (!extension_loaded('pdo_mysql')) {
    die('PDO MySQL extension is not available. Please contact your hosting provider.');
}

// Database connection test
$host = 'localhost';
$username = 'sunburni_foxhole';
$password = 'sunburni_foxhole';
$database = 'sunburni_foxhole';

$message = '';
$messageType = '';
$dbConnected = false;
$currentStep = 1;

// Test database connection
try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database}",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbConnected = true;
    $currentStep = 2;
} catch(PDOException $e) {
    $message = "Database connection failed: " . $e->getMessage();
    $messageType = 'error';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dbConnected) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'setup_database':
            try {
                // Create integrations table
                $sql = "
                CREATE TABLE IF NOT EXISTS integrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    service VARCHAR(50) UNIQUE NOT NULL,
                    api_key VARCHAR(500) NOT NULL,
                    webhook_url VARCHAR(200) NULL,
                    settings JSON NULL,
                    last_sync TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                
                CREATE TABLE IF NOT EXISTS sync_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    service VARCHAR(50) NOT NULL,
                    sync_type VARCHAR(50) NOT NULL,
                    status ENUM('success', 'error', 'partial') NOT NULL,
                    records_synced INT DEFAULT 0,
                    error_message TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                ";
                
                $pdo->exec($sql);
                
                // Try to add new columns to existing tables
                try {
                    $pdo->exec("ALTER TABLE employees ADD COLUMN IF NOT EXISTS clickup_user_id VARCHAR(50) NULL");
                    $pdo->exec("ALTER TABLE employees ADD COLUMN IF NOT EXISTS notion_user_id VARCHAR(50) NULL");
                    $pdo->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS clickup_project_id VARCHAR(50) NULL");
                    $pdo->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS notion_page_id VARCHAR(50) NULL");
                    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS clickup_task_id VARCHAR(50) NULL");
                } catch (Exception $e) {
                    // Ignore if columns already exist
                }
                
                $message = "✅ Database setup completed successfully!";
                $messageType = 'success';
                $currentStep = 3;
            } catch (Exception $e) {
                $message = "❌ Database setup failed: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'save_clickup':
            try {
                $apiKey = $_POST['clickup_api_key'] ?? '';
                $teamId = $_POST['clickup_team_id'] ?? '';
                
                if ($apiKey && $teamId) {
                    $stmt = $pdo->prepare("
                        INSERT INTO integrations (service, api_key, settings, created_at) 
                        VALUES ('clickup', ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                        api_key = VALUES(api_key), 
                        settings = VALUES(settings)
                    ");
                    $settings = json_encode(['team_id' => $teamId]);
                    $stmt->execute([$apiKey, $settings]);
                    
                    $message = "✅ ClickUp configuration saved successfully!";
                    $messageType = 'success';
                    $currentStep = 4;
                } else {
                    $message = "❌ Please provide both API key and Team ID.";
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = "❌ Failed to save ClickUp configuration: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'save_notion':
            try {
                $apiKey = $_POST['notion_api_key'] ?? '';
                $databaseId = $_POST['notion_database_id'] ?? '';
                
                if ($apiKey) {
                    $settings = [];
                    if ($databaseId) {
                        $settings['database_id'] = $databaseId;
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO integrations (service, api_key, settings, created_at) 
                        VALUES ('notion', ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                        api_key = VALUES(api_key), 
                        settings = VALUES(settings)
                    ");
                    $stmt->execute([$apiKey, json_encode($settings)]);
                    
                    $message = "✅ Notion configuration saved successfully!";
                    $messageType = 'success';
                    $currentStep = 4;
                } else {
                    $message = "❌ Please provide API key.";
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = "❌ Failed to save Notion configuration: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'test_clickup':
            try {
                $stmt = $pdo->prepare("SELECT api_key, settings FROM integrations WHERE service = 'clickup'");
                $stmt->execute();
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$config) {
                    throw new Exception('ClickUp not configured');
                }
                
                $apiKey = $config['api_key'];
                $url = 'https://api.clickup.com/api/v2/user';
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: ' . $apiKey,
                        'Content-Type: application/json'
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_VERBOSE => true
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $data = json_decode($response, true);
                    $username = $data['user']['username'] ?? 'Unknown';
                    $message = "✅ ClickUp connection successful! Connected as: {$username}";
                    $messageType = 'success';
                } else {
                    $message = "❌ ClickUp connection failed. HTTP Code: {$httpCode}";
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = "❌ ClickUp test failed: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'test_notion':
            try {
                $stmt = $pdo->prepare("SELECT api_key FROM integrations WHERE service = 'notion'");
                $stmt->execute();
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$config) {
                    throw new Exception('Notion not configured');
                }
                
                $apiKey = $config['api_key'];
                $url = 'https://api.notion.com/v1/search';
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json',
                        'Notion-Version: 2022-06-28'
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode([
                        'filter' => ['property' => 'object', 'value' => 'database']
                    ])
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $data = json_decode($response, true);
                    $count = count($data['results'] ?? []);
                    $message = "✅ Notion connection successful! Found {$count} databases.";
                    $messageType = 'success';
                } else {
                    $message = "❌ Notion connection failed. HTTP Code: {$httpCode}";
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = "❌ Notion test failed: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
    }
}

// Get current configurations
$clickupConfig = null;
$notionConfig = null;

if ($dbConnected) {
    try {
        $stmt = $pdo->prepare("SELECT api_key, settings FROM integrations WHERE service = 'clickup'");
        $stmt->execute();
        $clickupConfig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT api_key, settings FROM integrations WHERE service = 'notion'");
        $stmt->execute();
        $notionConfig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($clickupConfig || $notionConfig) {
            $currentStep = max($currentStep, 4);
        }
    } catch (Exception $e) {
        // Tables might not exist yet
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole Setup - Debug Mode</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4F46E5, #6366F1);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }

        .logo {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .subtitle {
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 40px;
        }

        .progress {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 10px;
            position: relative;
        }

        .step.active {
            background: #4F46E5;
            color: white;
        }

        .step.completed {
            background: #10B981;
            color: white;
        }

        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 20px;
            height: 2px;
            background: #e2e8f0;
            transform: translateY(-50%);
        }

        .step:last-child::after {
            display: none;
        }

        .message {
            margin-bottom: 30px;
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: 600;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .section {
            margin-bottom: 40px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            background: #f8fafc;
        }

        .section h2 {
            margin-bottom: 20px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #4F46E5;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background: #4F46E5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #10B981;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-ok {
            background: #d1fae5;
            color: #065f46;
        }

        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .debug-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-family: monospace;
            font-size: 13px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🦊 Foxhole Setup</div>
            <div class="subtitle">Debug Mode - API Configuration</div>
        </div>

        <div class="content">
            <!-- Progress Indicator -->
            <div class="progress">
                <div class="step <?= $currentStep >= 1 ? ($dbConnected ? 'completed' : 'active') : '' ?>">1</div>
                <div class="step <?= $currentStep >= 2 ? ($currentStep > 2 ? 'completed' : 'active') : '' ?>">2</div>
                <div class="step <?= $currentStep >= 3 ? ($currentStep > 3 ? 'completed' : 'active') : '' ?>">3</div>
                <div class="step <?= $currentStep >= 4 ? 'active' : '' ?>">4</div>
            </div>

            <!-- Debug Info -->
            <div class="debug-info">
                <strong>System Status:</strong><br>
                PHP Version: <?= PHP_VERSION ?><br>
                CURL: <?= function_exists('curl_init') ? '✅ Available' : '❌ Missing' ?><br>
                PDO MySQL: <?= extension_loaded('pdo_mysql') ? '✅ Available' : '❌ Missing' ?><br>
                Database: <?= $dbConnected ? '✅ Connected' : '❌ Failed' ?><br>
                Current Step: <?= $currentStep ?><br>
                Time: <?= date('Y-m-d H:i:s') ?>
            </div>

            <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= $message ?>
            </div>
            <?php endif; ?>

            <!-- Step 1: Database Connection -->
            <div class="section">
                <h2>
                    <?= $dbConnected ? '✅' : '❌' ?> Step 1: Database Connection
                </h2>
                
                <?php if ($dbConnected): ?>
                    <p style="color: #065f46; font-weight: 600;">Database connection successful!</p>
                <?php else: ?>
                    <p style="color: #991b1b; font-weight: 600;">
                        Database connection failed. Please check your database credentials in the code.
                    </p>
                    <p style="margin-top: 10px; font-size: 14px; color: #6b7280;">
                        Current settings: Host: <?= $host ?>, Database: <?= $database ?>, User: <?= $username ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($dbConnected): ?>
            <!-- Step 2: Database Setup -->
            <div class="section">
                <h2>🛠️ Step 2: Database Setup</h2>
                <p style="margin-bottom: 20px; color: #6b7280;">
                    Set up the required database tables for API integrations.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="setup_database">
                    <button type="submit" class="btn btn-success btn-block">
                        Setup Database Tables
                    </button>
                </form>
            </div>

            <!-- Step 3: API Configuration -->
            <div class="grid">
                <!-- ClickUp Configuration -->
                <div class="section">
                    <h2>
                        🖱️ ClickUp API
                        <span class="status-badge <?= $clickupConfig ? 'status-ok' : 'status-error' ?>">
                            <?= $clickupConfig ? 'Configured' : 'Not Set' ?>
                        </span>
                    </h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="save_clickup">
                        
                        <div class="form-group">
                            <label class="form-label">API Key</label>
                            <input type="password" name="clickup_api_key" class="form-input" 
                                   placeholder="pk_your_api_key_here"
                                   value="<?= $clickupConfig ? '••••••••••••••••' : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Team ID</label>
                            <input type="text" name="clickup_team_id" class="form-input" 
                                   placeholder="123456789"
                                   value="<?= $clickupConfig ? json_decode($clickupConfig['settings'], true)['team_id'] ?? '' : '' ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            Save ClickUp Config
                        </button>
                    </form>
                    
                    <?php if ($clickupConfig): ?>
                    <form method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="action" value="test_clickup">
                        <button type="submit" class="btn btn-secondary btn-block">
                            Test ClickUp Connection
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Notion Configuration -->
                <div class="section">
                    <h2>
                        📝 Notion API
                        <span class="status-badge <?= $notionConfig ? 'status-ok' : 'status-error' ?>">
                            <?= $notionConfig ? 'Configured' : 'Not Set' ?>
                        </span>
                    </h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="save_notion">
                        
                        <div class="form-group">
                            <label class="form-label">API Key</label>
                            <input type="password" name="notion_api_key" class="form-input" 
                                   placeholder="secret_your_api_key_here"
                                   value="<?= $notionConfig ? '••••••••••••••••' : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Database ID (Optional)</label>
                            <input type="text" name="notion_database_id" class="form-input" 
                                   placeholder="a1b2c3d4-e5f6-7890..."
                                   value="<?= $notionConfig ? json_decode($notionConfig['settings'], true)['database_id'] ?? '' : '' ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            Save Notion Config
                        </button>
                    </form>
                    
                    <?php if ($notionConfig): ?>
                    <form method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="action" value="test_notion">
                        <button type="submit" class="btn btn-secondary btn-block">
                            Test Notion Connection
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Step 4: Next Steps -->
            <?php if ($clickupConfig || $notionConfig): ?>
            <div class="section">
                <h2>🚀 Step 4: Ready to Go!</h2>
                <p style="margin-bottom: 20px; color: #6b7280;">
                    Your APIs are configured! Now you can proceed to set up the full integration system.
                </p>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="dashboard.php" class="btn btn-primary">
                        Go to Dashboard
                    </a>
                    <a href="#" onclick="alert('Upload the enhanced files from Claude to enable full sync functionality!')" class="btn btn-secondary">
                        Enable Full Sync (Upload Files)
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Instructions -->
            <div class="section">
                <h2>📋 Quick Setup Guide</h2>
                
                <h3 style="margin: 20px 0 10px 0;">ClickUp API Key:</h3>
                <ol style="margin-left: 20px; color: #6b7280;">
                    <li>Go to ClickUp → Settings → Apps</li>
                    <li>Click "Generate" for a personal API token</li>
                    <li>Find Team ID in URL: app.clickup.com/[TEAM_ID]/</li>
                </ol>
                
                <h3 style="margin: 20px 0 10px 0;">Notion API Key:</h3>
                <ol style="margin-left: 20px; color: #6b7280;">
                    <li>Go to notion.so/my-integrations</li>
                    <li>Create a "New Integration"</li>
                    <li>Copy the Internal Integration Token</li>
                    <li>Share your project database with the integration</li>
                </ol>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>