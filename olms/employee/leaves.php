<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_role('employee');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $requestId = (int)($_POST['request_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, (int)$user['id']]);
    if ($stmt->rowCount()) {
        notify_user($pdo, (int)$user['id'], 'Leave request cancelled', 'Your pending leave request has been cancelled.', 'warning', $requestId);
        log_activity($pdo, (int)$user['id'], 'leave_cancelled', 'leave_request', $requestId);
        set_flash('success', 'The pending request has been cancelled.');
    } else set_flash('warning', 'Only your pending requests can be cancelled.');
    redirect('employee/leaves.php');
}
$status = (string)($_GET['status'] ?? '');
$allowed = ['pending','approved','rejected','cancelled'];
$sql = 'SELECT lr.*, lt.name AS leave_type_name FROM leave_requests lr JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE lr.user_id = ?';
$params = [(int)$user['id']];
if (in_array($status, $allowed, true)) { $sql .= ' AND lr.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY lr.created_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $requests = $stmt->fetchAll();
$pageTitle = 'Leave history';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">MY LEAVE RECORDS</span><h1>Leave history.</h1><p>Review every request, from submission through the final decision.</p></div><a class="btn btn-primary" href="<?= h(url('employee/apply.php')) ?>">＋ New request</a></div>
<div class="filter-tabs"><a class="<?= $status === '' ? 'selected' : '' ?>" href="<?= h(url('employee/leaves.php')) ?>">All requests</a><?php foreach ($allowed as $option): ?><a class="<?= $status === $option ? 'selected' : '' ?>" href="<?= h(url('employee/leaves.php?status=' . $option)) ?>"><?= h(ucfirst($option)) ?></a><?php endforeach; ?></div>
<section class="panel"><div class="panel-heading"><div><h2><?= $status ? ucfirst($status) . ' requests' : 'All applications' ?></h2><p><?= count($requests) ?> record<?= count($requests) === 1 ? '' : 's' ?> found</p></div></div>
<?php if (!$requests): ?><div class="empty-state"><div class="empty-icon">▤</div><strong>No applications found</strong><p>There are no <?= $status ? h($status) : '' ?> leave requests to show.</p><a class="btn btn-primary btn-small" href="<?= h(url('employee/apply.php')) ?>">Apply for leave</a></div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Request</th><th>Leave type</th><th>Period</th><th>Working days</th><th>Status</th><th>Decision / Action</th></tr></thead><tbody>
<?php foreach ($requests as $request): ?><tr><td><strong><?= h($request['request_code']) ?></strong><small class="cell-sub">Applied <?= format_date($request['created_at']) ?></small></td><td><?= h($request['leave_type_name']) ?></td><td><?= format_date($request['start_date']) ?><small class="cell-sub">to <?= format_date($request['end_date']) ?></small></td><td><?= h(format_days($request['total_days'])) ?></td><td><span class="status-badge status-<?= h(status_class($request['status'])) ?>"><?= h(ucfirst($request['status'])) ?></span></td><td><?php if ($request['admin_remarks']): ?><span class="remark-text" title="<?= h($request['admin_remarks']) ?>"><?= h(mb_strimwidth($request['admin_remarks'], 0, 42, '…')) ?></span><?php elseif ($request['status'] === 'pending'): ?><span class="muted-copy">Awaiting review</span><?php else: ?><span class="muted-copy">No remarks</span><?php endif; ?><?php if ($request['status'] === 'pending'): ?><form method="post" class="inline-form" onsubmit="return confirm('Cancel this pending request?');"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><button class="link-danger" type="submit">Cancel</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
