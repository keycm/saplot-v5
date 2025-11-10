<?php include 'session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Shopping Cart - Saplot de Manila</title>
  <link rel="stylesheet" href="CSS/style.css" />
  <link rel="stylesheet" href="CSS/cart.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  
  <section class="hero" style="min-height: 12vh; padding: 0 80px; align-items: center; display: flex; justify-content: space-between;">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    
    <div class="hero-content" style="padding: 0;">
      <h1 class="hero-heading" style="font-size: 1.8rem; margin: 0;">
        Shopping Cart
      </h1>
    </div>
    
    <div class="hero-image-container">
        <img src="assets/logo.png" alt="New Shoe Collection" class="hero-product-img" style="max-width: 180px; margin-top: 0;">
    </div>
  </section>

  <main class="cart-page-container">
    <div class="cart-content">
      <div class="cart-items-column">
        <div class="cart-header">
          <h2>Your Cart</h2>
          <a href="product.php" class="continue-shopping">Continue Shopping &rarr;</a>
        </div>
        <div id="cartItemsContainer">
          </div>
        <div id="emptyCartView" style="display: none; text-align: center; padding: 50px 20px;">
            <img src="assets/shopping-cart 1.png" alt="Empty Cart" style="width: 80px; opacity: 0.5; margin-bottom: 20px;">
            <h3>Your Cart is Empty</h3>
            <p style="margin-bottom: 20px;">Looks like you haven't added anything to your cart yet.</p>
            <a href="product.php" class="checkout-btn" style="text-decoration: none; display: inline-block;">Start Shopping</a>
        </div>
      </div>

      <div class="order-summary-column">
        <div class="order-summary">
          <h2>Order Summary</h2>
          <div class="summary-info" id="summaryInfo">
            </div>
          <div class="total-section" id="totalSection">
            </div>
          <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout</button>
        </div>
      </div>
    </div>
  </main>

  <div class="modal" id="deliveryModal">
  <div class="modal-content">
    <span onclick="closeModal()" class="close-modal-btn">&times;</span>
    <h2>Delivery Information</h2>
    <p class="delivery-note">Note: Delivery is available for Pampanga only.</p>
    
    <form id="deliveryForm">
      <!-- FULL NAME -->
      <label for="fullname">Full Name</label>
      <input type="text" name="fullname" id="fullname" required 
      placeholder="Enter your full name"
      oninput="this.value = this.value.replace(/[^a-zA-Z\s\.\-]/g, '')" />

      <!-- CONTACT -->
      <label for="contact">Contact Number</label>
      <input type="tel" name="contact" id="contact" required maxlength="12"
      placeholder="09XXXXXXXXX"
      oninput="this.value = this.value.replace(/[^0-9]/g, '')" />

      <!-- ADDRESS -->
      <label for="address">Address</label>
      <textarea name="address" id="address" rows="3" required
      placeholder="Enter your complete address"
      oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s\.,\-]/g, '')"></textarea>

      <!-- PAYMENT METHOD -->
      <label>Payment Method</label>
      <div class="payment-options">
        <label><input type="radio" name="payment" value="COD" checked> Cash On Delivery</label>
        <label><input type="radio" name="payment" value="GCash"> GCash</label>
      </div>

      <!-- SUBMIT -->
      <button type="submit" class="submit-order-btn">Confirm Order</button>
    </form>
  </div>
</div>

<!-- VALIDATION SCRIPT -->

  <footer>
    <div class="footer-main">
        <div class="footer-left">
            <h3>Saplot<span>De Manila</span></h3>
            <p class="footer-links">
                <a href="index.php" class="link-1">Home</a>
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
                <p>+639 999 999 9999</p>
            </div>
            <div>
                <i class="fa fa-envelope"></i>
                <p><a href="mailto:support@company.com">saplotdemanila@gmail.com</a></p>
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

<script>
let cartItems = JSON.parse(localStorage.getItem("cart") || "[]");
const cartContainer = document.getElementById("cartItemsContainer");
const summaryInfo = document.getElementById("summaryInfo");
const totalSection = document.getElementById("totalSection");
const checkoutBtn = document.getElementById("checkoutBtn");
const emptyCartView = document.getElementById("emptyCartView");

function closeModal() {
  document.getElementById("deliveryModal").style.display = "none";
}
function updateLocalStorage() {
  localStorage.setItem("cart", JSON.stringify(cartItems));
}

