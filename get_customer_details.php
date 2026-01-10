<?php
include 'Database.php';

header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Get basic customer info
$customer_sql = "SELECT Users_id, name, email, created_at, status, failed_attempts FROM users WHERE Users_id = ?";
$stmt = $conn->prepare($customer_sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Customer not found']);
    exit();
}

$customer = $result->fetch_assoc();
$stmt->close();

// Get address and phone from users_details
$phone = 'Not provided';
$address = 'Not provided';
$details_row = null;

$details_sql = "SELECT phone_number, address_line1, city, state, zip_code FROM users_details WHERE users_id = ?";
$stmt = $conn->prepare($details_sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $details_result = $stmt->get_result();
    if ($row = $details_result->fetch_assoc()) {
        $details_row = $row;
        if (!empty($row['phone_number'])) {
            $phone = $row['phone_number'];
        }
        if (!empty($row['address_line1'])) {
            // Build full address
            $address_parts = [
                $row['address_line1'],
                $row['city'],
                $row['state'],
                $row['zip_code']
            ];
            $address = implode(', ', array_filter($address_parts));
        }
    }
    $stmt->close();
}

// Get order count and total spent
$order_count = 0;
$total_spent = 0;
$last_order = 'Never';

$stats_sql = "SELECT COUNT(*) as count, SUM(total_amount) as total, MAX(order_date) as last_date FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($stats_sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    if ($stats_row = $stats_result->fetch_assoc()) {
        $order_count = $stats_row['count'] ?? 0;
        $total_spent = $stats_row['total'] ?? 0;
        if ($stats_row['last_date']) {
            $last_order = date('M d, Y', strtotime($stats_row['last_date']));
        }
    }
    $stmt->close();
}

// Get recent orders
$orders = [];
$orders_sql = "SELECT order_id, order_date, order_status, total_amount FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5";
$stmt = $conn->prepare($orders_sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders_result = $stmt->get_result();
    while ($order = $orders_result->fetch_assoc()) {
        $orders[] = [
            'order_id' => '#ORD-' . str_pad($order['order_id'], 3, '0', STR_PAD_LEFT),
            'order_date' => date('M d, Y', strtotime($order['order_date'])),
            'order_status' => $order['order_status'],
            'total_amount' => number_format($order['total_amount'], 2)
        ];
    }
    $stmt->close();
}

$response = [
    'success' => true,
    'customer' => [
        'user_id' => $customer['Users_id'],
        'name' => $customer['name'],
        'email' => $customer['email'],
        'phone' => $phone,
        'address' => $address,
        'account_status' => $customer['status'] ?? 'active',
        'failed_attempts' => $customer['failed_attempts'] ?? 0,
        'joined_date' => date('F d, Y', strtotime($customer['created_at'])),
        'total_orders' => $order_count,
        'total_spent' => number_format($total_spent, 2),
        'last_order' => $last_order
    ],
    'contact_raw' => [
        'phone_number' => $details_row ? ($details_row['phone_number'] ?? '') : '',
        'address_line1' => $details_row ? ($details_row['address_line1'] ?? '') : '',
        'city' => $details_row ? ($details_row['city'] ?? '') : '',
        'state' => $details_row ? ($details_row['state'] ?? '') : '',
        'zip_code' => $details_row ? ($details_row['zip_code'] ?? '') : ''
    ],
    'recent_orders' => $orders
];

echo json_encode($response);
?>
