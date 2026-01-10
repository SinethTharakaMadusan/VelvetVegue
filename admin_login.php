<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel ="stylesheet" href ="log.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
</head>
<body>
    
    <div class="container">
    <?php
    session_start();

    if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === 1) {
        header("Location: Admin.php");
        exit();
    }

    if (isset($_POST["login"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];
        
        if (empty($username) || empty($password)) {
            echo "<div class='alert alert-danger'>Please fill in all fields</div>";
        } else {
            
            require_once "Database.php";
            
            $sql = "SELECT * FROM admins WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION["user_id"] = $row['admin_id'];
                    $_SESSION["user_name"] = $row['name']; 
                    $_SESSION["username"] = $row['username'];
                    $_SESSION["is_admin"] = 1;
                    header("Location: Admin.php");
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Invalid Username or Password</div>";
                }
            } else {
                 echo "<div class='alert alert-danger'>Invalid Username or Password</div>";
            }
            $stmt->close();
        }
    }
    ?>

        <a href="#" class="logo"><img src="image/logo.png" alt="logo"></a>
        <h2>Admin Login</h2>
        <form action="admin_login.php" method="post">
           
            <div class="form-group">
                <input type="text" class="form-control" name="username" placeholder="Enter Username">
            </div>
             <div class="form-group">
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter Password">
            </div>
            
            
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="showPassword" onclick="togglePassword()">
                <label class="form-check-label" for="showPassword">Show Password</label>
            </div>

            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login" name="login">
            </div>
            
        </form>
    </div>

    <script src="login.js"></script>
    <script>
        
        function togglePassword() {
            var x = document.getElementById("password");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</body>
</html>