function showCart() {
  if (cartItems.length === 0) {
    cartContainer.style.display = 'none';
    emptyCartView.style.display = 'block';
    document.querySelector('.order-summary').style.display = 'none';
    return;
  }
  
  cartContainer.style.display = 'block';
  emptyCartView.style.display = 'none';
  document.querySelector('.order-summary').style.display = 'block';


  let subtotal = 0;
  cartContainer.innerHTML = cartItems.map((item, index) => {
    const lineTotal = item.price * item.quantity;
    subtotal += lineTotal;
    return `
      <div class="cart-card">
        <img src="${item.image || 'assets/no-image.png'}" alt="${item.name}">
        <div class="cart-details">
            <span class="product-name">${item.name}</span>
            <span class="product-info">Size: ${item.size} | Color: ${item.color}</span>
            <div class="quantity">
                <button onclick="updateQuantity(${index}, -1)">-</button>
                <span>${item.quantity}</span>
                <button onclick="updateQuantity(${index}, 1)">+</button>
            </div>
        </div>
        <div class="cart-actions">
            <span class="line-total">₱${lineTotal.toLocaleString()}</span>
            <button class="remove-btn" onclick="removeItem(${index})"><i class="fas fa-trash-alt"></i></button>
        </div>
      </div>
    `;
  }).join("");

  const shipping = 50;
  const discount = 20;
  const total = subtotal + shipping - discount;

  summaryInfo.innerHTML = `
    <p><span>Subtotal</span> <span>₱${subtotal.toLocaleString()}</span></p>
    <p><span>Shipping</span> <span>₱${shipping.toLocaleString()}</span></p>
    <p><span>Discount</span> <span>-₱${discount.toLocaleString()}</span></p>
  `;
  totalSection.innerHTML = `<p><span>Total</span> <span>₱${total.toLocaleString()}</span></p>`;
  checkoutBtn.disabled = false;
  
  return total;
}

function updateQuantity(index, change) {
  let newQty = cartItems[index].quantity + change;
  if (newQty < 1) {
    newQty = 1; // Prevent quantity from going below 1
  } else if (newQty > 10) {
    newQty = 10;
    alert("Maximum of 10 items allowed per product.");
  }
  cartItems[index].quantity = newQty;
  updateLocalStorage();
  finalTotal = showCart();
}

function removeItem(index) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        cartItems.splice(index, 1);
        updateLocalStorage();
        finalTotal = showCart();
    }
}

let finalTotal = showCart();

checkoutBtn.addEventListener("click", () => {
  document.getElementById("deliveryModal").style.display = "block";
});

document.getElementById("deliveryForm").addEventListener("submit", async function(e) {
  e.preventDefault();

  const fullname = this.fullname.value.trim();
  const contact  = this.contact.value.trim();
  const address  = this.address.value.trim();
  const payment  = document.querySelector('input[name="payment"]:checked').value;

  // ✅ FULL NAME: must have 2 words
  const nameParts = fullname.split(/\s+/);
  if (nameParts.length < 2) {
    alert("Please enter your full name (first and last name).");
    return;
  }

  // ✅ CONTACT: must be 12 digits and start w/ 09
  if (!/^09\d{10}$/.test(contact)) {
    alert("Please enter a valid 12-digit contact number starting with 09.");
    return;
  }

  // ✅ ADDRESS: must be at least 10 chars
  if (address.length < 10) {
    alert("Please enter a more complete address.");
    return;
  }

  // ✅ send only if valid
  try {
    const res = await fetch("save_order.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ fullname, contact, address, payment, cart: cartItems, total: finalTotal })
    });

    const result = await res.json();
    
    if (result.success) {
      localStorage.removeItem("cart");
      alert("Order placed successfully!");
      window.location.href = "product.php";
    } else {
      alert("Error: " + result.error);
    }
  } catch (error) {
    console.error(error);
    alert("Failed to send order.");
  }
});

document.querySelector('input[name="payment"][value="GCash"]').addEventListener("change", function() {
  if (this.checked) {
    const total = finalTotal;
    if (total <= 0) { alert("Cart is empty."); return; }
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "create_payment.php";
    const inputAmount = document.createElement("input");
    inputAmount.type = "hidden";
    inputAmount.name = "amount";
    inputAmount.value = total;
    form.appendChild(inputAmount);
    document.body.appendChild(form);
    form.submit();
  }
});
</script>
</body>
</html>