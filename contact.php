<?php
session_start();
require_once 'Database.php';

// Get user first name
$firstName = "Guest";
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_name'])) {
        $parts = explode(' ', $_SESSION['user_name']);
        $firstName = $parts[0];
    }
    
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="loghome.css">
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
        <li><a class="active" href="contact.php">Contact/Help</a></li>
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
                    
                    <a class="logout" href="Home.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    
                </div>
            </div>

        </div>

   </nav>

    <div class="container" style="padding: 100px 20px; text-align: center;">
        <h1>Contact Us</h1>
        <p>This page is under construction. Please check back later.</p>
    </div>

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
    <!-- Admin Log link removed per user request interpretation -->
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
