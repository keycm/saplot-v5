<?php
session_start();
include 'config.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$login_error = '';
$forgot_error = '';
$forgot_verify_error = '';
$reset_error = '';
$register_error = '';
$register_success = '';

// Login logic
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['lock_time'])) {
    $_SESSION['lock_time'] = 0;
}

$lockDuration = 600; // 10 minutes

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    // If locked → check.
    if ($_SESSION['login_attempts'] >= 5) {
        if (time() - $_SESSION['lock_time'] < $lockDuration) {
            $remaining = $lockDuration - (time() - $_SESSION['lock_time']);
            $minutes = ceil($remaining / 60);
            $login_error = "Too many failed attempts. Try again in $minutes minute(s).";
        } else {
            // Unlock after time
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lock_time'] = 0;
        }
    }

    if (empty($login_error)) { // Only proceed if not locked out
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic validations
        if (empty($email) || empty($password)) {
            $login_error = "Please fill in both fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/', $email)) {
            $login_error = "Only valid Gmail addresses are allowed.";
        } elseif (strlen($password) < 8) {
            $login_error = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $login_error = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $login_error = "Password must contain at least one number.";
        } else {
            // Check user
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                // EMAIL NOT FOUND
                if ($result->num_rows !== 1) {
                    $_SESSION['login_attempts']++;

                    if ($_SESSION['login_attempts'] >= 5) {
                        $_SESSION['lock_time'] = time();
                    }
                    $login_error = "Email not found!";
                } 
                else {
                    $user = $result->fetch_assoc();

                    // WRONG PASSWORD
                    if (!password_verify($password, $user['password'])) {
                        $_SESSION['login_attempts']++;

                        if ($_SESSION['login_attempts'] >= 5) {
                            $_SESSION['lock_time'] = time();
                        }
                        $login_error = "Wrong password!";
                    } 
                    // SUCCESS LOGIN
                    else {
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['lock_time'] = 0;
                        session_regenerate_id(true);

                        if (empty($user['role'])) {
                            $user['role'] = 'user';
                        }

                        $_SESSION['user_id']   = $user['id'];
                        $_SESSION['email']     = $user['email'];
                        $_SESSION['fullname']  = $user['fullname'] ?? '';
                        $_SESSION['role']      = $user['role'];

                       // Redirect by role
                       if ($_SESSION['role'] === 'admin') {
                         header("Location: Dashboard.php");
                         exit;
                       } else {
                         header("Location: index.php");
                         exit;
                       }
                    }
                }
                $stmt->close();
            } 
            else {
                $login_error = "Database query failed.";
            }
        }
    }
}


// --- SEND OTP FOR FORGOT PASSWORD ---
if (isset($_POST['send_forgot_otp'])) {
    $email = trim($_POST['forgot_email']);
    $result = $conn->query("SELECT * FROM users WHERE email='$email'");

    if ($result && $result->num_rows > 0) {
        $otp = rand(100000, 999999);
        $_SESSION['forgot_email'] = $email;
        $_SESSION['forgot_otp'] = $otp;
        $_SESSION['forgot_otp_expire'] = time() + 600; // valid for 10 mins
        $_SESSION['forgot_attempts'] = 0; // reset attempt count

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'saplotdemanila@gmail.com';
            $mail->Password = 'nblwxvrwvksbyzvl'; // Gmail app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('saplotdemanila@gmail.com', 'Saplot de Manila');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body = "Your OTP for password reset is: <b>$otp</b>.<br>This OTP is valid for 10 minutes.";

            $mail->send();

            echo "<script>
              document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('forgotModal').style.display = 'none';
                document.getElementById('verifyOtpModal').style.display = 'flex';
              });
              alert('OTP sent to your email!');
            </script>";
        } catch (Exception $e) {
            $forgot_error = "Failed to send OTP. Please try again later.";
        }
    } else {
        $forgot_error = "Email not found!";
    }
}


// --- VERIFY OTP ---
if (isset($_POST['verify_forgot_otp'])) {
    $entered_otp = trim($_POST['forgot_otp']);
    $forgot_verify_error = '';

    // Check if OTP expired
    if (!isset($_SESSION['forgot_otp']) || time() > $_SESSION['forgot_otp_expire']) {
        $forgot_verify_error = "OTP expired. Please request a new one.";
        unset($_SESSION['forgot_otp'], $_SESSION['forgot_otp_expire']);
        echo "<script>
          alert('OTP expired. Please request a new one.');
          document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('verifyOtpModal').style.display = 'none';
            document.getElementById('forgotModal').style.display = 'flex';
          });
        </script>";
    } else {
        $_SESSION['forgot_attempts'] = $_SESSION['forgot_attempts'] ?? 0;

        // Too many attempts
        if ($_SESSION['forgot_attempts'] >= 3) {
            unset($_SESSION['forgot_otp'], $_SESSION['forgot_otp_expire'], $_SESSION['forgot_attempts']);
            echo "<script>
              alert('You have exceeded 3 attempts. Please enter your email again on the Forgot Password page to request a new OTP.');
              document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('verifyOtpModal').style.display = 'none';
                document.getElementById('forgotModal').style.display = 'flex';
              });
            </script>";
        } elseif ($entered_otp != $_SESSION['forgot_otp']) {
            $_SESSION['forgot_attempts']++;
            $remaining = 3 - $_SESSION['forgot_attempts'];
            $forgot_verify_error = "Invalid OTP. You have $remaining attempt(s) left.";

            echo "<script>
              document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('verifyOtpModal').style.display = 'flex';
              });
            </script>";
        } else {
            // OTP verified successfully
            echo "<script>
              alert('OTP verified successfully!');
              document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('verifyOtpModal').style.display = 'none';
                document.getElementById('resetPassModal').style.display = 'flex';
              });
            </script>";
        }
    }
}


