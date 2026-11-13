<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'read_all') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([(int)$user['id']]);
        set_flash('success', 'All notifications marked as read.');
    } elseif ($action === 'read_one') {
        $id = (int)($_POST['notification_id'] ?? 0);
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$id, (int)$user['id']]);
        set_flash('success', 'Notification marked as read.');
    }
    redirect('employee/notifications.php');
}
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([(int)$user['id']]);
$notifications = $stmt->fetchAll();
$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">UPDATES & REMINDERS</span><h1>Notifications.</h1><p>Important updates about leave requests and account activity.</p></div><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn-outline" type="submit">Mark all as read</button></form></div>
<section class="panel"><div class="panel-heading"><div><h2>Your updates</h2><p>Showing the latest <?= count($notifications) ?> notifications</p></div></div>
<?php if (!$notifications): ?><div class="empty-state"><div class="empty-icon">♧</div><strong>You’re all caught up</strong><p>New request updates and reminders will appear here.</p></div><?php else: ?><div class="notification-list"><?php foreach ($notifications as $item): ?><article class="notification-item <?= $item['is_read'] ? '' : 'unread' ?>"><span class="notification-mark type-<?= h($item['type']) ?>"><?= $item['type'] === 'success' ? '✓' : ($item['type'] === 'warning' ? '◷' : 'i') ?></span><div class="notification-body"><div class="notification-title"><strong><?= h($item['title']) ?></strong><?php if (!$item['is_read']): ?><span class="new-dot">NEW</span><?php endif; ?></div><p><?= h($item['message']) ?></p><small><?= format_date($item['created_at']) ?> · <?= h(date('h:i A', strtotime($item['created_at']))) ?></small></div><?php if (!$item['is_read']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_one"><input type="hidden" name="notification_id" value="<?= (int)$item['id'] ?>"><button type="submit" class="link-button">Mark read</button></form><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
