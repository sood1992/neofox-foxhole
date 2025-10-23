<?php
// setup.php - API Setup and Configuration Interface
require_once 'config-2.php';
require_once 'Clickup_Notion.php';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $config = new APIConfig();
    
    switch ($action) {
        case 'setup_database':
            if (setupDatabase()) {
                $message = "✅ Database setup completed successfully!";
                $messageType = 'success';
            } else {
                $message = "❌ Database setup failed. Check error logs.";
                $messageType = 'error';
            }
            break;
            
        case 'save_clickup':
            $apiKey = $_POST['clickup_api_key'] ?? '';
            $teamId = $_POST['clickup_team_id'] ?? '';
            
            if ($apiKey && $teamId) {
                $success = $config->setAPIKey('clickup', $apiKey, ['team_id' => $teamId]);
                if ($success) {
                    $message = "✅ ClickUp configuration saved successfully!";
                    $messageType = 'success';
                } else {
                    $message = "❌ Failed to save ClickUp configuration.";
                    $messageType = 'error';
                }
            } else {
                $message = "❌ Please provide both API key and Team ID.";
                $messageType = 'error';
            }
            break;
            
        case 'save_notion':
            $apiKey = $_POST['notion_api_key'] ?? '';
            $databaseId = $_POST['notion_database_id'] ?? '';
            
            if ($apiKey) {
                $settings = [];
                if ($databaseId) {
                    $settings['database_id'] = $databaseId;
                }
                $success = $config->setAPIKey('notion', $apiKey, $settings);
                if ($success) {
                    $message = "✅ Notion configuration saved successfully!";
                    $messageType = 'success';
                } else {
                    $message = "❌ Failed to save Notion configuration.";
                    $messageType = 'error';
                }
            } else {
                $message = "❌ Please provide API key.";
                $messageType = 'error';
            }
            break;
            
        case 'test_clickup':
            try {
                $clickup = new ClickUpIntegration();
                $user = $clickup->getAuthenticatedUser();
                $teams = $clickup->getTeams();
                $message = "✅ ClickUp connection successful! Connected as: " . $user['user']['username'];
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "❌ ClickUp connection failed: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'test_notion':
            try {
                $notion = new NotionIntegration();
                $search = $notion->search('', ['property' => 'object', 'value' => 'database']);
                $message = "✅ Notion connection successful! Found " . count($search['results']) . " databases.";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "❌ Notion connection failed: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'sync_data':
            try {
                $syncManager = new SyncManager();
                $clickupConfig = $config->getAPIKey('clickup');
                $notionConfig = $config->getAPIKey('notion');
                
                $teamId = $clickupConfig['settings']['team_id'] ?? null;
                $databaseId = $notionConfig['settings']['database_id'] ?? null;
                
                $results = $syncManager->syncAll($teamId, $databaseId);
                
                $successCount = 0;
                $totalSynced = 0;
                $errors = [];
                
                foreach ($results as $service => $result) {
                    if ($result['success']) {
                        $successCount++;
                        if (isset($result['members_synced'])) $totalSynced += $result['members_synced'];
                        if (isset($result['projects_synced'])) $totalSynced += $result['projects_synced'];
                        if (isset($result['tasks_synced'])) $totalSynced += $result['tasks_synced'];
                    } else {
                        $errors[] = "$service: " . $result['error'];
                    }
                }
                
                if ($successCount > 0) {
                    $message = "✅ Sync completed! Synced $totalSynced records from $successCount service(s).";
                    $messageType = 'success';
                    if (!empty($errors)) {
                        $message .= " Errors: " . implode(', ', $errors);
                    }
                } else {
                    $message = "❌ Sync failed: " . implode(', ', $errors);
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = "❌ Sync failed: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
    }
}

// Get current configuration
$config = new APIConfig();
$clickupConfig = $config->getAPIKey('clickup');
$notionConfig = $config->getAPIKey('notion');

// Get sync status
$syncManager = new SyncManager();
$syncHistory = $syncManager->getSyncStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole Setup - API Configuration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #6366F1;
            --secondary: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --success: #10B981;
            
            --dark: #0F172A;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-900: #0F172A;
            
            --bg-primary: #FAFAFB;
            --bg-secondary: #FFFFFF;
            
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-lg: 16px;
            --radius-md: 12px;
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
            text-align: center;
            margin-bottom: 3rem;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .subtitle {
            font-size: 18px;
            color: var(--gray-600);
        }

        .setup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .setup-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .clickup-icon {
            background: linear-gradient(135deg, #7B68EE, #9370DB);
        }

        .notion-icon {
            background: linear-gradient(135deg, #000000, #333333);
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
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

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
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

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-configured {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-not-configured {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .message {
            margin-bottom: 2rem;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .sync-section {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .sync-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 2rem;
        }

        .sync-history {
            max-height: 300px;
            overflow-y: auto;
        }

        .sync-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .sync-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sync-service {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }

        .sync-details {
            font-size: 14px;
            color: var(--gray-600);
        }

        .sync-time {
            font-size: 12px;
            color: var(--gray-500);
        }

        .instructions {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
        }

        .instructions h3 {
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .instructions ol {
            margin-left: 1.5rem;
        }

        .instructions li {
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        .instructions code {
            background: var(--gray-100);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .setup-grid {
                grid-template-columns: 1fr;
            }
            
            .sync-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <span>Foxhole Setup</span>
            </div>
            <p class="subtitle">Configure your ClickUp and Notion integrations</p>
        </div>

        <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <!-- Database Setup -->
        <div class="setup-card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div class="card-icon" style="background: linear-gradient(135deg, var(--secondary), #34D399);">
                    <i class="fas fa-database"></i>
                </div>
                <h2 class="card-title">Database Setup</h2>
            </div>
            
            <p style="margin-bottom: 1rem; color: var(--gray-600);">
                First, set up the database tables required for API integrations.
            </p>
            
            <form method="POST">
                <input type="hidden" name="action" value="setup_database">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-cog"></i>
                    Setup Database Tables
                </button>
            </form>
        </div>

        <!-- API Configuration -->
        <div class="setup-grid">
            <!-- ClickUp Configuration -->
            <div class="setup-card">
                <div class="card-header">
                    <div class="card-icon clickup-icon">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <div>
                        <h2 class="card-title">ClickUp</h2>
                        <div class="status-badge <?= $clickupConfig ? 'status-configured' : 'status-not-configured' ?>">
                            <i class="fas fa-<?= $clickupConfig ? 'check' : 'times' ?>"></i>
                            <?= $clickupConfig ? 'Configured' : 'Not Configured' ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="save_clickup">
                    
                    <div class="form-group">
                        <label class="form-label">API Key</label>
                        <input type="password" name="clickup_api_key" class="form-input" 
                               value="<?= $clickupConfig ? '••••••••••••••••' : '' ?>" 
                               placeholder="pk_your_clickup_api_key">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Team ID</label>
                        <input type="text" name="clickup_team_id" class="form-input" 
                               value="<?= $clickupConfig['settings']['team_id'] ?? '' ?>" 
                               placeholder="123456789">
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i>
                            Save
                        </button>
                    </div>
                </form>
                
                <?php if ($clickupConfig): ?>
                <form method="POST" style="margin-top: 8px;">
                    <input type="hidden" name="action" value="test_clickup">
                    <button type="submit" class="btn btn-secondary btn-block">
                        <i class="fas fa-plug"></i>
                        Test Connection
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Notion Configuration -->
            <div class="setup-card">
                <div class="card-header">
                    <div class="card-icon notion-icon">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <div>
                        <h2 class="card-title">Notion</h2>
                        <div class="status-badge <?= $notionConfig ? 'status-configured' : 'status-not-configured' ?>">
                            <i class="fas fa-<?= $notionConfig ? 'check' : 'times' ?>"></i>
                            <?= $notionConfig ? 'Configured' : 'Not Configured' ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="save_notion">
                    
                    <div class="form-group">
                        <label class="form-label">API Key</label>
                        <input type="password" name="notion_api_key" class="form-input" 
                               value="<?= $notionConfig ? '••••••••••••••••' : '' ?>" 
                               placeholder="secret_your_notion_api_key">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Project Database ID (Optional)</label>
                        <input type="text" name="notion_database_id" class="form-input" 
                               value="<?= $notionConfig['settings']['database_id'] ?? '' ?>" 
                               placeholder="a1b2c3d4-e5f6-7890-abcd-ef1234567890">
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i>
                            Save
                        </button>
                    </div>
                </form>
                
                <?php if ($notionConfig): ?>
                <form method="POST" style="margin-top: 8px;">
                    <input type="hidden" name="action" value="test_notion">
                    <button type="submit" class="btn btn-secondary btn-block">
                        <i class="fas fa-plug"></i>
                        Test Connection
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sync Data -->
        <div class="sync-section">
            <div class="card-header" style="border-bottom: 1px solid var(--gray-200); margin-bottom: 1.5rem;">
                <div class="card-icon" style="background: linear-gradient(135deg, var(--warning), #FBBF24);">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h2 class="card-title">Data Synchronization</h2>
            </div>
            
            <div class="sync-actions">
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="action" value="sync_data">
                    <button type="submit" class="btn btn-warning btn-block" 
                            <?= (!$clickupConfig && !$notionConfig) ? 'disabled' : '' ?>>
                        <i class="fas fa-sync-alt"></i>
                        Sync All Data
                    </button>
                </form>
                
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i>
                    Go to Dashboard
                </a>
            </div>
            
            <?php if (!empty($syncHistory)): ?>
            <h3 style="margin-bottom: 1rem;">Recent Sync History</h3>
            <div class="sync-history">
                <?php foreach ($syncHistory as $sync): ?>
                <div class="sync-item">
                    <div class="sync-info">
                        <div class="sync-service"><?= strtoupper($sync['service']) ?></div>
                        <div class="sync-details">
                            <?= ucfirst($sync['sync_type']) ?> - 
                            <?= $sync['records_synced'] ?> records - 
                            <span class="status-badge <?= $sync['status'] === 'success' ? 'status-configured' : 'status-not-configured' ?>">
                                <?= ucfirst($sync['status']) ?>
                            </span>
                        </div>
                        <?php if ($sync['error_message']): ?>
                        <div style="color: var(--danger); font-size: 12px;">
                            <?= htmlspecialchars($sync['error_message']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="sync-time">
                        <?= date('M j, Y g:i A', strtotime($sync['created_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Setup Instructions</h3>
            
            <h4 style="margin-top: 1.5rem; margin-bottom: 0.5rem;">ClickUp API Key:</h4>
            <ol>
                <li>Go to <strong>ClickUp Settings</strong> → <strong>Apps</strong></li>
                <li>Click <strong>Generate</strong> to create a personal API token</li>
                <li>Copy the token and paste it above</li>
                <li>Find your Team ID in the URL: <code>https://app.clickup.com/[TEAM_ID]/</code></li>
            </ol>
            
            <h4 style="margin-top: 1.5rem; margin-bottom: 0.5rem;">Notion API Key:</h4>
            <ol>
                <li>Go to <strong>Notion Integrations</strong> at <code>notion.so/my-integrations</code></li>
                <li>Create a <strong>New Integration</strong></li>
                <li>Copy the <strong>Internal Integration Token</strong></li>
                <li>Share your project database with the integration</li>
                <li>Copy the database ID from the URL</li>
            </ol>
            
            <h4 style="margin-top: 1.5rem; margin-bottom: 0.5rem;">After Setup:</h4>
            <ol>
                <li>Test both connections to ensure they work</li>
                <li>Click <strong>Sync All Data</strong> to import your existing data</li>
                <li>Go to the dashboard to see your live data!</li>
            </ol>
        </div>
    </div>
</body>
</html>