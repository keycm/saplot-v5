<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Us - Saplot de Manila</title>
  <link rel="stylesheet" href="CSS/style.css" />
  <link rel="stylesheet" href="CSS/about.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    /* START: ADDED FIX FOR HERO TEXT VISIBILITY */
    .hero-content {
        position: relative; /* This activates the z-index: 10 from style.css */
    }
    /* END: ADDED FIX */
  
    /* --- Responsive Styles --- */
    
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
        <li><a href="index.php">HOME</a></li>
        <li><a href="product.php">SHOP</a></li>
        <li><a href="about.php" class="active">ABOUT</a></li>
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

  <section class="hero" style="min-height: 12vh; padding: 0 80px; align-items: center; display: flex; justify-content: space-between;">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    
    <div class="hero-content" style="padding: 0; position: relative;">
      <h1 class="hero-heading" style="font-size: 1.8rem; margin: 0;">
        About Us
      </h1>
    </div>
    
    <div class="hero-image-container">
        <img src="assets/logo.png" alt="New Shoe Collection" class="hero-product-img" style="max-width: 180px; margin-top: 0;">
    </div>
  </section>

  <main>
    <section class="about-main-content">
        <div class="about-container">
            <div class="about-image">
                <img src="assets/sapsap.jpg" alt="About Us Image">
            </div>
            <div class="about-text">
                <h2>From Passion to Pavement</h2>
                <p>
                    Born from the vibrant streets of Manila, Saplot de Manila began with a simple mission: to create high-quality, stylish footwear that tells a story. "Saplot," a Filipino word meaning "to cover," represents our commitment to crafting shoes that do more than just protect; they empower your every step. We blend local artistry with contemporary design to create pieces that are both timeless and distinctly Filipino.
                </p>
                <p>
                    We believe that a great pair of shoes is built on a foundation of quality craftsmanship and thoughtful design. Each pair in our collection is meticulously crafted from premium materials to ensure lasting comfort and durability. We are dedicated to providing an exceptional customer experience, ensuring you feel valued and inspired.
                </p>
            </div>
        </div>
    </section>
  </main>

    <footer>
        <div class="footer-main">
            <div class="footer-left">
                <h3>Saplot<span>De Manila</span></h3>
                <p class="footer-links">
                    <a href="index.php">Home</a>
                    <a href="product.php">Pricing</a>
                    <a href="about.php">About</a>
                    <a href="contact.php">Contact</a>
                </p>
            </div>
            <div class="footer-center">
                <div><i class="fa fa-map-marker"></i><p><span>Fortuna, Floridablanca</span> Pampanga</p></div>
                <div><i class="fa fa-phone"></i><p>+639 131 019 6878</p></div>
                <div><i class="fa fa-envelope"></i><p><a href="mailto:Saplot09209@gmail.com">Saplotdemanila@gmail.com</a></p></div>
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
          <span class="close-btn">&times;</span>
          <h2>Login to Saplot</h2>
          <form method="POST" action="index.php">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <div class="options">
              <label><input type="checkbox" name="remember"> Remember me</label>
              <a href="#">Forgot Password?</a>
            </div>
            <button type="submit" name="login">Login</button>
            <p class="register">Don't you have an account? <a href="#">Register</a></p>
          </form>
        </div>
    </div>

    <div id="registerModal" class="modal">
        <div class="modal-content">
          <span class="close-btn">&times;</span>
          <h2>Register to Saplot</h2>
          <form method="POST" action="index.php">
              <input type="text" name="fullname" placeholder="Full Name" required>
              <input type="text" name="username" placeholder="Username" required>
              <input type="email" name="email" placeholder="Email" required>
              <input type="password" name="password" placeholder="Password" required>
              <input type="password" name="confirm_password" placeholder="Confirm Password" required>
              <button type="submit" name="register">Register</button>
              <p class="login">Have an account? <a href="#">Login</a></p>
          </form>
        </div>
    </div>
  
  <script>
    document.addEventListener("DOMContentLoaded", function() {
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

        // --- MODAL CONTROLS ---
        const loginModal = document.getElementById("loginModal");
        const registerModal = document.getElementById("registerModal");
        const loginBtn = document.getElementById("loginModalBtn");
        const mobileLoginBtn = document.getElementById("mobileLoginBtn"); // Mobile link
        
        const closeLoginModal = loginModal.querySelector(".close-btn");
        const closeRegisterModal = registerModal.querySelector(".close-btn");
        
        const showRegisterLink = loginModal.querySelector(".register a");
        const showLoginLink = registerModal.querySelector(".login a");

        if(loginBtn) {
            loginBtn.onclick = () => { loginModal.style.display = "block"; }
        }
        
        // Mobile login link
        if(mobileLoginBtn) {
            mobileLoginBtn.onclick = (e) => { 
                e.preventDefault();
                loginModal.style.display = "block"; 
                // Close burger menu if open
                if (navMenu.classList.contains('is-active')) {
                    navToggle.classList.remove('is-active');
                    navMenu.classList.remove('is-active');
                }
            }
        }
        
        if(closeLoginModal) {
            closeLoginModal.onclick = () => { loginModal.style.display = "none"; }
        }
        if(closeRegisterModal) {
            closeRegisterModal.onclick = () => { registerModal.style.display = "none"; }
        }
        
        if(showRegisterLink) {
            showRegisterLink.onclick = (e) => { 
                e.preventDefault(); 
                loginModal.style.display = "none"; 
                registerModal.style.display = "block"; 
            }
        }
        if(showLoginLink) {
            showLoginLink.onclick = (e) => { 
                e.preventDefault(); 
                registerModal.style.display = "none"; 
                loginModal.style.display = "block"; 
            }
        }
        
        window.addEventListener('click', (event) => {
            if (event.target == loginModal) loginModal.style.display = "none";
            if (event.target == registerModal) registerModal.style.display = "none";
        });
        
        // --- BURGER MENU SCRIPT ---
        const navToggle = document.getElementById('nav-toggle');
        const navMenu = document.getElementById('nav-menu');

        if (navToggle && navMenu) {
            navToggle.addEventListener('click', () => {
                navToggle.classList.toggle('is-active');
                navMenu.classList.toggle('is-active');
            });
        }
    });
  </script>
<script>
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
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {

  const cartCount = document.getElementById('cartCount');

  // Kung walang naka-login → i-clear ang cart
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
</body>
</html>