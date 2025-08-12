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
$globalPrivacy = 'private';
$children = [];
$profileVisibility = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update global privacy setting if provided
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
            $success = 'Privacy settings updated successfully!';
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to update privacy settings: ' . $e->getMessage();
        }
    }
}

// Get current settings
try {
    // Get children and their current privacy settings
    $stmt = $pdo->prepare("SELECT child_id, name, is_public FROM children WHERE user_id = ? ORDER BY name");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determine global privacy setting (majority)
    $publicCount = 0;
    foreach ($children as $child) {
        if ($child['is_public']) {
            $publicCount++;
        }
    }
    $globalPrivacy = ($publicCount > (count($children) / 2)) ? 'public' : 'private';
    
    // Get profile visibility setting
    $stmt = $pdo->prepare("SELECT profile_public FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profileVisibility = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = 'Failed to load current privacy settings: ' . $e->getMessage();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../includes/header.php';
?>


<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-800">Privacy Settings</h1>
                <p class="text-gray-600 mt-2">Control who can view your family's information</p>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mx-6 mt-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <p class="text-red-700"><?= htmlspecialchars($_SESSION['error']) ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mx-6 mt-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <p class="text-green-700"><?= htmlspecialchars($_SESSION['success']) ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
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
                    <button type="submit" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        Save Privacy Settings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Privacy Help Section -->
        <div class="mt-8 bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Understanding Privacy Settings</h2>
                <div class="prose prose-sm text-gray-600">
                    <p><strong>Public:</strong> Anyone can view this content, even without an account. This is great for sharing with extended family.</p>
                    <p><strong>Private:</strong> Only people you specifically share with can view this content. We recommend this for sensitive information.</p>
                    <p class="mt-4">Your privacy settings affect:</p>
                    <ul class="list-disc pl-5">
                        <li>Who can see your children's profiles</li>
                        <li>Visibility in search results</li>
                        <li>Access to photos and milestones</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>