<?php
session_start();
require_once 'Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Log.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Account.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Validate inputs
if (empty($name)) {
    $_SESSION['error_message'] = "Name cannot be empty";
    header("Location: Account.php");
    exit();
}

// Validate phone number (must be exactly 10 digits)
if (!empty($phone)) {
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $_SESSION['error_message'] = "Phone number must be exactly 10 digits";
        header("Location: Account.php");
        exit();
    }
}

// Update name in users table
$update_user_sql = "UPDATE users SET name = ? WHERE Users_id = ?";
$stmt = $conn->prepare($update_user_sql);
$stmt->bind_param("si", $name, $user_id);

if (!$stmt->execute()) {
    $_SESSION['error_message'] = "Failed to update profile";
    header("Location: Account.php");
    exit();
}
$stmt->close();

// Update session name
$_SESSION['user_name'] = $name;

// Handle phone number - check if record exists in users_details
$check_sql = "SELECT users_details_id FROM users_details WHERE users_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$exists = $result->num_rows > 0;
$stmt->close();

if ($exists) {
    // Update existing record
    $update_phone_sql = "UPDATE users_details SET phone_number = ? WHERE users_id = ?";
    $stmt = $conn->prepare($update_phone_sql);
    $stmt->bind_param("si", $phone, $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Insert new record
    $insert_phone_sql = "INSERT INTO users_details (users_id, phone_number) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_phone_sql);
    $stmt->bind_param("is", $user_id, $phone);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['success_message'] = "Profile updated successfully!";
header("Location: Account.php");
exit();
?>
