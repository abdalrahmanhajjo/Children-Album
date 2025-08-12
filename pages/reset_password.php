<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    header("HTTP/1.0 400 Bad Request");
    die("Invalid reset token");
}

try {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Invalid or expired reset link. Please request a new one.";
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || empty($confirm_password)) {
            $error = "Please fill in all fields";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords don't match";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters";
        } else {
            // Update password and clear reset token
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
            $stmt->execute([$password_hash, $user['user_id']]);
            
            $success = "Password updated successfully! You can now login.";
        }
    }
} catch (PDOException $e) {
    $error = "Database error. Please try again later.";
}

$page_title = "Reset Password";
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-20 bg-gray-50 min-h-screen flex items-center">
    <div class="container max-w-md mx-auto">
        <div class="bg-white rounded-xl shadow-md p-8">
            <h1 class="text-3xl font-bold text-center text-pink-600 mb-6">Reset Password</h1>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-4">
                        <a href="<?php echo SITE_URL; ?>/login.php" 
                           class="inline-block px-4 py-2 bg-pink-600 text-white rounded hover:bg-pink-700">
                            Go to Login
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="reset_password" value="1">
                    
                    <div>
                        <label for="password" class="block text-gray-700 font-medium mb-2">New Password</label>
                        <input type="password" id="password" name="password" required minlength="8"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        <p class="text-sm text-gray-500 mt-1">Minimum 8 characters</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    
                    <button type="submit" 
                            class="w-full px-6 py-3 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition-all">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>