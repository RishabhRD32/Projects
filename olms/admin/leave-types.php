<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'save');
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE leave_types SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?'); $stmt->execute([$id]);
        set_flash('success', 'Leave policy status updated.');
        redirect('admin/leave-types.php');
    }
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $code = strtoupper(trim((string)($_POST['code'] ?? '')));
    $quota = filter_var($_POST['annual_quota'] ?? null, FILTER_VALIDATE_FLOAT);
    $description = trim((string)($_POST['description'] ?? ''));
    $error = '';
    if ($name === '' || $code === '' || $quota === false || $quota < 0 || $quota > 366) $error = 'Enter a name, code and valid annual quota from 0 to 366.';
    $check = $pdo->prepare('SELECT COUNT(*) FROM leave_types WHERE (name = ? OR code = ?) AND id <> ?'); $check->execute([$name,$code,$id]);
    if ((int)$check->fetchColumn()) $error = 'Leave type name or code already exists.';
    if ($error) set_flash('danger', $error);
    else {
        if ($id) {
            $pdo->prepare('UPDATE leave_types SET name=?, code=?, annual_quota=?, description=? WHERE id=?')->execute([$name,$code,$quota,$description ?: null,$id]);
            // Keep annual allocations aligned with the edited policy while preserving days already used.
            $pdo->prepare('UPDATE user_leave_balances SET allocated_days = ? WHERE leave_type_id = ? AND balance_year = YEAR(CURDATE())')->execute([$quota,$id]);
            set_flash('success', 'Leave policy updated. Current-year employee allocations have been synchronised.');
        } else {
            $pdo->prepare('INSERT INTO leave_types (name, code, annual_quota, description) VALUES (?, ?, ?, ?)')->execute([$name,$code,$quota,$description ?: null]);
            $newTypeId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT IGNORE INTO user_leave_balances (user_id, leave_type_id, balance_year, allocated_days, used_days) SELECT id, ?, YEAR(CURDATE()), ?, 0 FROM users WHERE role = 'employee'")->execute([$newTypeId,$quota]);
            set_flash('success', 'Leave type created and added to employee balances for the current year.');
        }
    }
    redirect('admin/leave-types.php');
}
$types = $pdo->query('SELECT * FROM leave_types ORDER BY is_active DESC, name')->fetchAll();
$pageTitle = 'Leave policies';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">LEAVE CONFIGURATION</span><h1>Leave policies.</h1><p>Configure leave categories and annual day allocations for employees.</p></div></div>
<div class="policy-layout"><section class="panel"><div class="panel-heading"><div><h2>Current leave types</h2><p><?= count($types) ?> configured policies</p></div></div><?php if (!$types): ?><div class="empty-state"><strong>No leave types yet</strong></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Leave type</th><th>Annual quota</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach ($types as $type): ?><tr><td><strong><?= h($type['name']) ?></strong><small class="cell-sub"><?= h($type['code']) ?> · <?= h($type['description'] ?: 'No description') ?></small></td><td><?= h(format_days($type['annual_quota'])) ?></td><td><span class="status-badge status-<?= $type['is_active'] ? 'success' : 'muted' ?>"><?= $type['is_active'] ? 'Active' : 'Inactive' ?></span></td><td><button class="table-action" type="button" data-edit-policy data-id="<?= (int)$type['id'] ?>" data-name="<?= h($type['name']) ?>" data-code="<?= h($type['code']) ?>" data-quota="<?= h((string)$type['annual_quota']) ?>" data-description="<?= h($type['description'] ?? '') ?>">Edit</button><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$type['id'] ?>"><button class="link-danger" type="submit"><?= $type['is_active'] ? 'Deactivate' : 'Activate' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel policy-form-panel"><div class="panel-heading"><div><h2 id="policy-form-title">Add leave type</h2><p>Make the policy match your organisation’s rules.</p></div></div><form method="post" id="policy-form" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="policy-id" value=""><label>Leave type name *<input name="name" id="policy-name" required maxlength="80" placeholder="e.g. Compensatory Leave"></label><label>Short code *<input name="code" id="policy-code" required maxlength="20" placeholder="e.g. CO"></label><label>Annual quota (days) *<input type="number" name="annual_quota" id="policy-quota" min="0" max="366" step="0.5" required placeholder="12"></label><label>Description<textarea name="description" id="policy-description" rows="3" maxlength="255" placeholder="Describe when this leave type applies"></textarea></label><button class="btn btn-primary btn-block" type="submit">Save leave policy <span>→</span></button><button class="btn btn-quiet btn-block" type="reset" id="policy-reset">Clear form</button></form></section></div>
<div class="form-hint"><span>ⓘ</span><p>Changing an annual quota updates the current-year allocation for existing employees while preserving the number of days already used. Deactivating a leave type hides it from new applications but keeps existing records intact.</p></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
