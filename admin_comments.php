<?php
require 'config.php';
require_once 'functions.php';

// Verify admin identity
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle adding comments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    $order_id = $_POST['order_id'];
    $comment = trim($_POST['comment']);
    
    if (!empty($comment)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO admin_comments (order_id, admin_id, comment)
                VALUES (?, ?, ?)
            ");
            if ($stmt->execute([$order_id, $_SESSION['user_id'], $comment])) {
                $_SESSION['success'] = "Comment added successfully";
            } else {
                $_SESSION['error'] = "Error adding comment";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Comment cannot be empty";
    }
    
    header('Location: admin_comments.php?order_id=' . $order_id);
    exit();
}

// Get statistics
$stats = [];

// Total comments count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_comments");
$stmt->execute();
$stats['total_comments'] = $stmt->fetchColumn();

// Today's comments count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_comments WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$stats['today_comments'] = $stmt->fetchColumn();

// Get order list
$where_clause = "";
$params = [];

if (isset($_GET['order_id'])) {
    $where_clause = "WHERE o.id = ?";
    $params[] = $_GET['order_id'];
}

$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email,
           c.name as car_name, c.brand, c.model
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    $where_clause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Comments Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background: #495057; }
        .nav-link.active { background: #495057; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .comment-box {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'admin_sidebar.php'; ?>

            <!-- Main Content Area -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Comments Management</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Comments</h5>
                                <p class="card-text display-6"><?php echo $stats['total_comments']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Today's Comments</h5>
                                <p class="card-text display-6"><?php echo $stats['today_comments']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order and Comment List -->
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">
                        No orders found.
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    Order #<?= $order['id'] ?> - 
                                    <?= htmlspecialchars($order['car_name']) ?>
                                </h5>
                                <span class="badge bg-<?= get_status_color($order['status']) ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p><strong>Customer:</strong> <?= htmlspecialchars($order['user_name']) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($order['user_email']) ?></p>
                                        <p><strong>Car:</strong> <?= htmlspecialchars($order['brand'] . ' ' . $order['model']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Rental Period:</strong> 
                                            <?= date('M d, Y', strtotime($order['start_date'])) ?> - 
                                            <?= date('M d, Y', strtotime($order['end_date'])) ?>
                                        </p>
                                        <p><strong>Total Price:</strong> RM<?= number_format($order['total_price'], 2) ?></p>
                                        <p><strong>Created At:</strong> <?= date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
                                    </div>
                                </div>

                                <!-- Existing Comments -->
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT ac.*, u.name as admin_name 
                                    FROM admin_comments ac
                                    JOIN users u ON ac.admin_id = u.id
                                    WHERE ac.order_id = ?
                                    ORDER BY ac.created_at DESC
                                ");
                                $stmt->execute([$order['id']]);
                                $comments = $stmt->fetchAll();
                                ?>

                                <div class="mb-4">
                                    <h6 class="mb-3">Comments History:</h6>
                                    <?php if (empty($comments)): ?>
                                        <p class="text-muted">No comments yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($comments as $comment): ?>
                                            <div class="comment-box">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <strong>
                                                        <i class="fas fa-user-shield me-2"></i>
                                                        <?= htmlspecialchars($comment['admin_name']) ?>
                                                    </strong>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= date('M d, Y H:i', strtotime($comment['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <p class="mb-0"><?= htmlspecialchars($comment['comment']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Add Comment Form -->
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Add New Comment</label>
                                        <textarea class="form-control" name="comment" rows="3" 
                                                  placeholder="Enter your comment here..." required></textarea>
                                    </div>
                                    <button type="submit" name="add_comment" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add Comment
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 