<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // The plain text password
    $confirmPassword = $_POST['confirm-password'];

    // Basic validation
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        echo "<script>alert('All fields are required!'); window.location.href='signup.html';</script>";
        exit();
    }

    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.location.href='signup.html';</script>";
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already registered!'); window.location.href='signup.html';</script>";
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Insert new user into the database with the plain text password
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    // The password is bound directly without hashing
    $stmt->bind_param("sss", $fullName, $email, $password);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! You can now log in.'); window.location.href='login.html';</script>";
    } else {
        echo "<script>alert('Error during registration: " . $stmt->error . "'); window.location.href='signup.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>