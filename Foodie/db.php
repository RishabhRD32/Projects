<?php
$host   = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'foodie_db';

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    // Return JSON error if called from AJAX, else die with message
    $msg = 'Database connection failed: ' . $conn->connect_error;
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit;
    }
    die("<h2 style='color:red;font-family:sans-serif;padding:40px'>$msg<br><small>Check your XAMPP MySQL is running and db.php credentials are correct.</small></h2>");
}
$conn->set_charset('utf8mb4');
?>