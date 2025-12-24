
<?php
require_once 'Database.php';

// Check if delete request
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    // Optional: Add deletion logic here or redirect to a delete script
    // For now, we'll just redirect to avoid re-submission issues if logic were here
    // header("Location: products.php"); 
}

// Check if product_id is set
if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    // Fetch product details
    $sql = "
    SELECT
      p.product_id,
      p.name,
      p.category,
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
        
        // Fetch images for this product
        $img_sql = "SELECT file_path FROM product_images WHERE product_id = $product_id ORDER BY sort_order ASC";
        $img_result = $conn->query($img_sql);
        $images = [];
        if ($img_result) {
            while ($row = $img_result->fetch_assoc()) {
                $images[] = $row['file_path'];
            }
        }
        // Ensure at least one image exists to avoid errors
        if (empty($images)) {
            $images[] = 'image/default.png'; // Fallback image
        }

        // Parse colors and sizes
        $colors = !empty($product['colors']) ? explode(',', $product['colors']) : [];
        $sizes = !empty($product['sizes']) ? explode(',', $product['sizes']) : [];

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
  p.category,
  p.gender,
  IFNULL(p.price, 0.00) AS price,
  IFNULL(p.stock, 0) AS stock, 
  p.description,
  (SELECT file_path FROM product_images WHERE product_id = p.product_id ORDER BY sort_order ASC LIMIT 1) AS image_path,
  GROUP_CONCAT(DISTINCT pc.color SEPARATOR ', ') AS colors,
  GROUP_CONCAT(DISTINCT ps.size  SEPARATOR ', ') AS sizes
FROM products p
LEFT JOIN product_color pc ON pc.product_id = p.product_id
LEFT JOIN product_size ps ON ps.product_id = p.product_id
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
    <link rel="stylesheet" href="viewproduct.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="Home.php" class="logo"><img src="image/logo.png" alt="logo"></a>
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
                <p>Home / <?php echo htmlspecialchars($product['category']); ?></p>
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
                
                <a href="#" class="btn">Add To Cart</a>

                  <div class="btn-container">
                        <a href="Register.php" class="buy-btn">Buy Now</a>
                        <a href="Register.php" class="cart-btn">Add to Cart</a>
                    </div>
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
                <h2>Customer Reviews (5)</h2>
                <p>Great quality and fast shipping!</p>
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
    ?>
    <div class="card" data-category="<?php echo htmlspecialchars($row['category']); ?>" data-gender="<?php echo htmlspecialchars($row['gender']); ?>">
      <a href="viewproduct.php?id=<?php echo $row['product_id']; ?>">
        <img src="<?php echo $img; ?>" alt="<?php echo $name; ?>">
      </a>
      <div class="card-content">
        <a href="viewproduct.php?id=<?php echo $row['product_id']; ?>" style="text-decoration: none; color: inherit;">
          <p><?php echo $name; ?></p>
          <h3>Rs. <?php echo $price; ?></h3>
        </a>
        <div class="stars">
          <i class="fas fa-star"></i>
          <i class="fas fa-star"></i>
          <i class="fas fa-star"></i>
          <i class="fas fa-star"></i>
          <i class="fas fa-star"></i>
        </div>
        <div class="card-buttons">
          <a href="#" class="cart">Add to Cart</a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align:center; width:100%;">No products found.</p>
  <?php endif; ?>
</div>
      
    <script src="viewproducts.js?v=<?php echo time(); ?>"></script>
</body>

</html>