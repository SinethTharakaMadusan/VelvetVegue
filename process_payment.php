<?php
session_start();
require_once 'Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Log.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get form data
$payment_method = $_POST['payment_method'] ?? '';
$total = $_POST['total'] ?? 0;
$subtotal = $_POST['subtotal'] ?? 0;
$delivery_fee = $_POST['delivery_fee'] ?? 0;
$full_name = $_POST['full_name'] ?? '';
$user_phone = $_POST['user_phone'] ?? '';
$user_address = $_POST['user_address'] ?? '';

// Validate required fields
if (empty($payment_method) || empty($user_address) || empty($user_phone) || $total <= 0) {
    $_SESSION['payment_error'] = "Missing required order information.";
    header("Location: paymentM.php");
    exit();
}

// Get cart items
$cartItems = [];
if (isset($_POST['cart_items']) && is_array($_POST['cart_items'])) {
    foreach ($_POST['cart_items'] as $item_json) {
        $cartItems[] = json_decode($item_json, true);
    }
}

if (empty($cartItems)) {
    $_SESSION['payment_error'] = "Cart is empty.";
    header("Location: cart.php");
    exit();
}

// Card payment validation
if ($payment_method === 'card') {
    $card_number = $_POST['card_number'] ?? '';
    $card_holder = $_POST['card_holder'] ?? '';
    $expiry_month = $_POST['expiry_month'] ?? '';
    $expiry_year = $_POST['expiry_year'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Remove spaces from card number
    $card_number = str_replace(' ', '', $card_number);
    
    // Validate card number (must be 16 digits)
    if (!preg_match('/^\d{16}$/', $card_number)) {
        $_SESSION['payment_error'] = "Invalid card number. Must be 16 digits.";
        header("Location: paymentM.php");
        exit();
    }
    
    // Validate card holder name
    if (empty($card_holder) || strlen($card_holder) < 3) {
        $_SESSION['payment_error'] = "Invalid card holder name.";
        header("Location: paymentM.php");
        exit();
    }
    
    // Validate expiry date
    if (!preg_match('/^\d{2}$/', $expiry_month) || !preg_match('/^\d{2}$/', $expiry_year)) {
        $_SESSION['payment_error'] = "Invalid expiry date format.";
        header("Location: paymentM.php");
        exit();
    }
    
    $month = intval($expiry_month);
    $year = intval($expiry_year) + 2000; // Convert YY to YYYY
    
    if ($month < 1 || $month > 12) {
        $_SESSION['payment_error'] = "Invalid expiry month.";
        header("Location: paymentM.php");
        exit();
    }
    
    // Check if card is expired
    $current_year = intval(date('Y'));
    $current_month = intval(date('m'));
    
    if ($year < $current_year || ($year == $current_year && $month < $current_month)) {
        $_SESSION['payment_error'] = "Card has expired.";
        header("Location: paymentM.php");
        exit();
    }
    
    // Validate CVV (must be 3 digits)
    if (!preg_match('/^\d{3}$/', $cvv)) {
        $_SESSION['payment_error'] = "Invalid CVV. Must be 3 digits.";
        header("Location: paymentM.php");
        exit();
    }
    
    $payment_method_display = "Credit Card";
} else {
    $payment_method_display = "Cash on Delivery";
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert order into orders table
    $order_status = "Processing";
    $order_date = date('Y-m-d');
    $order_time = date('H:i:s');
    
    $insert_order = "INSERT INTO orders (user_id, total_amount, order_status, shipping_address, contact_no, payment_method, order_date, order_time) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_order);
    $stmt->bind_param("idssssss", $user_id, $total, $order_status, $user_address, $user_phone, $payment_method_display, $order_date, $order_time);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create order.");
    }
    
    $order_id = $conn->insert_id;
    $stmt->close();
    
    // Insert order items into order_items table
    $insert_item = "INSERT INTO order_items (order_id, product_id, qty, unit_price, selected_color, selected_size) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $item_stmt = $conn->prepare($insert_item);
    
    foreach ($cartItems as $item) {
        $product_id = $item['product_id'] ?? 0;
        $qty = $item['qty'] ?? 1;
        $unit_price = $item['price'] ?? 0;
        $selected_color = $item['selected_color'] ?? '';
        $selected_size = $item['selected_size'] ?? '';
        
        $item_stmt->bind_param("iiidss", 
            $order_id, 
            $product_id, 
            $qty, 
            $unit_price, 
            $selected_color, 
            $selected_size
        );
        
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to insert order item: " . $item_stmt->error);
        }
    }
    $item_stmt->close();
    
    // Cart items are kept - NOT deleted after order
    
    // Commit transaction
    $conn->commit();
    
    // Redirect to success page with order ID
    header("Location: succssfull.php?order_id=" . $order_id);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['payment_error'] = "Payment processing failed: " . $e->getMessage();
    header("Location: paymentM.php");
    exit();
}
?>
