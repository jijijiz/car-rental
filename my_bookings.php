<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query conditions
$where_clause = "WHERE o.user_id = ?";
$params = [$_SESSION['user_id']];

if ($status_filter !== 'all') {
    $where_clause .= " AND o.status = ?";
    $params[] = $status_filter;
}

// Get user orders
$stmt = $pdo->prepare("
    SELECT o.*, c.name as car_name, c.image_url, c.brand, c.model,
           c.price_per_day, c.year, c.transmission,
           r.id as review_id, r.rating, r.comment as review_comment,
           r.created_at as review_date
    FROM orders o 
    JOIN cars c ON o.car_id = c.id 
    LEFT JOIN reviews r ON o.id = r.order_id AND r.user_id = o.user_id
    $where_clause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order status statistics
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$_SESSION['user_id']]);
$status_counts = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'count', 'status');

// Handle order cancellation
if (isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    
    // Verify order belongs to current user and can be cancelled
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE id = ? 
        AND user_id = ? 
        AND (status = 'pending' OR status = 'paid')
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if ($order) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            if ($order['status'] === 'paid' && $order['payment_method'] === 'stripe' && $order['payment_id']) {
                // Process refund through Stripe
                require 'stripe_config.php';
                $refund = \Stripe\Refund::create([
                    'payment_intent' => $order['payment_id'],
                    'reason' => 'requested_by_customer'
                ]);
                
                if ($refund->status === 'succeeded') {
                    $payment_notes = 'Cancelled and refunded via Stripe: ' . $refund->id;
                } else {
                    throw new Exception('Refund failed');
                }
            } else if ($order['status'] === 'paid' && $order['payment_method'] === 'cash') {
                $payment_notes = 'Cancelled - Cash refund pending';
            } else {
                $payment_notes = 'Cancelled by customer';
            }

            // Update order status
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled',
                    payment_notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$payment_notes, $order_id]);
            
            // Commit transaction
            $pdo->commit();
            
            if ($order['status'] === 'paid' && $order['payment_method'] === 'cash') {
                $_SESSION['success'] = "Order cancelled. Please visit our office for cash refund.";
            } else {
                $_SESSION['success'] = "Order cancelled successfully" . 
                    ($order['status'] === 'paid' ? " and refund has been processed" : "");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            error_log("Cancel order error: " . $e->getMessage());
            $_SESSION['error'] = "Error cancelling order. Please contact support.";
        }
    } else {
        $_SESSION['error'] = "Invalid order or cannot be cancelled";
    }
    
    header('Location: my_bookings.php');
    exit();
}

