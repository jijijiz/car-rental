<?php
require 'config.php';
require_once 'functions.php';

// Verify administrator identity
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handling Search and Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$brand_filter = isset($_GET['brand']) ? $_GET['brand'] : "";
$transmission_filter = isset($_GET['transmission']) ? $_GET['transmission'] : "";
$sort = isset($_GET['sort']) ? $_GET['sort'] : "newest";

// Paging Settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Constructing a query
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR brand LIKE ? OR model LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($brand_filter) {
    $where_conditions[] = "brand = ?";
    $params[] = $brand_filter;
}

if ($transmission_filter) {
    $where_conditions[] = "transmission = ?";
    $params[] = $transmission_filter;
}

$where_clause = implode(" AND ", $where_conditions);

// sort by
$sort_clause = '';
switch($sort) {
    case 'price_asc':
        $sort_clause = 'price_per_day ASC';
        break;
    case 'price_desc':
        $sort_clause = 'price_per_day DESC';
        break;
    case 'name_asc':
        $sort_clause = 'name ASC';
        break;
    case 'name_desc':
        $sort_clause = 'name DESC';
        break;
    default:
        $sort_clause = 'id DESC';  
        break;
}

// Get the total number of vehicles
$count_sql = "SELECT COUNT(*) FROM cars WHERE $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_cars = $stmt->fetchColumn();
$total_pages = ceil($total_cars / $items_per_page);

// Get the vehicle list
$sql = "SELECT * FROM cars WHERE $where_clause ORDER BY $sort_clause LIMIT $items_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);  // Only pass in the WHERE condition parameters
$cars = $stmt->fetchAll();

// Get all brands for filtering
$brands_stmt = $pdo->query("SELECT DISTINCT brand FROM cars ORDER BY brand");
$brands = $brands_stmt->fetchAll(PDO::FETCH_COLUMN);

// Handling adding/editing vehicles
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    if (isset($_POST['action'])) {
        $car_id = $_POST['car_id'] ?? 0;
        
        switch ($_POST['action']) {
            case 'add':
                handleAddCar($pdo);
                break;
            case 'edit':
                handleEditCar($pdo);
                break;
            case 'delete':
                handleDeleteCar($pdo);
                break;
            case 'toggle_status':
                handleToggleStatus($pdo);
                break;
            case 'update_status':
                try {
                    $new_status = $_POST['status'] ?? '';
                    $stmt = $pdo->prepare("UPDATE cars SET status = ? WHERE id = ?");                        

                    if ($stmt->execute([$new_status, $car_id])) {
                        $_SESSION['success'] = "Car status updated successfully";
                    } else {
                        $_SESSION['error'] = "Failed to update car status";
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Database error occurred";
                }
                break;
            
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
                    if ($stmt->execute([$car_id])) {  
                        $_SESSION['success'] = "Car deleted successfully";
                    } else {
                        $_SESSION['error'] = "Failed to delete car";
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Database error occurred";
                }
                break;
        }
        
        header('Location: admin_cars.php');
        exit();
    }
}

// Handling image uploads
function handleImageUpload($file) {
    $target_dir = "uploads/cars/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    return false;
}

