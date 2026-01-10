<?php
session_start();
include 'Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $rating = isset($_POST['star']) ? (int)$_POST['star'] : 0;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
    
    // Validate required fields
    if ($product_id <= 0) {
        $_SESSION['review_error'] = "Invalid product selected.";
        header("Location: review.php?order_id=$order_id&product_id=$product_id");
        exit();
    }
    
    if ($rating < 1 || $rating > 5) {
        $_SESSION['review_error'] = "Please select a rating (1-5 stars).";
        header("Location: review.php?order_id=$order_id&product_id=$product_id");
        exit();
    }
    
    // Handle image upload
    $image_path = null;
    
    if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['review_image'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_type = $file['type'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['review_error'] = "Invalid image type. Please upload JPG, PNG, GIF or WebP.";
            header("Location: review.php?order_id=$order_id&product_id=$product_id");
            exit();
        }
        
        // Validate file size (5MB max)
        if ($file_size > 5 * 1024 * 1024) {
            $_SESSION['review_error'] = "Image size must be less than 5MB.";
            header("Location: review.php?order_id=$order_id&product_id=$product_id");
            exit();
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = 'uploads/reviews/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_filename = 'review_' . $user_id . '_' . $product_id . '_' . time() . '.' . $extension;
        $image_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file_tmp, $image_path)) {
            $_SESSION['review_error'] = "Failed to upload image. Please try again.";
            header("Location: review.php?order_id=$order_id&product_id=$product_id");
            exit();
        }
    }
    
    // Insert review into database
    $sql = "INSERT INTO product_reviews (user_id, product_id, rating, review_text, review_image, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $user_id, $product_id, $rating, $review_text, $image_path);
    
    if ($stmt->execute()) {
        $_SESSION['review_success'] = "Thank you! Your review has been submitted successfully.";
        header("Location: myorder.php");
        exit();
    } else {
        $_SESSION['review_error'] = "Failed to submit review. Please try again. Error: " . $conn->error;
        header("Location: review.php?order_id=$order_id&product_id=$product_id");
        exit();
    }
    
    $stmt->close();
    
} else {
    // If not POST request, redirect to orders page
    header("Location: myorder.php");
    exit();
}

$conn->close();
?>
