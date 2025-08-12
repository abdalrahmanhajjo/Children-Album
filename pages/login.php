<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = isset($_POST['login']) ? sanitize_input($_POST['login']) : '';
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($login)) {
        $error = "Username or email is required";
    } elseif (empty($password)) {
        $error = "Password is required";
    } else {
        // Check if input is email
        $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);
        
        // Prepare SQL query based on input type
        if ($is_email) {
            $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM users WHERE email = ?");
        } else {
            $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
        }
        
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            login_user($user['user_id'], $user['username']);
            
            // Check if there's a redirect URL in session
            if (isset($_SESSION['redirect_url'])) {
                $redirect_url = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);
                header('Location: ' . $redirect_url);
            } else {
                header('Location: ' . SITE_URL . '/pages/dashboard.php');
            }
            exit;
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    }
}

$page_title = 'Login';
require_once '../includes/header.php';
?>

<section class="py-20 bg-gray-50 min-h-screen flex items-center">
    <div class="container">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-pink-600 heading-font mb-2">Login</h2>
                <p class="text-gray-600">Welcome back! Please login to your account.</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="login" class="block text-gray-700 font-medium mb-2">Username or Email</label>
                    <input type="text" id="login" name="login" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                    <div class="mt-2 text-right">
                        <a href="../pages/forgot_password.php" class="text-sm text-pink-600 hover:text-pink-700">Forgot password?</a>
                    </div>
                </div>
                <button type="submit" class="w-full px-6 py-3 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition-all">
                    Login <i class="fas fa-sign-in-alt ml-2"></i>
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">Don't have an account? <a href="../pages/register.php" class="text-pink-600 hover:text-pink-700 font-medium">Register here</a></p>
            </div>
        </div>
    </div>
</section>

<?php
require_once '../includes/footer.php';
?>