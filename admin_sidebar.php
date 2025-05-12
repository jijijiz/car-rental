<div class="col-md-2 sidebar min-vh-100 p-0">
    <div class="d-flex flex-column text-white h-100">
        <!-- Admin Dashboard Header -->
        <div class="p-3 bg-dark">
            <h4 class="mb-0">
                <i class="fas fa-user-shield me-2"></i>
                Admin Panel
            </h4>
        </div>

        <!-- Navigation Links -->
        <nav class="nav flex-column p-3">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>" 
               href="admin_dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
            
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>" 
               href="admin_users.php">
                <i class="fas fa-users me-2"></i>
                Users Management
            </a>
            
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_cars.php' ? 'active' : ''; ?>" 
               href="admin_cars.php">
                <i class="fas fa-car me-2"></i>
                Cars Management
            </a>
            
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? 'active' : ''; ?>" 
               href="admin_orders.php">
                <i class="fas fa-shopping-cart me-2"></i>
                Orders Management
            </a>
            
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reviews.php' ? 'active' : ''; ?>" 
               href="admin_reviews.php">
                <i class="fas fa-star me-2"></i>
                Reviews Management
            </a>

            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_comments.php' ? 'active' : ''; ?>" 
               href="admin_comments.php">
                <i class="fas fa-comments me-2"></i>
                Comments Management
            </a>

            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_activity_logs.php' ? 'active' : ''; ?>" 
               href="admin_activity_logs.php">
                <i class="fas fa-history me-2"></i>
                Activity Logs
            </a>

            <!-- Divider -->
            <hr class="my-3">

            <!-- Logout -->
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>
                Logout
            </a>
        </nav>

        <!-- Admin Info Footer -->
        <div class="mt-auto p-3 bg-dark">
            <small class="text-muted">
                Logged in as: <br>
                <span class="text-white"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
            </small>
        </div>
    </div>
</div>

<style>
.sidebar {
    background: #343a40;
}

.sidebar .nav-link {
    color: rgba(255,255,255,.75);
    padding: 0.8rem 1rem;
    border-radius: 0.25rem;
    margin-bottom: 0.2rem;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    color: rgba(255,255,255,1);
    background: rgba(255,255,255,.1);
}

.sidebar .nav-link.active {
    color: #fff;
    background: rgba(255,255,255,.2);
}

.sidebar .nav-link i {
    width: 20px;
    text-align: center;
}

.sidebar hr {
    margin: 1rem 0;
    border-color: rgba(255,255,255,.1);
}
</style> 