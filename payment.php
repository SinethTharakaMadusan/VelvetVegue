<?php
session_start();
require_once 'Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Log.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$firstName = "User";
$fullName = "User";
$userPhone = "";
$userAddress = "";


if (isset($_SESSION['user_name'])) {
    $fullName = $_SESSION['user_name'];
    $parts = explode(' ', $_SESSION['user_name']);
    $firstName = $parts[0];
}


$address_sql = "SELECT full_name, phone_number, address_line1, city, state, zip_code 
                FROM users_details 
                WHERE users_id = ? 
                LIMIT 1";
$address_stmt = $conn->prepare($address_sql);
$address_stmt->bind_param("i", $user_id);
$address_stmt->execute();
$address_result = $address_stmt->get_result();

if ($address_row = $address_result->fetch_assoc()) {
    $fullName = $address_row['full_name'] ?: $fullName;
    $userPhone = $address_row['phone_number'] ?: '';
    $userAddress = $address_row['address_line1'] . ', ' . $address_row['city'] . ', ' . $address_row['state'] . ' ' . $address_row['zip_code'];
}
$address_stmt->close();

$cartItems = [];
$subtotal = 0;


if (isset($_POST['buy_now']) && $_POST['buy_now'] == '1') {
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $product_name = isset($_POST['product_name']) ? $_POST['product_name'] : '';
    $product_price = isset($_POST['product_price']) ? (float)$_POST['product_price'] : 0;
    $product_image = isset($_POST['product_image']) ? $_POST['product_image'] : 'image/no-image.png';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $selected_color = isset($_POST['selected_color']) ? $_POST['selected_color'] : '';
    $selected_size = isset($_POST['selected_size']) ? $_POST['selected_size'] : '';
    
    if ($product_id > 0) {
        $item = [
            'cart_id' => 0, 
            'product_id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'qty' => $quantity,
            'image_path' => $product_image,
            'selected_color' => $selected_color,
            'selected_size' => $selected_size,
            'total_price' => $product_price * $quantity
        ];
        $cartItems[] = $item;
        $subtotal = $item['total_price'];
    } else {
        header("Location: logHome.php");
        exit();
    }
} else {
    
    $selectedCartIds = [];
    if (isset($_POST['cart_ids']) && is_array($_POST['cart_ids'])) {
        $selectedCartIds = array_map('intval', $_POST['cart_ids']);
    }

    
    if (empty($selectedCartIds)) {
        header("Location: cart.php");
        exit();
    }

    
    $placeholders = implode(',', array_fill(0, count($selectedCartIds), '?'));

    $sql = "
    SELECT 
        c.cart_id, 
        c.qty, 
        c.selected_color,
        c.selected_size,
        p.product_id, 
        p.name, 
        p.price, 
        (SELECT file_path FROM product_images WHERE product_id = p.product_id ORDER BY sort_order ASC LIMIT 1) AS image_path
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.users_id = ? AND c.cart_id IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);

    // Bind user_id first, then all selected cart IDs
    $types = 'i' . str_repeat('i', count($selectedCartIds));
    $params = array_merge([$user_id], $selectedCartIds);
    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['total_price'] = $row['price'] * $row['qty'];
        $subtotal += $row['total_price'];
        $cartItems[] = $row;
    }

    $stmt->close();
}

// Calculate totals
$deliveryFee = 0; // Free shipping
$total = $subtotal + $deliveryFee;

