<?php
// test_basic.php - Very basic test to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Basic Foxhole Test</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Test 1: Basic PHP info
echo "<h2>📋 PHP Environment</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Directory: " . getcwd() . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";

// Test 2: Check if we can receive POST data
echo "<h2>📨 POST Data Test</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p style='color: green;'>✅ POST request received!</p>";
    echo "<p><strong>POST Data:</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    if (isset($_POST['test_action'])) {
        echo "<p><strong>Test Action:</strong> " . $_POST['test_action'] . "</p>";
        
        // Simple password test
        if ($_POST['test_action'] === 'admin_test' && isset($_POST['password'])) {
            $password = $_POST['password'];
            echo "<p><strong>Password received:</strong> '" . htmlspecialchars($password) . "'</p>";
            
            if ($password === '838838') {
                echo "<p style='color: green; font-weight: bold;'>✅ ADMIN PASSWORD CORRECT!</p>";
                
                // Test session
                session_start();
                $_SESSION['test'] = 'working';
                echo "<p style='color: green;'>✅ Session created</p>";
                echo "<p>Session ID: " . session_id() . "</p>";
                
                // Return JSON response
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Admin login would work!',
                    'redirect' => 'dashboard.php',
                    'session_id' => session_id()
                ]);
                exit;
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ ADMIN PASSWORD WRONG</p>";
                echo "<p>Expected: '838838'</p>";
                echo "<p>Received: '" . htmlspecialchars($password) . "'</p>";
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Wrong password',
                    'expected' => '838838',
                    'received' => $password
                ]);
                exit;
            }
        }
    }
} else {
    echo "<p>No POST data received yet. Use the form below to test.</p>";
}

// Test 3: File existence
echo "<h2>📁 File Check</h2>";
$files = ['auth.php', 'dashboard.php', 'config-2.php', 'api.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ {$file} exists</p>";
    } else {
        echo "<p style='color: red;'>❌ {$file} missing</p>";
    }
}

// Test 4: Test current auth.php
echo "<h2>🔐 Auth.php Test</h2>";
if (file_exists('auth.php')) {
    echo "<p>Testing if auth.php is accessible...</p>";
    
    // Try to include it
    try {
        ob_start();
        include_once 'auth.php';
        $output = ob_get_clean();
        
        if (empty($output)) {
            echo "<p style='color: green;'>✅ auth.php loaded without errors</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ auth.php produced output:</p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
        
        // Test if class exists
        if (class_exists('FoxholeAuth')) {
            echo "<p style='color: green;'>✅ FoxholeAuth class found</p>";
            
            // Try to create instance
            try {
                $auth = new FoxholeAuth();
                echo "<p style='color: green;'>✅ FoxholeAuth instance created</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Cannot create FoxholeAuth: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ FoxholeAuth class not found</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error loading auth.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ auth.php not found</p>";
}

?>

<hr>
<h2>🧪 Manual Test Form</h2>
<p>Use this form to test the login manually:</p>

<form method="POST" style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3>Test Admin Login</h3>
    <input type="hidden" name="test_action" value="admin_test">
    <p>
        <label>Password:</label><br>
        <input type="password" name="password" value="838838" style="padding: 8px; width: 200px;">
    </p>
    <button type="submit" style="padding: 10px 20px; background: #4F46E5; color: white; border: none; border-radius: 4px; cursor: pointer;">
        Test Admin Login
    </button>
</form>

<hr>
<h2>🌐 AJAX Test</h2>
<p>Test the same login via AJAX (like your real login page):</p>

<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3>AJAX Admin Login Test</h3>
    <input type="password" id="ajaxPassword" value="838838" style="padding: 8px; width: 200px;" placeholder="Admin password">
    <button onclick="testAjaxLogin()" style="padding: 10px 20px; background: #10B981; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
        Test AJAX Login
    </button>
    <div id="ajaxResult" style="margin-top: 15px; padding: 10px; border-radius: 4px; display: none;"></div>
</div>

<script>
async function testAjaxLogin() {
    const password = document.getElementById('ajaxPassword').value;
    const resultDiv = document.getElementById('ajaxResult');
    
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '🔄 Testing...';
    resultDiv.style.background = '#fff3cd';
    resultDiv.style.color = '#856404';
    
    try {
        console.log('Testing AJAX login...');
        
        const response = await fetch('test_basic.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `test_action=admin_test&password=${encodeURIComponent(password)}`
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        // Try to parse as JSON
        try {
            const result = JSON.parse(text);
            console.log('Parsed JSON:', result);
            
            if (result.success) {
                resultDiv.innerHTML = `✅ SUCCESS! ${result.message}<br>Would redirect to: ${result.redirect}<br>Session ID: ${result.session_id}`;
                resultDiv.style.background = '#d4edda';
                resultDiv.style.color = '#155724';
            } else {
                resultDiv.innerHTML = `❌ FAILED: ${result.error}<br>Expected: ${result.expected}<br>Received: ${result.received}`;
                resultDiv.style.background = '#f8d7da';
                resultDiv.style.color = '#721c24';
            }
        } catch (parseError) {
            resultDiv.innerHTML = `❌ Invalid JSON response:<br><pre>${text}</pre>`;
            resultDiv.style.background = '#f8d7da';
            resultDiv.style.color = '#721c24';
        }
        
    } catch (error) {
        console.error('AJAX error:', error);
        resultDiv.innerHTML = `❌ Network error: ${error.message}`;
        resultDiv.style.background = '#f8d7da';
        resultDiv.style.color = '#721c24';
    }
}
</script>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
hr {
    margin: 30px 0;
    border: none;
    border-top: 2px solid #ddd;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    border: 1px solid #e9ecef;
}
</style>