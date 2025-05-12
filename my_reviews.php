<?php
require 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get all user reviews
$stmt = $pdo->prepare("
    SELECT r.*, c.name as car_name, c.image_url, c.brand, c.model,
           o.start_date, o.end_date 
    FROM reviews r
    JOIN orders o ON r.order_id = o.id
    JOIN cars c ON r.car_id = c.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$reviews = $stmt->fetchAll();

// If adding a new review
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    
    // Get order information
    $stmt = $pdo->prepare("
        SELECT o.*, c.name as car_name, c.image_url, c.brand, c.model 
        FROM orders o 
        JOIN cars c ON o.car_id = c.id 
        WHERE o.id = ? AND o.user_id = ? AND o.status = 'completed'
        AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.order_id = o.id)
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    // Handle review submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Add CSRF validation
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if ($rating < 1 || $rating > 5) {
            $_SESSION['error_message'] = "Please select a valid rating (1-5 stars).";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (order_id, user_id, car_id, rating, comment)
                VALUES (?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$order_id, $_SESSION['user_id'], $order['car_id'], $rating, $comment])) {
                $_SESSION['success_message'] = "Thank you for your review!";
                header('Location: my_reviews.php');
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background: #495057; }
        .car-image { height: 150px; object-fit: cover; }
        .star-rating .fas { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'user_sidebar.php'; ?>

            <!-- Main content area -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">My Reviews</h2>

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

                <?php if (isset($order)): ?>
                    <!-- 新评价表单 -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Write a Review for <?php echo htmlspecialchars($order['car_name']); ?></h5>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="star-rating">
                                        <div class="btn-group" role="group">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <input type="radio" class="btn-check" name="rating" 
                                                       id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                                <label class="btn btn-outline-warning" for="star<?php echo $i; ?>">
                                                    <i class="fas fa-star"></i> <?php echo $i; ?>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="comment" class="form-label">Your Review</label>
                                    <textarea class="form-control" id="comment" name="comment" 
                                              rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                                <a href="my_reviews.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 评价列表 -->
                <div class="row">
                    <?php foreach ($reviews as $review): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="<?php echo htmlspecialchars($review['image_url']); ?>" 
                                             class="img-fluid rounded-start car-image" 
                                             alt="<?php echo htmlspecialchars($review['car_name']); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($review['car_name']); ?></h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?>
                                                </small>
                                            </p>
                                            <div class="mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="card-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Reviewed on <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($reviews) && !isset($order)): ?>
                        <div class="alert alert-info">
                            You haven't written any reviews yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 