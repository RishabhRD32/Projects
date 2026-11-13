<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect(is_admin() ? 'admin/dashboard.php' : 'employee/dashboard.php');
$adminMode = isset($_GET['admin']);
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT id, employee_code, full_name, email, department, designation, role, status, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $account = $stmt->fetch();
    if ($account && $account['status'] === 'active' && password_verify($password, $account['password_hash']) && (!$adminMode || $account['role'] === 'admin')) {
        session_regenerate_id(true);
        unset($account['password_hash']);
        $_SESSION['user'] = $account;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        log_activity($pdo, (int)$account['id'], 'login', 'user', (int)$account['id']);
        redirect($account['role'] === 'admin' ? 'admin/dashboard.php' : 'employee/dashboard.php');
    }
    $error = 'The email or password is incorrect, or this account is not permitted to sign in here.';
}
$pageTitle = $adminMode ? 'Administrator sign in' : 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout">
    <div class="auth-aside"><span class="eyebrow"><span class="eyebrow-dot"></span> WELCOME BACK</span><h1>Pick up right<br><em>where you left off.</em></h1><p>Your leave details, requests and updates are all in one secure workspace.</p><div class="aside-quote"><span>“</span><p>Good systems make everyday work feel lighter.</p></div></div>
    <div class="auth-card"><div class="auth-card-head"><span class="auth-symbol">↗</span><span class="eyebrow"><?= $adminMode ? 'ADMINISTRATOR ACCESS' : 'YOUR WORKSPACE' ?></span><h2><?= $adminMode ? 'Admin sign in' : 'Sign in to LeaveFlow' ?></h2><p>Enter your account details to continue.</p></div>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack"><?= csrf_field() ?>
            <label>Email address<input type="email" name="email" value="<?= h($email) ?>" placeholder="you@company.com" autocomplete="username" required></label>
            <label>Password<input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required></label>
            <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in <span>→</span></button>
        </form>
        <?php if (!$adminMode): ?><p class="auth-switch">New to LeaveFlow? <a href="<?= h(url('register.php')) ?>">Create an employee account</a></p><p class="auth-switch"><a href="<?= h(url('login.php?admin=1')) ?>">Administrator sign in</a></p><?php else: ?><p class="auth-switch"><a href="<?= h(url('login.php')) ?>">← Employee sign in</a></p><?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
