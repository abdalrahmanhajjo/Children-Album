<?php
require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';



// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitize_input($_POST['full_name']);
        
        // Validate inputs
        $validation_errors = [];
        
        if (empty($username)) {
            $validation_errors[] = "Username is required";
        } elseif (strlen($username) > 50) {
            $validation_errors[] = "Username must be less than 50 characters";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $validation_errors[] = "Username can only contain letters, numbers and underscores";
        }
        
        if (empty($email)) {
            $validation_errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Invalid email format";
        } elseif (strlen($email) > 100) {
            $validation_errors[] = "Email must be less than 100 characters";
        }
        
        if (empty($password)) {
            $validation_errors[] = "Password is required";
        } elseif (strlen($password) < 8) {
            $validation_errors[] = "Password must be at least 8 characters";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            $validation_errors[] = "Password must contain at least one uppercase letter, one number and one special character";
        } elseif ($password !== $confirm_password) {
            $validation_errors[] = "Passwords do not match";
        }
        
        if (strlen($full_name) > 100) {
            $validation_errors[] = "Full name must be less than 100 characters";
        }
        
        if (empty($validation_errors)) {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $validation_errors[] = "Username or email already exists";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Insert new user with is_verified=1 (no email verification)
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, is_verified) VALUES (?, ?, ?, ?, 1)");
                if ($stmt->execute([$username, $email, $password_hash, $full_name])) {
                    $_SESSION['registration_success'] = true;
                    header('Location: registration_success.php');
                    exit;
                } else {
                    $validation_errors[] = "Registration failed. Please try again.";
                }
            }
        }
        
        if (!empty($validation_errors)) {
            $error = implode("<br>", $validation_errors);
        }
    }
}

$page_title = 'Register';
require_once '../includes/header.php';
?>

<section class="py-20 bg-gray-50 min-h-screen flex items-center">
    <div class="container">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-pink-600 heading-font mb-2">Create Account</h2>
                <p class="text-gray-600">Join us to create beautiful albums for your children.</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                    <input type="text" id="username" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required maxlength="50">
                    <p class="text-xs text-gray-500 mt-1">Letters, numbers and underscores only</p>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required maxlength="100">
                </div>
                
                <div class="mb-4">
                    <label for="full_name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" maxlength="100">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters with at least one uppercase, one number and one special character</p>
                </div>
                
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                </div>
                
                <button type="submit" class="w-full px-6 py-3 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition-all">
                    Register <i class="fas fa-user-plus ml-2"></i>
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">Already have an account? <a href="https://children-album.great-site.net/children-album/pages/login.php" class="text-pink-600 hover:text-pink-700 font-medium">Login here</a></p>
            </div>
        </div>
    </div>
</section>

<?php
require_once '../includes/footer.php';
?>
