<?php
session_start();
require_once 'Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Log.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user first name
$firstName = "Guest";
$cartCount = 0;

// Fetch user details from database
$userName = "";
$userEmail = "";
$userPhone = "";
$profileImage = NULL; // Will be NULL if no profile image

// Get basic user info from users table
$user_sql = "SELECT name, email FROM users WHERE Users_id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $userName = $row['name'];
    $userEmail = $row['email'];
    // Check if profile_image column exists
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if ($check_column->num_rows > 0) {
        // Column exists, fetch profile image
        $img_sql = "SELECT profile_image FROM users WHERE Users_id = ?";
        $img_stmt = $conn->prepare($img_sql);
        $img_stmt->bind_param("i", $user_id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        if ($img_row = $img_result->fetch_assoc()) {
            $profileImage = !empty($img_row['profile_image']) ? $img_row['profile_image'] : NULL;
        }
        $img_stmt->close();
    }
    $parts = explode(' ', $userName);
    $firstName = $parts[0];
}
$stmt->close();

// Get phone number from users_details table
$details_sql = "SELECT phone_number FROM users_details WHERE users_id = ?";
$stmt = $conn->prepare($details_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $userPhone = $row['phone_number'] ?? "";
}
$stmt->close();

// Count cart items
$count_sql = "SELECT COUNT(*) as count FROM cart WHERE users_id = ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_count = $stmt->get_result();
if ($row_count = $result_count->fetch_assoc()) {
    $cartCount = $row_count['count'];
}
$stmt->close();

