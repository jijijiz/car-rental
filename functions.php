<?php
// Get order status color
function get_status_color($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'info';
        case 'active': return 'primary';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

// Log activity
function log_activity($user_id, $action, $description = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

// Format datetime
function format_datetime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

// Format money amount
function format_money($amount) {
    return 'RM' . number_format($amount, 2);
}

// Get user role label color
function get_role_color($role) {
    switch($role) {
        case 'admin': return 'danger';
        case 'user': return 'success';
        default: return 'secondary';
    }
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user name
function get_current_user_name() {
    return $_SESSION['name'] ?? 'Guest';
}

// Set flash message
function set_flash_message($type, $message) {
    $_SESSION[$type] = $message;
}

// Show flash message
function show_flash_message($type) {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    return null;
}

// Generate CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
?> 