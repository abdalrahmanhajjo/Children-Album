<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

// First, check if username_changes table exists and create if not
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'username_changes'");
    if ($tableCheck->rowCount() == 0) {
        // Create the username_changes table
        $pdo->exec("
            CREATE TABLE `username_changes` (
                `change_id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `old_username` varchar(50) NOT NULL,
                `new_username` varchar(50) NOT NULL,
                `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`change_id`),
                KEY `user_id` (`user_id`),
                KEY `changed_at` (`changed_at`),
                CONSTRAINT `username_changes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
} catch (PDOException $e) {
    // Table creation failed, but continue
}

// Initialize variables
$error = '';
$success = '';
$userData = [];
$children = [];
$canChangeUsername = true;
$daysUntilNextChange = 0;
$changesRemaining = 2;

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        throw new Exception('User not found');
    }

    // Check username change history if table exists
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as recent_changes 
            FROM username_changes 
            WHERE user_id = ? 
            AND changed_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $changeData = $stmt->fetch(PDO::FETCH_ASSOC);
        $recentChanges = $changeData['recent_changes'] ?? 0;

        $stmt = $pdo->prepare("
            SELECT MAX(changed_at) as last_change 
            FROM username_changes 
            WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $lastChangeData = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastUsernameChange = $lastChangeData['last_change'] ?? null;

        // Calculate if user can change username
        if ($recentChanges < 2) {
            $canChangeUsername = true;
            $changesRemaining = 2 - $recentChanges;
            
            if ($lastUsernameChange && $recentChanges >= 2) {
                $lastChange = new DateTime($lastUsernameChange);
                $fourteenDaysLater = clone $lastChange;
                $fourteenDaysLater->add(new DateInterval('P14D'));
                $now = new DateTime();
                
                if ($now < $fourteenDaysLater) {
                    $canChangeUsername = false;
                    $daysUntilNextChange = $now->diff($fourteenDaysLater)->days + 1;
                }
            }
        } else {
            $canChangeUsername = false;
            // Calculate when the oldest change within 14 days will expire
            $stmt = $pdo->prepare("
                SELECT MIN(changed_at) as oldest_change 
                FROM username_changes 
                WHERE user_id = ? 
                AND changed_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $oldestChange = $stmt->fetchColumn();
            
            if ($oldestChange) {
                $oldestDate = new DateTime($oldestChange);
                $fourteenDaysLater = clone $oldestDate;
                $fourteenDaysLater->add(new DateInterval('P14D'));
                $now = new DateTime();
                $daysUntilNextChange = $now->diff($fourteenDaysLater)->days + 1;
            }
        }
    } catch (PDOException $e) {
        // Table doesn't exist or query failed, allow username change
        $canChangeUsername = true;
        $changesRemaining = 2;
    }

    // Get user's children with stats
    $stmt = $pdo->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM gallery WHERE child_id = c.child_id) as photo_count,
               (SELECT COUNT(*) FROM milestones WHERE child_id = c.child_id) as milestone_count,
               (SELECT COUNT(*) FROM wishes WHERE child_id = c.child_id) as wish_count
        FROM children c 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        // Handle profile information update
        if (isset($_POST['update_profile'])) {
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');

            // Validation
            if (empty($full_name)) {
                throw new Exception('Full name is required');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            // Check if email is already taken
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception('Email address is already in use');
            }

            // Handle username change if different
            if ($username !== $userData['username']) {
                if (!$canChangeUsername) {
                    if ($daysUntilNextChange > 0) {
                        throw new Exception("You can change your username again in {$daysUntilNextChange} days");
                    } else {
                        throw new Exception("You have reached the maximum number of username changes (2) in the last 14 days");
                    }
                }

                // Validate username
                if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
                    throw new Exception('Username must be 3-20 characters and contain only letters, numbers, and underscores');
                }

                // Check if username is taken
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
                $stmt->execute([$username, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    throw new Exception('Username is already taken');
                }

                // Log username change (only if table exists)
                try {
                    $stmt = $pdo->prepare("INSERT INTO username_changes (user_id, old_username, new_username, changed_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$_SESSION['user_id'], $userData['username'], $username]);
                } catch (PDOException $e) {
                    // Table doesn't exist, continue without logging
                }
            }

            // Update user data
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$full_name, $email, $username, $_SESSION['user_id']]);

            // Update session
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $username;

            $success = 'Profile updated successfully!';
            
            // Refresh user data
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        }

        // Handle password change
        if (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Verify current password
            if (!password_verify($current_password, $userData['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }

            // Validate new password
            if (strlen($new_password) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }

            if ($new_password !== $confirm_password) {
                throw new Exception('New passwords do not match');
            }

            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$new_hash, $_SESSION['user_id']]);

            $success = 'Password changed successfully!';
        }

        // Handle profile picture upload
        if (isset($_POST['upload_picture']) && isset($_FILES['profile_picture'])) {
            $file = $_FILES['profile_picture'];
            
            // Validate file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception('File size too large. Maximum size is 5MB.');
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $upload_path = __DIR__ . '/../uploads/profile_pics/' . $filename;

            // Create directory if it doesn't exist
            $upload_dir = dirname($upload_path);
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Delete old profile picture if exists
            if (!empty($userData['profile_picture'])) {
                $old_path = __DIR__ . '/../uploads/profile_pics/' . $userData['profile_picture'];
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
            }

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update database
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$filename, $_SESSION['user_id']]);
                
                $success = 'Profile picture updated successfully!';
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=2");
                exit;
            } else {
                throw new Exception('Failed to upload file');
            }
        }

        // Handle profile picture removal
        if (isset($_POST['remove_picture'])) {
            if (!empty($userData['profile_picture'])) {
                $old_path = __DIR__ . '/../uploads/profile_pics/' . $userData['profile_picture'];
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
                
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = NULL, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                $success = 'Profile picture removed successfully!';
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=3");
                exit;
            }
        }
    }

    // Handle success messages from redirects
    if (isset($_GET['success'])) {
        switch ($_GET['success']) {
            case '1':
                $success = 'Profile updated successfully!';
                break;
            case '2':
                $success = 'Profile picture updated successfully!';
                break;
            case '3':
                $success = 'Profile picture removed successfully!';
                break;
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set profile picture
$profilePic = !empty($userData['profile_picture']) 
    ? SITE_URL . '/uploads/profile_pics/' . $userData['profile_picture']
    : null;

// Helper function to calculate age
function calculateAge($birthDate) {
    $birthDate = new DateTime($birthDate);
    $today = new DateTime();
    $interval = $today->diff($birthDate);
    
    if ($interval->y > 0) {
        return $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
    } elseif ($interval->m > 0) {
        return $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
    } else {
        return $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
    }
}

// Get total statistics
$totalPhotos = 0;
$totalMilestones = 0;
foreach ($children as $child) {
    $totalPhotos += $child['photo_count'] ?? 0;
    $totalMilestones += $child['milestone_count'] ?? 0;
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .profile-avatar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .tab-active {
        border-bottom: 3px solid #667eea;
        color: #667eea;
    }
    .stat-card {
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .upload-area {
        border: 2px dashed #cbd5e0;
        transition: all 0.3s;
    }
    .upload-area:hover {
        border-color: #667eea;
        background-color: #f7fafc;
    }
    .child-card {
        transition: all 0.3s;
    }
    .child-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
</style>

<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Profile Header Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
                <!-- Cover Image -->
                <div class="h-48 bg-gradient-to-r from-purple-500 via-pink-500 to-red-500"></div>
                
                <!-- Profile Info -->
                <div class="relative px-8 pb-8">
                    <!-- Avatar -->
                    <div class="absolute -top-20 left-8">
                        <div class="relative group">
                            <?php if ($profilePic): ?>
                                <img src="<?= htmlspecialchars($profilePic) ?>" 
                                     alt="Profile" 
                                     class="w-40 h-40 rounded-full border-4 border-white shadow-xl object-cover">
                            <?php else: ?>
                                <div class="w-40 h-40 rounded-full border-4 border-white shadow-xl profile-avatar flex items-center justify-center">
                                    <span class="text-white text-5xl font-bold">
                                        <?= strtoupper(substr($userData['username'] ?? 'U', 0, 1)) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Upload Overlay -->
                            <form method="POST" enctype="multipart/form-data" class="absolute inset-0">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <label for="profile_pic_upload" class="absolute inset-0 rounded-full bg-black bg-opacity-0 hover:bg-opacity-50 flex items-center justify-center cursor-pointer transition-all opacity-0 group-hover:opacity-100">
                                    <i class="fas fa-camera text-white text-2xl"></i>
                                </label>
                                <input type="file" id="profile_pic_upload" name="profile_picture" class="hidden" accept="image/*" onchange="this.form.submit()">
                                <input type="hidden" name="upload_picture" value="1">
                            </form>
                        </div>
                    </div>
                    
                    <!-- User Details -->
                    <div class="pt-24 flex flex-col md:flex-row md:items-end md:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                <?= htmlspecialchars($userData['full_name'] ?? $userData['username'] ?? 'User') ?>
                            </h1>
                            <p class="text-lg text-gray-600 mt-1">@<?= htmlspecialchars($userData['username'] ?? 'username') ?></p>
                            
                            <div class="flex flex-wrap gap-4 mt-4 text-gray-600">
                                <span><i class="fas fa-envelope mr-2"></i><?= htmlspecialchars($userData['email'] ?? 'email@example.com') ?></span>
                                <span><i class="fas fa-calendar mr-2"></i>Joined <?= isset($userData['created_at']) ? date('F Y', strtotime($userData['created_at'])) : 'Recently' ?></span>
                                <?php if (isset($userData['is_verified']) && $userData['is_verified']): ?>
                                    <span class="text-green-600"><i class="fas fa-check-circle mr-2"></i>Verified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="flex gap-6 mt-6 md:mt-0">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900"><?= count($children) ?></div>
                                <div class="text-sm text-gray-600">Children</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900"><?= $totalPhotos ?></div>
                                <div class="text-sm text-gray-600">Photos</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900"><?= $totalMilestones ?></div>
                                <div class="text-sm text-gray-600">Milestones</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-0.5"></i>
                        <p class="text-red-800"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-0.5"></i>
                        <p class="text-green-800"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-md mb-8">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button onclick="switchTab('profile')" id="profile-tab" class="tab-active py-4 px-6 text-gray-700 font-medium hover:text-purple-600 transition-colors">
                            <i class="fas fa-user mr-2"></i>Profile Settings
                        </button>
                        <button onclick="switchTab('security')" id="security-tab" class="py-4 px-6 text-gray-700 font-medium hover:text-purple-600 transition-colors">
                            <i class="fas fa-lock mr-2"></i>Security
                        </button>
                        <button onclick="switchTab('children')" id="children-tab" class="py-4 px-6 text-gray-700 font-medium hover:text-purple-600 transition-colors">
                            <i class="fas fa-child mr-2"></i>Children
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-8">
                    <!-- Profile Tab -->
                    <div id="profile-content" class="tab-content">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                                    <input type="text" name="full_name" value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                           placeholder="Enter your full name">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                           placeholder="your@email.com">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Username
                                        <?php if (!$canChangeUsername): ?>
                                            <span class="text-xs text-gray-500 ml-2">
                                                (Can change in <?= $daysUntilNextChange ?> days)
                                            </span>
                                        <?php elseif ($changesRemaining > 0): ?>
                                            <span class="text-xs text-green-600 ml-2">
                                                (<?= $changesRemaining ?> changes remaining)
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" name="username" value="<?= htmlspecialchars($userData['username'] ?? '') ?>" 
                                           class="w-full px-4 py-3 border <?= $canChangeUsername ? 'border-gray-300' : 'border-gray-200 bg-gray-50' ?> rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                           <?= !$canChangeUsername ? 'readonly' : '' ?>
                                           placeholder="username">
                                    <?php if (isset($lastUsernameChange) && $lastUsernameChange): ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Last changed: <?= date('M d, Y', strtotime($lastUsernameChange)) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Profile Picture</label>
                                    <div class="flex items-center space-x-4">
                                        <?php if ($profilePic): ?>
                                            <img src="<?= htmlspecialchars($profilePic) ?>" alt="Current profile" class="w-16 h-16 rounded-full object-cover">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <button type="submit" name="remove_picture" class="text-red-600 hover:text-red-700 text-sm">
                                                    <i class="fas fa-trash mr-1"></i>Remove
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-400 text-2xl"></i>
                                            </div>
                                            <span class="text-sm text-gray-500">No picture uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" name="update_profile" 
                                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all">
                                    <i class="fas fa-save mr-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div id="security-content" class="tab-content hidden">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                <div class="flex">
                                    <i class="fas fa-info-circle text-yellow-400 mr-3"></i>
                                    <p class="text-sm text-yellow-800">
                                        For your security, you'll need to enter your current password to make changes.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Current Password</label>
                                    <input type="password" name="current_password" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                           placeholder="Enter your current password">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                                    <input type="password" name="new_password" id="new_password"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                           placeholder="Enter new password">
                                    <div class="mt-2">
                                        <div class="flex items-center text-xs">
                                            <i class="fas fa-check-circle text-gray-300 mr-2" id="length-check"></i>
                                            <span class="text-gray-600">At least 8 characters</span>
                                        </div>
                                        <div class="flex items-center text-xs mt-1">
                                            <i class="fas fa-check-circle text-gray-300 mr-2" id="uppercase-check"></i>
                                            <span class="text-gray-600">Contains uppercase letter</span>
                                        </div>
                                        <div class="flex items-center text-xs mt-1">
                                            <i class="fas fa-check-circle text-gray-300 mr-2" id="number-check"></i>
                                            <span class="text-gray-600">Contains number</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                           placeholder="Confirm new password">
                                    <div class="mt-2">
                                        <div class="flex items-center text-xs">
                                            <i class="fas fa-check-circle text-gray-300 mr-2" id="match-check"></i>
                                            <span class="text-gray-600">Passwords match</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" name="change_password"
                                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all">
                                    <i class="fas fa-key mr-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                        
                        <!-- Two-Factor Authentication Section -->
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Two-Factor Authentication</h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-700 font-medium">Enhance your account security</p>
                                        <p class="text-sm text-gray-500 mt-1">Add an extra layer of protection to your account</p>
                                    </div>
                                    <button class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-mobile-alt mr-2"></i>Setup 2FA
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Children Tab -->
                    <div id="children-content" class="tab-content hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-semibold text-gray-900">Manage Children</h3>
                            <a href="<?= SITE_URL ?>/pages/add-child.php" 
                               class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all">
                                <i class="fas fa-plus mr-2"></i>Add Child
                            </a>
                        </div>
                        
                        <?php if (empty($children)): ?>
                            <div class="text-center py-16 bg-gray-50 rounded-lg">
                                <i class="fas fa-baby text-6xl text-gray-300 mb-4"></i>
                                <h4 class="text-lg font-medium text-gray-700 mb-2">No children added yet</h4>
                                <p class="text-gray-500 mb-6">Start creating beautiful memories for your little ones</p>
                                <a href="<?= SITE_URL ?>/pages/add-child.php" 
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all">
                                    <i class="fas fa-plus mr-2"></i>Add Your First Child
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($children as $child): 
                                    $childPic = !empty($child['profile_picture']) 
                                        ? SITE_URL . '/uploads/children/' . $child['profile_picture']
                                        : null;
                                    $age = calculateAge($child['birth_date']);
                                    $genderIcon = $child['gender'] === 'boy' ? 'mars' : 'venus';
                                    $genderColor = $child['gender'] === 'boy' ? 'blue' : 'pink';
                                ?>
                                    <div class="child-card bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-xl">
                                        <!-- Child Cover/Profile -->
                                        <div class="relative h-48 bg-gradient-to-br from-purple-400 to-pink-400">
                                            <?php if ($childPic): ?>
                                                <img src="<?= htmlspecialchars($childPic) ?>" 
                                                     alt="<?= htmlspecialchars($child['name']) ?>" 
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fas fa-child text-white text-6xl opacity-50"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Status Badge -->
                                            <div class="absolute top-4 right-4">
                                                <?php if (isset($child['is_public']) && $child['is_public']): ?>
                                                    <span class="px-2 py-1 bg-green-500 text-white text-xs rounded-full">
                                                        <i class="fas fa-globe mr-1"></i>Public
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-gray-700 text-white text-xs rounded-full">
                                                        <i class="fas fa-lock mr-1"></i>Private
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Child Info Overlay -->
                                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                                                <h4 class="text-white font-bold text-lg flex items-center">
                                                    <?= htmlspecialchars($child['name']) ?>
                                                    <?php if (!empty($child['nickname'])): ?>
                                                        <span class="text-sm font-normal ml-2 opacity-90">(<?= htmlspecialchars($child['nickname']) ?>)</span>
                                                    <?php endif; ?>
                                                </h4>
                                                <p class="text-white/90 text-sm flex items-center">
                                                    <i class="fas fa-<?= $genderIcon ?> mr-2"></i>
                                                    <?= ucfirst($child['gender']) ?>, <?= $age ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Child Details -->
                                        <div class="p-4">
                                            <div class="text-sm text-gray-600 space-y-2">
                                                <div class="flex items-center">
                                                    <i class="fas fa-birthday-cake mr-2 text-gray-400"></i>
                                                    <?= date('F j, Y', strtotime($child['birth_date'])) ?>
                                                </div>
                                                <?php if (!empty($child['birth_weight']) || !empty($child['birth_length'])): ?>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-weight mr-2 text-gray-400"></i>
                                                        <?php if (!empty($child['birth_weight'])): ?>
                                                            <?= $child['birth_weight'] ?> kg
                                                        <?php endif; ?>
                                                        <?php if (!empty($child['birth_weight']) && !empty($child['birth_length'])): ?>
                                                            &bull;
                                                        <?php endif; ?>
                                                        <?php if (!empty($child['birth_length'])): ?>
                                                            <?= $child['birth_length'] ?> cm
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Stats -->
                                            <div class="flex justify-around mt-4 pt-4 border-t border-gray-100">
                                                <div class="text-center">
                                                    <div class="text-lg font-semibold text-gray-700"><?= $child['photo_count'] ?? 0 ?></div>
                                                    <div class="text-xs text-gray-500">Photos</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-lg font-semibold text-gray-700"><?= $child['milestone_count'] ?? 0 ?></div>
                                                    <div class="text-xs text-gray-500">Milestones</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-lg font-semibold text-gray-700"><?= $child['wish_count'] ?? 0 ?></div>
                                                    <div class="text-xs text-gray-500">Wishes</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Actions -->
                                            <div class="flex gap-2 mt-4">
                                                <a href="<?= SITE_URL ?>/pages/view-child.php?id=<?= $child['child_id'] ?>" 
                                                   class="flex-1 text-center py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors text-sm font-medium">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </a>
                                                <a href="<?= SITE_URL ?>/pages/edit-child.php?id=<?= $child['child_id'] ?>" 
                                                   class="flex-1 text-center py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm font-medium">
                                                    <i class="fas fa-edit mr-1"></i>Edit
                                                </a>
                                                <a href="<?= SITE_URL ?>/pages/share-child.php?id=<?= $child['child_id'] ?>" 
                                                   class="flex-1 text-center py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors text-sm font-medium">
                                                    <i class="fas fa-share mr-1"></i>Share
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
<div class="bg-white rounded-xl shadow-md p-8">
    <h3 class="text-xl font-semibold text-gray-900 mb-6">Account Actions</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Export Data Button -->
      
<!-- Export Data Button -->
<form method="POST" action="<?= SITE_URL ?>/pages/export-data.php" id="exportForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <button type="button" onclick="startExport()" 
            class="w-full p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left">
            <i class="fas fa-download text-blue-600 text-xl mb-2"></i>
            <h4 class="font-medium text-gray-900">Export Data</h4>
            <p class="text-sm text-gray-500 mt-1">Download all your memories (Photos + Details)</p>
    </button>
</form>
        
        <!-- Privacy Settings Button -->
        <a href="<?= SITE_URL ?>/pages/privacy-settings.php" 
           class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left">
            <i class="fas fa-shield-alt text-green-600 text-xl mb-2"></i>
            <h4 class="font-medium text-gray-900">Privacy Settings</h4>
            <p class="text-sm text-gray-500 mt-1">Manage your privacy</p>
        </a>
        
        <!-- Delete Account Button -->
        <button onclick="confirmAccountDeletion()" 
                class="p-4 border border-red-200 rounded-lg hover:bg-red-50 transition-colors text-left">
            <i class="fas fa-trash-alt text-red-600 text-xl mb-2"></i>
            <h4 class="font-medium text-red-900">Delete Account</h4>
            <p class="text-sm text-red-500 mt-1">Permanently delete account</p>
        </button>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Account</h3>
        <p class="text-gray-600 mb-6">
            This action cannot be undone. All your data, including children profiles, photos, and memories will be permanently deleted.
        </p>
        <form method="POST" action="<?= SITE_URL ?>/pages/delete-account.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="flex gap-4">
                <button type="button" onclick="closeDeleteModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="delete_account" 
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Delete Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Tab switching
function switchTab(tabName) {
    // Hide all content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('[id$="-tab"]').forEach(tab => {
        tab.classList.remove('tab-active');
    });
    
    // Show selected content and mark tab as active
    document.getElementById(tabName + '-content').classList.remove('hidden');
    document.getElementById(tabName + '-tab').classList.add('tab-active');
}

// Password validation
document.getElementById('new_password')?.addEventListener('input', function(e) {
    const password = e.target.value;
    
    // Length check
    if (password.length >= 8) {
        document.getElementById('length-check').classList.remove('text-gray-300');
        document.getElementById('length-check').classList.add('text-green-500');
    } else {
        document.getElementById('length-check').classList.remove('text-green-500');
        document.getElementById('length-check').classList.add('text-gray-300');
    }
    
    // Uppercase check
    if (/[A-Z]/.test(password)) {
        document.getElementById('uppercase-check').classList.remove('text-gray-300');
        document.getElementById('uppercase-check').classList.add('text-green-500');
    } else {
        document.getElementById('uppercase-check').classList.remove('text-green-500');
        document.getElementById('uppercase-check').classList.add('text-gray-300');
    }
    
    // Number check
    if (/\d/.test(password)) {
        document.getElementById('number-check').classList.remove('text-gray-300');
        document.getElementById('number-check').classList.add('text-green-500');
    } else {
        document.getElementById('number-check').classList.remove('text-green-500');
        document.getElementById('number-check').classList.add('text-gray-300');
    }
    
    // Check if passwords match
    checkPasswordMatch();
});

document.getElementById('confirm_password')?.addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.getElementById('new_password')?.value || '';
    const confirmPassword = document.getElementById('confirm_password')?.value || '';
    
    if (password && confirmPassword && password === confirmPassword) {
        document.getElementById('match-check')?.classList.remove('text-gray-300');
        document.getElementById('match-check')?.classList.add('text-green-500');
    } else {
        document.getElementById('match-check')?.classList.remove('text-green-500');
        document.getElementById('match-check')?.classList.add('text-gray-300');
    }
}

// Delete account modal
function confirmAccountDeletion() {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = ''; // Restore scrolling
}


// Auto-submit profile picture form
document.getElementById('profile_pic_upload')?.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        // Preview image before upload (optional)
        const reader = new FileReader();
        reader.onload = function(e) {
            // Could add preview here
        };
        reader.readAsDataURL(this.files[0]);
    }
});

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
        closeDeleteModal();
    }
});
function startExport() {
    // Show loading indicator
    const btn = event.target;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Preparing your export...';
    btn.disabled = true;
    
    // Submit form
    document.getElementById('exportForm').submit();
    
    // Revert button after 5 seconds
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-download text-blue-600 text-xl mb-2"></i><h4 class="font-medium text-gray-900">Export Data</h4><p class="text-sm text-gray-500 mt-1">Download all your memories (Photos + Details)</p>';
        btn.disabled = false;
    }, 5000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>