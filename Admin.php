<?php
require_once 'Database.php';
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$pass_msg = '';
$pass_err = '';

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password_dashboard'])) {
    $current_p = $_POST['current_password'];
    $new_p = $_POST['new_password'];
    $confirm_p = $_POST['confirm_password'];

    if (empty($current_p) || empty($new_p) || empty($confirm_p)) {
        $pass_err = "All password fields are required.";
    } elseif ($new_p !== $confirm_p) {
        $pass_err = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM admins WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $admin_data = $res->fetch_assoc();
            if (password_verify($current_p, $admin_data['password'])) {
                $new_hash = password_hash($new_p, PASSWORD_DEFAULT);
                $up_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                $up_stmt->bind_param("si", $new_hash, $admin_id);
                if ($up_stmt->execute()) {
                    $pass_msg = "Password updated successfully!";
                } else {
                    $pass_err = "Error updating password.";
                }
                $up_stmt->close();
            } else {
                $pass_err = "Incorrect current password.";
            }
        } else {
            $pass_err = "Admin not found.";
        }
        $stmt->close();
    }
}

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

$recent_orders_sql = "
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
ORDER BY o.order_date DESC, o.order_id DESC
LIMIT 10
";
$recent_orders = $conn->query($recent_orders_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Dashboard</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="product.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
        }
        .status-select.pending { background: #fff3cd; color: #856404; border-color: #ffc107; }
        .status-select.processing { background: #cce5ff; color: #004085; border-color: #007bff; }
        .status-select.shipped { background: #d1ecf1; color: #0c5460; border-color: #17a2b8; }
        .status-select.delivered { background: #d4edda; color: #155724; border-color: #28a745; }
        .status-select.cancelled { background: #f8d7da; color: #721c24; border-color: #dc3545; }
    </style>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-container">
                <img class="logo" src="image/logo.png" alt="Logo">

            </div>
            <div class="menu-items">
                <a href="Admin.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-box"></i> Products</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
                <a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            </div>
            <div class="user-profile">
                <img src="image/user.png" alt="User" class="user-avatar">
                <div class="user-info">
                    <h4>Admin User</h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Dashboard</h1>
            </div>
            <div class="cards-container">
                <div class="card">
                    <i class="fas fa-dollar-sign"></i>
                    <div class="card-info">
                        <h2>Total Sales</h2>
                        <p class="value" id="total-sales">LKR <?php echo number_format($total_sales, 2); ?></p>
                        <p class="change positive">All Time</p>
                    </div>
                </div>
                <div class="card">
                    <i class="fas fa-shopping-bag"></i>
                    <div class="card-info">
                        <h2>Total Orders</h2>
                        <p class="value" id="total-orders"><?php echo $total_orders; ?></p>
                        <p class="change positive">All Time</p>
                    </div>
                </div>
                <div class="card">
                    <i class="fas fa-users"></i>
                    <div class="card-info">
                        <h2>Customers</h2>
                        <p class="value" id="total-customers"><?php echo $total_customers; ?></p>
                        <p class="change positive">Registered Users</p>
                    </div>
                </div>
            </div>


            <div class="recent-orders">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <div class="header-actions">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" placeholder="Search order number..." class="search-input" id="order-search" onkeyup="searchOrders()">
                        </div>
                        <button class="view-all" onclick="location.href='orders.php'">View All Orders</button>
                    </div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="orders-tbody">
                            <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                <?php while($order = $recent_orders->fetch_assoc()): 
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
                                ?>
                                <tr>
                                    <td>#ORD-<?php echo str_pad($order['order_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($customer_name); ?></td>
                                    <td><?php echo htmlspecialchars($product_name); ?></td>
                                    <td>LKR <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                    <td><span class="status <?php echo $status_class; ?>"><?php echo $order['order_status']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px;">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Password Update Section -->
            <div class="card" style="margin-top: 30px; display: block; max-width: 600px;">
                <div class="card-info" style="display: block;">
                    <h2 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Change Password</h2>
                    
                    <?php if(!empty($pass_msg)): ?>
                        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo $pass_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if(!empty($pass_err)): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo $pass_err; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Current Password</label>
                            <input type="password" name="current_password" class="pass-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">New Password</label>
                            <input type="password" name="new_password" class="pass-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="pass-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <input type="checkbox" id="showPassToggle" onclick="toggleDashboardPassword()"> 
                            <label for="showPassToggle" style="user-select: none; cursor: pointer;">Show Password</label>
                        </div>
                        <button type="submit" name="update_password_dashboard" style="background: #244551; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Update Password</button>
                    </form>
                </div>
            </div>

            <script>
            function toggleDashboardPassword() {
                var inputs = document.querySelectorAll('.pass-input');
                var checkBox = document.getElementById('showPassToggle');
                for (var i = 0; i < inputs.length; i++) {
                    if (checkBox.checked) {
                        inputs[i].type = "text";
                    } else {
                        inputs[i].type = "password";
                    }
                }
            }
            </script>
        </div>

    
    <script>
        function refreshDashboardStats() {
            fetch('get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-sales').textContent = 'LKR ' + data.data.total_sales;
                        document.getElementById('total-orders').textContent = data.data.total_orders;
                        document.getElementById('total-customers').textContent = data.data.total_customers;
                    }
                })
                .catch(error => console.log('Error refreshing stats:', error));
        }

        setInterval(refreshDashboardStats, 5000);

        let searchTimeout;
        function searchOrders() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const searchValue = document.getElementById('order-search').value.trim();
                const tbody = document.getElementById('orders-tbody');
                
                fetch('search_orders.php?search=' + encodeURIComponent(searchValue))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let html = '';
                            if (data.orders.length > 0) {
                                data.orders.forEach(order => {
                                    html += `<tr>
                                        <td>${order.order_id_formatted}</td>
                                        <td>${order.customer_name}</td>
                                        <td>${order.product_name}</td>
                                        <td>${order.total_amount}</td>
                                        <td>${order.order_date}</td>
                                        <td><span class="status ${order.status_class}">${order.order_status}</span></td>
                                    </tr>`;
                                });
                            } else {
                                html = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No orders found</td></tr>';
                            }
                            tbody.innerHTML = html;
                        }
                    })
                    .catch(error => console.error('Error searching orders:', error));
            }, 300); 
        }

        function updateStatus(selectElement) {
            const orderId = selectElement.getAttribute('data-order-id');
            const newStatus = selectElement.value;
            
            selectElement.className = 'status-select ' + newStatus.toLowerCase();
            
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('new_status', newStatus);
            
            fetch('update_order_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Status updated successfully');
                } else {
                    alert('Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        }
    </script>
</body>

</html>