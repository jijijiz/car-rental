<?php
require_once 'config.php';
require_once 'functions.php';

// 确保用户已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 检查是否有订单ID
if (!isset($_GET['order_id'])) {
    $_SESSION['error'] = "No order specified";
    header('Location: my_bookings.php');
    exit();
}

$order_id = (int)$_GET['order_id'];

// 获取订单信息
$stmt = $pdo->prepare("
    SELECT o.*, c.name as car_name, c.image_url, c.brand, c.model 
    FROM orders o 
    JOIN cars c ON o.car_id = c.id 
    WHERE o.id = ? AND o.user_id = ? AND o.status = 'completed'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.order_id = o.id)
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

// 如果找不到订单或订单已评价
if (!$order) {
    $_SESSION['error'] = "Invalid order or already reviewed";
    header('Location: my_bookings.php');
    exit();
}

// 处理评价提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF验证
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // 验证评分
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Please select a valid rating (1-5 stars)";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (order_id, user_id, car_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            if ($stmt->execute([
                $order_id,
                $_SESSION['user_id'],
                $order['car_id'],
                $rating,
                $comment
            ])) {
                $_SESSION['success'] = "Thank you for your review!";
                header('Location: my_reviews.php');
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error submitting review";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Write Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .car-image {
            height: 200px;
            object-fit: cover;
        }
        .rating-stars {
            font-size: 24px;
        }
        .rating-stars input[type="radio"] {
            display: none;
        }
        .rating-stars label {
            cursor: pointer;
            padding: 5px;
        }
        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input[type="radio"]:checked ~ label {
            color: #ffc107;
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
                                    <h4 class="mb-0">Write a Review</h4>
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

                                    <!-- Car Information -->
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <img src="<?= htmlspecialchars($order['image_url']) ?>" 
                                                 class="img-fluid rounded car-image" 
                                                 alt="<?= htmlspecialchars($order['car_name']) ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <h5><?= htmlspecialchars($order['car_name']) ?></h5>
                                            <p class="text-muted">
                                                <?= htmlspecialchars($order['brand'] . ' ' . $order['model']) ?>
                                            </p>
                                            <p>
                                                <strong>Rental Period:</strong><br>
                                                <?= date('M d, Y', strtotime($order['start_date'])) ?> - 
                                                <?= date('M d, Y', strtotime($order['end_date'])) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Review Form -->
                                    <form method="POST" class="needs-validation" novalidate>
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        
                                        <!-- Star Rating -->
                                        <div class="mb-4">
                                            <label class="form-label">Rating</label>
                                            <div class="rating-stars">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <input type="radio" name="rating" value="<?= $i ?>" 
                                                           id="star<?= $i ?>" required>
                                                    <label for="star<?= $i ?>">
                                                        <i class="fas fa-star"></i>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>

                                        <!-- Review Comment -->
                                        <div class="mb-4">
                                            <label for="comment" class="form-label">Your Review</label>
                                            <textarea class="form-control" id="comment" name="comment" 
                                                      rows="4" required
                                                      placeholder="Share your experience with this car rental..."></textarea>
                                            <div class="invalid-feedback">
                                                Please write your review.
                                            </div>
                                        </div>

                                        <!-- Submit Buttons -->
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                Submit Review
                                            </button>
                                            <a href="my_bookings.php" class="btn btn-outline-secondary">
                                                Cancel
                                            </a>
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
        // 表单验证
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