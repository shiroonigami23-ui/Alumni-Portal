<?php
// includes/auth_check.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_COOKIE['jwt_token'])) {
    header('Location: login.php');
    exit();
}

// You can add more authentication checks here
// For example, verify JWT token from cookie
?>