<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $requestId = (int)($_POST['request_id'] ?? 0);
    $decision = (string)($_POST['decision'] ?? '');
    $remarks = trim((string)($_POST['admin_remarks'] ?? ''));
    $errors = [];
    if (!in_array($decision, ['approved','rejected'], true)) $errors[] = 'Choose approve or reject.';
    if ($decision === 'rejected' && $remarks === '') $errors[] = 'Provide a reason when rejecting a request.';
    if (mb_strlen($remarks) > 500) $errors[] = 'Remarks must be 500 characters or fewer.';
    if ($errors) {
        set_flash('danger', implode(' ', $errors));
        redirect('admin/leaves.php?view=' . $requestId);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT lr.*, u.full_name, u.email, lt.name AS leave_type_name FROM leave_requests lr JOIN users u ON u.id = lr.user_id JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE lr.id = ? FOR UPDATE');
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        if (!$request || $request['status'] !== 'pending') throw new RuntimeException('This request is no longer pending and cannot be reviewed again.');

        if ($decision === 'approved') {
            $overlap = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE user_id = ? AND id <> ? AND status = 'approved' AND start_date <= ? AND end_date >= ?");
            $overlap->execute([(int)$request['user_id'], $requestId, $request['end_date'], $request['start_date']]);
            if ((int)$overlap->fetchColumn() > 0) throw new RuntimeException('This employee already has an approved leave request overlapping these dates.');

            $year = (int)date('Y', strtotime($request['start_date']));
            $pdo->prepare('INSERT IGNORE INTO user_leave_balances (user_id, leave_type_id, balance_year, allocated_days, used_days) SELECT ?, id, ?, annual_quota, 0 FROM leave_types WHERE id = ?')
                ->execute([(int)$request['user_id'], $year, (int)$request['leave_type_id']]);
            $balanceStmt = $pdo->prepare('SELECT allocated_days, used_days FROM user_leave_balances WHERE user_id = ? AND leave_type_id = ? AND balance_year = ? FOR UPDATE');
            $balanceStmt->execute([(int)$request['user_id'], (int)$request['leave_type_id'], $year]);
            $balance = $balanceStmt->fetch();
            $available = (float)($balance['allocated_days'] ?? 0) - (float)($balance['used_days'] ?? 0);
            if ((float)$request['total_days'] > $available) throw new RuntimeException('Insufficient leave balance to approve this request. Available: ' . format_days(max(0, $available)) . '.');

            $pdo->prepare('UPDATE user_leave_balances SET used_days = used_days + ? WHERE user_id = ? AND leave_type_id = ? AND balance_year = ?')
                ->execute([(float)$request['total_days'], (int)$request['user_id'], (int)$request['leave_type_id'], $year]);
        }

        $pdo->prepare('UPDATE leave_requests SET status = ?, admin_id = ?, admin_remarks = ?, reviewed_at = NOW() WHERE id = ?')
            ->execute([$decision, (int)$admin['id'], $remarks ?: null, $requestId]);
        $notificationTitle = $decision === 'approved' ? 'Leave request approved' : 'Leave request rejected';
        $notificationMessage = 'Your request ' . $request['request_code'] . ' for ' . $request['leave_type_name'] . ' (' . format_date($request['start_date']) . ' – ' . format_date($request['end_date']) . ') has been ' . $decision . '.';
        if ($remarks !== '') $notificationMessage .= ' Administrator remarks: ' . $remarks;
        notify_user($pdo, (int)$request['user_id'], $notificationTitle, $notificationMessage, $decision === 'approved' ? 'success' : 'danger', $requestId);
        log_activity($pdo, (int)$admin['id'], 'leave_' . $decision, 'leave_request', $requestId, $request['request_code']);
        $pdo->commit();
        set_flash('success', 'Request ' . $request['request_code'] . ' was ' . $decision . '.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('danger', $exception instanceof PDOException ? 'The request could not be processed due to a database error.' : $exception->getMessage());
    }
    redirect('admin/leaves.php');
}
$status = (string)($_GET['status'] ?? '');
$allowed = ['pending','approved','rejected','cancelled'];
$sql = 'SELECT lr.*, u.full_name, u.employee_code, u.department, u.designation, lt.name AS leave_type_name, a.full_name AS reviewer_name FROM leave_requests lr JOIN users u ON u.id = lr.user_id JOIN leave_types lt ON lt.id = lr.leave_type_id LEFT JOIN users a ON a.id = lr.admin_id';
$params = [];
if (in_array($status, $allowed, true)) { $sql .= ' WHERE lr.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY CASE lr.status WHEN \'pending\' THEN 0 ELSE 1 END, lr.created_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $requests = $stmt->fetchAll();
$selected = null; $conflicts = []; $backups = [];
$viewId = (int)($_GET['view'] ?? 0);
if ($viewId) {
    $detailStmt = $pdo->prepare('SELECT lr.*, u.full_name, u.employee_code, u.department, u.designation, u.email, lt.name AS leave_type_name, a.full_name AS reviewer_name FROM leave_requests lr JOIN users u ON u.id = lr.user_id JOIN leave_types lt ON lt.id = lr.leave_type_id LEFT JOIN users a ON a.id = lr.admin_id WHERE lr.id = ?');
    $detailStmt->execute([$viewId]);
    $selected = $detailStmt->fetch() ?: null;
    if ($selected) {
        $conflicts = overlapping_requests($pdo, $selected['start_date'], $selected['end_date'], (int)$selected['id'], (int)$selected['user_id']);
        $backups = backup_suggestions($pdo, (int)$selected['user_id'], $selected['start_date'], $selected['end_date']);
    }
}
$pageTitle = 'Leave requests';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">REQUEST MANAGEMENT</span><h1>Leave requests.</h1><p>Review applications, make decisions and identify team coverage overlaps.</p></div><div class="heading-meta"><span class="meta-count"><?= count($requests) ?> records</span></div></div>
<div class="filter-tabs"><a class="<?= $status === '' ? 'selected' : '' ?>" href="<?= h(url('admin/leaves.php')) ?>">All requests</a><?php foreach ($allowed as $option): ?><a class="<?= $status === $option ? 'selected' : '' ?>" href="<?= h(url('admin/leaves.php?status=' . $option)) ?>"><?= h(ucfirst($option)) ?></a><?php endforeach; ?></div>
<div class="admin-leave-layout"><section class="panel"><div class="panel-heading"><div><h2><?= $status ? ucfirst($status) . ' requests' : 'All applications' ?></h2><p>Select a request to review its details</p></div></div>
<?php if (!$requests): ?><div class="empty-state"><div class="empty-icon">✓</div><strong>No requests in this view</strong><p>New applications will appear here.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Employee</th><th>Leave period</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($requests as $row): ?><tr class="<?= $viewId === (int)$row['id'] ? 'row-selected' : '' ?>"><td><strong><?= h($row['full_name']) ?></strong><small class="cell-sub"><?= h($row['employee_code']) ?> · <?= h($row['department']) ?></small></td><td><?= h($row['leave_type_name']) ?><small class="cell-sub"><?= format_date($row['start_date']) ?> – <?= format_date($row['end_date']) ?></small></td><td><span class="status-badge status-<?= h(status_class($row['status'])) ?>"><?= h(ucfirst($row['status'])) ?></span></td><td><a class="table-action" href="<?= h(url('admin/leaves.php?status=' . urlencode($status) . '&view=' . (int)$row['id'])) ?>">Review →</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<aside class="panel review-panel"><div class="panel-heading"><div><h2>Request review</h2><p><?= $selected ? h($selected['request_code']) : 'Select an application' ?></p></div><?php if ($selected): ?><span class="status-badge status-<?= h(status_class($selected['status'])) ?>"><?= h(ucfirst($selected['status'])) ?></span><?php endif; ?></div>
<?php if (!$selected): ?><div class="empty-state compact"><div class="empty-icon">◉</div><strong>Nothing selected</strong><p>Choose a request from the list to see its details, conflicts and backup suggestions.</p></div><?php else: ?>
<div class="review-employee"><span class="avatar avatar-large"><?= h(strtoupper(substr($selected['full_name'], 0, 1))) ?></span><div><strong><?= h($selected['full_name']) ?></strong><p><?= h($selected['employee_code']) ?> · <?= h($selected['department']) ?></p><small><?= h($selected['designation'] ?: 'Employee') ?> · <?= h($selected['email']) ?></small></div></div>
<div class="detail-grid"><div><small>LEAVE TYPE</small><strong><?= h($selected['leave_type_name']) ?></strong></div><div><small>WORKING DAYS</small><strong><?= h(format_days($selected['total_days'])) ?></strong></div><div><small>START DATE</small><strong><?= format_date($selected['start_date']) ?></strong></div><div><small>END DATE</small><strong><?= format_date($selected['end_date']) ?></strong></div><div class="detail-full"><small>EMPLOYEE REASON</small><p><?= nl2br(h($selected['reason'])) ?></p></div></div>
<div class="coverage-box"><div class="coverage-heading"><strong>Team overlap check</strong><span class="coverage-count <?= $conflicts ? 'has-conflicts' : '' ?>"><?= count($conflicts) ?> overlap<?= count($conflicts) === 1 ? '' : 's' ?></span></div><?php if ($conflicts): ?><p class="coverage-description">Other pending or approved requests overlap these dates:</p><?php foreach ($conflicts as $conflict): ?><div class="coverage-person"><span class="avatar"> <?= h(strtoupper(substr($conflict['full_name'], 0, 1))) ?> </span><div><strong><?= h($conflict['full_name']) ?></strong><small><?= h($conflict['department']) ?> · <?= format_date($conflict['start_date']) ?> – <?= format_date($conflict['end_date']) ?> · <?= h(ucfirst($conflict['status'])) ?></small></div></div><?php endforeach; ?><?php else: ?><p class="coverage-description">No other pending or approved leave request overlaps the selected dates.</p><?php endif; ?>
<div class="backup-suggestions"><strong>Basic backup-planning suggestions</strong><?php if ($backups): ?><p>Active colleagues in the same department without an approved leave overlap:</p><div class="backup-chips"><?php foreach ($backups as $person): ?><span><?= h($person['full_name']) ?> <small><?= h($person['designation'] ?: $person['employee_code']) ?></small></span><?php endforeach; ?></div><?php else: ?><p>No other active colleague is available in this department under the current date check. HR should review coverage manually.</p><?php endif; ?></div></div>
<?php if ($selected['status'] === 'pending'): ?><form method="post" class="decision-form"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$selected['id'] ?>"><label>Administrator remarks<textarea name="admin_remarks" rows="3" maxlength="500" placeholder="Optional for approval; required for rejection"></textarea></label><div class="decision-actions"><button class="btn btn-primary" type="submit" name="decision" value="approved">✓ Approve request</button><button class="btn btn-danger" type="submit" name="decision" value="rejected" onclick="return confirm('Reject this leave request?');">× Reject</button></div></form><?php else: ?><div class="reviewed-note"><strong>Decision: <?= h(ucfirst($selected['status'])) ?></strong><p>Reviewed <?= format_date($selected['reviewed_at']) ?><?= $selected['reviewer_name'] ? ' by ' . h($selected['reviewer_name']) : '' ?>.</p><?php if ($selected['admin_remarks']): ?><p><?= nl2br(h($selected['admin_remarks'])) ?></p><?php endif; ?></div><?php endif; ?>
<?php endif; ?></aside></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
