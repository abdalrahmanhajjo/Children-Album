<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isset($_SESSION['registration_success'])) {
    header('Location: register.php');
    exit;
}

unset($_SESSION['registration_success']);
?>

<section class="py-20 bg-gray-50 min-h-screen flex items-center">
    <div class="container">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8 text-center">
            <div class="text-green-500 text-5xl mb-4">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Registration Successful!</h2>
            <a href="login.php" class="px-6 py-3 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition-all inline-block">
                Go to Login
            </a>
        </div>
    </div>
</section>

<?php
require_once '../includes/footer.php';
?>