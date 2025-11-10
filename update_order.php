<?php
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $action_l = strtolower($action);

    $conn->begin_transaction();
    $transaction_started = true;

    try {
        if ($action_l === 'delete') {
            // 1) Get the order
            $sel = $conn->prepare("SELECT * FROM cart WHERE id = ?");
            if (!$sel) throw new Exception("Prepare SELECT failed: " . $conn->error);
            $sel->bind_param("i", $id);
            $sel->execute();
            $res = $sel->get_result();
            if ($res->num_rows === 0) {
                $sel->close();
                throw new Exception("Order not found (id=$id).");
            }
            $row = $res->fetch_assoc();
            $sel->close();

            // 2) Insert into recently_deleted
            $ins_sql = "
                INSERT INTO recently_deleted
                (order_id, fullname, contact, address, total, created_at, status, cart)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $ins = $conn->prepare($ins_sql);
            if (!$ins) throw new Exception("Prepare INSERT recently_deleted failed: " . $conn->error);

            $ins->bind_param(
                "isssdsss",
                $row['id'],
                $row['fullname'],
                $row['contact'],
                $row['address'],
                $row['total'],
                $row['created_at'],
                $row['status'],
                $row['cart']
            );
            if (!$ins->execute()) {
                $ins->close();
                throw new Exception("Execute INSERT failed: " . $ins->error);
            }
            $ins->close();

            // 3) Delete from cart
            $del = $conn->prepare("DELETE FROM cart WHERE id = ?");
            if (!$del) throw new Exception("Prepare DELETE failed: " . $conn->error);
            $del->bind_param("i", $id);
            if (!$del->execute()) {
                $del->close();
                throw new Exception("Execute DELETE failed: " . $del->error);
            }
            $del->close();

            $conn->commit();

        } elseif ($action_l === 'cancel') {
            // Restore stock
            $sel = $conn->prepare("SELECT cart FROM cart WHERE id = ?");
            if (!$sel) throw new Exception("Prepare SELECT cart failed: " . $conn->error);
            $sel->bind_param("i", $id);
            $sel->execute();
            $sel->bind_result($cart_json);
            $sel->fetch();
            $sel->close();

            if ($cart_json) {
                $cart_items = json_decode($cart_json, true);
                if ($cart_items && is_array($cart_items)) {
                    foreach ($cart_items as $item) {
                        $product_id = isset($item['id']) ? intval($item['id']) : 0;
                        $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;

                        if ($product_id > 0) {
                            $upd = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                            if (!$upd) throw new Exception("Prepare UPDATE failed: " . $conn->error);
                            $upd->bind_param("ii", $qty, $product_id);
                            if (!$upd->execute()) {
                                $upd->close();
                                throw new Exception("Execute UPDATE failed: " . $upd->error);
                            }
                            $upd->close();
                        }
                    }
                }
            }

            $up = $conn->prepare("UPDATE cart SET status = 'Cancelled' WHERE id = ?");
            if (!$up) throw new Exception("Prepare UPDATE status failed: " . $conn->error);
            $up->bind_param("i", $id);
            if (!$up->execute()) {
                $up->close();
                throw new Exception("Execute UPDATE status failed: " . $up->error);
            }
            $up->close();

            $conn->commit();

        } elseif ($action_l === 'completed') {
            // Get order details para maipasok sa revenue
            $sel = $conn->prepare("SELECT id, total FROM cart WHERE id = ?");
            if (!$sel) throw new Exception("Prepare SELECT failed: " . $conn->error);
            $sel->bind_param("i", $id);
            $sel->execute();
            $order = $sel->get_result()->fetch_assoc();
            $sel->close();

            // Update status to Completed
            $up = $conn->prepare("UPDATE cart SET status = 'Completed' WHERE id = ?");
            if (!$up) throw new Exception("Prepare UPDATE completed failed: " . $conn->error);
            $up->bind_param("i", $id);
            if (!$up->execute()) {
                $up->close();
                throw new Exception("Execute UPDATE completed failed: " . $up->error);
            }
            $up->close();

            // Insert revenue record
            $ins_rev = $conn->prepare("INSERT INTO revenue (order_id, amount, date_created) VALUES (?, ?, NOW())");
            if (!$ins_rev) throw new Exception("Prepare INSERT revenue failed: " . $conn->error);
            $ins_rev->bind_param("id", $order['id'], $order['total']);
            if (!$ins_rev->execute()) {
                $ins_rev->close();
                throw new Exception("Execute INSERT revenue failed: " . $ins_rev->error);
            }
            $ins_rev->close();

            $conn->commit();
        } else {
            $conn->rollback();
            throw new Exception("Unknown action: " . htmlspecialchars($action));
        }

        header("Location: Orders.php");
        exit();

    } catch (Exception $e) {
        if ($transaction_started) $conn->rollback();
        error_log("update_order.php error: " . $e->getMessage());
        die("Operation failed: " . htmlspecialchars($e->getMessage()));
    }
}

$conn->close();
?>
