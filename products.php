<?php
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
GROUP BY p.product_id
ORDER BY p.product_id DESC
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
    <title>Admin Panel - Products</title>
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-container">
                <img class="logo" src="image/logo.png" alt="Logo">
            </div>
            <div class="menu-items">
                <a href="Admin.html"><i class="fas fa-home"></i> Dashboard</a>
                <a href="products.html" class="active"><i class="fas fa-box"></i> Products</a>
                <a href="orders.html"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="customers.html"><i class="fas fa-users"></i> Customers</a>
                <a href="#"><i class="fas fa-chart-bar"></i> Analytics</a>
                <a href="#"><i class="fas fa-cog"></i> Settings</a>
            </div>
            <div class="user-profile">
                <img src="image/user.png" alt="User" class="user-avatar">
                <div class="user-info">
                    <h4>Admin User</h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Products Management</h1>
                <button onclick="openPopup()" class="add-product-btn">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
            </div>

            <div id="popup" class="popup-bg">
                <div class="popup-content">
                    <span class="close-btn" onclick="closePopup()">&times;</span>
                    <h3>Add New Product</h3>
                    <form id="addProductForm" action="add_product.php" method="POST" enctype="multipart/form-data">

                        <input type="text" name="name" placeholder="Product Title" required>
                        
                        <select name="category" id="category">
                            <option class="Choose" value="">--Choose Category</option>
                            <option value="casual">Casual Weare</option>
                            <option value="formal">Formal Wear</option>
                            <option value="accessories">Accessories</option>
                            <option value="footwear">Footwear</option>
                        </select>
                        <select name="gender" id="gender" class="gender">
                            <option class="Choose" value="">--Choose Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <div class="form-group">
                            <textarea name="description" placeholder="Product Description" required></textarea>
                        </div>

                        <div class="form-group">
                            <input type="text" name="price" placeholder="Price (LKR)" required>
                        </div>

                        <div class="form-group">
                            <input type="text" name="stock" placeholder="Stock" required>
                        </div>

                        <div class="form-group">
                            <h3>Available Colors</h3>
                            <div class="color-picker">
                                <div class="color-option">
                                    <input type="checkbox" id="colorBlack" name="colors[]" value="black" class="color-checkbox">
                                    <label for="colorBlack" class="color-label" style="background-color: #000000;">
                                        <span class="color-name">Black</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorWhite" name="colors[]" value="white" class="color-checkbox">
                                    <label for="colorWhite" class="color-label" style="background-color: #FFFFFF; border: 1px solid #ddd;">
                                        <span class="color-name">White</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorNavy" name="colors[]" value="navy" class="color-checkbox">
                                    <label for="colorNavy" class="color-label" style="background-color: #000080;">
                                        <span class="color-name">Navy</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorGrey" name="colors[]" value="grey" class="color-checkbox">
                                    <label for="colorGrey" class="color-label" style="background-color: #808080;">
                                        <span class="color-name">Grey</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorBeige" name="colors[]" value="beige" class="color-checkbox">
                                    <label for="colorBeige" class="color-label" style="background-color: #F5F5DC;">
                                        <span class="color-name">Beige</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorRed" name="colors[]" value="red" class="color-checkbox">
                                    <label for="colorRed" class="color-label" style="background-color: #FF0000;">
                                        <span class="color-name">Red</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorBlue" name="colors[]" value="blue" class="color-checkbox">
                                    <label for="colorBlue" class="color-label" style="background-color: #0000FF;">
                                        <span class="color-name">Blue</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorGreen" name="colors[]" value="green" class="color-checkbox">
                                    <label for="colorGreen" class="color-label" style="background-color: #008000;">
                                        <span class="color-name">Green</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorPink" name="colors[]" value="pink" class="color-checkbox">
                                    <label for="colorPink" class="color-label" style="background-color: #FFC0CB;">
                                        <span class="color-name">Pink</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorPurple" name="colors[]" value="purple" class="color-checkbox">
                                    <label for="colorPurple" class="color-label" style="background-color: #800080;">
                                        <span class="color-name">Purple</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorBrown" name="colors[]" value="brown" class="color-checkbox">
                                    <label for="colorBrown" class="color-label" style="background-color: #8B4513;">
                                        <span class="color-name">Brown</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorKhaki" name="colors[]" value="khaki" class="color-checkbox">
                                    <label for="colorKhaki" class="color-label" style="background-color: #F0E68C;">
                                        <span class="color-name">Khaki</span>
                                    </label>
                                </div>

                                <div class="color-option">
                                    <input type="checkbox" id="colorother" name="colors[]" value="other" class="color-checkbox">
                                    <label for="colorother" class="color-label" style="background-color: #FFFFFF;">
                                        <span class="color-name">Other</span>
                                    </label>
                                </div>
                            </div>
                        </div> <br>

                        <h3>Available Sizes</h3>
                        <div class="size-checkboxes">
                            <input type="checkbox" id="sizeXS" name="sizes[]" value="XS" class="size-checkbox">
                            <label for="sizeXS" class="size-label">XS</label>
                            
                            <input type="checkbox" id="sizeS" name="sizes[]" value="S" class="size-checkbox">
                            <label for="sizeS" class="size-label">S</label>
                            
                            <input type="checkbox" id="sizeM" name="sizes[]" value="M" class="size-checkbox">
                            <label for="sizeM" class="size-label">M</label>
                            
                            <input type="checkbox" id="sizeL" name="sizes[]" value="L" class="size-checkbox">
                            <label for="sizeL" class="size-label">L</label>
                            
                            <input type="checkbox" id="sizeXL" name="sizes[]" value="XL" class="size-checkbox">
                            <label for="sizeXL" class="size-label">XL</label>
                            
                            <input type="checkbox" id="sizeXXL" name="sizes[]" value="XXL" class="size-checkbox">
                            <label for="sizeXXL" class="size-label">XXL</label>
                        </div>
                        
                        <div class="product-images">
                            <h3>Upload up to 4 product photo</h3>
                            <div class="upload-grid">
            
                                <label class="upload-box">
                                <input type="file" name="images[]" accept="image/*" onchange="previewImg(this,0)">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload</span>
                                <img id="preview0" class="preview-img">
                                <button type="button" class="remove-btn" onclick="removeImg(0)">×</button>
                                </label>

                                <label class="upload-box">
                                <input type="file" name="images[]" accept="image/*" onchange="previewImg(this,1)">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload</span>
                                <img id="preview1" class="preview-img">
                                <button type="button" class="remove-btn" onclick="removeImg(1)">×</button>
                                </label>

                                <label class="upload-box">
                                <input type="file" name="images[]" accept="image/*" onchange="previewImg(this,2)">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload</span>
                                <img id="preview2" class="preview-img">
                                <button type="button" class="remove-btn" onclick="removeImg(2)">×</button>
                                </label>

                                <label class="upload-box">
                                <input type="file" name="images[]" accept="image/*" onchange="previewImg(this,3)">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload</span>
                                <img id="preview3" class="preview-img">
                                <button type="button" class="remove-btn" onclick="removeImg(3)">×</button>
                                </label>
                            </div>
                        </div>

                        <button class="submit" type="submit">Save Product</button>
                        <button type="button" onclick="closePopup()" class="cancel">Cancel</button>
                           
                    </form>

                </div>
            </div>

            <div class="product-filters">
                <div class="search-container">
                     <button onclick="searchFunction()"><i class="fas fa-search search-icon"></i></button>
                    <input type="text" placeholder="Search products..." class="search-input">
                </div>
                <div class="filter-group">
                    <select class="filter-select">
                        <option value="">All Categories</option>
                        <option value="men">Men's Fashion</option>
                        <option value="women">Women's Fashion</option>
                        <option value="accessories">Accessories</option>
                        <option value="footwear">Footwear</option>
                    </select>
                    <select class="filter-select">
                        <option value="">Status</option>
                        <option value="in-stock">In Stock</option>
                        <option value="low-stock">Low Stock</option>
                        <option value="out-of-stock">Out of Stock</option>
                    </select>
                    <button class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <div class="products-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $id    = (int)$row['product_id'];
                        $name  = htmlspecialchars($row['name']);
                        $cat   = htmlspecialchars($row['category']);
                        $price = number_format((float)$row['price'], 2);
                        $stock = (int)$row['stock'];
                        $img   = !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : 'image/no-image.png';
                        $colors = $row['colors'] ? htmlspecialchars($row['colors']) : '';
                        $sizes  = $row['sizes']  ? htmlspecialchars($row['sizes'])  : '';
                        
                        // Determine stock class
                        $stockClass = 'out-of-stock';
                        $stockText = 'Out of Stock';
                        if ($stock > 10) {
                            $stockClass = 'in-stock';
                            $stockText = 'In Stock: ' . $stock;
                        } elseif ($stock > 0) {
                            $stockClass = 'low-stock';
                            $stockText = 'Low Stock: ' . $stock;
                        }
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" onerror="this.src='image/no-image.png'">
                            <div class="product-actions">
                                <button class="edit-btn" onclick="window.location.href='edit_product.php?id=<?php echo $id; ?>'">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="delete-btn" onclick="window.location.href='delete_product.php?id=<?php echo $id; ?>'">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">


                        
                            <h3><?php echo $name; ?></h3>
                            <p class="category"><?php echo $cat; ?></p>
                            <div class="product-stats">
                                <span class="price">LKR <?php echo $price; ?></span>
                                <span class="stock <?php echo $stockClass; ?>"><?php echo $stockText; ?></span>
                            </div>
                            <?php if($colors): ?><p><small>Colors: <?php echo $colors; ?></small></p><?php endif; ?>
                            <?php if($sizes): ?><p><small>Sizes: <?php echo $sizes; ?></small></p><?php endif; ?>
                            <p><small>ID: <?php echo $id; ?></small></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center;">No products found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="product.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
</body>
</html>