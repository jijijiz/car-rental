<?php
// Security Settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600); // 1 hour

session_start();
require_once 'vendor/autoload.php';
require 'db.php';

// Google Configuration
$googleClient = new Google_Client();
$googleClient->setClientId('734480979302-bbmnki6r3unjcvndlvu4q6h7ead81g5p.apps.googleusercontent.com');
$googleClient->setClientSecret('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');
$googleClient->setRedirectUri('https://www.car-rental.com/login.php');
$googleClient->addScope('email');
$googleClient->addScope('profile');
?>