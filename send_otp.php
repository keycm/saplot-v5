<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'saplotdemanila@gmail.com';  // Gmail mo
    $mail->Password   = 'nblwxvrwvksbyzvl';          // app password (no spaces!)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('saplotdemanila@gmail.com', 'Saplot de Manila'); // dapat same sa Gmail mo
    $mail->addAddress($recipientEmail);

    $mail->isHTML(true);
    $mail->Subject = 'Your Saplot OTP Code';
    $mail->Body    = "Hello! Your OTP code is: <b>$otp</b><br>It will expire in 10 minutes.";

    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
}
?>
