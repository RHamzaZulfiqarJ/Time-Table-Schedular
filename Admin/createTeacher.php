<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: ../Auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request.";
    header("Location: Dashboard.php");
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($name === "" || $phone === "" || $email === "" || $password === "") {
    $_SESSION['error'] = "All fields are required.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email address.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$role = "Teacher";

$stmt = $connection->prepare(
    "INSERT INTO user (Name, Phone, Email, Role, Password) VALUES (?, ?, ?, ?, ?)"
);

$stmt->bind_param("sssss", $name, $phone, $email, $role, $password);

if (!$stmt->execute()) {
    if ($connection->errno == 1062) { // Duplicate email
        $_SESSION['error'] = "Email already exists.";
    } else {
        $_SESSION['error'] = "Failed to create teacher.";
    }
} else {
    $_SESSION['success'] = "Teacher created successfully.";
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>