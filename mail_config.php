<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Mail Server Configuration
if (!defined('MAIL_HOST')) define('MAIL_HOST', 'smtp.gmail.com');
if (!defined('MAIL_USERNAME')) define('MAIL_USERNAME', 'wongjianzhi0@gmail.com');
if (!defined('MAIL_PASSWORD')) define('MAIL_PASSWORD', 'sewu eqcl bguv lywf');
if (!defined('MAIL_ENCRYPTION')) define('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
if (!defined('MAIL_PORT')) define('MAIL_PORT', 587);
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', 'jianzhiw168@gmail.com');

function sendMail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        // Sender and recipient settings
        $mail->setFrom(MAIL_USERNAME, 'Car Rental Service');
        $mail->addAddress($to);

        // Content settings
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
} 