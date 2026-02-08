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

$teacherId = (int) $_POST['U_ID'];

$stmt = $connection->prepare(
    "DELETE FROM user WHERE U_ID = ? AND Role = 'Teacher'"
);

$stmt->bind_param("i", $teacherId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $_SESSION['error'] = "Teacher could not be deleted.";
} else {
    $_SESSION['success'] = "Teacher deleted successfully.";
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;