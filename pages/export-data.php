<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. SECURITY CHECKS
if (!is_logged_in()) {
    die(json_encode(['error' => 'Please log in to export data']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die(json_encode(['error' => 'Invalid request']));
}

try {
    // 2. GET USER DATA
    $stmt = $pdo->prepare("SELECT username, email, full_name, created_at FROM users WHERE user_id = ?");
    if (!$stmt->execute([$_SESSION['user_id']])) {
        throw new Exception("Failed to fetch user data");
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. GET ALL CHILDREN WITH THEIR DATA
    $stmt = $pdo->prepare("SELECT * FROM children WHERE user_id = ?");
    if (!$stmt->execute([$_SESSION['user_id']])) {
        throw new Exception("Failed to fetch children data");
    }
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. ADD PHOTOS AND MILESTONES TO EACH CHILD
    foreach ($children as &$child) {
        // Get photos - using only columns that exist in your table
        $stmt = $pdo->prepare("SELECT image_path, created_at FROM gallery WHERE child_id = ?");
        if (!$stmt->execute([$child['child_id']])) {
            throw new Exception("Failed to fetch photos");
        }
        $child['photos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get milestones
        $stmt = $pdo->prepare("SELECT title, description, date_achieved FROM milestones WHERE child_id = ? ORDER BY date_achieved");
        if (!$stmt->execute([$child['child_id']])) {
            throw new Exception("Failed to fetch milestones");
        }
        $child['milestones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get wishes
        $stmt = $pdo->prepare("SELECT sender_name, message, relationship, created_at FROM wishes WHERE child_id = ?");
        if (!$stmt->execute([$child['child_id']])) {
            throw new Exception("Failed to fetch wishes");
        }
        $child['wishes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. PREPARE EXPORT DATA
    $exportData = [
        'user' => $user,
        'children' => $children,
        'export_date' => date('Y-m-d H:i:s'),
        'app_name' => SITE_NAME,
        'app_url' => SITE_URL
    ];

    // 6. SEND AS JSON FILE
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="children_album_export_' . date('Y-m-d') . '.json"');
    echo json_encode($exportData, JSON_PRETTY_PRINT);
    exit;

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
}