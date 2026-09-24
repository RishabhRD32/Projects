<?php
require 'db_connect.php';

// Handle Status Updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE reports SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $status, 'id' => $id]);
}

// Fetch All Reports
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
if($filter == 'All') {
    $stmt = $conn->query("SELECT * FROM reports ORDER BY created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM reports WHERE status = :status ORDER BY created_at DESC");
    $stmt->execute(['status' => $filter]);
}
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$total = $conn->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$pending = $conn->query("SELECT COUNT(*) FROM reports WHERE status='Pending'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CityWatch Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <div class="brand">🛡️ Admin Dashboard</div>
        <div class="nav-links">
            <a href="index.php">View Site</a>
            <a href="#" class="active">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Stats -->
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
            <div class="card" style="flex:1; text-align:center;">
                <div style="font-size: 2rem; font-weight:bold; color:var(--primary);"><?= $total ?></div>
                <div style="color:var(--text-light); font-size:0.9rem; text-transform:uppercase;">Total Reports</div>
            </div>
            <div class="card" style="flex:1; text-align:center;">
                <div style="font-size: 2rem; font-weight:bold; color:var(--danger);"><?= $pending ?></div>
                <div style="color:var(--text-light); font-size:0.9rem; text-transform:uppercase;">Pending Action</div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin:0;">Manage Issues</h3>
                <div>
                    Filter: 
                    <a href="?filter=All" style="margin-left:5px; text-decoration:none; color:<?= $filter=='All' ? 'black' : 'gray' ?>;">All</a> |
                    <a href="?filter=Pending" style="margin-left:5px; text-decoration:none; color:<?= $filter=='Pending' ? 'black' : 'gray' ?>;">Pending</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Type</th>
                        <th width="20%">Location</th>
                        <th width="30%">Description</th>
                        <th width="10%">Status</th>
                        <th width="20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reports as $row): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['type']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><span class="badge status-<?= substr($row['status'], 0, 2) ?>"><?= $row['status'] ?></span></td>
                        <td>
                            <?php if($row['status'] !== 'Resolved'): ?>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <?php if($row['status'] == 'Pending'): ?>
                                <button type="submit" name="status" value="In Progress" class="btn btn-sm btn-blue">Start</button>
                                <?php endif; ?>
                                <button type="submit" name="status" value="Resolved" class="btn btn-sm btn-green">Resolve</button>
                            </form>
                            <?php else: ?>
                            <span style="color:var(--text-light); font-size:0.9rem;">No action needed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if(empty($reports)): ?>
                <p style="text-align:center; padding: 2rem; color: gray;">No reports found.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
