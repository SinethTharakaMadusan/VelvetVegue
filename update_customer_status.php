<?php
include 'Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

if (!in_array($status, ['active', 'locked'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

if ($status === 'active') {
    $sql = "UPDATE users SET status = ?, failed_attempts = 0, last_failed_login = NULL WHERE Users_id = ?";
} else {
    $sql = "UPDATE users SET status = ? WHERE Users_id = ?";
}
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$stmt->bind_param("si", $status, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Account status updated successfully',
        'new_status' => $status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

$stmt->close();
$conn->close();
?>
