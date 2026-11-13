<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) log_activity($pdo, (int)current_user()['id'], 'logout', 'user', (int)current_user()['id']);
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: ' . url('index.php'));
exit;
