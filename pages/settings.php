<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

// Initialize variables
$error = '';
$success = '';
$userData = [];
$children = [];
$globalPrivacy = 'private';
$profileVisibility = 0;

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get children data
    $stmt = $pdo->prepare("SELECT child_id, name, is_public FROM children WHERE user_id = ? ORDER BY name");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate global privacy setting
    if (!empty($children)) {
        $publicCount = 0;
        foreach ($children as $child) {
            if ($child['is_public']) {
                $publicCount++;
            }
        }
        $globalPrivacy = ($publicCount > (count($children) / 2)) ? 'public' : 'private';
    }
    
    // Get profile visibility (with fallback for older installations)
    $profileVisibility = isset($userData['profile_public']) ? $userData['profile_public'] : 0;
    
} catch (PDOException $e) {
    $error = 'Failed to load user data: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update profile information
            if (isset($_POST['full_name'])) {
                $fullName = trim($_POST['full_name']);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
                $stmt->execute([$fullName, $_SESSION['user_id']]);
            }
            
            // Update email if changed
            if (isset($_POST['email']) && $_POST['email'] !== $userData['email']) {
                $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
                if ($email) {
                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                    $stmt->execute([$email, $_SESSION['user_id']]);
                    if ($stmt->fetch()) {
                        throw new Exception("This email is already registered");
                    }
                    
                    $stmt = $pdo->prepare("UPDATE users SET email = ?, is_verified = 0 WHERE user_id = ?");
                    $stmt->execute([$email, $_SESSION['user_id']]);
                    // Here you would typically send a new verification email
                }
            }
            
            // Update password if provided
            if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
                if (!password_verify($_POST['current_password'], $userData['password_hash'])) {
                    throw new Exception("Current password is incorrect");
                }
                
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    throw new Exception("New passwords don't match");
                }
                
                $newPasswordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->execute([$newPasswordHash, $_SESSION['user_id']]);
            }
            
            // Update privacy settings
            if (isset($_POST['global_privacy'])) {
                $globalPrivacy = $_POST['global_privacy'] === 'public' ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE children SET is_public = ? WHERE user_id = ?");
                $stmt->execute([$globalPrivacy, $_SESSION['user_id']]);
            }
            
            // Update individual child settings
            if (isset($_POST['child_privacy']) && is_array($_POST['child_privacy'])) {
                foreach ($_POST['child_privacy'] as $childId => $privacy) {
                    $isPublic = $privacy === 'public' ? 1 : 0;
                    $stmt = $pdo->prepare("UPDATE children SET is_public = ? WHERE child_id = ? AND user_id = ?");
                    $stmt->execute([$isPublic, $childId, $_SESSION['user_id']]);
                }
            }
            
            // Update profile visibility
            if (isset($_POST['profile_visibility'])) {
                $profileVisibility = $_POST['profile_visibility'] === 'public' ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE users SET profile_public = ? WHERE user_id = ?");
                $stmt->execute([$profileVisibility, $_SESSION['user_id']]);
            }
            
            $pdo->commit();
            $success = 'Settings updated successfully!';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to update settings: ' . $e->getMessage();
        }
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Account Settings</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-green-700"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Profile Information</h2>
                <p class="text-gray-600 mt-1">Update your account's profile information and email address.</p>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                        <div class="flex items-center">
                            <div class="h-12 w-12 rounded-full overflow-hidden bg-gray-200 mr-4">
                                <?php if (!empty($userData['profile_picture'])): ?>
                                    <img src="<?= htmlspecialchars($userData['profile_picture']) ?>" alt="Profile" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <div class="h-full w-full flex items-center justify-center text-gray-500">
                                        <i class="fas fa-user text-xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                                Change
                            </button>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <button type="submit" name="update_profile"
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                            Save Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Update Password</h2>
                <p class="text-gray-600 mt-1">Ensure your account is using a long, random password to stay secure.</p>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" id="current_password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" id="new_password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <button type="submit" name="update_password"
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                            Update Password
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Privacy Settings</h2>
                <p class="text-gray-600 mt-1">Control who can view your family's information.</p>
            </div>
            
            <form method="POST" class="divide-y divide-gray-200">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <!-- Global Privacy Setting -->
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Default Privacy</h2>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input id="global-public" name="global_privacy" type="radio" value="public" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500" 
                                   <?= $globalPrivacy === 'public' ? 'checked' : '' ?>>
                            <label for="global-public" class="ml-3 block text-sm font-medium text-gray-700">
                                Public - Anyone can view
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input id="global-private" name="global_privacy" type="radio" value="private" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500" 
                                   <?= $globalPrivacy === 'private' ? 'checked' : '' ?>>
                            <label for="global-private" class="ml-3 block text-sm font-medium text-gray-700">
                                Private - Only people I share with can view
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            This sets the default for all children. You can customize individual children below.
                        </p>
                    </div>
                </div>
                
                <!-- Individual Child Settings -->
                <?php if (!empty($children)): ?>
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Child-Specific Settings</h2>
                    <div class="space-y-6">
                        <?php foreach ($children as $child): ?>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-md font-medium text-gray-800"><?= htmlspecialchars($child['name']) ?></h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Currently: <?= $child['is_public'] ? 'Public' : 'Private' ?>
                                    </p>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center">
                                        <input id="child-<?= $child['child_id'] ?>-public" 
                                               name="child_privacy[<?= $child['child_id'] ?>]" 
                                               type="radio" value="public"
                                               class="h-4 w-4 text-purple-600 focus:ring-purple-500"
                                               <?= $child['is_public'] ? 'checked' : '' ?>>
                                        <label for="child-<?= $child['child_id'] ?>-public" class="ml-2 text-sm text-gray-700">
                                            Public
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="child-<?= $child['child_id'] ?>-private" 
                                               name="child_privacy[<?= $child['child_id'] ?>]" 
                                               type="radio" value="private"
                                               class="h-4 w-4 text-purple-600 focus:ring-purple-500"
                                               <?= !$child['is_public'] ? 'checked' : '' ?>>
                                        <label for="child-<?= $child['child_id'] ?>-private" class="ml-2 text-sm text-gray-700">
                                            Private
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Profile Visibility -->
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Profile Visibility</h2>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input id="profile-public" name="profile_visibility" type="radio" value="public" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500" 
                                   <?= $profileVisibility ? 'checked' : '' ?>>
                            <label for="profile-public" class="ml-3 block text-sm font-medium text-gray-700">
                                Show my profile in public directories
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input id="profile-private" name="profile_visibility" type="radio" value="private" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500" 
                                   <?= !$profileVisibility ? 'checked' : '' ?>>
                            <label for="profile-private" class="ml-3 block text-sm font-medium text-gray-700">
                                Hide my profile from public view
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Save Button -->
                <div class="p-6 bg-gray-50 text-right">
                    <button type="submit" name="update_privacy"
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        Save Privacy Settings
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Account Actions</h2>
                <p class="text-gray-600 mt-1">Manage your account and data.</p>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-md font-medium text-gray-800">Export Data</h3>
                        <p class="text-sm text-gray-500 mt-1">Download all your data in a portable format.</p>
                    </div>
                    <button type="button" onclick="exportData()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Export Data
                    </button>
                </div>
                
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-md font-medium text-gray-800">Delete Account</h3>
                        <p class="text-sm text-gray-500 mt-1">Permanently delete your account and all associated data.</p>
                    </div>
                    <button type="button" onclick="confirmDelete()" class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        // You would typically redirect to a delete confirmation page
        window.location.href = '<?= SITE_URL ?>/pages/delete_account.php';
    }
}
function exportData() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        // You would typically redirect to a delete confirmation page
        window.location.href = '<?= SITE_URL ?>/pages/export-data.php';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>