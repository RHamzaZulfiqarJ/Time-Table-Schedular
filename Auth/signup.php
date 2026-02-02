<?php include "../db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($phone) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {

        $role = "Student";

        $stmt = $connection->prepare("INSERT INTO User (Name, Phone, Email, Role, Password) VALUES (?, ?, ?, ?, ?)");

        $stmt->bind_param("sssss", $name, $phone, $email, $role, $password);

        if ($stmt->execute()) {
            echo "<script>window.location.href = './login.php';</script>";
        } else {
            if ($connection->errno == 1062) {
                $error = "Email already exists!";
            } else {
                $error = "Error: " . $connection->error;
            }
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
    <title>Signup | Timetable Scheduler</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="signup.css">

</head>
<body>

<div class="auth-wrapper">

    <div class="auth-header">
        <h1>Create Account</h1>
        <p>Timetable Scheduler</p>
    </div>

    <?php
        if (!empty($error)) {
            echo "<p style='color:red;'>$error</p>";
        } elseif (!empty($success)) {
            echo "<p style='color:green;'>$success</p>";
        }
    ?>

    <form style="margin-top: 20px;" method="POST" action="signup.php">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" name="signup" class="auth-btn">Signup</button>
    </form>


    <div class="auth-footer">
        Already have an account? <a href="./login.php">Login</a>
    </div>

</div>

</body>
</html>