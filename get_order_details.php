<?php
// Disable error display, log to file instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ensure header is set before any output
header('Content-Type: application/json');

require 'config.php';

try {
    // Check database connection
    if (!isset($pdo)) {
        throw new Exception("Database connection not established");
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid order ID");
    }

    $orderId = (int)$_GET['id'];
    
    // Get all required data in a single query
    $sql = "SELECT 
                o.*,
                u.name as user_name,
                u.email as user_email,
                c.name as car_name,
                c.brand as car_brand,
                c.model as car_model,
                c.price_per_day
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN cars c ON o.car_id = c.id
            WHERE o.id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$orderId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception("Order not found");
    }

    // Calculate rental duration
    $result['duration'] = ceil((strtotime($result['return_date']) - strtotime($result['pickup_date'])) / (60 * 60 * 24));

    // Format dates
    $result['created_at'] = date('Y-m-d H:i', strtotime($result['created_at']));
    $result['pickup_date'] = date('Y-m-d', strtotime($result['pickup_date']));
    $result['return_date'] = date('Y-m-d', strtotime($result['return_date']));

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Order details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} 