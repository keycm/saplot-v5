<?php
include 'session_check.php';

// Connection for products/orders
$conn_addproduct = new mysqli("localhost", "root", "", "addproduct");
if ($conn_addproduct->connect_error) {
  die("Connection failed for addproduct DB: " . $conn_addproduct->connect_error);
}

// Connection for user accounts
$conn_login_system = new mysqli("localhost", "root", "", "login_system");
if ($conn_login_system->connect_error) {
  die("Connection failed for login_system DB: " . $conn_login_system->connect_error);
}

// Get total sales
$result_sales = $conn_addproduct->query("SELECT SUM(amount) as total_revenue FROM revenue");
$row_sales = $result_sales->fetch_assoc();
$total_revenue = $row_sales['total_revenue'] ?? 0;

// Get total orders
$result_orders = $conn_addproduct->query("SELECT COUNT(*) as total_orders FROM cart");
$row_orders = $result_orders->fetch_assoc();
$total_orders = $row_orders['total_orders'] ?? 0;

// Get total users
$result_users = $conn_login_system->query("SELECT COUNT(*) as total_users FROM users");
$row_users = $result_users->fetch_assoc();
$total_users = $row_users['total_users'] ?? 0;

// Close connections
$conn_addproduct->close();
$conn_login_system->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Saplot Dashboard</title>
  <link rel="stylesheet" href="CSS/admin.css"/> 
  <link rel="stylesheet" href="CSS/home.css"/> 
  <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="admin-container">
    
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
      <header class="main-header">
        <h1>Dashboard</h1>
        <a href="logout.php" class="logout-button">Log Out</a>
      </header>

      <section class="dashboard-metrics">
        <div class="metric">
            <p class="metric-title">Sales</p>
            <img src="assets/financial-statement.png" alt="Revenue" class="metric-icon">
            <p class="metric-value">₱ <strong><?= number_format($total_revenue, 2) ?></strong></p>
        </div>
       
        <div class="metric">
            <p class="metric-title">Orders</p>
            <img src="assets/completed-task.png" alt="Orders" class="metric-icon">
            <p class="metric-value"><span class="plus-sign">+</span> <strong><?php echo number_format($total_orders); ?></strong></p>
        </div>
        
        <div class="metric">
            <p class="metric-title">Total Users</p>
            <i class="fas fa-users metric-icon" style="font-size: 24px;"></i>
            <p class="metric-value"><strong><?php echo number_format($total_users); ?></strong></p>
        </div>
      </section>  

      <div class="content-wrapper">
        <section class="stock-alert" id="stock-alert">
            <div class="stock-alert-header">
                <h2>Stock Alert</h2>
                <input type="text" id="stock-search" placeholder="Search products...">
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Product No.</th><th>Product Name</th><th>Price</th>
                            <th>Stock</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>

        <div class="right-dashboard-column">
            <section class="top-products" id="top-products">
              <h2>Top Selling Products</h2>
              <canvas id="topProductsChart"></canvas>
            </section>
    
            <section class="top-product-table" id="top-product-table">
              <table>
                <caption>Top Selling Products</caption>
                <thead>
                  <tr><th>Name</th><th>Price</th></tr>
                </thead>
                <tbody></tbody>
              </table>    
            </section>
        </div>
      </div>
    </main>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let allProducts = [];

    loadTopProducts();
    fetch('get_stock.php')
        .then(res => res.json())
        .then(data => {
            allProducts = data;
            renderStockTable(allProducts);
        });

    const searchInput = document.getElementById('stock-search');
    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        const filteredProducts = allProducts.filter(p => p.name.toLowerCase().includes(searchTerm));
        renderStockTable(filteredProducts);
    });

    function renderStockTable(products) {
        const tbody = document.querySelector('#stock-alert tbody');
        tbody.innerHTML = '';
        products.forEach(p => {
            tbody.innerHTML += `<tr><td>${p.product_no}</td><td>${p.name}</td><td>₱${parseFloat(p.price).toFixed(2)}</td><td>${p.quantity}</td><td style="color: ${p.status === 'In Stock' ? 'green' : 'red'}; font-weight: 600;">${p.status}</td></tr>`;
        });
    }

    function loadTopProducts() {
        fetch('get_top_selling.php')
            .then(res => res.json())
            .then(response => {
                if (!response.success) { return; }
                const data = response.data;
                const tbody = document.querySelector('#top-product-table tbody');
                tbody.innerHTML = '';
                data.forEach(p => {
                    tbody.innerHTML += `<tr><td>${p.product_name}</td><td>₱${parseFloat(p.price).toFixed(2)}<br> (${p.total_sold} sold)</td></tr>`;
                });
                const ctx = document.getElementById('topProductsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.map(p => p.product_name),
                        datasets: [{
                            data: data.map(p => p.total_sold),
                            backgroundColor: ['#F94144', '#F3722C', '#F8961E', '#F9C74F', '#90BE6D']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            });
    }
});
</script>
</body>
</html>