<?php
include 'session_check.php';
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$successMessage = "";
$errorMessage = "";

// --- HANDLE SOFT DELETE --- (pinanatili)
if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
        $stmt->bind_param("i",$id_to_delete);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if (!$product) throw new Exception("Product not found.");

        $insert_stmt = $conn->prepare("INSERT INTO recently_deleted_products (original_id,name,price,stock,image,category,rating) VALUES (?,?,?,?,?,?,?)");
        $insert_stmt->bind_param("isdissi",$product['id'],$product['name'],$product['price'],$product['stock'],$product['image'],$product['category'],$product['rating']);
        $insert_stmt->execute();

        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $delete_stmt->bind_param("i",$id_to_delete);
        $delete_stmt->execute();

        $conn->commit();
        header("Location: recently_deleted.php"); exit();
    } catch(Exception $e) {
        $conn->rollback();
        $errorMessage = " Error deleting product: ".$e->getMessage();
    }
}

// --- HELPER FUNCTION FOR UPLOADS ---
function uploadFiles($fileInput) {
    $paths = [];
    if (!empty($_FILES[$fileInput]['name'])) {
        if (is_array($_FILES[$fileInput]['name'])) {
            foreach ($_FILES[$fileInput]['name'] as $i => $name) {
                if ($name) {
                    $filename = basename($name);
                    $tmp_name = $_FILES[$fileInput]['tmp_name'][$i];
                    $target = "uploads/$filename";
                    move_uploaded_file($tmp_name, $target);
                    $paths[] = $target;
                }
            }
        } else {
            $filename = basename($_FILES[$fileInput]['name']);
            $tmp_name = $_FILES[$fileInput]['tmp_name'];
            $target = "uploads/$filename";
            move_uploaded_file($tmp_name, $target);
            $paths[] = $target;
        }
    }
    return $paths; // return array of uploaded paths
}

// --- HANDLE ADD/UPDATE ---
if($_SERVER["REQUEST_METHOD"]=="POST") {
    $id = $_POST['product_id'] ?? '';
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];
    $color_name = $_POST['color_name'] ?? [];
    $new_color_name = $_POST['new_color_name'] ?? [];

    // Ensure color names are arrays
    if (!is_array($color_name)) $color_name = [$color_name];
    if (!is_array($new_color_name)) $new_color_name = [$new_color_name];

    // Upload files
    $targetFiles = uploadFiles('image');            
    $colorTargets = uploadFiles('color_image');     
    $newColorTargets = uploadFiles('new_color_image'); 

    // --- UPDATE PRODUCT ---
    if(!empty($id)) {
        $imagePath = $targetFiles[0] ?? null;
        if($imagePath){
            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, category=?, image=? WHERE id=?");
            $stmt->bind_param("sdissi",$name,$price,$stock,$category,$imagePath,$id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, category=? WHERE id=?");
            $stmt->bind_param("sdssi",$name,$price,$stock,$category,$id);
        }

        if($stmt->execute()){
            // Update existing colors
            foreach($color_name as $index => $cname){
                $cname = trim($cname);
                $cimagePath = $colorTargets[$index] ?? '';
                $stmtCheck = $conn->prepare("SELECT id FROM product_colors WHERE product_id=? AND color_name=?");
                $stmtCheck->bind_param("is",$id,$cname);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();

                if($resCheck->num_rows > 0){
                    if($cimagePath){
                        $row = $resCheck->fetch_assoc();
                        $stmtUpdate = $conn->prepare("UPDATE product_colors SET color_image=? WHERE id=?");
                        $stmtUpdate->bind_param("si",$cimagePath,$row['id']);
                        $stmtUpdate->execute();
                    }
                } else {
                    $stmtInsert = $conn->prepare("INSERT INTO product_colors (product_id,color_name,color_image) VALUES (?,?,?)");
                    $stmtInsert->bind_param("iss",$id,$cname,$cimagePath);
                    $stmtInsert->execute();
                }
            }

            // Add new colors
            foreach($new_color_name as $i => $newC){
                $newC = trim($newC);
                $newCImagePath = $newColorTargets[$i] ?? '';
                $stmtNew = $conn->prepare("INSERT INTO product_colors (product_id,color_name,color_image) VALUES (?,?,?)");
                $stmtNew->bind_param("iss",$id,$newC,$newCImagePath);
                $stmtNew->execute();
            }

            $successMessage = "Product updated successfully!";
        } else {
            $errorMessage = "Failed to update product.";
        }
    }
    // --- ADD NEW PRODUCT ---
    else {
        $imagePath = $targetFiles[0] ?? null;
        $stmt = $conn->prepare("INSERT INTO products (name,price,stock,image,category) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sdiss",$name,$price,$stock,$imagePath,$category);
        if($stmt->execute()){
            $product_id = $stmt->insert_id;

            // Add existing colors
            foreach($color_name as $i => $cname){
                $cname = trim($cname);
                $cimagePath = $colorTargets[$i] ?? '';
                $stmtColor = $conn->prepare("INSERT INTO product_colors (product_id,color_name,color_image) VALUES (?,?,?)");
                $stmtColor->bind_param("iss",$product_id,$cname,$cimagePath);
                $stmtColor->execute();
            }

            // Add new colors
            foreach($new_color_name as $i => $newC){
                $newC = trim($newC);
                $newCImagePath = $newColorTargets[$i] ?? '';
                $stmtNewColor = $conn->prepare("INSERT INTO product_colors (product_id,color_name,color_image) VALUES (?,?,?)");
                $stmtNewColor->bind_param("iss",$product_id,$newC,$newCImagePath);
                $stmtNewColor->execute();
            }

            $successMessage = "Product and colors added successfully!";
        } else {
            $errorMessage = "Failed to add product.";
        }
    }
}

