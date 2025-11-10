<?php
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="sidebar">
   <a href="Dashboard.php" class="sidebar-logo-link">
       <img class="logo" src="assets/Media (2) 1.png" alt="Logo">
   </a>

    <nav class="sidebar-nav">
        <a href="Dashboard.php" class="nav-item <?php if ($current_page == 'Dashboard.php') echo 'active'; ?>">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="Sales.php" class="nav-item <?php if ($current_page == 'Sales.php') echo 'active'; ?>">
            <i class="fas fa-chart-line"></i><span>Sales</span>
        </a>
        <a href="Orders.php" class="nav-item <?php if ($current_page == 'Orders.php') echo 'active'; ?>">
            <i class="fas fa-receipt"></i><span>Orders</span>
        </a>
        <a href="admin_inquiries.php" class="nav-item <?php if ($current_page == 'admin_inquiries.php') echo 'active'; ?>">
            <i class="fas fa-envelope-open-text"></i><span>Inquiries</span>
        </a>
        <a href="add_stock.php" class="nav-item <?php if ($current_page == 'add_stock.php') echo 'active'; ?>">
            <i class="fas fa-cubes"></i><span>Add Stock</span>
        </a>
        <a href="practiceaddproduct.php" class="nav-item <?php if ($current_page == 'practiceaddproduct.php') echo 'active'; ?>">
            <i class="fas fa-plus-square"></i><span>Add Product</span>
        </a>
        <a href="user_accounts.php" class="nav-item <?php if ($current_page == 'user_accounts.php') echo 'active'; ?>">
            <i class="fas fa-users-cog"></i><span>User Accounts</span>
        </a>
        <a href="recently_deleted.php" class="nav-item <?php if ($current_page == 'recently_deleted.php') echo 'active'; ?>">
            <i class="fas fa-trash"></i><span>Recently Deleted</span>
        </a>
    </nav>
    
    <div style="flex-grow: 1;"></div> 
</aside>