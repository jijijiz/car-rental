<?php
// Maximum login attempts
define('MAX_LOGIN_ATTEMPTS', 3);
// Lock time (seconds)
define('LOCK_TIME', 60);  // 60 seconds = 1 minute

// Record failed login attempt
function handleFailedLogin($email) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE email = ?");
    $stmt->execute([$email]);
    $record = $stmt->fetch();
    
    if ($record) {
        $stmt = $pdo->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE email = ?");
        $stmt->execute([$email]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, attempts, last_attempt) VALUES (?, 1, CURRENT_TIMESTAMP)");
        $stmt->execute([$email]);
    }
}

// Check if account is locked
function isAccountLocked($email) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE email = ? AND attempts >= ?");
    $stmt->execute([$email, MAX_LOGIN_ATTEMPTS]);
    $record = $stmt->fetch();
    
    if ($record) {
        // 直接使用 MySQL 的时间比较
        $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, last_attempt, NOW()) as time_diff FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        $time_diff = $result['time_diff'];
        
        if ($time_diff < LOCK_TIME) {
            global $lock_time_remaining;
            $lock_time_remaining = LOCK_TIME - $time_diff;
            return true;
        } else {
            resetLoginAttempts($email);
            return false;
        }
    }
    return false;
}

// Reset login attempts (called after successful login)
function resetLoginAttempts($email) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
    $stmt->execute([$email]);
}

function getAttempts($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT attempts FROM login_attempts WHERE email = ?");
    $stmt->execute([$email]);
    $result = $stmt->fetch();
    return $result ? $result['attempts'] : 0;
}
?>