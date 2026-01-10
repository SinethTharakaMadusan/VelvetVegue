<?php
session_start();
require_once 'Database.php';

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
  GROUP_CONCAT(DISTINCT pc.color SEPARATOR ', ') AS colors,
  GROUP_CONCAT(DISTINCT ps.size  SEPARATOR ', ') AS sizes,
  COALESCE(AVG(r.rating), 0) as avg_rating
FROM products p
LEFT JOIN product_color pc ON pc.product_id = p.product_id
LEFT JOIN product_size ps ON ps.product_id = p.product_id
LEFT JOIN product_reviews r ON r.product_id = p.product_id
GROUP BY p.product_id
ORDER BY p.product_id DESC
";

$result = $conn->query($sql);
if (!$result) {
    die('DB error: ' . $conn->error);
}

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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Product</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="allproducts.css">
    <!-- <link rel="stylesheet" href="loghome.css"> -->
    <link rel="stylesheet" href="loghome.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
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
                <li><a href="#">Shop</a></li>
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



    <!-- End Navigation  -->

    <!-- start side filter -->
    <div class="page">
        <aside class="sidebar" id="filterSidebar">
            <div class="sidebar-header">
                <button class="close-sidebar" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
                <h2>Filter</h2>
            </div>



            <div class="filter-section">
                <div class="filter-header" onclick="toggleSection(this)">
                    <h4>Main Category</h4>
                    <span class="arrow"><i class="fa-solid fa-caret-down"></i></span>
                </div>
                <div class="filter-content">
                    <label><input type="radio" name="mainCategory" value="all" checked onchange="updateSubcategoryFilter()">All</label>
                    <label><input type="radio" name="mainCategory" value="menswear" onchange="updateSubcategoryFilter()">Menswear</label>
                    <label><input type="radio" name="mainCategory" value="womenswear" onchange="updateSubcategoryFilter()">Womenswear</label>
                    <label><input type="radio" name="mainCategory" value="shoes" onchange="updateSubcategoryFilter()">Shoes</label>
                </div>
            </div>

            <div class="filter-section">
                <div class="filter-header" onclick="toggleSection(this)">
                    <h4>Subcategory</h4>
                    <span class="arrow"><i class="fa-solid fa-caret-down"></i></span>
                </div>
                <div class="filter-content" id="subcategoryFilters">
                    <label><input type="radio" name="subcategory" value="all" checked>All</label>
                </div>
            </div>
            <div class="filter-section">
                <div class="filter-header" onclick="toggleSection(this)">
                    <h4>Price Range</h4>
                    <span class="arrow"><i class="fa-solid fa-caret-down"></i></span>
                </div>
                <div class="filter-content">
                    <div id="current-price" style="text-align: center; font-weight: 600; color: #244551; margin-bottom: 10px;">
                        Max: LKR 15,000
                    </div>
                    <input type="range" min="0" max="15000" value="15000" id="price-slider">
                    <div class="bar-level">
                        <span>LKR 0</span>
                        <span>LKR 15, 000</span>
                    </div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <!-- Start search bar -->
            <div class="top-controls">
                <button class="filter-toggle-btn" onclick="toggleSidebar()">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <div class="Search-bar">
                    <input type="text" placeholder="Search in Velvet Vogue" class="Search">
                    <button onclick="searchFunction()"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <!-- End search bar -->

            <div id="products-container" class="products-grid card-container">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $nameVal = $row['name'];
                        if (strlen($nameVal) > 40) {
                            $nameVal = substr($nameVal, 0, 40) . '...';
                        }
                        $name = htmlspecialchars($nameVal);
                        $price = number_format((float)$row['price'], 2);
                        $priceRaw = (float)$row['price']; 
                        $img = !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : 'image/no-image.png';
                        $gender = htmlspecialchars(strtolower($row['gender'] ?? '')); 
                        $mainCategory = htmlspecialchars(strtolower($row['main_category'] ?? ''));
                        $subcategory = htmlspecialchars(strtolower($row['subcategory'] ?? ''));
                        $rating = round($row['avg_rating']); 
                    ?>
                    <div class="card" 
                         data-gender="<?php echo $gender; ?>" 
                         data-main-category="<?php echo $mainCategory; ?>"
                         data-subcategory="<?php echo $subcategory; ?>"
                         data-price="<?php echo $priceRaw; ?>">
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
                    <p style="text-align:center; width:100%; padding: 40px; color: #666;">No products found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>




    <!-- End search bar -->













    <script src="loghome.js"></script>
    <script src="allproducts.js"></script>
</body>

</html>