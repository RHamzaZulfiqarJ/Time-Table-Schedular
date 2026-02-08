<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: ../Auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['U_ID'])) {
    $_SESSION['error'] = "Invalid request.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$U_ID = (int) $_POST['U_ID'];
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($U_ID <= 0 || $name === '' || $phone === '' || $email === '' || $password === '') {
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

// Check if email already exists for another teacher
$stmt = $connection->prepare("SELECT U_ID FROM user WHERE Email = ? AND U_ID != ?");
$stmt->bind_param("si", $email, $U_ID);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email already exists.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

// Update teacher
$stmt = $connection->prepare("
    UPDATE user
    SET Name = ?, Phone = ?, Email = ?, Password = ?
    WHERE U_ID = ? AND Role = 'Teacher'
");
$stmt->bind_param("ssssi", $name, $phone, $email, $password, $U_ID);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $_SESSION['error'] = "No changes made or update failed.";
} else {
    $_SESSION['success'] = "Teacher updated successfully.";
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>