<?php
session_start();
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product id from URL
$product_id = $_GET['id'] ?? 0;
if ($product_id == 0) die("Product ID missing.");

// Fetch product details
$sql = "SELECT * FROM products WHERE id = $product_id";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) die("Product not found.");
$product = $result->fetch_assoc();

// Fetch product colors from product_colors table
$sql_colors = "SELECT color_name, color_image FROM product_colors WHERE product_id = $product_id";
$colorsResult = $conn->query($sql_colors);
$colors = [];
if ($colorsResult && $colorsResult->num_rows > 0) {
    while ($row = $colorsResult->fetch_assoc()) {
        $colors[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Product Details - Saplot de Manila</title>
  <link rel="stylesheet" href="CSS/style.css"/>
  <link rel="stylesheet" href="CSS/quantity.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<header class="navbar">
    <div class="logo">
        <img src="assets/Media (2) 1.png">
        <span class="brand-name">SAPLOT de MANILA</span>
    </div>
        <ul class="nav-links">
            <li><a href="index.php">HOME</a></li>
            <li><a href="product.php" class="active">SHOP</a></li>
            <li><a href="about.php">ABOUT</a></li>
            <li><a href="contact.php">CONTACT</a></li>
        </ul>
    </nav>
    <div class="nav-icons">
        <a href="#" class="icon-btn" id="search-icon">
            <img src="assets/search (1) 1.png" alt="Search">
        </a>
        <a href="cart.php" class="icon-btn cart-wrapper">
            <img src="assets/shopping-cart 1.png" alt="Cart" id="cartBtn">
            <span class="cart-count" id="cartCount">0</span>
        </a>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="logout.php" class="login-btn">Log Out</a>
    <?php else: ?>
        <button id="loginModalBtn" class="login-btn">Log In / Sign Up</button>
    <?php endif; ?>
</header>

<section class="hero" style="min-height: 12vh; padding: 0 80px; align-items: center; display: flex; justify-content: space-between;">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="hero-content" style="padding: 0;">
      <h1 class="hero-heading" style="font-size: 1.8rem; margin: 0;">
        Product Details
      </h1>
    </div>
    <div class="hero-image-container">
        <img src="assets/logo.png" alt="New Shoe Collection" class="hero-product-img" style="max-width: 180px; margin-top: 0;">
    </div>
</section>

<main class="container">
  <div class="product-details-grid">
    <div class="product-image-container">
      <img src="<?php echo !empty($colors) ? $colors[0]['color_image'] : 'assets/shoes3 1.png'; ?>" alt="Shoe" id="main-product-image">
    </div>

    <div class="product-details-content">
      <h1 id="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
      <div class="rating" id="product-rating"><?php echo str_repeat('★', $product['rating']) . str_repeat('☆', 5 - $product['rating']); ?></div>
      <p class="price" id="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
      
      <div class="selector-group">
          <label>Size</label>
          <div class="sizes">
            <button class="size-btn">40</button>
            <button class="size-btn">41</button>
            <button class="size-btn">42</button>
            <button class="size-btn">43</button>
            <button class="size-btn">44</button>
            <button class="size-btn">45</button>
          </div>
      </div>

      <div class="selector-group">
          <label>Color</label>
          <div class="colors">
              <?php if (!empty($colors)): ?>
                  <?php foreach ($colors as $color): ?>
                      <span class="color-swatch"
                            data-color="<?php echo strtolower($color['color_name']); ?>"
                            data-image="<?php echo $color['color_image']; ?>"
                            style="background-color: <?php echo strtolower($color['color_name']); ?>;">
                      </span>
                  <?php endforeach; ?>
              <?php else: ?>
                  <p>No colors available</p>
              <?php endif; ?>
          </div>
      </div>

      <div class="selector-group">
          <label for="quantity">Quantity</label>
          <div class="quantity-selector">
            <button id="qty-minus">-</button>
            <input type="number" id="quantity-input" value="1" min="1" max="10" readonly />
            <button id="qty-plus">+</button>
          </div>
      </div>

      <div class="actions">
        <button class="add-to-cart-btn">Add to Cart</button>
        <button class="buy-now-btn" onclick="buyNow()">Buy Now</button>
      </div>
    </div>
  </div>
</main>

<footer></footer>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const product = JSON.parse(localStorage.getItem('selectedProduct'));
    if (!product) { window.location.href = 'product.php'; return; }

    const sizeBtns = document.querySelectorAll('.size-btn');
    const colorSwatches = document.querySelectorAll('.color-swatch');
    const quantityInput = document.getElementById('quantity-input');
    const qtyPlus = document.getElementById('qty-plus');
    const qtyMinus = document.getElementById('qty-minus');
    const addToCartBtn = document.querySelector('.add-to-cart-btn');

    sizeBtns.forEach(btn => btn.addEventListener('click', () => {
        sizeBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }));

    colorSwatches.forEach(swatch => swatch.addEventListener('click', () => {
        colorSwatches.forEach(s => s.classList.remove('active'));
        swatch.classList.add('active');
        document.getElementById('main-product-image').src = swatch.dataset.image;
    }));

    qtyPlus.addEventListener('click', () => {
        let currentQty = parseInt(quantityInput.value);
        if (currentQty < 10) quantityInput.value = currentQty + 1;
    });

    qtyMinus.addEventListener('click', () => {
        let currentQty = parseInt(quantityInput.value);
        if (currentQty > 1) quantityInput.value = currentQty - 1;
    });

    addToCartBtn.addEventListener('click', () => {
        const selectedSize = document.querySelector('.size-btn.active');
        const selectedColor = document.querySelector('.color-swatch.active');
        if (!selectedSize || !selectedColor) { alert('Please select a size and color.'); return; }

        const item = {
            id: product.id || Date.now(),
            name: product.name,
            price: product.price,
            image: selectedColor.dataset.image, // Use selected color image
            quantity: parseInt(quantityInput.value),
            size: selectedSize.textContent,
            color: selectedColor.dataset.color
        };

        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const existingItemIndex = cart.findIndex(ci => ci.name === item.name && ci.size === item.size && ci.color === item.color);
        if (existingItemIndex > -1) { cart[existingItemIndex].quantity += item.quantity; }
        else { cart.push(item); }

        localStorage.setItem('cart', JSON.stringify(cart));
        alert(`${item.name} has been added to your cart!`);
    });
});

function buyNow() {
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    addToCartBtn.click();
    if (document.querySelector('.size-btn.active') && document.querySelector('.color-swatch.active')) {
        window.location.href = 'cart.php';
    }
}

function updateCartCount() {
  const cartItems = JSON.parse(localStorage.getItem('cart')) || [];
  const count = cartItems.reduce((total, item) => total + (item.quantity || 1), 0);
  const cartCount = document.getElementById('cartCount');
  if (cartCount) cartCount.textContent = count;
}
updateCartCount();
</script>
</body>
</html>
