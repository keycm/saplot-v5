<?php
include 'session_check.php';
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = ""; // 'success' or 'error'

// --- HANDLE STOCK UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($product_id > 0 && $quantity >= 0) {

        // Kunin muna current stock
        $check = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $check->bind_param("i", $product_id);
        $check->execute();
        $check_result = $check->get_result();
        $current_stock = $check_result->fetch_assoc()['stock'] ?? 0;
        $check->close();

        if ($action === 'add') {
            $new_stock = $current_stock + $quantity;
            if ($new_stock > 20) {
                $message = " Maximum stock limit reached (20 items only).";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_stock, $product_id);
                $stmt->execute();
                $message = " Stock added successfully!";
                $message_type = "success";
                $stmt->close();
            }
        } elseif ($action === 'set') {
            if ($quantity > 20) {
                $message = " Maximum stock limit is 20 items.";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
                $message = " Stock updated successfully!";
                $message_type = "success";
                $stmt->close();
            }
        }
    } else {
        $message = " Invalid product or quantity provided.";
        $message_type = "error";
    }
}
// --- FETCH ALL PRODUCTS ---
$products_result = $conn->query("SELECT id, name, stock, category FROM products ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Stock - Saplot de Manila</title>
<link rel="stylesheet" href="CSS/admin.css"/>
<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
    .message { text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 1.1rem; padding: 15px; border-radius: 8px; }
    .message.success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
    .message.error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); padding: 30px; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .card-header h2 { margin: 0; font-size: 1.8rem; }
    #stock-search { padding: 10px 15px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; width: 300px; }
    .btn { padding: 8px 15px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 0.9rem; }
    .btn-success { background: #28a745; color: #fff; }
    .btn-secondary { background: #6c757d; color: #fff; }

    /* Stock Table & Scrolling Container */
    .table-container {
        max-height: 420px; /* This sets the height for about 8 rows */
        overflow-y: auto; /* This enables the vertical scrollbar when needed */
        border-top: 1px solid #f0f0f0;
        border-bottom: 1px solid #f0f0f0;
    }
    .stock-table { width: 100%; border-collapse: collapse; }
    .stock-table th, .stock-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
    .stock-table th { background: #f9fafb; font-weight: 600; position: sticky; top: 0; }
    .stock-table tbody tr:last-child td { border-bottom: none; }
    .stock-status { font-weight: 600; }
    .stock-status.in-stock { color: #28a745; }
    .stock-status.low-stock { color: #ffc107; }
    .stock-status.out-of-stock { color: #dc3545; }
    .stock-table .actions { display: flex; gap: 10px; }

    /* Modal */
    .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); }
    .modal-content { background-color: #fefefe; margin: 15% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; position: relative; }
    .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-title { margin-top: 0; margin-bottom: 20px; }
    .modal-product-name { font-weight: bold; color: #E03A3E; margin-bottom: 20px; }
    form label { font-weight: 600; display: block; margin-bottom: 5px; }
    form input { width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem; }
</style>
</head>
<body>
<div class="admin-container">
    
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Manage Stock</h1>
            <a href="logout.php" class="logout-button">Log Out</a>
        </header>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Product Stock Levels</h2>
                <input type="text" id="stock-search" placeholder="Search by name...">
            </div>
            <div class="table-container">
                <table class="stock-table" id="stock-table">
                    <thead>
                        <tr><th>Product Name</th><th>Category</th><th>Current Stock</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $products_result->fetch_assoc()): ?>
                            <?php
                                $status_class = 'in-stock';
                                $status_text = 'In Stock';
                                if ($row['stock'] == 0) {
                                    $status_class = 'out-of-stock';
                                    $status_text = 'Out of Stock';
                                } elseif ($row['stock'] <= 5) {
                                    $status_class = 'low-stock';
                                    $status_text = 'Low Stock';
                                }
                            ?>
                            <tr data-name="<?= strtolower(htmlspecialchars($row['name'])) ?>">
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= $row['stock'] ?></td>
                                <td><span class="stock-status <?= $status_class ?>"><?= $status_text ?></span></td>
                                <td class="actions">
                                    <button class="btn btn-success btn-sm" onclick="openModal('add', <?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="openModal('set', <?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', <?= $row['stock'] ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="stockModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Update Stock</h2>
        <p class="modal-product-name" id="modalProductName"></p>
        <form method="POST">
            <input type="hidden" name="product_id" id="modalProductId">
            <input type="hidden" name="action" id="modalAction">
            <label for="quantity" id="modalLabel">Quantity:</label>
            <input type="number" name="quantity" id="modalQuantity" min="0" max="20" required>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Update Stock</button>
        </form>
    </div>
</div>

<script>
    const stockModal = document.getElementById('stockModal');
    const searchInput = document.getElementById('stock-search');
    const tableRows = document.querySelectorAll('#stock-table tbody tr');

    function openModal(action, productId, productName, currentStock = 0) {
        document.getElementById('modalProductId').value = productId;
        document.getElementById('modalAction').value = action;
        document.getElementById('modalProductName').textContent = productName;

        if (action === 'add') {
            document.getElementById('modalTitle').textContent = 'Add Stock';
            document.getElementById('modalLabel').textContent = 'Quantity to Add:';
            document.getElementById('modalQuantity').value = '';
            document.getElementById('modalQuantity').min = 1;
        } else if (action === 'set') {
            document.getElementById('modalTitle').textContent = 'Set New Stock Level';
            document.getElementById('modalLabel').textContent = 'New Stock Quantity:';
            document.getElementById('modalQuantity').value = currentStock;
            document.getElementById('modalQuantity').min = 0;
        }
        stockModal.style.display = 'block';
    }

    function closeModal() {
        stockModal.style.display = 'none';
    }

    // Search functionality
    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        tableRows.forEach(row => {
            const name = row.dataset.name;
            if (name.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    window.onclick = (event) => {
        if (event.target == stockModal) {
            closeModal();
        }
    }
</script>

</body>
</html>
<?php $conn->close(); ?>