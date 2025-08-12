<?php
// Security-related functions

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

function rate_limit_request($key, $limit = 5, $timeout = 300) {
    $redis = new Redis();
    try {
        $redis->connect('127.0.0.1', 6379);
        $current = $redis->get($key);
        
        if ($current && $current >= $limit) {
            return false;
        }
        
        $redis->multi();
        $redis->incr($key);
        $redis->expire($key, $timeout);
        $redis->exec();
        
        return true;
    } catch (Exception $e) {
        // If Redis fails, we'll just continue without rate limiting
        error_log("Redis error: " . $e->getMessage());
        return true;
    }
}
?>