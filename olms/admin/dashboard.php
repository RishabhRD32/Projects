<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');
// Create at most one reminder per request/recipient per rolling 24-hour period.
$stale = $pdo->query("SELECT lr.id, lr.request_code, lr.user_id, lr.start_date, lr.end_date, u.full_name
                       FROM leave_requests lr JOIN users u ON u.id = lr.user_id
                       WHERE lr.status = 'pending' AND lr.created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)
                       ORDER BY lr.created_at ASC LIMIT 100")->fetchAll();
$admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($stale as $request) {
    $recipientIds = array_unique(array_merge([(int)$request['user_id']], array_map('intval', $admins)));
    foreach ($recipientIds as $recipientId) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND leave_request_id = ? AND title = 'Pending leave reminder' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $check->execute([$recipientId, (int)$request['id']]);
        if ((int)$check->fetchColumn() === 0) {
            $message = $recipientId === (int)$request['user_id']
                ? 'Your leave request ' . $request['request_code'] . ' is still awaiting review. Please contact HR if the dates are approaching.'
                : 'Request ' . $request['request_code'] . ' from ' . $request['full_name'] . ' has been pending for more than 3 days.';
            notify_user($pdo, $recipientId, 'Pending leave reminder', $message, 'warning', (int)$request['id']);
        }
    }
}
$employeeCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND status = 'active'")->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();
$approvedThisMonth = (int)$pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND YEAR(reviewed_at) = YEAR(CURDATE()) AND MONTH(reviewed_at) = MONTH(CURDATE())")->fetchColumn();
$teamCount = (int)$pdo->query("SELECT COUNT(DISTINCT department) FROM users WHERE role = 'employee' AND status = 'active'")->fetchColumn();
$recent = $pdo->query("SELECT lr.*, u.full_name, u.employee_code, u.department, lt.name AS leave_type_name
                       FROM leave_requests lr JOIN users u ON u.id = lr.user_id JOIN leave_types lt ON lt.id = lr.leave_type_id
                       ORDER BY lr.created_at DESC LIMIT 7")->fetchAll();
$conflictCount = (int)$pdo->query("SELECT COUNT(*) FROM leave_requests a JOIN leave_requests b ON a.id < b.id AND a.user_id <> b.user_id
                                  JOIN users ua ON ua.id = a.user_id JOIN users ub ON ub.id = b.user_id
                                  WHERE a.status IN ('pending','approved') AND b.status IN ('pending','approved')
                                    AND a.start_date <= b.end_date AND a.end_date >= b.start_date
                                    AND ua.department = ub.department")->fetchColumn();
$pageTitle = 'Admin overview';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">ADMINISTRATOR WORKSPACE</span><h1>Team overview.</h1><p>Monitor leave activity, review requests and keep work coverage on track.</p></div><a class="btn btn-primary" href="<?= h(url('admin/leaves.php?status=pending')) ?>">Review requests <span>→</span></a></div>
<div class="stats-grid stats-four"><article class="stat-card"><div class="stat-top"><span>Active employees</span><span class="stat-icon indigo">♙</span></div><strong><?= $employeeCount ?></strong><small>Across <?= $teamCount ?> department<?= $teamCount === 1 ? '' : 's' ?></small></article><article class="stat-card"><div class="stat-top"><span>Pending requests</span><span class="stat-icon amber">◷</span></div><strong><?= $pendingCount ?></strong><small>Require administrator review</small></article><article class="stat-card"><div class="stat-top"><span>Approved this month</span><span class="stat-icon green">✓</span></div><strong><?= $approvedThisMonth ?></strong><small>Decisions made this month</small></article><article class="stat-card"><div class="stat-top"><span>Team date overlaps</span><span class="stat-icon rose">⇄</span></div><strong><?= $conflictCount ?></strong><small>Pending or approved overlaps</small></article></div>
<div class="content-grid admin-content-grid"><section class="panel panel-wide"><div class="panel-heading"><div><h2>Latest leave requests</h2><p>Recent activity across your organisation</p></div><a class="text-link" href="<?= h(url('admin/leaves.php')) ?>">All requests →</a></div>
<?php if (!$recent): ?><div class="empty-state"><div class="empty-icon">▤</div><strong>No leave activity yet</strong><p>New employee requests will appear here.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Employee</th><th>Leave type</th><th>Period</th><th>Days</th><th>Status</th></tr></thead><tbody><?php foreach ($recent as $row): ?><tr><td><strong><?= h($row['full_name']) ?></strong><small class="cell-sub"><?= h($row['employee_code']) ?> · <?= h($row['department']) ?></small></td><td><?= h($row['leave_type_name']) ?></td><td><?= format_date($row['start_date']) ?><small class="cell-sub">to <?= format_date($row['end_date']) ?></small></td><td><?= h(format_days($row['total_days'])) ?></td><td><a href="<?= h(url('admin/leaves.php?view=' . (int)$row['id'])) ?>" class="status-badge status-<?= h(status_class($row['status'])) ?>"><?= h(ucfirst($row['status'])) ?></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<aside class="panel"><div class="panel-heading"><div><h2>Admin shortcuts</h2><p>Common management tasks</p></div></div><div class="shortcut-list"><a href="<?= h(url('admin/leaves.php?status=pending')) ?>"><span class="shortcut-icon amber">◷</span><span><strong>Review pending leave</strong><small><?= $pendingCount ?> waiting for a decision</small></span><b>→</b></a><a href="<?= h(url('admin/employees.php')) ?>"><span class="shortcut-icon indigo">♙</span><span><strong>Manage employees</strong><small>Profiles and account status</small></span><b>→</b></a><a href="<?= h(url('admin/leave-types.php')) ?>"><span class="shortcut-icon green">◷</span><span><strong>Leave policies</strong><small>Configure annual allocations</small></span><b>→</b></a><a href="<?= h(url('admin/holidays.php')) ?>"><span class="shortcut-icon rose">▦</span><span><strong>Company holidays</strong><small>Exclude non-working days</small></span><b>→</b></a></div></aside></div>
<div class="admin-note"><span>ⓘ</span><p>Pending reminders are generated automatically when an application has been waiting for more than 3 days. This dashboard checks for a 24-hour reminder cooldown.</p></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
