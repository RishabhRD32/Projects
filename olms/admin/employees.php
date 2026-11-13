<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    if ($employeeId === (int)$admin['id']) set_flash('danger', 'You cannot deactivate your own administrator account from this screen.');
    elseif ($action === 'toggle') {
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ? AND role = 'employee'");
        $stmt->execute([$employeeId]);
        if ($stmt->rowCount()) {
            $statusStmt = $pdo->prepare('SELECT status FROM users WHERE id = ?'); $statusStmt->execute([$employeeId]); $newStatus = $statusStmt->fetchColumn();
            log_activity($pdo, (int)$admin['id'], 'employee_' . $newStatus, 'user', $employeeId);
            set_flash('success', 'Employee account ' . $newStatus . '.');
        } else set_flash('warning', 'Employee account was not found.');
    }
    redirect('admin/employees.php');
}
$search = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? 'active');
$sql = "SELECT u.*, (SELECT COUNT(*) FROM leave_requests lr WHERE lr.user_id = u.id) AS request_count FROM users u WHERE u.role = 'employee'";
$params = [];
if (in_array($status, ['active','inactive'], true)) { $sql .= ' AND u.status = ?'; $params[] = $status; }
if ($search !== '') { $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.employee_code LIKE ? OR u.department LIKE ?)'; $needle = '%' . $search . '%'; array_push($params, $needle, $needle, $needle, $needle); }
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $employees = $stmt->fetchAll();
$pageTitle = 'Employee management';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">PEOPLE DIRECTORY</span><h1>Employees.</h1><p>Manage employee profiles, departments and account access.</p></div><a class="btn btn-primary" href="<?= h(url('admin/employee-form.php')) ?>">＋ Add employee</a></div>
<div class="toolbar"><form method="get" class="search-form"><input type="search" name="q" value="<?= h($search) ?>" placeholder="Search name, email, code or department"><button class="btn btn-outline btn-small" type="submit">Search</button></form><div class="filter-tabs compact-tabs"><a class="<?= $status === 'active' ? 'selected' : '' ?>" href="<?= h(url('admin/employees.php?status=active')) ?>">Active</a><a class="<?= $status === 'inactive' ? 'selected' : '' ?>" href="<?= h(url('admin/employees.php?status=inactive')) ?>">Inactive</a><a class="<?= $status === '' ? 'selected' : '' ?>" href="<?= h(url('admin/employees.php?status=all')) ?>">All</a></div></div>
<section class="panel"><div class="panel-heading"><div><h2><?= $status === 'all' || $status === '' ? 'All employees' : ucfirst($status) . ' employees' ?></h2><p><?= count($employees) ?> employee<?= count($employees) === 1 ? '' : 's' ?> found</p></div></div>
<?php if (!$employees): ?><div class="empty-state"><div class="empty-icon">♙</div><strong>No employees found</strong><p>Try another search or add an employee to the directory.</p><a class="btn btn-primary btn-small" href="<?= h(url('admin/employee-form.php')) ?>">Add employee</a></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Employee</th><th>Department</th><th>Contact</th><th>Joined</th><th>Requests</th><th>Account</th><th>Manage</th></tr></thead><tbody><?php foreach ($employees as $employee): ?><tr><td><div class="employee-cell"><span class="avatar"><?= h(strtoupper(substr($employee['full_name'], 0, 1))) ?></span><span><strong><?= h($employee['full_name']) ?></strong><small class="cell-sub"><?= h($employee['employee_code']) ?> · <?= h($employee['designation'] ?: 'Employee') ?></small></span></div></td><td><?= h($employee['department']) ?></td><td><?= h($employee['email']) ?><small class="cell-sub"><?= h($employee['phone'] ?: 'No phone') ?></small></td><td><?= format_date($employee['date_of_joining']) ?></td><td><?= (int)$employee['request_count'] ?></td><td><span class="status-badge status-<?= $employee['status'] === 'active' ? 'success' : 'muted' ?>"><?= h(ucfirst($employee['status'])) ?></span></td><td><div class="table-actions"><a class="table-action" href="<?= h(url('admin/employee-form.php?id=' . (int)$employee['id'])) ?>">Edit</a><form method="post" onsubmit="return confirm('Change this employee account status?');"><?= csrf_field() ?><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>"><input type="hidden" name="action" value="toggle"><button type="submit" class="link-danger"><?= $employee['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
