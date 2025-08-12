<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/i18n.php';
require_once '../includes/functions.php';

redirect_if_not_logged_in();

$i18n = new i18n();

// Initialize error array
$errors = [];

// Check if photo ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errors[] = $i18n->translate('invalid_photo_id');
} else {
    $photo_id = (int)$_GET['id'];
    $photo = get_photo_details($photo_id);

    // Verify photo exists
    if (!$photo) {
        $errors[] = $i18n->translate('photo_not_found');
    } else {
        $child = get_child_details($photo['child_id']);
        // Verify photo belongs to current user
        if (!$child || $child['user_id'] != get_current_user_id()) {
            $errors[] = $i18n->translate('unauthorized_access');
        }
    }
}

// Handle form submission only if no errors exist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (!verify_csrf_token()) {
        $errors[] = $i18n->translate('invalid_csrf_token');
    } else {
        // Sanitize inputs
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date_taken = trim($_POST['date_taken'] ?? '');

        // Validate inputs
        if (strlen($title) > 100) {
            $errors['title'] = $i18n->translate('title_too_long');
        }
        
        if ($date_taken && !strtotime($date_taken)) {
            $errors['date_taken'] = $i18n->translate('invalid_date_format');
        }

        // Process if no errors
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE child_gallery 
                                      SET title = ?, description = ?, date_taken = ?
                                      WHERE id = ?");
                $stmt->execute([
                    $title ?: null,
                    $description ?: null,
                    $date_taken ? date('Y-m-d', strtotime($date_taken)) : null,
                    $photo_id
                ]);
                
                $_SESSION['success_message'] = $i18n->translate('photo_updated_success');
                header("Location: album.php?id={$child['id']}#gallery");
                exit;
            } catch (PDOException $e) {
                $errors['database'] = $i18n->translate('update_error') . ": " . $e->getMessage();
            }
        }
    }
}

$page_title = $i18n->translate('edit_photo');
require_once '../includes/header.php';

// If there are any initialization errors, show them and stop rendering the form
if (!empty($errors) && !isset($photo)): ?>
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="text-red-500 mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?= $error ?></p>
                <?php endforeach; ?>
            </div>
            <a href="dashboard.php" class="text-blue-500 hover:underline">Return to Dashboard</a>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<!-- Modern UI with sidebar layout -->
<div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
        <div class="container mx-auto px-4 py-8">
            <!-- Rest of your HTML form remains exactly the same -->
            <!-- ... -->
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>