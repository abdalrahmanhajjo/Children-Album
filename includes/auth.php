<?php
// C:\xampp\htdocs\children-album\includes\auth.php

require_once 'config.php';



/**
 * Login user and set session variables
 */
function login_user($user_id, $username) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['logged_in'] = true;
}
/*
 * Check if user is logged in
 */



/**
 * Get current user ID
 */


/**
 * Verify password against hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Hash password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Authenticate user and start session
 */
function authenticate_user($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Attempt to log in user
 */
function attempt_login($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verify_password($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}
/**
 * Logout user and clear session
 */
function logout_user() {
    // Unset all session variables
    $_SESSION = array();

    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();
}
