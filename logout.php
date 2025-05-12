<?php

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy session
session_destroy();

// Define base URL
define('BASE_URL', '');

// Redirect to login page
header('Location: ' . BASE_URL . '/login.php');
exit();
?> 