<?php
include 'session_check.php';
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT * FROM cart ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Orders Dashboard</title>
<link rel="stylesheet" href="CSS/admin.css"/>
<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
  .order-table { width:100%; border-collapse:collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
  .order-table th, .order-table td { padding:15px; border-bottom:1px solid #ddd; text-align:left; }
  .order-table th { background:#f9fafb; font-weight: 600; }
  .status-badge { padding:5px 12px; border-radius:50px; color:white; font-weight:bold; font-size:0.8em; display:inline-block; }
  .status-pending { background:#3b82f6; }
  .status-cancelled { background:#ef4444; }
  .status-completed { background:#10b981; }
  .status-select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Poppins', sans-serif; font-size: 0.9em; cursor: pointer; }
</style>
</head>
<body>
<div class="admin-container">
  
  <?php include 'admin_sidebar.php'; ?>

  <main class="main-content">
    <header class="main-header">
      <h1>Orders</h1>
      <a href="logout.php" class="logout-button">Log Out</a>
    </header>

    <table class="order-table">
      <thead>
        <tr>
          <th>Size(s)</th>
          <th>Customer</th>
          <th>Contact</th>
          <th>Address</th>
          <th>Products</th>
          <th>Total</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()) : ?>
          <?php
            $cart_items = json_decode($row['cart'], true);
            $product_list = [];
            $size_list = []; // Array to hold sizes
            if ($cart_items && is_array($cart_items)) {
              foreach ($cart_items as $item) {
                // Get product name and quantity
                $product_list[] = ($item['name'] ?? 'N/A') . " x" . ($item['quantity'] ?? 1);
                // Get size for each item
                $size_list[] = $item['size'] ?? 'N/A';
              }
            }
            $products_display = implode("<br>", $product_list);
            $sizes_display = implode("<br>", $size_list); // Display sizes on new lines if multiple
            $status_class = strtolower($row['status'] ?? 'pending');
          ?>
          <tr>
            <td><?= $sizes_display ?></td>
            <td><?= htmlspecialchars($row['fullname']) ?></td>
            <td><?= htmlspecialchars($row['contact']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td><?= $products_display ?></td>
            <td>₱<?= number_format($row['total'], 2) ?></td>
            <td><span class="status-badge status-<?= $status_class ?>"><?= ucfirst($status_class) ?></span></td>
            <td>
              <form method="POST" action="update_order.php">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <select name="action" class="status-select" onchange="this.form.submit()">
                  <option value="">-- Select --</option>
                  <?php if ($status_class === 'pending') : ?>
                    <option value="completed">Complete</option>
                    <option value="cancel">Cancel</option>
                  <?php endif; ?>
                  <option value="delete">Delete</option>
                </select>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </main>
</div>
</body>
</html>
<?php $conn->close(); ?>