<?php
session_start();
require_once 'Database.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (empty($name) || empty($email)) {
        $error_msg = "Name and Email are required.";
    } else {
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE admin_id = ?");
        $stmt->bind_param("ssi", $name, $email, $admin_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name; // Update session
            $success_msg = "Profile updated successfully.";
        } else {
            $error_msg = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $password_error = "All fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $password_error = "New passwords do not match.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM admins WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $admin = $res->fetch_assoc();
        
        if ($admin && password_verify($current_pass, $admin['password'])) {
            // Update password
            $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
            $update_stmt->bind_param("si", $new_hashed, $admin_id);
            
            if ($update_stmt->execute()) {
                $password_success = "Password changed successfully.";
            } else {
                $password_error = "Error updating password.";
            }
            $update_stmt->close();
        } else {
            $password_error = "Incorrect current password.";
        }
        $stmt->close();
    }
}

// Fetch current admin details
$stmt = $conn->prepare("SELECT name, email, username FROM admins WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .settings-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .settings-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #244551;
            outline: none;
        }
        
        .form-control[readonly] {
            background-color: #f9f9f9;
        }

        .btn-save {
            background: #244551;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-save:hover {
            background: #1a343d;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-container">
                <img class="logo" src="image/logo.png" alt="Logo">
            </div>
            <div class="menu-items">
                <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-box"></i> Products</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
                <a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
                <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
            </div>
            <div class="user-profile">
                <img src="image/user.png" alt="User" class="user-avatar">
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Settings</h1>
            </div>

            <div class="settings-container">
                
                <!-- Profile Settings -->
                <div class="settings-card">
                    <h2>Profile Information</h2>
                    <?php if (!empty($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
                    <?php if (!empty($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['username']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                    </form>
                </div>

                <!-- Password Settings -->
                <div class="settings-card">
                    <h2>Change Password</h2>
                    <?php if (!empty($password_success)) echo "<div class='alert alert-success'>$password_success</div>"; ?>
                    <?php if (!empty($password_error)) echo "<div class='alert alert-danger'>$password_error</div>"; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn-save">Update Password</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
