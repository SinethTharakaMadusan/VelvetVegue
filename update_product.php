<?php
// Handle product update
require_once __DIR__ . '/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Invalid request method.';
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$mainCategory = isset($_POST['main_category']) ? trim($_POST['main_category']) : '';
$subcategory = isset($_POST['subcategory']) ? trim($_POST['subcategory']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$price = isset($_POST['price']) ? trim($_POST['price']) : '';
$stock = isset($_POST['stock']) ? trim($_POST['stock']) : '';

$colors = isset($_POST['colors']) && is_array($_POST['colors']) ? array_values($_POST['colors']) : [];
$sizes = isset($_POST['sizes']) && is_array($_POST['sizes']) ? array_values($_POST['sizes']) : [];

// Validation
$errors = [];
if ($product_id <= 0) $errors[] = 'Invalid product ID';
if ($name === '') $errors[] = 'Product name is required';
if ($mainCategory === '') $errors[] = 'Main category is required';
if ($subcategory === '') $errors[] = 'Subcategory is required';
if ($description === '') $errors[] = 'Description is required';

$price_value = 0.00;
if ($price !== '') {
    if (!is_numeric($price)) {
        $errors[] = 'Price must be a number';
    } else {
        $price_value = (float) $price;
        if ($price_value < 0) $errors[] = 'Price must be >= 0';
    }
} else {
    $errors[] = 'Price is required';
}

$stock_value = 0;
if ($stock !== '') {
    if (!is_numeric($stock)) {
        $errors[] = 'Stock must be a number';
    } else {
        $stock_value = (int) $stock;
        if ($stock_value < 0) $errors[] = 'Stock must be >= 0';
    }
} else {
    $errors[] = 'Stock is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo implode(', ', $errors);
    exit;
}

mysqli_begin_transaction($conn);

try {
    // Update product
    $sql = "UPDATE products SET 
            name = ?, 
            main_category = ?, 
            subcategory = ?, 
            gender = ?, 
            description = ?, 
            price = ?, 
            stock = ? 
            WHERE product_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception('Prepare failed: ' . mysqli_error($conn));
    
    mysqli_stmt_bind_param($stmt, 'sssssdii', $name, $mainCategory, $subcategory, $gender, $description, $price_value, $stock_value, $product_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Update failed: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
    
    // Delete existing colors and re-insert
    $deleteColors = mysqli_prepare($conn, "DELETE FROM product_color WHERE product_id = ?");
    mysqli_stmt_bind_param($deleteColors, 'i', $product_id);
    mysqli_stmt_execute($deleteColors);
    mysqli_stmt_close($deleteColors);
    
    // Insert new colors
    if (!empty($colors)) {
        $sqlColor = "INSERT INTO product_color (product_id, color) VALUES (?, ?)";
        $stmtColor = mysqli_prepare($conn, $sqlColor);
        if (!$stmtColor) throw new Exception('Prepare color failed: ' . mysqli_error($conn));
        
        foreach ($colors as $c) {
            $color_value = substr(trim($c), 0, 100);
            if ($color_value === '') continue;
            mysqli_stmt_bind_param($stmtColor, 'is', $product_id, $color_value);
            if (!mysqli_stmt_execute($stmtColor)) {
                throw new Exception('Execute color failed: ' . mysqli_stmt_error($stmtColor));
            }
        }
        mysqli_stmt_close($stmtColor);
    }
    
    // Delete existing sizes and re-insert
    $deleteSizes = mysqli_prepare($conn, "DELETE FROM product_size WHERE product_id = ?");
    mysqli_stmt_bind_param($deleteSizes, 'i', $product_id);
    mysqli_stmt_execute($deleteSizes);
    mysqli_stmt_close($deleteSizes);
    
    // Insert new sizes
    if (!empty($sizes)) {
        $sqlSize = "INSERT INTO product_size (product_id, size) VALUES (?, ?)";
        $stmtSize = mysqli_prepare($conn, $sqlSize);
        if (!$stmtSize) throw new Exception('Prepare size failed: ' . mysqli_error($conn));
        
        foreach ($sizes as $s) {
            $size_value = substr(trim($s), 0, 50);
            if ($size_value === '') continue;
            mysqli_stmt_bind_param($stmtSize, 'is', $product_id, $size_value);
            if (!mysqli_stmt_execute($stmtSize)) {
                throw new Exception('Execute size failed: ' . mysqli_stmt_error($stmtSize));
            }
        }
        mysqli_stmt_close($stmtSize);
    }
    
    // Handle new image uploads
    if (!empty($_FILES['new_images'])) {
        $files = $_FILES['new_images'];
        
        // Get current max sort_order
        $maxOrderSql = "SELECT COALESCE(MAX(sort_order), -1) as max_order FROM product_images WHERE product_id = ?";
        $maxOrderStmt = mysqli_prepare($conn, $maxOrderSql);
        mysqli_stmt_bind_param($maxOrderStmt, 'i', $product_id);
        mysqli_stmt_execute($maxOrderStmt);
        $maxOrderResult = mysqli_stmt_get_result($maxOrderStmt);
        $maxOrder = mysqli_fetch_assoc($maxOrderResult)['max_order'];
        mysqli_stmt_close($maxOrderStmt);
        
        $uploadDir = __DIR__ . '/uploads/products/' . $product_id . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new Exception('Failed to create upload directory.');
        }
        
        $sqlImg = "INSERT INTO product_images (product_id, file_path, sort_order, created_at) VALUES (?, ?, ?, NOW())";
        $stmtImg = mysqli_prepare($conn, $sqlImg);
        if (!$stmtImg) throw new Exception('Prepare image failed: ' . mysqli_error($conn));
        
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 4 * 1024 * 1024; // 4MB
        $filesCount = is_array($files['name']) ? count($files['name']) : 0;
        $insertedCount = 0;
        
        for ($i = 0; $i < $filesCount && $insertedCount < 4; $i++) {
            if (empty($files['name'][$i])) continue;
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > $maxFileSize) continue;
            
            $tmp = $files['tmp_name'][$i];
            if (!is_uploaded_file($tmp)) continue;
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            if (!in_array($mime, $allowedMime)) continue;
            
            $origName = basename($files['name'][$i]);
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $safeName = uniqid('img_', true) . '.' . $ext;
            $destFull = $uploadDir . $safeName;
            $webPath = 'uploads/products/' . $product_id . '/' . $safeName;
            
            if (!move_uploaded_file($tmp, $destFull)) {
                throw new Exception('Failed to move uploaded file');
            }
            
            $sortOrder = $maxOrder + 1 + $insertedCount;
            mysqli_stmt_bind_param($stmtImg, 'isi', $product_id, $webPath, $sortOrder);
            if (!mysqli_stmt_execute($stmtImg)) {
                @unlink($destFull);
                throw new Exception('Execute image failed: ' . mysqli_stmt_error($stmtImg));
            }
            
            $insertedCount++;
        }
        mysqli_stmt_close($stmtImg);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect back to products page
    header('Location: products.php?status=updated');
    exit;
    
} catch (Exception $ex) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo 'Error updating product: ' . htmlspecialchars($ex->getMessage());
    exit;
}
?>
