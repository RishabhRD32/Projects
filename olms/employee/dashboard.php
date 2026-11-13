<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_role('employee');
ensure_user_balances($pdo, (int)$user['id']);
$balances = get_leave_balances($pdo, (int)$user['id']);
$counts = [];
foreach (['pending','approved','rejected'] as $status) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM leave_requests WHERE user_id = ? AND status = ?');
    $stmt->execute([(int)$user['id'], $status]);
    $counts[$status] = (int)$stmt->fetchColumn();
}
$stmt = $pdo->prepare('SELECT lr.*, lt.name AS leave_type_name FROM leave_requests lr JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE lr.user_id = ? ORDER BY lr.created_at DESC LIMIT 5');
$stmt->execute([(int)$user['id']]);
$recent = $stmt->fetchAll();
$pendingReminder = $counts['pending'];
$pageTitle = 'My dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">EMPLOYEE WORKSPACE</span><h1>Good to see you, <?= h(explode(' ', $user['full_name'])[0]) ?>.</h1><p>Here’s a quick look at your leave and upcoming plans.</p></div><a class="btn btn-primary" href="<?= h(url('employee/apply.php')) ?>">＋ Apply for leave</a></div>
<?php if ($pendingReminder > 0): ?><div class="notice-banner"><span class="notice-icon">◷</span><div><strong>You have <?= $pendingReminder ?> request<?= $pendingReminder === 1 ? '' : 's' ?> awaiting review.</strong><p>You’ll see an update here as soon as your administrator makes a decision.</p></div><a href="<?= h(url('employee/leaves.php?status=pending')) ?>">View requests →</a></div><?php endif; ?>
<div class="stats-grid stats-four"><article class="stat-card"><div class="stat-top"><span>Total requests</span><span class="stat-icon indigo">▤</span></div><strong><?= array_sum($counts) ?></strong><small>All-time applications</small></article><article class="stat-card"><div class="stat-top"><span>Pending review</span><span class="stat-icon amber">◷</span></div><strong><?= $counts['pending'] ?></strong><small>Awaiting a decision</small></article><article class="stat-card"><div class="stat-top"><span>Approved</span><span class="stat-icon green">✓</span></div><strong><?= $counts['approved'] ?></strong><small>Approved applications</small></article><article class="stat-card"><div class="stat-top"><span>Rejected</span><span class="stat-icon rose">×</span></div><strong><?= $counts['rejected'] ?></strong><small>Reviewed applications</small></article></div>
<div class="content-grid"><section class="panel panel-wide"><div class="panel-heading"><div><h2>Recent leave requests</h2><p>Your latest applications and their status</p></div><a class="text-link" href="<?= h(url('employee/leaves.php')) ?>">View history →</a></div>
<?php if (!$recent): ?><div class="empty-state"><div class="empty-icon">▤</div><strong>No leave requests yet</strong><p>When you apply for leave, your requests will appear here.</p><a class="btn btn-primary btn-small" href="<?= h(url('employee/apply.php')) ?>">Apply for leave</a></div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Request</th><th>Leave type</th><th>Dates</th><th>Days</th><th>Status</th></tr></thead><tbody><?php foreach ($recent as $item): ?><tr><td><strong><?= h($item['request_code']) ?></strong><small class="cell-sub">Submitted <?= format_date($item['created_at']) ?></small></td><td><?= h($item['leave_type_name']) ?></td><td><?= format_date($item['start_date']) ?> – <?= format_date($item['end_date']) ?></td><td><?= h(format_days($item['total_days'])) ?></td><td><span class="status-badge status-<?= h(status_class($item['status'])) ?>"><?= h(ucfirst($item['status'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel balance-panel"><div class="panel-heading"><div><h2>Leave balance</h2><p>Available for <?= date('Y') ?></p></div><a class="text-link" href="<?= h(url('employee/balance.php')) ?>">Details →</a></div>
<?php if (!$balances): ?><div class="empty-state compact"><p>No active leave policies have been configured yet. Contact your administrator.</p></div><?php else: foreach ($balances as $balance): $percentage = (float)$balance['allocated_days'] > 0 ? min(100, ((float)$balance['remaining_days'] / (float)$balance['allocated_days']) * 100) : 0; ?><div class="balance-item"><div class="balance-info"><strong><?= h($balance['name']) ?></strong><span><b><?= h(format_days($balance['remaining_days'])) ?></b> left</span></div><div class="progress-track"><span style="width:<?= h((string)$percentage) ?>%"></span></div><small><?= h(format_days($balance['used_days'])) ?> used of <?= h(format_days($balance['allocated_days'])) ?></small></div><?php endforeach; endif; ?></section></div>
<div class="profile-footnote"><span class="avatar avatar-large"><?= h(strtoupper(substr($user['full_name'], 0, 1))) ?></span><div><strong><?= h($user['full_name']) ?></strong><p><?= h($user['employee_code']) ?> · <?= h($user['department']) ?><?= $user['designation'] ? ' · ' . h($user['designation']) : '' ?></p></div><a class="text-link" href="<?= h(url('employee/notifications.php')) ?>">View notifications →</a></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
