<?php
require_once __DIR__ . '/includes/functions.php';
require_authentication();

$user = current_user();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$today = new DateTimeImmutable('today');
$weekStart = $today->modify('-' . ($today->format('N') - 1) . ' days');
$weekEnd = $weekStart->modify('+6 days');
$monthStart = $today->modify('first day of this month');
$monthEnd = $today->modify('last day of this month');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_project' && $user['role'] === 'admin') {
            $stmt = $pdo->prepare('INSERT INTO projects (name, description, manager_id, client_id, start_date, due_date, status, pipeline_stage, health, priority, budget_hours, budget_amount, creative_theme)
                VALUES (:name, :description, :manager_id, :client_id, :start_date, :due_date, :status, :pipeline_stage, :health, :priority, :budget_hours, :budget_amount, :creative_theme)');
            $stmt->execute([
                'name' => trim($_POST['project_name']),
                'description' => trim($_POST['project_description']),
                'manager_id' => $_POST['manager_id'] ?: null,
                'client_id' => $_POST['client_id'] ?: null,
                'start_date' => $_POST['start_date'] ?: null,
                'due_date' => $_POST['due_date'] ?: null,
                'status' => $_POST['status'] ?? 'not_started',
                'pipeline_stage' => $_POST['pipeline_stage'] ?? 'production',
                'health' => $_POST['health'] ?? 'steady',
                'priority' => $_POST['priority'] ?? 'medium',
                'budget_hours' => $_POST['budget_hours'] ?: null,
                'budget_amount' => $_POST['budget_amount'] ?: null,
                'creative_theme' => trim($_POST['creative_theme']) ?: null,
            ]);
            $_SESSION['flash'] = 'Project created successfully.';
        } elseif ($action === 'create_task' && in_array($user['role'], ['admin', 'manager'], true)) {
            if ($user['role'] === 'manager') {
                $stmt = $pdo->prepare('SELECT manager_id FROM projects WHERE id = :id');
                $stmt->execute(['id' => (int) $_POST['project_id']]);
                $project = $stmt->fetch();
                if (!$project || (int) $project['manager_id'] !== (int) $user['id']) {
                    throw new RuntimeException('You can only create tasks for your own projects.');
                }
            }
            $stmt = $pdo->prepare('INSERT INTO tasks (project_id, assigned_to, title, description, estimated_minutes, due_date, status)
                VALUES (:project_id, :assigned_to, :title, :description, :estimated_minutes, :due_date, :status)');
            $stmt->execute([
                'project_id' => (int) $_POST['project_id'],
                'assigned_to' => (int) $_POST['assigned_to'],
                'title' => trim($_POST['task_title']),
                'description' => trim($_POST['task_description']),
                'estimated_minutes' => $_POST['estimated_minutes'] ?: null,
                'due_date' => $_POST['task_due_date'] ?: null,
                'status' => $_POST['task_status'] ?? 'not_started',
            ]);
            $_SESSION['flash'] = 'Task added successfully.';
        } elseif ($action === 'update_task_status') {
            $taskId = (int) $_POST['task_id'];
            $newStatus = $_POST['status'] ?? 'not_started';

            $stmt = $pdo->prepare('SELECT t.assigned_to, p.manager_id FROM tasks t INNER JOIN projects p ON p.id = t.project_id WHERE t.id = :id');
            $stmt->execute(['id' => $taskId]);
            $task = $stmt->fetch();

            if (!$task) {
                throw new RuntimeException('Task not found.');
            }

            if ($user['role'] === 'employee' && (int) $task['assigned_to'] !== (int) $user['id']) {
                throw new RuntimeException('You are not allowed to update this task.');
            }

            if ($user['role'] === 'manager' && (int) $task['manager_id'] !== (int) $user['id']) {
                throw new RuntimeException('You are not allowed to update this task.');
            }

            $stmt = $pdo->prepare('UPDATE tasks SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $newStatus, 'id' => $taskId]);
            $_SESSION['flash'] = 'Task status updated.';
        } elseif ($action === 'add_comment' && in_array($user['role'], ['admin', 'manager'], true)) {
            $stmt = $pdo->prepare('INSERT INTO project_comments (project_id, user_id, comment) VALUES (:project_id, :user_id, :comment)');
            $stmt->execute([
                'project_id' => (int) $_POST['project_id'],
                'user_id' => (int) $user['id'],
                'comment' => trim($_POST['comment']),
            ]);
            $_SESSION['flash'] = 'Project update posted.';
        } elseif ($action === 'create_client' && $user['role'] === 'admin') {
            $stmt = $pdo->prepare('INSERT INTO clients (name, industry, relationship_status, account_lead, retainer_hours, retainer_value, brand_vibe)
                VALUES (:name, :industry, :relationship_status, :account_lead, :retainer_hours, :retainer_value, :brand_vibe)');
            $stmt->execute([
                'name' => trim($_POST['client_name']),
                'industry' => trim($_POST['industry']) ?: null,
                'relationship_status' => $_POST['relationship_status'] ?? 'active',
                'account_lead' => $_POST['account_lead'] ?: null,
                'retainer_hours' => $_POST['retainer_hours'] ?: null,
                'retainer_value' => $_POST['retainer_value'] ?: null,
                'brand_vibe' => trim($_POST['brand_vibe']) ?: null,
            ]);
            $_SESSION['flash'] = 'Client added.';
        } elseif ($action === 'log_capacity' && $user['role'] === 'admin') {
            save_resource_capacity($pdo, [
                'user_id' => $_POST['capacity_user'],
                'week_start' => $_POST['week_start'],
                'planned_minutes' => $_POST['planned_minutes'],
                'meeting_minutes' => $_POST['meeting_minutes'],
                'focus_theme' => $_POST['focus_theme'],
            ]);
            $_SESSION['flash'] = 'Capacity plan updated.';
        } elseif ($action === 'log_checkin' && $user['role'] === 'employee') {
            save_wellbeing_checkin($pdo, (int) $user['id'], [
                'energy_level' => $_POST['energy_level'],
                'mood' => $_POST['mood'],
                'blockers' => trim($_POST['blockers'] ?? ''),
                'focus_goal' => trim($_POST['focus_goal'] ?? ''),
            ]);
            $_SESSION['flash'] = 'Check-in logged. Thanks for the pulse!';
        } elseif ($action === 'log_meeting_note' && in_array($user['role'], ['admin', 'manager'], true)) {
            save_meeting_note($pdo, [
                'project_id' => $_POST['meeting_project'],
                'note_date' => $_POST['note_date'],
                'summary' => $_POST['summary'],
                'next_steps' => $_POST['next_steps'] ?? '',
            ], (int) $user['id']);
            $_SESSION['flash'] = 'Meeting note captured.';
        } elseif ($action === 'create_milestone' && in_array($user['role'], ['admin', 'manager'], true)) {
            save_milestone($pdo, [
                'project_id' => $_POST['milestone_project'],
                'title' => $_POST['milestone_title'],
                'owner_id' => $_POST['milestone_owner'] ?: null,
                'status' => $_POST['milestone_status'],
                'highlight' => $_POST['milestone_highlight'] ?? '',
                'due_date' => $_POST['milestone_due'] ?? null,
            ]);
            $_SESSION['flash'] = 'Milestone scheduled.';
        } elseif ($action === 'create_idea' && in_array($user['role'], ['manager', 'employee'], true)) {
            $stmt = $pdo->prepare('INSERT INTO idea_bank (project_id, owner_id, title, impact, effort, description) VALUES (:project_id, :owner_id, :title, :impact, :effort, :description)');
            $stmt->execute([
                'project_id' => $_POST['idea_project'] ?: null,
                'owner_id' => $user['id'],
                'title' => trim($_POST['idea_title']),
                'impact' => $_POST['idea_impact'] ?? 'medium',
                'effort' => $_POST['idea_effort'] ?? 'moderate',
                'description' => trim($_POST['idea_description']) ?: null,
            ]);
            $_SESSION['flash'] = 'Idea added to the brain trust.';
        }
    } catch (Throwable $e) {
        $_SESSION['flash'] = 'Error: ' . $e->getMessage();
    }

    header('Location: dashboard.php');
    exit;
}

$managers = get_users_by_role($pdo, 'manager');
$employees = get_users_by_role($pdo, 'employee');

if ($user['role'] === 'admin') {
    $projects = get_projects($pdo);
    $projectIds = array_column($projects, 'id');
    $milestones = get_project_milestones($pdo, $projectIds);
    $pipeline = get_pipeline_distribution($pdo);
    $clients = get_clients_with_metrics($pdo, $monthStart->format('Y-m-d 00:00:00'), $monthEnd->format('Y-m-d 23:59:59'));
    $heatmap = get_resource_heatmap($pdo);
    $recentCheckins = get_recent_checkins($pdo, 6);
    $ideas = get_idea_bank($pdo);
    $meetingNotes = get_meeting_notes($pdo);
    $blockers = get_blockers_feed($pdo);

    $teamFocus = $pdo->query('SELECT u.name, u.focus_color, COUNT(t.id) AS active_tasks
        FROM users u
        LEFT JOIN tasks t ON t.assigned_to = u.id AND t.status IN ("not_started", "in_progress", "blocked")
        WHERE u.role = "employee"
        GROUP BY u.id
        ORDER BY active_tasks DESC, u.name ASC
        LIMIT 6')->fetchAll();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='employee'");
    $totalEmployees = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM clients WHERE relationship_status='active'");
    $activeClients = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM projects WHERE status != 'complete'");
    $activeProjects = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT IFNULL(SUM(duration_minutes),0) FROM time_entries WHERE end_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $hoursWeek = round(($stmt->fetchColumn() / 60), 1);

    $totalRetainerHours = 0;
    $usedRetainerHours = 0;
    foreach ($clients as $client) {
        $totalRetainerHours += (int) ($client['retainer_hours'] ?? 0);
        $usedRetainerHours += $client['minutes_logged'] / 60;
    }
    $retainerCoverage = $totalRetainerHours ? min(100, ($usedRetainerHours / $totalRetainerHours) * 100) : null;
    $avgEnergy = compute_focus_score($recentCheckins);
} elseif ($user['role'] === 'manager') {
    $projects = get_projects($pdo, $user['id']);
    $projectIds = array_column($projects, 'id');
    $milestones = get_project_milestones($pdo, $projectIds);
    $pipeline = get_pipeline_distribution($pdo, $user['id']);
    $clients = get_clients_with_metrics($pdo, $monthStart->format('Y-m-d 00:00:00'), $monthEnd->format('Y-m-d 23:59:59'));
    $heatmap = get_resource_heatmap($pdo, $user['id']);
    $recentCheckins = get_recent_checkins($pdo, 6);
    $ideas = get_idea_bank($pdo);
    $meetingNotes = get_meeting_notes($pdo, $user['id']);
    $blockers = get_blockers_feed($pdo, $user['id']);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE manager_id = :id AND status != "complete"');
    $stmt->execute(['id' => $user['id']]);
    $activeProjects = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON p.id = t.project_id WHERE p.manager_id = :id AND t.status IN ("in_progress","blocked")');
    $stmt->execute(['id' => $user['id']]);
    $tasksInFlight = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT IFNULL(SUM(te.duration_minutes),0)
        FROM time_entries te
        INNER JOIN tasks t ON te.task_id = t.id
        INNER JOIN projects p ON p.id = t.project_id
        WHERE p.manager_id = :id AND te.end_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
    $stmt->execute(['id' => $user['id']]);
    $hoursWeek = round(($stmt->fetchColumn() / 60), 1);

    $avgEnergy = compute_focus_score($recentCheckins);
} else {
    $tasks = get_tasks_for_user($pdo, $user['id']);
    $recentCheckins = get_recent_checkins($pdo, 5, $user['id']);
    $latestCheckin = get_latest_checkin($pdo, $user['id']);
    $ideas = get_idea_bank($pdo, null, $user['id']);

    $stmt = $pdo->prepare('SELECT IFNULL(SUM(duration_minutes),0) FROM time_entries WHERE user_id = :id AND end_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
    $stmt->execute(['id' => $user['id']]);
    $hoursWeek = round(($stmt->fetchColumn() / 60), 1);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE assigned_to = :id');
    $stmt->execute(['id' => $user['id']]);
    $totalTasks = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE assigned_to = :id AND status = "complete"');
    $stmt->execute(['id' => $user['id']]);
    $completedTasks = (int) $stmt->fetchColumn();

    $focusScore = compute_focus_score($recentCheckins);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxhole Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
    <div class="app-shell">
        <aside class="sidebar">
            <div class="logo">🦊 Foxhole</div>
            <nav>
                <a href="dashboard.php" class="active">Dashboard</a>
                <?php if (in_array($user['role'], ['admin','manager'], true)): ?>
                    <a href="reports.php">Reports</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
            <div class="sidebar-card">
                <p class="muted"><?= htmlspecialchars($user['title'] ?? ucfirst($user['role'])) ?></p>
                <p><strong><?= htmlspecialchars($user['name']) ?></strong></p>
            </div>
        </aside>
        <main>
            <header class="dashboard-header">
                <div>
                    <div class="welcome">Hello <?= htmlspecialchars($user['name']) ?> 👋</div>
                    <p class="muted">Your ADHD-friendly command center keeps priorities crisp, visual, and calm.</p>
                </div>
                <?php if ($user['role'] === 'employee' && isset($focusScore)): ?>
                    <div class="focus-chip">Focus energy: <?= $focusScore ? number_format($focusScore, 1) : '—' ?>/10</div>
                <?php elseif (isset($avgEnergy) && $avgEnergy): ?>
                    <div class="focus-chip">Team energy avg: <?= number_format($avgEnergy, 1) ?>/10</div>
                <?php endif; ?>
            </header>
            <?php if ($flash): ?>
                <div class="alert alert-inline">
                    <?= htmlspecialchars($flash) ?>
                </div>
            <?php endif; ?>

            <?php if ($user['role'] === 'admin'): ?>
                <section class="stat-grid">
                    <div class="stat-card">
                        <h3>Team members</h3>
                        <div class="value"><?= $totalEmployees ?></div>
                        <p class="muted">Across creative + ops</p>
                    </div>
                    <div class="stat-card">
                        <h3>Active clients</h3>
                        <div class="value"><?= $activeClients ?></div>
                        <p class="muted">Relationship health monitor</p>
                    </div>
                    <div class="stat-card">
                        <h3>Projects in motion</h3>
                        <div class="value"><?= $activeProjects ?></div>
                        <p class="muted">Not yet completed</p>
                    </div>
                    <div class="stat-card">
                        <h3>Hours logged (7d)</h3>
                        <div class="value"><?= $hoursWeek ?></div>
                        <p class="muted">All contributors</p>
                    </div>
                    <div class="stat-card">
                        <h3>Retainer coverage</h3>
                        <div class="value"><?= $retainerCoverage ? number_format($retainerCoverage, 0) : '—' ?>%</div>
                        <p class="muted">Usage this month</p>
                    </div>
                    <div class="stat-card">
                        <h3>Pipeline health</h3>
                        <div class="value">❤️‍🔥 <?= array_sum(array_column($pipeline, 'total')) ?></div>
                        <p class="muted">Total engagements</p>
                    </div>
                </section>

                <div class="grid two-col">
                    <section class="card">
                        <h2>Client retainers <span>🤝</span></h2>
                        <table class="table tight">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Lead</th>
                                    <th>Hours</th>
                                    <th>Used</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($client['name']) ?></strong>
                                            <div class="muted small"><?= htmlspecialchars($client['industry'] ?? '—') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($client['lead_name'] ?? 'Unassigned') ?></td>
                                        <td><?= $client['retainer_hours'] ?: '—' ?></td>
                                        <td><?= number_format($client['minutes_logged'] / 60, 1) ?></td>
                                        <td><?= format_currency($client['retainer_value']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <form method="post" class="inline-form" style="margin-top:1rem;">
                            <input type="hidden" name="action" value="create_client">
                            <h3 class="form-title">Add client</h3>
                            <div class="form-grid">
                                <input name="client_name" placeholder="Client name" required>
                                <input name="industry" placeholder="Industry">
                                <select name="relationship_status">
                                    <option value="active">Active</option>
                                    <option value="prospect">Prospect</option>
                                    <option value="paused">Paused</option>
                                    <option value="closed">Closed</option>
                                </select>
                                <select name="account_lead">
                                    <option value="">Account lead</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?= $manager['id'] ?>"><?= htmlspecialchars($manager['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="retainer_hours" placeholder="Hours/mo">
                                <input type="number" step="0.01" name="retainer_value" placeholder="Value">
                                <input name="brand_vibe" placeholder="Brand vibe (emoji ok!)">
                                <button class="primary-btn" type="submit">Save client</button>
                            </div>
                        </form>
                    </section>

                    <section class="card">
                        <h2>Weekly capacity planner <span>🧠</span></h2>
                        <div class="heatmap">
                            <?php foreach ($heatmap as $slot): 
                                $planned = (int) $slot['planned_minutes'];
                                $actual = (int) $slot['minutes_logged'];
                                $utilization = $planned ? min(100, round(($actual / $planned) * 100)) : 0;
                                ?>
                                <div class="heatmap-row">
                                    <div class="heatmap-avatar" style="--accent: <?= htmlspecialchars($slot['focus_color']) ?>;"></div>
                                    <div class="heatmap-info">
                                        <strong><?= htmlspecialchars($slot['name']) ?></strong>
                                        <span class="muted small"><?= htmlspecialchars($slot['title'] ?? '') ?></span>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?= $utilization ?>%"></div>
                                        </div>
                                        <div class="muted tiny">Planned <?= format_duration($planned) ?> • Logged <?= format_duration($actual) ?> • Meetings <?= format_duration((int) $slot['meeting_minutes']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="post" class="inline-form" style="margin-top:1rem;">
                            <input type="hidden" name="action" value="log_capacity">
                            <h3 class="form-title">Adjust capacity</h3>
                            <div class="form-grid">
                                <select name="capacity_user" required>
                                    <option value="">Select teammate</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" name="week_start" value="<?= $weekStart->format('Y-m-d') ?>" required>
                                <input type="number" name="planned_minutes" placeholder="Planned minutes" required>
                                <input type="number" name="meeting_minutes" placeholder="Meeting minutes" required>
                                <input name="focus_theme" placeholder="Focus theme">
                                <button class="primary-btn" type="submit">Update</button>
                            </div>
                        </form>
                    </section>
                </div>

                <section class="card">
                    <h2>Pipeline board <span>🗺️</span></h2>
                    <div class="pipeline-grid">
                        <?php foreach ($pipeline as $column): ?>
                            <div class="pipeline-column">
                                <header>
                                    <h3><?= ucfirst($column['pipeline_stage']) ?></h3>
                                    <span class="muted tiny"><?= (int) $column['total'] ?> engagements</span>
                                </header>
                                <?php foreach ($projects as $project): ?>
                                    <?php if ($project['pipeline_stage'] !== $column['pipeline_stage']) { continue; } ?>
                                    <article class="pipeline-card status-<?= htmlspecialchars($project['health']) ?>">
                                        <h4><?= htmlspecialchars($project['name']) ?></h4>
                                        <p class="muted small">Client <?= htmlspecialchars($project['client_name'] ?? 'Internal') ?></p>
                                        <div class="tiny">Budget <?= $project['budget_hours'] ? $project['budget_hours'] . 'h' : '—' ?> • <?= format_currency($project['budget_amount']) ?></div>
                                        <div class="tiny">Tasks <?= (int) $project['task_done'] ?>/<?= (int) $project['task_total'] ?> • Logged <?= format_duration((int) $project['minutes_spent']) ?></div>
                                        <div class="tags">
                                            <span><?= ucfirst($project['priority']) ?></span>
                                            <span><?= ucfirst(str_replace('_',' ', $project['status'])) ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="card">
                    <h2>Team updates <span>📰</span></h2>
                    <div class="grid two-col">
                        <div>
                            <h3 class="subhead">Mood &amp; energy pulses</h3>
                            <ul class="pulse-feed">
                                <?php foreach ($recentCheckins as $checkin): ?>
                                    <li>
                                        <span class="dot" style="--accent: <?= htmlspecialchars($checkin['focus_color']) ?>;"></span>
                                        <div>
                                            <strong><?= htmlspecialchars($checkin['name']) ?></strong>
                                            <div class="muted tiny">Energy <?= (int) $checkin['energy_level'] ?>/10 • <?= ucfirst($checkin['mood']) ?> • <?= (new DateTimeImmutable($checkin['created_at']))->format('M d H:i') ?></div>
                                            <?php if ($checkin['focus_goal']): ?><div class="tiny">Focus: <?= htmlspecialchars($checkin['focus_goal']) ?></div><?php endif; ?>
                                            <?php if ($checkin['blockers']): ?><div class="tiny warning">Blocker: <?= htmlspecialchars($checkin['blockers']) ?></div><?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div>
                            <h3 class="subhead">Escalated blockers</h3>
                            <ul class="pulse-feed">
                                <?php foreach ($blockers as $item): ?>
                                    <li>
                                        <span class="dot warning"></span>
                                        <div>
                                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                                            <div class="muted tiny"><?= (new DateTimeImmutable($item['created_at']))->format('M d H:i') ?> • <?= htmlspecialchars($item['project_name'] ?? 'General') ?></div>
                                            <div class="tiny warning"><?= htmlspecialchars($item['blockers']) ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (!$blockers): ?>
                                    <li class="muted tiny">No blockers flagged this week 🎉</li>
                                <?php endif; ?>
                            </ul>
                            <h3 class="subhead" style="margin-top:1.5rem;">Workload spotlight</h3>
                            <ul class="pulse-feed">
                                <?php foreach ($teamFocus as $focus): ?>
                                    <li>
                                        <span class="dot" style="--accent: <?= htmlspecialchars($focus['focus_color']) ?>;"></span>
                                        <div>
                                            <strong><?= htmlspecialchars($focus['name']) ?></strong>
                                            <div class="muted tiny">Active tasks <?= (int) $focus['active_tasks'] ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </section>

                <div class="grid two-col">
                    <section class="card">
                        <h2>Project milestones <span>🗓️</span></h2>
                        <?php foreach ($projects as $project): ?>
                            <div class="milestone-group">
                                <header>
                                    <strong><?= htmlspecialchars($project['name']) ?></strong>
                                    <span class="muted tiny">Due <?= $project['due_date'] ?: '—' ?></span>
                                </header>
                                <ul class="milestone-list">
                                    <?php foreach ($milestones[$project['id']] ?? [] as $milestone): ?>
                                        <li>
                                            <span class="status-pill status-<?= htmlspecialchars($milestone['status']) ?>"><?= ucfirst(str_replace('_',' ', $milestone['status'])) ?></span>
                                            <div>
                                                <strong><?= htmlspecialchars($milestone['title']) ?></strong>
                                                <div class="muted tiny">Owner <?= htmlspecialchars($milestone['owner_name'] ?? '—') ?> • <?= $milestone['due_date'] ?: 'No due date' ?></div>
                                                <?php if ($milestone['highlight']): ?><div class="tiny"><?= htmlspecialchars($milestone['highlight']) ?></div><?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($milestones[$project['id']] ?? [])): ?>
                                        <li class="muted tiny">No milestones logged yet.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                        <form method="post" class="inline-form" style="margin-top:1rem;">
                            <input type="hidden" name="action" value="create_milestone">
                            <h3 class="form-title">Add milestone</h3>
                            <div class="form-grid">
                                <select name="milestone_project" required>
                                    <option value="">Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input name="milestone_title" placeholder="Milestone" required>
                                <select name="milestone_owner">
                                    <option value="">Owner</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="milestone_status">
                                    <option value="planned">Planned</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="complete">Complete</option>
                                    <option value="blocked">Blocked</option>
                                </select>
                                <input type="date" name="milestone_due">
                                <input name="milestone_highlight" placeholder="Highlight">
                                <button class="primary-btn" type="submit">Log milestone</button>
                            </div>
                        </form>
                    </section>

                    <section class="card">
                        <h2>Meeting notes &amp; idea bank <span>💡</span></h2>
                        <div class="notes-stack">
                            <?php foreach ($meetingNotes as $note): ?>
                                <article class="note-card">
                                    <header>
                                        <strong><?= htmlspecialchars($note['project_name']) ?></strong>
                                        <span class="muted tiny"><?= (new DateTimeImmutable($note['note_date']))->format('M d') ?></span>
                                    </header>
                                    <p><?= nl2br(htmlspecialchars($note['summary'])) ?></p>
                                    <?php if ($note['next_steps']): ?><p class="muted tiny">Next: <?= nl2br(htmlspecialchars($note['next_steps'])) ?></p><?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <form method="post" class="inline-form" style="margin-top:1rem;">
                            <input type="hidden" name="action" value="log_meeting_note">
                            <h3 class="form-title">Capture meeting</h3>
                            <div class="form-grid">
                                <select name="meeting_project" required>
                                    <option value="">Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" name="note_date" value="<?= $today->format('Y-m-d') ?>" required>
                                <textarea name="summary" placeholder="Summary" required></textarea>
                                <textarea name="next_steps" placeholder="Next steps"></textarea>
                                <button class="primary-btn" type="submit">Save note</button>
                            </div>
                        </form>
                        <div class="idea-strip">
                            <h3 class="subhead">Fresh ideas</h3>
                            <ul>
                                <?php foreach ($ideas as $idea): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($idea['title']) ?></strong>
                                        <span class="muted tiny">Impact <?= ucfirst($idea['impact']) ?> • Effort <?= ucfirst($idea['effort']) ?></span>
                                        <?php if ($idea['project_name']): ?><div class="tiny">Project <?= htmlspecialchars($idea['project_name']) ?></div><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </section>
                </div>

                <section class="card">
                    <h2>Project control center <span>🛠️</span></h2>
                    <div class="grid two-col">
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="create_project">
                            <h3 class="form-title">Launch project</h3>
                            <div class="form-grid">
                                <input name="project_name" placeholder="Project name" required>
                                <textarea name="project_description" placeholder="Description"></textarea>
                                <select name="manager_id">
                                    <option value="">Project manager</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?= $manager['id'] ?>"><?= htmlspecialchars($manager['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="client_id">
                                    <option value="">Client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="pipeline_stage">
                                    <option value="pitch">Pitch</option>
                                    <option value="discovery">Discovery</option>
                                    <option value="production" selected>Production</option>
                                    <option value="review">Review</option>
                                    <option value="launch">Launch</option>
                                    <option value="retainer">Retainer</option>
                                </select>
                                <select name="status">
                                    <option value="not_started">Not started</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="at_risk">At risk</option>
                                    <option value="complete">Complete</option>
                                </select>
                                <select name="health">
                                    <option value="thriving">Thriving</option>
                                    <option value="steady" selected>Steady</option>
                                    <option value="watch">Watch</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <select name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <input type="date" name="start_date">
                                <input type="date" name="due_date">
                                <input type="number" name="budget_hours" placeholder="Budget hours">
                                <input type="number" step="0.01" name="budget_amount" placeholder="Budget amount">
                                <input name="creative_theme" placeholder="Creative theme">
                                <button class="primary-btn" type="submit">Create project</button>
                            </div>
                        </form>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="create_task">
                            <h3 class="form-title">Assign task</h3>
                            <div class="form-grid">
                                <select name="project_id" required>
                                    <option value="">Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="assigned_to" required>
                                    <option value="">Assignee</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input name="task_title" placeholder="Task title" required>
                                <textarea name="task_description" placeholder="Description"></textarea>
                                <input type="number" name="estimated_minutes" placeholder="Estimate (minutes)">
                                <input type="date" name="task_due_date">
                                <select name="task_status">
                                    <option value="not_started">Not started</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="blocked">Blocked</option>
                                    <option value="complete">Complete</option>
                                </select>
                                <button class="primary-btn" type="submit">Create task</button>
                            </div>
                        </form>
                    </div>
                </section>

            <?php elseif ($user['role'] === 'manager'): ?>
                <section class="stat-grid">
                    <div class="stat-card">
                        <h3>Projects owning</h3>
                        <div class="value"><?= $activeProjects ?></div>
                        <p class="muted">Live engagements</p>
                    </div>
                    <div class="stat-card">
                        <h3>Tasks in flight</h3>
                        <div class="value"><?= $tasksInFlight ?></div>
                        <p class="muted">Across your pod</p>
                    </div>
                    <div class="stat-card">
                        <h3>Hours logged (7d)</h3>
                        <div class="value"><?= $hoursWeek ?></div>
                        <p class="muted">Team output</p>
                    </div>
                    <div class="stat-card">
                        <h3>Pipeline slots</h3>
                        <div class="value"><?= array_sum(array_column($pipeline, 'total')) ?></div>
                        <p class="muted">Across stages</p>
                    </div>
                </section>

                <section class="card">
                    <h2>Pipeline glance <span>🛰️</span></h2>
                    <div class="pipeline-grid">
                        <?php foreach ($pipeline as $column): ?>
                            <div class="pipeline-column">
                                <header>
                                    <h3><?= ucfirst($column['pipeline_stage']) ?></h3>
                                    <span class="muted tiny">Needs eyes: <?= (int) $column['needs_attention'] ?></span>
                                </header>
                                <?php foreach ($projects as $project): ?>
                                    <?php if ($project['pipeline_stage'] !== $column['pipeline_stage']) { continue; } ?>
                                    <article class="pipeline-card status-<?= htmlspecialchars($project['health']) ?>">
                                        <h4><?= htmlspecialchars($project['name']) ?></h4>
                                        <div class="muted tiny">Due <?= $project['due_date'] ?: '—' ?></div>
                                        <div class="tiny">Tasks <?= (int) $project['task_done'] ?>/<?= (int) $project['task_total'] ?> • Logged <?= format_duration((int) $project['minutes_spent']) ?></div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="grid two-col">
                    <section class="card">
                        <h2>Milestones &amp; blockers <span>🚦</span></h2>
                        <?php foreach ($projects as $project): ?>
                            <div class="milestone-group">
                                <header>
                                    <strong><?= htmlspecialchars($project['name']) ?></strong>
                                    <span class="muted tiny">Health <?= ucfirst($project['health']) ?></span>
                                </header>
                                <ul class="milestone-list">
                                    <?php foreach ($milestones[$project['id']] ?? [] as $milestone): ?>
                                        <li>
                                            <span class="status-pill status-<?= htmlspecialchars($milestone['status']) ?>"><?= ucfirst(str_replace('_',' ', $milestone['status'])) ?></span>
                                            <div>
                                                <strong><?= htmlspecialchars($milestone['title']) ?></strong>
                                                <div class="muted tiny">Owner <?= htmlspecialchars($milestone['owner_name'] ?? '—') ?> • <?= $milestone['due_date'] ?: 'No due date' ?></div>
                                                <?php if ($milestone['highlight']): ?><div class="tiny"><?= htmlspecialchars($milestone['highlight']) ?></div><?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                        <form method="post" class="inline-form" style="margin-top:1rem;">
                            <input type="hidden" name="action" value="create_milestone">
                            <h3 class="form-title">Plan milestone</h3>
                            <div class="form-grid">
                                <select name="milestone_project" required>
                                    <option value="">Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input name="milestone_title" placeholder="Milestone" required>
                                <select name="milestone_owner">
                                    <option value="">Owner</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" name="milestone_due">
                                <input name="milestone_highlight" placeholder="Highlight">
                                <select name="milestone_status">
                                    <option value="planned">Planned</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="complete">Complete</option>
                                    <option value="blocked">Blocked</option>
                                </select>
                                <button class="primary-btn" type="submit">Save</button>
                            </div>
                        </form>
                    </section>

                    <section class="card">
                        <h2>Team wellbeing <span>💚</span></h2>
                        <ul class="pulse-feed">
                            <?php foreach ($recentCheckins as $checkin): ?>
                                <li>
                                    <span class="dot" style="--accent: <?= htmlspecialchars($checkin['focus_color']) ?>;"></span>
                                    <div>
                                        <strong><?= htmlspecialchars($checkin['name']) ?></strong>
                                        <div class="muted tiny">Energy <?= (int) $checkin['energy_level'] ?>/10 • <?= ucfirst($checkin['mood']) ?></div>
                                        <?php if ($checkin['focus_goal']): ?><div class="tiny">Focus: <?= htmlspecialchars($checkin['focus_goal']) ?></div><?php endif; ?>
                                        <?php if ($checkin['blockers']): ?><div class="tiny warning">Blocker: <?= htmlspecialchars($checkin['blockers']) ?></div><?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="idea-strip">
                            <h3 class="subhead">Flagged blockers</h3>
                            <ul>
                                <?php foreach ($blockers as $item): ?>
                                    <li class="tiny warning"><?= htmlspecialchars($item['blockers']) ?> — <?= htmlspecialchars($item['name']) ?></li>
                                <?php endforeach; ?>
                                <?php if (!$blockers): ?>
                                    <li class="muted tiny">Clear skies 🌤️</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </section>
                </div>

                <div class="grid two-col">
                    <section class="card">
                        <h2>Meeting notes <span>📝</span></h2>
                        <div class="notes-stack">
                            <?php foreach ($meetingNotes as $note): ?>
                                <article class="note-card">
                                    <header>
                                        <strong><?= htmlspecialchars($note['project_name']) ?></strong>
                                        <span class="muted tiny"><?= (new DateTimeImmutable($note['note_date']))->format('M d') ?></span>
                                    </header>
                                    <p><?= nl2br(htmlspecialchars($note['summary'])) ?></p>
                                    <?php if ($note['next_steps']): ?><p class="muted tiny">Next: <?= nl2br(htmlspecialchars($note['next_steps'])) ?></p><?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <form method="post" class="inline-form" style="margin-top:1rem;">
                            <input type="hidden" name="action" value="log_meeting_note">
                            <h3 class="form-title">Document sync</h3>
                            <div class="form-grid">
                                <select name="meeting_project" required>
                                    <option value="">Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" name="note_date" value="<?= $today->format('Y-m-d') ?>" required>
                                <textarea name="summary" placeholder="Summary" required></textarea>
                                <textarea name="next_steps" placeholder="Next steps"></textarea>
                                <button class="primary-btn" type="submit">Save note</button>
                            </div>
                        </form>
                    </section>

                    <section class="card">
                        <h2>Idea bank <span>🎯</span></h2>
                        <ul class="idea-list">
                            <?php foreach ($ideas as $idea): ?>
                                <?php if ($idea['project_id'] && !in_array($idea['project_id'], $projectIds, true)) { continue; } ?>
                                <li>
                                    <strong><?= htmlspecialchars($idea['title']) ?></strong>
                                    <span class="muted tiny">Impact <?= ucfirst($idea['impact']) ?> • Effort <?= ucfirst($idea['effort']) ?></span>
                                    <?php if ($idea['project_name']): ?><div class="tiny">Project <?= htmlspecialchars($idea['project_name']) ?></div><?php endif; ?>
                                    <?php if ($idea['description']): ?><p class="tiny"><?= htmlspecialchars($idea['description']) ?></p><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <form method="post" class="inline-form" style="margin-top:1rem;">
                            <input type="hidden" name="action" value="create_idea">
                            <h3 class="form-title">Log idea</h3>
                            <div class="form-grid">
                                <select name="idea_project">
                                    <option value="">Project (optional)</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input name="idea_title" placeholder="Idea title" required>
                                <select name="idea_impact">
                                    <option value="low">Low impact</option>
                                    <option value="medium" selected>Medium impact</option>
                                    <option value="high">High impact</option>
                                </select>
                                <select name="idea_effort">
                                    <option value="light">Light effort</option>
                                    <option value="moderate" selected>Moderate effort</option>
                                    <option value="heavy">Heavy effort</option>
                                </select>
                                <textarea name="idea_description" placeholder="Describe the concept"></textarea>
                                <button class="primary-btn" type="submit">Save idea</button>
                            </div>
                        </form>
                    </section>
                </div>

                <section class="card">
                    <h2>Quick task assign <span>🧾</span></h2>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="create_task">
                        <div class="form-grid">
                            <select name="project_id" required>
                                <option value="">Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="assigned_to" required>
                                <option value="">Assignee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input name="task_title" placeholder="Task title" required>
                            <textarea name="task_description" placeholder="Description"></textarea>
                            <input type="number" name="estimated_minutes" placeholder="Estimate (minutes)">
                            <input type="date" name="task_due_date">
                            <select name="task_status">
                                <option value="not_started">Not started</option>
                                <option value="in_progress">In progress</option>
                                <option value="blocked">Blocked</option>
                                <option value="complete">Complete</option>
                            </select>
                            <button class="primary-btn" type="submit">Assign</button>
                        </div>
                    </form>
                </section>

            <?php else: ?>
                <section class="stat-grid">
                    <div class="stat-card">
                        <h3>Weekly hours</h3>
                        <div class="value"><?= $hoursWeek ?></div>
                        <p class="muted">Logged past 7 days</p>
                    </div>
                    <div class="stat-card">
                        <h3>Tasks completed</h3>
                        <div class="value"><?= $completedTasks ?>/<?= $totalTasks ?></div>
                        <p class="muted">Great momentum!</p>
                    </div>
                    <div class="stat-card">
                        <h3>Capacity</h3>
                        <div class="value"><?= format_duration((int) ($user['weekly_capacity_minutes'] ?? 0)) ?></div>
                        <p class="muted">Weekly goal</p>
                    </div>
                </section>

                <section class="card">
                    <h2>Focus planner <span>🎯</span></h2>
                    <div class="focus-plan">
                        <?php foreach ($tasks as $task): ?>
                            <article class="task-card status-<?= htmlspecialchars($task['status']) ?>">
                                <header>
                                    <div>
                                        <strong><?= htmlspecialchars($task['title']) ?></strong>
                                        <div class="muted tiny">Project <?= htmlspecialchars($task['project_name']) ?></div>
                                    </div>
                                    <form method="post" class="tiny-form">
                                        <input type="hidden" name="action" value="update_task_status">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="not_started" <?= $task['status']==='not_started'?'selected':'' ?>>Not started</option>
                                            <option value="in_progress" <?= $task['status']==='in_progress'?'selected':'' ?>>In progress</option>
                                            <option value="blocked" <?= $task['status']==='blocked'?'selected':'' ?>>Blocked</option>
                                            <option value="complete" <?= $task['status']==='complete'?'selected':'' ?>>Complete</option>
                                        </select>
                                    </form>
                                </header>
                                <p><?= htmlspecialchars($task['description']) ?></p>
                                <div class="tiny">Est <?= $task['estimated_minutes'] ? format_duration((int) $task['estimated_minutes']) : '—' ?> • Logged <?= format_duration((int) $task['minutes_spent']) ?></div>
                                <div class="tiny">Due <?= $task['due_date'] ?: '—' ?> • Priority <?= ucfirst($task['priority']) ?></div>
                                <div class="actions">
                                    <?php if ($task['status'] !== 'complete'): ?>
                                        <?php if ($task['active_entry_id']): ?>
                                            <button class="timer-btn stop" data-task-id="<?= $task['id'] ?>">Stop timer</button>
                                        <?php else: ?>
                                            <button class="timer-btn" data-task-id="<?= $task['id'] ?>">Start focus timer</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="grid two-col">
                    <section class="card">
                        <h2>Daily pulse <span>💬</span></h2>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="log_checkin">
                            <div class="form-grid">
                                <label>Energy</label>
                                <input type="number" name="energy_level" min="1" max="10" value="<?= $latestCheckin['energy_level'] ?? 6 ?>" required>
                                <label>Mood</label>
                                <select name="mood">
                                    <option value="energized">Energized</option>
                                    <option value="steady" <?= isset($latestCheckin['mood']) && $latestCheckin['mood']==='steady' ? 'selected' : '' ?>>Steady</option>
                                    <option value="stretched" <?= isset($latestCheckin['mood']) && $latestCheckin['mood']==='stretched' ? 'selected' : '' ?>>Stretched</option>
                                    <option value="drained" <?= isset($latestCheckin['mood']) && $latestCheckin['mood']==='drained' ? 'selected' : '' ?>>Drained</option>
                                </select>
                                <textarea name="focus_goal" placeholder="What are you focusing on?"><?= htmlspecialchars($latestCheckin['focus_goal'] ?? '') ?></textarea>
                                <textarea name="blockers" placeholder="Any blockers?"><?= htmlspecialchars($latestCheckin['blockers'] ?? '') ?></textarea>
                                <button class="primary-btn" type="submit">Submit check-in</button>
                            </div>
                        </form>
                        <ul class="pulse-feed" style="margin-top:1rem;">
                            <?php foreach ($recentCheckins as $checkin): ?>
                                <li>
                                    <span class="dot" style="--accent: <?= htmlspecialchars($user['focus_color'] ?? '#5BC0BE') ?>;"></span>
                                    <div>
                                        <div class="muted tiny"><?= (new DateTimeImmutable($checkin['created_at']))->format('M d H:i') ?> • Energy <?= (int) $checkin['energy_level'] ?>/10</div>
                                        <?php if ($checkin['focus_goal']): ?><div class="tiny">Focus: <?= htmlspecialchars($checkin['focus_goal']) ?></div><?php endif; ?>
                                        <?php if ($checkin['blockers']): ?><div class="tiny warning">Blocker: <?= htmlspecialchars($checkin['blockers']) ?></div><?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="card">
                        <h2>Idea sparks <span>✨</span></h2>
                        <ul class="idea-list">
                            <?php foreach ($ideas as $idea): ?>
                                <li>
                                    <strong><?= htmlspecialchars($idea['title']) ?></strong>
                                    <span class="muted tiny">Impact <?= ucfirst($idea['impact']) ?> • Effort <?= ucfirst($idea['effort']) ?></span>
                                    <?php if ($idea['project_name']): ?><div class="tiny">Project <?= htmlspecialchars($idea['project_name']) ?></div><?php endif; ?>
                                    <?php if ($idea['description']): ?><p class="tiny"><?= htmlspecialchars($idea['description']) ?></p><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <form method="post" class="inline-form" style="margin-top:1rem;">
                            <input type="hidden" name="action" value="create_idea">
                            <div class="form-grid">
                                <select name="idea_project">
                                    <option value="">Project (optional)</option>
                                <?php foreach ($tasks as $task): ?>
                                    <option value="<?= $task['project_id'] ?>"><?= htmlspecialchars($task['project_name']) ?></option>
                                <?php endforeach; ?>
                                </select>
                                <input name="idea_title" placeholder="Idea title" required>
                                <select name="idea_impact">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                                <select name="idea_effort">
                                    <option value="light">Light</option>
                                    <option value="moderate" selected>Moderate</option>
                                    <option value="heavy">Heavy</option>
                                </select>
                                <textarea name="idea_description" placeholder="Describe the concept"></textarea>
                                <button class="primary-btn" type="submit">Share idea</button>
                            </div>
                        </form>
                    </section>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="assets/js/timer.js"></script>
</body>
</html>
