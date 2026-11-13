<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Kolkata');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session_name'] ?? 'OLMS_SESSION');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!empty($config['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/functions.php';

try {
    $dbConfig = $config['database'];
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'] ?? '3306',
        $dbConfig['name'],
        $dbConfig['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    $message = !empty($config['debug']) ? h($exception->getMessage()) : 'The database connection could not be established. Check config/config.php and import database/olms.sql.';
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>OLMS setup</title><body style="font-family:Arial,sans-serif;background:#f4f6fb;padding:40px;color:#172033"><main style="max-width:680px;margin:auto;background:#fff;padding:28px;border-radius:16px"><h1>Database connection failed</h1><p>' . $message . '</p><p>Setup guide: open <strong>README.md</strong>, configure the database, and import the SQL file.</p></main></body></html>';
    exit;
}

$APP_NAME = $config['app_name'] ?? 'LeaveFlow';
$BASE_URL = rtrim((string)($config['app_url'] ?? ''), '/');
if ($BASE_URL === '') {
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if (preg_match('#/(employee|admin|cron)$#', $basePath)) {
        $basePath = substr($basePath, 0, strrpos($basePath, '/'));
    }
    $BASE_URL = $basePath === '' ? '' : $basePath;
}
