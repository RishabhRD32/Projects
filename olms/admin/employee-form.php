<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');
$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$employee = null;
if ($editing) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'employee'"); $stmt->execute([$id]); $employee = $stmt->fetch();
    if (!$employee) { set_flash('danger', 'Employee was not found.'); redirect('admin/employees.php'); }
}
$values = ['full_name'=>$employee['full_name'] ?? '', 'email'=>$employee['email'] ?? '', 'phone'=>$employee['phone'] ?? '', 'department'=>$employee['department'] ?? '', 'designation'=>$employee['designation'] ?? '', 'date_of_joining'=>$employee['date_of_joining'] ?? '', 'employee_code'=>$employee['employee_code'] ?? ''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($values['full_name'] === '') $errors[] = 'Full name is required.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if ($values['department'] === '') $errors[] = 'Department is required.';
    if (!$editing && strlen($password) < 8) $errors[] = 'A password of at least 8 characters is required for a new employee.';
    if ($editing && $password !== '' && strlen($password) < 8) $errors[] = 'A new password must contain at least 8 characters.';
    $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?'); $check->execute([$values['email'], $id]);
    if ((int)$check->fetchColumn()) $errors[] = 'That email address is already in use.';
    if ($values['date_of_joining'] !== '') {
        $date = DateTime::createFromFormat('Y-m-d', $values['date_of_joining']);
        if (!$date || $date->format('Y-m-d') !== $values['date_of_joining']) $errors[] = 'Enter a valid joining date.';
    }
    if ($values['employee_code'] !== '') {
        $codeCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE employee_code = ? AND id <> ?');
        $codeCheck->execute([$values['employee_code'], $id]);
        if ((int)$codeCheck->fetchColumn()) $errors[] = 'That employee code is already in use.';
    }
    if (!$errors) {
        if ($editing) {
            $sql = 'UPDATE users SET full_name=?, email=?, phone=?, department=?, designation=?, date_of_joining=?, employee_code=?';
            $params = [$values['full_name'],$values['email'],$values['phone'] ?: null,$values['department'],$values['designation'] ?: null,$values['date_of_joining'] ?: null,$values['employee_code'] ?: $employee['employee_code']];
            if ($password !== '') { $sql .= ', password_hash=?'; $params[] = password_hash($password, PASSWORD_DEFAULT); }
            $sql .= ' WHERE id=? AND role=\'employee\''; $params[] = $id;
            $pdo->prepare($sql)->execute($params);
            log_activity($pdo, (int)$admin['id'], 'employee_updated', 'user', $id);
            set_flash('success', 'Employee profile updated.');
        } else {
            $code = $values['employee_code'] ?: generate_employee_code($pdo);
            $stmt = $pdo->prepare("INSERT INTO users (employee_code, full_name, email, phone, department, designation, date_of_joining, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'employee', 'active')");
            try {
                $stmt->execute([$code,$values['full_name'],$values['email'],$values['phone'] ?: null,$values['department'],$values['designation'] ?: null,$values['date_of_joining'] ?: null,password_hash($password, PASSWORD_DEFAULT)]);
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '23000') $errors[] = 'That employee code or email is already in use.';
                else throw $e;
            }
            if (!$errors) {
                $newId = (int)$pdo->lastInsertId(); ensure_user_balances($pdo, $newId);
                notify_user($pdo, $newId, 'Welcome to LeaveFlow', 'Your employee account has been created by an administrator.', 'success');
                log_activity($pdo, (int)$admin['id'], 'employee_created', 'user', $newId);
                set_flash('success', 'Employee account created. Share their sign-in credentials securely.');
                redirect('admin/employees.php');
            }
        }
        if (!$errors) redirect('admin/employees.php');
    }
}
$pageTitle = $editing ? 'Edit employee' : 'Add employee';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">PEOPLE DIRECTORY</span><h1><?= $editing ? 'Edit employee.' : 'Add an employee.' ?></h1><p><?= $editing ? 'Update this employee’s profile and account access.' : 'Create an employee profile and initial sign-in credentials.' ?></p></div><a class="btn btn-outline" href="<?= h(url('admin/employees.php')) ?>">← Back to employees</a></div>
<section class="panel form-panel"><div class="panel-heading"><div><h2>Employee information</h2><p>Employee accounts cannot access administrator features.</p></div></div>
<?php if ($errors): ?><div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-grid"><?= csrf_field() ?>
<label>Full name *<input name="full_name" value="<?= h($values['full_name']) ?>" required></label><label>Email *<input type="email" name="email" value="<?= h($values['email']) ?>" required></label>
<label>Employee code<input name="employee_code" value="<?= h($values['employee_code']) ?>" placeholder="Auto-generate when blank"></label><label>Phone<input name="phone" value="<?= h($values['phone']) ?>"></label>
<label>Department *<input name="department" value="<?= h($values['department']) ?>" required></label><label>Designation<input name="designation" value="<?= h($values['designation']) ?>"></label>
<label>Date of joining<input type="date" name="date_of_joining" value="<?= h($values['date_of_joining']) ?>"></label><label>Password <?= $editing ? '(leave blank to keep current)' : '*' ?><input type="password" name="password" <?= $editing ? '' : 'required' ?> minlength="8" autocomplete="new-password"></label>
<div class="full form-submit"><button class="btn btn-primary btn-lg" type="submit"><?= $editing ? 'Save changes' : 'Create employee' ?> <span>→</span></button><a class="btn btn-quiet" href="<?= h(url('admin/employees.php')) ?>">Cancel</a></div>
</form></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
