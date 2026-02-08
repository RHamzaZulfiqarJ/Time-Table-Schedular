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

// Remove enrollments first
$connection->query("DELETE FROM enrollments WHERE U_ID = $studentId");

// Remove student
$connection->query("DELETE FROM user WHERE U_ID = $studentId AND Role = 'Student'");

$_SESSION['success'] = "Student deleted successfully.";
$connection->close();

header("Location: Dashboard.php");
exit;
?>