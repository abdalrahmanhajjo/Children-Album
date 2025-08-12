<?php
require_once __DIR__ . '/../includes/config.php';

// Clear any remaining session data
session_unset();
session_destroy();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-8 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <i class="fas fa-check text-green-600"></i>
            </div>
            <h2 class="mt-3 text-lg font-medium text-gray-900">Account Deleted</h2>
            <p class="mt-2 text-sm text-gray-500">
                Your account and all associated data have been permanently removed.
            </p>
            <div class="mt-6">
                <a href="<?= SITE_URL ?>/index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    Return to Homepage
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>