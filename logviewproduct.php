
<?php
session_start();
require_once 'Database.php';

// Get user info if logged in
$firstName = "User";
$cartCount = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get user name
    if (isset($_SESSION['user_name'])) {
        $parts = explode(' ', $_SESSION['user_name']);
        $firstName = $parts[0];
    }
    
    // Get cart count
    $cart_sql = "SELECT COUNT(*) as count FROM cart WHERE users_id = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    if ($cart_row = $cart_result->fetch_assoc()) {
        $cartCount = $cart_row['count'];
    }
    $cart_stmt->close();
}

if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
}

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    $sql = "
    SELECT
      p.product_id,
      p.name,
      p.main_category,
      p.subcategory,
      p.gender,
      IFNULL(p.price, 0.00) AS price,
      IFNULL(p.stock, 0) AS stock, 
      p.description,
      GROUP_CONCAT(DISTINCT pc.color SEPARATOR ',') AS colors,
      GROUP_CONCAT(DISTINCT ps.size  SEPARATOR ',') AS sizes
    FROM products p
    LEFT JOIN product_color pc ON pc.product_id = p.product_id
    LEFT JOIN product_size ps ON ps.product_id = p.product_id
    WHERE p.product_id = $product_id
    GROUP BY p.product_id
    ";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        $img_sql = "SELECT file_path FROM product_images WHERE product_id = $product_id ORDER BY sort_order ASC";
        $img_result = $conn->query($img_sql);
        $images = [];
        if ($img_result) {
            while ($row = $img_result->fetch_assoc()) {
                $images[] = $row['file_path'];
            }
        }
        if (empty($images)) {
            $images[] = 'image/default.png'; 
        }

        $colors = !empty($product['colors']) ? explode(',', $product['colors']) : [];
        $sizes = !empty($product['sizes']) ? explode(',', $product['sizes']) : [];

        // Fetch reviews
        $reviews_sql = "
        SELECT 
            r.review_id,
            r.rating,
            r.review_text,
            r.review_image,
            r.created_at,
            u.name as user_name
            -- u.profile_image 
        FROM product_reviews r
        JOIN users u ON r.user_id = u.users_id
        WHERE r.product_id = $product_id
        ORDER BY r.created_at DESC
        ";
        
        $reviews_result = $conn->query($reviews_sql);
        $reviews = [];
        $total_rating = 0;
        $review_count = 0;
        
        if ($reviews_result) {
            while ($row = $reviews_result->fetch_assoc()) {
                $reviews[] = $row;
                $total_rating += $row['rating'];
                $review_count++;
            }
        }
        
        $avg_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;

    } else {
        die('Product not found.');
    }
} else {
    die('Product ID not specified.');
}

if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
   
}


$genderFilter = $conn->real_escape_string($product['gender']);
$currentId = (int)$product['product_id'];

$sql = "
SELECT
  p.product_id,
  p.name,
  p.main_category,
  p.subcategory,
  p.gender,
  IFNULL(p.price, 0.00) AS price,
  IFNULL(p.stock, 0) AS stock, 
  p.description,
  (SELECT file_path FROM product_images WHERE product_id = p.product_id ORDER BY sort_order ASC LIMIT 1) AS image_path,
  GROUP_CONCAT(DISTINCT pc.color SEPARATOR ', ') AS colors,
  GROUP_CONCAT(DISTINCT ps.size  SEPARATOR ', ') AS sizes,
  COALESCE(AVG(r.rating), 0) as avg_rating