// Processing Adding Vehicles
function handleAddCar($pdo) {
    try {
        $name = $_POST['name'];
        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $price_per_day = $_POST['price_per_day'];
        
        $image_url = '';
        if (!empty($_FILES['image']['name'])) {
            $image_url = handleImageUpload($_FILES['image']);
        }

        $stmt = $pdo->prepare("INSERT INTO cars (name, brand, model, year, price_per_day, seats, transmission, fuel_type, image_url, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $description = isset($_POST['description']) ? json_encode($_POST['description']) : '[]';
        
        $stmt->execute([
            $name,
            $brand,
            $model,
            $_POST['year'],
            $price_per_day,
            $_POST['seats'],
            $_POST['transmission'],
            $_POST['fuel_type'],
            $image_url,
            1,
            $description
        ]);

        // Add activity log
        log_activity($_SESSION['user_id'], 'Add Car', sprintf(
            'Added new car: %s %s %s (RM%.2f/day)',
            $brand,
            $model,
            $name,
            $price_per_day
        ));
        
        $_SESSION['success'] = "Car added successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding car: " . $e->getMessage();
    }
    header('Location: admin_cars.php');
    exit();
}

// Processing Editing Vehicles
function handleEditCar($pdo) {
    try {
        $car_id = $_POST['car_id'];
        
        // 获取原始车辆数据
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $original_car = $stmt->fetch();
        
        $name = $_POST['name'];
        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $price_per_day = $_POST['price_per_day'];
        
        $params = [
            $name,
            $brand,
            $model,
            $_POST['year'],
            $price_per_day,
            $_POST['seats'],
            $_POST['transmission'],
            $_POST['fuel_type']
        ];

        $sql = "UPDATE cars SET name=?, brand=?, model=?, year=?, price_per_day=?, seats=?, transmission=?, fuel_type=?";

        if (!empty($_FILES['image']['name'])) {
            $image_url = handleImageUpload($_FILES['image']);
            if ($image_url) {
                $sql .= ", image_url=?";
                $params[] = $image_url;
            }
        }

        if (isset($_POST['description'])) {
            $sql .= ", description=?";
            $params[] = json_encode($_POST['description']);
        }

        $sql .= " WHERE id=?";
        $params[] = $car_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // 添加活动日志，包含价格变化信息
        $price_change = '';
        if ($original_car['price_per_day'] != $price_per_day) {
            $price_change = sprintf(
                ' (Price changed from RM%.2f to RM%.2f)',
                $original_car['price_per_day'],
                $price_per_day
            );
        }

        log_activity($_SESSION['user_id'], 'Edit Car', sprintf(
            'Updated car ID %d: %s %s %s%s',
            $car_id,
            $brand,
            $model,
            $name,
            $price_change
        ));

        $_SESSION['success'] = "Car updated successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating car: " . $e->getMessage();
    }
    header('Location: admin_cars.php');
    exit();
}

// Processing deleted vehicles
function handleDeleteCar($pdo) {
    try {
        $car_id = $_POST['car_id'];
        
        // Get the vehicle image path
        $stmt = $pdo->prepare("SELECT image_url FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();
        
        // Delete image files
        if ($car && $car['image_url'] && file_exists($car['image_url'])) {
            unlink($car['image_url']);
        }
        
        // Delete Database Records
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        
        // Add activity log
        log_activity($_SESSION['user_id'], 'Delete Car', sprintf(
            'Deleted car ID %d: %s %s %s',
            $car_id,
            $car['brand'],
            $car['model'],
            $car['name']
        ));
        
        $_SESSION['success'] = "Car deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting car: " . $e->getMessage();
    }
    header('Location: admin_cars.php');
    exit();
}

// Handle state transitions
function handleToggleStatus($pdo) {
    try {
        $car_id = $_POST['car_id'];
        $stmt = $pdo->prepare("UPDATE cars SET status = NOT status WHERE id = ?");
        $stmt->execute([$car_id]);
        $_SESSION['success'] = "Car status updated successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating status: " . $e->getMessage();
    }
    header('Location: admin_cars.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Car Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background: #495057; }
        .nav-link.active { background: #495057; }
        .card { border: none; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .car-image { width: 100px; height: 60px; object-fit: cover; border-radius: 5px; }
        .status-badge { cursor: pointer; }
        .description-badge { margin: 2px; }
        .select2-container { width: 100% !important; }
        .table td {
            vertical-align: middle;
        }
        .table {
            table-layout: fixed;
            width: 100%;
        }
        .car-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .status-badge {
            cursor: pointer;
            width: 80px;
            display: inline-block;
            text-align: center;
        }
        .description-badge {
            margin: 2px;
            font-size: 0.8em;
            white-space: nowrap;
        }
        .description-cell {
            overflow-x: auto;
            max-height: 80px;
        }
        .table td {
            vertical-align: middle;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .text-nowrap {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-group {
            white-space: nowrap;
        }
        .table-container {
            height: calc(100vh - 250px);
            overflow-y: auto;
            position: relative;
        }
        .table {
            table-layout: fixed;
            width: 100%;
            margin-bottom: 0;
        }
        thead {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }
        .car-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .status-badge {
            width: 80px;
            display: inline-block;
            text-align: center;
        }
        .description-cell {
            max-height: 60px;
            overflow: hidden;
        }
        .description-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 2px;
        }
        .table td {
            vertical-align: middle;
            height: 80px;
            padding: 10px;
        }
        .text-nowrap {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-group {
            white-space: nowrap;
        }
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'admin_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Car Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">
                    <i class="fas fa-plus"></i> Add New Car
                </button>
            </div>

            <!-- Alerts -->
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
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="brand" class="form-select">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= htmlspecialchars($brand) ?>" <?= $brand_filter === $brand ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($brand) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="transmission" class="form-select">
                                <option value="">All Transmissions</option>
                                <option value="Automatic" <?= $transmission_filter === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                                <option value="Manual" <?= $transmission_filter === 'Manual' ? 'selected' : '' ?>>Manual</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="sort" class="form-select">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="admin_cars.php" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cars Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 120px;">Image</th>
                                    <th style="width: 150px;">Name</th>
                                    <th style="width: 150px;">Brand/Model</th>
                                    <th style="width: 80px;">Year</th>
                                    <th style="width: 100px;">Price/Day</th>
                                    <th style="width: 80px;">Seats</th>
                                    <th style="width: 120px;">Transmission</th>
                                    <th style="width: 100px;">Fuel Type</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cars as $car): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($car['id']) ?></td>
                                        <td>
                                            <img src="<?= htmlspecialchars($car['image_url']) ?>" 
                                                 class="car-image" 
                                                 onerror="this.src='images/no-image.jpg'"
                                                 alt="<?= htmlspecialchars($car['name']) ?>">
                                        </td>
                                        <td class="text-nowrap"><?= htmlspecialchars($car['name']) ?></td>
                                        <td>
                                            <div class="text-nowrap">
                                                <?= htmlspecialchars($car['brand']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($car['model']) ?></small>
                                            </div>
                                        </td>
                                        <td class="text-nowrap"><?= htmlspecialchars($car['year']) ?></td>
                                        <td class="text-nowrap">$<?= number_format($car['price_per_day'], 2) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($car['seats']) ?></td>
                                        <td class="text-nowrap"><?= htmlspecialchars($car['transmission']) ?></td>
                                        <td class="text-nowrap"><?= htmlspecialchars($car['fuel_type']) ?></td>
                                        <td>
                                            <span class="badge <?= $car['status'] ? 'bg-success' : 'bg-warning' ?> status-badge">
                                                <?= $car['status'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick="editCar(<?= $car['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteCar(<?= $car['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&brand=<?= urlencode($brand_filter) ?>&transmission=<?= urlencode($transmission_filter) ?>&sort=<?= urlencode($sort) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Car Modal -->
<div class="modal fade" id="addCarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Add New Car</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Price per Day</label>
                        <input type="number" name="price_per_day" class="form-control" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Seats</label>
                        <input type="number" name="seats" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Transmission</label>
                        <select name="transmission" class="form-select" required>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fuel Type</label>
                        <select name="fuel_type" class="form-select" required>
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Electric">Electric</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="action" value="add" class="btn btn-primary">Add Car</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Car Modal -->
<div class="modal fade" id="editCarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Car</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCarForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="car_id" id="editCarId">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" class="form-control" name="brand" id="editBrand" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" class="form-control" name="model" id="editModel" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" id="editYear" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Price per Day</label>
                            <input type="number" class="form-control" name="price_per_day" id="editPrice" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Seats</label>
                            <input type="number" class="form-control" name="seats" id="editSeats" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Transmission</label>
                            <select class="form-select" name="transmission" id="editTransmission" required>
                                <option value="Automatic">Automatic</option>
                                <option value="Manual">Manual</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fuel Type</label>
                            <select class="form-select" name="fuel_type" id="editFuelType" required>
                                <option value="Petrol">Petrol</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Electric">Electric</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" class="form-control" name="image" id="editImage">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="updateCar()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Car</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this car?
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="car_id" id="deleteCarId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="action" value="delete" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function editCar(id) {
    fetch(`get_car.php?id=${id}`)
        .then(response => response.json())
        .then(car => {
            console.log('Car data received:', car); // Add debug output
            
            document.getElementById('editCarId').value = car.id;
            document.getElementById('editName').value = car.name;
            document.getElementById('editBrand').value = car.brand;
            document.getElementById('editModel').value = car.model;
            document.getElementById('editYear').value = car.year;
            document.getElementById('editPrice').value = car.price_per_day;
            document.getElementById('editSeats').value = car.seats;
            document.getElementById('editStatus').value = car.status;
            
            // Set transmission and fuel_type
            if (car.transmission) {
                document.getElementById('editTransmission').value = car.transmission;
                console.log('Setting transmission to:', car.transmission);
            }
            if (car.fuel_type) {
                document.getElementById('editFuelType').value = car.fuel_type;
                console.log('Setting fuel type to:', car.fuel_type);
            }
            
            new bootstrap.Modal(document.getElementById('editCarModal')).show();
        })
        .catch(error => {
            console.error('Error fetching car data:', error);
        });
}

function deleteCar(id) {
    $('#deleteCarId').val(id);
    $('#deleteCarModal').modal('show');
}

function toggleStatus(id) {
    if (confirm('Are you sure you want to change the status?')) {
        $('<form>').attr({
            method: 'POST',
            action: 'admin_cars.php'
        }).append($('<input>').attr({
            type: 'hidden',
            name: 'action',
            value: 'toggle_status'
        })).append($('<input>').attr({
            type: 'hidden',
            name: 'car_id',
            value: id
        })).appendTo('body').submit();
    }
}

function updateCar() {
    // Get the form
    const form = document.getElementById('editCarForm');
    // Add action field
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'edit';
    form.appendChild(actionInput);
    // Submit form
    form.submit();
}

// Initialize Select2
$(document).ready(function() {
    $('.form-select[multiple]').select2({
        tags: true,
        tokenSeparators: [',', ' ']
    });
});
</script>

</body>
</html>
