<?php
include 'session_check.php';
// Connect to the correct database for users
$conn = new mysqli("localhost", "root", "", "login_system"); 
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch all users
$sql = "SELECT id, fullname, username, email, role FROM users ORDER BY fullname ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Accounts - Saplot de Manila</title>
<link rel="stylesheet" href="CSS/admin.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
<style>
  .card { background:white; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); overflow-x: auto; }
  .users-table { width:100%; border-collapse:collapse; }
  .users-table th, .users-table td { padding:12px 15px; text-align:left; border-bottom:1px solid #f0f0f0; }
  .users-table th { background:#f9fafb; font-weight: 600; }
  .role-badge { padding: 4px 10px; border-radius: 50px; font-weight: bold; font-size: 0.8em; }
  .role-admin { background: #E03A3E; color: white; }
  .role-user { background: #f0f0f0; color: #555; }
  .actions { display: flex; gap: 10px; }
  .btn { padding: 8px 15px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;}
  .btn-promote { background: #28a745; color: #fff; }
  .btn-demote { background: #ffc107; color: #212529; } /* Style for the new button */
  .btn-delete { background: #dc3545; color: #fff; }
  .current-user-text { font-style: italic; color: #6c757d; }
</style>
</head>
<body>
<div class="admin-container">
  
  <?php include 'admin_sidebar.php'; ?>

  <main class="main-content">
    <header class="main-header">
      <h1>User Accounts</h1>
      <a href="logout.php" class="logout-button">Log Out</a>
    </header>

    <div class="card">
        <table class="users-table">
          <thead>
            <tr>
              <th>Full Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()) : ?>
              <tr>
                <td><?= htmlspecialchars($row['fullname']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <span class="role-badge <?= $row['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                        <?= ucfirst($row['role']) ?>
                    </span>
                </td>
                <td class="actions">
                    <?php 
                    // Security check: Prevent the currently logged-in admin from changing their own role or being deleted.
                    if ($row['id'] != $_SESSION['user_id']) {
                        if ($row['role'] === 'admin') { ?>
                            <a href="user_actions.php?action=demote&id=<?= $row['id'] ?>" class="btn btn-demote" onclick="return confirm('Demote this admin back to a user?')">
                                <i class="fas fa-user-times"></i> Make User
                            </a>
                        <?php } else { ?>
                            <a href="user_actions.php?action=promote&id=<?= $row['id'] ?>" class="btn btn-promote" onclick="return confirm('Are you sure you want to make this user an admin?')">
                                <i class="fas fa-user-shield"></i> Make Admin
                            </a>
                        <?php } ?>
                        <a href="user_actions.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    <?php } else { ?>
                        <span class="current-user-text">(Current User)</span>
                    <?php } ?>
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
<?php $conn->close(); ?>