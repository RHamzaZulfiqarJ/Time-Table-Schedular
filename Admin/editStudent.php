<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: ../Auth/login.php");
    exit;
}

if (!isset($_POST['U_ID'])) {
    $_SESSION['error'] = "Invalid request";
    header("Location: Dashboard.php");
    exit;
}

$studentId = (int) $_POST['U_ID'];
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$password = trim($_POST['password']);

if ($name === "" || $phone === "" || $email === "" || $password === "") {
    $_SESSION['error'] = "All fields are required.";
    header("Location: Dashboard.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email.";
    header("Location: Dashboard.php");
    exit;
}

$stmt = $connection->prepare(
    "UPDATE user SET Name = ?, Phone = ?, Email = ?, Password = ? WHERE U_ID = ? AND Role = 'Student'"
);
$stmt->bind_param("ssssi", $name, $phone, $email, $password, $studentId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $_SESSION['error'] = "Student could not be updated.";
} else {
    $_SESSION['success'] = "Student updated successfully.";
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>