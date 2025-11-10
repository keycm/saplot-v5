<?php
$conn = new mysqli("localhost", "root", "", "saplot_inventory");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $_POST['name'];
$price = $_POST['price'];
$quantity = $_POST['quantity'];

$status = "Good";
if ($quantity < 5) {
    $status = "Very Low";
} elseif ($quantity < 10) {
    $status = "Low";
}

$sql = "INSERT INTO products (name, price, quantity, status) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sdis", $name, $price, $quantity, $status);

if ($stmt->execute()) {
    header("Location: homepage.html");
    exit();
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();
$conn->close();

?>