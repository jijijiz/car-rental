<?php

require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

// If user is already logged in, redirect to appropriate page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

// Handle Google login
if (isset($_GET['code'])) {
    try {
        $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (!isset($token['error'])) {
            $googleClient->setAccessToken($token['access_token']);
            
            // Get user information
            $google_oauth = new Google_Service_Oauth2($googleClient);
            $google_account_info = $google_oauth->userinfo->get();
            
            $email = $google_account_info->email;
            $name = $google_account_info->name;
            
            // Check if user exists, create new user if not
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Create new user
                $stmt = $pdo->prepare("INSERT INTO users (email, name, oauth_provider) VALUES (?, ?, 'google')");
                $stmt->execute([$email, $name]);
                $user_id = $pdo->lastInsertId();
                
                // Get the new user's data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $user_id = $user['id'];
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Add activity log
            log_activity($user_id, 'Google Login', sprintf(
                'User Details - Name: %s | Email: %s | Role: %s | Login Time: %s | IP: %s',
                $name,
                $email,
                $user['role'],
                date('Y-m-d H:i:s'),
                $_SERVER['REMOTE_ADDR']
            ));

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle regular login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token";
        header('Location: login.php');
        exit();
    }

    $recaptcha_secret = "6LfD9GQqAAAAAIzrDTu8FseYSKl9lp9hKwix-frT";
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$recaptcha_secret.'&response='.$recaptcha_response);
    $response_data = json_decode($verify_response);
    
    if (!$response_data->success) {
        $_SESSION['error_message'] = "Please complete the reCAPTCHA verification.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (isAccountLocked($email)) {
        global $lock_time_remaining;
        $_SESSION['error_message'] = "Account is locked. Please try again in " . ceil($lock_time_remaining/60) . " minute(s).";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        resetLoginAttempts($email);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        // Add activity log here
        log_activity($user['id'], 'User Login', sprintf(
            'User Details - Name: %s | Email: %s | Role: %s | Login Time: %s | IP: %s',
            $user['name'],
            $user['email'],
            $user['role'],
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR']
        ));
        
        // Debug information
        echo "Login successful!<br>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "Role: " . $_SESSION['role'] . "<br>";
        echo "Name: " . $_SESSION['name'] . "<br>";
        
        // Redirect based on role
        if ($user['role'] == 'admin') {
            if (file_exists('admin_dashboard.php')) {
                echo "admin_dashboard.php exists!<br>";
                header('Location: admin_dashboard.php');
            } else {
                die('Error: admin_dashboard.php not found in ' . __DIR__);
            }
        } else {
            header('Location: dashboard.php');
        }
        exit();
    } else {
        handleFailedLogin($email);
        $attempts_left = MAX_LOGIN_ATTEMPTS - getAttempts($email);
        
        if ($attempts_left <= 0) {
            $_SESSION['error_message'] = "Too many failed attempts. Your account has been locked for 1 minute.";
        } else {
            $_SESSION['error_message'] = "Invalid credentials! {$attempts_left} attempts remaining.";
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .video-background {
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
        
        .login-container {
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
            .video-background {
                object-position: center;
            }
            
            .login-container {
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
    <!-- Video Background -->
    <video 
        autoplay 
        muted 
        loop 
        playsinline
        webkit-playsinline
        x-webkit-airplay="deny"
        preload="metadata"
        class="video-background"
        style="pointer-events: none;"
    >
        <source src="assets/videos/car-background-test.mp4" type="video/mp4" />
        Your browser does not support the video tag.
    </video>

    <?php if (isset($_SESSION['register_success'])): ?>
        <div class="alert alert-success" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1000;">
            Registration successful! Please login with your credentials.
        </div>
        <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
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

    <div class="login-container">
        <h2 class="text-center mb-4">Car Rental System</h2>
        
        <!-- Google Login button -->
        <?php
        $googleClient->setPrompt('select_account consent');
        $googleClient->setAccessType('offline');
        ?>
        <a href="<?= $googleClient->createAuthUrl() ?>" class="btn google-btn">
            <img src="https://developers.google.com/identity/images/g-logo.png" 
                 alt="Google" style="width: 20px; margin-right: 10px;">
            Sign in with Google
        </a>
        
        <div class="divider">
            <span class="px-2 bg-white">or</span>
        </div>
        
        <!-- Login form -->
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="6LfD9GQqAAAAACwaX0AAZpGO38hvVeFUJA4PDtws"></div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
        </form>
        
        <div class="text-center mt-3">
            <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
        </div>
        
        <div class="text-center mt-2">
            Don't have an account? <a href="register.php" class="text-decoration-none">Sign Up</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <!-- Video Background Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.querySelector('.video-background');
            
            // 基本设置
            video.loop = true;
            video.muted = true;
            video.playsInline = true;
            
            // 强制循环播放
            function ensurePlayback() {
                const playPromise = video.play();
                
                if (playPromise !== undefined) {
                    playPromise.then(_ => {
                        // 自动循环
                        video.addEventListener('timeupdate', function() {
                            if (video.currentTime >= video.duration - 0.5) {
                                video.currentTime = 0;
                            }
                        });
                    })
                    .catch(error => {
                        console.log("Playback failed:", error);
                        // 1秒后重试
                        setTimeout(ensurePlayback, 1000);
                    });
                }
            }

            // 初始播放
            ensurePlayback();

            // 处理页面可见性变化
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    ensurePlayback();
                }
            });

            // 处理视频结束
            video.addEventListener('ended', function() {
                video.currentTime = 0;
                ensurePlayback();
            });

            // 处理移动设备
            if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
                video.setAttribute('playsinline', '');
                video.setAttribute('webkit-playsinline', '');
            }

            // 定期检查播放状态
            setInterval(() => {
                if (video.paused) {
                    ensurePlayback();
                }
            }, 1000);
        });
    </script>
</body>
</html>