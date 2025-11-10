<?php
session_start();
$conn = new mysqli("localhost", "root", "", "addproduct");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search keyword
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Get category (default all)
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : 'all';

// Base query
$sql = "SELECT * FROM products WHERE stock > 0";

// Filter by category if not 'all'
if ($category !== 'all') {
    $sql .= " AND category = '$category'";
}

//  Filter by search (using `name` column)
if (!empty($search)) {
    $sql .= " AND name LIKE '%$search%'";
}

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Saplot de Manila - Products</title>
  <link rel="stylesheet" href="CSS/style.css">
  <link rel="stylesheet" href="CSS/product.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  
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

    /* --- START: PRODUCT PAGE RESPONSIVE IMPROVEMENTS --- */

    /* Filter Toggle Button (Mobile Only) */
    .filter-toggle-btn {
        display: none; /* Hidden on desktop */
        background: #E03A3E;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        margin-bottom: 20px;
    }
    .filter-toggle-btn i {
        margin-right: 8px;
    }

    /* Sidebar Container */
    .sidebar-container {
        width: 250px;
        flex-shrink: 0;
        position: sticky;
        top: 100px; /* Adjust based on navbar height */
        height: calc(100vh - 120px);
        overflow-y: auto;
    }

    /* Sidebar Close Button (Mobile Only) */
    .sidebar-close-btn {
        display: none; /* Hidden on desktop */
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.5rem;
        color: #333;
        background: none;
        border: none;
        cursor: pointer;
    }
    
    #filter-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1999;
    }

    @media (max-width: 992px) {
        .filter-toggle-btn {
            display: inline-block; /* Show button */
        }

        .sidebar-container {
            position: fixed;
            left: -100%; /* Hide off-screen */
            top: 0;
            width: 300px;
            height: 100vh;
            background: #fff;
            z-index: 2000;
            transition: left 0.3s ease-in-out;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            box-sizing: border-box;
        }

        .sidebar-container.is-open {
            left: 0; /* Slide in */
        }

        .sidebar-close-btn {
            display: block; /* Show close button */
        }

        /* Override product.css to make product grid 3 columns */
        .product-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        /* Override product.css to NOT stack sidebar */
        .container {
            flex-direction: row !important;
        }
        .sidebar {
            position: static !important; /* Remove sticky from product.css */
            height: auto !important;
            width: 100% !important;
        }
    }

    @media (max-width: 768px) {
        /* Override product.css to make product grid 2 columns */
        .product-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .container {
            padding: 20px !important;
        }
        /* Adjust hero padding on mobile */
        .hero {
            padding: 0 20px !important;
        }
    }

    @media (max-width: 576px) {
        .product-grid {
            gap: 15px; /* Smaller gap for 2 columns */
        }
    }
    /* --- END: PRODUCT PAGE RESPONSIVE IMPROVEMENTS --- */
    
  </style>
</head>
<body>

<div id="filter-overlay"></div>

