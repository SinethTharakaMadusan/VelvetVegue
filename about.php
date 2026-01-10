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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About</title>
    <link rel="stylesheet" href="about.css">
    <link rel="stylesheet" href="loghome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
</head>

<body>

    <!-- Start Navigation -->
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


            <!-- <button class="regbtn">Register/Login</button> -->

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

    <!-- End Navigation -->


    <div class="banner">
        <img src="image/about.png" alt="banner">
    </div>

    <section class="content">
        <div class="content-container">
            <div class="mission">
                <h2>Our Mission</h2>
                <div class="mission-container">
                    <img src="image/mision.png" alt="">
                    <p>To inspire confidence and self-expression by delivering trendy, high-quality fashion with
                        a seamless online shopping experience.</p>
                </div>
            </div>

            <div class="mission">
                <h2>Our Service</h2>
                <div class="mission-container">

                    <p>To inspire confidence and self-expression by delivering trendy,
                        high-quality fashion with a seamless online shopping experience.</p>
                    <img src="image/service.png" alt="">
                </div>
            </div>

            <div class="mission">
                <h2>Why Choose Us</h2>
                <div class="mission-container">
                    <img src="image/choose.png" alt="">
                    <p>Modern fashion, user-friendly design, secure transactions, and collections
                        made for today’s young lifestyle.</p>
                </div>
            </div>

            <div class="mission">
                <h2>Customer Commitment</h2>
                <div class="mission-container">
                    <p>We are committed to quality, security, and customer satisfaction through reliable
                        service and continuous improvement.</p>

                    <img src="image/commitment.png" alt="">
                </div>
            </div>
        </div>
    </section>
<!-- start footer -->
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





<!-- end footer -->



<script src="loghome.js"></script>
</body>

</html>