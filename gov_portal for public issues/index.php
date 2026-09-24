<?php
require 'config.php';

$success_msg = "";
$track_data = null;

// 1. Handle Report Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_report'])) {
    $docket = generateDocketId();
    $name = clean($_POST['name']);
    $contact = clean($_POST['contact']);
    $category = clean($_POST['category']);
    $location = clean($_POST['location']);
    $desc = clean($_POST['description']);
    
    // Handle Image Upload
    $image_path = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);
        $file_ext = strtolower(pathinfo($_FILES["evidence"]["name"], PATHINFO_EXTENSION));
        $new_filename = $docket . '.' . $file_ext;
        if (move_uploaded_file($_FILES["evidence"]["tmp_name"], $target_dir . $new_filename)) {
            $image_path = $new_filename;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO reports (docket_id, citizen_name, contact_no, category, location, description, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$docket, $name, $contact, $category, $location, $desc, $image_path])) {
        $success_msg = $docket;
    }
}

// 2. Handle Tracking
if (isset($_GET['track_id'])) {
    $tid = clean($_GET['track_id']);
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE docket_id = ?");
    $stmt->execute([$tid]);
    $track_data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Grievance Portal | Govt. of Environment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gov-header { background: linear-gradient(90deg, #1e3a8a 0%, #172554 100%); }
        .pattern-bg { background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0IiBoZWlnaHQ9IjQiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjZjNmNGY2Ii8+CjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9IiNlNWU3ZWIiLz4KPC9zdmc+'); }
    </style>
</head>
<body class="bg-gray-50 pattern-bg">

    <!-- Official Header -->
    <header class="gov-header text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 p-2 rounded-full border border-white/20">
                    <i class="fas fa-university text-2xl text-yellow-400"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-tight">Ministry of Urban Development</h1>
                    <p class="text-xs text-blue-200 uppercase tracking-wider">Environmental Grievance Redressal System</p>
                </div>
            </div>
            <div class="hidden md:flex gap-6 text-sm font-medium">
                <a href="index.php" class="hover:text-yellow-400 transition">Home</a>
                <a href="#track" class="hover:text-yellow-400 transition">Track Status</a>
                <a href="admin.php" class="bg-white/10 hover:bg-white/20 px-4 py-1.5 rounded-md border border-white/20 transition">Official Login</a>
            </div>
        </div>
    </header>

    <!-- Success Modal -->
    <?php if($success_msg): ?>
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-center border-t-4 border-green-600">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-2xl text-green-600"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Grievance Submitted</h2>
            <p class="text-gray-600 mt-2">Your report has been registered successfully.</p>
            <div class="bg-gray-100 p-4 rounded-lg mt-4 border border-dashed border-gray-300">
                <span class="text-xs text-gray-500 uppercase block">Your Docket ID</span>
                <span class="text-2xl font-mono font-bold text-blue-900 select-all"><?= $success_msg ?></span>
            </div>
            <p class="text-xs text-red-500 mt-2">* Please save this ID to track status</p>
            <button onclick="window.location.href='index.php'" class="mt-6 w-full bg-blue-900 text-white py-2 rounded hover:bg-blue-800">Close</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Report Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center gap-3">
                    <i class="fas fa-file-signature text-blue-600"></i>
                    <h2 class="font-bold text-gray-800">Lodge a New Complaint</h2>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    <input type="hidden" name="submit_report" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Citizen Name</label>
                            <input type="text" name="name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                            <input type="text" name="contact" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2.5">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category of Issue</label>
                        <select name="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2.5">
                            <option>Garbage / Solid Waste</option>
                            <option>Water Leakage / Stagnation</option>
                            <option>Air / Industrial Pollution</option>
                            <option>Tree Cutting / Greenery Loss</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Exact Location / Address</label>
                        <input type="text" name="location" required placeholder="Ward No, Street Name, Landmark" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2.5">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Detailed Description</label>
                        <textarea name="description" rows="4" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2.5"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Photographic Evidence</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        <span>Upload a file</span>
                                        <input id="file-upload" name="evidence" type="file" class="sr-only" accept="image/*">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t">
                        <button type="submit" class="w-full bg-blue-900 text-white font-bold py-3 px-4 rounded shadow hover:bg-blue-800 transition flex justify-center items-center gap-2">
                            <i class="fas fa-paper-plane"></i> Submit Grievance
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Tracking & Info -->
        <div class="space-y-6">
            
            <!-- Track Status Widget -->
            <div id="track" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-search-location text-blue-600"></i> Track Application
                </h3>
                <form method="GET" class="flex gap-2">
                    <input type="text" name="track_id" placeholder="Enter Docket ID (e.g. GOV-...)" class="flex-1 rounded border border-gray-300 p-2 text-sm uppercase">
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Search</button>
                </form>

                <?php if($track_data): ?>
                <div class="mt-4 p-4 bg-blue-50 rounded border border-blue-100 animate-fade-in">
                    <div class="text-xs text-gray-500 uppercase">Current Status</div>
                    <div class="text-lg font-bold text-blue-900 mt-1">
                        <?= $track_data['status'] ?>
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-blue-200">
                        <div class="text-xs text-gray-500">Remarks:</div>
                        <p class="text-sm text-gray-700 italic">"<?= $track_data['admin_remarks'] ?: 'Processing request...' ?>"</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Stats Widget -->
            <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-xl shadow-lg p-6 text-white">
                <h3 class="font-bold text-lg mb-4 border-b border-emerald-500 pb-2">Public Transparency</h3>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="bg-white/10 rounded-lg p-3">
                        <div class="text-3xl font-bold">
                            <?= $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn() ?>
                        </div>
                        <div class="text-xs text-emerald-100 uppercase">Total Reports</div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3">
                        <div class="text-3xl font-bold text-yellow-300">
                            <?= $pdo->query("SELECT COUNT(*) FROM reports WHERE status='Action Taken'")->fetchColumn() ?>
                        </div>
                        <div class="text-xs text-emerald-100 uppercase">Resolved</div>
                    </div>
                </div>
            </div>

            <!-- Helplines -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <h4 class="font-bold text-gray-800 text-sm mb-3">Emergency Helplines</h4>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex justify-between"><span>🚮 Sanitation Dept</span> <span class="font-mono font-bold">1800-22-9988</span></li>
                    <li class="flex justify-between"><span>💧 Water Supply</span> <span class="font-mono font-bold">1916</span></li>
                    <li class="flex justify-between"><span>🏭 Pollution Control</span> <span class="font-mono font-bold">022-243000</span></li>
                </ul>
            </div>

        </div>
    </main>
    
    <footer class="bg-gray-800 text-gray-400 py-6 text-center text-sm">
        <p>&copy; <?= date('Y') ?> Department of Environmental Services. All Rights Reserved.</p>
        <p class="text-xs mt-1">Government of India Digital Initiative</p>
    </footer>

</body>
</html>