<header class="navbar">
    <div class="logo">
      <img src="assets/Media (2) 1.png">
      <span class="brand-name">SAPLOT de MANILA</span>
    </div>
    
    <nav class="nav-menu" id="nav-menu">
      <ul class="nav-links">
        <li><a href="index.php">HOME</a></li>
        <li><a href="product.php" class="active">SHOP</a></li>
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
</header>

  
  <section class="hero" style="min-height: 12vh; padding: 0 80px; align-items: center; display: flex; justify-content: space-between;">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    
    <div class="hero-content" style="padding: 0; position: relative;">
      <h1 class="hero-heading" style="font-size: 1.8rem; margin: 0;">
        ALL Products
      </h1>
    </div>
    <div class="hero-image-container">
        <img src="assets/logo.png" alt="New Shoe Collection" class="hero-product-img" style="max-width: 180px; margin-top: 0;">
    </div>
  </section>

  <main class="container">
    
    <div class="sidebar-container" id="filter-sidebar">
        <button class="sidebar-close-btn" id="close-filter-btn">&times;</button>
        <aside class="sidebar">
          <div class="sidebar-section">
            <h3>Browse By</h3>
            <ul>
              <li><a href="product.php?category=running" <?php if($category == 'running') echo 'class="active"'; ?>>Running Shoes</a></li>
              <li><a href="product.php?category=basketball" <?php if($category == 'basketball') echo 'class="active"'; ?>>Basketball Shoes</a></li>
              <li><a href="product.php?category=style" <?php if($category == 'style') echo 'class="active"'; ?>>Style Shoes</a></li>
              <li><a href="product.php?category=all" <?php if($category == 'all') echo 'class="active"'; ?>>All Products</a></li>
            </ul>
          </div>

          <div class="sidebar-section">
            <h3>Filter By</h3>
            <label for="price">Price: <span id="price-value">₱10,000</span></label>
            <div class="price-slider">
              <input type="range" min="1000" max="10000" value="10000" id="price-range">
              <div class="price-labels">
                <span>₱1,000</span>
                <span>₱10,000</span>
              </div>
            </div>
          </div>
        </aside>
    </div>
    <section class="products">
      <button class="filter-toggle-btn" id="filter-toggle-btn">
          <i class="fa fa-filter"></i> Filters
      </button>
      <h2 style="text-transform: capitalize;"><?php echo htmlspecialchars($category); ?> Products <?php if(!empty($search)) echo "matching '".htmlspecialchars($search)."'"; ?></h2>
      <div class="product-grid" id="product-grid">
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $product_data = htmlspecialchars(json_encode([
                    "id" => $row["id"], "name" => $row["name"], "price" => $row["price"],
                    "image" => $row["image"], "rating" => $row["rating"]
                ]), ENT_QUOTES, 'UTF-8');

                // Define the onclick action based on login status
                $onclick_action = isset($_SESSION['user_id'])
                    ? "viewProduct(" . $product_data . ")"
                    : "document.getElementById('loginModal').style.display='block'";

                echo '
                <div class="product-card" data-price="' . $row["price"] . '" onclick="' . $onclick_action . '">
                    <div class="product-image-container">
                        <img src="' . $row["image"] . '" loading="lazy"/>
                        <div class="product-overlay"><button class="shop-button">Shop Now</button></div>
                    </div>
                    <div class="product-info">
                        <h4 class="product-name">' . htmlspecialchars($row["name"]) . '</h4>
                        <p class="product-price">₱' . number_format($row["price"], 2) . '</p>
                    </div>
                </div>';
            }
        } else {
            echo "<p>No products found.</p>";
        }
        ?>
      </div>
    </section>
  </main>

  <footer>
    <div class="footer-main">
        <div class="footer-left">
            <h3>Saplot<span>De Manila</span></h3>
            <p class="footer-links">
                <a href="index.php" class="link-1">Home</a><a href="product.php">Pricing</a>
                <a href="about.php">About</a><a href="contact.php">Contact</a>
            </p>
        </div>
        <div class="footer-center">
            <div><i class="fa fa-map-marker"></i><p><span>Fortuna, Floridablanca</span> Pampanga</p></div>
            <div><i class="fa fa-phone"></i><p>+639 131 019 6878</p></div>
            <div><i class="fa fa-envelope"></i><p><a href="mailto:support@company.com">Saplotdemanila@gmail.com</a></p></div>
        </div>
        <div class="footer-right">
            <p class="footer-company-about">
                <span>About the company</span>
                Welcome to Saplot De Manila, your go to destination for exquisite footwear.
            </p>
            <div class="footer-icons">
                <a href="https://www.facebook.com/share/1FmsacvVRP/"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-copyright"><p>Copyright ©2025 All rights reserved</p></div>
  </footer>

  <div id="loginModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="closeLoginModal">&times;</span>
      <h2>Login to Saplot</h2>
      <form method="POST" action="index.php">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <div class="options">
          <label><input type="checkbox" name="remember"> Remember me</label>
          <a href="#">Forgot Password?</a>
        </div>
        <button type="submit" name="login">Login</button>
        <p class="register">Don't you have an account? <a href="#" id="showRegisterModal">Register</a></p>
      </form>
    </div>
  </div>

  <div id="registerModal" class="modal">
      <div class="modal-content">
          <span class="close-btn" id="closeRegisterModal">&times;</span>
          <h2>Register to Saplot</h2>
          <form method="POST" action="index.php">
              <input type="text" name="fullname" placeholder="Full Name" required>
              <input type="text" name="username" placeholder="Username" required>
              <input type="email" name="email" placeholder="Email" required>
              <input type="password" name="password" placeholder="Password" required>
              <input type="password" name="confirm_password" placeholder="Confirm Password" required>
              <button type="submit" name="register">Register</button>
              <p class="login">Have an account? <a href="#" id="showLoginModal">Login</a></p>
          </form>
      </div>
  </div>


  <script>
   function viewProduct(productData) {
      // Check if user is logged in (PHP session variable is set)
      <?php if (isset($_SESSION['user_id'])): ?>
        localStorage.setItem('selectedProduct', JSON.stringify(productData));
        window.location.href = `quantity.php?id=${productData.id}`;
      <?php else: ?>
        // If not logged in, show the login modal
        document.getElementById('loginModal').style.display = 'block';
      <?php endif; ?>
    }

    // Price Slider Filter
    const priceRange = document.getElementById('price-range');
    const priceValue = document.getElementById('price-value');
    const productCards = document.querySelectorAll('.product-card');

    if (priceRange && priceValue) {
        // Update the price value display
        priceRange.oninput = function() {
            priceValue.textContent = `₱${this.value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
            filterProducts();
        }
    }
    
    function filterProducts() {
        const maxPrice = parseInt(priceRange.value, 10);
        productCards.forEach(card => {
            const cardPrice = parseInt(card.dataset.price, 10);
            if (cardPrice <= maxPrice) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Run filter on page load
    filterProducts();


    function updateCartCount() {
      const cartItems = JSON.parse(localStorage.getItem('cart')) || [];
      const count = cartItems.reduce((total, item) => total + (item.quantity || 1), 0);
      const cartCount = document.getElementById('cartCount');
      if (cartCount) cartCount.textContent = count;
    }

    updateCartCount();
  </script>
  
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
        
        // --- MOBILE FILTER SIDEBAR SCRIPT ---
        const filterToggleBtn = document.getElementById('filter-toggle-btn');
        const filterSidebar = document.getElementById('filter-sidebar');
        const closeFilterBtn = document.getElementById('close-filter-btn');
        const filterOverlay = document.getElementById('filter-overlay');

        function openFilter() {
            if (filterSidebar) filterSidebar.classList.add('is-open');
            if (filterOverlay) filterOverlay.style.display = 'block';
        }

        function closeFilter() {
            if (filterSidebar) filterSidebar.classList.remove('is-open');
            if (filterOverlay) filterOverlay.style.display = 'none';
        }

        if (filterToggleBtn) {
            filterToggleBtn.addEventListener('click', openFilter);
        }
        if (closeFilterBtn) {
            closeFilterBtn.addEventListener('click', closeFilter);
        }
        if (filterOverlay) {
            filterOverlay.addEventListener('click', closeFilter);
        }
    });
  </script>
  </body>
</html>
<?php $conn->close(); ?>