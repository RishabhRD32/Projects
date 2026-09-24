<?php
require 'config.php';

try {
    // Create Reports Table
    $sql = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        docket_id VARCHAR(20) UNIQUE NOT NULL,
        citizen_name VARCHAR(100),
        contact_no VARCHAR(15),
        category VARCHAR(50),
        location VARCHAR(150),
        description TEXT,
        image_path VARCHAR(255),
        status ENUM('Submitted', 'In Review', 'Action Taken', 'Rejected') DEFAULT 'Submitted',
        priority ENUM('Normal', 'High', 'Critical') DEFAULT 'Normal',
        admin_remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    
    // Create Admin User Table (for future expansion)
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password VARCHAR(255)
    )");

    // Insert Default Admin (User: admin, Pass: admin123)
    // In production, use password_hash()
    $pdo->exec("INSERT IGNORE INTO admins (username, password) VALUES ('admin', 'admin123')");

    echo "<div style='font-family: sans-serif; padding: 20px; background: #d1fae5; color: #065f46; border-radius: 8px; max-width: 600px; margin: 50px auto; text-align: center;'>
            <h1>✅ System Installed Successfully</h1>
            <p>Database and Tables have been created.</p>
            <p><strong>Admin User:</strong> admin<br><strong>Password:</strong> admin123</p>
            <a href='index.php' style='display: inline-block; padding: 10px 20px; background: #059669; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Go to Portal</a>
          </div>";

} catch (PDOException $e) {
    echo "Setup Failed: " . $e->getMessage();
}
?>