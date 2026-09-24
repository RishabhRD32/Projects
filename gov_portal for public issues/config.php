<?php
// Database Configuration
$host = 'localhost';
$dbname = 'gov_environment_db';
$username = 'root';
$password = '';

// Error Reporting (Turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
session_start();

// Connect to Database
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create DB if not exists (handled in setup.php, but this ensures connection)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");
} catch (PDOException $e) {
    die("DB Connection Failed: " . $e->getMessage());
}

// Helper: Generate Unique Docket ID (e.g., GOV-2023-X892)
function generateDocketId() {
    return 'GOV-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
}

// Helper: Sanitize Input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>