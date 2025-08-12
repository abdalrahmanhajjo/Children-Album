<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$error = '';
$success = '';
$confirmationRequired = true;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Verify password if this is the confirmation step
        if (isset($_POST['password'])) {
            try {
                // Get user's current password hash
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($_POST['password'], $user['password_hash'])) {
                    throw new Exception("Incorrect password. Please try again.");
                }
                
                // Password verified - proceed with deletion
                $pdo->beginTransaction();
                
                // Get all child IDs for this user
                $stmt = $pdo->prepare("SELECT child_id FROM children WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $childIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Delete related data in proper order (foreign key constraints)
                if (!empty($childIds)) {
                    // Delete wishes
                    $placeholders = implode(',', array_fill(0, count($childIds), '?'));
                    $stmt = $pdo->prepare("DELETE FROM wishes WHERE child_id IN ($placeholders)");
                    $stmt->execute($childIds);
                    
                    // Delete milestones
                    $stmt = $pdo->prepare("DELETE FROM milestones WHERE child_id IN ($placeholders)");
                    $stmt->execute($childIds);
                    
                    // Delete gallery items
                    $stmt = $pdo->prepare("DELETE FROM gallery WHERE child_id IN ($placeholders)");
                    $stmt->execute($childIds);
                    
                    // Delete memory books
                    $stmt = $pdo->prepare("DELETE FROM memory_books WHERE child_id IN ($placeholders)");
                    $stmt->execute($childIds);
                    
                    // Delete share links
                    $stmt = $pdo->prepare("DELETE FROM share_links WHERE child_id IN ($placeholders)");
                    $stmt->execute($childIds);
                }
                
                // Delete children
                $stmt = $pdo->prepare("DELETE FROM children WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                // Finally delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                $pdo->commit();
                
                // Logout and destroy session
                session_unset();
                session_destroy();
                
                // Redirect to goodbye page
                header('Location: ' . SITE_URL . '/pages/goodbye.php');
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Account deletion failed: ' . $e->getMessage();
            }
        } else {
            // This is the initial confirmation step
            $confirmationRequired = true;
        }
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-8">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Delete Your Account</h1>
                <p class="text-gray-600 mb-6">This action cannot be undone. All your data will be permanently removed.</p>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($confirmationRequired): ?>
                    <form method="POST" class="mt-8 space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        
                        <div class="rounded-md shadow-sm -space-y-px">
                            <div>
                                <label for="password" class="sr-only">Password</label>
                                <input id="password" name="password" type="password" autocomplete="current-password" required
                                       class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm"
                                       placeholder="Enter your password to confirm">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="text-sm">
                                <a href="<?= SITE_URL ?>/pages/settings.php" class="font-medium text-purple-600 hover:text-purple-500">
                                    Cancel and return to settings
                                </a>
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit"
                                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <i class="fas fa-trash-alt"></i>
                                </span>
                                Permanently Delete Account
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>