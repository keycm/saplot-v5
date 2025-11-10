<?php
session_start();
include 'config.php'; // DB connection

// Initialize attempts counter
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
}

// Handle resend OTP
if (isset($_POST['resend'])) {
    // Generate new OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 600; // 10 minutes

    // Get recipient email from pending_user session
    $recipientEmail = $_SESSION['pending_user']['email'] ?? null;

    if ($recipientEmail) {
        require 'send_otp.php';
        $info = "A new OTP has been sent to your email.";
    } else {
        $error = "⚠️ No email found. Please register again.";
    }
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $enteredOtp = trim($_POST['otp']);
    $storedOtp  = $_SESSION['otp'] ?? '';
    $expiry     = $_SESSION['otp_expiry'] ?? 0;

    // If OTP expired
    if (time() > $expiry) {
        $error = "OTP expired! Please resend or register again.";
        session_destroy();
    } 
    // If OTP correct
    elseif ($enteredOtp == $storedOtp) {
        if (isset($_SESSION['pending_user'])) {
            $user = $_SESSION['pending_user'];
            $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user['username'], $user['password'], $user['fullname'], $user['email']);
            
            if ($stmt->execute()) {
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expiry']);
                unset($_SESSION['pending_user']);
                unset($_SESSION['attempts']);

                echo "<script>
                    alert('✅ Verification successful! You can now login.');
                    window.location.href = 'index.php';
                </script>";
                exit;
            } else {
                $error = "Database error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $error = "No registration data found. Please register again.";
        }
    } 
    // If wrong OTP
    else {
        $_SESSION['attempts']++;
        $remaining = 3 - $_SESSION['attempts'];
        if ($remaining <= 0) {
            $error = "Too many wrong attempts! OTP expired. Please register again.";
            session_destroy();
        } else {
            $error = "Invalid OTP. You have $remaining attempt(s) left.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}
form {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    text-align: center;
    width: 320px;
}
input {
    padding: 10px;
    margin: 10px 0;
    width: 100%;
    border: 1px solid #ccc;
    border-radius: 6px;
    text-align: center;
}
button {
    padding: 10px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    width: 100%;
}
button:hover {
    background: #333;
}
.timer {
    color: #d00;
    font-weight: bold;
}
.message {
    margin: 5px 0;
}
</style>
<script>
let expiry = <?php echo isset($_SESSION['otp_expiry']) ? $_SESSION['otp_expiry'] - time() : 0; ?>;
function startCountdown() {
    const timerDisplay = document.getElementById("timer");
    if (!timerDisplay) return;
    const countdown = setInterval(() => {
        if (expiry <= 0) {
            clearInterval(countdown);
            timerDisplay.textContent = "OTP expired!";
            return;
        }
        const min = Math.floor(expiry / 60);
        const sec = expiry % 60;
        timerDisplay.textContent = `${min}:${sec < 10 ? '0' : ''}${sec}`;
        expiry--;
    }, 1000);
}
window.onload = startCountdown;
</script>
</head>
<body>
<form method="POST">
    <h2>Email Verification</h2>
    <p>We sent a 6-digit OTP to your email.</p>
    <p class="timer">Time remaining: <span id="timer"></span></p>

    <?php if (!empty($error)) echo "<p class='message' style='color:red;'>$error</p>"; ?>
    <?php if (!empty($info)) echo "<p class='message' style='color:blue;'>$info</p>"; ?>
    
    <input type="text" name="otp" placeholder="Enter OTP" maxlength="6" required>
    <button type="submit" name="verify">Verify</button>
    <button type="submit" name="resend" style="background:#555; margin-top:10px;">Resend OTP</button>
</form>
</body>
</html>
