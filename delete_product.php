<?php
require_once 'Database.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($id > 0) {
        // Start transaction to ensure data integrity
        $conn->begin_transaction();

        try {
            // 1. Delete from product_color
            $stmt = $conn->prepare("DELETE FROM product_color WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 2. Delete from product_size
            $stmt = $conn->prepare("DELETE FROM product_size WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 3. Delete from product_images
            // First fetch images to delete files from server if needed (optional, skipping file unlink for now to keep it simple)
            $stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 4. Delete from products table
            $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();

            // Redirect with success
            header("Location: products.php?msg=deleted");
            exit();

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            echo "Error deleting product: " . $e->getMessage();
        }
    } else {
        echo "Invalid Product ID.";
    }
} else {
    header("Location: products.php");
    exit();
}
?>
