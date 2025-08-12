<?php
function send_verification_email($to_email, $to_name, $verification_token) {
    if (!EMAIL_ENABLED) {
        error_log("Email sending is disabled. Verification token for $to_email: $verification_token");
        return true; // Pretend it worked for testing
    }

    $verification_link = SITE_URL . "/verify.php?token=" . urlencode($verification_token);
    
    $subject = "Verify Your Email Address for " . SITE_NAME;
    $message = "
        <html>
        <head>
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { 
                    display: inline-block; 
                    padding: 10px 20px; 
                    background-color: #EC4899; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 15px 0;
                }
                .footer { 
                    margin-top: 20px; 
                    padding-top: 20px; 
                    border-top: 1px solid #eee; 
                    font-size: 0.9em; 
                    color: #777;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Hello $to_name,</h2>
                <p>Thank you for registering with " . SITE_NAME . "!</p>
                <p>Please click the button below to verify your email address:</p>
                <p><a href='$verification_link' class='button'>Verify Email</a></p>
                <p>Or copy and paste this link into your browser:<br>
                <code>$verification_link</code></p>
                <p>This link will expire in 24 hours.</p>
                <div class='footer'>
                    <p>If you didn't request this, please ignore this email.</p>
                    <p>© " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    $headers = "From: " . SITE_NAME . " <" . SITE_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    try {
        $sent = mail($to_email, $subject, $message, $headers);
        if (!$sent) {
            throw new Exception('PHP mail() function failed');
        }
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed for $to_email: " . $e->getMessage());
        return false;
    }
}