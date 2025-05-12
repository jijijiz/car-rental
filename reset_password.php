<?php
require 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

// Handle verification code validation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_code'])) {
    $email = $_SESSION['reset_email'];
    $code = $_POST['verification_code'];
    
    // Verify code
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND reset_code = ? AND reset_code_expiry > NOW()");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['code_verified'] = true;
        $_SESSION['success_message'] = "Code verified successfully. Please set your new password.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['error_message'] = "Invalid or expired verification code.";
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['code_verified'])) {
        $_SESSION['error_message'] = "Please verify your code first.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $email = $_SESSION['reset_email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
    } else {
        // Update password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_code = NULL, reset_code_expiry = NULL WHERE email = ?");
        $stmt->execute([$password_hash, $email]);
        
        $_SESSION['success_message'] = "Password has been reset successfully!";
        unset($_SESSION['reset_email']);
        unset($_SESSION['code_verified']);
        header('Location: login.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                            echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Reset Password</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!isset($_SESSION['code_verified'])): ?>
                            <!-- Verification code form -->
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="mb-3">
                                    <label for="verification_code" class="form-label">Verification Code</label>
                                    <input type="text" class="form-control" id="verification_code" name="verification_code" required>
                                </div>
                                <button type="submit" name="verify_code" class="btn btn-primary">Verify Code</button>
                            </form>
                        <?php else: ?>
                            <!-- Reset password form -->
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 