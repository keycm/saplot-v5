<?php
session_start(); // Start the session to store feedback messages
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    $conn->begin_transaction();
    try {
        // Get the deleted product record from the 'recently_deleted_products' table
        $stmt = $conn->prepare("SELECT * FROM recently_deleted_products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            throw new Exception("Product not found in recently deleted items.");
        }

        if ($action === 'restore') {
            // Restore: Insert it back into the main 'products' table.
            // We do NOT specify the ID to let the database assign a new one, avoiding conflicts.
            $insert_stmt = $conn->prepare("INSERT INTO products (name, price, stock, image, category, rating) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sdissi", $product['name'], $product['price'], $product['stock'], $product['image'], $product['category'], $product['rating']);
            $insert_stmt->execute();

            // Now, delete the record from the 'recently_deleted_products' table
            $delete_stmt = $conn->prepare("DELETE FROM recently_deleted_products WHERE id = ?");
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            
            $_SESSION['success_message'] = "Product restored successfully!";

        } elseif ($action === 'permanent_delete') {
            // Permanently delete: first, remove the image file from the server
            if (file_exists($product['image'])) {
                unlink($product['image']);
            }
            // Then, delete the record from the 'recently_deleted_products' table permanently
            $delete_stmt = $conn->prepare("DELETE FROM recently_deleted_products WHERE id = ?");
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            
            $_SESSION['success_message'] = "Product permanently deleted!";
        }

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        // Store an error message to display on the next page
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    }
}

// Redirect back to the unified 'recently_deleted.php' page
header("Location: recently_deleted.php");
exit();
?>