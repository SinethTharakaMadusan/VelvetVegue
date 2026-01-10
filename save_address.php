<?php
session_start();
require_once 'Database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get POST data
$full_name = $_POST['full_name'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$address_line1 = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$zip_code = $_POST['zip_code'] ?? '';

// Validate required fields
if (empty($full_name) || empty($phone_number) || empty($address_line1) || empty($city) || empty($state) || empty($zip_code)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

try {
    // Check if user already has an address
    $check_sql = "SELECT users_details_id FROM users_details WHERE users_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing address
        $sql = "UPDATE users_details SET 
                full_name = ?, 
                phone_number = ?, 
                address_line1 = ?, 
                city = ?, 
                state = ?, 
                zip_code = ?,
                is_default = 1
                WHERE users_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $full_name, $phone_number, $address_line1, $city, $state, $zip_code, $user_id);
    } else {
        // Insert new address
        $sql = "INSERT INTO users_details (users_id, full_name, phone_number, address_line1, city, state, zip_code, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $user_id, $full_name, $phone_number, $address_line1, $city, $state, $zip_code);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Address saved successfully',
            'data' => [
                'full_name' => $full_name,
                'phone_number' => $phone_number,
                'address' => $address_line1,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zip_code
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save address']);
    }
    
    $stmt->close();
    $check_stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
