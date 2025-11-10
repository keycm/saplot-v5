<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB Connection failed"]));
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

$fullname = $data['fullname'];
$contact = $data['contact'];
$address = $data['address'];
$payment = $data['payment'];
$items = $data['items'];
$total = $data['total'];

// Validate inputs
if (empty($fullname) || empty($contact) || empty($address) || empty($payment) || empty($items)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Insert order
$orderStmt = $conn->prepare("INSERT INTO orders (fullname, contact, address, payment, total, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$orderStmt->bind_param("ssssd", $fullname, $contact, $address, $payment, $total);

if (!$orderStmt->execute()) {
    echo json_encode(["status" => "error", "message" => "Failed to create order"]);
    exit;
}
$order_id = $orderStmt->insert_id;

// Prepare statements
$itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
$updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
$checkStock = $conn->prepare("SELECT stock FROM products WHERE id = ?");

// Loop through items
foreach ($items as $item) {
    $product_id = $item['id'];
    $quantity = $item['quantity'];
    $price = $item['price'];

    // Check stock availability
    $checkStock->bind_param("i", $product_id);
    $checkStock->execute();
    $result = $checkStock->get_result();
    $row = $result->fetch_assoc();

    if (!$row || $row['stock'] < $quantity) {
        echo json_encode(["status" => "error", "message" => "Insufficient stock for product ID $product_id"]);
        $conn->rollback();
        exit;
    }

    // Insert order item
    $itemStmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
    $itemStmt->execute();

    // Deduct stock
    $updateStock->bind_param("ii", $quantity, $product_id);
    $updateStock->execute();
}

echo json_encode(["status" => "success", "message" => "Order placed successfully!"]);

?>