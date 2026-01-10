<?php
session_start();
require_once 'Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cart_id = isset($data['cart_id']) ? (int)$data['cart_id'] : 0;

    if ($cart_id > 0) {
        $user_id = $_SESSION['user_id'];
        
        $sql = "DELETE FROM cart WHERE cart_id = ? AND users_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cart_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete item']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
