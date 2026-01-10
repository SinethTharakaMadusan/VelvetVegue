<?php
require_once 'Database.php';

// Create admins table
$sql = "CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'admins' created successfully.<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Check if default admin exists
$checkSql = "SELECT * FROM admins WHERE username = 'Admin'";
$result = $conn->query($checkSql);

if ($result->num_rows == 0) {
    // Insert default admin
    $username = 'Admin';
    $password = 'Admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $name = 'Admin User';
    $email = 'admin@velvetvegue.com';

    $stmt = $conn->prepare("INSERT INTO admins (username, password, name, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hashed_password, $name, $email);

    if ($stmt->execute()) {
        echo "Default admin account created (User: Admin, Pass: Admin123).<br>";
    } else {
        echo "Error creating default admin: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Default admin account already exists.<br>";
}

echo "Setup complete.";
?>
