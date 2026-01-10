<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function log_msg($msg) {
    file_put_contents('cart_debug.log', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

log_msg("Request received: " . $_SERVER["REQUEST_METHOD"]);
log_msg("POST data: " . print_r($_POST, true));
log_msg("Session data: " . print_r($_SESSION, true));

require_once 'Database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    $selected_color = isset($_POST['selected_color']) ? trim($_POST['selected_color']) : '';
    $selected_size = isset($_POST['selected_size']) ? trim($_POST['selected_size']) : '';

    log_msg("Product ID: $product_id, Qty: $qty, Color: $selected_color, Size: $selected_size");

    if ($product_id > 0) {
        
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            $sql_check = "SELECT cart_id, qty FROM cart 
                         WHERE users_id = ? AND product_id = ? 
                         AND (selected_color = ? OR (selected_color IS NULL AND ? = ''))
                         AND (selected_size = ? OR (selected_size IS NULL AND ? = ''))";
            $stmt = $conn->prepare($sql_check);
            $stmt->bind_param("iissss", $user_id, $product_id, $selected_color, $selected_color, $selected_size, $selected_size);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $new_qty = $row['qty'] + $qty;
                $update_sql = "UPDATE cart SET qty = ? WHERE cart_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $new_qty, $row['cart_id']);
                $update_stmt->execute();
                log_msg("Updated cart item $row[cart_id] with new qty: $new_qty");
            } else {
                $color_val = empty($selected_color) ? null : $selected_color;
                $size_val = empty($selected_size) ? null : $selected_size;
                
                $insert_sql = "INSERT INTO cart (users_id, product_id, qty, selected_color, selected_size) 
                              VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iiiss", $user_id, $product_id, $qty, $color_val, $size_val);
                $insert_stmt->execute();
                log_msg("Inserted new cart item for product $product_id");
            }

        } else {
            log_msg("User not logged in, redirecting to Log.php");
            header("Location: Log.php");
            exit();
        }

        header("Location: cart.php");
        exit();
    } else {
        log_msg("Invalid product_id, redirecting to home");
        header("Location: logHome.php");
        exit();
    }
} else {
    header("Location: logHome.php");
    exit();
}
?>
