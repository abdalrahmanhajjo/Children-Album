<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isset($_GET['child_id'])) {
    echo json_encode(['photos' => 0, 'milestones' => 0, 'wishes' => 0]);
    exit;
}

$child_id = $_GET['child_id'];

// Verify child belongs to user
$stmt = $pdo->prepare("SELECT child_id FROM children WHERE child_id = ? AND user_id = ?");
$stmt->execute([$child_id, get_current_user_id()]);

if ($stmt->fetch()) {
    // Get counts
    $photos = $pdo->prepare("SELECT COUNT(*) FROM gallery WHERE child_id = ?")->execute([$child_id])->fetchColumn();
    $milestones = $pdo->prepare("SELECT COUNT(*) FROM milestones WHERE child_id = ?")->execute([$child_id])->fetchColumn();
    $wishes = $pdo->prepare("SELECT COUNT(*) FROM wishes WHERE child_id = ?")->execute([$child_id])->fetchColumn();
    
    echo json_encode([
        'photos' => $photos,
        'milestones' => $milestones,
        'wishes' => $wishes
    ]);
} else {
    echo json_encode(['photos' => 0, 'milestones' => 0, 'wishes' => 0]);
}