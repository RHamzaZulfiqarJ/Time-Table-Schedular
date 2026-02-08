<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: ../Auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['S_ID'])) {
    $_SESSION['error'] = "Invalid request.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$S_ID = (int) $_POST['S_ID'];

if ($S_ID <= 0) {
    $_SESSION['error'] = "Invalid section ID.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$stmt = $connection->prepare("DELETE FROM enrollments WHERE S_ID = ?");
$stmt->bind_param("i", $S_ID);
$stmt->execute();
$stmt->close();

$stmt = $connection->prepare("DELETE FROM course_schedule WHERE S_ID = ?");
$stmt->bind_param("i", $S_ID);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $_SESSION['error'] = "Section could not be deleted.";
} else {
    $_SESSION['success'] = "Section deleted successfully.";
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>