// Handle Password Update Logic
$pass_msg = '';
$pass_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $pass_err = "All password fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $pass_err = "New passwords do not match.";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE Users_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            // Assuming users table passwords are hashed. If legacy plain text exists, might need generic check, 
            // but Admin is hashed, so assuming standard password_verify here.
            // If uses plain text (unlikely for final prod but possible), logic would differ.
            // Using password_verify as best practice.
            if (password_verify($current_pass, $row['password'])) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $up_stmt = $conn->prepare("UPDATE users SET password = ? WHERE Users_id = ?");
                $up_stmt->bind_param("si", $new_hash, $user_id);
                if ($up_stmt->execute()) {
                    $pass_msg = "Password changed successfully!";
                    // Clear POST to avoid re-submission populating fields (browser default clears, but we can be explicit if needed via JS)
                } else {
                    $pass_err = "Error updating password.";
                }
                $up_stmt->close();
            } else {
                $pass_err = "Incorrect current password.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="Account.css">
    <link rel="stylesheet" href="loghome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

</head>

<body>
    <!-- start navigation -->

    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="logo"><img src="image/logo.png" alt="logo"></a>
            <button class="navbar-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <ul class="navbar-menu">
                <li><a class="active" href="logHome.php">Home</a></li>
                <li><a href="allproducts.php">Shop</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="#">Contact/Help</a></li>
            </ul>


            <!-- <button class="regbtn">Register/Login</button> -->

            <div class="icon-bar">

                <div class="user-name">
                    <h3>Welcome,</h3>
                    <p>
                        <?php echo htmlspecialchars($firstName); ?>
                    </p>
                </div>
                <a href="cart.php" class="icon-box">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="count">
                        <?php echo $cartCount; ?>
                    </span>
                </a>

                <div class="user-menu-container">
                    <a href="javascript:void(0)" class="icon-box user" id="userBtn">
                        <i class="fas fa-user"></i>
                    </a>
                    <div class="user-dropdown" id="userMenu">

                        <a href="myorder.php"><i class="fas fa-box"></i> My Orders</a>
                        <a href="#"><i class="fas fa-heart"></i> Wishlist</a>
                        <a href="Account.php"><i class="fas fa-user-cog"></i> Profile</a>
                        <hr>

                        <a class="logout" href="Home.php"><i class="fas fa-sign-out-alt"></i>Logout</a>

                    </div>
                </div>

            </div>

    </nav>





    <!-- end navigation  -->
    <div class="account-container">
        <div class="sidebar">
            <div class="user-profile">
                <div class="profile-image-wrapper">
                    <?php if ($profileImage): ?>
                        <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="User">
                    <?php else: ?>
                        <div class="default-profile-icon">
                            <i class="fa fa-user-circle"></i>
                        </div>
                    <?php endif; ?>
                    <label for="file-input" class="edit-icon">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </label>
                    <input type="file" id="file-input">
                </div>
                <h3><?php echo htmlspecialchars($userName); ?></h3>
            </div>
            <nav>
                <a href="Account.php" class="active"><i class="fa fa-user"></i> My Profile</a>
                <a href="myorder.php"><i class="fa fa-shopping-bag"></i> My Orders</a>
                <a href="#"><i class="fa fa-heart"></i> Wishlist</a>
                <a href="address.php"><i class="fa fa-map-marker"></i> Addresses</a>
                <hr>
                <a href="logout.php" class="logout"><i class="fa fa-sign-out"></i> Logout</a>
            </nav>
        </div>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>My Profile</h2>
                <button onclick="openPasswordModal()" style="background: #244551; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Reset Password</button>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-card">
                <form action="update_profile.php" method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($userName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($userPhone); ?>" 
                               pattern="[0-9]{10}" 
                               maxlength="10" 
                               minlength="10" 
                               title="Please enter exactly 10 digits">
                    </div>
                    <button type="submit" class="save-btn">Update Profile</button>
                </form>
            </div>

            <!-- Password Modal -->
            <div id="passwordModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
                <div class="modal-content" style="background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; position: relative;">
                    <span onclick="closePasswordModal()" style="position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer;">&times;</span>
                    <h3 style="margin-bottom: 20px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px;">Change Password</h3>
                    
                    <?php if(!empty($pass_msg)): ?>
                        <div class="alert alert-success"><?php echo $pass_msg; ?></div>
                    <?php endif; ?>
                    <?php if(!empty($pass_err)): ?>
                        <div class="alert alert-error"><?php echo $pass_err; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="passwordForm">
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 5px;">Current Password</label>
                            <input type="password" name="current_password" class="pass-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                        </div>
                        <div class="form-group" style="margin-top: 15px;">
                            <label style="display: block; margin-bottom: 5px;">New Password</label>
                            <input type="password" name="new_password" class="pass-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                        </div>
                        <div class="form-group" style="margin-top: 15px;">
                            <label style="display: block; margin-bottom: 5px;">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="pass-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                        </div>
                        <div style="margin: 20px 0; text-align: left;">
                            <input type="checkbox" id="showUserPass" onclick="toggleUserPassword()"> 
                            <label for="showUserPass" style="cursor: pointer; user-select: none;">Show Password</label>
                        </div>
                        <button type="submit" name="update_password" class="save-btn" style="width: 100%;">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script src="loghome.js"></script>
<script>
    
    document.getElementById('file-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, or GIF)');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }

        // Upload the file
        const formData = new FormData();
        formData.append('profile_image', file);

        fetch('upload_profile_image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Profile image updated successfully!');
                location.reload();
            } else {
                alert('Upload failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while uploading the image');
        });
    });



    function openPasswordModal() {
        document.getElementById('passwordModal').style.display = 'flex';
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').style.display = 'none';
        // Clear errors/messages when closing manually if desired, but keeping them might be better
    }

    // Close modal if clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('passwordModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function toggleUserPassword() {
        var inputs = document.querySelectorAll('.pass-input');
        var checkBox = document.getElementById('showUserPass');
        for (var i = 0; i < inputs.length; i++) {
            if (checkBox.checked) {
                inputs[i].type = "text";
            } else {
                inputs[i].type = "password";
            }
        }
    }

    // Keep modal open if there are messages (error or success)
    <?php if(!empty($pass_msg) || !empty($pass_err)): ?>
    openPasswordModal();
    <?php endif; ?>

    // Clear fields if success message is present
    <?php if(!empty($pass_msg)): ?>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    document.getElementById('passwordForm').reset();
    <?php endif; ?>
</script>
</body>

</html>