<?php
/**
 * OLMS configuration. Change the database credentials before installation.
 */
return [
    'app_name' => 'LeaveFlow',
    'app_url' => '', // Optional, e.g. http://localhost/olms-php. Leave blank for auto-detection.
    'timezone' => 'Asia/Kolkata',
    'debug' => false,
    'session_name' => 'OLMS_SESSION',
    'database' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'olms',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
