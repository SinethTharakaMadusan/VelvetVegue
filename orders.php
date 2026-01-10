<?php
include 'Database.php';

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to avoid form resubmission
    header("Location: orders.php?updated=1");
    exit();
}

// Fetch all orders with customer info
$sql = "
SELECT 
    o.order_id,
    o.order_date,
    o.order_status,
    o.total_amount,
    o.payment_method,
    u.name as customer_name,
    u.email as customer_email,
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
FROM orders o
INNER JOIN users u ON o.user_id = u.users_id
ORDER BY o.order_date DESC
";

$result = $conn->query($sql);
if (!$result) {
    die('DB error: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Orders</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-container">
                <img class="logo" src="image/logo.png" alt="Logo">
            </div>
            <div class="menu-items">
                <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-box"></i> Products</a>
                <a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a>
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
                <h1>Orders Management</h1>
            </div>

            <?php if (isset($_GET['updated'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Order status updated successfully!
            </div>
            <?php endif; ?>

            <div class="orders-filters product-filters">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="order-search" placeholder="Search order ID or customer..." class="search-input" onkeyup="searchOrders()">
                </div>
                <div class="filter-group">
                    <select class="filter-select" id="status-filter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <button class="filter-btn" onclick="filterByStatus()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <div class="orders-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($order = $result->fetch_assoc()): 
                        $order_date_formatted = date('M d, Y', strtotime($order['order_date']));
                        $total_formatted = number_format($order['total_amount'], 2);
                        $status = $order['order_status'];
                        $status_class = strtolower($status);
                    ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">#ORD-<?php echo str_pad($order['order_id'], 3, '0', STR_PAD_LEFT); ?></div>
                        <form method="POST" class="status-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <select name="new_status" class="status-select <?php echo $status_class; ?>" onchange="this.form.submit()">
                                <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo $status === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="Shipped" <?php echo $status === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo $status === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo $status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">

                        </form>
                    </div>
                    <div class="order-details">
                        <div class="customer-info">
                            <i class="fas fa-user"></i>
                            <div>
                                <h3><?php echo htmlspecialchars($order['customer_name']); ?></h3>
                                <p><?php echo htmlspecialchars($order['customer_email']); ?></p>
                            </div>
                        </div>
                        <div class="order-info">
                            <div class="info-item">
                                <span class="label">Date:</span>
                                <span class="value"><?php echo $order_date_formatted; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Items:</span>
                                <span class="value"><?php echo $order['item_count']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Total:</span>
                                <span class="value">LKR <?php echo $total_formatted; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="order-actions">
                        <button class="view-btn" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)"><i class="fas fa-eye"></i> View Details</button>
                    </div>
                </div>
                    <?php endwhile; ?>
                <?php else: ?>
                <div class="no-orders">
                    <p style="text-align: center; padding: 40px; font-size: 18px; color: #666;">No orders found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Details Popup -->
    <div class="popup-bg" id="orderPopup">
        <div class="popup-content order-popup">
            <span class="close-btn" onclick="closePopup()">&times;</span>
            <h3>Order Details</h3>
            <div id="orderDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.status-select').forEach(function(select) {
            select.addEventListener('change', function() {
                
                this.classList.remove('pending', 'processing', 'shipped', 'delivered', 'cancelled');
                
                this.classList.add(this.value.toLowerCase());
            });
        });

        
        let searchTimeout;
        function searchOrders() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const searchValue = document.getElementById('order-search').value.toLowerCase().trim();
                const orderCards = document.querySelectorAll('.order-card');
                
                orderCards.forEach(function(card) {
                    const orderId = card.querySelector('.order-id').textContent.toLowerCase();
                    const customerName = card.querySelector('.customer-info h3').textContent.toLowerCase();
                    
                    if (orderId.includes(searchValue) || customerName.includes(searchValue)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }, 300); 
        }

        
        function filterByStatus() {
            const filterValue = document.getElementById('status-filter').value.toLowerCase();
            const orderCards = document.querySelectorAll('.order-card');
            
            orderCards.forEach(function(card) {
                const statusSelect = card.querySelector('.status-select');
                const currentStatus = statusSelect ? statusSelect.value.toLowerCase() : '';
                
                if (filterValue === '' || currentStatus === filterValue) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        
        function viewOrderDetails(orderId) {
            const popup = document.getElementById('orderPopup');
            const content = document.getElementById('orderDetailsContent');
            
            content.innerHTML = '<p style="text-align: center; padding: 20px;">Loading...</p>';
            popup.style.display = 'flex';
            
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const order = data.order;
                        const items = data.items;
                        
                        let itemsHtml = '';
                        items.forEach(item => {
                            itemsHtml += `
                                <div class="order-item">
                                    <img src="${item.product_image}" alt="${item.product_name}">
                                    <div class="item-info">
                                        <h4>${item.product_name}</h4>
                                        <p>Color: ${item.selected_color} | Size: ${item.selected_size}</p>
                                        <p>Qty: ${item.qty} × LKR ${item.unit_price} = <strong>LKR ${item.subtotal}</strong></p>
                                    </div>
                                </div>
                            `;
                        });
                        
                        content.innerHTML = `
                            <div class="order-summary">
                                <div class="summary-row">
                                    <span class="summary-label">Order ID:</span>
                                    <span class="summary-value">${order.order_id_formatted}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Date:</span>
                                    <span class="summary-value">${order.order_date}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Status:</span>
                                    <span class="summary-value status ${order.order_status.toLowerCase()}">${order.order_status}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Payment:</span>
                                    <span class="summary-value">${order.payment_method}</span>
                                </div>
                            </div>
                            
                            <div class="customer-details">
                                <h4><i class="fas fa-user"></i> Customer Information</h4>
                                <p><strong>Name:</strong> ${order.customer_name}</p>
                                <p><strong>Email:</strong> ${order.customer_email}</p>
                                <p><strong>Phone:</strong> ${order.phone}</p>
                                <p><strong>Address:</strong> ${order.address}</p>
                            </div>
                            
                            <div class="order-items-list">
                                <h4><i class="fas fa-box"></i> Order Items</h4>
                                ${itemsHtml}
                            </div>
                            
                            <div class="order-total">
                                <span>Total Amount:</span>
                                <span>LKR ${order.total_amount}</span>
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<p style="text-align: center; color: red;">Error loading order details</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<p style="text-align: center; color: red;">Error loading order details</p>';
                });
        }

        // Close Popup
        function closePopup() {
            document.getElementById('orderPopup').style.display = 'none';
        }

        // Close popup when clicking outside
        document.getElementById('orderPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });
    </script>
</body>
</html>
