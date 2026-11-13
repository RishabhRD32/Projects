<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?'); $stmt->execute([(int)$user['id']]);
    $hash = (string)$stmt->fetchColumn();
    if (!password_verify($current, $hash)) $errors[] = 'Current password is incorrect.';
    if (strlen($new) < 8) $errors[] = 'New password must contain at least 8 characters.';
    if ($new !== $confirm) $errors[] = 'New password and confirmation do not match.';
    if ($new !== '' && password_verify($new, $hash)) $errors[] = 'Choose a password different from your current password.';
    if (!$errors) {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), (int)$user['id']]);
        session_regenerate_id(true);
        log_activity($pdo, (int)$user['id'], 'password_changed', 'user', (int)$user['id']);
        set_flash('success', 'Your password has been updated.');
        redirect('account.php');
    }
}
$pageTitle = 'Account security';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">ACCOUNT SETTINGS</span><h1>Account security.</h1><p>Update your password and keep your account protected.</p></div></div>
<section class="panel form-panel"><div class="panel-heading"><div><h2>Change password</h2><p>Use a unique password with at least 8 characters.</p></div></div>
<?php if ($errors): ?><div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-stack" style="max-width:480px"><?= csrf_field() ?><label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label><label>New password<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label><label>Confirm new password<input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></label><button class="btn btn-primary" type="submit">Update password <span>→</span></button></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
