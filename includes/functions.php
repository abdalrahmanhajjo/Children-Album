<?php

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect_if_not_logged_in() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}
// Authentication functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user's ID
 */
// In includes/functions.php (or includes/config.php if functions are there)
function get_current_user_id() {
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    return null; // or throw an error if you want to enforce login
}


/**
 * Get user by username
 */
function get_user_by_username($username) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function validate_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function upload_file($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File is too large'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $destination = UPLOAD_PATH . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return ['success' => true, 'filename' => $filename];
}

function calculate_age($birth_date) {
    $birthday = new DateTime($birth_date);
    $now = new DateTime();
    $interval = $now->diff($birthday);
    
    if ($interval->y > 0) {
        return $interval->y . ' years';
    } elseif ($interval->m > 0) {
        return $interval->m . ' months';
    } else {
        return $interval->d . ' days';
    }
}

function get_user_children($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM children WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!function_exists('get_photo_details')) {
    function get_photo_details($photo_id) {
        // function implementation
    }
}

function get_child_details($child_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM children WHERE child_id = ?");
        $stmt->execute([$child_id]);
        return $stmt->fetch(); // This returns false if no record found
    } catch (PDOException $e) {
        error_log("Error getting child details: " . $e->getMessage());
        return false;
    }
}

function delete_photo($photo_id) {
    global $pdo;
    try {
        // Get photo details first
        $photo = get_photo_details($photo_id);
        if (!$photo) return false;

        // Delete the file
        $file_path = "../uploads/" . $photo['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM child_gallery WHERE id = ?");
        return $stmt->execute([$photo_id]);
    } catch (PDOException $e) {
        error_log("Error deleting photo: " . $e->getMessage());
        return false;
    }
}

function get_child_gallery($child_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM gallery WHERE child_id = ? ORDER BY date_taken DESC");
    $stmt->execute([$child_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_child_milestones($child_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM milestones WHERE child_id = ? ORDER BY date_achieved DESC");
    $stmt->execute([$child_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_child_wishes($child_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM wishes WHERE child_id = ? ORDER BY created_at DESC");
    $stmt->execute([$child_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function truncate($string, $length = 100, $append = "...") {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $append;
}
function get_user_name($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user ? $user['full_name'] : 'Anonymous';
}
function get_user_data($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in get_user_data: " . $e->getMessage());
        return false;
    }
}
?>