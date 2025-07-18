<?php
// Redirect to dashboard or login page based on session
session_start();

// Check if user is logged in
if (isset($_SESSION['admin_user']) && $_SESSION['admin_user'] === true) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.php');
    exit();
} else {
    // User is not logged in, redirect to login
    header('Location: login.php');
    exit();
}
?>