<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_role('employee');
ensure_user_balances($pdo, (int)$user['id']);
$leaveTypes = $pdo->query('SELECT id, name, code FROM leave_types WHERE is_active = 1 ORDER BY name')->fetchAll();
$errors = [];
$values = ['leave_type_id'=>'','start_date'=>'','end_date'=>'','reason'=>''];
$teamConflicts = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $typeId = (int)$values['leave_type_id'];
    $typeStmt = $pdo->prepare('SELECT id, name FROM leave_types WHERE id = ? AND is_active = 1');
    $typeStmt->execute([$typeId]);
    $leaveType = $typeStmt->fetch();
    if (!$leaveType) $errors[] = 'Select a valid leave type.';
    $start = DateTime::createFromFormat('Y-m-d', $values['start_date']);
    $end = DateTime::createFromFormat('Y-m-d', $values['end_date']);
    if (!$start || $start->format('Y-m-d') !== $values['start_date']) $errors[] = 'Enter a valid start date.';
    if (!$end || $end->format('Y-m-d') !== $values['end_date']) $errors[] = 'Enter a valid end date.';
    if ($start && $end && $end < $start) $errors[] = 'End date cannot be earlier than start date.';
    if ($start && $end && $start->format('Y') !== $end->format('Y')) $errors[] = 'A single leave request must fall within one calendar year so the correct annual balance can be applied.';
    if (mb_strlen($values['reason']) < 5) $errors[] = 'Please provide a reason of at least 5 characters.';
    if (mb_strlen($values['reason']) > 2000) $errors[] = 'Reason must be 2,000 characters or fewer.';

    if (!$errors) {
        $days = working_days($pdo, $values['start_date'], $values['end_date']);
        if ($days <= 0) $errors[] = 'The selected range contains no working days (weekends and configured holidays are excluded).';
        $overlapStmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE user_id = ? AND status IN ('pending','approved') AND start_date <= ? AND end_date >= ?");
        $overlapStmt->execute([(int)$user['id'], $values['end_date'], $values['start_date']]);
        if ((int)$overlapStmt->fetchColumn() > 0) $errors[] = 'This request overlaps another pending or approved leave request of yours.';

        $year = (int)$start->format('Y');
        $balanceStmt = $pdo->prepare('SELECT b.allocated_days, b.used_days FROM user_leave_balances b WHERE b.user_id = ? AND b.leave_type_id = ? AND b.balance_year = ?');
        $balanceStmt->execute([(int)$user['id'], $typeId, $year]);
        $balance = $balanceStmt->fetch();
        if (!$balance) {
            $pdo->prepare('INSERT IGNORE INTO user_leave_balances (user_id, leave_type_id, balance_year, allocated_days, used_days) SELECT ?, id, ?, annual_quota, 0 FROM leave_types WHERE id = ?')->execute([(int)$user['id'], $year, $typeId]);
            $balanceStmt->execute([(int)$user['id'], $typeId, $year]);
            $balance = $balanceStmt->fetch();
        }
        $pendingStmt = $pdo->prepare("SELECT COALESCE(SUM(total_days),0) FROM leave_requests WHERE user_id = ? AND leave_type_id = ? AND status = 'pending' AND YEAR(start_date) = ?");
        $pendingStmt->execute([(int)$user['id'], $typeId, $year]);
        $pendingDays = (float)$pendingStmt->fetchColumn();
        $available = (float)($balance['allocated_days'] ?? 0) - (float)($balance['used_days'] ?? 0) - $pendingDays;
        if ($days > $available) $errors[] = 'Insufficient ' . $leaveType['name'] . ' balance. Available after pending requests: ' . format_days(max(0, $available)) . '; requested: ' . format_days($days) . '.';

        if (!$errors) $teamConflicts = overlapping_requests($pdo, $values['start_date'], $values['end_date'], null, (int)$user['id']);
        if (!$errors) {
            $requestCode = 'LV-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $pdo->prepare("INSERT INTO leave_requests (request_code, user_id, leave_type_id, start_date, end_date, total_days, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$requestCode, (int)$user['id'], $typeId, $values['start_date'], $values['end_date'], $days, $values['reason']]);
            $requestId = (int)$pdo->lastInsertId();
            notify_user($pdo, (int)$user['id'], 'Leave request submitted', 'Your request ' . $requestCode . ' has been submitted and is awaiting administrator review.', 'success', $requestId);
            $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adminId) notify_user($pdo, (int)$adminId, 'New leave request', $user['full_name'] . ' submitted ' . $requestCode . ' for ' . format_date($values['start_date']) . ' – ' . format_date($values['end_date']) . '.', 'info', $requestId);
            log_activity($pdo, (int)$user['id'], 'leave_submitted', 'leave_request', $requestId, $requestCode);
            set_flash('success', 'Your leave request ' . $requestCode . ' has been submitted.');
            redirect('employee/leaves.php');
        }
    }
}
$balanceRows = get_leave_balances($pdo, (int)$user['id']);
$pageTitle = 'Apply for leave';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">LEAVE APPLICATION</span><h1>Request time off.</h1><p>Share the dates and a short reason. Your administrator will review the request.</p></div><a class="btn btn-outline" href="<?= h(url('employee/leaves.php')) ?>">View my requests</a></div>
<div class="apply-layout"><section class="panel apply-panel"><div class="panel-heading"><div><h2>Leave details</h2><p>All fields marked * are required.</p></div><span class="step-chip">NEW REQUEST</span></div>
<?php if ($errors): ?><div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-grid"><?= csrf_field() ?>
<label class="full">Leave type *<select name="leave_type_id" id="leave-type" required><option value="">Choose a leave type</option><?php foreach ($leaveTypes as $type): ?><option value="<?= (int)$type['id'] ?>" <?= (string)$type['id'] === $values['leave_type_id'] ? 'selected' : '' ?>><?= h($type['name']) ?> (<?= h($type['code']) ?>)</option><?php endforeach; ?></select></label>
<label>Start date *<input type="date" name="start_date" id="start-date" min="<?= date('Y') ?>-01-01" value="<?= h($values['start_date']) ?>" required></label>
<label>End date *<input type="date" name="end_date" id="end-date" value="<?= h($values['end_date']) ?>" required></label>
<label class="full">Reason for leave *<textarea name="reason" rows="5" maxlength="2000" required placeholder="Briefly explain the reason for your leave..."><?= h($values['reason']) ?></textarea><small>Keep it clear and concise (5–2,000 characters).</small></label>
<div class="form-hint full"><span>ⓘ</span><p>Working days are calculated automatically. Saturdays, Sundays and holidays configured by HR are not counted. Requests must be within one calendar year.</p></div>
<div class="full form-submit"><button class="btn btn-primary btn-lg" type="submit">Submit leave request <span>→</span></button><a class="btn btn-quiet" href="<?= h(url('employee/dashboard.php')) ?>">Cancel</a></div>
</form></section>
<aside class="panel side-panel"><div class="panel-heading"><div><h2>Your available balance</h2><p><?= date('Y') ?> leave year</p></div></div>
<?php if (!$balanceRows): ?><p class="muted-copy">No leave types are active. Contact your administrator.</p><?php else: foreach ($balanceRows as $row): ?><div class="mini-balance"><div><strong><?= h($row['name']) ?></strong><span><?= h(format_days($row['remaining_days'])) ?></span></div><small>of <?= h(format_days($row['allocated_days'])) ?> allocated</small></div><?php endforeach; endif; ?>
<div class="side-callout"><strong>Before you submit</strong><p>Check your dates carefully. Overlapping requests on your own account are blocked. Team overlaps are shown to HR to help plan coverage.</p></div></aside></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
