<?php
require 'config.php';

// Verify admin identity
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get statistics
$stats = [];

// Total users count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stmt->execute();
$stats['total_users'] = $stmt->fetchColumn();

// Total orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders");
$stmt->execute();
$stats['total_orders'] = $stmt->fetchColumn();

// Total revenue
$stmt = $pdo->prepare("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'");
$stmt->execute();
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// Active orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status IN ('paid', 'active')");
$stmt->execute();
$stats['active_orders'] = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name, c.name as car_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Recent user comments
$stmt = $pdo->prepare("
    SELECT uc.*, u.name as user_name
    FROM user_comments uc
    JOIN users u ON uc.user_id = u.id
    ORDER BY uc.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background: #495057; }
        .nav-link.active { background: #495057; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'admin_sidebar.php'; ?>

            <!-- Main Content Area -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <p class="card-text display-6"><?php echo $stats['total_users']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Revenue</h5>
                                <p class="card-text display-6">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Orders</h5>
                                <p class="card-text display-6"><?php echo $stats['total_orders']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Active Orders</h5>
                                <p class="card-text display-6"><?php echo $stats['active_orders']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders and Comments -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Orders</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Car</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['car_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo get_status_color($order['status']); ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Comments</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($recent_comments as $index => $comment): ?>
                                    <div class="mb-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($comment['user_name']); ?></h6>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?>
                                        </small>
                                    </div>
                                    <?php if ($index < count($recent_comments) - 1): ?>
                                        <hr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
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

<?php
function get_status_color($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'info';
        case 'active': return 'primary';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?> 