// --- RESET PASSWORD ---
if (isset($_POST['reset_password'])) {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = $_SESSION['forgot_email'] ?? '';

    if (empty($email)) {
        $reset_error = "Session expired. Please start again.";
    } elseif ($new_password !== $confirm_password) {
        $reset_error = "Passwords do not match!";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE email='$email'");
        unset($_SESSION['forgot_email'], $_SESSION['forgot_otp'], $_SESSION['forgot_otp_expire'], $_SESSION['forgot_attempts']);
        echo "<script>alert('Password changed successfully! You can now login.'); window.location='index.php';</script>";
        exit;
    }
}

// Registration logic
if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);

    if ($password !== $confirm) {
        $register_error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $_SESSION['pending_user'] = [
            'fullname' => $fullname,
            'username' => $username,
            'email' => $email,
            'password' => $hashed
        ];

        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 600; // 10 mins expiry
        $_SESSION['otp_attempts'] = 0; // reset attempts

        // Send OTP
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'saplotdemanila@gmail.com';
            $mail->Password   = 'nblwxvrwvksbyzvl'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('saplotdemanila@gmail.com', 'Saplot de Manila');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Saplot OTP Code';
            $mail->Body    = "Hello <b>$fullname</b>!<br>Your OTP code is: <b>$otp</b><br>This code will expire in 10 minutes.";

            $mail->send();
            $register_success = "OTP sent to your email. Please verify to complete registration.";
        } catch (Exception $e) {
            $register_error = "Failed to send OTP. Please try again later.";
        }
    }
}

