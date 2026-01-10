<?php
require_once 'Database.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($id > 0) {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("DELETE FROM product_color WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM product_size WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

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
