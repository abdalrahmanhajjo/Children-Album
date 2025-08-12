<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
} else {
    die('Invalid request');
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // First, get all children IDs for this user
    $stmt = $pdo->prepare("SELECT child_id FROM children WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Delete all photos (files and database records)
    if (!empty($children)) {
        // Get all photo filenames
        $placeholders = implode(',', array_fill(0, count($children), '?'));
        $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE child_id IN ($placeholders)");
        $stmt->execute($children);
        $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete photo files
        foreach ($photos as $photo) {
            $filePath = __DIR__ . '/../uploads/gallery/' . $photo;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete gallery records
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE child_id IN ($placeholders)");
        $stmt->execute($children);
    }

    // Delete profile picture if exists
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profilePic = $stmt->fetchColumn();

    if ($profilePic) {
        $filePath = __DIR__ . '/../uploads/profile_pics/' . $profilePic;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Delete user record (this will cascade delete children, milestones, etc. due to foreign keys)
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Commit transaction
    $pdo->commit();

    // Destroy session and redirect
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php?account_deleted=1');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Account deletion error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete account. Please try again.';
    header('Location: ' . SITE_URL . '/pages/profile.php');
    exit;
}