<?php
require_once 'config.php';
require_once 'functions.php';

// 确保用户已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 获取管理员对该用户的所有评论
$stmt = $pdo->prepare("
    SELECT ac.*, u.name as admin_name, o.id as order_id,
           c.name as car_name, c.brand, c.model,
           o.start_date, o.end_date
    FROM admin_comments ac
    JOIN users u ON ac.admin_id = u.id
    LEFT JOIN orders o ON ac.order_id = o.id
    LEFT JOIN cars c ON o.car_id = c.id
    WHERE o.user_id = ?
    ORDER BY ac.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Comments</title>
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
            <?php include 'user_sidebar.php'; ?>

            <!-- Main content area -->
            <div class="col-md-10 p-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-10">
                            <div class="card shadow">
                                <div class="card-header bg-dark text-white">
                                    <h4 class="mb-0">Admin Comments</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($comments)): ?>
                                        <div class="alert alert-info">
                                            No comments from administrators yet.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($comments as $comment): ?>
                                            <div class="comment-box">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div>
                                                        <h5 class="mb-0">
                                                            <i class="fas fa-user-shield me-2"></i>
                                                            <?= htmlspecialchars($comment['admin_name']) ?>
                                                        </h5>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?= date('M d, Y H:i', strtotime($comment['created_at'])) ?>
                                                        </small>
                                                    </div>
                                                    <?php if ($comment['order_id']): ?>
                                                        <div class="text-end">
                                                            <small class="text-muted d-block">Related to Order #<?= $comment['order_id'] ?></small>
                                                            <small class="text-muted d-block">
                                                                <?= htmlspecialchars($comment['car_name']) ?> 
                                                                (<?= date('M d, Y', strtotime($comment['start_date'])) ?> - 
                                                                <?= date('M d, Y', strtotime($comment['end_date'])) ?>)
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card">
                                                    <div class="card-body">
                                                        <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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