// Get cart count for nav
$cartCount = count($cartItems);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Options</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="loghome.css">
    <link rel="stylesheet" href="payment.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="logo"><img src="image/logo.png" alt="logo"></a>
            <button class="navbar-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <ul class="navbar-menu">
                <li><a href="logHome.php">Home</a></li>
                <li><a href="allproducts.php">Shop</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="#">Contact/Help</a></li>
            </ul>

            <div class="icon-bar">
                <div class="user-name">
                    <h3>Welcome,</h3>
                    <p><?php echo htmlspecialchars($firstName); ?></p>
                </div>

                <a href="cart.php" class="icon-box">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="count"><?php echo $cartCount; ?></span>
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
                        <a class="logout" href="Home.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="popup">
        <div class="address">
            <h3>Personal Details</h3>
            <form class="address-form">
                <input type="text" placeholder="Full Name" required>  
                <input type="text" placeholder="Phone Number" required> 
                <input type="text" placeholder="Address" required> 
                <input type="text" placeholder="City" required> 
                <input type="text" placeholder="State" required> 
                <input type="text" placeholder="Zip Code" required> 
                <button type="submit">Save</button> 
            </form>
        </div>
    </div>

    <div class="payment-layout">
        <div class="payment-left">
            <div class="billing-container">
                <div class="billing-header">
                    <span class="header-title">Shipping and Billing</span>
                    <button class="edit-button">Change</button>
                </div>

                <div class="billing-content">
                    <p class="name-phone">
                        <?php echo htmlspecialchars($fullName); ?> 
                        <?php if ($userPhone): ?>
                            <span class="phone"><?php echo htmlspecialchars($userPhone); ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="address">
                        <?php echo !empty($userAddress) ? htmlspecialchars($userAddress) : ''; ?>
                    </p>
                </div>
            </div>

            <div class="package-container">
                <div class="package-header">
                    <span class="header-title">Packages</span>
                </div>

                <div class="delivery-section">
                    <h3 class="delivery-title">Delivery option</h3>

                    <div class="delivery-box selected-option">
                        <p class="shipping-cost">Free Shipping</p>
                        <p class="shipping-time">3-5 Business Days</p>
                    </div>
                </div>

                <?php if (!empty($cartItems)): ?>
                    <?php foreach ($cartItems as $item): 
                        $img = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'image/no-image.png';
                    ?>
                <div class="product-summery">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                        <div class="product-details">
                            <p class="product-name"><?php echo htmlspecialchars($item['name']); ?></p>
                            <p class="product-sku">Quantity: <?php echo $item['qty']; ?></p>
                            <?php if (!empty($item['selected_color'])): ?>
                                <p class="product-sku">Color: <?php echo htmlspecialchars($item['selected_color']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['selected_size'])): ?>
                                <p class="product-sku">Size: <?php echo htmlspecialchars($item['selected_size']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="price-details">
                            <p class="original-price">Rs. <?php echo number_format($item['price'], 2); ?></p>
                            <p class="quantity">Qty: <?php echo $item['qty']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="padding: 20px; text-align: center;">No items in cart.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="order-summary">
            <h2 class="summary-title">Order Summary</h2>

            <div class="summary-row">
                <span class="label">Items Total</span>
                <span class="value">Rs. <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="summary-row">
                <span class="label">Delivery Fee</span>
                <span class="value">Rs. <?php echo number_format($deliveryFee, 2); ?></span>
            </div>

            <hr class="summary-separator">

            <div class="summary-row totle-row">
                <span class="label totle-label">Total</span>
                <span class="value totle-value">Rs. <?php echo number_format($total, 2); ?></span>
            </div>

            <form action="paymentM.php" method="POST">
                <!-- Cart Items Data -->
                <?php foreach ($cartItems as $item): ?>
                    <input type="hidden" name="cart_items[]" value="<?php echo htmlspecialchars(json_encode($item)); ?>">
                <?php endforeach; ?>
                
                <!-- Order Totals -->
                <input type="hidden" name="subtotal" value="<?php echo $subtotal; ?>">
                <input type="hidden" name="delivery_fee" value="<?php echo $deliveryFee; ?>">
                <input type="hidden" name="total" value="<?php echo $total; ?>">
                
                <!-- User Details -->
                <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>">
                <input type="hidden" name="user_phone" value="<?php echo htmlspecialchars($userPhone); ?>">
                <input type="hidden" name="user_address" value="<?php echo htmlspecialchars($userAddress); ?>">
                
                <button type="button" class="proceed-button" onclick="validateAddress()">
                    Proceed to Pay
                </button>
            </form>
        </div>
    </div>

    <script src="loghome.js"></script>
    <script>
        // Validate address before proceeding
        function validateAddress() {
            const addressValue = document.querySelector('input[name="user_address"]').value.trim();
            const phoneValue = document.querySelector('input[name="user_phone"]').value.trim();
            
            if (!addressValue || addressValue === ', , ') {
                alert('Please add your shipping address before proceeding to payment.\n\nClick the "Change" button to enter your address details.');
                return false;
            }
            
            if (!phoneValue) {
                alert('Please add your phone number before proceeding to payment.\n\nClick the "Change" button to enter your details.');
                return false;
            }
            
            // If validation passes, submit the form
            document.querySelector('form[action="paymentM.php"]').submit();
        }

        const popup = document.querySelector('.popup');
        const editButton = document.querySelector('.edit-button');
        const addressForm = document.querySelector('.address-form');

        if (editButton) {
            editButton.addEventListener('click', function() {
                
                const namePhone = document.querySelector('.name-phone');
                const addressLine = document.querySelector('.billing-content .address');
                
                if (namePhone && addressForm) {
                    const inputs = addressForm.querySelectorAll('input');
                    
                   
                    const nameText = namePhone.childNodes[0]?.textContent?.trim() || '';
                    const phoneText = namePhone.querySelector('.phone')?.textContent?.trim() || '';
                    
                    const addressText = addressLine?.textContent?.trim() || '';
                    const addressParts = addressText.split(', ');
                    
                    if (inputs[0]) inputs[0].value = nameText; 
                    if (inputs[1]) inputs[1].value = phoneText; 
                    if (inputs[2] && addressParts[0]) inputs[2].value = addressParts[0]; 
                    if (inputs[3] && addressParts[1]) inputs[3].value = addressParts[1]; 
                    
                    if (addressParts[2]) {
                        const stateZip = addressParts[2].split(' ');
                        if (inputs[4] && stateZip[0]) inputs[4].value = stateZip[0]; 
                        if (inputs[5] && stateZip[1]) inputs[5].value = stateZip[1]; 
                    }
                }
                
                popup.classList.add('show');
            });
        }

        
        if (popup) {
            popup.addEventListener('click', function(e) {
                if (e.target === popup) {
                    popup.classList.remove('show');
                }
            });
        }

        
        if (addressForm) {
            addressForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                
                const formData = new FormData();
                const inputs = addressForm.querySelectorAll('input');
                
                formData.append('full_name', inputs[0].value);
                formData.append('phone_number', inputs[1].value);
                formData.append('address', inputs[2].value);
                formData.append('city', inputs[3].value);
                formData.append('state', inputs[4].value);
                formData.append('zip_code', inputs[5].value);

                
                fetch('save_address.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        
                        const namePhone = document.querySelector('.name-phone');
                        const addressLine = document.querySelector('.billing-content .address');
                        
                        if (namePhone && data.data) {
                            namePhone.innerHTML = data.data.full_name + ' <span class="phone">' + data.data.phone_number + '</span>';
                        }
                        
                        if (addressLine && data.data) {
                            const fullAddress = data.data.address + ', ' + data.data.city + ', ' + data.data.state + ' ' + data.data.zip_code;
                            addressLine.textContent = fullAddress;
                            
                            document.querySelector('input[name="user_address"]').value = fullAddress;
                            document.querySelector('input[name="user_phone"]').value = data.data.phone_number;
                            document.querySelector('input[name="full_name"]').value = data.data.full_name;
                        }
                        
                        alert('Address saved successfully!');
                        popup.classList.remove('show');
                        
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to save address. Please try again.');
                });
            });
        }
    </script>
</body>

</html>