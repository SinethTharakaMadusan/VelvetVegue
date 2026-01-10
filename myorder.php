<?php
session_start();

// Database connection
include 'Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$firstName = "Guest";
$userName = "";
$profileImage = NULL; // Will be NULL if no profile image
$cartCount = 0;

// Get user's name and profile image from session or database
if (isset($_SESSION['user_name'])) {
    $userName = $_SESSION['user_name'];
    $parts = explode(' ', $_SESSION['user_name']);
    $firstName = $parts[0];
}

// Fetch profile image from database
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
if ($check_column->num_rows > 0) {
    // Column exists, fetch profile image
    $profile_sql = "SELECT profile_image FROM users WHERE Users_id = ?";
    $profile_stmt = $conn->prepare($profile_sql);
    $profile_stmt->bind_param("i", $user_id);
    $profile_stmt->execute();
    $profile_result = $profile_stmt->get_result();
    if ($profile_row = $profile_result->fetch_assoc()) {
        $profileImage = !empty($profile_row['profile_image']) ? $profile_row['profile_image'] : NULL;
    }
    $profile_stmt->close();
}

// Get cart count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE users_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $cartCount = $row['count'];
}
$stmt->close();

// Fetch orders for the logged-in user
$orders_sql = "
SELECT 
    o.order_id,
    o.order_date,
    o.order_status,
    o.total_amount,
    o.payment_method,
    DATE_ADD(o.order_date, INTERVAL 3 DAY) as delivery_date,
    oi.product_id,
    oi.qty,
    oi.unit_price,
    oi.selected_color,
    oi.selected_size,
    p.name as product_name,
    (SELECT file_path FROM product_images WHERE product_id = p.product_id ORDER BY sort_order ASC LIMIT 1) AS product_image
FROM orders o
INNER JOIN order_items oi ON o.order_id = oi.order_id
INNER JOIN products p ON oi.product_id = p.product_id
WHERE o.user_id = ?
ORDER BY o.order_date DESC, o.order_id DESC
";

$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="myorder.css">
    <link rel="stylesheet" href="Account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="loghome.css">
</head>

