<?php include "../db.php";

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {
        // Fetch user from database
        $stmt = $connection->prepare("SELECT U_ID, Name, Email, Role, Password FROM User WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if ($password == $user['Password']) {
                // Set session variables
                $_SESSION['U_ID'] = $user['U_ID'];
                $_SESSION['Name'] = $user['Name'];
                $_SESSION['Email'] = $user['Email'];
                $_SESSION['Role'] = $user['Role'];

                // Redirect based on role
                if ($user['Role'] == "Admin") {
                    header("Location: ../Admin/dashboard.php");
                } elseif ($user['Role'] == "Teacher") {
                    header("Location: ../Teacher/Dashboard.php");
                } elseif ($user['Role'] == "Student") {
                    header("Location: ../Student/Dashboard.php");
                } else {
                    $error = "Unknown role!";
                }
                exit();
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "User not found with this email!";
        }

        $stmt->close();
    }
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | University Timetable Scheduler</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>
<body>

<div class="auth-wrapper">

    <div class="auth-header">
        <h1>Welcome Back</h1>
        <p>Login to your timetable portal</p>
    </div>

    <?php
        if (!empty($error)) {
            echo "<p style='color:red;'>$error</p>";
        }
    ?>

    <form style="margin-top: 20px;" method="POST" action="login.php">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="john@example.com" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="options-row">
            <span>
                <input type="checkbox" id="remember"> 
                <label for="remember">Remember me</label>
            </span>
            <a href="#">Forgot password?</a>
        </div>

        <button type="submit" class="auth-btn">Login</button>
    </form>

    <div class="auth-footer">
        Don’t have an account? <a href="./signup.php">Create one</a>
    </div>

</div>

</body>
</html>