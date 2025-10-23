<?php
require_once __DIR__ . '/config.php';

session_start();

function is_authenticated(): bool
{
    return isset($_SESSION['user']);
}

function require_authentication(): void
{
    if (!is_authenticated()) {
        header('Location: index.php');
        exit;
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function format_duration(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    return sprintf('%02dh %02dm', $hours, $mins);
}

function format_currency(?float $value): string
{
    if ($value === null) {
        return '—';
    }
    return '$' . number_format($value, 2);
}

function format_percentage(?float $value): string
{
    if ($value === null) {
        return '—';
    }
    return number_format($value, 0) . '%';
}

function get_users_by_role(PDO $pdo, string $role): array
{
    $stmt = $pdo->prepare('SELECT id, name, title, focus_color FROM users WHERE role = :role ORDER BY name');
    $stmt->execute(['role' => $role]);
    return $stmt->fetchAll();
}

function get_projects(PDO $pdo, ?int $managerId = null): array
{
    $query = 'SELECT p.*, u.name AS manager_name, c.name AS client_name, c.brand_vibe,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS task_total,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = "complete") AS task_done,
        (SELECT IFNULL(SUM(duration_minutes),0) FROM time_entries te
            INNER JOIN tasks tt ON te.task_id = tt.id WHERE tt.project_id = p.id) AS minutes_spent
        FROM projects p
        LEFT JOIN users u ON u.id = p.manager_id
        LEFT JOIN clients c ON c.id = p.client_id';

    $params = [];
    if ($managerId) {
        $query .= ' WHERE p.manager_id = :manager_id';
        $params['manager_id'] = $managerId;
    }
    $query .= ' ORDER BY FIELD(p.pipeline_stage, "pitch","discovery","production","review","launch","retainer"), p.due_date IS NULL, p.due_date ASC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_tasks_for_user(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT t.*, p.name AS project_name, p.focus_theme, p.priority, p.health,
        (SELECT IFNULL(SUM(duration_minutes),0) FROM time_entries te WHERE te.task_id = t.id) AS minutes_spent,
        (SELECT id FROM time_entries te WHERE te.task_id = t.id AND te.user_id = :user_id AND te.end_time IS NULL LIMIT 1) AS active_entry_id
        FROM tasks t
        INNER JOIN projects p ON p.id = t.project_id
        WHERE t.assigned_to = :user_id
        ORDER BY t.status = "complete", t.due_date IS NULL, t.due_date ASC, t.created_at DESC');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function get_team_time_summary(PDO $pdo, string $startDate, string $endDate, ?int $userId = null): array
{
    $query = 'SELECT u.id, u.name, u.role, u.title, IFNULL(SUM(te.duration_minutes), 0) AS minutes
        FROM users u
        LEFT JOIN time_entries te ON te.user_id = u.id AND te.end_time BETWEEN :start AND :end
        WHERE u.role != "admin"';
    $params = ['start' => $startDate, 'end' => $endDate];
    if ($userId) {
        $query .= ' AND u.id = :user_id';
        $params['user_id'] = $userId;
    }
    $query .= ' GROUP BY u.id ORDER BY FIELD(u.role, "manager","employee"), u.name';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_project_comments(PDO $pdo, int $projectId): array
{
    $stmt = $pdo->prepare('SELECT pc.*, u.name, u.focus_color FROM project_comments pc
        INNER JOIN users u ON u.id = pc.user_id
        WHERE pc.project_id = :project_id
        ORDER BY pc.created_at DESC');
    $stmt->execute(['project_id' => $projectId]);
    return $stmt->fetchAll();
}

function get_clients_with_metrics(PDO $pdo, string $startDate, string $endDate): array
{
    $stmt = $pdo->prepare('SELECT c.*, u.name AS lead_name,
        IFNULL(SUM(CASE WHEN te.end_time BETWEEN :start AND :end THEN te.duration_minutes ELSE 0 END), 0) AS minutes_logged
        FROM clients c
        LEFT JOIN users u ON u.id = c.account_lead
        LEFT JOIN projects p ON p.client_id = c.id
        LEFT JOIN tasks t ON t.project_id = p.id
        LEFT JOIN time_entries te ON te.task_id = t.id
        GROUP BY c.id
        ORDER BY c.relationship_status = "active" DESC, c.name');
    $stmt->execute(['start' => $startDate, 'end' => $endDate]);
    return $stmt->fetchAll();
}

function get_pipeline_distribution(PDO $pdo, ?int $managerId = null): array
{
    $query = 'SELECT pipeline_stage, COUNT(*) AS total,
        SUM(CASE WHEN health IN ("watch","critical") THEN 1 ELSE 0 END) AS needs_attention
        FROM projects';
    $params = [];
    if ($managerId) {
        $query .= ' WHERE manager_id = :manager_id';
        $params['manager_id'] = $managerId;
    }
    $query .= ' GROUP BY pipeline_stage';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $ordered = ['pitch','discovery','production','review','launch','retainer'];
    $result = [];
    foreach ($ordered as $stage) {
        $found = ['pipeline_stage' => $stage, 'total' => 0, 'needs_attention' => 0];
        foreach ($rows as $row) {
            if ($row['pipeline_stage'] === $stage) {
                $found = $row;
                break;
            }
        }
        $result[] = $found;
    }
    return $result;
}

function get_resource_heatmap(PDO $pdo, ?int $managerId = null): array
{
    $latestWeek = $pdo->query('SELECT MAX(week_start) FROM resource_capacity')->fetchColumn();
    if (!$latestWeek) {
        return [];
    }

    $weekStart = new DateTimeImmutable($latestWeek);
    $weekEnd = $weekStart->modify('+6 days 23:59:59');

    $query = 'SELECT rc.*, u.name, u.title, u.focus_color,
        IFNULL(SUM(CASE WHEN te.end_time BETWEEN :start AND :end THEN te.duration_minutes ELSE 0 END),0) AS minutes_logged
        FROM resource_capacity rc
        INNER JOIN users u ON u.id = rc.user_id
        LEFT JOIN time_entries te ON te.user_id = rc.user_id
            AND te.end_time BETWEEN :start AND :end';

    $params = ['start' => $weekStart->format('Y-m-d 00:00:00'), 'end' => $weekEnd->format('Y-m-d H:i:s')];
    if ($managerId) {
        $query .= ' WHERE rc.user_id IN (SELECT DISTINCT assigned_to FROM tasks t INNER JOIN projects p ON p.id = t.project_id WHERE p.manager_id = :manager_id)';
        $params['manager_id'] = $managerId;
    }
    $query .= ' GROUP BY rc.id ORDER BY minutes_logged DESC';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_recent_checkins(PDO $pdo, int $limit = 6, ?int $userId = null): array
{
    $query = 'SELECT wc.*, u.name, u.focus_color, u.role
        FROM wellbeing_checkins wc
        INNER JOIN users u ON u.id = wc.user_id';
    $params = [];
    if ($userId) {
        $query .= ' WHERE wc.user_id = :user_id';
        $params['user_id'] = $userId;
    }
    $query .= ' ORDER BY wc.created_at DESC LIMIT ' . (int) $limit;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_latest_checkin(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM wellbeing_checkins WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_blockers_feed(PDO $pdo, ?int $managerId = null): array
{
    $query = "SELECT wc.blockers, wc.created_at, u.name, p.name AS project_name
        FROM wellbeing_checkins wc
        INNER JOIN users u ON u.id = wc.user_id
        LEFT JOIN tasks t ON t.assigned_to = u.id
        LEFT JOIN projects p ON p.id = t.project_id
        WHERE wc.blockers IS NOT NULL AND wc.blockers != ''
            AND wc.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $params = [];
    if ($managerId) {
        $query .= ' AND p.manager_id = :manager_id';
        $params['manager_id'] = $managerId;
    }
    $query .= ' GROUP BY wc.id ORDER BY wc.created_at DESC';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_project_milestones(PDO $pdo, array $projectIds): array
{
    if (!$projectIds) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $stmt = $pdo->prepare("SELECT pm.*, u.name AS owner_name FROM project_milestones pm
        LEFT JOIN users u ON u.id = pm.owner_id
        WHERE pm.project_id IN ($placeholders)
        ORDER BY pm.due_date IS NULL, pm.due_date ASC");
    $stmt->execute($projectIds);
    $rows = $stmt->fetchAll();
    $grouped = [];
    foreach ($rows as $row) {
        $grouped[$row['project_id']][] = $row;
    }
    return $grouped;
}

function get_idea_bank(PDO $pdo, ?int $projectId = null, ?int $ownerId = null): array
{
    $query = 'SELECT ib.*, p.name AS project_name, u.name AS owner_name
        FROM idea_bank ib
        LEFT JOIN projects p ON p.id = ib.project_id
        LEFT JOIN users u ON u.id = ib.owner_id';
    $conditions = [];
    $params = [];
    if ($projectId) {
        $conditions[] = 'ib.project_id = :project_id';
        $params['project_id'] = $projectId;
    }
    if ($ownerId) {
        $conditions[] = 'ib.owner_id = :owner_id';
        $params['owner_id'] = $ownerId;
    }
    if ($conditions) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $query .= ' ORDER BY ib.created_at DESC';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_meeting_notes(PDO $pdo, ?int $managerId = null): array
{
    $query = 'SELECT mn.*, p.name AS project_name, u.name AS facilitator_name
        FROM meeting_notes mn
        INNER JOIN projects p ON p.id = mn.project_id
        LEFT JOIN users u ON u.id = mn.facilitator_id';
    $params = [];
    if ($managerId) {
        $query .= ' WHERE p.manager_id = :manager_id';
        $params['manager_id'] = $managerId;
    }
    $query .= ' ORDER BY mn.note_date DESC LIMIT 12';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function save_wellbeing_checkin(PDO $pdo, int $userId, array $data): void
{
    $stmt = $pdo->prepare('INSERT INTO wellbeing_checkins (user_id, energy_level, mood, blockers, focus_goal, created_at)
        VALUES (:user_id, :energy_level, :mood, :blockers, :focus_goal, NOW())');
    $stmt->execute([
        'user_id' => $userId,
        'energy_level' => (int) $data['energy_level'],
        'mood' => $data['mood'],
        'blockers' => $data['blockers'] ?: null,
        'focus_goal' => $data['focus_goal'] ?: null,
    ]);
}

function save_resource_capacity(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare('INSERT INTO resource_capacity (user_id, week_start, planned_minutes, meeting_minutes, focus_theme)
        VALUES (:user_id, :week_start, :planned_minutes, :meeting_minutes, :focus_theme)
        ON DUPLICATE KEY UPDATE planned_minutes = VALUES(planned_minutes), meeting_minutes = VALUES(meeting_minutes), focus_theme = VALUES(focus_theme)');
    $stmt->execute([
        'user_id' => (int) $data['user_id'],
        'week_start' => $data['week_start'],
        'planned_minutes' => (int) $data['planned_minutes'],
        'meeting_minutes' => (int) $data['meeting_minutes'],
        'focus_theme' => $data['focus_theme'] ?: null,
    ]);
}

function save_meeting_note(PDO $pdo, array $data, int $facilitatorId): void
{
    $stmt = $pdo->prepare('INSERT INTO meeting_notes (project_id, facilitator_id, note_date, summary, next_steps)
        VALUES (:project_id, :facilitator_id, :note_date, :summary, :next_steps)');
    $stmt->execute([
        'project_id' => (int) $data['project_id'],
        'facilitator_id' => $facilitatorId,
        'note_date' => $data['note_date'],
        'summary' => trim($data['summary']),
        'next_steps' => $data['next_steps'] ? trim($data['next_steps']) : null,
    ]);
}

function save_milestone(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare('INSERT INTO project_milestones (project_id, title, owner_id, status, highlight, due_date)
        VALUES (:project_id, :title, :owner_id, :status, :highlight, :due_date)');
    $stmt->execute([
        'project_id' => (int) $data['project_id'],
        'title' => trim($data['title']),
        'owner_id' => $data['owner_id'] ? (int) $data['owner_id'] : null,
        'status' => $data['status'] ?? 'planned',
        'highlight' => $data['highlight'] ? trim($data['highlight']) : null,
        'due_date' => $data['due_date'] ?: null,
    ]);
}

function update_milestone_status(PDO $pdo, int $milestoneId, string $status): void
{
    $stmt = $pdo->prepare('UPDATE project_milestones SET status = :status, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $milestoneId]);
}

function compute_focus_score(array $checkins): ?float
{
    if (!$checkins) {
        return null;
    }
    $sum = 0;
    foreach ($checkins as $checkin) {
        $sum += (int) $checkin['energy_level'];
    }
    return $sum / count($checkins);
}
