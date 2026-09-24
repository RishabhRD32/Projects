<?php
include('includes/config.php');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// --- System Settings Update Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateSettings'])) {
    $systemName = $_POST['systemName'];
    $currency = $_POST['currency'];
    $id = 1; // Assuming settings are always for id = 1

    // Check if a logo was uploaded
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logoName = basename($_FILES['logo']['name']); // Use basename for security
        $logoTmpName = $_FILES['logo']['tmp_name'];
        $uploadPath = 'uploads/';
        $targetPath = $uploadPath . $logoName;

        // Ensure the uploads directory exists
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if (move_uploaded_file($logoTmpName, $targetPath)) {
            // Update settings with the new logo path
            $updateSettingsQuery = "UPDATE settings SET system_name = ?, logo = ?, currency = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSettingsQuery);
            $stmt->bind_param("sssi", $systemName, $targetPath, $currency, $id);
            if ($stmt->execute()) {
                $successMessage = 'System settings updated successfully.';
            } else {
                $errorMessage = 'Error updating system settings: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errorMessage = 'Error moving uploaded file.';
        }
    } else {
        // Update settings without changing the logo
        $updateSettingsQuery = "UPDATE settings SET system_name = ?, currency = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSettingsQuery);
        $stmt->bind_param("ssi", $systemName, $currency, $id);
        if ($stmt->execute()) {
            $successMessage = 'System settings updated successfully.';
        } else {
            $errorMessage = 'Error updating system settings: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// --- Change Password Logic (without hashing) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changePassword'])) {
    // Get form data
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    $userId = $_SESSION['user_id'];

    // Basic validation: ensure new passwords match
    if ($newPassword !== $confirmPassword) {
        $errorMessagePassword = 'New password and confirm password do not match.';
    } else {
        // Fetch the current password from the database
        $validatePasswordQuery = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($validatePasswordQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $validatePasswordResult = $stmt->get_result();

        if ($validatePasswordResult->num_rows > 0) {
            $row = $validatePasswordResult->fetch_assoc();
            $storedPassword = $row['password']; // This is the plain text password from the DB

            // Directly compare the provided current password with the stored plain text password
            if ($currentPassword === $storedPassword) {
                // Update the user's password with the new plain text password
                $updatePasswordQuery = "UPDATE users SET password = ? WHERE id = ?";
                $stmt_update = $conn->prepare($updatePasswordQuery);
                $stmt_update->bind_param("si", $newPassword, $userId);
                
                if ($stmt_update->execute()) {
                    $successMessagePassword = 'Password updated successfully.';
                } else {
                    $errorMessagePassword = 'Error updating password: ' . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $errorMessagePassword = 'Current password is incorrect.';
            }
        }
        $stmt->close();
    }
}


// Fetch current settings to display in the form
$fetchSettingsQuery = "SELECT * FROM settings WHERE id = 1";
$fetchSettingsResult = $conn->query($fetchSettingsQuery);

if ($fetchSettingsResult->num_rows > 0) {
    $settings = $fetchSettingsResult->fetch_assoc();
}

?>

<?php include('includes/header.php'); ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
    <div class="wrapper">
        <?php include('includes/nav.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-wrapper">
            <?php include('includes/pagetitle.php'); ?>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-cogs"></i> System Settings</h3>
                                </div>

                                <?php
                                if (!empty($successMessage)) {
                                    echo '<div class="alert alert-success m-3">' . $successMessage . '</div>';
                                } elseif (!empty($errorMessage)) {
                                    echo '<div class="alert alert-danger m-3">' . $errorMessage . '</div>';
                                }

                                if (!empty($successMessagePassword)) {
                                    echo '<div class="alert alert-success m-3">' . $successMessagePassword . '</div>';
                                } elseif (!empty($errorMessagePassword)) {
                                    echo '<div class="alert alert-danger m-3">' . $errorMessagePassword . '</div>';
                                }
                                ?>

                                <form method="post" action="" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="systemName">System Name:</label>
                                            <input type="text" id="systemName" name="systemName" class="form-control"
                                                value="<?php echo isset($settings['system_name']) ? htmlspecialchars($settings['system_name']) : ''; ?>"
                                                required>
                                        </div>

                                        <div class="form-group">
                                            <label for="logo">Logo:</label>
                                            <input type="file" id="logo" name="logo" class="form-control">
                                        </div>

                                        <div class="form-group">
                                            <label for="currency">Currency:</label>
                                            <input type="text" id="currency" name="currency" class="form-control"
                                                value="<?php echo isset($settings['currency']) ? htmlspecialchars($settings['currency']) : '₹'; ?>"
                                                required>
                                        </div>

                                        <button type="submit" name="updateSettings" class="btn btn-primary">Update Settings</button>
                                    </div>
                                </form>
                                
                                <hr>

                                <form method="post" action="">
                                    <div class="card-body">
                                        <h5 class="mb-3">Change Password</h5>
                                        <div class="form-group">
                                            <label for="currentPassword">Current Password:</label>
                                            <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="newPassword">New Password:</label>
                                            <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="confirmPassword">Confirm Password:</label>
                                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                                        </div>

                                        <button type="submit" name="changePassword" class="btn btn-primary">Change Password</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="control-sidebar control-sidebar-dark">
        </aside>

        <footer class="main-footer">
            <strong> &copy; <?php echo date('Y'); ?> </a> -</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Developed By Aaryan Pathak </b>
            </div>
        </footer>
    </div>

    <?php include('includes/footer.php'); ?>

</body>

</html>