<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect(is_admin() ? 'admin/dashboard.php' : 'employee/dashboard.php');
$errors = [];
$values = ['full_name'=>'','email'=>'','phone'=>'','department'=>'','designation'=>'','date_of_joining'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if ($values['full_name'] === '' || mb_strlen($values['full_name']) < 2) $errors[] = 'Enter your full name.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($values['department'] === '') $errors[] = 'Enter your department or team.';
    if ($password === '' || strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Password and confirmation do not match.';
    if ($values['date_of_joining'] !== '') {
        $date = DateTime::createFromFormat('Y-m-d', $values['date_of_joining']);
        if (!$date || $date->format('Y-m-d') !== $values['date_of_joining']) $errors[] = 'Enter a valid joining date.';
    }
    $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $check->execute([$values['email']]);
    if ((int)$check->fetchColumn() > 0) $errors[] = 'An account with this email already exists.';

    if (!$errors) {
        $code = generate_employee_code($pdo);
        $stmt = $pdo->prepare("INSERT INTO users (employee_code, full_name, email, phone, department, designation, date_of_joining, password_hash, role, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'employee', 'active')");
        $stmt->execute([$code, $values['full_name'], $values['email'], $values['phone'] ?: null, $values['department'], $values['designation'] ?: null, $values['date_of_joining'] ?: null, password_hash($password, PASSWORD_DEFAULT)]);
        $newId = (int)$pdo->lastInsertId();
        ensure_user_balances($pdo, $newId);
        notify_user($pdo, $newId, 'Welcome to LeaveFlow', 'Your employee account is ready. You can now submit and track leave requests.', 'success');
        log_activity($pdo, $newId, 'register', 'user', $newId);
        set_flash('success', 'Your account has been created. Sign in to access your dashboard.');
        redirect('login.php');
    }
}
$pageTitle = 'Create employee account';
require __DIR__ . '/includes/header.php';
?>
<div class="register-layout"><div class="register-intro"><span class="eyebrow"><span class="eyebrow-dot"></span> GET SET UP</span><h1>Your work life,<br><em>better organised.</em></h1><p>Create your employee account to apply for leave, check your available balance and stay updated at every step.</p><div class="register-perks"><div><span>✓</span><p><strong>Simple requests</strong><small>Apply in a few steps</small></p></div><div><span>✓</span><p><strong>Full visibility</strong><small>Track status and history</small></p></div><div><span>✓</span><p><strong>Clear balances</strong><small>Know your available days</small></p></div></div></div>
<div class="form-card"><div class="form-card-title"><div><span class="eyebrow">EMPLOYEE REGISTRATION</span><h2>Create your account</h2><p>Fields marked * are required.</p></div><span class="step-chip">01 / 01</span></div>
<?php if ($errors): ?><div class="alert alert-danger"><strong>Please review the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-grid"><?= csrf_field() ?>
<label class="full">Full name *<input name="full_name" value="<?= h($values['full_name']) ?>" required maxlength="120" placeholder="Your full name"></label>
<label>Email address *<input type="email" name="email" value="<?= h($values['email']) ?>" required maxlength="190" placeholder="you@company.com"></label>
<label>Phone number<input name="phone" value="<?= h($values['phone']) ?>" maxlength="25" placeholder="Optional"></label>
<label>Department / team *<input name="department" value="<?= h($values['department']) ?>" required maxlength="100" placeholder="e.g. Engineering"></label>
<label>Designation<input name="designation" value="<?= h($values['designation']) ?>" maxlength="100" placeholder="e.g. Software Engineer"></label>
<label>Date of joining<input type="date" name="date_of_joining" value="<?= h($values['date_of_joining']) ?>"></label>
<div class="form-divider full"><span>SECURE YOUR ACCOUNT</span></div>
<label>Password *<input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters"><small>Use at least 8 characters.</small></label>
<label>Confirm password *<input type="password" name="confirm_password" required minlength="8" autocomplete="new-password" placeholder="Re-enter password"></label>
<div class="full form-submit"><button class="btn btn-primary btn-block btn-lg" type="submit">Create employee account <span>→</span></button><p>Already registered? <a href="<?= h(url('login.php')) ?>">Sign in</a></p></div>
</form></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
