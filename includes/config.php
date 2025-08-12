<?php
session_start();

// Site configuration
define('SITE_NAME', 'Children Album');
define('SITE_URL', 'http://localhost/children-album');
define('SITE_EMAIL', 'noreply@children-album.com');  // Replace with your domain
define('ADMIN_EMAIL', 'admin@children-album.com');   // Replace with your admin email
define('EMAIL_ENABLED', true); // Set to false to disable emails during testing

// File upload configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Security configuration
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('VERIFICATION_TOKEN_EXPIRY', 86400); // 24 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'children_album');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');



// Set default timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize PDO database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Include other required files
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__.'/../vendor/autoload.php';

?>