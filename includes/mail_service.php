<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

/**
 * Send a welcome email to the user
 */
function sendWelcomeEmail($toEmail, $userName) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                // Enable verbose debug output
        $mail->isSMTP();                                         // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                // Enable SMTP authentication
        $mail->Username   = 'prasamshapokharel11@gmail.com';              // SMTP username
        $mail->Password   = 'eupa rlax pfei lvew';                 // SMTP password (use App Password for Gmail)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption
        $mail->Port       = 587;                                 // TCP port to connect to

        // Recipients
        $mail->setFrom('noreply@ticketly.com', 'Ticketly');
        $mail->addAddress($toEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Ticketly!';
        
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #2575fc; text-align: center;'>Welcome to Ticketly, {$userName}!</h2>
                <p>Thank you for creating an account with us. We're excited to have you on board.</p>
                <p>With Ticketly, you can:</p>
                <ul>
                    <li>Discover the latest movies</li>
                    <li>Book your favorite seats in advance</li>
                    <li>Enjoy a seamless movie-going experience</li>
                </ul>
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='" . BASE_URL . "/login.php' style='background: #2575fc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Log In to Your Account</a>
                </div>
                <p style='margin-top: 30px; font-size: 12px; color: #888;'>If you did not create this account, please ignore this email.</p>
            </div>
        ";

        $mail->Body    = $body;
        $mail->AltBody = "Welcome to Ticketly, {$userName}! Thank you for creating an account. Log in at: " . BASE_URL . "/login.php";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error if needed: $mail->ErrorInfo
        return false;
    }
}
