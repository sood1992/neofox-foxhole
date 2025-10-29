<?php
require_once __DIR__ . '/../includes/functions.php';
require_authentication();

header('Content-Type: application/json');

$user = current_user();
if ($user['role'] !== 'employee') {
    http_response_code(403);
    echo json_encode(['error' => 'Only employees can control timers.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $payload['action'] ?? '';
$taskId = isset($payload['task_id']) ? (int) $payload['task_id'] : 0;
$note = trim($payload['note'] ?? '');

if (!$taskId || !in_array($action, ['start', 'stop'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$stmt = $pdo->prepare('SELECT assigned_to FROM tasks WHERE id = :id');
$stmt->execute(['id' => $taskId]);
$task = $stmt->fetch();

if (!$task || (int) $task['assigned_to'] !== (int) $user['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'You are not assigned to this task.']);
    exit;
}

try {
    if ($action === 'start') {
        // ensure no active timer exists for user
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM time_entries WHERE user_id = :user_id AND end_time IS NULL');
        $stmt->execute(['user_id' => $user['id']]);
        if ($stmt->fetchColumn()) {
            throw new RuntimeException('You already have an active timer. Stop it before starting another.');
        }

        $stmt = $pdo->prepare('INSERT INTO time_entries (task_id, user_id, start_time) VALUES (:task_id, :user_id, NOW())');
        $stmt->execute(['task_id' => $taskId, 'user_id' => $user['id']]);
        echo json_encode(['status' => 'started']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, start_time FROM time_entries WHERE task_id = :task_id AND user_id = :user_id AND end_time IS NULL ORDER BY start_time DESC LIMIT 1');
    $stmt->execute(['task_id' => $taskId, 'user_id' => $user['id']]);
    $entry = $stmt->fetch();

    if (!$entry) {
        throw new RuntimeException('No active timer found for this task.');
    }

    $stmt = $pdo->prepare('UPDATE time_entries SET end_time = NOW(), duration_minutes = TIMESTAMPDIFF(MINUTE, start_time, NOW()), note = :note WHERE id = :id');
    $stmt->execute(['id' => $entry['id'], 'note' => $note ?: null]);

    echo json_encode(['status' => 'stopped']);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);
}
