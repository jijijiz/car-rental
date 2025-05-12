<?php
require 'config.php';
require_once 'functions.php';

// Verify admin identity
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    if (isset($_POST['action'])) {
        $user_id = $_POST['user_id'];
        
        switch ($_POST['action']) {
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "User deleted successfully";
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE users SET status = NOT status WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "User status updated";
                break;
                
            case 'reset_password':
                if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];
            
                    // Ensure passwords match
                    if ($new_password !== $confirm_password) {
                        $_SESSION['error'] = "Passwords do not match.";
                        header('Location: admin_users.php');
                        exit();
                    }
            
                    // Ensure password length is secure
                    if (strlen($new_password) < 6) {
                        $_SESSION['error'] = "Password must be at least 6 characters.";
                        header('Location: admin_users.php');
                        exit();
                    }
            
                    // Encrypt new password
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
                    // Update user password
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$password_hash, $user_id]);
            
                    $_SESSION['success'] = "Password updated successfully.";
                } else {
                    $_SESSION['error'] = "Please enter and confirm the new password.";
                }
            
                header('Location: admin_users.php');
                exit();
                           
        }
        
        header('Location: admin_users.php');
        exit();
    }
}

// Get user list
$stmt = $pdo->prepare("
    SELECT id, name, email, role, 
           COALESCE(created_at, CURRENT_TIMESTAMP) as created_at,
           COALESCE(status, 1) as status,
           (SELECT COUNT(*) FROM orders WHERE user_id = users.id) as order_count
    FROM users 
    ORDER BY created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background: #495057; }
        .nav-link.active { background: #495057; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'admin_sidebar.php'; ?>

            <!-- Main Content Area -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>User Management</h2>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Orders</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $user['order_count']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if ($user['role'] != 'admin'): ?>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <form method="POST" class="dropdown-item">
                                                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <input type="hidden" name="action" value="toggle_status">
                                                                    <button type="submit" class="btn btn-link text-decoration-none p-0">
                                                                        <i class="fas fa-toggle-on me-2"></i>
                                                                        Toggle Status
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <!-- Reset Password 按钮（触发模态框） -->
<li>
    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" 
        data-userid="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['name']); ?>">
        <i class="fas fa-key me-2"></i> Reset Password
    </button>
</li>

                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" class="dropdown-item" 
                                                                      onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <button type="submit" class="btn btn-link text-decoration-none p-0 text-danger">
                                                                        <i class="fas fa-trash me-2"></i>
                                                                        Delete User
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 模态框 -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password for <span id="modal-username"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="resetPasswordForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="user_id" id="modal-user-id">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var resetModal = document.getElementById('resetPasswordModal');
        
        resetModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; 
            var userId = button.getAttribute('data-userid');
            var username = button.getAttribute('data-username');

            // 填充模态框中的数据
            document.getElementById('modal-user-id').value = userId;
            document.getElementById('modal-username').innerText = username;
        });

        // 表单验证（确保密码匹配）
        document.getElementById('resetPasswordForm').addEventListener('submit', function (event) {
            var newPassword = document.getElementById('new_password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert("Passwords do not match!");
                event.preventDefault();
            }
        });
    });
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 