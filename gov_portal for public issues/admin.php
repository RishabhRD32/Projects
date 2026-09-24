<?php
require 'config.php';

// Basic Authentication (Hardcoded for demo: admin/admin123)
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != 'admin' || $_SERVER['PHP_AUTH_PW'] != 'admin123') {
    header('WWW-Authenticate: Basic realm="Government Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    die('Access Denied');
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['report_id'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $remarks = clean($_POST['remarks']);
    
    $stmt = $pdo->prepare("UPDATE reports SET status=?, priority=?, admin_remarks=? WHERE id=?");
    $stmt->execute([$status, $priority, $remarks, $id]);
}

// Fetch Filters
$filter = $_GET['filter'] ?? 'All';
$sql = "SELECT * FROM reports";
if ($filter != 'All') $sql .= " WHERE status = '$filter'";
$sql .= " ORDER BY created_at DESC";
$reports = $pdo->query($sql)->fetchAll();

// Stats
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM reports WHERE status='Submitted'")->fetchColumn(),
    'resolved' => $pdo->query("SELECT COUNT(*) FROM reports WHERE status='Action Taken'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Govt Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #0f172a; }
        .nav-item.active { background: #1e293b; border-left: 4px solid #3b82f6; }
    </style>
</head>
<body class="bg-gray-100 flex">

    <!-- Sidebar -->
    <aside class="sidebar w-64 text-white fixed hidden md:block">
        <div class="p-6 border-b border-gray-700">
            <h1 class="text-xl font-bold tracking-wider">ADMIN PANEL</h1>
            <p class="text-xs text-gray-400 mt-1">Environment Dept.</p>
        </div>
        <nav class="mt-6">
            <a href="?" class="nav-item active flex items-center gap-3 px-6 py-4 text-gray-300 hover:bg-gray-800 transition">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="index.php" target="_blank" class="nav-item flex items-center gap-3 px-6 py-4 text-gray-300 hover:bg-gray-800 transition">
                <i class="fas fa-external-link-alt"></i> Visit Site
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-6 py-4 text-red-300 hover:bg-gray-800 transition">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="md:ml-64 w-full min-h-screen">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm px-8 py-4 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">Grievance Overview</h2>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <div class="text-sm font-bold text-gray-800">Administrator</div>
                    <div class="text-xs text-gray-500">Super User</div>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">A</div>
            </div>
        </header>

        <div class="p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <div class="text-gray-500 text-sm uppercase">Total Grievances</div>
                    <div class="text-3xl font-bold text-gray-800 mt-1"><?= $stats['total'] ?></div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                    <div class="text-gray-500 text-sm uppercase">Pending Action</div>
                    <div class="text-3xl font-bold text-red-600 mt-1"><?= $stats['pending'] ?></div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                    <div class="text-gray-500 text-sm uppercase">Resolved Cases</div>
                    <div class="text-3xl font-bold text-green-600 mt-1"><?= $stats['resolved'] ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex gap-2 mb-4">
                <a href="?filter=All" class="px-4 py-2 rounded-full text-sm font-medium <?= $filter=='All'?'bg-blue-600 text-white':'bg-white text-gray-600 hover:bg-gray-50' ?>">All</a>
                <a href="?filter=Submitted" class="px-4 py-2 rounded-full text-sm font-medium <?= $filter=='Submitted'?'bg-blue-600 text-white':'bg-white text-gray-600 hover:bg-gray-50' ?>">Pending</a>
                <a href="?filter=Action Taken" class="px-4 py-2 rounded-full text-sm font-medium <?= $filter=='Action Taken'?'bg-blue-600 text-white':'bg-white text-gray-600 hover:bg-gray-50' ?>">Resolved</a>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Docket ID</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Location</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($reports as $r): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-blue-600 text-sm"><?= $r['docket_id'] ?></span>
                                <div class="text-xs text-gray-400"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?= $r['category'] ?>
                                <?php if($r['priority'] == 'Critical'): ?>
                                    <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded ml-2">Critical</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= substr($r['location'], 0, 30) ?>...</td>
                            <td class="px-6 py-4">
                                <?php
                                    $color = 'bg-yellow-100 text-yellow-800';
                                    if($r['status'] == 'Action Taken') $color = 'bg-green-100 text-green-800';
                                    if($r['status'] == 'Rejected') $color = 'bg-red-100 text-red-800';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $color ?>">
                                    <?= $r['status'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="openModal(<?= htmlspecialchars(json_encode($r)) ?>)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Review</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if(empty($reports)): ?>
                    <div class="p-8 text-center text-gray-500">No records found.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Action Modal -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden">
            <form method="POST" class="flex flex-col max-h-[90vh]">
                <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">Manage Ticket <span id="modal_id" class="text-blue-600"></span></h3>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1">
                    <input type="hidden" name="report_id" id="input_id">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Citizen Details</p>
                            <p class="font-medium" id="modal_name"></p>
                            <p class="text-sm text-gray-600" id="modal_contact"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Location</p>
                            <p class="text-sm text-gray-700" id="modal_loc"></p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-xs text-gray-500 uppercase">Issue Description</p>
                        <p class="bg-gray-50 p-3 rounded text-sm text-gray-700 border" id="modal_desc"></p>
                    </div>

                    <div id="modal_img_container" class="mb-6 hidden">
                        <p class="text-xs text-gray-500 uppercase mb-2">Evidence</p>
                        <a id="modal_img_link" href="#" target="_blank">
                            <img id="modal_img" src="" class="h-32 rounded border hover:opacity-90 cursor-zoom-in">
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Update Status</label>
                            <select name="status" id="input_status" class="w-full border rounded p-2">
                                <option value="Submitted">Submitted</option>
                                <option value="In Review">In Review</option>
                                <option value="Action Taken">Action Taken (Resolve)</option>
                                <option value="Rejected">Reject</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select name="priority" id="input_priority" class="w-full border rounded p-2">
                                <option value="Normal">Normal</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Official Remarks</label>
                        <textarea name="remarks" id="input_remarks" rows="3" placeholder="Action taken details..." class="w-full border rounded p-2"></textarea>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t text-right">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800 mr-2">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700 font-medium">Update Ticket</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(data) {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('modal_id').innerText = data.docket_id;
            document.getElementById('input_id').value = data.id;
            document.getElementById('modal_name').innerText = data.citizen_name;
            document.getElementById('modal_contact').innerText = data.contact_no;
            document.getElementById('modal_loc').innerText = data.location;
            document.getElementById('modal_desc').innerText = data.description;
            document.getElementById('input_status').value = data.status;
            document.getElementById('input_priority').value = data.priority;
            document.getElementById('input_remarks').value = data.admin_remarks;

            if(data.image_path) {
                document.getElementById('modal_img_container').classList.remove('hidden');
                document.getElementById('modal_img').src = 'uploads/' + data.image_path;
                document.getElementById('modal_img_link').href = 'uploads/' + data.image_path;
            } else {
                document.getElementById('modal_img_container').classList.add('hidden');
            }
        }
        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }
    </script>
</body>
</html>