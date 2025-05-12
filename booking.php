<?php
require 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get car information
$car_id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND status = 'available'");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    $_SESSION['error_message'] = "Car not found or not available.";
    header('Location: cars.php');
    exit();
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Basic validation
    if (strtotime($start_date) >= strtotime($end_date)) {
        $_SESSION['error_message'] = "End date must be after start date.";
    } else if (strtotime($start_date) < strtotime('today')) {
        $_SESSION['error_message'] = "Start date cannot be in the past.";
    } else {
        // Check if dates are available
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE car_id = ? 
            AND status IN ('pending', 'paid') 
            AND (
                (start_date BETWEEN ? AND ?) OR
                (end_date BETWEEN ? AND ?) OR
                (start_date <= ? AND end_date >= ?)
            )
        ");
        $stmt->execute([$car_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
        $conflicts = $stmt->fetchColumn();

        if ($conflicts > 0) {
            $_SESSION['error_message'] = "Selected dates are not available.";
        } else {
            // Calculate total price
            $days = ceil((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
            $total_price = $car['price_per_day'] * $days;

            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, car_id, start_date, end_date, total_price, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");

            if ($stmt->execute([$_SESSION['user_id'], $car_id, $start_date, $end_date, $total_price])) {
                $order_id = $pdo->lastInsertId();
                $_SESSION['success_message'] = "Booking created successfully!";
                header("Location: payment.php?order_id=" . $order_id);
                exit();
            } else {
                $_SESSION['error_message'] = "Failed to create booking.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book a Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
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
        .car-image {
            max-height: 300px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        <?php include 'user_sidebar.php'; ?>

            <!-- 主要内容区 -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Book a Car</h2>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- 车辆信息 -->
                    <div class="col-md-6">
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($car['image_url']); ?>" 
                                 class="card-img-top car-image" 
                                 alt="<?php echo htmlspecialchars($car['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($car['name']); ?></h5>
                                <p class="card-text">
                                    <strong>Brand:</strong> <?php echo htmlspecialchars($car['brand']); ?><br>
                                    <strong>Model:</strong> <?php echo htmlspecialchars($car['model']); ?><br>
                                    <strong>Year:</strong> <?php echo htmlspecialchars($car['year']); ?><br>
                                    <strong>Transmission:</strong> <?php echo ucfirst(htmlspecialchars($car['transmission'])); ?><br>
                                    <strong>Fuel Type:</strong> <?php echo ucfirst(htmlspecialchars($car['fuel_type'])); ?><br>
                                    <strong>Seats:</strong> <?php echo htmlspecialchars($car['seats']); ?><br>
                                    <strong>Price per day:</strong> $<?php echo number_format($car['price_per_day'], 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 预订表单 -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Booking Details</h5>
                                <form method="POST" id="bookingForm">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="text" class="form-control datepicker" 
                                               id="start_date" name="start_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="text" class="form-control datepicker" 
                                               id="end_date" name="end_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Total Price</label>
                                        <div class="form-control" id="totalPrice">$0.00</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Book Now</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // 初始化日期选择器
        const pricePerDay = <?php echo $car['price_per_day']; ?>;
        
        function updateTotalPrice() {
            const startDate = flatpickr.parseDate(document.getElementById('start_date').value, "Y-m-d");
            const endDate = flatpickr.parseDate(document.getElementById('end_date').value, "Y-m-d");
            
            if (startDate && endDate) {
                const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                if (days > 0) {
                    const total = days * pricePerDay;
                    document.getElementById('totalPrice').textContent = `$${total.toFixed(2)}`;
                }
            }
        }

        const dateConfig = {
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                if (instance.element.id === 'start_date') {
                    endDatePicker.set('minDate', dateStr);
                }
                updateTotalPrice();
            }
        };

        const startDatePicker = flatpickr("#start_date", dateConfig);
        const endDatePicker = flatpickr("#end_date", {
            ...dateConfig,
            minDate: document.getElementById('start_date').value || "today"
        });
    </script>
</body>
</html> 