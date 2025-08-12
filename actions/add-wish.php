<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$child_id = $_POST['child_id'] ?? null;
$sender_name = sanitize_input($_POST['sender_name']);
$message = sanitize_input($_POST['message']);
$relationship = sanitize_input($_POST['relationship'] ?? 'Other');

// Validate inputs
if (empty($child_id)) {
    $_SESSION['error'] = "Invalid child";
    header("Location: ../index.php");
    exit;
}

if (empty($sender_name)) {
    $_SESSION['error'] = "Your name is required";
    header("Location: ../view-child.php?id=$child_id#wishes");
    exit;
}

if (empty($message)) {
    $_SESSION['error'] = "Message is required";
    header("Location: ../view-child.php?id=$child_id#wishes");
    exit;
}

// Insert wish
$stmt = $pdo->prepare("INSERT INTO wishes (child_id, sender_name, message, relationship) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$child_id, $sender_name, $message, $relationship])) {
    $_SESSION['success'] = "Thank you for your wish!";
} else {
    $_SESSION['error'] = "Failed to add wish. Please try again.";
}

header("Location: ../view-child.php?id=$child_id#wishes");
exit;
?>