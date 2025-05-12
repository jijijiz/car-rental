<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user orders
$stmt = $pdo->prepare("
    SELECT o.*, c.name as car_name, c.image_url, c.brand, c.model 
    FROM orders o 
    JOIN cars c ON o.car_id = c.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get number of pending reviews (completed orders without reviews)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM orders o 
    WHERE o.user_id = ? 
    AND o.status = 'completed'
    AND NOT EXISTS (
        SELECT 1 
        FROM reviews r 
        WHERE r.order_id = o.id
    )
");
$stmt->execute([$_SESSION['user_id']]);
$pending_reviews = $stmt->fetch()['count'];

// Get number of pending payments
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? AND status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$pending_payments = $stmt->fetch()['count'];

// Get number of active orders (including paid and active status)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? AND status IN ('paid', 'active')
");
$stmt->execute([$_SESSION['user_id']]);
$active_orders = $stmt->fetch()['count'];

function match_status($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'primary';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .nav-link {
            color: #fff;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background: #495057;
            transform: translateX(5px);
        }
        .nav-link.active {
            background: #0d6efd;
        }
        .nav-link i {
            width: 25px;
            text-align: center;
        }
        .main-content {
            padding: 30px;
            background: #f8f9fa;
        }
        .welcome-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stats-label {
            color: #6c757d;
            font-size: 14px;
        }
        .recent-orders {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .order-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }
        .order-item:hover {
            background-color: #f8f9fa;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .btn-action {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        .car-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        <?php include 'user_sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-10 main-content">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                    <p class="mb-0">Here's what's happening with your car rentals today.</p>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stats-number"><?php echo $active_orders; ?></div>
                            <div class="stats-label">Active Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="stats-number"><?php echo $pending_payments; ?></div>
                            <div class="stats-label">Pending Payments</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stats-number"><?php echo $pending_reviews; ?></div>
                            <div class="stats-label">Pending Reviews</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stats-number"><?php echo $active_orders; ?></div>
                            <div class="stats-label">Active Orders</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="recent-orders">
                    <h4 class="mb-4">Recent Orders</h4>
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent orders found</p>
                            <a href="cars.php" class="btn btn-primary">Browse Cars</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                             class="car-image" 
                                             alt="<?php echo htmlspecialchars($order['car_name']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($order['car_name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($order['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($order['end_date'])); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="badge bg-<?php echo match_status($order['status']); ?> status-badge">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-<?php echo $order['payment_method'] === 'cash' ? 'money-bill-wave' : 'credit-card'; ?> me-2"></i>
                                            <span><?php echo $order['payment_method'] === 'cash' ? 'Cash Payment' : 'Card Payment'; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <a href="my_bookings.php" class="btn btn-outline-primary btn-action">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 