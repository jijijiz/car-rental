<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
try {
    $stmt = $pdo->prepare("
        SELECT id, email, name, phone, created_at, role 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "User not found";
        header('Location: dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching user data";
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, phone = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([
            $_POST['name'],
            $_POST['phone'],
            $_SESSION['user_id']
        ])) {
            $_SESSION['success'] = "Profile updated successfully";
            header('Location: profile.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating profile";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
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
                        <div class="col-md-8">
                            <div class="card shadow">
                                <div class="card-header bg-dark text-white">
                                    <h4 class="mb-0">My Profile</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_SESSION['success'])): ?>
                                        <div class="alert alert-success">
                                            <?php 
                                                echo $_SESSION['success'];
                                                unset($_SESSION['success']);
                                            ?>
                                        </div>
                                    <?php endif; ?>

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
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="<?= ucfirst(htmlspecialchars($user['role'])) ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Member Since</label>
                                            <input type="text" class="form-control" value="<?= date('F j, Y', strtotime($user['created_at'])) ?>" disabled>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-dark">Update Profile</button>
                                            <a href="change_password.php" class="btn btn-outline-dark">Change Password</a>
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
</body>
</html> 