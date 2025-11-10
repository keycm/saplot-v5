<?php
include 'session_check.php';
// Connection for products/orders
$conn_addproduct = new mysqli("localhost", "root", "", "addproduct");
if ($conn_addproduct->connect_error) { die("Connection failed: " . $conn_addproduct->connect_error); }

// Connection for users
$conn_login_system = new mysqli("localhost", "root", "", "login_system");
if ($conn_login_system->connect_error) { die("Connection failed: " . $conn_login_system->connect_error); }

// Fetch recently deleted orders
$orders_result = $conn_addproduct->query("SELECT * FROM recently_deleted ORDER BY deleted_at DESC");

// Fetch recently deleted products
$products_result = $conn_addproduct->query("SELECT * FROM recently_deleted_products ORDER BY deleted_at DESC");

// Fetch recently deleted users
$users_result = $conn_login_system->query("SELECT * FROM recently_deleted_users ORDER BY deleted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recently Deleted Items</title>
<link rel="stylesheet" href="CSS/admin.css"/>
<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
  .card { background:white; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); overflow-x: auto; margin-bottom: 30px; }
  .card h2 { font-size: 1.8rem; margin-top: 0; margin-bottom: 20px; }
  table { width:100%; border-collapse:collapse; }
  th, td { padding:12px 15px; border-bottom:1px solid #f0f0f0; text-align:left; white-space: nowrap; }
  th { background:#f9fafb; font-weight: 600; }
  td img { width: 50px; height: 50px; object-fit: contain; border-radius: 8px; }
  .action-select { padding: 6px 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 0.9em; cursor: pointer; }
</style>
</head>
<body>
<div class="admin-container">

  <?php include 'admin_sidebar.php'; ?>

  <main class="main-content">
    <header class="main-header">
        <h1>Recently Deleted</h1>
        <a href="logout.php" class="logout-button">Log Out</a>
    </header>

    <div class="card">
      <h2>Deleted Orders</h2>
      <table>
        <thead><tr><th>Order ID</th><th>Customer Name</th><th>Deleted At</th><th>Action</th></tr></thead>
        <tbody>
          <?php while ($row = $orders_result->fetch_assoc()) : ?>
            <tr>
              <td><?= $row['order_id'] ?></td>
              <td><?= htmlspecialchars($row['fullname']) ?></td>
              <td><?= $row['deleted_at'] ?></td>
              <td>
                <form method="POST" action="restore_delete.php"><input type="hidden" name="id" value="<?= $row['id'] ?>"><select name="action" class="action-select" onchange="this.form.submit()"><option value="">-- Select --</option><option value="restore">Restore</option><option value="permanent">Permanent Delete</option></select></form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Deleted Products</h2>
      <table>
        <thead><tr><th>Image</th><th>Product Name</th><th>Deleted At</th><th>Action</th></tr></thead>
        <tbody>
          <?php while ($row = $products_result->fetch_assoc()) : ?>
            <tr>
              <td><img src="<?= htmlspecialchars($row['image']) ?>" alt=""></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= $row['deleted_at'] ?></td>
              <td>
                <form method="POST" action="product_actions.php"><input type="hidden" name="id" value="<?= $row['id'] ?>"><select name="action" class="action-select" onchange="this.form.submit()"><option value="">-- Select --</option><option value="restore">Restore</option><option value="permanent_delete">Permanent Delete</option></select></form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Deleted User Accounts</h2>
      <table>
        <thead><tr><th>Full Name</th><th>Username</th><th>Email</th><th>Deleted At</th><th>Action</th></tr></thead>
        <tbody>
          <?php while ($row = $users_result->fetch_assoc()) : ?>
            <tr>
              <td><?= htmlspecialchars($row['fullname']) ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= $row['deleted_at'] ?></td>
              <td>
                <form method="POST" action="user_restore_actions.php"><input type="hidden" name="id" value="<?= $row['id'] ?>"><select name="action" class="action-select" onchange="this.form.submit()"><option value="">-- Select --</option><option value="restore">Restore</option><option value="permanent_delete">Permanent Delete</option></select></form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>

</div>
</body>
</html>
<?php 
$conn_addproduct->close();
$conn_login_system->close(); 
?>