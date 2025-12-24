<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel ="stylesheet" href ="log.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
</head>
<body>
    
    <div class="container">
    <?php
    session_start();
   


    
    
    if (isset($_SESSION["user_id"])) {
        if ($_SESSION["is_admin"]) {
            header("Location: Admin.html");
        } else {
            header("Location: logHome.php");
        }
        exit();
    }

    if (isset($_POST["login"])) {
        $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
        $password = $_POST["password"];
        
        if (empty($email) || empty($password)) {
            echo "<div class='alert alert-danger'>Please fill in all fields</div>";
        } else {
            require_once "database.php";
            
            
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
            
            if ($user) {
                if (password_verify($password, $user["password"])) {
                    
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
                    echo "<div class='alert alert-danger'>Invalid password</div>";
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
        <form action="login.php" method="post">
           
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