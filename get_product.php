<?php
// AJAX endpoint to get product data for editing
require_once 'Database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$product_id = (int)$_GET['id'];

// Get product details
$sql = "
SELECT
  p.product_id,
  p.name,
  p.main_category,
  p.subcategory,
  p.gender,
  IFNULL(p.price, 0.00) AS price,
  IFNULL(p.stock, 0) AS stock, 
  p.description
FROM products p
WHERE p.product_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

// Get colors
$colors = [];
$color_sql = "SELECT color FROM product_color WHERE product_id = ?";
$color_stmt = $conn->prepare($color_sql);
$color_stmt->bind_param('i', $product_id);
$color_stmt->execute();
$color_result = $color_stmt->get_result();
while ($color_row = $color_result->fetch_assoc()) {
    $colors[] = $color_row['color'];
}
$color_stmt->close();

// Get sizes
$sizes = [];
$size_sql = "SELECT size FROM product_size WHERE product_id = ?";
$size_stmt = $conn->prepare($size_sql);
$size_stmt->bind_param('i', $product_id);
$size_stmt->execute();
$size_result = $size_stmt->get_result();
while ($size_row = $size_result->fetch_assoc()) {
    $sizes[] = $size_row['size'];
}
$size_stmt->close();

// Get images
$images = [];
$img_sql = "SELECT file_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param('i', $product_id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
while ($img_row = $img_result->fetch_assoc()) {
    $images[] = $img_row['file_path'];
}
$img_stmt->close();

$product['colors'] = $colors;
$product['sizes'] = $sizes;
$product['images'] = $images;

echo json_encode(['success' => true, 'data' => $product]);
?>
