<?php
include 'Database.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

// Get order details - with address and phone from orders table
$order_sql = "
SELECT 
    o.order_id,
    o.order_date,
    o.order_status,
    o.total_amount,
    o.payment_method,
    o.shipping_address,
    o.contact_no,
    u.name as customer_name,
    u.email as customer_email
FROM orders o
INNER JOIN users u ON o.user_id = u.users_id
WHERE o.order_id = ?
";

$stmt = $conn->prepare($order_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

$order = $order_result->fetch_assoc();
$stmt->close();

// Get order items
$items_sql = "
SELECT 
    oi.qty,
    oi.unit_price,
    oi.selected_color,
    oi.selected_size,
    p.name as product_name,
    (SELECT file_path FROM product_images WHERE product_id = p.product_id ORDER BY sort_order ASC LIMIT 1) AS product_image
FROM order_items oi
INNER JOIN products p ON oi.product_id = p.product_id
WHERE oi.order_id = ?
";

$stmt = $conn->prepare($items_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Items DB Error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = [
        'product_name' => htmlspecialchars($item['product_name']),
        'product_image' => !empty($item['product_image']) ? $item['product_image'] : 'image/no-image.png',
        'qty' => $item['qty'],
        'unit_price' => number_format($item['unit_price'], 2),
        'selected_color' => $item['selected_color'] ?: 'N/A',
        'selected_size' => $item['selected_size'] ?: 'N/A',
        'subtotal' => number_format($item['qty'] * $item['unit_price'], 2)
    ];
}
$stmt->close();

// Prepare response
$response = [
    'success' => true,
    'order' => [
        'order_id' => $order['order_id'],
        'order_id_formatted' => '#ORD-' . str_pad($order['order_id'], 3, '0', STR_PAD_LEFT),
        'order_date' => date('F d, Y', strtotime($order['order_date'])),
        'order_status' => $order['order_status'] ?? 'Pending',
        'total_amount' => number_format($order['total_amount'], 2),
        'payment_method' => $order['payment_method'] ?? 'N/A',
        'customer_name' => htmlspecialchars($order['customer_name']),
        'customer_email' => htmlspecialchars($order['customer_email']),
        'address' => htmlspecialchars($order['shipping_address'] ?? 'Not provided'),
        'phone' => htmlspecialchars($order['contact_no'] ?? 'Not provided')
    ],
    'items' => $items
];

echo json_encode($response);
?>
