<?php
include("connect.php");

// Verify Google reCAPTCHA
$recaptcha_secret = "6Le0_dArAAAAAJ2VWwsDuHKLtbv7Lx0B7mO_0Cbz";
$recaptcha_response = $_POST['g-recaptcha-response'];
$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$recaptcha_response);
$response_keys = json_decode($response, true);

if(intval($response_keys["success"]) !== 1) {
    echo '<script>
            alert("CAPTCHA verification failed. Please try again.");
            window.location = "../Routes/register.html";
          </script>';
    exit();
}

$name = trim($_POST["name"]);
$mobile = trim($_POST["mobile"]);
$aadhaar = trim($_POST["aadhaar"]);
$voterid = trim($_POST["voterid"]);
$email = trim($_POST["email"]);
$password = $_POST["password"];
$cpassword = $_POST["cpassword"];
$address = trim($_POST["address"]);
$image = $_FILES["photo"]["name"];
$tmp_name = $_FILES["photo"]["tmp_name"];
$role = $_POST["role"];

// Validate all fields are filled
if (
    empty($name) || empty($mobile) || empty($aadhaar) || empty($voterid) || 
    empty($email) || empty($password) || empty($cpassword) || 
    empty($address) || empty($image) || empty($role) 
) {
    echo "<script>alert('Error: All fields are required!'); window.location = '../Routes/register.html';</script>";
    exit();
}

// Server-side validations
if (!preg_match('/^[0-9]{10}$/', $mobile)) {
    echo "<script>alert('Enter a valid 10-digit mobile number!'); window.location = '../Routes/register.html';</script>";
    exit();
}

if (!preg_match('/^[0-9]{12}$/', $aadhaar)) {
    echo "<script>alert('Enter a valid 12-digit Aadhaar number!'); window.location = '../Routes/register.html';</script>";
    exit();
}

if (!preg_match('/^[A-Z]{3}[0-9]{7}$/i', $voterid)) {
    echo "<script>alert('Enter a valid Voter ID format (e.g., ABC1234567)!'); window.location = '../Routes/register.html';</script>";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Enter a valid email address!'); window.location = '../Routes/register.html';</script>";
    exit();
}

if (strlen($password) < 6) {
    echo "<script>alert('Password must be at least 6 characters!'); window.location = '../Routes/register.html';</script>";
    exit();
}

if ($password !== $cpassword) {
    echo "<script>alert('Password and Confirm password do not match'); window.location = '../Routes/register.html';</script>";
    exit();
}

// Check for duplicate mobile, email, aadhaar, or voter id
$stmt_check = mysqli_prepare($con, "SELECT id FROM user WHERE mobile=? OR email=? OR aadhaar_no=? OR voter_id=?");
mysqli_stmt_bind_param($stmt_check, "ssss", $mobile, $email, $aadhaar, $voterid);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    echo "<script>alert('Mobile, Email, Aadhaar, or Voter ID is already registered!'); window.location = '../Routes/register.html';</script>";
    exit();
}
mysqli_stmt_close($stmt_check);

// Move uploaded image
if (!move_uploaded_file($tmp_name, "../uploads/$image")) {
    echo "<script>alert('Image upload failed!'); window.location = '../Routes/register.html';</script>";
    exit();
}

// Insert data using prepared statement
$stmt_insert = mysqli_prepare($con, "INSERT INTO user (name, mobile, aadhaar_no, voter_id, email, address, password, photo, role, status, votes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)");
mysqli_stmt_bind_param($stmt_insert, "ssssssssi", $name, $mobile, $aadhaar, $voterid, $email, $address, $password, $image, $role);

if (mysqli_stmt_execute($stmt_insert)) {
    echo "<script>alert('Registration Successful!'); window.location = '../';</script>";
} else {
    echo "<script>alert('Some error occurred during registration!'); window.location = '../Routes/register.html';</script>";
}
mysqli_stmt_close($stmt_insert);
?>
