<?php
session_start();
require_once("config.php");

// 1. Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['uid'])) {
    header("location:login.php");
    exit();
}

// 2. Check if a property ID (pid) was passed in the URL.
if (!isset($_GET['pid'])) {
    // If no pid, redirect to the main property listing page.
    header("location:property.php");
    exit();
}

// 3. Get the User ID from the session and the Property ID from the URL.
$uid = $_SESSION['uid'];
$pid = $_GET['pid'];

// 4. (Recommended) Check if the user has already sent a request for this property to avoid spam.
$query_check = mysqli_query($con, "SELECT * FROM inquiry WHERE uid='$uid' AND pid='$pid'");

if (mysqli_num_rows($query_check) > 0) {
    // If a request already exists, redirect back with a 'duplicate' status.
    header("location:propertydetail.php?pid=$pid&status=duplicate");
    exit();
} else {
    // 5. If no duplicate found, insert the new inquiry into the database.
    $sql = "INSERT INTO inquiry (uid, pid) VALUES ('$uid', '$pid')";
    $result = mysqli_query($con, $sql);

    if ($result) {
        // If the insertion was successful, redirect back with a 'success' status.
        header("location:propertydetail.php?pid=$pid&status=success");
        exit();
    } else {
        // If there was a database error, redirect back with a 'failed' status.
        header("location:propertydetail.php?pid=$pid&status=failed");
        exit();
    }
}
?>