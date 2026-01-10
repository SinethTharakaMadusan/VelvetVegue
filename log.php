<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login form</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel ="stylesheet" href ="log.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
</head>
<body>
    
    <div class="container">
    <?php
    session_start();
   


    
    
    // if (isset($_SESSION["user_id"])) {
    //     if ($_SESSION["is_admin"]) {
    //         header("Location: Admin.html");
    //     } else {
    //         header("Location: logHome.php");
    //     }
    //     exit();
    // }

    if (isset($_POST["login"])) {
        $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
        $password = $_POST["password"];
        
        if (empty($email) || empty($password)) {
            echo "<div class='alert alert-danger'>Please fill in all fields</div>";
        } else {
            require_once "Database.php";
            
            // Get user with login attempts info
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
            
            if ($user) {
                // Check if account is locked
                $account_status = $user["status"] ?? 'active';
                if ($account_status === 'locked') {
                    echo "<div class='alert alert-danger'>Your account has been locked due to multiple failed login attempts. Please contact support.</div>";
                } else {
                    // Check password
                    if (password_verify($password, $user["password"])) {
                        // Reset failed attempts on successful login
                        $reset_sql = "UPDATE users SET failed_attempts = 0, last_failed_login = NULL WHERE Users_id = ?";
                        $reset_stmt = mysqli_prepare($conn, $reset_sql);
                        mysqli_stmt_bind_param($reset_stmt, "i", $user["Users_id"]);
                        mysqli_stmt_execute($reset_stmt);
                        
                        $_SESSION["user_id"] = $user["Users_id"];
                        $_SESSION["user_name"] = $user["name"];
                        $_SESSION["is_admin"] = $user["is_admin"];
                        
                        if ($user["is_admin"]) {
                            header("Location: Admin.html");
                        } else {
                            header("Location: logHome.php");
                        }
                        exit();
                    } else {
                        // Increment failed login attempts
                        $failed_attempts = ($user["failed_attempts"] ?? 0) + 1;
                        
                        if ($failed_attempts >= 3) {
                            // Lock the account after 3 failed attempts
                            $lock_sql = "UPDATE users SET failed_attempts = ?, status = 'locked', last_failed_login = NOW() WHERE Users_id = ?";
                            $lock_stmt = mysqli_prepare($conn, $lock_sql);
                            mysqli_stmt_bind_param($lock_stmt, "ii", $failed_attempts, $user["Users_id"]);
                            mysqli_stmt_execute($lock_stmt);
                            echo "<div class='alert alert-danger'>Your account has been locked due to 3 failed login attempts. Please contact support.</div>";
                        } else {
                            // Update failed attempts count
                            $update_sql = "UPDATE users SET failed_attempts = ?, last_failed_login = NOW() WHERE Users_id = ?";
                            $update_stmt = mysqli_prepare($conn, $update_sql);
                            mysqli_stmt_bind_param($update_stmt, "ii", $failed_attempts, $user["Users_id"]);
                            mysqli_stmt_execute($update_stmt);
                            $remaining = 3 - $failed_attempts;
                            echo "<div class='alert alert-danger'>Invalid password. You have {$remaining} attempt(s) remaining before your account is locked.</div>";
                        }
                    }
                }
            } else {
                echo "<div class='alert alert-danger'>Email not found</div>";
            }
            mysqli_close($conn);
        }
    }
    ?>







        <a href="#" class="logo"><img src="image/logo.png" alt="logo"></a>
    <h2>Login</h2>
        <form action="Log.php" method="post">
           
            <div class="form-group">
                <input type="email" class="form-control" name= "email" placeholder="Enter Email Address">
            </div>
             <div class="form-group">
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter Password" minlength="8">
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="showPassword" onclick="togglePassword()">
                <label class="form-check-label" for="showPassword">Show Password</label>
            </div>
            <div class="forgot-password">
                <a href="reset_password.php">Forgot Password?</a>
            </div>

            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login" name="login">
            </div>
            <div class="social-login">
        <a href="#" class="social-btn google-btn" onclick="registerWithGoogle()">
            <i class="fa-brands fa-google"></i>
            Continue with Google
        </a>

        <a href="#" class="social-btn facebook-btn" onclick="registerWithFacebook()">
            <i class="fa-brands fa-facebook-f"></i>
            Continue with Facebook
        </a>
    </div>
            


    <div>
        
        
    </div>
    <div class="login-link">
        <span>If you don't have an Account</span>
        <a href="Register.php">Register now</a>
    </div>

        </form>
    </div>

    <script src = "login.js"></script>
</body>
</html>