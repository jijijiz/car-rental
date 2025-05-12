<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'mail_config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize database connection
$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($subject) || empty($message)) {
        $error = "Subject and message cannot be empty";
    } else {
        try {
            $mail = new PHPMailer(true);
            
            // Use configuration from mail_config.php
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port = MAIL_PORT;
            $mail->CharSet = 'UTF-8';

            // Set sender and recipient
            $mail->setFrom($user['email'], $user['name']);
            $mail->addAddress(ADMIN_EMAIL);
            
            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            
            // Build email body
            $emailBody = "<h2>User Inquiry</h2>";
            $emailBody .= "<p><strong>Username:</strong> " . htmlspecialchars($user['name']) . "</p>";
            $emailBody .= "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
            $emailBody .= "<p><strong>Message:</strong></p>";
            $emailBody .= "<p>" . nl2br(htmlspecialchars($message)) . "</p>";
            
            $mail->Body = $emailBody;
            
            if ($mail->send()) {
                // Save to database
                $stmt = $conn->prepare("INSERT INTO user_messages (user_id, message, subject, created_at, status) 
                                     VALUES (?, ?, ?, NOW(), 'sent')");
                $stmt->bind_param("iss", $user_id, $message, $subject);
                $stmt->execute();
                
                $success = "Email has been sent to administrator successfully";
            } else {
                $error = "Failed to send email, please try again";
            }
        } catch (Exception $e) {
            $error = "Error sending email: " . $mail->ErrorInfo;
        }
    }
}

// Get user's message history
$stmt = $conn->prepare("SELECT * FROM user_messages 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
    .message-card {
        transition: transform 0.2s;
    }
    .message-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'user_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="container mt-4">
                    <h2 class="mb-4">Send Email to Administrator</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Email Subject</label>
                                    <input type="text" class="form-control" name="subject" id="subject" required
                                           placeholder="Enter email subject...">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Email Content</label>
                                    <textarea class="form-control" name="message" id="message" rows="4" required 
                                            placeholder="Describe your issue or request..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Email
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <h3 class="mb-4">Email History</h3>
                    <?php if (empty($messages)): ?>
                        <div class="alert alert-info">No email records found</div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="card mb-3 message-card shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-2"><?php echo htmlspecialchars($message['subject']); ?></h5>
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge <?php echo $message['status'] === 'sent' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $message['status'] === 'sent' ? 'Sent' : 'Failed'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 