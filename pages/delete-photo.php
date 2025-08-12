<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/i18n.php';

redirect_if_not_logged_in();

$i18n = new i18n();

// Check if photo ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = $i18n->translate('no_photo_specified');
    header('Location: dashboard.php');
    exit;
}

$photo_id = (int)$_GET['id'];

// Get photo details
$photo = get_photo_details($photo_id);
if (!$photo) {
    $_SESSION['error_message'] = $i18n->translate('photo_not_found');
    header('Location: dashboard.php');
    exit;
}

// Verify the photo belongs to a child owned by the current user
$child = get_child_details($photo['child_id']);
if (!$child || $child['user_id'] != get_current_user_id()) {
    $_SESSION['error_message'] = $i18n->translate('unauthorized_access');
    header('Location: dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = $i18n->translate('invalid_csrf');
        header("Location: album.php?id=" . $child['id']);
        exit;
    }

    // Attempt to delete the photo
    if (delete_photo($photo_id)) {
        $_SESSION['success_message'] = $i18n->translate('photo_deleted_success');
    } else {
        $_SESSION['error_message'] = $i18n->translate('delete_error');
    }
    
    header("Location: album.php?id=" . $child['id'] . "#gallery");
    exit;
}

$page_title = $i18n->translate('delete_photo');
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0"><?= $i18n->translate('delete_photo') ?></h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h5><?= $i18n->translate('confirm_delete') ?></h5>
                        <p><?= $i18n->translate('delete_photo_warning') ?></p>
                        
                        <div class="text-center my-4">
                            <img src="../uploads/<?= htmlspecialchars($photo['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($photo['title'] ?? '') ?>" 
                                 class="img-fluid rounded" style="max-height: 300px;">
                            <h5 class="mt-3"><?= htmlspecialchars($photo['title'] ?? $i18n->translate('untitled')) ?></h5>
                            <?php if ($photo['date_taken']): ?>
                                <p><?= date('F j, Y', strtotime($photo['date_taken'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="d-flex justify-content-between">
                            <a href="album.php?id=<?= $child['id'] ?>#gallery" class="btn btn-secondary">
                                <?= $i18n->translate('cancel') ?>
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-1"></i>
                                <?= $i18n->translate('confirm_delete') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>