<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000000;

// Build query
$query = "SELECT * FROM cars WHERE status = 'available'";
$params = [];

if ($brand) {
    $query .= " AND brand = ?";
    $params[] = $brand;
}
if ($transmission) {
    $query .= " AND transmission = ?";
    $params[] = $transmission;
}
$query .= " AND price_per_day BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;

$query .= " ORDER BY price_per_day ASC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$cars = $stmt->fetchAll();

// Get all brands for filtering
$stmt = $pdo->query("SELECT DISTINCT brand FROM cars ORDER BY brand");
$brands = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rent a Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .car-card {
            transition: transform 0.3s;
        }
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .car-image {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'user_sidebar.php'; ?>

            <!-- Main content area -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Available Cars</h2>

                <!-- Filter form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Brand</label>
                                <select name="brand" class="form-select">
                                    <option value="">All Brands</option>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?php echo htmlspecialchars($b); ?>" 
                                                <?php echo $brand === $b ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Transmission</label>
                                <select name="transmission" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="automatic" <?php echo $transmission === 'automatic' ? 'selected' : ''; ?>>Automatic</option>
                                    <option value="manual" <?php echo $transmission === 'manual' ? 'selected' : ''; ?>>Manual</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Min Price</label>
                                <input type="number" name="min_price" class="form-control" 
                                       value="<?php echo $min_price; ?>" min="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Max Price</label>
                                <input type="number" name="max_price" class="form-control" 
                                       value="<?php echo $max_price; ?>" min="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Car list -->
                <div class="row">
                    <?php foreach ($cars as $car): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card car-card h-100">
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
                                    <div class="d-grid">
                                        <a href="booking.php?car_id=<?php echo $car['id']; ?>" 
                                           class="btn btn-primary">Book Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($cars)): ?>
                    <div class="alert alert-info">
                        No cars found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 