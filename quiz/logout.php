<?php
// Always start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the homepage after logout
header("Location: index.html");
exit(); // Important to prevent further script execution
?>