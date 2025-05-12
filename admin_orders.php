<?php
require_once 'config.php';
require_once 'functions.php';  // Make sure this line is at the beginning of the file

// Verify admin identity
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle search and filtering
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = [];
$params = [];

// Search by order ID or username
if ($search) {
    $where_clauses[] = "(o.id LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filter by order status
if ($status_filter) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
}

// Assemble query conditions
$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get order data (paginated)
$stmt = $pdo->prepare("
    SELECT o.*, u.name AS user_name, c.name AS car_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    $where_sql
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get total orders count (for pagination)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id JOIN cars c ON o.car_id = c.id $where_sql");
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Handle order status updates
if (isset($_POST['action'])) {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    $order_id = $_POST['order_id'] ?? 0;
    
    switch ($_POST['action']) {
        case 'delete':
            try {
                $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
                if ($stmt->execute([$order_id])) {
                    $_SESSION['success'] = "Order deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete order";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Database error occurred";
            }
            break;
            
        case 'update_status':
            $new_status = $_POST['status'] ?? '';
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $order_id])) {
                    $_SESSION['success'] = "Order status updated successfully";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Failed to update order status";
            }
            break;
    }
    
    header('Location: admin_orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Orders Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background: #495057; }
        .nav-link.active { background: #495057; }
        .status-badge { cursor: pointer; }
        .order-summary { 
            background: #f8f9fa; 
            border-radius: 10px; 
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-item {
            padding: 10px;
            border-right: 1px solid #dee2e6;
        }
        .summary-item:last-child {
            border-right: none;
        }
        .table th { 
            background-color: #f8f9fa;
            white-space: nowrap;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Orders Management</h2>
                    <div>
                        <a href="?export=csv" class="btn btn-success">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </a>
                        <a href="?export=pdf" class="btn btn-danger ms-2">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </a>
                    </div>
                </div>

                <!-- Order Statistics -->
                <div class="row order-summary text-center mb-4">
                    <div class="col summary-item">
                        <h3 class="h5">Total Orders</h3>
                        <p class="h3 mb-0"><?php echo $total_orders; ?></p>
                    </div>
                    <div class="col summary-item">
                        <h3 class="h5">Pending</h3>
                        <p class="h3 mb-0 text-warning">
                            <?php 
                            $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
                            echo $stmt->fetchColumn();
                            ?>
                        </p>
                    </div>
                    <div class="col summary-item">
                        <h3 class="h5">Active</h3>
                        <p class="h3 mb-0 text-primary">
                            <?php 
                            $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'active'");
                            echo $stmt->fetchColumn();
                            ?>
                        </p>
                    </div>
                    <div class="col summary-item">
                        <h3 class="h5">Completed</h3>
                        <p class="h3 mb-0 text-success">
                            <?php 
                            $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
                            echo $stmt->fetchColumn();
                            ?>
                        </p>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search by Order ID or User" 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="admin_orders.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Order List -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>User</th>
                                        <th>Car</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr data-order-id="<?php echo $order['id']; ?>">
                                        <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($order['user_name']); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-car me-2"></i>
                                            <?php echo htmlspecialchars($order['car_name']); ?>
                                        </td>
                                        <td>
                                            <strong>$<?php echo number_format($order['total_price'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="status" class="form-select form-select-sm status-select" 
                                                        style="width: 130px;" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="paid" <?php echo $order['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                    <option value="active" <?php echo $order['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-info btn-sm" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- 分页 -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 在 HTML 底部添加订单详情模态框 -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <!-- 订单详情将通过 AJAX 加载到这里 -->
                </div>
            </div>
        </div>
    </div>

    <!-- 修改删除确认模态框 -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                        <h5>Are you sure?</h5>
                        <p class="text-muted">
                            You are about to delete order #<span id="deleteOrderId"></span>. 
                            This action cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash-alt me-2"></i>Delete Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let orderIdToDelete = null;

    function deleteOrder(orderId) {
        orderIdToDelete = orderId;
        $('#deleteOrderId').text(String(orderId).padStart(5, '0'));
        $('#deleteOrderModal').modal('show');
    }

    $(document).ready(function() {
        $('#confirmDelete').click(function() {
            if (!orderIdToDelete) return;
            
            const $btn = $(this);
            const originalText = $btn.html();
            
            // 显示加载状态
            $btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Deleting...').prop('disabled', true);
            
            $.ajax({
                url: 'delete_order.php',
                method: 'POST',
                data: { id: orderIdToDelete },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // 关闭模态框
                        $('#deleteOrderModal').modal('hide');
                        
                        // 显示成功消息
                        const alert = $(`
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                Order #${String(orderIdToDelete).padStart(5, '0')} has been deleted successfully
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `).insertBefore('.card:first');
                        
                        // 移除对应的表格行
                        $(`tr[data-order-id="${orderIdToDelete}"]`).fadeOut(400, function() {
                            $(this).remove();
                            
                            // 如果表格为空，显示提示
                            if ($('table tbody tr').length === 0) {
                                $('table tbody').append(`
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p class="mb-0">No orders found</p>
                                        </td>
                                    </tr>
                                `);
                            }
                        });
                        
                        // 3秒后自动隐藏提示
                        setTimeout(() => {
                            alert.alert('close');
                        }, 3000);
                    } else {
                        // 显示错误消息
                        $('#deleteOrderModal .modal-body').prepend(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${response.message || 'Failed to delete order'}
                            </div>
                        `);
                    }
                },
                error: function() {
                    // 显示错误消息
                    $('#deleteOrderModal .modal-body').prepend(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error occurred while deleting order
                        </div>
                    `);
                },
                complete: function() {
                    // 恢复按钮状态
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });
        
        // 当模态框关闭时清理
        $('#deleteOrderModal').on('hidden.bs.modal', function() {
            orderIdToDelete = null;
            $(this).find('.alert').remove();
        });
    });

    function viewOrder(orderId) {
        $('#orderDetailContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#orderDetailModal').modal('show');
        
        $.ajax({
            url: 'get_order_details.php',
            data: { id: orderId },
            method: 'GET',
            dataType: 'json',
            success: function(order) {
                if (order.error) {
                    $('#orderDetailContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${order.message}
                        </div>
                    `);
                    return;
                }

                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p><strong>Order ID:</strong> #${String(order.id).padStart(5, '0')}</p>
                            <p><strong>Created At:</strong> ${order.created_at}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${getStatusBadgeColor(order.status)}">${order.status}</span></p>
                            <p><strong>Total Price:</strong> $${parseFloat(order.total_price).toFixed(2)}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong>Name:</strong> ${order.user_name || 'N/A'}</p>
                            <p><strong>Email:</strong> ${order.user_email || 'N/A'}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Car Information</h6>
                            <p><strong>Car:</strong> ${order.car_name || 'N/A'}</p>
                            <p><strong>Brand:</strong> ${order.car_brand || 'N/A'}</p>
                            <p><strong>Model:</strong> ${order.car_model || 'N/A'}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Rental Details</h6>
                            <p><strong>Pickup Date:</strong> ${order.pickup_date}</p>
                            <p><strong>Return Date:</strong> ${order.return_date}</p>
                            <p><strong>Duration:</strong> ${order.duration} days</p>
                            <p><strong>Price per Day:</strong> $${parseFloat(order.price_per_day || 0).toFixed(2)}</p>
                        </div>
                    </div>
                `;
                $('#orderDetailContent').html(html);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                $('#orderDetailContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading order details. Please try again later.
                    </div>
                `);
            }
        });
    }

    function getStatusBadgeColor(status) {
        const colors = {
            'pending': 'warning',
            'paid': 'info',
            'active': 'primary',
            'completed': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }
    </script>
</body>
</html>