/* -------------------- VERIFY OTP -------------------- */
if (isset($_POST['verify_otp'])) {
    $enteredOtp = trim($_POST['otp']);
    $storedOtp = $_SESSION['otp'] ?? '';
    $expiry = $_SESSION['otp_expiry'] ?? 0;

    // Initialize OTP attempts
    if (!isset($_SESSION['otp_attempts'])) {
        $_SESSION['otp_attempts'] = 0;
    }

    // --- OTP Expired ---
    if (time() > $expiry) {
        $register_error = "OTP expired! Please register again.";
        session_destroy();
        echo "<script>
            alert('OTP expired! Please register again.');
            window.location.href = 'homepage.php';
        </script>";
        exit;
    }

    // --- OTP Correct ---
    elseif ($enteredOtp == $storedOtp) {
        if (isset($_SESSION['pending_user'])) {
            $user = $_SESSION['pending_user'];
            $default_role = 'user';
            $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $user['fullname'], $user['username'], $user['email'], $user['password'], $default_role);
            if ($stmt->execute()) {
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expiry']);
                unset($_SESSION['pending_user']);
                $_SESSION['otp_attempts'] = 0;

              echo "<script>
              alert('Verification successful! You can now login.');
              window.location.href = 'homepage.php?openLogin=1';
              </script>";
            exit;
            } else {
                $register_error = "Database error: " . $stmt->error;
            }
        } else {
            $register_error = "No registration data found. Please register again.";
        }
    }

    // --- Wrong OTP ---
    else {
        $_SESSION['otp_attempts']++;

        if ($_SESSION['otp_attempts'] >= 3) {
            $register_error = "3 invalid attempts! Please register again for security.";
            session_destroy();
            echo "<script>
                alert('You entered the wrong OTP 3 times. Please register again.');
                window.location.href = 'homepage.php';
            </script>";
            exit;
        } else {
            $remaining = 3 - $_SESSION['otp_attempts'];
            $register_error = "Invalid OTP. You have $remaining attempt(s) left.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Saplot de Manila</title>
  <link rel="stylesheet" href="CSS/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  
  <style>
    /* --- Responsive Styles --- */

    /* START: ADDED FIXES FOR HERO STACKING */
    .hero-content {
        position: relative; /* Activates z-index: 10, bringing text above shapes */
    }
    .hero-image-container {
    position: relative;
    z-index: 1;         /* Bawasan */
    width: 100%;
    display: flex;
    justify-content: center;
}

    /* END: ADDED FIXES */
    
    /* --- Navbar & Burger Menu --- */
    .nav-toggle {
        display: none; /* Hidden on desktop */
        background: none;
        border: none;
        cursor: pointer;
        padding: 10px;
        z-index: 1001; /* Above nav menu */
    }
    .nav-toggle .burger-bar {
        display: block;
        width: 25px;
        height: 3px;
        background-color: #333;
        border-radius: 3px;
        transition: all 0.3s ease;
    }
    .nav-toggle .burger-bar + .burger-bar {
        margin-top: 5px;
    }

    /* START: HIDE MOBILE AUTH ON DESKTOP */
    .mobile-auth {
        display: none;
    }
    /* END: HIDE MOBILE AUTH ON DESKTOP */


    @media (max-width: 992px) {
      .navbar {
        padding: 15px 20px;
        justify-content: space-between;
      }

      /* START: ADDED LOGO SIZING FOR TABLET */
      .brand-name {
          font-size: 22px; 
      }
      .logo img {
          height: 28px;
      }
      /* END: ADDED LOGO SIZING FOR TABLET */

      .nav-toggle {
          display: block; /* Show burger button */
          order: 3; /* Place it after nav-icons */
      }
      .nav-icons {
          order: 2; /* Keep icons before burger */
          margin-left: auto; /* Push icons to the right, but before burger */
      }
      .logo {
          order: 1; /* Logo first */
      }
      
      /* Hide user controls on mobile, they should be in the menu */
      .login-btn, .profile-dropdown {
          display: none; 
      }

      .nav-menu {
        display: none; /* Hide menu by default */
        position: absolute;
        top: 100%; /* Position right below the navbar */
        left: 0;
        width: 100%;
        background-color: #fff;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        flex-direction: column;
        padding: 10px 0;
      }
      
      .nav-menu.is-active {
          display: flex; /* Show menu when active */
      }
      
      .nav-links {
        flex-direction: column;
        width: 100%;
        margin: 0;
      }
      .nav-links li {
        text-align: center;
        width: 100%;
      }
      .nav-links a {
        display: block;
        padding: 15px 0;
        width: 100%;
      }
      .nav-links a:hover {
          background-color: #f8f9fa;
      }

      /* START: ADDED CSS FOR MOBILE AUTH LINKS */
      .mobile-auth {
          display: block; 
          width: 100%;
          text-align: center;
      }
      .mobile-auth a {
          display: block;
          padding: 15px 0;
          width: 100%;
          color: #E03A3E;
          font-weight: 600;
      }
      .mobile-auth a:hover {
          background-color: #f8f9fa;
      }
      .mobile-auth i {
          margin-right: 8px;
      }
      /* END: ADDED CSS FOR MOBILE AUTH LINKS */


      /* Burger 'X' animation */
      .nav-toggle.is-active .burger-bar:nth-child(1) {
          transform: translateY(8px) rotate(45deg);
      }
      .nav-toggle.is-active .burger-bar:nth-child(2) {
          opacity: 0;
      }
      .nav-toggle.is-active .burger-bar:nth-child(3) {
          transform: translateY(-8px) rotate(-45deg);
      }
    }

    @media (max-width: 768px) {
      .navbar {
        padding: 10px 15px;
      }
      /* Adjust order for smaller screens if needed */
      .nav-toggle {
          order: 2; /* Burger before icons */
      }
      .nav-icons {
          order: 3; /* Icons last */
          margin-left: 0;
      }
      .brand-name {
          font-size: 20px; /* Smaller brand name */
      }
      .logo img {
          height: 25px; /* Smaller logo */
      }
      .icon-btn {
          width: 35px;
          height: 35px;
      }
      .nav-icons img {
          width: 18px;
          height: 18px;
      }
    }

    /* --- Hero Section --- */
    @media (max-width: 1024px) {
      .hero {
        flex-direction: column;
        padding: 40px 20px;
        text-align: center;
        min-height: auto; /* Allow height to adjust */
      }
      .hero-content {
        max-width: 100%;
        padding: 0;
        order: 2; /* Content below image */
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      .hero-heading {
        font-size: 48px;
      }
      .hero-subtext {
        max-width: 100%;
      }
      .hero-image-container {
        order: 1; /* Image on top */
        padding-right: 0;
        margin-bottom: 30px;
        width: 100%;
      }
      .hero-product-img {
        margin-top: -40px;
        max-width: 450px;
      }
      .shape {
        transform: skew(-10deg);
      }
      .shape-1 { right: 50%; width: 30%; }
      .shape-2 { right: 20%; width: 30%; }
      .shape-3 { right: -10%; width: 30%; }
    }
    
    @media (max-width: 768px) {
       .hero-heading {
          font-size: 36px; /* Even smaller for phones */
       }
       .hero-product-img {
          max-width: 300px;
          margin-top: 0;
       }
    }

    /* --- Features Section --- */
    @media (max-width: 768px) {
      .features-container {
        flex-direction: column;
        gap: 20px;
        padding: 20px;
      }
      .feature-item {
        min-width: 100%;
      }
    }
    
    /* --- What's New Section --- */
    @media (max-width: 768px) {
      .whats-new {
        grid-template-columns: 1fr; /* Stack category cards */
      }
    }
    
    /* --- New Arrivals Grid --- */
    @media (max-width: 992px) {
      .product-grid-container {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        padding: 0 10px;
      }
    }
    
    @media (max-width: 576px) {
      .product-grid-container {
        grid-template-columns: repeat(2, 1fr); /* 2 columns on small phones */
        gap: 15px;
      }
      .product-card {
          flex: 0 0 auto; /* Let grid handle sizing */
      }
      .product-name {
          font-size: 0.9rem;
      }
      .product-price {
          font-size: 1rem;
      }
    }

    /* --- About Section --- */
    @media (max-width: 768px) {
      .about-container {
        flex-direction: column;
        gap: 30px;
      }
      .about-content {
          text-align: center;
      }
      .about-content .section-title {
          text-align: center;
      }
    }
    
    /* --- Footer --- */
    @media (max-width: 880px) {
      .footer-main {
          padding: 40px 20px;
      }
      .footer-main .footer-left,
      .footer-main .footer-center,
      .footer-main .footer-right {
        width: 100%;
        margin-bottom: 30px;
        text-align: center;
      }
      .footer-main .footer-center i {
        margin-left: 0;
      }
    }
  </style>
  </head>
<body>
  <header class="navbar">
    <div class="logo">
      <img src="assets/Media (2) 1.png">
      <span class="brand-name">SAPLOT de MANILA</span>
    </div>

    <nav class="nav-menu" id="nav-menu">
      <ul class="nav-links">
        <li><a href="index.php" class="active">HOME</a></li>
        <li><a href="product.php">SHOP</a></li>
        <li><a href="about.php">ABOUT</a></li>
        <li><a href="contact.php">CONTACT</a></li>
        
        <li class="mobile-auth"> 
          <?php if (isset($_SESSION['user_id']) && isset($_SESSION['fullname'])): ?>
            <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Log Out (<?php echo htmlspecialchars($_SESSION['fullname']); ?>)</a>
          <?php else: ?>
            <a href="#" id="mobileLoginBtn"><i class="fa fa-sign-in-alt"></i> Log In / Sign Up</a>
          <?php endif; ?>
        </li>
        </ul>
    </nav>
    
    <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation">
        <span class="burger-bar"></span>
        <span class="burger-bar"></span>
        <span class="burger-bar"></span>
    </button>
    <div class="nav-icons">
        <a href="#" class="icon-btn" id="search-icon">
    <img src="assets/search (1) 1.png" alt="Search">
  </a>
  
  <a href="cart.php" class="icon-btn cart-wrapper">
    <img src="assets/shopping-cart 1.png" alt="Cart" id="cartBtn">
    <span class="cart-count" id="cartCount">0</span>
  </a>
</div>

<!--Search Overlay -->
<div id="search-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:999;">
  <div style="background:#fff; padding:20px 30px; border-radius:10px; display:flex; align-items:center; gap:10px;">
    <form method="GET" action="product.php" style="display:flex; gap:10px; align-items:center;">
      <input type="text" name="search" id="search-input" placeholder="Search product..." 
             style="padding:10px; width:250px; border:1px solid #ccc; border-radius:5px; color:black; background:white;">
      <button type="submit" style="background:#007bff; color:#fff; border:none; padding:8px 12px; border-radius:5px; cursor:pointer;">
        Search
      </button>
    </form>
    <button id="close-search" style="background:#dc3545; color:#fff; border:none; padding:8px 12px; border-radius:5px; cursor:pointer;">✕</button>
  </div>
</div>


      <?php if (isset($_SESSION['user_id']) && isset($_SESSION['fullname'])): ?>
        <div class="profile-dropdown">
            <div class="profile-info">
                <i class="fa fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                <i class="fa fa-caret-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
      <?php else: ?>
        <button id="loginModalBtn" class="login-btn">Log In / Sign Up</button>
      <?php endif; ?>
    </div>
  </header>

  <section class="hero">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    
    <div class="hero-content">
      <h1 class="hero-heading">
        Saplot New<br>
        Collection!
      </h1>
      <p class="hero-subtext">
        Discover our latest arrivals. Fresh styles, premium quality, and the iconic comfort you love. Shop the new collection today and define your look.
      </p>
      <a href="product.php" class="buy-now-btn">BUY NOW</a>
    </div>
    
    <div class="hero-image-container">
        <img src="assets/logo.png" alt="New Shoe Collection" class="hero-product-img">
    </div>
  </section>

  <section class="features-section">
    <div class="features-container">
      <div class="feature-item">
        <i class="fa-solid fa-truck-fast"></i>
        <h4>Affordable Shipping</h4>
        <p>Budget-friendly delivery.</p>
      </div>
      <div class="feature-item">
        <i class="fa-solid fa-arrows-rotate"></i>
        <h4>Return Policy</h4>
        <p>Easy and hassle-free returns.</p>
      </div>
      <div class="feature-item">
        <i class="fa-solid fa-headset"></i>
        <h4>24/7 Support</h4>
        <p>Always here to help.</p>
      </div>
      <div class="feature-item">
        <i class="fa-solid fa-shield-halved"></i>
        <h4>Secure Payment</h4>
        <p>Safe and protected transactions.</p>
      </div>
    </div>
  </section>


  <div class="new" id="arrivals">

    <section class="whats-new" >
        <a href="product.php?category=running" class="category-card">
            <img src="assets/running.png" />
            <span class="category-label">RUNNING</span>
        </a>
        <a href="product.php?category=basketball" class="category-card">
            <img src="assets/Basketball.png" />
            <span class="category-label">BASKETBALL</span>
        </a>
    </section>

    <section class="new-arrivals" >
      <h2>New Arrivals</h2>
      <div class="product-grid-container">
        <?php
          $products = [
              ['image' => 'assets/Gtcut academy black.png', 'name' => 'Gt cut academy', 'price' => '2,100'],
              ['image' => 'assets/Immortality 3 red.png', 'name' => 'Immortality 3', 'price' => '2,000'],
              ['image' => 'assets/Precision 7 pink.png', 'name' => 'Precision 7', 'price' => '1,800'],
              ['image' => 'assets/Precision 6 black.png', 'name' => 'Precision 6', 'price' => '1,500'],
              ['image' => 'assets/Luka 77 white.png', 'name' => 'Luka 77', 'price' => '2,000'],
              ['image' => 'assets/Nike Hyperset white.png', 'name' => 'Nike Hypersert', 'price' => '1,800'],
              ['image' => 'assets/Kobe 6 white.png', 'name' => 'Kobe 6', 'price' => '2,900'],
              ['image' => 'assets/Nike ZoomX.png', 'name' => 'Nike ZoomX', 'price' => '1,500']
          ];

          $card_action = 'class="product-card" onclick="goToProduct()"';

          foreach ($products as $product) {
              echo '
              <article ' . $card_action . ' data-name="' . strtolower($product['name']) . '">
                  <div class="product-image-container">
                      <img src="' . $product['image'] . '" loading="lazy"/>
                      <div class="product-overlay"><button class="shop-button">Shop Now</button></div>
                  </div>
                  <div class="product-info">
                      <h4 class="product-name">' . $product['name'] . '</h4>
                      <p class="product-price">₱' . $product['price'] . '</p>
                  </div>
              </article>
              ';
          }
        ?>
      </div>
      <div class="view-all-container">
          <a href="product.php" class="view-all-btn">View All Products</a>
      </div>
    </section>
  </div>


  <section class="about-section" id="about">
    <div class="about-container">
        <div class="about-image">
            <img src="assets/sapsap.jpg" alt="About Us Image">
        </div>
        <div class="about-content">
            <h2 class="section-title">About Saplot de Manila</h2>
            <p>Welcome to Saplot De Manila, your go-to destination for exquisite footwear. With a passion for quality craftsmanship and timeless style, we pride ourselves on curating a collection that blends modern trends with classic elegance.</p>
            <p>Our journey began with a simple idea: to provide shoe lovers with high-quality, comfortable, and stylish footwear that doesn't break the bank. Every pair in our collection is handpicked to ensure it meets our high standards of excellence. We believe that the right pair of shoes can transform your look and boost your confidence.</p>
            <a href="product.php" class="about-button">Discover Our Collection</a>
        </div>
    </div>
  </section>

<footer>
    <div class="footer-main">
        <div class="footer-left">
            <h3>Saplot<span>De Manila</span></h3>
            <p class="footer-links">
                <a href="homepage.php" class="link-1">Home</a>
                <a href="product.php">Pricing</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
            </p>
        </div>
        <div class="footer-center">
            <div>
                <i class="fa fa-map-marker"></i>
                <p><span>Fortuna, Floridablanca</span> Pampanga</p>
            </div>
            <div>
                <i class="fa fa-phone"></i>
                <p>+09999999999</p>
            </div>
            <div>
                <i class="fa fa-envelope"></i>
                <p><a href="mailto:support@company.com">Saplotdemanila@gmail.com</a></p>
            </div>
        </div>
        <div class="footer-right">
            <p class="footer-company-about">
                <span>About the company</span>
                Welcome to Saplot De Manila, your go to destination for exquisite footwear. With a passion for quality and timeless style, we pride ourselves with this.
            </p>
            <div class="footer-icons">
                <a href="https://www.facebook.com/share/1FmsacvVRP/"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-copyright">
        <p>Copyright ©2025 All rights reserved</p>
    </div>
</footer>

  <div id="loginModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="closeLoginModal">&times;</span>
      <h2>Login to Saplot</h2>
      <?php if ($login_error): ?><p style="color:red;"><?php echo $login_error; ?></p><?php endif; ?>
      <form id="loginFormModal" method="POST" action="index.php">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <div class="options">
          <label><input type="checkbox" name="remember"> Remember me</label>
        <a href="#" id="showForgotPasswordModal">Forgot Password?</a>
        </div>
        <button type="submit" name="login">Login</button>
        <p class="register">Don't you have an account? <a href="#" id="showRegisterModal">Register</a></p>
      </form>
    </div>
  </div>
 <div id="forgotModal" class="modal" style="display:none;">
  <div class="modal-content" style="
      max-width:380px;
      margin:auto;
      padding:25px;
      border-radius:10px;
      background:#fff;
      box-shadow:0 4px 12px rgba(0,0,0,0.15);
      text-align:center;
      position:relative;">
      
    <span class="close-btn" id="closeForgotModal" style="
        position:absolute;
        top:10px;
        right:15px;
        font-size:20px;
        cursor:pointer;
        color:#555;">&times;</span>

    <h2 style="margin-bottom:8px;">Forgot Password</h2>
    <p style="color:#555; margin-bottom:15px;">Enter your registered email to reset your password.</p>

    <?php if (!empty($forgot_error)): ?>
      <div style="
          background: <?= (strpos($forgot_error, 'not found') !== false || strpos($forgot_error, 'failed') !== false) ? '#f8d7da' : '#d1e7dd' ?>;
          color: <?= (strpos($forgot_error, 'not found') !== false || strpos($forgot_error, 'failed') !== false) ? '#842029' : '#0f5132' ?>;
          padding:10px; border-radius:6px; margin-bottom:15px;
          font-size:14px; font-weight:500;">
        <?php echo $forgot_error; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <input type="email" name="forgot_email" placeholder="Enter your email" required
        style="width:80%; padding:10px; border:1px solid #ccc; border-radius:5px; margin-bottom:12px; text-align:center; font-size:16px;">
      <br>
      <button type="submit" name="send_forgot_otp"
        style="background:#000; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer; width:85%; font-size:15px; transition:0.3s;">
        Send OTP
      </button>
    </form>
  </div>
</div>

<div id="verifyOtpModal" class="modal" style="display:none;">
  <div class="modal-content" style="
      max-width:380px;
      margin:auto;
      padding:25px;
      border-radius:10px;
      background:#fff;
      box-shadow:0 4px 12px rgba(0,0,0,0.15);
      text-align:center;
      position:relative;">
      
    <span class="close-btn" id="closeVerifyOtpModal" style="
        position:absolute;
        top:10px;
        right:15px;
        font-size:20px;
        cursor:pointer;
        color:#555;">&times;</span>

    <h2 style="margin-bottom:8px;">Verify OTP</h2>
    <p style="color:#555; margin-bottom:15px;">We sent a 6-digit OTP to your email.</p>

    <?php if (!empty($forgot_verify_error)): ?>
      <div style="
          background: <?= (strpos($forgot_verify_error, 'Invalid') !== false || strpos($forgot_verify_error, 'expired') !== false) ? '#f8d7da' : '#d1e7dd' ?>;
          color: <?= (strpos($forgot_verify_error, 'Invalid') !== false || strpos($forgot_verify_error, 'expired') !== false) ? '#842029' : '#0f5132' ?>;
          padding:10px; border-radius:6px; margin-bottom:15px;
          font-size:14px; font-weight:500;">
        <?php echo $forgot_verify_error; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="forgot_otp" placeholder="Enter OTP" maxlength="6" required
        style="width:80%; padding:10px; border:1px solid #ccc; border-radius:5px; margin-bottom:12px; text-align:center; font-size:16px;">
      <br>
      <button type="submit" name="verify_forgot_otp"
        style="background:#000; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer; width:85%; font-size:15px; transition:0.3s;">
        Verify OTP
      </button>
    </form>
  </div>
</div>

<div id="resetPassModal" class="modal" style="display:none;">
  <div class="modal-content" style="
      max-width:380px;
      margin:auto;
      padding:25px;
      border-radius:10px;
      background:#fff;
      box-shadow:0 4px 12px rgba(0,0,0,0.15);
      text-align:center;
      position:relative;">
      
    <span class="close-btn" id="closeResetPassModal" style="
        position:absolute;
        top:10px;
        right:15px;
        font-size:20px;
        cursor:pointer;
        color:#555;">&times;</span>

    <h2 style="margin-bottom:8px;">Reset Password</h2>
    <p style="color:#555; margin-bottom:15px;">Enter your new password below.</p>

    <?php if (!empty($reset_error)): ?>
      <div style="
          background: <?= (strpos($reset_error, 'match') !== false || strpos($reset_error, 'failed') !== false) ? '#f8d7da' : '#d1e7dd' ?>;
          color: <?= (strpos($reset_error, 'match') !== false || strpos($reset_error, 'failed') !== false) ? '#842029' : '#0f5132' ?>;
          padding:10px; border-radius:6px; margin-bottom:15px;
          font-size:14px; font-weight:500;">
        <?php echo $reset_error; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <input type="password" name="new_password" placeholder="New Password" required
        style="width:80%; padding:10px; border:1px solid #ccc; border-radius:5px; margin-bottom:12px; text-align:center; font-size:16px;">
      <input type="password" name="confirm_password" placeholder="Confirm Password" required
        style="width:80%; padding:10px; border:1px solid #ccc; border-radius:5px; margin-bottom:15px; text-align:center; font-size:16px;">
      <br>
      <button type="submit" name="reset_password"
        style="background:#000; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer; width:85%; font-size:15px; transition:0.3s;">
        Update Password
      </button>
    </form>
  </div>
</div>

 <div id="registerModal" class="modal">  
    <div class="modal-content">
        <span class="close-btn" id="closeRegisterModal">&times;</span>
        <h2>Register to Saplot</h2>

        <?php if ($register_error): ?>
            <p style="color:red;"><?php echo $register_error; ?></p>
        <?php endif; ?>

        <?php if ($register_success): ?>
            <p style="color:green;"><?php echo $register_success; ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php">
            
            <input
                type="text"
                name="fullname"
                placeholder="Full Name"
                required
                minlength="4"
                pattern="[A-Za-z\s]+"
                title="Full name must contain letters and spaces only"
            >

            <input
                type="text"
                name="username"
                placeholder="Username"
                required
                minlength="4"
                pattern="[A-Za-z\s]+"
                title="Username must contain letters and spaces only"
            >

            <input
                type="email"
                name="email"
                placeholder="Email"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Password"
                required
                minlength="8"
                pattern="^(?=.*[A-Z]).{8,}$"
                title="Password must be at least 8 characters and contain at least 1 capital letter"
            >

            <input
                type="password"
                name="confirm_password"
                placeholder="Confirm Password"
                required
                minlength="8"
            >

            <button type="submit" name="register">Register</button>
            <p class="login">Have an account? <a href="#" id="showLoginModal">Login</a></p>
        </form>
    </div>
</div>



  <div id="search-overlay" class="search-overlay">
    <span class="close-search-btn" id="close-search">&times;</span>
    <div class="search-overlay-content">
        <input type="search" id="search-input" placeholder="Search for products..." autocomplete="off">
    </div>
  </div>

<script>
    function goToProduct() {
      window.location.href = "product.php";
    }

    document.addEventListener("DOMContentLoaded", function() {
        const loginModal = document.getElementById("loginModal");
        const registerModal = document.getElementById("registerModal");
        const loginBtn = document.getElementById("loginModalBtn");
        const closeLoginModal = document.getElementById("closeLoginModal");
        const closeRegisterModal = document.getElementById("closeRegisterModal");
        const showRegisterModal = document.getElementById("showRegisterModal");
        const showLoginModal = document.getElementById("showLoginModal");
        const mobileLoginBtn = document.getElementById("mobileLoginBtn"); // <-- ADDED

        // --- FIXED: Check for login action from URL to auto-open modal ---
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'login') {
            if (loginModal) {
                loginModal.style.display = "block";
            }
        }

        // --- START: BURGER MENU SCRIPT ---
        const navToggle = document.getElementById('nav-toggle');
        const navMenu = document.getElementById('nav-menu');

        // --- MODAL CONTROLS ---
        if(loginBtn) loginBtn.onclick = () => { loginModal.style.display = "block"; }
        
        // START: ADDED LISTENER FOR MOBILE LOGIN BUTTON
        if(mobileLoginBtn) mobileLoginBtn.onclick = (e) => { 
            e.preventDefault();
            loginModal.style.display = "block"; 
            
            // Close the nav menu if it's open
            if (navMenu && navToggle && navMenu.classList.contains('is-active')) {
                navToggle.classList.remove('is-active');
                navMenu.classList.remove('is-active');
            }
        }
        // END: ADDED LISTENER

        if(closeLoginModal) closeLoginModal.onclick = () => { loginModal.style.display = "none"; }
        if(closeRegisterModal) closeRegisterModal.onclick = () => { registerModal.style.display = "none"; }
        
        window.onclick = (event) => {
            if (event.target == loginModal) loginModal.style.display = "none";
            if (event.target == registerModal) registerModal.style.display = "none";
        }

        if(showRegisterModal) showRegisterModal.onclick = (e) => { e.preventDefault(); loginModal.style.display = "none"; registerModal.style.display = "block"; }
        if(showLoginModal) showLoginModal.onclick = (e) => { e.preventDefault(); registerModal.style.display = "none"; loginModal.style.display = "block"; }

        if (navToggle && navMenu) {
            navToggle.addEventListener('click', () => {
                // Toggle the 'is-active' class on both the button and the menu
                navToggle.classList.toggle('is-active');
                navMenu.classList.toggle('is-active');
            });
        }
        // --- END: BURGER MENU SCRIPT ---

      // --- SEARCH ---
const searchIcon = document.getElementById('search-icon');
const searchOverlay = document.getElementById('search-overlay');
const closeSearchBtn = document.getElementById('close-search');
const searchInput = document.getElementById('search-input');

// Open overlay
searchIcon.addEventListener('click', (e) => {
  e.preventDefault();
  searchOverlay.style.display = 'flex';
  searchInput.focus();
});

// Close overlay
closeSearchBtn.addEventListener('click', () => {
  searchOverlay.style.display = 'none';
  searchInput.value = '';
});
        
        // Also close the second search overlay if it exists
        const closeSearchBtn2 = document.getElementById('close-search');
        if (closeSearchBtn2) {
            closeSearchBtn2.addEventListener('click', () => {
              searchOverlay.style.display = 'none';
              searchInput.value = '';
            });
        }
        
        // --- PROFILE DROPDOWN ---
        const profileDropdown = document.querySelector('.profile-dropdown');
        if (profileDropdown) {
            profileDropdown.addEventListener('click', function(event) {
                event.stopPropagation();
                this.classList.toggle('active');
            });
            window.addEventListener('click', function() {
                if(profileDropdown.classList.contains('active')) {
                    profileDropdown.classList.remove('active');
                }
            });
        }
    });
</script>

<<script>
const forgotLink = document.querySelector('.options a');
const forgotModal = document.getElementById('forgotModal');
const verifyOtpModal = document.getElementById('verifyOtpModal');
const resetPassModal = document.getElementById('resetPassModal');
const closeForgotModal = document.getElementById('closeForgotModal');
const closeVerifyOtpModal = document.getElementById('closeVerifyOtpModal');
const closeResetPassModal = document.getElementById('closeResetPassModal');

// Open Forgot Password Modal from Login
if (forgotLink) {
    forgotLink.addEventListener('click', (e) => {
      e.preventDefault();
      document.getElementById('loginModal').style.display = 'none';
      forgotModal.style.display = 'flex';
    });
}


//  Close Forgot Password Modal
if (closeForgotModal) {
    closeForgotModal.addEventListener('click', () => {
      forgotModal.style.display = 'none';
    });
}


//  Close Verify OTP Modal
if (closeVerifyOtpModal) {
  closeVerifyOtpModal.addEventListener('click', () => {
    verifyOtpModal.style.display = 'none';
  });
}

//  Close Reset Password Modal
if (closeResetPassModal) {
  closeResetPassModal.addEventListener('click', () => {
    resetPassModal.style.display = 'none';
  });
}

//  Optional: close when clicking outside any modal
window.addEventListener('click', (e) => {
  const modals = [forgotModal, verifyOtpModal, resetPassModal];
  modals.forEach(modal => {
    if (modal && e.target === modal) {
      modal.style.display = 'none';
    }
  });
});
</script>


<div id="otpModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:380px; margin:auto; padding:25px; border-radius:10px; background:#fff; box-shadow:0 4px 12px rgba(0,0,0,0.15); text-align:center;">
    <h2 style="margin-bottom:8px;">Email Verification</h2>
    <p style="color:#555; margin-bottom:15px;">We sent a 6-digit OTP to your email.</p>

    <?php if (!empty($register_error) && isset($_POST['verify_otp'])): ?>
      <div id="otpAlert" style="
          background: <?= (strpos($register_error, 'Invalid') !== false || strpos($register_error, 'expired') !== false) ? '#f8d7da' : '#d1e7dd' ?>;
          color: <?= (strpos($register_error, 'Invalid') !== false || strpos($register_error, 'expired') !== false) ? '#842029' : '#0f5132' ?>;
          padding:10px; border-radius:6px; margin-bottom:15px;
          font-size:14px; font-weight:500;">
        <?php echo $register_error; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="text" name="otp" placeholder="Enter OTP" maxlength="6" required
        style="width:80%; padding:10px; border:1px solid #ccc; border-radius:5px; margin-bottom:12px; text-align:center; font-size:16px;">
      <br>
      <button type="submit" name="verify_otp"
        style="background:#000; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer; width:85%; font-size:15px; transition:0.3s;">
        Verify OTP
      </button>
    </form>
  </div>
</div>

<script>
window.onload = function() {
  <?php if (!empty($register_success) && isset($_SESSION['otp'])): ?>
      document.getElementById('otpModal').style.display = 'block';
  <?php endif; ?>

  <?php if (!empty($register_error) && isset($_POST['verify_otp'])): ?>
      document.getElementById('otpModal').style.display = 'block';
  <?php endif; ?>
};
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {

  const cartCount = document.getElementById('cartCount');

  //Kung walang naka-login → i-clear ang cart
  <?php if (!isset($_SESSION['user_id'])): ?>
    localStorage.removeItem('cart');
    localStorage.removeItem('activeUser');
  <?php endif; ?>

  //Function para i-update ang cart badge
  function updateCartCount() {
    const cartItems = JSON.parse(localStorage.getItem('cart')) || [];
    const count = cartItems.reduce((total, item) => total + (item.quantity || 1), 0);
    if (cartCount) {
      cartCount.textContent = count > 0 ? count : 0;
    }
  }

  // Tumakbo agad once DOM loaded
  updateCartCount();
});
</script>
<?php if (!empty($login_error) || !empty($register_error)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {

        const loginModal = document.getElementById("loginModal");
        const registerModal = document.getElementById("registerModal");

        <?php if (!empty($login_error)): ?>
            if (loginModal) {
                loginModal.style.display = "block";
            }
        <?php endif; ?>

        <?php if (!empty($register_error)): ?>
            if (registerModal) {
                registerModal.style.display = "block";
            }
        <?php endif; ?>

    });
</script>
<?php endif; ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);

    // If ang URL nag contain  ?openLogin=1 - show modal
    if (params.get("openLogin") === "1") {
        const loginModal = document.getElementById("loginModal");
        if (loginModal) {
            loginModal.style.display = "block";
        }
    }
});
</script>
</body>
</html>