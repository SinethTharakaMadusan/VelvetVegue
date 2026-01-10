<?php
session_start();
require_once 'Database.php';

// Check if delete request
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    // Optional: Add deletion logic here or redirect to a delete script
    // For now, we'll just redirect to avoid re-submission issues if logic were here
    // header("Location: products.php"); 
}

// Fetch products
// Note: 'stock' column is missing in the provided schema, defaulting to 0.
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
GROUP BY p.product_id
ORDER BY p.product_id DESC LIMIT 8
";

$result = $conn->query($sql);
if (!$result) {
    die('DB error: ' . $conn->error);
}

// Get user first name
$firstName = "Guest";
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_name'])) {
        $parts = explode(' ', $_SESSION['user_name']);
        $firstName = $parts[0];
    }
    
    // Count cart items
    $count_sql = "SELECT COUNT(*) as count FROM cart WHERE users_id = ?";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result_count = $stmt->get_result();
    if ($row_count = $result_count->fetch_assoc()) {
        $cartCount = $row_count['count'];
    }
    $stmt->close();
}

// Fetch recent reviews for homepage display
$reviews_sql = "
SELECT 
    r.review_id,
    r.rating,
    r.review_text,
    r.review_image,
    r.created_at,
    u.name as user_name
FROM product_reviews r
INNER JOIN users u ON r.user_id = u.users_id
WHERE r.review_text IS NOT NULL AND r.review_text != ''
ORDER BY r.created_at DESC
LIMIT 3
";

