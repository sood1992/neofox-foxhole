<?php
// auto_checkout_cron.php - Run at 10 PM daily to auto-checkout employees
// This script should be run via cron job: 0 22 * * * /usr/bin/php /path/to/auto_checkout_cron.php

// Prevent running from web browser
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/config-2.php';
require_once __DIR__ . '/attendance_functions.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting auto-checkout process...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    $attendanceManager = new AttendanceManager();
    
    // Get all employees who checked in but haven't checked out today
    $stmt = $db->query("
        SELECT 
            a.employee_id,
            a.check_in_time,
            e.name
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.date = CURDATE() 
        AND a.check_in_time IS NOT NULL 
        AND a.check_out_time IS NULL
    ");
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        echo "No employees to auto-checkout today.\n";
    } else {
        echo "Found " . count($employees) . " employees to auto-checkout.\n";
        
        foreach ($employees as $emp) {
            echo "Processing: {$emp['name']} (ID: {$emp['employee_id']})... ";
            
            $result = $attendanceManager->autoCheckOut($emp['employee_id']);
            
            if ($result['success']) {
                echo "SUCCESS\n";
                
                // Log to file
                $logEntry = date('Y-m-d H:i:s') . " - Auto-checkout: {$emp['name']} (ID: {$emp['employee_id']})\n";
                file_put_contents(__DIR__ . '/logs/auto_checkout.log', $logEntry, FILE_APPEND);
            } else {
                echo "FAILED: " . $result['message'] . "\n";
            }
        }
    }
    
    // Clean up old attendance records (optional - keep last 6 months)
    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
    $stmt = $db->prepare("DELETE FROM attendance WHERE date < ?");
    $deletedCount = $stmt->execute([$sixMonthsAgo]);
    
    if ($deletedCount > 0) {
        echo "Cleaned up $deletedCount old attendance records.\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Auto-checkout process completed.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    // Log error
    $errorLog = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/logs/auto_checkout_errors.log', $errorLog, FILE_APPEND);
    
    exit(1);
}

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

exit(0);
?>