FROM products p
LEFT JOIN product_color pc ON pc.product_id = p.product_id
LEFT JOIN product_size ps ON ps.product_id = p.product_id
LEFT JOIN product_reviews r ON r.product_id = p.product_id
WHERE p.gender = '$genderFilter' AND p.product_id != $currentId
GROUP BY p.product_id
ORDER BY p.product_id DESC LIMIT 4
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
    <title><?php echo htmlspecialchars($product['name']); ?> - Product View</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="loghome.css">
    <link rel="stylesheet" href="logviewproduct.css?v=<?php echo time(); ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="logHome.php" class="logo"><img src="image/logo.png" alt="logo"></a>
            <button class="navbar-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            <!-- ... -->


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

    <!-- Search bar -->

  <div class="Search-container">
    <input type="text" placeholder="Search in Velvet Vogue" class="Search">
    <button onclick = "searchFunction()"><i class="fas fa-search"></i></button>
  </div>


    <div class="small-container single-product">
        <div class="row">
            <div class="col-2">
                <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="productImage">
                <div class="small-image-row">
                    <?php foreach ($images as $img): ?>
                    <div class="small-img-col">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="" class="small-img">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-2">
                <p>Home / <?php echo htmlspecialchars($product['main_category'] ?? ''); ?> / <?php echo htmlspecialchars($product['subcategory'] ?? ''); ?></p>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <h4>Rs. <?php echo number_format($product['price'], 2); ?></h4>

                <?php if (!empty($colors)): ?>
                <h4 class="color">Colour</h4>
                <div class="color-picker">
                    <?php foreach ($colors as $color): 
                        $color = trim($color);
                        $colorId = 'color' . ucfirst($color);
                    ?>
                    <div class="color-option">
                        <input type="radio" id="<?php echo $colorId; ?>" name="color" value="<?php echo htmlspecialchars($color); ?>" class="color-checkbox">
                        <label for="<?php echo $colorId; ?>" class="color-label" style="background-color: <?php echo htmlspecialchars($color); ?>; <?php echo strtolower($color) === 'white' ? 'border: 1px solid #ddd;' : ''; ?>">
                            <span class="color-name"><?php echo htmlspecialchars($color); ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($sizes)): ?>
                <h4 class="size">Size</h4>
                <div class="size-checkboxes">
                    <?php foreach ($sizes as $size): 
                        $size = trim($size);
                        $sizeId = 'size' . $size;
                    ?>
                    <input type="radio" id="<?php echo $sizeId; ?>" name="sizes[]" value="<?php echo htmlspecialchars($size); ?>" class="size-checkbox">
                    <label for="<?php echo $sizeId; ?>" class="size-label"><?php echo htmlspecialchars($size); ?></label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <h4 class="qty">Quantity</h4>
                <div class="qty-container">
                    <button type="button" class="qty-btn minus-btn" disabled>-</button>
                    <input type="text" class="qty-box" value="1">
                    <button type="button" class="qty-btn plus-btn">+</button>
                </div>
                
                <!-- Add to Cart Form -->
                <form action="add_to_cart.php" method="POST" id="addToCartForm">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <input type="hidden" name="qty" id="cartQty" value="1">
                    <input type="hidden" name="selected_color" id="cartColor" value="">
                    <input type="hidden" name="selected_size" id="cartSize" value="">
                </form>

                  <form action="payment.php" method="POST" id="buyNowForm">
                      <input type="hidden" name="buy_now" value="1">
                      <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                      <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                      <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                      <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($images[0]); ?>">
                      <input type="hidden" name="quantity" id="buyNowQty" value="1">
                      <input type="hidden" name="selected_color" id="buyNowColor" value="">
                      <input type="hidden" name="selected_size" id="buyNowSize" value="">
                      
                      <div class="btn-container">
                          <button type="button" class="buy-btn" onclick="validateAndBuyNow()">Buy Now</button>
                          <button type="button" class="cart-btn" onclick="validateAndAddToCart()">Add to Cart</button>
                      </div>
                  </form>
            </div>
        </div>
    </div>

  <div class="product-info-row">
      <div class="tab-container">
        <div class="tab-header">
            <button class="tab-button active" data-tab="description">Description</button>
            <button class="tab-button" data-tab="review">Review</button>
        </div>

        <div class="tab-content-area">
            <div id="description" class="tab-content active">
                <h2>Product Description</h2>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <div id="review" class="tab-content">
                <h2>Customer Reviews (<?php echo $review_count; ?>)</h2>
                <?php if ($review_count > 0): ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <span class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                    <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                            <?php if (!empty($review['review_image'])): ?>
                            <div class="review-image">
                                <img src="<?php echo htmlspecialchars($review['review_image']); ?>" alt="Review Image" class="review-img-thumb" onclick="window.open(this.src, '_blank')">
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                <?php endif; ?>
            </div>
        </div>
      </div>

      <div class="shipping">
        <div class="shipping-details">
            <h2>Shipping Details <span><i class="fa-sharp fa-solid fa-truck-fast"></i></span></h2>
            <p>Free shipping for orders over Rs 1000/-</p>
            <p>Delivery within 3-5 business days</p>
        </div>
      </div>
  </div>

  <!-- cards -->
  <h2 class="title" style="text-align:center; margin: 20px 0;">More <?php echo htmlspecialchars($product['gender']); ?>'s Clothing</h2>
  
