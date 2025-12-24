<?php
session_start();
require_once 'Database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity   = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $color      = isset($_POST['color']) ? $_POST['color'] : '';
    $size       = isset($_POST['size']) ? $_POST['size'] : '';

    if ($product_id > 0 && $quantity > 0) {
        
        // CHECK IF USER IS LOGGED IN
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            // Check if item already exists in DB cart
            $sql_check = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?";
            $stmt = $conn->prepare($sql_check);
            $stmt->bind_param("iiss", $user_id, $product_id, $color, $size);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update quantity
                $row = $result->fetch_assoc();
                $new_qty = $row['quantity'] + $quantity;
                $update_sql = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $new_qty, $row['cart_id']);
                $update_stmt->execute();
            } else {
                // Insert new item
                $insert_sql = "INSERT INTO cart (user_id, product_id, quantity, color, size) VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iiiss", $user_id, $product_id, $quantity, $color, $size);
                $insert_stmt->execute();
            }

        } else {
            // FALLBACK TO SESSION CART (GUEST)
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $product_id && $item['color'] == $color && $item['size'] == $size) {
                    $item['qty'] += $quantity;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $product_id,
                    'qty' => $quantity,
                    'color' => $color,
                    'size' => $size
                ];
            }
        }

        // Redirect to cart page
        header("Location: cart.php");
        exit();
    } else {
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: products.php");
        }
        exit();
    }
} else {
    header("Location: Home.php");
    exit();
}
?>
