<?php
/**
 * CLI reminder worker. Run daily from cron/Task Scheduler.
 * It sends in-app reminders for requests pending longer than 3 days.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}
require __DIR__ . '/../includes/bootstrap.php';

$stale = $pdo->query("SELECT lr.id, lr.request_code, lr.user_id, u.full_name
                       FROM leave_requests lr JOIN users u ON u.id = lr.user_id
                       WHERE lr.status = 'pending' AND lr.created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)
                       ORDER BY lr.created_at ASC LIMIT 500")->fetchAll();
$admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
$created = 0;
foreach ($stale as $request) {
    $recipients = array_unique(array_merge([(int)$request['user_id']], array_map('intval', $admins)));
    foreach ($recipients as $recipientId) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND leave_request_id = ? AND title = 'Pending leave reminder' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $check->execute([$recipientId, (int)$request['id']]);
        if ((int)$check->fetchColumn() === 0) {
            $message = $recipientId === (int)$request['user_id']
                ? 'Your leave request ' . $request['request_code'] . ' is still awaiting review. Please contact HR if the dates are approaching.'
                : 'Request ' . $request['request_code'] . ' from ' . $request['full_name'] . ' has been pending for more than 3 days.';
            notify_user($pdo, $recipientId, 'Pending leave reminder', $message, 'warning', (int)$request['id']);
            $created++;
        }
    }
}
echo sprintf("Reminder run complete. Checked %d stale request(s); created %d notification(s).\n", count($stale), $created);
