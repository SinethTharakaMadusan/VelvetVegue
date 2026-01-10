<?php
include 'Database.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
SELECT 
    o.order_id,
    o.total_amount,
    o.order_date,
    o.order_status,
    u.name as customer_name,
    (SELECT p.name FROM order_items oi 
     JOIN products p ON oi.product_id = p.product_id 
     WHERE oi.order_id = o.order_id LIMIT 1) as product_name
FROM orders o
LEFT JOIN users u ON o.user_id = u.Users_id
";

if (!empty($search)) {
    // Search all orders
    $sql .= " WHERE o.order_id LIKE ? OR u.name LIKE ?";
    $sql .= " ORDER BY o.order_date DESC, o.order_id DESC";
    
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Show only recent 10 orders
    $sql .= " ORDER BY o.order_date DESC, o.order_id DESC LIMIT 10";
    $result = $conn->query($sql);
}

$orders = [];
while ($order = $result->fetch_assoc()) {
    $customer_name = !empty($order['customer_name']) ? $order['customer_name'] : 'Guest';
    $product_name = $order['product_name'] ?? 'N/A';
    if (strlen($product_name) > 25) {
        $product_name = substr($product_name, 0, 25) . '...';
    }
    
    $status_class = strtolower($order['order_status']);
    if ($status_class == 'processing') $status_class = 'processing';
    elseif ($status_class == 'shipped' || $status_class == 'delivered') $status_class = 'delivered';
    elseif ($status_class == 'cancelled') $status_class = 'cancelled';
    else $status_class = 'pending';
    
    $orders[] = [
        'order_id' => $order['order_id'],
        'order_id_formatted' => '#ORD-' . str_pad($order['order_id'], 3, '0', STR_PAD_LEFT),
        'customer_name' => htmlspecialchars($customer_name),
        'product_name' => htmlspecialchars($product_name),
        'total_amount' => 'LKR ' . number_format($order['total_amount'], 2),
        'order_date' => date('Y-m-d', strtotime($order['order_date'])),
        'order_status' => $order['order_status'],
        'status_class' => $status_class
    ];
}

echo json_encode(['success' => true, 'orders' => $orders]);
?>
