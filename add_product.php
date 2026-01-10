<?php

require_once __DIR__ . '/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Invalid request method.';
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$mainCategory = isset($_POST['main_category']) ? trim($_POST['main_category']) : '';
$subcategory = isset($_POST['subcategory']) ? trim($_POST['subcategory']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$price = isset($_POST['price']) ? trim($_POST['price']) : '';
$stock = isset($_POST['stock']) ? trim($_POST['stock']) : '';

$colors = isset($_POST['colors']) && is_array($_POST['colors']) ? array_values($_POST['colors']) : [];
$sizes = isset($_POST['sizes']) && is_array($_POST['sizes']) ? array_values($_POST['sizes']) : [];

$errors = [];
if($name === '') $errors[] = 'Product name is required';
if($mainCategory === '') $errors[] = 'Main category is required';
if($subcategory === '') $errors[] = 'Subcategory is required';
if($description === '') $errors[] = 'Description is required';

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

if(!empty($errors)){
    http_response_code(400);
    echo implode(', ', $errors);
    exit;
}

mysqli_begin_transaction($conn);

try {
 
    $hasPrice = false;
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM `products` LIKE 'price'");
    if ($colCheck && mysqli_num_rows($colCheck) > 0) {
        $hasPrice = true;
    } else {
      
        $alter = mysqli_query($conn, "ALTER TABLE `products` ADD COLUMN `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        if ($alter) $hasPrice = true;
    }

    $hasStock = false;
    $colCheckStock = mysqli_query($conn, "SHOW COLUMNS FROM `products` LIKE 'stock'");
    if ($colCheckStock && mysqli_num_rows($colCheckStock) > 0) {
        $hasStock = true;
    } else {
        
        $alterStock = mysqli_query($conn, "ALTER TABLE `products` ADD COLUMN `stock` INT NOT NULL DEFAULT 0");
        if ($alterStock) $hasStock = true;
    }

    $cols = ['name', 'main_category', 'subcategory', 'gender', 'description', 'created_at'];
    $vals = ['?', '?', '?', '?', '?', 'NOW()'];
    $types = 'sssss';
    $params = [$name, $mainCategory, $subcategory, $gender, $description];

    if ($hasPrice) {
        $cols[] = 'price';
        $vals[] = '?';
        $types .= 'd';
        $params[] = $price_value;
    }
    if ($hasStock) {
        $cols[] = 'stock';
        $vals[] = '?';
        $types .= 'i';
        $params[] = $stock_value;
    }

    $sqlProd = "INSERT INTO products (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
    $stmtProd = mysqli_prepare($conn, $sqlProd);
    if (!$stmtProd) throw new Exception('Prepare products failed: ' . mysqli_error($conn));
    
    mysqli_stmt_bind_param($stmtProd, $types, ...$params);
    if (!mysqli_stmt_execute($stmtProd)) {
        throw new Exception('Execute products failed: ' . mysqli_stmt_error($stmtProd));
    }

    $product_id = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmtProd);

    if ($product_id <= 0) {
        throw new Exception('Failed to obtain product id.');
    }

    // Insert colors
    if(!empty($colors)){
        $sqlColor = "INSERT INTO product_color (product_id, color) VALUES (?, ?)";
        $stmtColor = mysqli_prepare($conn, $sqlColor);
        if (!$stmtColor) throw new Exception('Prepare color failed: ' . mysqli_error($conn));

        $bind_pid = $product_id;
        $bind_color = '';
        if(!mysqli_stmt_bind_param($stmtColor, 'is', $bind_pid, $bind_color)){
            throw new Exception('Bind color failed: ' . mysqli_stmt_error($stmtColor));
        }

        foreach($colors as $c){
            $bind_color = substr(trim($c), 0, 100);
            if($bind_color === '') continue;
            if(!mysqli_stmt_execute($stmtColor)){
                throw new Exception('Execute color failed: ' . mysqli_stmt_error($stmtColor));
            }
        }
        mysqli_stmt_close($stmtColor);
    }

    // Insert sizes
    if(!empty($sizes)){
        $sqlSize = "INSERT INTO product_size (product_id, size) VALUES (?, ?)";
        $stmtSize = mysqli_prepare($conn, $sqlSize);
        if(!$stmtSize) throw new Exception('Prepare size failed: ' . mysqli_error($conn));

        $bind_pid2 = $product_id;
        $bind_size = '';
        if(!mysqli_stmt_bind_param($stmtSize, 'is', $bind_pid2, $bind_size)){
            throw new Exception('Bind size failed: ' . mysqli_stmt_error($stmtSize));
        }

        foreach($sizes as $s){
            $bind_size = substr(trim($s), 0, 50);
            if($bind_size === '') continue;
            if(!mysqli_stmt_execute($stmtSize)){
                throw new Exception('Execute size failed: ' . mysqli_stmt_error($stmtSize));
            }
        }
        mysqli_stmt_close($stmtSize);
    }

    // Handle image uploading
    $storedFiles = [];
    if(!empty($_FILES['images'])){
        $files = $_FILES['images'];

        // Check for upload errors early and provide clearer messages
        $fileErrors = [];
        if (is_array($files['error'])) {
            foreach ($files['error'] as $i => $err) {
                if ($err === UPLOAD_ERR_NO_FILE) continue; // no file for this slot
                if ($err === UPLOAD_ERR_OK) continue;
                $msg = "Image slot {$i} upload error ";
                switch ($err) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $msg .= "(file too large). Check php.ini 'upload_max_filesize' and 'post_max_size'.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $msg .= "(partial upload).";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $msg .= "(missing tmp folder).";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $msg .= "(failed to write to disk).";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $msg .= "(PHP extension blocked the upload).";
                        break;
                    default:
                        $msg .= "(error code {$err}).";
                }
                $fileErrors[] = $msg;
            }
        }

        if (!empty($fileErrors)) {
            // Log and throw a clear exception so user sees the reason
            throw new Exception('Image upload errors: ' . implode(' ; ', $fileErrors));
        }

        $uploadDir = __DIR__  . '/uploads/products/' . $product_id . '/';
        if(!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)){
            throw new Exception('Failed to create upload directory.');
        }

        $sqlImg = "INSERT INTO product_images (product_id, file_path, sort_order, created_at) VALUES (?, ?, ?, NOW())";
        $stmtImg = mysqli_prepare($conn, $sqlImg);
        if(!$stmtImg) throw new Exception('Prepare image failed: ' . mysqli_error($conn));

        $bind_pid_img = $product_id;
        $bind_fp = '';
        $bind_order = 0;
        if(!mysqli_stmt_bind_param($stmtImg, 'isi', $bind_pid_img, $bind_fp, $bind_order)){
            throw new Exception('Bind image failed: ' . mysqli_stmt_error($stmtImg));
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 4 * 1024 * 1024; // 4MB
        $filesCount = is_array($files['name']) ? count($files['name']) : 0;
        $insertedCount = 0;

        for ($i = 0; $i < $filesCount && $insertedCount < 4; $i++){
             if(empty($files['name'][$i])) continue;
             if($files['error'][$i] !== UPLOAD_ERR_OK) continue;
             if($files['size'][$i] > $maxFileSize) continue;

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

             if(!move_uploaded_file($tmp, $destFull)) {
                throw new Exception('Failed to move uploaded file');
             }

             $bind_fp = $webPath;
             $bind_order = $insertedCount;
             if(!mysqli_stmt_execute($stmtImg)){
                @unlink($destFull);
                throw new Exception('Execute image failed: ' . mysqli_stmt_error($stmtImg));
             }

             $storedFiles[] = $webPath;
             $insertedCount++;
        }
        mysqli_stmt_close($stmtImg);
    }

    // All done, commit
    mysqli_commit($conn);

    // Redirect to products page
    header('Location: products.php?status=success');
    exit;

} catch(Exception $ex){
    mysqli_rollback($conn);

    // cleanup uploaded files if any
    if(!empty($product_id)){
        $dir = __DIR__ . '/uploads/products/' . $product_id . '/';
        if(is_dir($dir)){
            $files = glob($dir . '*');
            foreach($files as $f) {
                if(is_file($f)) @unlink($f);
            }
            @rmdir($dir);
        }
    }

    http_response_code(500);
    echo 'Error saving product: ' . htmlspecialchars($ex->getMessage());
    exit;
}

?>
