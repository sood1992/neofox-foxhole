<?php
// sync_cron.php - Automated sync script for ClickUp and Notion
// Run this script via cron job every 15-30 minutes to keep data fresh

require_once 'config-2.php';
require_once 'Clickup_Notion.php';

// Log output to both file and console
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$type}] {$message}\n";
    
    // Log to file
    file_put_contents(__DIR__ . '/sync.log', $logEntry, FILE_APPEND | LOCK_EX);
    
    // Output to console (for manual runs)
    echo $logEntry;
}

// Get configuration
$config = new APIConfig();
$clickupConfig = $config->getAPIKey('clickup');
$notionConfig = $config->getAPIKey('notion');

logMessage("Starting automated sync process...");

// Check if any integrations are configured
if (!$clickupConfig && !$notionConfig) {
    logMessage("No integrations configured. Exiting.", 'WARNING');
    exit(0);
}

$totalSynced = 0;
$errors = [];

try {
    // Sync ClickUp data
    if ($clickupConfig) {
        logMessage("Starting ClickUp sync...");
        
        try {
            $clickup = new ClickUpIntegration();
            $teamId = $clickupConfig['settings']['team_id'] ?? null;
            
            if ($teamId) {
                $result = $clickup->syncToDatabase($teamId);
                
                if ($result['success']) {
                    $synced = ($result['members_synced'] ?? 0) + 
                             ($result['projects_synced'] ?? 0) + 
                             ($result['tasks_synced'] ?? 0);
                    $totalSynced += $synced;
                    logMessage("ClickUp sync completed successfully. Records synced: {$synced}");
                } else {
                    $errors[] = "ClickUp sync failed";
                    logMessage("ClickUp sync failed", 'ERROR');
                }
            } else {
                $errors[] = "ClickUp team ID not configured";
                logMessage("ClickUp team ID not configured", 'WARNING');
            }
        } catch (Exception $e) {
            $errors[] = "ClickUp error: " . $e->getMessage();
            logMessage("ClickUp sync error: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Sync Notion data
    if ($notionConfig) {
        logMessage("Starting Notion sync...");
        
        try {
            $notion = new NotionIntegration();
            $databaseId = $notionConfig['settings']['database_id'] ?? null;
            
            $result = $notion->syncToDatabase($databaseId);
            
            if ($result['success']) {
                $synced = $result['projects_synced'] ?? 0;
                $totalSynced += $synced;
                logMessage("Notion sync completed successfully. Records synced: {$synced}");
            } else {
                $errors[] = "Notion sync failed";
                logMessage("Notion sync failed", 'ERROR');
            }
        } catch (Exception $e) {
            $errors[] = "Notion error: " . $e->getMessage();
            logMessage("Notion sync error: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Summary
    if (empty($errors)) {
        logMessage("Sync completed successfully! Total records synced: {$totalSynced}");
    } else {
        logMessage("Sync completed with errors. Records synced: {$totalSynced}, Errors: " . implode(', ', $errors), 'WARNING');
    }
    
} catch (Exception $e) {
    logMessage("Critical error during sync: " . $e->getMessage(), 'ERROR');
    exit(1);
}

logMessage("Sync process finished.\n" . str_repeat('-', 50));

// Clean up old log entries (keep last 1000 lines)
$logFile = __DIR__ . '/sync.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    if (count($lines) > 1000) {
        $lines = array_slice($lines, -1000);
        file_put_contents($logFile, implode('', $lines));
    }
}

exit(0);
?>