<body>

    <!-- start navigation -->


    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="logo"><img src="image/logo.png" alt="logo"></a>
            <button class="navbar-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <ul class="navbar-menu">
                <li><a class="active" href="logHome.php">Home</a></li>
                <li><a href="allproducts.php">Shop</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="#">Contact/Help</a></li>
            </ul>


            <div class="icon-bar">

                <div class="user-name">
                    <h3>Welcome,</h3>
                    <p>
                        <?php echo htmlspecialchars($firstName); ?>
                    </p>
                </div>
                <a href="cart.php" class="icon-box">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="count">
                        <?php echo $cartCount; ?>
                    </span>
                </a>

                <div class="user-menu-container">
                    <a href="javascript:void(0)" class="icon-box user" id="userBtn">
                        <i class="fas fa-user"></i>
                    </a>
                    <div class="user-dropdown" id="userMenu">

                        <a href="myorder.php"><i class="fas fa-box"></i> My Orders</a>
                        <a href="#"><i class="fas fa-heart"></i> Wishlist</a>
                        <a href="Account.php"><i class="fas fa-user-cog"></i> Profile</a>
                        <hr>

                        <a class="logout" href="Home.php"><i class="fas fa-sign-out-alt"></i>Logout</a>

                    </div>
                </div>

            </div>

    </nav>
    <!-- end navigation  -->

    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="account-container">
        <div class="sidebar" id="sidebar">
            <div class="user-profile">
                <div class="profile-image-wrapper">
                    <?php if ($profileImage): ?>
                        <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="User">
                    <?php else: ?>
                        <div class="default-profile-icon">
                            <i class="fa fa-user-circle"></i>
                        </div>
                    <?php endif; ?>
                    <label for="file-input" class="edit-icon">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </label>
                    <input type="file" id="file-input">
                </div>
                <h3><?php echo htmlspecialchars($userName); ?></h3>
            </div>
            <nav>
                <a href="Account.php"><i class="fa fa-user"></i> My Profile</a>
                <a href="myorder.php" class="active"><i class="fa fa-shopping-bag"></i> My Orders</a>
                <a href="#"><i class="fa fa-heart"></i> Wishlist</a>
                <a href="address.php"><i class="fa fa-map-marker"></i> Addresses</a>
                <hr>
                <a href="logout.php" class="logout"><i class="fa fa-sign-out"></i> Logout</a>
            </nav>
        </div>

        <div class="main-content">
            <h2>My Orders</h2>

            <?php if (isset($_SESSION['review_success'])): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['review_success']; unset($_SESSION['review_success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['review_error'])): ?>
                <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['review_error']; unset($_SESSION['review_error']); ?>
                </div>
            <?php endif; ?>

            <div class="containt">
                <?php if ($orders_result->num_rows > 0): ?>
                    <?php while($order = $orders_result->fetch_assoc()): 
                        // Prepare data
                        $product_image = !empty($order['product_image']) ? htmlspecialchars($order['product_image']) : 'image/no-image.png';
                        $product_name = htmlspecialchars($order['product_name']);
                        if (strlen($product_name) > 50) {
                            $product_name = substr($product_name, 0, 50) . '...';
                        }
                        $order_date_formatted = date('F d, Y', strtotime($order['order_date']));
                        $delivery_date_formatted = date('F d, Y', strtotime($order['delivery_date']));
                        $unit_price_formatted = number_format($order['unit_price'], 2);
                        
                        // Use actual order status from database (admin updates this)
                        $delivery_status = $order['order_status'];
                        $delivery_method = htmlspecialchars($order['payment_method']); // Get actual delivery/payment method from database
                    ?>
                <div class="order">
                    <div class="details">
                        <img src="<?php echo $product_image; ?>" alt="<?php echo $product_name; ?>">

                        <div class="product">
                            <p><?php echo $product_name; ?></p>
                            <div class="select">
                                <p>Color: <span><?php echo htmlspecialchars($order['selected_color'] ?: 'N/A'); ?></span></p>
                                <p>Size: <span><?php echo htmlspecialchars($order['selected_size'] ?: 'N/A'); ?></span></p>
                                <p>Price: <span>LKR <?php echo $unit_price_formatted; ?></span></p>
                                <p>Qty: <span><?php echo $order['qty']; ?></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="order-info">

                        <p>Order ID: <span><?php echo $order['order_id']; ?></span></p>
                        <p>Order Date: <span><?php echo $order_date_formatted; ?></span></p>
                        <p>Delivered: <span><?php echo $delivery_date_formatted; ?></span></p>
                    </div>
                    <div class="order-items">
                        
                    </div>

                    <div class="status">
                        <p>Delivery Method: <span><?php echo $delivery_method; ?></span></p>
                        <p>Delivery Status: <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $delivery_status)); ?>"><?php echo $delivery_status; ?></span></p>

                    </div>

                    <div class="button">
                        <?php if ($delivery_status === 'Delivered'): 
                            // Check if user already reviewed this product
                            $review_check_sql = "SELECT review_id FROM product_reviews WHERE user_id = ? AND product_id = ?";
                            $review_check_stmt = $conn->prepare($review_check_sql);
                            $review_check_stmt->bind_param("ii", $user_id, $order['product_id']);
                            $review_check_stmt->execute();
                            $review_check_result = $review_check_stmt->get_result();
                            $already_reviewed = $review_check_result->num_rows > 0;
                            $review_check_stmt->close();
                        ?>
                            <?php if ($already_reviewed): ?>
                                <button class="reviewed-btn" disabled>
                                    <i class="fas fa-check"></i> Reviewed
                                </button>
                            <?php else: ?>
                                <a href="review.php?order_id=<?php echo $order['order_id']; ?>&product_id=<?php echo $order['product_id']; ?>" class="review-btn">Review</a>
                            <?php endif; ?>
                        <?php elseif ($delivery_status === 'Pending' || $delivery_status === 'Processing' || $delivery_status === 'Order Placed'): ?>
                            <button type="button" class="cancel-btn" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">Cancel Order</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-orders">
                        <p style="text-align: center; padding: 40px; font-size: 18px; color: #666;">You haven't placed any orders yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- end account container -->

    <script>
        // Handle profile image upload
        document.getElementById('file-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, or GIF)');
                return;
            }

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }

            // Upload the file
            const formData = new FormData();
            formData.append('profile_image', file);

            fetch('upload_profile_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile image updated successfully!');
                    // Reload page to show new image
                    location.reload();
                } else {
                    alert('Upload failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while uploading the image');
            });
        });

        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('sidebar-open');
            overlay.classList.toggle('active');
        }
    </script>
    <script src="myorder.js"></script>

















    <script src="loghome.js"></script>
    <script src="myorder.js"></script>
</body>

</html>