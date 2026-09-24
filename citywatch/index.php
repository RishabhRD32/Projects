<?php
require 'db_connect.php';

// Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $location = htmlspecialchars($_POST['location']);
    $description = htmlspecialchars($_POST['description']);

    if (!empty($location) && !empty($description)) {
        $stmt = $conn->prepare("INSERT INTO reports (type, location, description) VALUES (:type, :loc, :desc)");
        $stmt->execute(['type' => $type, 'loc' => $location, 'desc' => $description]);
        $message = "Report submitted successfully!";
    }
}

// Fetch Reports for Feed (Latest 5)
$stmt = $conn->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 5");
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CityWatch - Report Issues</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <div class="brand">🌱 CityWatch</div>
        <div class="nav-links">
            <a href="index.php" class="active">Public Portal</a>
            <a href="admin.php">Admin Login</a>
        </div>
    </nav>

    <div class="container">
        <!-- Success Message -->
        <?php if($message): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            
            <!-- Report Form -->
            <div>
                <div class="card">
                    <h2 style="margin-top:0;">Report an Issue</h2>
                    <p style="color:var(--text-light); margin-bottom: 1.5rem;">Identify an environmental hazard in your area.</p>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Issue Type</label>
                            <select name="type" required>
                                <option value="Garbage">Garbage Dump</option>
                                <option value="Water Leakage">Water Leakage</option>
                                <option value="Pollution">Air/Noise Pollution</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" placeholder="e.g. Main Street, Sector 4" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="4" placeholder="Describe the details..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-block">Submit Report</button>
                    </form>
                </div>
            </div>

            <!-- Community Feed -->
            <div>
                <h3 style="margin-top:0; margin-bottom:1rem;">Recent Reports</h3>
                <?php foreach($reports as $row): ?>
                <div class="card report-item">
                    <div class="report-header">
                        <span class="report-type"><?= htmlspecialchars($row['type']) ?></span>
                        <span class="badge status-<?= substr($row['status'], 0, 2) ?>"><?= $row['status'] ?></span>
                    </div>
                    <p style="margin: 0.5rem 0; font-size: 0.95rem;"><?= htmlspecialchars($row['description']) ?></p>
                    <div class="report-loc">📍 <?= htmlspecialchars($row['location']) ?> &bull; <span class="report-date"><?= date('M j', strtotime($row['created_at'])) ?></span></div>
                </div>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>

</body>
</html>