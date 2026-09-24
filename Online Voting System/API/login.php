<?php
session_start();
include("connect.php");

// Verify Google reCAPTCHA
$recaptcha_secret = "6Le0_dArAAAAAJ2VWwsDuHKLtbv7Lx0B7mO_0Cbz";
$recaptcha_response = $_POST['g-recaptcha-response'];
$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$recaptcha_response);
$response_keys = json_decode($response, true);

$email = $_POST["email"];
$mobile = $_POST["mobile"];
$password = $_POST["password"];
$role = $_POST["role"];

// Check user credentials
$stmt = mysqli_prepare($con, "SELECT * FROM user WHERE email=? AND mobile=? AND password=? AND role=?");
mysqli_stmt_bind_param($stmt, "sssi", $email, $mobile, $password, $role);
mysqli_stmt_execute($stmt);
$check = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($check) > 0){
    $userdata = mysqli_fetch_assoc($check);

    // Get group data
    $groups_query = mysqli_query($con, "SELECT * FROM user WHERE role=2");
    $groupsdata = mysqli_fetch_all($groups_query, MYSQLI_ASSOC);

    $_SESSION["userdata"] = $userdata;
    $_SESSION["groupsdata"] = $groupsdata;

    echo "<script>window.location = '../Routes/dashboard.php';</script>";
} else {
    echo "<script>alert('Invalid Credentials or User not found!'); window.location = '../';</script>";
}
mysqli_stmt_close($stmt);
?>
