<?php
session_start();

// Database connection
include 'Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$firstName = "Guest";
$cartCount = 0;

// Get user's first name from session
if (isset($_SESSION['user_name'])) {
    $parts = explode(' ', $_SESSION['user_name']);
    $firstName = $parts[0];
}

// Get cart count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE users_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $cartCount = $row['count'];
}
$stmt->close();

// Get order_id and product_id from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Initialize product data
$product_data = null;

if ($order_id > 0 && $product_id > 0) {
    // Fetch product and order details
    $sql = "
    SELECT 
        o.order_id,
        o.order_date,
        DATE_ADD(o.order_date, INTERVAL 3 DAY) as delivery_date,
        oi.product_id,
        oi.selected_color,
        oi.selected_size,
        p.name as product_name,
        (SELECT file_path FROM product_images WHERE product_id = p.product_id ORDER BY sort_order ASC LIMIT 1) AS product_image
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    INNER JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_id = ? AND oi.product_id = ? AND o.user_id = ?
    LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $order_id, $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product_data = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review</title>
    <link rel="stylesheet" href="review.css">
    <link rel="stylesheet" href="loghome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
</head>

<body>
    <!-- start navigation  -->

    <nav class="navbar">
        <div class="navbar-container">
            <a href="logHome.php" class="logo"><img src="image/logo.png" alt="logo"></a>
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
                        <a class="logout" href="Home.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>






    <!-- end navigation  -->

    <!-- start form -->

    <div class="review-container">
        <h2 class="review-header">Write Review</h2>
        <div class="review-form">

            <?php if ($product_data): 
                $product_image = !empty($product_data['product_image']) ? htmlspecialchars($product_data['product_image']) : 'image/no-image.png';
                $product_name = htmlspecialchars($product_data['product_name']);
                $delivery_date_formatted = date('d M Y', strtotime($product_data['delivery_date']));
                $order_date_formatted = date('d M Y', strtotime($product_data['order_date']));
            ?>
            <div class="product">
                <div class="date">
                    <p>Ordered on <span><?php echo $order_date_formatted; ?></span></p>
                    <p>Delivered on <span><?php echo $delivery_date_formatted; ?></span></p>
                </div>

                <div class="product-content">
                    <div class="product-image">
                        <img src="<?php echo $product_image; ?>" alt="<?php echo $product_name; ?>">
                    </div>
                    <div class="product-info">
                        <p class="product-title"><?php echo $product_name; ?></p>
                        <div class="selected">
                            <p>Color: <span><?php echo htmlspecialchars($product_data['selected_color'] ?: 'N/A'); ?></span></p>
                            <p>Size: <span><?php echo htmlspecialchars($product_data['selected_size'] ?: 'N/A'); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
            <form class="review" method="POST" action="submit_review.php" enctype="multipart/form-data">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <?php else: ?>
            <div class="no-product">
                <p>No product found for review. Please go back to <a href="myorder.php">My Orders</a> and select a product to review.</p>
            </div>
            <form class="review" style="display: none;">
            <?php endif; ?>
                <div class="rating">
                    <input type="radio" name="star" id="star5" value="5" onclick="sendRating(5)"><label for="star5">★</label>
                    <input type="radio" name="star" id="star4" value="4" onclick="sendRating(4)"><label for="star4">★</label>
                    <input type="radio" name="star" id="star3" value="3" onclick="sendRating(3)"><label for="star3">★</label>
                    <input type="radio" name="star" id="star2" value="2" onclick="sendRating(2)"><label for="star2">★</label>
                    <input type="radio" name="star" id="star1" value="1" onclick="sendRating(1)"><label for="star1">★</label>
                </div>

                <textarea id="reviewDetails" name="review_text" placeholder="What do you think of this product?"></textarea>

                <div class="upload-section">
                    <label for="fileUpload" class="upload-box" id="uploadBox">
                        <input type="file" id="fileUpload" name="review_image" accept="image/*">
                        <i class="fas fa-cloud-upload-alt" id="uploadIcon"></i>
                        <span id="uploadText">Upload Photo</span>
                        <img id="imagePreview" class="preview-img" src="" alt="Preview">
                        <button type="button" class="remove-btn" id="removeBtn" onclick="removeImage(event)">×</button>
                    </label>
                </div>

                <button class="submit-review">Submit Review</button>

                <div class="important-notes">
                <h4>Important:</h4>
                <ul>
                    <li>Maximum 6 images can be uploaded</li>
                    <li>Image size can be maximum 5mb</li>
                    <li>It takes upto 24 hours for the image to be reviewed</li>
                </ul>
                </div>
                
            </form>
        </div>
    </div>




    <!-- end form  -->

    <script>
        // Image preview functionality
        document.getElementById('fileUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file');
                    this.value = '';
                    return;
                }
                
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    const removeBtn = document.getElementById('removeBtn');
                    const uploadIcon = document.getElementById('uploadIcon');
                    const uploadText = document.getElementById('uploadText');
                    
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    removeBtn.style.display = 'flex';
                    uploadIcon.style.display = 'none';
                    uploadText.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
        
        function removeImage(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const fileInput = document.getElementById('fileUpload');
            const preview = document.getElementById('imagePreview');
            const removeBtn = document.getElementById('removeBtn');
            const uploadIcon = document.getElementById('uploadIcon');
            const uploadText = document.getElementById('uploadText');
            
            fileInput.value = '';
            preview.src = '';
            preview.style.display = 'none';
            removeBtn.style.display = 'none';
            uploadIcon.style.display = 'block';
            uploadText.style.display = 'block';
        }
    </script>

    <style>
        .upload-section {
            margin: 20px 0;
        }
        
        .upload-box {
            width: 120px;
            height: 120px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #f8fafc;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .upload-box:hover {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        
        .upload-box input {
            display: none;
        }
        
        .upload-box i {
            font-size: 28px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .upload-box span {
            font-size: 12px;
            color: #6b7280;
        }
        
        .preview-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            border-radius: 10px;
        }
        
        .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 14px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2;
            line-height: 1;
        }
        
        .remove-btn:hover {
            background: #dc2626;
        }
    </style>

</body>

</html>