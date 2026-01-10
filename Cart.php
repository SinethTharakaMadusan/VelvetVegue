<?php
session_start();
require_once 'Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Log.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$firstName = "User";
if (isset($_SESSION['user_name'])) {
    $parts = explode(' ', $_SESSION['user_name']);
    $firstName = $parts[0];
}

// Fetch cart items with color and size
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
WHERE c.users_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$subtotal = 0;

while ($row = $result->fetch_assoc()) {
    $row['total_price'] = $row['price'] * $row['qty'];
    $subtotal += $row['total_price'];
    $cartItems[] = $row;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <!-- <link rel="stylesheet" href="home.css"> -->
    <link rel="stylesheet" href="cart.css">
    <link rel="stylesheet" href="loghome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
     <link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
/>

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
        <li><a class="active" href="logHome.php">Home</a></li>
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
                <span class="count"><?php echo count($cartItems); ?></span>
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
                    <a class="logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

        </div>
             
      

    </div>

   </nav>

   
<form action="payment.php" method="POST" id="checkoutForm">
<div class="cart-page">
    <div class="cart-left">
        <h2>My Shopping Cart</h2>

        <?php if (empty($cartItems)): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <?php foreach ($cartItems as $item): 
                $img = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'image/no-image.png';
            ?>
            <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
                <input type="checkbox" class="select-item" name="cart_ids[]" value="<?php echo $item['cart_id']; ?>" checked>

                <div class="item-info">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="item-details">
                        <p class="name"><?php 
                            $productName = htmlspecialchars($item['name']);
                            echo strlen($productName) > 45 ? substr($productName, 0, 45) . '...' : $productName;
                        ?></p>
                        <?php if (!empty($item['selected_color']) || !empty($item['selected_size'])): ?>
                        <p class="variant">
                            <?php if (!empty($item['selected_color'])): ?>
                                <span class="color-badge" style="background-color: <?php echo htmlspecialchars($item['selected_color']); ?>; display: inline-block; width: 15px; height: 15px; border-radius: 50%; border: 1px solid #ccc; vertical-align: middle;"></span>
                                <span><?php echo htmlspecialchars($item['selected_color']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['selected_size'])): ?>
                                <span style="margin-left: 10px;"><strong>Size:</strong> <?php echo htmlspecialchars($item['selected_size']); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="price">Rs. <span class="item-price"><?php echo number_format($item['price'], 2); ?></span></div>
                
                    <div class="qty-container">
                        <button type="button" class="qty-btn minus-btn" data-id="<?php echo $item['cart_id']; ?>">-</button>
                        <input type="text" class="qty-box" value="<?php echo $item['qty']; ?>" readonly>
                        <button type="button" class="qty-btn plus-btn" data-id="<?php echo $item['cart_id']; ?>">+</button>
                    </div>

                <div class="deletebtn">
                    
                    <button type="button" class="remove-btn" data-id="<?php echo $item['cart_id']; ?>"><i class="fas fa-trash"></i></button>
                    
                    
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cart-right">
        <h3>Order Summary</h3>

            <div class="summmary-row">
                <span>Subtotal</span>
                <span>Rs. <span id="subtotal"><?php echo number_format($subtotal, 2); ?></span></span>
            </div>

                <hr>

            <div class="summary-row total">
                <span>Total</span>
                <span>Rs. <span id="total"><?php echo number_format($subtotal, 2); ?></span></span>
            </div>

            <button type="submit" class="checkout-btn">CHECKOUT</button>
    </div>


</div>
</form>


   <script src="Cart.js"></script>
   <script src="loghome.js"></script>

</body>
</html>