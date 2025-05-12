<?php
require 'config.php';
require 'mail_config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate verification code
        $verification_code = sprintf("%06d", mt_rand(0, 999999));
        
        // Save verification code to database
        $stmt = $pdo->prepare("UPDATE users SET reset_code = ?, reset_code_expiry = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email = ?");
        $stmt->execute([$verification_code, $email]);
        
        // Use new mail sending function
        $subject = "Password Reset Verification Code";
        $message = "
        <html>
        <body>
            <h2>Password Reset</h2>
            <p>Your verification code is: <strong>{$verification_code}</strong></p>
            <p>This code will expire in 15 minutes.</p>
        </body>
        </html>
        ";
        
        if (sendMail($email, $subject, $message)) {
            $_SESSION['reset_email'] = $email;
            header('Location: reset_password.php');
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to send verification code. Please try again.";
        }
    } else {
        $_SESSION['error_message'] = "Email not found in our records.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
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
                
                <div class="card">
                    <div class="card-header">
                        <h3>Forgot Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Reset Code</button>
                            <a href="login.php" class="btn btn-link">Back to Login</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 