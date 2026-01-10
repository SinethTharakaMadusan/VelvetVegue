<?php
session_start();

include 'Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$firstName = "Guest";
$userName = "";
$profileImage = NULL;
$cartCount = 0;

if (isset($_SESSION['user_name'])) {
    $userName = $_SESSION['user_name'];
    $parts = explode(' ', $_SESSION['user_name']);
    $firstName = $parts[0];
}

$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
if ($check_column->num_rows > 0) {
    $profile_sql = "SELECT profile_image FROM users WHERE Users_id = ?";
    $profile_stmt = $conn->prepare($profile_sql);
    $profile_stmt->bind_param("i", $user_id);
    $profile_stmt->execute();
    $profile_result = $profile_stmt->get_result();
    if ($profile_row = $profile_result->fetch_assoc()) {
        $profileImage = !empty($profile_row['profile_image']) ? $profile_row['profile_image'] : NULL;
    }
    $profile_stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE users_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cartCount = $result->fetch_assoc()['count'];
$stmt->close();

$address_sql = "SELECT * FROM users_details WHERE users_id = ?";
$stmt = $conn->prepare($address_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$address = $result->fetch_assoc();
$stmt->close();

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address_line = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    
    if ($address) {
        $update_sql = "UPDATE users_details SET full_name = ?, phone_number = ?, address_line1 = ?, city = ?, state = ?, zip_code = ? WHERE users_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssssi", $full_name, $phone, $address_line, $city, $state, $zip_code, $user_id);
    } else {
        $insert_sql = "INSERT INTO users_details (users_id, full_name, phone_number, address_line1, city, state, zip_code, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("issssss", $user_id, $full_name, $phone, $address_line, $city, $state, $zip_code);
    }
    
    if ($stmt->execute()) {
        $message = "Address updated successfully!";
        $messageType = "success";
        $stmt->close();
        $stmt = $conn->prepare("SELECT * FROM users_details WHERE users_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $address = $result->fetch_assoc();
    } else {
        $message = "Error updating address: " . $conn->error;
        $messageType = "error";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Addresses</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="Account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="loghome.css">
    <link rel="stylesheet" href="address.css">

</head>

<body>
    <!-- Start Navigation -->
   <nav class="navbar">
    <div class="navbar-container">
        <a href="index.html" class="logo"><img src="image/logo.png" alt="logo"></a>
        
        <ul class="navbar-menu">
            <li><a href="logHome.php">Home</a></li>
            <li><a href="allproducts.php">Shop</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="contact.php">Contact/Help</a></li>
        </ul>

        <div class="icon-bar">
            <div class="user-name">
                <h3>Welcome,</h3>
                <p><?php echo htmlspecialchars($firstName); ?></p>
            </div>
            <a href="Cart.php" class="icon-box">
                <i class="fas fa-shopping-cart"></i>
                <span class="count"><?php echo $cartCount; ?></span>
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
                    <a class="logout" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
                </div>
            </div>
        </div>
    </div>
   </nav>
   <!-- End Navigation -->

   
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fa fa-bars"></i>
    </button>

   
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="account-container">
        <div class="sidebar" id="sidebar">
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
                <a href="Account.php"><i class="fa fa-user"></i> My Profile</a>
                <a href="myorder.php"><i class="fa fa-shopping-bag"></i> My Orders</a>
                <a href="#"><i class="fa fa-heart"></i> Wishlist</a>
                <a href="address.php" class="active"><i class="fa fa-map-marker"></i> Addresses</a>
                <hr>
                <a href="logout.php" class="logout"><i class="fa fa-sign-out"></i> Logout</a>
            </nav>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>My Addresses</h1>
                
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="profile-details">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($address['full_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($address['phone_number'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address Line</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address['address_line1'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($address['city'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($address['state'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="zip_code">Zip Code</label>
                        <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($address['zip_code'] ?? ''); ?>" required>
                    </div>

                    <button type="submit" class="save-btn">Save Address</button>
                </form>
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

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('sidebar-open');
            overlay.classList.toggle('active');
        }
    </script>
</body>
</html>
