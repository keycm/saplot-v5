<?php
session_start();
// Ensure the current user is an admin before proceeding
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$conn = new mysqli("localhost", "root", "", "login_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = intval($_GET['id']);

    // Security check: an admin cannot demote or delete their own account
    if ($user_id == $_SESSION['user_id']) {
        header("Location: user_accounts.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        if ($action === 'promote') {
            $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        } elseif ($action === 'demote') {
            $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        } elseif ($action === 'delete') {
            // 1. Get the user's data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                // 2. Insert into the recently_deleted_users table
                $insert_stmt = $conn->prepare("INSERT INTO recently_deleted_users (original_id, fullname, username, email, role) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("issss", $user['id'], $user['fullname'], $user['username'], $user['email'], $user['role']);
                $insert_stmt->execute();

                // 3. Delete from the main users table
                $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $delete_stmt->bind_param("i", $user_id);
                $delete_stmt->execute();
            } else {
                throw new Exception("User not found.");
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // You can handle the error here, e.g., by setting a session message
    }
}

// Redirect back to the user accounts page after the action
header("Location: user_accounts.php");
exit();
?>