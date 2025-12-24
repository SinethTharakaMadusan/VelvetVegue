<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel ="stylesheet" href ="Register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/416b43dbe0.js" crossorigin="anonymous"></script>

    

    

</head>
<body>


    <div class="container">

    <?php
    if(isset($_POST["submit"])){
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirmpassword = $_POST["confirmpassword"];
    $errors = array();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $errors = array();

    if (empty($name) or empty($email) or empty($password) or empty($confirmpassword)){
        array_push($errors, "All fields are required");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        array_push($errors, "Email is not valid");
    }

    if(strlen($password) < 8){
        array_push($errors, "Password must be at least 8 characters long");
    }

    if ($password != $confirmpassword){
        array_push($errors, "Password does not match");
    }

    require_once "database.php";
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    $rowCount = mysqli_num_rows($result);
    if($rowCount>0){
        array_push($errors, "Email already exists! Please login");
    }

    if (count($errors) > 0){
        foreach($errors as $error){
            echo "<div class='alert alert-danger'>$error</div>";
        }
    } else {
        require_once "Database.php";
        $sql = "INSERT INTO users(name, email, password) VALUES( ?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);
        $prepareStmt = mysqli_stmt_prepare($stmt, $sql);
        if ($prepareStmt) {
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $passwordHash);
            mysqli_stmt_execute($stmt);
            
            // Auto Login Logic
            session_start();
            $newUserId = mysqli_insert_id($conn);
            $_SESSION["user_id"] = $newUserId;
            $_SESSION["user_name"] = $name;
            $_SESSION["is_admin"] = 0; // Default to normal user

            // Redirect to logged-in home
            header("Location: logHome.php");
            exit();
            
        }else{
            die("Something went wrong");
        }
    }
}
?>

    <a href="#" class="logo"><img src="image/logo.png" alt="logo"></a>

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

    <div class="divider">
        <span>or register with email</span>
    </div>

    <h2>Register</h2>
        <form action="Register.php" method="post">
            <div class="form-group">
                <input type="text" class="form-control" name="name" placeholder="First Name">
            </div>
            <div class="form-group">
                <input type="email" class="form-control" name= "email" placeholder="Email Address">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="confirmpassword" placeholder="Confirm Password">
            </div>
             <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Register" name="submit">
            </div>


    <div>
        
        
    </div>

    <div class="login-link">
        <span>Already have an Account?</span>
        <a href="login.php">Log now</a>
    </div>
        </form>
    </div>
    <script src="Register.js"></script>
</body>
</html>