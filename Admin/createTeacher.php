<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: ../Auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Dashboard.php");
    exit;
}

$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$password = trim($_POST['password']);

if ($name === "" || $phone === "" || $email === "" || $password === "") {
    die("All fields are required");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email");
}

$role = "Teacher";

$stmt = $connection->prepare(
    "INSERT INTO user (Name, Phone, Email, Role, Password)
     VALUES (?, ?, ?, ?, ?)"
);

$stmt->bind_param("sssss", $name, $phone, $email, $role, $password);

if (!$stmt->execute()) {
    if ($connection->errno == 1062) {
        die("Email already exists");
    } else {
        die("Failed to create teacher");
    }
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>