<div class="card-container">
  <?php if ($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): 
      $nameVal = $row['name'];
      if (strlen($nameVal) > 40) {
          $nameVal = substr($nameVal, 0, 40) . '...';
      }
      $name = htmlspecialchars($nameVal);
      $price = number_format((float)$row['price'], 2);
      $img = !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : 'image/no-image.png';
      $rating = round($row['avg_rating']); 
    ?>
    <div class="card" 
         data-main-category="<?php echo htmlspecialchars($row['main_category'] ?? ''); ?>" 
         data-subcategory="<?php echo htmlspecialchars($row['subcategory'] ?? ''); ?>" 
         data-gender="<?php echo htmlspecialchars($row['gender']); ?>">
      <a href="logviewproduct.php?id=<?php echo $row['product_id']; ?>">
        <img src="<?php echo $img; ?>" alt="<?php echo $name; ?>">
      </a>
      <div class="card-content">
        <a href="logviewproduct.php?id=<?php echo $row['product_id']; ?>" style="text-decoration: none; color: inherit;">
          <p><?php echo $name; ?></p>
          <h3>Rs. <?php echo $price; ?></h3>
        </a>
        <div class="stars">
          <?php for($i = 1; $i <= 5; $i++): ?>
            <?php if ($i <= $rating): ?>
                <i class="fas fa-star" style="color: #ffc107;"></i>
            <?php else: ?>
                <i class="far fa-star" style="color: #ccc;"></i>
            <?php endif; ?>
          <?php endfor; ?>
          <span style="font-size: 12px; color: #777;">(<?php echo number_format($row['avg_rating'], 1); ?>)</span>
        </div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align:center; width:100%;">No products found.</p>
  <?php endif; ?>
</div>
      
    <script src="logviewproduct.js?v=<?php echo time(); ?>"></script>
    <script>
        // Check if colors and sizes exist
        const hasColors = <?php echo !empty($colors) ? 'true' : 'false'; ?>;
        const hasSizes = <?php echo !empty($sizes) ? 'true' : 'false'; ?>;
        
        // Validation function for both buttons
        function validateSelection() {
            let isValid = true;
            let message = '';
            
            // Check color selection if colors exist
            if (hasColors) {
                const selectedColor = document.getElementById('buyNowColor').value || document.getElementById('cartColor').value;
                if (!selectedColor) {
                    message += 'Please select a color.\n';
                    isValid = false;
                }
            }
            
            // Check size selection if sizes exist
            if (hasSizes) {
                const selectedSize = document.getElementById('buyNowSize').value || document.getElementById('cartSize').value;
                if (!selectedSize) {
                    message += 'Please select a size.';
                    isValid = false;
                }
            }
            
            if (!isValid) {
                alert(message);
            }
            
            return isValid;
        }
        
        // Validate and Buy Now
        function validateAndBuyNow() {
            if (validateSelection()) {
                document.getElementById('buyNowForm').submit();
            }
        }
        
        // Validate and Add to Cart
        function validateAndAddToCart() {
            if (validateSelection()) {
                document.getElementById('addToCartForm').submit();
            }
        }
        
        // Update hidden fields when user selects color, size, or quantity
        document.addEventListener('DOMContentLoaded', function() {
            const colorInputs = document.querySelectorAll('input[name="color"]');
            const sizeInputs = document.querySelectorAll('input[name="sizes[]"]');
            const qtyInput = document.querySelector('.qty-box');
            
            // Update color for both forms
            colorInputs.forEach(input => {
                input.addEventListener('change', function() {
                    document.getElementById('buyNowColor').value = this.value;
                    document.getElementById('cartColor').value = this.value;
                });
            });
            
            // Update size for both forms
            sizeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    document.getElementById('buyNowSize').value = this.value;
                    document.getElementById('cartSize').value = this.value;
                });
            });
            
            // Update quantity for both forms
            function updateQty() {
                document.getElementById('buyNowQty').value = qtyInput.value;
                document.getElementById('cartQty').value = qtyInput.value;
            }
            
            if (qtyInput) {
                qtyInput.addEventListener('input', updateQty);
            }
            
            // Also update when plus/minus buttons are clicked
            const plusBtn = document.querySelector('.plus-btn');
            const minusBtn = document.querySelector('.minus-btn');
            
            if (plusBtn) {
                plusBtn.addEventListener('click', function() {
                    setTimeout(updateQty, 10);
                });
            }
            
            if (minusBtn) {
                minusBtn.addEventListener('click', function() {
                    setTimeout(updateQty, 10);
                });
            }
        });
    </script>
    <script src="loghome.js"></script>
</body>

</html>