function get_status_badge($status) {
    $badges = [
        'pending' => 'warning',
        'paid' => 'primary',
        'active' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    return $badges[$status] ?? 'secondary';
}

function format_price($price) {
    return '$ ' . number_format($price, 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    // ... existing code ...
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .nav-link {
            color: #fff;
            padding: 0.8rem 1.5rem;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background: #495057;
            transform: translateX(5px);
        }
        .nav-link.active {
            background: #495057;
            border-left: 4px solid #007bff;
        }
        .order-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .car-img {
            height: 220px;
            object-fit: cover;
            border-radius: 10px;
            margin: 10px;
        }
        .status-count {
            font-size: 0.9rem;
        }
        .status-badge {
            padding: 0.5em 1em;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .booking-details {
            padding: 1.5rem;
        }
        .price-tag {
            font-size: 1.2rem;
            font-weight: 600;
            color: #28a745;
        }
        .booking-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .car-info {
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .action-buttons .btn {
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        .review-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .star-rating {
            color: #ffc107;
        }
        .status-filter-buttons {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .status-filter-buttons .btn {
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            border-radius: 20px;
        }
        .admin-comment {
            background: #e9ecef;
            border-left: 4px solid #007bff;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 0 8px 8px 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'user_sidebar.php'; ?>

            <!-- Main content area -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">My Bookings</h2>
                </div>

                <!-- Status filter buttons -->
                <div class="status-filter-buttons">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="my_bookings.php" class="btn btn-<?= $status_filter === 'all' ? 'dark' : 'outline-dark' ?>">
                            All Orders
                            <span class="badge bg-secondary ms-2"><?= array_sum($status_counts) ?></span>
                        </a>
                        <a href="my_bookings.php?status=pending" class="btn btn-<?= $status_filter === 'pending' ? 'warning' : 'outline-warning' ?>">
                            Pending
                            <span class="badge bg-secondary ms-2"><?= $status_counts['pending'] ?? 0 ?></span>
                        </a>
                        <a href="my_bookings.php?status=paid" class="btn btn-<?= $status_filter === 'paid' ? 'primary' : 'outline-primary' ?>">
                            Paid
                            <span class="badge bg-secondary ms-2"><?= $status_counts['paid'] ?? 0 ?></span>
                        </a>
                        <a href="my_bookings.php?status=active" class="btn btn-<?= $status_filter === 'active' ? 'info' : 'outline-info' ?>">
                            Active
                            <span class="badge bg-secondary ms-2"><?= $status_counts['active'] ?? 0 ?></span>
                        </a>
                        <a href="my_bookings.php?status=completed" class="btn btn-<?= $status_filter === 'completed' ? 'success' : 'outline-success' ?>">
                            Completed
                            <span class="badge bg-secondary ms-2"><?= $status_counts['completed'] ?? 0 ?></span>
                        </a>
                        <a href="my_bookings.php?status=cancelled" class="btn btn-<?= $status_filter === 'cancelled' ? 'danger' : 'outline-danger' ?>">
                            Cancelled
                            <span class="badge bg-secondary ms-2"><?= $status_counts['cancelled'] ?? 0 ?></span>
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

                <?php if (empty($orders)): ?>
                    <div class="alert alert-info text-center p-5">
                        <i class="fas fa-car fa-3x mb-3"></i>
                        <h4>No bookings found</h4>
                        <p class="mb-0">Ready to start your journey? Browse our collection of cars.</p>
                        <a href="cars.php" class="btn btn-primary mt-3">Browse Cars</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($orders as $order): ?>
                            <div class="col-12">
                                <div class="card order-card">
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                            <img src="<?= htmlspecialchars($order['image_url']) ?>" 
                                                 class="car-img w-100" 
                                                 alt="<?= htmlspecialchars($order['car_name']) ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="booking-details">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h4 class="card-title mb-0">
                                                        <?= htmlspecialchars($order['car_name']) ?>
                                                    </h4>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php if ($order['payment_method']): ?>
                                                            <span class="badge bg-<?= $order['payment_method'] === 'cash' ? 'success' : 'info' ?>">
                                                                <i class="<?= $order['payment_method'] === 'cash' ? 'fas fa-money-bill-wave' : 'fas fa-credit-card' ?> me-1"></i>
                                                                <?= $order['payment_method'] === 'cash' ? 'Cash Payment' : 'Card Payment' ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-<?= get_status_badge($order['status']) ?> status-badge">
                                                            <?= $order['status'] === 'pending' ? 'Pending' :
                                                               ($order['status'] === 'paid' ? 'Paid' :
                                                               ($order['status'] === 'active' ? 'Active' :
                                                               ($order['status'] === 'completed' ? 'Completed' :
                                                               ($order['status'] === 'cancelled' ? 'Cancelled' :
                                                               ucfirst($order['status']))))) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="car-info">
                                                    <i class="fas fa-car me-2"></i>
                                                    <?= htmlspecialchars($order['brand'] . ' ' . $order['model'] . ' ' . $order['year']) ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-gear me-2"></i>
                                                    <?= htmlspecialchars($order['transmission']) ?>
                                                </div>
                                                
                                                <div class="booking-date">
                                                    <i class="fas fa-calendar me-2"></i>
                                                    <?= date('M d, Y', strtotime($order['start_date'])) ?> - 
                                                    <?= date('M d, Y', strtotime($order['end_date'])) ?>
                                                </div>
                                                
                                                <div class="price-tag mb-3">
                                                    <?= format_price($order['total_price']) ?>
                                                </div>

                                                <?php if ($order['status'] === 'pending' || $order['status'] === 'paid'): ?>
                                                    <div class="d-flex gap-2">
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <?php if ($order['payment_method'] === 'stripe'): ?>
                                                                <a href="payment.php?order_id=<?= $order['id'] ?>" 
                                                                   class="btn btn-primary btn-sm">
                                                                    Pay Now
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" 
                                                                class="btn btn-danger" 
                                                                onclick="showCancelConfirmation(<?php echo $order['id']; ?>, '<?php echo $order['payment_method']; ?>', <?php echo $order['total_price']; ?>)">
                                                            <i class="fas fa-times"></i> Cancel Booking
                                                        </button>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($order['status'] === 'completed'): ?>
                                                    <?php if (!isset($order['review_id'])): ?>
                                                        <div class="action-buttons">
                                                            <a href="review.php?order_id=<?= $order['id'] ?>" 
                                                               class="btn btn-outline-success">
                                                                <i class="fas fa-star me-2"></i>Write Review
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="review-section">
                                                            <h6 class="mb-2">Your Review</h6>
                                                            <div class="star-rating mb-2">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="fas fa-star<?= $i <= $order['rating'] ? '' : '-o' ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <p class="mb-1">
                                                                <?= htmlspecialchars($order['review_comment']) ?>
                                                            </p>
                                                            <small class="text-muted">
                                                                <?= date('M d, Y', strtotime($order['review_date'])) ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php
                                                // 获取管理员评论
                                                try {
                                                    $stmt = $pdo->prepare("
                                                        SELECT ac.*, u.name as admin_name 
                                                        FROM admin_comments ac
                                                        JOIN users u ON u.id = ac.admin_id
                                                        WHERE ac.order_id = ?
                                                    ");
                                                    $stmt->execute([$order['id']]);
                                                    $admin_comments = $stmt->fetchAll();
                                                } catch (PDOException $e) {
                                                    $admin_comments = [];
                                                }
                                                
                                                if (!empty($admin_comments)): ?>
                                                    <?php foreach ($admin_comments as $comment): ?>
                                                        <div class="admin-comment">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <strong><?= htmlspecialchars($comment['admin_name']) ?></strong>
                                                                <small class="text-muted">
                                                                    <?= date('M d, Y', strtotime($comment['created_at'])) ?>
                                                                </small>
                                                            </div>
                                                            <p class="mb-0">
                                                                <?= htmlspecialchars($comment['comment']) ?>
                                                            </p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add the cancel confirmation modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this booking?</p>
                    <div id="refundInfo" class="alert alert-info d-none">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="refundMessage"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                    <form method="POST" id="cancelForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="order_id" id="cancelOrderId">
                        <button type="submit" name="cancel_order" class="btn btn-danger">
                            Confirm Cancellation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showCancelConfirmation(orderId, paymentMethod, amount) {
            document.getElementById('cancelOrderId').value = orderId;
            let refundInfo = document.getElementById('refundInfo');
            let refundMessage = document.getElementById('refundMessage');
            
            if (paymentMethod === 'stripe') {
                refundInfo.classList.remove('d-none');
                refundMessage.textContent = `Your payment of $${amount.toFixed(2)} will be refunded to your card.`;
            } else if (paymentMethod === 'cash') {
                refundInfo.classList.remove('d-none');
                refundMessage.textContent = 'Please visit our office to receive your cash refund.';
            } else {
                refundInfo.classList.add('d-none');
            }
            
            var cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
            cancelModal.show();
        }
    </script>
</body>
</html> 