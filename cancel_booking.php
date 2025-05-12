<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Verify order exists and belongs to current user
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ? 
    AND status IN ('pending', 'completed')  // Allow cancellation of pending and completed orders
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if ($order) {
    // Update order status to cancelled
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'cancelled' 
        WHERE id = ? AND user_id = ?
    ");
    
    if ($stmt->execute([$order_id, $_SESSION['user_id']])) {
        $_SESSION['success_message'] = "Booking cancelled successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to cancel booking.";
    }
} else {
    $_SESSION['error_message'] = "Invalid booking or cannot be cancelled.";
}

// Return to my bookings page
header('Location: my_bookings.php');
exit();