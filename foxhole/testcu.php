<?php
// clickup_test.php - Simple test script for your ClickUp API
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Your credentials (DELETE THIS FILE AFTER TESTING!)
$apiKey = 'D57GWYMBPQ9B6IS9K6GJ7Y5GRPEDQGXW1BHK4KQDE69GHKBPJNRHYOXKZBT3AL3B';
$teamId = '9016504311';

echo "<h1>🦊 ClickUp API Test</h1>";
echo "<p><strong>Testing API Key:</strong> " . substr($apiKey, 0, 10) . "...</p>";
echo "<p><strong>Team ID:</strong> {$teamId}</p>";
echo "<hr>";

// Test 1: Get authenticated user
echo "<h2>Test 1: Get User Info</h2>";
$url = 'https://api.clickup.com/api/v2/user';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";

if ($error) {
    echo "<p style='color: red;'><strong>CURL Error:</strong> {$error}</p>";
} else {
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "<p style='color: green;'><strong>✅ SUCCESS!</strong></p>";
        echo "<p><strong>User:</strong> " . $data['user']['username'] . "</p>";
        echo "<p><strong>Email:</strong> " . $data['user']['email'] . "</p>";
        echo "<p><strong>User ID:</strong> " . $data['user']['id'] . "</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ FAILED!</strong></p>";
        echo "<p><strong>Response:</strong> {$response}</p>";
    }
}

echo "<hr>";

// Test 2: Get teams
echo "<h2>Test 2: Get Teams</h2>";
$url = 'https://api.clickup.com/api/v2/team';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "<p style='color: green;'><strong>✅ Teams found!</strong></p>";
    foreach ($data['teams'] as $team) {
        echo "<p><strong>Team:</strong> {$team['name']} (ID: {$team['id']})</p>";
        if ($team['id'] == $teamId) {
            echo "<p style='color: green;'>🎯 <strong>This is your team!</strong></p>";
        }
    }
} else {
    echo "<p style='color: red;'><strong>❌ Failed to get teams</strong></p>";
    echo "<p><strong>Response:</strong> {$response}</p>";
}

echo "<hr>";

// Test 3: Get team members
echo "<h2>Test 3: Get Team Members</h2>";
$url = "https://api.clickup.com/api/v2/team/{$teamId}/member";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "<p style='color: green;'><strong>✅ Team members found!</strong></p>";
    echo "<p><strong>Member count:</strong> " . count($data['members']) . "</p>";
    foreach ($data['members'] as $member) {
        $user = $member['user'];
        echo "<p>👤 <strong>{$user['username']}</strong> ({$user['email']})</p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ Failed to get team members</strong></p>";
    echo "<p><strong>Response:</strong> {$response}</p>";
}

echo "<hr>";

// Test 4: Get spaces (projects)
echo "<h2>Test 4: Get Spaces (Projects)</h2>";
$url = "https://api.clickup.com/api/v2/team/{$teamId}/space";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "<p style='color: green;'><strong>✅ Spaces found!</strong></p>";
    echo "<p><strong>Space count:</strong> " . count($data['spaces']) . "</p>";
    foreach ($data['spaces'] as $space) {
        echo "<p>📁 <strong>{$space['name']}</strong> (ID: {$space['id']})</p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ Failed to get spaces</strong></p>";
    echo "<p><strong>Response:</strong> {$response}</p>";
}

echo "<hr>";
echo "<h2>🎯 Next Steps</h2>";
echo "<p>If all tests show ✅ SUCCESS, your API is working perfectly!</p>";
echo "<p>You can now proceed with the full integration setup.</p>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT: Delete this file after testing for security!</strong></p>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2 {
    color: #333;
}
hr {
    margin: 20px 0;
    border: none;
    border-top: 2px solid #ddd;
}
</style>