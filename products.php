<?php
require_once 'Database.php';


if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
}


$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';


$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.product_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($category_filter)) {
    $where_conditions[] = "LOWER(p.main_category) LIKE LOWER(?)";
    $params[] = $category_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    if ($status_filter === 'in-stock') {
        $where_conditions[] = "p.stock > 10";
    } elseif ($status_filter === 'low-stock') {
        $where_conditions[] = "p.stock > 0 AND p.stock <= 10";
    } elseif ($status_filter === 'out-of-stock') {
        $where_conditions[] = "p.stock = 0";
    }
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';


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
  GROUP_CONCAT(DISTINCT ps.size  SEPARATOR ', ') AS sizes
FROM products p
LEFT JOIN product_color pc ON pc.product_id = p.product_id
LEFT JOIN product_size ps ON ps.product_id = p.product_id
$where_sql
GROUP BY p.product_id
ORDER BY p.product_id DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

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
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="products.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-container">
                <img class="logo" src="image/logo.png" alt="Logo">
            </div>
            <div class="menu-items">
                <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="products.php" class="active"><i class="fas fa-box"></i> Products</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
                <a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
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
                        
                        <select name="main_category" id="mainCategory" onchange="updateSubcategories()" required>
                            <option class="Choose" value="">--Choose Main Category--</option>
                            <option value="Menswear">Menswear</option>
                            <option value="Womenswear">Womenswear</option>
                            <option value="Shoes">Shoes</option>
                        </select>

                        <select name="subcategory" id="subcategory" required>
                            <option class="Choose" value="">--Choose Subcategory--</option>
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
                        
                        <!-- Clothing Sizes (for Menswear/Womenswear) -->
                        <div class="size-checkboxes" id="clothingSizes">
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

                        <!-- Shoe Sizes  -->
                        <div class="size-checkboxes" id="shoeSizes" style="display:none;">
                            <input type="checkbox" id="size38" name="sizes[]" value="38" class="size-checkbox">
                            <label for="size38" class="size-label">38</label>
                            
                            <input type="checkbox" id="size39" name="sizes[]" value="39" class="size-checkbox">
                            <label for="size39" class="size-label">39</label>
                            
                            <input type="checkbox" id="size40" name="sizes[]" value="40" class="size-checkbox">
                            <label for="size40" class="size-label">40</label>
                            
                            <input type="checkbox" id="size41" name="sizes[]" value="41" class="size-checkbox">
                            <label for="size41" class="size-label">41</label>
                            
                            <input type="checkbox" id="size42" name="sizes[]" value="42" class="size-checkbox">
                            <label for="size42" class="size-label">42</label>
                            
                            <input type="checkbox" id="size43" name="sizes[]" value="43" class="size-checkbox">
                            <label for="size43" class="size-label">43</label>
                            
                            <input type="checkbox" id="size44" name="sizes[]" value="44" class="size-checkbox">
                            <label for="size44" class="size-label">44</label>
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

            <form class="product-filters" method="GET" action="products.php">
                <div class="search-container">
                    <!-- <i class="fas fa-search search-icon"></i> -->
                    <input type="text" name="search" id="auto-search" placeholder="Search products..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select class="filter-select" name="category">
                        <option value="">All Categories</option>
                        <option value="Menswear" <?php echo $category_filter === 'Menswear' ? 'selected' : ''; ?>>Menswear</option>
                        <option value="Womenswear" <?php echo $category_filter === 'Womenswear' ? 'selected' : ''; ?>>Womenswear</option>
                        <option value="Shoes" <?php echo $category_filter === 'Shoes' ? 'selected' : ''; ?>>Shoes</option>
                    </select>
                    <select class="filter-select" name="status">
                        <option value="">All Status</option>
                        <option value="in-stock" <?php echo $status_filter === 'in-stock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="low-stock" <?php echo $status_filter === 'low-stock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="out-of-stock" <?php echo $status_filter === 'out-of-stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <?php if ($search || $category_filter || $status_filter): ?>
                    <a href="products.php" class="filter-btn" style="text-decoration:none; background:#666;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="products-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $id    = (int)$row['product_id'];
                        $name  = htmlspecialchars($row['name']);
                        $mainCat = htmlspecialchars($row['main_category'] ?? '');
                        $subCat = htmlspecialchars($row['subcategory'] ?? '');
                        $cat   = $mainCat . ($subCat ? ' > ' . $subCat : ''); // Combine for display
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
                                <button class="edit-btn" onclick="editProduct(<?php echo $id; ?>)">
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

    <!-- Edit Product Popup -->
    <div id="editPopup" class="popup-bg">
        <div class="popup-content">
            <span class="close-btn" onclick="closeEditPopup()">&times;</span>
            <h3>Edit Product</h3>
            <form id="editProductForm" action="update_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_product_id">
                
                <input type="text" name="name" id="edit_name" placeholder="Product Title" required>
                
                <select name="main_category" id="edit_mainCategory" onchange="updateEditSubcategories()" required>
                    <option class="Choose" value="">--Choose Main Category--</option>
                    <option value="Menswear">Menswear</option>
                    <option value="Womenswear">Womenswear</option>
                    <option value="Shoes">Shoes</option>
                </select>

                <select name="subcategory" id="edit_subcategory" required>
                    <option class="Choose" value="">--Choose Subcategory--</option>
                </select>

                <select name="gender" id="edit_gender" class="gender">
                    <option class="Choose" value="">--Choose Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                
                <div class="form-group">
                    <textarea name="description" id="edit_description" placeholder="Product Description" required></textarea>
                </div>

                <div class="form-group">
                    <input type="text" name="price" id="edit_price" placeholder="Price (LKR)" required>
                </div>

                <div class="form-group">
                    <input type="text" name="stock" id="edit_stock" placeholder="Stock" required>
                </div>

                <div class="form-group">
                    <h3>Available Colors</h3>
                    <div class="color-picker" id="edit_colors">
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorBlack" name="colors[]" value="black" class="color-checkbox">
                            <label for="edit_colorBlack" class="color-label" style="background-color: #000000;">
                                <span class="color-name">Black</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorWhite" name="colors[]" value="white" class="color-checkbox">
                            <label for="edit_colorWhite" class="color-label" style="background-color: #FFFFFF; border: 1px solid #ddd;">
                                <span class="color-name">White</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorNavy" name="colors[]" value="navy" class="color-checkbox">
                            <label for="edit_colorNavy" class="color-label" style="background-color: #000080;">
                                <span class="color-name">Navy</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorGrey" name="colors[]" value="grey" class="color-checkbox">
                            <label for="edit_colorGrey" class="color-label" style="background-color: #808080;">
                                <span class="color-name">Grey</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorBeige" name="colors[]" value="beige" class="color-checkbox">
                            <label for="edit_colorBeige" class="color-label" style="background-color: #F5F5DC;">
                                <span class="color-name">Beige</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorRed" name="colors[]" value="red" class="color-checkbox">
                            <label for="edit_colorRed" class="color-label" style="background-color: #FF0000;">
                                <span class="color-name">Red</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorBlue" name="colors[]" value="blue" class="color-checkbox">
                            <label for="edit_colorBlue" class="color-label" style="background-color: #0000FF;">
                                <span class="color-name">Blue</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorGreen" name="colors[]" value="green" class="color-checkbox">
                            <label for="edit_colorGreen" class="color-label" style="background-color: #008000;">
                                <span class="color-name">Green</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorPink" name="colors[]" value="pink" class="color-checkbox">
                            <label for="edit_colorPink" class="color-label" style="background-color: #FFC0CB;">
                                <span class="color-name">Pink</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorPurple" name="colors[]" value="purple" class="color-checkbox">
                            <label for="edit_colorPurple" class="color-label" style="background-color: #800080;">
                                <span class="color-name">Purple</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorBrown" name="colors[]" value="brown" class="color-checkbox">
                            <label for="edit_colorBrown" class="color-label" style="background-color: #8B4513;">
                                <span class="color-name">Brown</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="checkbox" id="edit_colorKhaki" name="colors[]" value="khaki" class="color-checkbox">
                            <label for="edit_colorKhaki" class="color-label" style="background-color: #F0E68C;">
                                <span class="color-name">Khaki</span>
                            </label>
                        </div>
                    </div>
                </div> <br>

                <h3>Available Sizes</h3>
                
                <!-- Clothing Sizes -->
                <div class="size-checkboxes" id="edit_clothingSizes">
                    <input type="checkbox" id="edit_sizeXS" name="sizes[]" value="XS" class="size-checkbox">
                    <label for="edit_sizeXS" class="size-label">XS</label>
                    
                    <input type="checkbox" id="edit_sizeS" name="sizes[]" value="S" class="size-checkbox">
                    <label for="edit_sizeS" class="size-label">S</label>
                    
                    <input type="checkbox" id="edit_sizeM" name="sizes[]" value="M" class="size-checkbox">
                    <label for="edit_sizeM" class="size-label">M</label>
                    
                    <input type="checkbox" id="edit_sizeL" name="sizes[]" value="L" class="size-checkbox">
                    <label for="edit_sizeL" class="size-label">L</label>
                    
                    <input type="checkbox" id="edit_sizeXL" name="sizes[]" value="XL" class="size-checkbox">
                    <label for="edit_sizeXL" class="size-label">XL</label>
                    
                    <input type="checkbox" id="edit_sizeXXL" name="sizes[]" value="XXL" class="size-checkbox">
                    <label for="edit_sizeXXL" class="size-label">XXL</label>
                </div>

                <!-- Shoe Sizes -->
                <div class="size-checkboxes" id="edit_shoeSizes" style="display:none;">
                    <input type="checkbox" id="edit_size38" name="sizes[]" value="38" class="size-checkbox">
                    <label for="edit_size38" class="size-label">38</label>
                    
                    <input type="checkbox" id="edit_size39" name="sizes[]" value="39" class="size-checkbox">
                    <label for="edit_size39" class="size-label">39</label>
                    
                    <input type="checkbox" id="edit_size40" name="sizes[]" value="40" class="size-checkbox">
                    <label for="edit_size40" class="size-label">40</label>
                    
                    <input type="checkbox" id="edit_size41" name="sizes[]" value="41" class="size-checkbox">
                    <label for="edit_size41" class="size-label">41</label>
                    
                    <input type="checkbox" id="edit_size42" name="sizes[]" value="42" class="size-checkbox">
                    <label for="edit_size42" class="size-label">42</label>
                    
                    <input type="checkbox" id="edit_size43" name="sizes[]" value="43" class="size-checkbox">
                    <label for="edit_size43" class="size-label">43</label>
                    
                    <input type="checkbox" id="edit_size44" name="sizes[]" value="44" class="size-checkbox">
                    <label for="edit_size44" class="size-label">44</label>
                </div>

                <!-- Product Images (Current + New Upload) -->
                <div class="product-images">
                    <h3>Product Images</h3>
                    <div id="product_images_grid" class="upload-grid"></div>
                </div>

                <button class="submit" type="submit">Update Product</button>
                <button type="button" onclick="closeEditPopup()" class="cancel">Cancel</button>
                   
            </form>
        </div>
    </div>
    
    <script src="product.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
    
    <script>
        // Edit product popup functions
        function editProduct(productId) {
            fetch('get_product.php?id=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.data;
                        
                        // Fill form fields
                        document.getElementById('edit_product_id').value = product.product_id;
                        document.getElementById('edit_name').value = product.name;
                        document.getElementById('edit_mainCategory').value = product.main_category;
                        document.getElementById('edit_description').value = product.description;
                        document.getElementById('edit_price').value = product.price;
                        document.getElementById('edit_stock').value = product.stock;
                        document.getElementById('edit_gender').value = product.gender || '';
                        
                        // Update subcategories and set value
                        updateEditSubcategories();
                        setTimeout(() => {
                            document.getElementById('edit_subcategory').value = product.subcategory;
                        }, 100);
                        
                        // Check colors
                        document.querySelectorAll('#editProductForm input[name="colors[]"]').forEach(cb => {
                            cb.checked = product.colors.includes(cb.value);
                        });
                        
                        // Check sizes
                        document.querySelectorAll('#editProductForm input[name="sizes[]"]').forEach(cb => {
                            cb.checked = product.sizes.includes(cb.value);
                        });
                        
                        // Show correct size options
                        if (product.main_category === 'Shoes') {
                            document.getElementById('edit_clothingSizes').style.display = 'none';
                            document.getElementById('edit_shoeSizes').style.display = 'flex';
                        } else {
                            document.getElementById('edit_clothingSizes').style.display = 'flex';
                            document.getElementById('edit_shoeSizes').style.display = 'none';
                        }
                        
                        // Build unified Product Images grid (current + upload boxes)
                        const imagesGrid = document.getElementById('product_images_grid');
                        imagesGrid.innerHTML = '';
                        currentProductId = product.product_id;
                        
                        let uploadIndex = 0;
                        
                        // Add current images with delete buttons
                        product.images.forEach((img, index) => {
                            imagesGrid.innerHTML += `
                                <div class="upload-box" style="position:relative;" id="img_${index}">
                                    <img src="${img}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                                    <button type="button" class="remove-btn" onclick="deleteProductImage('${img}', 'img_${index}')" style="display:flex;">×</button>
                                </div>
                            `;
                            uploadIndex++;
                        });
                        
                        // Add upload boxes (total should not exceed 4)
                        const maxImages = 4;
                        const remainingSlots = maxImages - product.images.length;
                        
                        for (let i = 0; i < remainingSlots; i++) {
                            imagesGrid.innerHTML += `
                                <label class="upload-box">
                                    <input type="file" name="new_images[]" accept="image/*" onchange="editPreviewImg(this,${i})">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Upload</span>
                                    <img id="edit_preview${i}" class="preview-img">
                                    <button type="button" class="remove-btn" onclick="editRemoveImg(${i})">×</button>
                                </label>
                            `;
                        }
                        
                        // Show popup
                        document.getElementById('editPopup').style.display = 'flex';
                    } else {
                        alert('Error loading product: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load product data');
                });
        }
        
        function closeEditPopup() {
            document.getElementById('editPopup').style.display = 'none';
        }
        
        function updateEditSubcategories() {
            const mainCategory = document.getElementById('edit_mainCategory').value;
            const subcategorySelect = document.getElementById('edit_subcategory');
            
            const subcategories = {
                'Menswear': ['Shirts', 'Pants', 'Suits', 'Jackets', 'T-Shirts', 'Sweaters'],
                'Womenswear': ['Dresses - Mini', 'Dresses - Maxi', 'Dresses - T-shirt', 'Top - Blouses', 'Top - Crop', 'Bottoms - Leggings', 'Bottoms - Skirts'],
                'Shoes': ['Sneakers', 'Formal', 'Boots', 'Sandals', 'Loafers', 'Sports']
            };
            
            subcategorySelect.innerHTML = '<option value="">--Choose Subcategory--</option>';
            
            if (subcategories[mainCategory]) {
                subcategories[mainCategory].forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub;
                    option.textContent = sub;
                    subcategorySelect.appendChild(option);
                });
            }
            
            // Toggle size options
            if (mainCategory === 'Shoes') {
                document.getElementById('edit_clothingSizes').style.display = 'none';
                document.getElementById('edit_shoeSizes').style.display = 'flex';
            } else {
                document.getElementById('edit_clothingSizes').style.display = 'flex';
                document.getElementById('edit_shoeSizes').style.display = 'none';
            }
        }
        
        // Close popup when clicking outside
        document.getElementById('editPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditPopup();
            }
        });
        
        // Variable to store current product ID for image deletion
        let currentProductId = 0;
        
        // Delete product image
        function deleteProductImage(imagePath, elementId) {
            if (!confirm('Are you sure you want to delete this image?')) return;
            
            const formData = new FormData();
            formData.append('product_id', currentProductId);
            formData.append('image_path', imagePath);
            
            fetch('delete_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(elementId).remove();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete image');
            });
        }
        
        // Preview new image in edit popup
        function editPreviewImg(input, index) {
            const preview = document.getElementById('edit_preview' + index);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    input.parentElement.querySelector('i').style.display = 'none';
                    input.parentElement.querySelector('span').style.display = 'none';
                    input.parentElement.querySelector('.remove-btn').style.display = 'flex';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Remove new image preview in edit popup
        function editRemoveImg(index) {
            const preview = document.getElementById('edit_preview' + index);
            const uploadBox = preview.parentElement;
            const input = uploadBox.querySelector('input[type="file"]');
            
            preview.src = '';
            preview.style.display = 'none';
            input.value = '';
            uploadBox.querySelector('i').style.display = 'block';
            uploadBox.querySelector('span').style.display = 'block';
            uploadBox.querySelector('.remove-btn').style.display = 'none';
        }
        
        // Auto-search functionality with debounce
        let searchTimeout;
        const autoSearchInput = document.getElementById('auto-search');
        
        if (autoSearchInput) {
            autoSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500); // Wait 500ms after user stops typing
            });
        }
    </script>
</body>
</html>