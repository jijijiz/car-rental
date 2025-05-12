<?php
/**
 * Payment Processing Script
 * 
 * Test Card Information:
 * ---------------------
 * Successful payment:
 * Card number: 4242 4242 4242 4242
 * Expiry date: Any future date
 * CVC: Any 3 digits
 * 
 * 3D Secure Authentication:
 * Card number: 4000 0025 0000 3155
 * 
 * Payment Decline:
 * Card number: 4000 0000 0000 9995
 */

require 'config.php';
require_once 'functions.php';
require 'vendor/autoload.php';
require 'stripe_config.php';  // 添加Stripe配置

header('Content-Type: application/json');

// Verify user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Process POST request
$input = file_get_contents('php://input');
if (!empty($input)) {
    $input = json_decode($input, true);
}
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : (isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0);

// Handle order cancellation and refund
if ((isset($input['action']) && $input['action'] === 'cancel') || (isset($_POST['action']) && $_POST['action'] === 'cancel')) {
    try {
        // First, get the order details
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE id = ? AND user_id = ? AND status = 'paid'
        ");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order = $stmt->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found or cannot be cancelled']);
            exit();
        }

        // Check if the rental period hasn't started yet
        $start_date = new DateTime($order['start_date']);
        $now = new DateTime();
        
        if ($start_date <= $now) {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel order after rental period has started']);
            exit();
        }

        // Process refund based on payment method
        if ($order['payment_method'] === 'stripe' && $order['payment_id']) {
            // Process Stripe refund
            $refund = \Stripe\Refund::create([
                'payment_intent' => $order['payment_id'],
                'reason' => 'requested_by_customer'
            ]);

            if ($refund->status === 'succeeded') {
                // Update order status
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'cancelled',
                        payment_notes = CONCAT(payment_notes, ' | Refunded via Stripe: ', ?),
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                
                if ($stmt->execute([$refund->id, $order_id, $_SESSION['user_id']])) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Order cancelled and payment refunded successfully'
                    ]);
                    exit();
                }
            }
        } else if ($order['payment_method'] === 'cash') {
            // Handle cash refund
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled',
                    payment_notes = CONCAT(payment_notes, ' | Cash refund pending'),
                    updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            if ($stmt->execute([$order_id, $_SESSION['user_id']])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Order cancelled. Please visit our office for cash refund.'
                ]);
                exit();
            }
        }

        echo json_encode(['success' => false, 'message' => 'Failed to process refund']);
        exit();

    } catch (Exception $e) {
        error_log('Refund error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error processing refund']);
        exit();
    }
}

// Handle cash payment
if ((isset($_POST['cash_payment']) && isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash') || 
    (isset($input['cash_payment']) && isset($input['payment_method']) && $input['payment_method'] === 'cash')) {
    error_log("Cash payment request received");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Input data: " . print_r($input, true));
    
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : (isset($input['csrf_token']) ? $input['csrf_token'] : '');
    if (!verify_csrf_token($csrf_token)) {
        error_log("CSRF token verification failed");
        $_SESSION['error_message'] = "Invalid request";
        header('Location: payment.php?order_id=' . $order_id);
        exit();
    }

    try {
        // First verify the order exists and is pending
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order = $stmt->fetch();

        if (!$order) {
            error_log("Order not found or not pending: order_id=" . $order_id . ", user_id=" . $_SESSION['user_id']);
            $_SESSION['error_message'] = "Invalid order or already processed";
            header('Location: payment.php?order_id=' . $order_id);
            exit();
        }

        error_log("Found order: " . print_r($order, true));

        // Update order with cash payment details
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'pending',
                payment_method = 'cash',
                payment_notes = 'Pending cash payment on arrival',
                updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        if ($stmt->execute([$order_id, $_SESSION['user_id']])) {
            error_log("Order updated successfully");
            $_SESSION['success_message'] = "Order confirmed! Please pay cash on arrival";
            header('Location: my_bookings.php');
            exit();
        } else {
            error_log("Failed to update order");
            throw new PDOException("Failed to update order");
        }
    } catch (PDOException $e) {
        error_log("Cash payment error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error processing payment";
        header('Location: payment.php?order_id=' . $order_id);
        exit();
    }
}

// Handle Stripe payment
if (isset($input['payment_intent']) && isset($input['payment_method']) && $input['payment_method'] === 'stripe') {
    try {
        // Verify payment intent
        $payment_intent = \Stripe\PaymentIntent::retrieve($input['payment_intent']);
        
        if ($payment_intent->status === 'succeeded') {
            // Update order status
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'paid',
                    payment_method = 'stripe',
                    payment_id = ?,
                    payment_notes = 'Payment completed via Stripe',
                    paid_at = NOW(),
                    updated_at = NOW()
                WHERE id = ? AND user_id = ? AND status = 'pending'
            ");
            
            if ($stmt->execute([$payment_intent->id, $order_id, $_SESSION['user_id']])) {
                // Log payment if needed
                // logPayment($order_id, 'stripe', $payment_intent->id, $payment_intent->amount);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment successful',
                    'redirect_url' => 'my_bookings.php?payment=success'
                ]);
                exit();
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
        exit();
        
    } catch (Exception $e) {
        error_log('Stripe payment error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error processing payment']);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit(); 