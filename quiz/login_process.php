<?php
include 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password']; // Password from the login form

    // Basic validation
    if (empty($email) || empty($password)) {
        echo "<script>alert('Both email and password are required!'); window.location.href='login.html';</script>";
        exit();
    }

    // Retrieve user and their stored password from the database
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $username, $stored_password); // Fetches the plain text password from DB
    $stmt->fetch();

    // Direct comparison of the submitted password and the stored password
    if ($stmt->num_rows > 0 && $password === $stored_password) {
        // Login successful
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        echo "<script>alert('Login successful! Welcome, " . $username . "!'); window.location.href='dashboard.php';</script>";
    } else {
        // Login failed
        echo "<script>alert('Invalid email or password!'); window.location.href='login.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>