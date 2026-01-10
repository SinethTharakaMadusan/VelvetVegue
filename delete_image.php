<?php
require_once 'Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$image_path = isset($_POST['image_path']) ? trim($_POST['image_path']) : '';

if ($product_id <= 0 || empty($image_path)) {
    echo json_encode(['success' => false, 'message' => 'Product ID and image path required']);
    exit;
}

try {
    $sql = "DELETE FROM product_images WHERE product_id = ? AND file_path = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $product_id, $image_path);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $full_path = __DIR__ . '/' . $image_path;
        if (file_exists($full_path)) {
            @unlink($full_path);
        }
        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Image not found in database']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
