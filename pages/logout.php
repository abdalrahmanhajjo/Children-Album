<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

logout_user();

// Redirect to login page
header('Location: ' . SITE_URL . '/pages/login.php');
exit;
?>