// --- FETCH PRODUCTS ---
$products_result = $conn->query("SELECT p.*, c.color_name, c.color_image FROM products p LEFT JOIN product_colors c ON p.id=c.product_id ORDER BY p.id DESC");
?>




<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Products - Saplot de Manila</title>
<link rel="stylesheet" href="CSS/admin.css"/>
<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
.message { position: relative; text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 1.1rem; padding: 15px; border-radius: 8px; }
.message.success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
.message.error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
.message .close-x { position: absolute; right: 15px; top: 8px; font-size: 22px; font-weight: bold; color: #555; cursor: pointer; transition: 0.2s; }
.message .close-x:hover { color: #000; }
.card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); padding: 30px; margin-bottom: 30px; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.card-header h2 { margin: 0; font-size: 1.8rem; }
.btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 0.9rem; }
.btn-primary { background: #E03A3E; color: #fff; }
.btn-primary:hover { background: #c03034; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-sm { padding: 5px 10px; font-size: 0.8rem; }
form label { font-weight: 600; display: block; margin-bottom: 8px; font-size: 1rem; }
form input, form select, form button { width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem; }
.table-container { max-height: 420px; overflow-y: auto; }
.products-table { width: 100%; border-collapse: collapse; }
.products-table th, .products-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
.products-table th { background: #f9fafb; font-weight: 600; position: sticky; top: 0; }
.products-table img { width: 50px; height: 50px; object-fit: contain; border-radius: 8px; background: #f8f9fa; }
#product-search { padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 0.9rem; width: 250px; }
.modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
.modal-content { background-color: #fefefe; margin: 10% auto; padding: 30px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 12px; position: relative; }
.close-btn { color: #aaa; position: absolute; top: 15px; right: 25px; font-size: 28px; font-weight: bold; cursor: pointer; }
<style>
body {
  font-family: 'Poppins', sans-serif;
  background-color: #f7f8fa;
  margin: 0;
  color: #333;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

th, td {
  padding: 14px 16px;              /* Mas spacing, pantay sa button */
  text-align: center;              /* Centered text */
  font-size: 0.95rem;
}

th {
  background-color: #2d3e50;       /* Dark blue-gray header */
  color: white;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

tr:nth-child(even) {
  background-color: #f8f9fa;       /* Light gray alternation */
}

tr:hover {
  background-color: #e9f3ff;       /* Hover highlight */
  transition: background 0.2s ease;
}

/* ===== Messages ===== */
.message {
  position: relative;
  text-align: center;
  font-weight: 500;
  margin: 15px auto;
  font-size: 1rem;
  padding: 15px 20px;
  border-radius: 10px;
  max-width: 700px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.message.success {
  color: #155724;
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
}
.message.error {
  color: #721c24;
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
}
.message .close-x {
  position: absolute;
  right: 15px;
  top: 10px;
  font-size: 22px;
  color: #555;
  cursor: pointer;
  transition: 0.2s;
}
.message .close-x:hover { color: #000; }

/* ===== Buttons ===== */
.btn {
  padding: 12px 22px;              /* Mas makapal at mahaba ng konti */
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;                 /* Slightly bigger text */
  display: inline-flex;
  align-items: center;
  justify-content: center;         /* Center horizontally */
  text-align: center;
  gap: 6px;                        /* Space for icons or text gap */
  min-width: 120px;                /* Para di sobrang liit */
}
.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}


.btn-primary {
  background: #e03a3e;
  color: #fff;
  box-shadow: 0 3px 8px rgba(224, 58, 62, 0.3);
}
.btn-primary:hover {
  background: #c22f33;
  transform: translateY(-2px);
}

.btn-secondary {
  background: #6c757d;
  color: #fff;
}
.btn-secondary:hover {
  background: #5a6268;
  transform: translateY(-2px);
}

.btn-danger {
  background: #dc3545;
  color: #fff;
}
.btn-danger:hover {
  background: #c82333;
  transform: translateY(-2px);
}

.btn-sm {
  padding: 6px 10px;
  font-size: 0.8rem;
}

/* ===== Cards and Table ===== */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 15px 20px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  margin-bottom: 20px;
}

.card-header h2 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
}

#product-search {
  padding: 10px 12px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 0.9rem;
  outline: none;
  transition: 0.3s;
}
#product-search:focus {
  border-color: #e03a3e;
  box-shadow: 0 0 0 2px rgba(224, 58, 62, 0.1);
}

/* ===== Table ===== */
.table-container {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  padding: 20px;
  overflow-x: auto;
}

.products-table {
  width: 100%;
  border-collapse: collapse;
}

.products-table th {
  background: #f1f1f1;
  text-transform: uppercase;
  font-size: 0.85rem;
  color: #555;
  padding: 12px;
}
.products-table td {
  padding: 12px;
  border-top: 1px solid #eee;
  vertical-align: middle;
}

.products-table tr:hover {
  background-color: #f9f9f9;
  transition: 0.2s;
}

/* ===== Modal ===== */
.modal {
  display: none;
  position: fixed;
  z-index: 1001;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow-y: auto;
  background-color: rgba(0,0,0,0.6);
  backdrop-filter: blur(3px);
}

.modal-content {
  background: #fff;
  margin: 5% auto;
  padding: 30px;
  border-radius: 12px;
  max-width: 550px;
  width: 90%;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  position: relative;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from { transform: translateY(-40px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-content h2 {
  margin-top: 0;
  font-size: 1.5rem;
  color: #e03a3e;
  text-align: center;
}

.close-btn {
  color: #aaa;
  position: absolute;
  top: 12px;
  right: 18px;
  font-size: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.2s;
}
.close-btn:hover { color: #000; }

/* ===== Form ===== */
form label {
  font-weight: 600;
  display: block;
  margin-bottom: 5px;
}
form input, form select {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 0.95rem;
  margin-bottom: 15px;
  outline: none;
  transition: 0.2s;
}
form input:focus, form select:focus {
  border-color: #e03a3e;
  box-shadow: 0 0 0 2px rgba(224, 58, 62, 0.1);
}
</style>

</style>
</head>
<body>
<div class="admin-container">
<?php include 'admin_sidebar.php'; ?>

<main class="main-content">
<header class="main-header">
<h1>Manage Products</h1>
<a href="logout.php" class="logout-button">Log Out</a>
</header>

<?php if($successMessage): ?>
<div class="message success"><span class="close-x" onclick="this.parentElement.style.display='none';">&times;</span><?= $successMessage ?></div>
<?php endif; ?>
<?php if($errorMessage): ?>
<div class="message error"><span class="close-x" onclick="this.parentElement.style.display='none';">&times;</span><?= $errorMessage ?></div>
<?php endif; ?>

<div class="card-header">
<h2>Product List</h2>
<input type="text" id="product-search" placeholder="Search products..." onkeyup="filterProducts()">
<button id="showAddFormBtn" class="btn btn-primary"><i class="fas fa-plus"></i> Add New</button>
</div>

<div class="table-container">
<table class="products-table">
<thead>
<tr>
<th>Image</th><th>Name</th><th>Category</th><th>Color</th><th>Price</th><th>Stock</th><th>Actions</th>
</tr>
</thead>
<tbody id="productTable">
<?php while ($row = $products_result->fetch_assoc()): ?>
<tr data-name="<?= strtolower(htmlspecialchars($row['name'])) ?>">
<td><img src="<?= htmlspecialchars($row['image']) ?>" width="60"></td>
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['category']) ?></td>
<td>
<?= htmlspecialchars($row['color_name'] ?? 'N/A') ?><br>
<?php if (!empty($row['color_image'])): ?>
<img src="<?= htmlspecialchars($row['color_image']) ?>" width="40" height="40" style="border-radius:8px;">
<?php endif; ?>
</td>
<td>₱<?= number_format($row['price'], 2) ?></td>
<td><?= $row['stock'] ?></td>
<td>
<button class="btn btn-secondary btn-sm edit-btn"
 data-id="<?= $row['id'] ?>"
 data-name="<?= htmlspecialchars($row['name']) ?>"
 data-price="<?= $row['price'] ?>"
 data-stock="<?= $row['stock'] ?>"
 data-category="<?= $row['category'] ?>"
 data-color="<?= htmlspecialchars($row['color_name'] ?? '') ?>">
 <i class="fas fa-edit"></i> Edit
</button>
<a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Move to Recently Deleted?')"><i class="fas fa-trash"></i> Delete</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</main>
</div>
<!-- ADD MODAL -->
<div id="addModal" class="modal">
<div class="modal-content">
<span class="close-btn" id="addClose">&times;</span>
<h2>Add New Product</h2>
<form method="POST" enctype="multipart/form-data">
<label>Product Name:</label><input type="text" name="name" required>
<label>Price:</label><input type="number" name="price" step="0.01" required>
<label>Stock:</label><input type="number" name="stock" required>
<label>Category:</label>
<select name="category" required>
<option value="running">Running</option>
<option value="basketball">Basketball</option>
<option value="style">Style</option>
</select>
<label>Color Name:</label><input type="text" name="color_name" pattern="[A-Za-z\s]+">
<label>Product Image:</label><input type="file" name="image" accept="image/*">
<label>Color Image:</label><input type="file" name="color_image" accept="image/*">
<button type="submit" class="btn btn-primary">Add Product</button>
</form>
</div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>Edit Product</h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="product_id" id="edit-id">

      <label>Product Name:</label>
      <input type="text" name="name" id="edit-name" required>

      <label>Price:</label>
      <input type="number" name="price" id="edit-price" step="0.01" required>

      <label>Stock:</label>
      <input type="number" name="stock" id="edit-stock" required>

      <label>Category:</label>
      <select name="category" id="edit-category" required>
        <option value="running">Running</option>
        <option value="basketball">Basketball</option>
        <option value="style">Style</option>
      </select>

      <hr>
      <h3>Colors</h3>
      <div id="color-container"></div>
      <button type="button" id="addColorBtn" class="btn btn-secondary btn-sm">Add Another Color</button>

      <button type="submit" class="btn btn-primary">Update Product</button>
    </form>
  </div>
</div>

<script>
// ===== ADD MODAL =====
const addModal = document.getElementById('addModal');
const showAddBtn = document.getElementById('showAddFormBtn');
const addCloseBtn = document.getElementById('addClose');

showAddBtn.onclick = () => addModal.style.display = 'block';
addCloseBtn.onclick = () => addModal.style.display = 'none';

// ===== EDIT MODAL =====
const editModal = document.getElementById('editModal');
const editCloseBtn = editModal.querySelector('.close-btn');
const colorContainer = document.getElementById('color-container');
const addColorBtn = document.getElementById('addColorBtn');

// Function to add a color input row
function addColorRow(name = '') {
  const div = document.createElement('div');
  div.classList.add('color-row');
  div.style.marginBottom = '10px';
  div.innerHTML = `
    <input type="text" name="color_name[]" value="${name}" placeholder="Color Name" required style="width:60%;">
    <input type="file" name="color_image[]" style="width:35%;">
    <button type="button" class="removeColorBtn btn btn-danger btn-sm" style="margin-left:5px;">Remove</button>
  `;
  colorContainer.appendChild(div);

  // Remove row when "Remove" button is clicked
  div.querySelector('.removeColorBtn').onclick = () => div.remove();
}

// Open Edit Modal and Load Product Data
document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    editModal.style.display = 'block';

    // Fill basic info
    document.getElementById('edit-id').value = btn.dataset.id;
    document.getElementById('edit-name').value = btn.dataset.name;
    document.getElementById('edit-price').value = btn.dataset.price;
    document.getElementById('edit-stock').value = btn.dataset.stock;
    document.getElementById('edit-category').value = btn.dataset.category;

    // Clear previous colors
    colorContainer.innerHTML = '';

    // Load existing colors (expects btn.dataset.color to be comma-separated)
    const colors = btn.dataset.color ? btn.dataset.color.split(',') : [];
    colors.forEach(color => {
      if (color.trim() !== '') addColorRow(color.trim());
    });
  });
});

//  Add new color field dynamically
addColorBtn.onclick = () => addColorRow();

// Close Edit Modal
editCloseBtn.onclick = () => editModal.style.display = 'none';

// Close any modal when clicking outside
window.onclick = (event) => {
  if (event.target == addModal) addModal.style.display = 'none';
  if (event.target == editModal) editModal.style.display = 'none';
};

// ===== REAL-TIME SEARCH =====
function filterProducts() {
  const search = document.getElementById('product-search').value.toLowerCase();
  const rows = document.querySelectorAll('#productTable tr');
  rows.forEach(row => {
    const name = row.getAttribute('data-name')?.toLowerCase() || '';
    row.style.display = name.includes(search) ? '' : 'none';
  });
}
</script>


</body>
</html>
