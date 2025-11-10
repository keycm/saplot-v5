<?php
// --- DIRECT CONNECTION TO addproduct DATABASE ---
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) {
    die("<script>alert('Database connection failed: " . addslashes($conn->connect_error) . "');</script>");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'];
    $reply = $_POST['reply_message'];

    // Fetch user email
    $query = "SELECT first_name, email FROM inquiries WHERE id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("<script>
            alert('Database error: cannot prepare statement.\\nError: " . addslashes($conn->error) . "');
            window.history.back();
        </script>");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('No inquiry found.'); window.history.back();</script>";
        exit();
    }

    $row = $result->fetch_assoc();
    $email = $row['email'];
    $name = $row['first_name'];

    // --- PHPMailer ---
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'saplotdemanila@gmail.com';
        $mail->Password   = 'nblwxvrwvksbyzvl'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Sender and recipient
        $mail->setFrom('saplotdemanila@gmail.com', 'Saplot de Manila');
        $mail->addReplyTo('saplotdemanila@gmail.com', 'Saplot de Manila Team'); // ✅ This ensures replies go to this address
        $mail->addAddress($email, $name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Reply from Saplot de Manila';
        $mail->Body = "
        <div style='font-family:Poppins,Arial,sans-serif; padding:20px;'>
            <h2 style='color:#333;'>Hello, $name!</h2>
            <p style='font-size:15px; color:#444;'>Thank you for reaching out to Saplot de Manila. Below is our response to your inquiry:</p>
            <blockquote style='background:#f7f7f7; padding:15px; border-left:4px solid #007bff; margin:10px 0;'>"
                . nl2br(htmlspecialchars($reply)) .
            "</blockquote>
            <p style='margin-top:15px; font-size:15px; color:#444;'>
                We appreciate your interest and support. If you have more questions, feel free to reply to this email.
            </p>
            <p style='margin-top:25px; color:#333; font-weight:600;'>Warm regards,<br><span style='color:#007bff;'>Saplot de Manila Team</span></p>
        </div>";

        // Send the email
        $mail->send();

        // Update the database to mark as replied
        $update = $conn->prepare("UPDATE inquiries SET reply = ?, replied_at = NOW(), status = 'Replied' WHERE id = ?");
        $update->bind_param("si", $reply, $id);

        if ($update->execute()) {
            echo "<script>
                alert('✅ Reply sent successfully to $email!');
                window.location.href = 'admin_inquiries.php';
            </script>";
        } else {
            echo "<script>
                alert('⚠️ Reply sent but failed to update database.');
                window.history.back();
            </script>";
        }

    } catch (Exception $e) {
        echo "<script>
            alert('❌ Failed to send reply. Error: " . addslashes($mail->ErrorInfo) . "');
            window.history.back();
        </script>";
    }
}
?>
