<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="cart.css">
    <link rel="stylesheet" href="loghome.css">
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
        <li><a class="active" href="Home.html">Home</a></li>
        <li><a href="#">Shop</a></li>
        <li><a href="#">About Us</a></li>
        <li><a href="#">Contact/Help</a></li>
       </ul>

        
      
        <div class="icon-bar">

            <a href="#" class="icon-box">
                <i class="fas fa-shopping-cart"></i>
                <span class="count">3</span>
            </a>

            <div class="user-menu-container">
                 <a href="javascript:void(0)" class="icon-box user" id="userBtn">
                    <i class="fas fa-user"></i>
                </a>
                <div class="user-dropdown" id="userMenu">
                    
                     <a href="#"><i class="fas fa-box"></i> My Orders</a>
                    <a href="#"><i class="fas fa-heart"></i> Wishlist</a>
                    <a href="#"><i class="fas fa-user-cog"></i> Profile</a>
                    <hr>
                    <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

        </div>
             
      

    </div>

   </nav>

   
<div class="cart-page">
    <div class="cart-left">
        <h2>My Shopping Cart</h2>

        <div class="cart-item">
            <input type="checkbox" class="select-item" checked>

            <div class="item-info">
                <img src="image/itm1.webp" alt="">
                <p class="name"> New Men's Shirt Fashion Stripes print ...</p>
            </div>

            <div class="price">Rs. <span class="item-price">3,276</span></div>
            
                <div class="qty-container">
                    <button type="button" class="qty-btn minus-btn" disabled>-</button>
                    <input type="text" class="qty-box" value="1">
                    <button type="button" class="qty-btn plus-btn">+</button>
                </div>

            <div class="deletebtn">
                
                <button class="remove-btn"><i class="fas fa-trash"></i></button>
                
            </div>
        </div>
    </div>

    <div class="cart-right">
        <h3>Order Summary</h3>

            <div class="summmary-row">
                <span>Subtotal</span>
                <span>Rs. <span id="subtotal">0</span></span>
            </div>

    <hr>

            <div class="summary-row total">
                <span>Total</span>
                <span>Rs. <span id="total">0</span></span>
            </div>

            <button class="checkout-btn">CHECKOUT</button>
    </div>


</div>








   <script src="Cart.js"></script>
   <script src="logHome.js"></script>

</body>
</html>