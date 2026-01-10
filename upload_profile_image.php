<?php
session_start();
require_once 'Database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if profile_image column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
if ($check_column->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Database not configured. Please run: ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL;']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['profile_image'];
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF allowed']);
    exit();
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit();
}

// Create upload directory if it doesn't exist
$upload_dir = 'uploads/profile_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
$upload_path = $upload_dir . $new_filename;

// Delete old profile image if exists
$old_image_sql = "SELECT profile_image FROM users WHERE Users_id = ?";
$stmt = $conn->prepare($old_image_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $old_image = $row['profile_image'];
    if ($old_image && file_exists($old_image)) {
        unlink($old_image);
    }
}
$stmt->close();

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Update database
    $update_sql = "UPDATE users SET profile_image = ? WHERE Users_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $upload_path, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Profile image updated successfully',
            'image_path' => $upload_path
        ]);
    } else {
        // Delete uploaded file if database update fails
        unlink($upload_path);
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}

$conn->close();
?>
