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

$managerFilter = $user['role'] === 'manager' ? (int) $user['id'] : null;

sync_automation_alerts($pdo, $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'));

$summary = get_team_time_summary($pdo, $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'), $employeeId ?: null);
$clients = get_clients_with_metrics($pdo, $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'));
$pipeline = get_pipeline_distribution($pdo, $managerFilter);
$energyFeed = get_recent_checkins($pdo, 40, $employeeId ?: null);
$avgEnergy = compute_focus_score($energyFeed);
$financials = get_profitability_overview($pdo, $managerFilter);
$invoicePipeline = get_invoice_pipeline($pdo, $managerFilter);
$freelancers = get_freelancers($pdo);
$freelancerAssignments = get_freelancer_assignments($pdo, $managerFilter);
$alerts = get_active_alerts($pdo, $managerFilter);

$financialTotals = [
    'invoiced' => array_sum(array_column($financials, 'invoice_total')),
    'paid' => array_sum(array_column($financials, 'invoice_paid')),
    'expenses' => array_sum(array_column($financials, 'expense_total')),
    'internal_cost' => array_sum(array_column($financials, 'internal_cost')),
];
$financialTotals['outstanding'] = $financialTotals['invoiced'] - $financialTotals['paid'];
$financialTotals['margin'] = $financialTotals['paid'] - ($financialTotals['expenses'] + $financialTotals['internal_cost']);

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

$baseQuery = $_GET;
unset($baseQuery['export'], $baseQuery['dataset']);

$exportType = $_GET['export'] ?? null;
if ($exportType === 'csv') {
    $dataset = $_GET['dataset'] ?? 'time';
    $filenameParts = ['foxhole'];
    $filenameParts[] = $dataset;
    $filenameParts[] = $range;
    if ($employeeId) {
        $filenameParts[] = 'user-' . $employeeId;
    }
    $filenameParts[] = $startDate->format('Ymd') . '-' . $endDate->format('Ymd');
    $filename = implode('_', $filenameParts) . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    $sanitizeCsv = static function ($value) {
        if (is_string($value)) {
            $value = preg_replace("/\r|\n/", ' ', $value);
            $trimmed = ltrim($value);
            if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
                $value = "'" . $value;
            }
        }
        return $value;
    };
    $writeRow = static function ($handle, array $row) use ($sanitizeCsv): void {
        fputcsv($handle, array_map($sanitizeCsv, $row));
    };

    $writeRow($output, ['Foxhole export generated', (new DateTimeImmutable())->format(DateTimeInterface::ATOM)]);
    fputcsv($output, []);

    switch ($dataset) {
        case 'projects':
            $writeRow($output, ['Project', 'Status', 'Due date', 'Completed tasks', 'Total tasks', 'Hours logged']);
            foreach ($projectRows as $project) {
                $hours = round(((int) $project['minutes_spent']) / 60, 2);
                $completed = (int) $project['completed_tasks'];
                $total = (int) $project['total_tasks'];
                $due = $project['due_date'] ? (new DateTimeImmutable($project['due_date']))->format('Y-m-d') : '—';
                $writeRow($output, [
                    $project['name'],
                    $project['status'],
                    $due,
                    $completed,
                    $total,
                    $hours,
                ]);
            }
            break;
        case 'financial':
            $writeRow($output, ['Project', 'Client', 'Invoice total', 'Invoice paid', 'Outstanding', 'Expenses', 'Internal cost', 'Margin']);
            foreach ($financials as $row) {
                $outstanding = ($row['invoice_total'] ?? 0) - ($row['invoice_paid'] ?? 0);
                $margin = ($row['invoice_paid'] ?? 0) - (($row['expense_total'] ?? 0) + ($row['internal_cost'] ?? 0));
                $writeRow($output, [
                    $row['project_name'],
                    $row['client_name'],
                    number_format((float) ($row['invoice_total'] ?? 0), 2, '.', ''),
                    number_format((float) ($row['invoice_paid'] ?? 0), 2, '.', ''),
                    number_format((float) $outstanding, 2, '.', ''),
                    number_format((float) ($row['expense_total'] ?? 0), 2, '.', ''),
                    number_format((float) ($row['internal_cost'] ?? 0), 2, '.', ''),
                    number_format((float) $margin, 2, '.', ''),
                ]);
            }
            break;
        case 'time':
        default:
            $writeRow($output, ['Name', 'Role', 'Title', 'Hours logged', 'Entries']);
            foreach ($summary as $row) {
                $hours = round(((int) $row['minutes']) / 60, 2);
                $writeRow($output, [
                    $row['name'],
                    ucfirst($row['role']),
                    $row['title'] ?? '—',
                    $hours,
                    $row['entry_count'] ?? 0,
                ]);
            }
            break;
    }

    fclose($output);
    exit;
}
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
                <?php
                    $buildExportUrl = static function (array $params): string {
                        $query = http_build_query($params);
                        return $query ? 'reports.php?' . $query : 'reports.php';
                    };

                    $timeExportUrl = $buildExportUrl(array_merge($baseQuery, ['export' => 'csv', 'dataset' => 'time']));
                    $projectsExportUrl = $buildExportUrl(array_merge($baseQuery, ['export' => 'csv', 'dataset' => 'projects']));
                    $financialExportUrl = $buildExportUrl(array_merge($baseQuery, ['export' => 'csv', 'dataset' => 'financial']));
                ?>
                <div class="export-actions">
                    <a class="ghost-btn" href="<?= htmlspecialchars($timeExportUrl, ENT_QUOTES) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3a1 1 0 0 1 1 1v8.59l1.3-1.3a1 1 0 0 1 1.4 1.42l-3 3a1 1 0 0 1-1.4 0l-3-3a1 1 0 0 1 1.4-1.42L11 12.59V4a1 1 0 0 1 1-1Zm-7 14a1 1 0 0 1 1-1h3a1 1 0 0 1 0 2H6v2h12v-2h-3a1 1 0 0 1 0-2h3a3 3 0 0 1 3 3v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a3 3 0 0 1 1-2.24V17Z"/></svg>
                        Export time summary
                    </a>
                    <a class="ghost-btn" href="<?= htmlspecialchars($projectsExportUrl, ENT_QUOTES) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 3h14a2 2 0 0 1 2 2v13.5A2.5 2.5 0 0 1 18.5 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 2v12h13V5H5Zm2 3h5a1 1 0 1 1 0 2H7a1 1 0 0 1 0-2Zm0 4h8a1 1 0 1 1 0 2H7a1 1 0 0 1 0-2Z"/></svg>
                        Export project rollup
                    </a>
                    <a class="ghost-btn" href="<?= htmlspecialchars($financialExportUrl, ENT_QUOTES) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 5a1 1 0 0 1 1-1h2.5a1 1 0 0 1 .98.8L8.86 6H19a1 1 0 0 1 .96 1.27l-2.5 8.76A3 3 0 0 1 14.58 18H9.1a3 3 0 0 1-2.9-2.26L4.23 6.59A1 1 0 0 1 5 5Zm4.4 11H14.6a1 1 0 0 0 .96-.74L17.8 8H9.44l-1.2 6.26A1 1 0 0 0 8.4 16ZM9 20a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2h-4a1 1 0 0 1-1-1Z"/></svg>
                        Export financials
                    </a>
                </div>
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
                            <th>Entries</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= ucfirst($row['role']) ?></td>
                                <td><?= htmlspecialchars($row['title'] ?? '—') ?></td>
                                <td><?= number_format($row['minutes'] / 60, 2) ?></td>
                                <td><?= (int) ($row['entry_count'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($summary)): ?>
                            <tr><td colspan="5" style="color:var(--muted);">No time entries yet.</td></tr>
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
                <h2>Financial performance <span>💰</span></h2>
                <div class="chip-row" style="margin-bottom:1rem;">
                    <span class="chip">Paid <?= format_currency($financialTotals['paid']) ?></span>
                    <span class="chip">Outstanding <?= format_currency($financialTotals['outstanding']) ?></span>
                    <span class="chip">Expenses <?= format_currency($financialTotals['expenses']) ?></span>
                    <span class="chip">Margin <?= format_currency($financialTotals['margin']) ?></span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Client</th>
                            <th>Paid</th>
                            <th>Outstanding</th>
                            <th>Total cost</th>
                            <th>Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($financials as $row):
                            $outstanding = $row['invoice_total'] - $row['invoice_paid'];
                            $marginPercent = $row['invoice_paid'] ? ($row['realized_margin'] / $row['invoice_paid']) * 100 : null;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['client_name'] ?? 'Internal') ?></td>
                                <td><?= format_currency((float) $row['invoice_paid']) ?></td>
                                <td><?= format_currency((float) $outstanding) ?></td>
                                <td><?= format_currency((float) $row['total_cost']) ?></td>
                                <td>
                                    <?= format_currency((float) $row['realized_margin']) ?>
                                    <div class="muted tiny"><?= $marginPercent ? number_format($marginPercent, 0) : '—' ?>%</div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($financials)): ?>
                            <tr><td colspan="6" class="muted">No projects to report.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-top:2rem;">
                <h2>Invoice pipeline <span>🧾</span></h2>
                <ul class="invoice-pipeline">
                    <?php foreach ($invoicePipeline as $invoice): ?>
                        <li class="invoice <?= 'status-' . htmlspecialchars($invoice['status']) ?>">
                            <div style="display:flex; justify-content: space-between; gap:1rem;">
                                <div>
                                    <strong><?= htmlspecialchars($invoice['client_name']) ?></strong>
                                    <?php if ($invoice['project_name']): ?><div class="tiny muted">Project <?= htmlspecialchars($invoice['project_name']) ?></div><?php endif; ?>
                                </div>
                                <div class="tiny">Due <?= $invoice['due_date'] ?: '—' ?> • <?= ucfirst($invoice['status']) ?></div>
                            </div>
                            <div class="muted tiny">Amount <?= format_currency((float) $invoice['amount']) ?> • Issued <?= (new DateTimeImmutable($invoice['issue_date']))->format('M d, Y') ?></div>
                            <?php if ($invoice['notes']): ?><div class="tiny">Note: <?= htmlspecialchars($invoice['notes']) ?></div><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($invoicePipeline)): ?>
                        <li class="muted tiny">No invoices logged for this range.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="card" style="margin-top:2rem;">
                <h2>Freelancer network <span>🤝</span></h2>
                <div class="grid two-col" style="gap:1.5rem;">
                    <div>
                        <h3 class="subhead">Roster</h3>
                        <ul class="freelancer-list">
                            <?php foreach ($freelancers as $freelancer): ?>
                                <li class="freelancer-card status-<?= htmlspecialchars($freelancer['status']) ?>">
                                    <div>
                                        <strong><?= htmlspecialchars($freelancer['name']) ?></strong>
                                        <div class="muted tiny"><?= htmlspecialchars($freelancer['specialty'] ?? 'Multi-disciplinary') ?></div>
                                        <div class="tiny">Rate <?= format_rate($freelancer['hourly_rate'] ? (float) $freelancer['hourly_rate'] : null) ?></div>
                                        <?php if ($freelancer['available_from']): ?><div class="tiny">Next open <?= (new DateTimeImmutable($freelancer['available_from']))->format('M d') ?></div><?php endif; ?>
                                    </div>
                                    <span class="badge severity-<?= $freelancer['status'] === 'available' ? 'info' : ($freelancer['status'] === 'booked' ? 'watch' : 'urgent') ?>"><?= ucfirst($freelancer['status']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div>
                        <h3 class="subhead">Assignments</h3>
                        <ul class="assignment-list">
                            <?php foreach ($freelancerAssignments as $assignment): ?>
                                <li>
                                    <strong><?= htmlspecialchars($assignment['freelancer_name']) ?></strong>
                                    <div class="muted tiny"><?= htmlspecialchars($assignment['project_name']) ?> • <?= htmlspecialchars($assignment['role'] ?? 'Contributor') ?></div>
                                    <div class="tiny"><?= $assignment['start_date'] ?: 'TBD' ?> → <?= $assignment['end_date'] ?: 'TBD' ?> • <?= $assignment['committed_hours'] ? $assignment['committed_hours'] . 'h' : 'open scope' ?></div>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($freelancerAssignments)): ?>
                                <li class="muted tiny">No active assignments in this window.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:2rem;">
                <h2>Automation alerts <span>🚨</span></h2>
                <?php if (!empty($alerts)): ?>
                    <ul class="alert-list">
                        <?php foreach ($alerts as $alert): ?>
                            <?php $context = $alert['project_name'] ?? ($alert['client_name'] ?? 'General'); ?>
                            <li>
                                <span class="badge severity-<?= htmlspecialchars($alert['severity']) ?>"><?= ucfirst($alert['severity']) ?></span>
                                <div>
                                    <strong><?= htmlspecialchars($context) ?></strong>
                                    <p class="muted small"><?= htmlspecialchars($alert['message']) ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No open alerts detected for this range.</p>
                <?php endif; ?>
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
