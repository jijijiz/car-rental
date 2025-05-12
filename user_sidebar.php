<!-- Sidebar -->
<div class="col-md-2 sidebar p-0">
    <div class="d-flex flex-column h-100">
        <div class="p-3 text-white">
            <h5>User Dashboard</h5>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'active' : ''; ?>" href="cars.php">
                    <i class="fas fa-car me-2"></i> Rent a Car
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_bookings.php' ? 'active' : ''; ?>" href="my_bookings.php">
                    <i class="fas fa-shopping-cart me-2"></i> My Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_reviews.php' ? 'active' : ''; ?>" href="my_reviews.php">
                    <i class="fas fa-star me-2"></i> My Reviews
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_comments.php' ? 'active' : ''; ?>" href="my_comments.php">
                    <i class="fas fa-comments me-2"></i> Admin Comments
                </a>
            </li>
            <li class="nav-item">
                <a href="send_email.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'send_email.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope me-2"></i> Send Email
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user me-2"></i> My Profile
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
.sidebar {
    background-color: #343a40;
    min-height: 100vh;
    position: sticky;
    top: 0;
}

.sidebar .nav-link {
    color: rgba(255,255,255,.8);
    padding: 0.8rem 1rem;
    transition: all 0.3s;
}

.sidebar .nav-link:hover {
    color: #fff;
    background-color: rgba(255,255,255,.1);
}

.sidebar .nav-link.active {
    color: #fff;
    background-color: #0d6efd;
}

.sidebar .fas {
    width: 20px;
    text-align: center;
}
</style> 