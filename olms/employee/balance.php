<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_role('employee');
$year = (int)($_GET['year'] ?? date('Y'));
if ($year < 2000 || $year > 2100) $year = (int)date('Y');
$balances = get_leave_balances($pdo, (int)$user['id'], $year);
$pendingStmt = $pdo->prepare("SELECT leave_type_id, COALESCE(SUM(total_days),0) AS pending_days FROM leave_requests WHERE user_id = ? AND status = 'pending' AND YEAR(start_date) = ? GROUP BY leave_type_id");
$pendingStmt->execute([(int)$user['id'], $year]);
$pendingMap = [];
foreach ($pendingStmt->fetchAll() as $row) $pendingMap[(int)$row['leave_type_id']] = (float)$row['pending_days'];
$pageTitle = 'Leave balance';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">YOUR LEAVE ENTITLEMENT</span><h1>Know where you stand.</h1><p>Your allocated, used and remaining leave days in one view.</p></div><div class="year-select"><a class="btn btn-outline btn-small" href="<?= h(url('employee/balance.php?year=' . ($year - 1))) ?>">← <?= $year - 1 ?></a><span><?= $year ?></span><a class="btn btn-outline btn-small" href="<?= h(url('employee/balance.php?year=' . ($year + 1))) ?>"><?= $year + 1 ?> →</a></div></div>
<div class="balance-cards-grid">
<?php if (!$balances): ?><section class="panel"><div class="empty-state"><strong>No leave policies configured</strong><p>Ask your administrator to activate leave types.</p></div></section><?php else: foreach ($balances as $balance): $pending = $pendingMap[(int)$balance['leave_type_id']] ?? 0; $remaining = max(0, (float)$balance['remaining_days']); $availableAfterPending = max(0, $remaining - $pending); $quota = (float)$balance['allocated_days']; $usedPercent = $quota > 0 ? min(100, ((float)$balance['used_days'] / $quota) * 100) : 0; ?>
<article class="panel balance-detail-card"><div class="balance-detail-head"><div><span class="leave-code"><?= h($balance['code']) ?></span><h2><?= h($balance['name']) ?></h2></div><span class="stat-icon indigo">◷</span></div><div class="balance-big-number"><?= h(format_days($remaining)) ?><small>remaining before pending requests</small></div><div class="progress-track large"><span style="width:<?= h((string)$usedPercent) ?>%"></span></div><div class="balance-breakdown"><div><small>Allocated</small><strong><?= h(format_days($quota)) ?></strong></div><div><small>Used</small><strong><?= h(format_days($balance['used_days'])) ?></strong></div><div><small>Pending</small><strong><?= h(format_days($pending)) ?></strong></div></div><div class="available-note">Available after pending requests <strong><?= h(format_days($availableAfterPending)) ?></strong></div></article>
<?php endforeach; endif; ?></div>
<div class="form-hint balance-note"><span>ⓘ</span><p>Only approved requests reduce the official used balance. Pending requests are shown separately and temporarily considered when validating new applications.</p></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
