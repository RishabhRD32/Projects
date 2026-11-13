<?php
$user = current_user();
$unreadCount = ($user && isset($pdo)) ? unread_notification_count($pdo, (int)$user['id']) : 0;
$flashes = get_flashes();
$pageTitle = $pageTitle ?? $APP_NAME;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111a32">
    <title><?= h($pageTitle) ?> · <?= h($APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= h(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="<?= h(url($user ? ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'employee/dashboard.php') : 'index.php')) ?>">
            <span class="brand-mark">LF</span><span><strong>LeaveFlow</strong><small>Leave management, simplified</small></span>
        </a>
        <nav class="top-actions">
            <?php if ($user): ?>
                <a class="top-link notification-link" href="<?= h(url('employee/notifications.php')) ?>" aria-label="Notifications">Notifications <?php if ($unreadCount): ?><span class="count-pill"><?= $unreadCount ?></span><?php endif; ?></a>
                <a class="top-link" href="<?= h(url('account.php')) ?>">Account</a>
                <span class="user-chip"><span class="avatar"><?= h(strtoupper(substr($user['full_name'], 0, 1))) ?></span><span><?= h($user['full_name']) ?><small><?= h(ucfirst($user['role'])) ?></small></span></span>
                <a class="btn btn-quiet btn-small" href="<?= h(url('logout.php')) ?>">Sign out</a>
            <?php else: ?>
                <a class="top-link" href="<?= h(url('login.php')) ?>">Sign in</a>
                <a class="btn btn-primary btn-small" href="<?= h(url('register.php')) ?>">Create account</a>
            <?php endif; ?>
        </nav>
    </header>
    <?php if ($user): ?>
    <div class="workspace">
        <aside class="sidebar">
            <div class="side-label">WORKSPACE</div>
            <?php if ($user['role'] === 'admin'): ?>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'dashboard.php') ? 'active' : '' ?>" href="<?= h(url('admin/dashboard.php')) ?>"><span>▦</span> Overview</a>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'leaves.php') ? 'active' : '' ?>" href="<?= h(url('admin/leaves.php')) ?>"><span>▤</span> Leave requests</a>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'employees.php') ? 'active' : '' ?>" href="<?= h(url('admin/employees.php')) ?>"><span>♙</span> Employees</a>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'leave-types.php') ? 'active' : '' ?>" href="<?= h(url('admin/leave-types.php')) ?>"><span>◷</span> Leave policies</a>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'holidays.php') ? 'active' : '' ?>" href="<?= h(url('admin/holidays.php')) ?>"><span>▦</span> Holidays</a>
                <a class="side-link" href="<?= h(url('employee/notifications.php')) ?>"><span>♧</span> Notifications <?php if ($unreadCount): ?><em><?= $unreadCount ?></em><?php endif; ?></a>
            <?php else: ?>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'dashboard.php') ? 'active' : '' ?>" href="<?= h(url('employee/dashboard.php')) ?>"><span>▦</span> Dashboard</a>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'apply.php') ? 'active' : '' ?>" href="<?= h(url('employee/apply.php')) ?>"><span>＋</span> Apply for leave</a>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'leaves.php') ? 'active' : '' ?>" href="<?= h(url('employee/leaves.php')) ?>"><span>▤</span> Leave history</a>
                <a class="side-link <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'balance.php') ? 'active' : '' ?>" href="<?= h(url('employee/balance.php')) ?>"><span>◷</span> Leave balance</a>
                <a class="side-link" href="<?= h(url('employee/notifications.php')) ?>"><span>♧</span> Notifications <?php if ($unreadCount): ?><em><?= $unreadCount ?></em><?php endif; ?></a>
            <?php endif; ?>
            <div class="sidebar-foot"><div class="help-card"><span class="help-dot"></span><strong>Need a hand?</strong><p>Contact your HR administrator for policy or account support.</p></div><small>OLMS · <?= date('Y') ?></small></div>
        </aside>
        <main class="main-content">
    <?php else: ?>
        <main class="public-content">
    <?php endif; ?>
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?><button type="button" class="alert-close" aria-label="Dismiss">×</button></div>
    <?php endforeach; ?>
