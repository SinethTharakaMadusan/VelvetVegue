<?php
session_start();
require_once 'Database.php';

if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: logHome.php");
    exit();
}

$order_id = intval($_GET['order_id']);

$query = "SELECT * FROM orders WHERE order_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Order not found.";
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

$order_date = date('Y-m-d', strtotime($order['order_date']));
$order_date_formatted = date('F d, Y', strtotime($order['order_date']));

$delivery_date = date('F d, Y', strtotime($order['order_date'] . ' + 4 days'));

$payment_status = ($order['payment_method'] === 'Cash on Delivery') ? 'Pending' : 'Completed';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>
    <link rel="stylesheet" href="succefull.css?v=3">
</head>

<body>
    <form class="payment-success">
        <div class="form-content">
            <div class="header">
            <div class="success-icon">
                <dotlottie-wc src="https://lottie.host/bd14b743-4a63-445c-aa2a-8eb1ff4b4859/Hl53GdRj2T.lottie"
                    style="width: 120px; height: 120px;" autoplay loop></dotlottie-wc>
            </div>
            <div class="success-text">
                <h2>Payment Successful</h2>
                <p>Thank you for your order. Your order has been processed successfully.</p>
            </div>

            </div>
            <hr>
            <div class="content">
                <div class="details">
                    <h3>Order Details</h3>
                    <p>Order ID: <span>#<?php echo $order['order_id']; ?></span></p>
                    <p>Order Date: <span><?php echo $order_date_formatted; ?></span></p>
                    <p>Order Status: <span><?php echo htmlspecialchars($order['order_status']); ?></span></p>
                    <p>Estimated Delivery: <span><?php echo $delivery_date; ?></span></p>
                </div>
                <div class="details">
                    <h3>Payment Details</h3>
                    <p>Payment Method: <span><?php echo htmlspecialchars($order['payment_method']); ?></span></p>
                    <p>Payment Status: <span><?php echo $payment_status; ?></span></p>
                    <p>Total Amount: <span>Rs. <?php echo number_format($order['total_amount'], 2); ?></span></p>
                </div>
                <div class="details">
                    <h3>Shipping Information</h3>
                    <p>Contact: <span><?php echo htmlspecialchars($order['contact_no']); ?></span></p>
                    <p>Address: <span><?php echo htmlspecialchars($order['shipping_address']); ?></span></p>
                </div>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="logHome.php" style="padding: 12px 30px; background-color: #244551; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">Continue Shopping</a>
            </div>
        </div>
    </form>
</body>

</html>
