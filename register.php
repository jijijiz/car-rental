<?php
require 'config.php';
require_once 'functions.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Invalid security token";
    } else {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $errors = [];

        // Validate input
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered";
        }

        // If no errors, create user
        if (empty($errors)) {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $password_hash]);
                
                // Show success message and redirect to login page
                $_SESSION['register_success'] = true;
                header('Location: login.php');
                exit();
            } catch (Exception $e) {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .background-image {
            position: fixed;
            right: 0;
            bottom: 0;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1000;
            filter: brightness(50%);
        }
        
        body {
            height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            position: relative;
            overflow-x: hidden;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            margin: 20px;
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .background-image {
                object-position: center;
            }
            
            .register-container {
                margin: 15px;
                padding: 1.5rem;
            }
        }
        
        .google-btn {
            background: #fff;
            border: 1px solid #ddd;
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .google-btn:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        
        .divider {
            text-align: center;
            margin: 1rem 0;
            position: relative;
        }
        
        .divider::before, .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #ddd;
        }
        
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        
        .form-control {
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            background: #fff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .alert {
            position: fixed !important;
            top: 20px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 1000 !important;
            min-width: 300px;
            text-align: center;
            padding: 15px 25px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <!-- Background Image -->
    <img src="assets/images/car-background.jpg" class="background-image" alt="Background">

    <div class="register-container">
        <h2 class="text-center mb-4">Create Account</h2>
        
        <!-- Google Sign Up button -->
        <?php
        $googleClient->setPrompt('select_account consent');
        $googleClient->setAccessType('offline');
        ?>
        <a href="<?= $googleClient->createAuthUrl() ?>" class="btn google-btn">
            <img src="https://developers.google.com/identity/images/g-logo.png" 
                 alt="Google" style="width: 20px; margin-right: 10px;">
            Sign up with Google
        </a>
        
        <div class="divider">
            <span class="px-2 bg-white">or</span>
        </div>
        
        <!-- Display errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Registration form -->
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" required 
                       value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required
                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="form-text">Must be at least 8 characters long</div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
        
        <div class="text-center mt-3">
            Already have an account? <a href="login.php" class="text-decoration-none">Sign In</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 