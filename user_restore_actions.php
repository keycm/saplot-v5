<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$conn = new mysqli("localhost", "root", "", "login_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM recently_deleted_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            throw new Exception("Deleted user not found.");
        }

        if ($action === 'restore') {
            // Insert back into the main users table
            $insert_stmt = $conn->prepare("INSERT INTO users (id, fullname, username, email, role) VALUES (?, ?, ?, ?, ?)");
            // Note: We are restoring with the original ID
            $insert_stmt->bind_param("issss", $user['original_id'], $user['fullname'], $user['username'], $user['email'], $user['role']);
            $insert_stmt->execute();
        }

        // For both 'restore' and 'permanent_delete', we remove the record from the temp table
        $delete_stmt = $conn->prepare("DELETE FROM recently_deleted_users WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        // Handle error if needed
    }
}

header("Location: recently_deleted.php");
exit();
?>