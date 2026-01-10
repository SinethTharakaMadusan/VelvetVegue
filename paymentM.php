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

// Get data from POST
$cartItems = [];
if (isset($_POST['cart_items']) && is_array($_POST['cart_items'])) {
    foreach ($_POST['cart_items'] as $item_json) {
        $cartItems[] = json_decode($item_json, true);
    }
}

$subtotal = $_POST['subtotal'] ?? 0;
$deliveryFee = $_POST['delivery_fee'] ?? 0;
$total = $_POST['total'] ?? 0;

$fullName = $_POST['full_name'] ?? '';
$userPhone = $_POST['user_phone'] ?? '';
$userAddress = $_POST['user_address'] ?? '';

// Get cart count for nav
$count_sql = "SELECT COUNT(*) as count FROM cart WHERE users_id = ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_count = $stmt->get_result();
$cartCount = 0;
if ($row_count = $result_count->fetch_assoc()) {
    $cartCount = $row_count['count'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Method</title>
    <link rel="stylesheet" href="loghome.css">
    <link rel="stylesheet" href="paymentM.css">
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

    <?php
    // Display error message if any
    if (isset($_SESSION['payment_error'])) {
        echo '<div class="error-message" style="max-width: 800px; margin: 20px auto; padding: 15px; background-color: #fee; border: 1px solid #fcc; border-radius: 5px; color: #c00; text-align: center;">';
        echo htmlspecialchars($_SESSION['payment_error']);
        echo '</div>';
        unset($_SESSION['payment_error']);
    }
    ?>

    <form action="process_payment.php" method="POST" id="paymentForm">
        <!-- Hidden fields for order data -->
        <?php foreach ($cartItems as $item): ?>
            <input type="hidden" name="cart_items[]" value='<?php echo htmlspecialchars(json_encode($item)); ?>'>
        <?php endforeach; ?>
        
        <input type="hidden" name="subtotal" value="<?php echo $subtotal; ?>">
        <input type="hidden" name="delivery_fee" value="<?php echo $deliveryFee; ?>">
        <input type="hidden" name="total" value="<?php echo $total; ?>">
        <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>">
        <input type="hidden" name="user_phone" value="<?php echo htmlspecialchars($userPhone); ?>">
        <input type="hidden" name="user_address" value="<?php echo htmlspecialchars($userAddress); ?>">

    <div class="payment-method-container">
        <div class="left-section">
            <h2>Select Payment Method</h2>
            

             <div class="payment-option">
                    <input type="radio" name="payment_method" id="cod" value="cod" checked>
                    <label for="cod">
                        <i class="fas fa-money-bill-wave"></i>
                        Cash on Delivery
                    </label>
                </div>

            <div class="payment-options">
                <div class="payment-option">
                    <input type="radio" name="payment_method" id="card" value="card">
                    <label for="card">
                        <i class="fas fa-credit-card"></i>
                        Credit/Debit Card
                    </label>
                </div>

               

            </div>

            <!-- Card Details Form (Shows when card is selected) -->
            <div class="card-details-form" id="cardDetailsForm">
                <h3>Enter Card Details</h3>
                <div class="form-group">
                    <label for="cardNumber">Card Number</label>
                    <input type="text" id="cardNumber" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="form-group">
                    <label for="cardHolder">Card Holder Name</label>
                    <input type="text" id="cardHolder" name="card_holder" placeholder="John Doe">
                </div>
                <div class="form-group-split">
                    <div class="expiry-group">
                        <label>Expiry Date</label>
                        <div class="expiry-inputs">
                            <input type="text" id="expiryMonth" name="expiry_month" placeholder="MM" maxlength="2">
                            <span>/</span>
                            <input type="text" id="expiryYear" name="expiry_year" placeholder="YY" maxlength="2">
                        </div>
                    </div>
                    <div class="cvv-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3">
                    </div>
                </div>
            </div>

            <div class="shipping-info">
                <h3>Shipping Information</h3>
                <p><strong><?php echo htmlspecialchars($fullName); ?></strong></p>
                <p><?php echo htmlspecialchars($userPhone); ?></p>
                <p><?php echo htmlspecialchars($userAddress); ?></p>
            </div>
        </div>

        <div class="right-section">
            <h2>Order Summary</h2>
            
            <div class="order-items">
                <?php if (!empty($cartItems)): ?>
                    <?php foreach ($cartItems as $item): 
                        $img = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'image/no-image.png';
                    ?>
                    <div class="order-item">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <p class="item-name"><?php echo htmlspecialchars($item['name']); ?></p>
                            <p class="item-qty">Qty: <?php echo $item['qty']; ?></p>
                        </div>
                        <p class="item-price">Rs. <?php echo number_format($item['price'], 2); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>Rs. <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery:</span>
                    <span>Rs. <?php echo number_format($deliveryFee, 2); ?></span>
                </div>
                <hr>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>Rs. <?php echo number_format($total, 2); ?></span>
                </div>
            </div>

            <button type="submit" class="place-order-btn">Place Order</button>
        </div>
    </div>
    </form>

    <script src="loghome.js"></script>
    <script>
        // Payment method toggle
        const cardRadio = document.getElementById('card');
        const codRadio = document.getElementById('cod');
        const bankRadio = document.getElementById('bank');
        const cardDetailsForm = document.getElementById('cardDetailsForm');

        console.log('Card Radio:', cardRadio);
        console.log('Card Details Form:', cardDetailsForm);

        // Show/hide card form based on payment method
        function toggleCardForm() {
            console.log('Toggle called, card checked:', cardRadio.checked);
            
            if (cardRadio.checked) {
                cardDetailsForm.style.display = 'block';
                console.log('Showing card form');
            } else {
                cardDetailsForm.style.display = 'none';
                console.log('Hiding card form');
            }
        }

        // Add event listeners
        if (cardRadio) cardRadio.addEventListener('change', toggleCardForm);
        if (codRadio) codRadio.addEventListener('change', toggleCardForm);
        if (bankRadio) bankRadio.addEventListener('change', toggleCardForm);

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, initializing...');
            toggleCardForm();
        });
        
        // Also try to run immediately
        toggleCardForm();

        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            if (paymentMethod === 'card') {
                // Validate card fields
                const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                const cardHolder = document.getElementById('cardHolder').value;
                const expiryMonth = document.getElementById('expiryMonth').value;
                const expiryYear = document.getElementById('expiryYear').value;
                const cvv = document.getElementById('cvv').value;
                
                if (!cardNumber || cardNumber.length !== 16 || !/^\d+$/.test(cardNumber)) {
                    alert('Please enter a valid 16-digit card number.');
                    e.preventDefault();
                    return false;
                }
                
                if (!cardHolder || cardHolder.trim().length < 3) {
                    alert('Please enter the cardholder name.');
                    e.preventDefault();
                    return false;
                }
                
                if (!expiryMonth || !expiryYear || !/^\d{2}$/.test(expiryMonth) || !/^\d{2}$/.test(expiryYear)) {
                    alert('Please enter a valid expiry date (MM/YY).');
                    e.preventDefault();
                    return false;
                }
                
                if (!cvv || cvv.length !== 3 || !/^\d+$/.test(cvv)) {
                    alert('Please enter a valid 3-digit CVV.');
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });

        // Auto-format card number with spaces
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Only allow digits in card number
        document.getElementById('cardNumber').addEventListener('keypress', function(e) {
            if (!/\d/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete') {
                e.preventDefault();
            }
        });

        // Only allow digits in expiry and CVV
        ['expiryMonth', 'expiryYear', 'cvv'].forEach(id => {
            const elem = document.getElementById(id);
            if (elem) {
                elem.addEventListener('keypress', function(e) {
                    if (!/\d/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete') {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
