<?php
require 'config.php';

// Verify that you are an administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get statistics
$stats = [];

// Total number of activities
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs");
$stmt->execute();
$stats['total_activities'] = $stmt->fetchColumn();

// Number of activities today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$stats['today_activities'] = $stmt->fetchColumn();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get the total number of pages
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs");
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get activity log
$stmt = $pdo->prepare("
    SELECT al.*, u.name as user_name, u.email, u.role
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT ?, ?
");
$stmt->bindValue(1, $offset, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background: #495057; }
        .nav-link.active { background: #495057; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .log-entry {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        .log-entry:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .log-entry.admin { border-left-color: #dc3545; }
        .log-entry.user { border-left-color: #198754; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'admin_sidebar.php'; ?>

            <!-- Main content area -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Activity Logs</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Activities</h5>
                                <p class="card-text display-6"><?php echo $stats['total_activities']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Today's Activities</h5>
                                <p class="card-text display-6"><?php echo $stats['today_activities']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Log List -->
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($logs as $log): ?>
                            <div class="log-entry <?php echo $log['role']; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-circle me-2"></i>
                                        <?php echo htmlspecialchars($log['user_name']); ?>
                                        <span class="badge bg-<?php echo $log['role'] === 'admin' ? 'danger' : 'success'; ?> ms-2">
                                            <?php echo ucfirst($log['role']); ?>
                                        </span>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <strong class="text-primary">
                                        <i class="fas fa-arrow-right me-2"></i>
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </strong>
                                </div>
                                <p class="mb-2"><?php echo htmlspecialchars($log['description']); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-globe me-1"></i> <?php echo htmlspecialchars($log['ip_address']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 