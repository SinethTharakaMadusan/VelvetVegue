<?php
include 'Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$address_line1 = isset($_POST['address_line1']) ? trim($_POST['address_line1']) : '';
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$state = isset($_POST['state']) ? trim($_POST['state']) : '';
$zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : '';

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Check if record exists
$check_sql = "SELECT users_details_id FROM users_details WHERE users_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$exists = $result->num_rows > 0;
$stmt->close();

if ($exists) {
    // Update existing record
    $sql = "UPDATE users_details SET phone_number = ?, address_line1 = ?, city = ?, state = ?, zip_code = ? WHERE users_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $phone_number, $address_line1, $city, $state, $zip_code, $user_id);
} else {
    // Insert new record
    $sql = "INSERT INTO users_details (users_id, phone_number, address_line1, city, state, zip_code) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $phone_number, $address_line1, $city, $state, $zip_code);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Contact details updated successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update details']);
}

$stmt->close();
$conn->close();
?>
