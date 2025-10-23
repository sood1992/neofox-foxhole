<?php
require_once __DIR__ . '/includes/functions.php';
require_authentication();

$user = current_user();
if (!in_array($user['role'], ['admin', 'manager'], true)) {
    header('Location: dashboard.php');
    exit;
}

$range = $_GET['range'] ?? 'week';
$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : null;
$customStart = $_GET['start'] ?? null;
$customEnd = $_GET['end'] ?? null;

$startDate = new DateTimeImmutable('today');
$endDate = new DateTimeImmutable('today 23:59:59');

switch ($range) {
    case 'month':
        $startDate = $startDate->modify('first day of this month')->setTime(0,0,0);
        break;
    case 'custom':
        if ($customStart && $customEnd) {
            $startDate = (new DateTimeImmutable($customStart))->setTime(0,0,0);
            $endDate = (new DateTimeImmutable($customEnd))->setTime(23,59,59);
        }
        break;
    case 'week':
    default:
        $startDate = $startDate->modify('-6 days')->setTime(0,0,0);
        break;
}

$summary = get_team_time_summary($pdo, $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'), $employeeId ?: null);
$clients = get_clients_with_metrics($pdo, $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'));
$pipeline = get_pipeline_distribution($pdo, $user['role'] === 'manager' ? (int) $user['id'] : null);
$energyFeed = get_recent_checkins($pdo, 40, $employeeId ?: null);
$avgEnergy = compute_focus_score($energyFeed);

// project level roll-up
$query = 'SELECT p.id, p.name, p.status, p.due_date,
    COUNT(DISTINCT t.id) AS total_tasks,
    SUM(CASE WHEN t.status = "complete" THEN 1 ELSE 0 END) AS completed_tasks,
    IFNULL(SUM(CASE WHEN te.end_time BETWEEN :start AND :end THEN te.duration_minutes ELSE 0 END),0) AS minutes_spent
    FROM projects p
    LEFT JOIN tasks t ON t.project_id = p.id
    LEFT JOIN time_entries te ON te.task_id = t.id';
$params = ['start' => $startDate->format('Y-m-d H:i:s'), 'end' => $endDate->format('Y-m-d H:i:s')];

$conditions = [];
if ($user['role'] === 'manager') {
    $conditions[] = 'p.manager_id = :manager_id';
    $params['manager_id'] = $user['id'];
}
if ($employeeId) {
    $conditions[] = 't.assigned_to = :employee_id';
    $params['employee_id'] = $employeeId;
}
if ($conditions) {
    $query .= ' WHERE ' . implode(' AND ', $conditions);
}
$query .= ' GROUP BY p.id ORDER BY p.due_date IS NULL, p.due_date ASC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projectRows = $stmt->fetchAll();

$employees = get_users_by_role($pdo, 'employee');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole Reports</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
    <div class="app-shell">
        <aside class="sidebar">
            <div class="logo">🦊 Foxhole</div>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="reports.php" class="active">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main>
            <header class="dashboard-header">
                <div>
                    <div class="welcome">Reporting command center 📈</div>
                    <p class="muted">Generate weekly or monthly visibility by teammate, project, or focus window.</p>
                </div>
            </header>

            <div class="card">
                <h2>Filters <span>🧮</span></h2>
                <form method="get" class="inline-form">
                    <label style="color:var(--muted); text-transform:uppercase; letter-spacing:0.08em; font-size:0.75rem;">Range</label>
                    <select name="range">
                        <option value="week" <?= $range === 'week' ? 'selected' : '' ?>>Last 7 days</option>
                        <option value="month" <?= $range === 'month' ? 'selected' : '' ?>>This month</option>
                        <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom</option>
                    </select>
                    <input type="date" name="start" value="<?= htmlspecialchars($customStart ?? $startDate->format('Y-m-d')) ?>">
                    <input type="date" name="end" value="<?= htmlspecialchars($customEnd ?? $endDate->format('Y-m-d')) ?>">
                    <select name="employee_id">
                        <option value="">All team members</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= $employee['id'] ?>" <?= $employeeId === (int) $employee['id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="primary-btn" type="submit">Run report</button>
                </form>
            </div>

            <div class="stat-grid" style="margin-top:2rem;">
                <div class="stat-card">
                    <h3>Window start</h3>
                    <div class="value" style="font-size:1.3rem;"><?= $startDate->format('M d, Y') ?></div>
                </div>
                <div class="stat-card">
                    <h3>Window end</h3>
                    <div class="value" style="font-size:1.3rem;"><?= $endDate->format('M d, Y') ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total hours logged</h3>
                    <div class="value" style="font-size:1.8rem;">
                        <?= number_format(array_sum(array_column($summary, 'minutes')) / 60, 1) ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Avg energy pulse</h3>
                    <div class="value" style="font-size:1.6rem;"><?= $avgEnergy ? number_format($avgEnergy, 1) : '—' ?></div>
                </div>
            </div>

            <div class="card" style="margin-top:2rem;">
                <h2>Employee breakdown <span>👥</span></h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Title</th>
                            <th>Hours logged</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= ucfirst($row['role']) ?></td>
                                <td><?= htmlspecialchars($row['title'] ?? '—') ?></td>
                                <td><?= number_format($row['minutes'] / 60, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($summary)): ?>
                            <tr><td colspan="3" style="color:var(--muted);">No time entries yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-top:2rem;">
                <h2>Retainer utilization <span>🤝</span></h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Lead</th>
                            <th>Hours committed</th>
                            <th>Hours logged</th>
                            <th>Coverage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client):
                            $committed = (int) ($client['retainer_hours'] ?? 0);
                            $logged = $client['minutes_logged'] / 60;
                            $coverage = $committed ? min(100, ($logged / $committed) * 100) : null;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($client['name']) ?></td>
                                <td><?= htmlspecialchars($client['lead_name'] ?? '—') ?></td>
                                <td><?= $committed ?: '—' ?></td>
                                <td><?= number_format($logged, 1) ?></td>
                                <td><?= $coverage ? number_format($coverage, 0) . '%' : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-top:2rem;">
                <h2>Pipeline snapshot <span>🛣️</span></h2>
                <div class="pipeline-grid compact">
                    <?php foreach ($pipeline as $column): ?>
                        <div class="pipeline-column">
                            <header>
                                <h3><?= ucfirst($column['pipeline_stage']) ?></h3>
                                <span class="muted tiny">Projects <?= (int) $column['total'] ?></span>
                            </header>
                            <p class="muted tiny">Needs attention: <?= (int) $column['needs_attention'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card" style="margin-top:2rem;">
                <h2>Project velocity <span>🚀</span></h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Status</th>
                            <th>Tasks complete</th>
                            <th>Hours logged</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projectRows as $project): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['name']) ?></td>
                                <td><span class="status-pill status-<?= htmlspecialchars($project['status']) ?>"><?= ucfirst(str_replace('_',' ', $project['status'])) ?></span></td>
                                <td><?= (int) $project['completed_tasks'] ?>/<?= (int) $project['total_tasks'] ?></td>
                                <td><?= number_format($project['minutes_spent'] / 60, 1) ?></td>
                                <td><?= $project['due_date'] ?: '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projectRows)): ?>
                            <tr><td colspan="5" style="color:var(--muted);">No projects in this range.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-top:2rem;">
                <h2>Energy journal <span>⚡</span></h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Teammate</th>
                            <th>Logged</th>
                            <th>Energy</th>
                            <th>Mood</th>
                            <th>Focus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($energyFeed as $pulse): ?>
                            <tr>
                                <td><?= htmlspecialchars($pulse['name']) ?></td>
                                <td><?= (new DateTimeImmutable($pulse['created_at']))->format('M d H:i') ?></td>
                                <td><?= (int) $pulse['energy_level'] ?>/10</td>
                                <td><?= ucfirst($pulse['mood']) ?></td>
                                <td><?= htmlspecialchars($pulse['focus_goal'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$energyFeed): ?>
                            <tr><td colspan="5" class="muted">No check-ins logged for this range.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
