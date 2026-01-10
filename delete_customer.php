<?php
include 'Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete from users_details first (foreign key)
    $sql1 = "DELETE FROM users_details WHERE users_id = ?";
    $stmt1 = $conn->prepare($sql1);
    if ($stmt1) {
        $stmt1->bind_param("i", $user_id);
        $stmt1->execute();
        $stmt1->close();
    }
    
    // Delete user from users table
    $sql2 = "DELETE FROM users WHERE Users_id = ?";
    $stmt2 = $conn->prepare($sql2);
    
    if (!$stmt2) {
        throw new Exception('Database error');
    }
    
    $stmt2->bind_param("i", $user_id);
    
    if ($stmt2->execute()) {
        if ($stmt2->affected_rows > 0) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
    } else {
        throw new Exception('Failed to delete customer');
    }
    
    $stmt2->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
