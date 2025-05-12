<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get user's current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "User not found";
        header('Location: profile.php');
        exit();
    }

    // Verify current password
    if (!password_verify($current_password, $user['password_hash'])) {
        $_SESSION['error'] = "Current password is incorrect";
        header('Location: change_password.php');
        exit();
    }

    // Validate new password
    if (strlen($new_password) < 8) {
        $_SESSION['error'] = "New password must be at least 8 characters long";
        header('Location: change_password.php');
        exit();
    }

    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match";
        header('Location: change_password.php');
        exit();
    }

    try {
        // Update password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        
        if ($stmt->execute([$new_password_hash, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Password updated successfully";
            header('Location: profile.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating password";
        header('Location: change_password.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .nav-link {
            color: #fff;
        }
        .nav-link:hover {
            background: #495057;
        }
        .nav-link.active {
            background: #495057;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: #6c757d;
            padding: 5px;
            z-index: 10;
        }
        .form-control {
            padding-right: 40px;
        }
        input[type="password"], 
        input[type="text"] {
            height: 42px;
        }
        .form-label {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'user_sidebar.php'; ?>

            <!-- Main content area -->
            <div class="col-md-10 p-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card shadow">
                                <div class="card-header bg-dark text-white">
                                    <h4 class="mb-0">Change Password</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_SESSION['error'])): ?>
                                        <div class="alert alert-danger">
                                            <?php 
                                                echo $_SESSION['error'];
                                                unset($_SESSION['error']);
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" class="needs-validation" novalidate>
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        
                                        <div class="mb-3 password-container">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                            <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                                        </div>

                                        <div class="mb-3 password-container">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" 
                                                   required minlength="8"
                                                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$"
                                                   title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, and one number">
                                            <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                                            <div class="form-text">
                                                Password must be at least 8 characters long and include uppercase, lowercase, and numbers
                                            </div>
                                        </div>

                                        <div class="mb-3 password-container">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                            <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-dark">Update Password</button>
                                            <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = document.querySelector(`input[name="${this.dataset.target}"]`);
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                }
            });
        });

        // 初始化所有密码输入框的图标
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        });

        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 