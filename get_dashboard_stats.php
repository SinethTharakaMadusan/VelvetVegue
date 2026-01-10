<?php
// AJAX endpoint to get dashboard statistics
require_once 'Database.php';

header('Content-Type: application/json');

// Get Total Sales
$sales_sql = "SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM orders";
$sales_result = $conn->query($sales_sql);
$total_sales = $sales_result->fetch_assoc()['total_sales'];

// Get Total Orders
$orders_sql = "SELECT COUNT(*) as total_orders FROM orders";
$orders_result = $conn->query($orders_sql);
$total_orders = $orders_result->fetch_assoc()['total_orders'];

// Get Total Customers
$customers_sql = "SELECT COUNT(*) as total_customers FROM users";
$customers_result = $conn->query($customers_sql);
$total_customers = $customers_result->fetch_assoc()['total_customers'];

echo json_encode([
    'success' => true,
    'data' => [
        'total_sales' => number_format($total_sales, 2),
        'total_orders' => $total_orders,
        'total_customers' => $total_customers
    ]
]);
?>
