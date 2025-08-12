<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate reset token (valid for 1 hour)
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
                $stmt->execute([$token, $expires, $user['user_id']]);
                
                // Send reset email using PHPMailer
                $reset_link = SITE_URL . "/reset_password.php?token=$token";
                
                $mail = new PHPMailer(true);
                try {
                    // Server settings for Gmail
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'your.email@gmail.com'; // Your Gmail address
                    $mail->Password   = 'your-app-password';    // Gmail app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->SMTPDebug  = 0; // Set to 2 for debugging

                    // Recipients
                    $mail->setFrom('your.email@gmail.com', 'Children Album');
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body    = "
                        <h2>Password Reset Request</h2>
                        <p>Hello,</p>
                        <p>You requested a password reset. Click the button below to reset your password:</p>
                        <p><a href='$reset_link' style='background: #E91E63; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
                        <p>This link expires in 1 hour.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                    ";
                    $mail->AltBody = "To reset your password, visit this link: $reset_link";

                    $mail->send();
                    $success = "Password reset link sent to your email!";
                } catch (Exception $e) {
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                    $error = "Failed to send reset email. Please try again later.";
                }
            } else {
                // Show generic message for security
                $success = "If an account exists with this email, a reset link has been sent.";
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $error = "A system error occurred. Please try again later.";
        }
    }
}

$page_title = "Forgot Password";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Rest of your HTML remains the same -->

<section class="py-20 bg-gray-50 min-h-screen flex items-center">
    <div class="container max-w-md mx-auto">
        <div class="bg-white rounded-xl shadow-md p-8">
            <h1 class="text-3xl font-bold text-center text-pink-600 mb-6">Forgot Password</h1>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                </div>
                
                <button type="submit" 
                        class="w-full px-6 py-3 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition-all">
                    Send Reset Link
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="<?php echo SITE_URL; ?>/login.php" class="text-pink-600 hover:text-pink-700 font-medium">
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>