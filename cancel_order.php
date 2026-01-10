<?php
session_start();
require_once 'Database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_POST['order_id'];
$check_sql = "SELECT order_id, order_status FROM orders WHERE order_id = ? AND user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or unauthorized']);
    $stmt->close();
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

$cancellable_statuses = ['Pending', 'Processing', 'Order Placed'];
if (!in_array($order['order_status'], $cancellable_statuses)) {
    echo json_encode(['success' => false, 'message' => 'This order cannot be cancelled']);
    exit();
}

// Update order status to Cancelled
$update_sql = "UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
}

$stmt->close();
$conn->close();
?>
