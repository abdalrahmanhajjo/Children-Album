<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verify CSRF token
if (!verify_csrf_token()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Check if user is logged in
redirect_if_not_logged_in();

// Validate input
if (!isset($_POST['child_id']) || !isset($_POST['theme'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$child_id = (int)$_POST['child_id'];
$theme = $_POST['theme'];

// Verify child belongs to current user
$child = get_child_details($child_id);
if (!$child || $child['user_id'] != get_current_user_id()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Update theme in database
try {
    $stmt = $pdo->prepare("UPDATE children SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $child_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>