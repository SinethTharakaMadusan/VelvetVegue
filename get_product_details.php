<?php
require_once 'Database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$id = (int)$_GET['id'];

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
WHERE p.product_id = ?
GROUP BY p.product_id
LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format data similar to products.php logic
    $row['price'] = number_format((float)$row['price'], 2, '.', ''); // keep as number/string for JS
    if (empty($row['image_path'])) {
        $row['image_path'] = 'image/no-image.png';
    }
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Product not found']);
}

$stmt->close();
$conn->close();
?>
