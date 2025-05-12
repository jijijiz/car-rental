<?php
require 'config.php';
require_once 'functions.php';
require 'vendor/autoload.php';
require 'stripe_config.php';  // 添加Stripe配置

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Get order information
$stmt = $pdo->prepare("
    SELECT o.*, c.name as car_name, c.image_url, c.brand, c.model 
    FROM orders o 
    JOIN cars c ON o.car_id = c.id 
    WHERE o.id = ? AND o.user_id = ? AND o.status = 'pending'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error_message'] = "Invalid order or already paid";
    header('Location: my_bookings.php');
    exit();
}

// Create Stripe Payment Intent
$stripe_amount = formatStripeAmount($order['total_price']); // 使用配置文件中的格式化函数
$payment_intent = \Stripe\PaymentIntent::create([
    'amount' => $stripe_amount,
    'currency' => STRIPE_CURRENCY,
    'metadata' => [
        'order_id' => $order_id
    ]
]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
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
            max-height: 200px;
            object-fit: cover;
        }
        #stripe-payment-form {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        #card-element {
            margin-bottom: 24px;
            padding: 12px;
            border: 1px solid #e6e6e6;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        <?php include 'user_sidebar.php'; ?>

            <!-- Main content area -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Payment</h2>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Order information -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                 class="card-img-top car-image" 
                                 alt="<?php echo htmlspecialchars($order['car_name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($order['car_name']); ?></h5>
                                <p class="card-text">
                                    <strong>Brand:</strong> <?php echo htmlspecialchars($order['brand']); ?><br>
                                    <strong>Model:</strong> <?php echo htmlspecialchars($order['model']); ?><br>
                                    <strong>Rental Period:</strong><br>
                                    <?php echo date('M d, Y', strtotime($order['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($order['end_date'])); ?><br>
                                    <strong>Total Price:</strong> <?php echo STRIPE_CURRENCY; ?> <?php echo number_format($order['total_price'], 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment options -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Payment Methods</h5>
                                
                                <!-- Stripe payment form -->
                                <form id="stripe-payment-form" class="mb-3">
                                    <div id="card-element"></div>
                                    <button id="stripe-submit" class="btn btn-primary w-100">
                                        <i class="fab fa-stripe me-2"></i>Pay with Card
                                    </button>
                                    <div id="card-errors" class="text-danger mt-2"></div>
                                </form>

                                <hr>
                                
                                <!-- Cash payment option -->
                                <form action="process_payment.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                    <input type="hidden" name="payment_method" value="cash">
                                    <input type="hidden" name="cash_payment" value="1">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-money-bill-wave me-2"></i>Pay with Cash on Arrival
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Stripe payment processing
        var stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
        var elements = stripe.elements();
        var card = elements.create('card');
        card.mount('#card-element');

        var form = document.getElementById('stripe-payment-form');
        var errorElement = document.getElementById('card-errors');
        var submitButton = document.getElementById('stripe-submit');

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            stripe.confirmCardPayment('<?php echo $payment_intent->client_secret; ?>', {
                payment_method: {
                    card: card,
                }
            }).then(function(result) {
                if (result.error) {
                    errorElement.textContent = result.error.message;
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fab fa-stripe me-2"></i>Pay with Card';
                } else {
                    // Payment successful
                    fetch('process_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            payment_intent: result.paymentIntent.id,
                            order_id: <?php echo $order_id; ?>,
                            payment_method: 'stripe'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'my_bookings.php?payment=success';
                        } else {
                            errorElement.textContent = 'Payment processing failed. Please try again.';
                            submitButton.disabled = false;
                            submitButton.innerHTML = '<i class="fab fa-stripe me-2"></i>Pay with Card';
                        }
                    });
                }
            });
        });
    </script>
</body>
</html> 