$reviews_result = $conn->query($reviews_sql);
$reviews = [];
if ($reviews_result && $reviews_result->num_rows > 0) {
    while ($review_row = $reviews_result->fetch_assoc()) {
        $reviews[] = $review_row;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VelvatVogue</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <!-- <link rel="stylesheet" href="home.css"> -->
    <link rel="stylesheet" href="loghome.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> -->
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
        <li><a href="contact.php">Contact/Help</a></li>
       </ul>

        
        <!-- <button class="regbtn">Register/Login</button> -->

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
                    
                    <a class="logout" href="Home.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    
                </div>
            </div>

        </div>

   </nav>

   <section class="main-banner">
    
    <div class="banner-content">
        <h1>Wear nature, 
            <br>Wear you</h1>
        <h2>30% OFF</h2>
        
        <a href="#" class="orderbtn">Order Now</a>
  
       
    </div>
</section>

    <div class="features-container">
  <div class="feature-box">
    <i class="fas fa-truck"></i>
    <!-- <span class="divider"></span> -->
    <a href="#" class="shipping">FREE SHIPPING</a>
  </div>

  <div class="feature-box">
    <i class="fas fa-headset"></i>
    <span class="divider"></span>
    <a href="#" class="support">24/7 SUPPORT</a>
  </div>

  <div class="feature-box">
    <i class="fas fa-undo-alt"></i>
    <span class="divider"></span>
    <a href="#" class="return">EASY RETURNS</a>
  </div>

  </div>

<!-- Search bar -->

  <div class="Search-container">
    <input type="text" placeholder="Search in Velvet Vogue" class="Search">
    <button onclick = "searchFunction()"><i class="fas fa-search"></i></button>
  </div>

  

<!-- categories -->
<!-- 
<div class="wrapper">
  
  <div class="parent">
    <div class="child bg1"></div>
    <button class="category-btn" onclick="location.href='menscasual.html'">See Item ...</button>

    
  </div>
  <div class="parent">
    <div class="child bg2"></div>
    <button class="category-btn" onclick="location.href='mensformal.html'">See Item ...</button>
    
   
  </div>
  <div class="parent">
    <div class="child bg3" ></div>
    <button class="category-btn" onclick="location.href= 'womenscasual.html'">See Item ...</button>

  </div>
  <div class="parent">
    <div class="child bg4"></div>
    <button class="category-btn" onclick="location.href ='womensformal.html'">See Item ...</button>
  
  </div>
</div> -->

<!-- card  -->

<h2>#Our Best Collections</h2>

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
        <div class="card-buttons">
          <form action="add_to_cart.php" method="POST" style="width: 100%;">
            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
            <input type="hidden" name="qty" value="1">
            <input type="hidden" name="selected_color" value="">
            <input type="hidden" name="selected_size" value="">
          </form>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align:center; width:100%;">No products found.</p>
  <?php endif; ?>
</div>
<!-- <button class="See"> See More ...</button> -->
  
<!-- promotion -->
 <div class="promo-container">
<h1># Flash Sale <span><i class="fa-solid fa-bolt-lightning"></i></span></h1>
<img src="image/promo.png" alt="promotion" class="promo">
</div>

<!-- Feedback -->

<section class="review" id="review">
  <h2 class="customer-review-title"># Customer's Review</h2>
  <div class="reviews-container">
    
    <?php if (!empty($reviews)): ?>
      <?php foreach ($reviews as $review): 
        // Extract user first name for display
        $review_user_parts = explode(' ', $review['user_name']);
        $review_first_name = $review_user_parts[0];
        
        // Create username handle  
        $username_handle = '@' . strtolower(str_replace(' ', '', $review['user_name']));
        
        // Get rating value
        $rating = (int)$review['rating'];
        
        // Format date
        $review_date = date('M d, Y', strtotime($review['created_at']));
      ?>
      <div class="review-card">
        <div class="card-top">
          <div class="profile">
            <div class="profile-img">
              <img src="image/fb1.png" alt="<?php echo htmlspecialchars($review['user_name']); ?>">
            </div>
            <div class="name-user">
              <strong><?php echo htmlspecialchars($review_first_name); ?></strong>
              <span><?php echo htmlspecialchars($username_handle); ?></span>
            </div>
          </div>
          <div class="reviews">
            <?php 
            // Display filled stars based on rating
            for ($i = 1; $i <= 5; $i++): 
              if ($i <= $rating): ?>
                <i class="fas fa-star"></i>
              <?php else: ?>
                <i class="far fa-star"></i>
              <?php endif;
            endfor; 
            ?>
          </div>
        </div>
        <div class="client-comment">
          <p><?php echo htmlspecialchars($review['review_text']); ?></p>
          

          
          <small style="color: #999; font-size: 0.85rem; margin-top: 10px; display: block;"><?php echo $review_date; ?></small>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- Fallback when no reviews exist -->
      <div class="no-reviews" style="text-align: center; padding: 40px; color: #777;">
        <p>No customer reviews yet. Be the first to review a product!</p>
      </div>
    <?php endif; ?>

  </div>
</section>

<footer>
   
  
  <div class="container">
<div class="footer-content">
    <img src="image/logo.png" alt="logo" class="footer-logo">
  </div>
   



    <div class="footer-content">
      <h3>Contact Us</h3>
      <p><i class="fa-regular fa-envelope"></i>Email : support@velvetvogue.com</p>
      <p><i class="fas fa-phone"></i> +1 234 567 890</p>
      <p><i class="fas fa-map-marker-alt"></i> 123 Velvet St, Fashion City, FC 12345</p>
  </div>

  <div class="footer-content">
    <h3>Quick Links</h3>
    <ul class="list">
    <li><a href="logHome.php">Home</a></li>
    <li><a href="allproducts.php">Shop</a></li>
    <li><a href="about.php">About Us</a></li>
    <li><a href="contact.php">Contact/Help</a></li>
    <li><a href="admin_login.php">Admin Log</a></li>

    </ul>
  </div>



  <div class="footer-content">
    
    <h3>Follow Us</h3>
    <ul class="social-icons">
    <li><a href=""><i class="fa-brands fa-facebook"></i></a></li>
    <li><a href=""><i class="fa-brands fa-square-x-twitter"></i></a></li>
    <li><a href=""><i class="fa-brands fa-square-instagram"></i></a></li>
    <li><a href=""><i class="fa-brands fa-square-linkedin"></i></a></li>
    </ul>

  </div>
  </div>

  
  
</footer>
<div class="bottom-bar">
    <p>&copy; 2024 VelvetVogue. All rights reserved.</p>
  </div>







<script src="loghome.js"></script>
</body>
</html>