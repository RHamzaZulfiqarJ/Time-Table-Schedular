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
$Section_Name = trim($_POST['Section_Name'] ?? '');
$Day = $_POST['Day'] ?? '';
$Start_Time = $_POST['Start_Time'] ?? '';
$End_Time = $_POST['End_Time'] ?? '';
$Room = trim($_POST['Room'] ?? '');

if ($S_ID <= 0 || $Section_Name === '' || $Day === '' || $Start_Time === '' || $End_Time === '' || $Room === '') {
    $_SESSION['error'] = "All fields are required.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

if ($Start_Time >= $End_Time) {
    $_SESSION['error'] = "Invalid time range.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$stmt = $connection->prepare("SELECT C_Code FROM course_schedule WHERE S_ID = ?");
$stmt->bind_param("i", $S_ID);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    $_SESSION['error'] = "Section not found.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$C_Code = $course['C_Code'];

$stmt = $connection->prepare("
    SELECT S_ID
    FROM course_schedule
    WHERE C_Code = ? AND Section_Name = ? AND S_ID != ?
");
$stmt->bind_param("ssi", $C_Code, $Section_Name, $S_ID);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Section name already exists for this course.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$stmt = $connection->prepare("
    SELECT S_ID
    FROM course_schedule
    WHERE Room = ? AND Day = ? AND Start_Time < ? AND End_Time > ? AND S_ID != ?
");
$stmt->bind_param("ssssi", $Room, $Day, $End_Time, $Start_Time, $S_ID);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Room already booked for this time slot.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}
// Update the section
$stmt = $connection->prepare("
    UPDATE course_schedule
    SET Section_Name = ?, Day = ?, Start_Time = ?, End_Time = ?, Room = ?
    WHERE S_ID = ?
");

$stmt->bind_param("sssssi", $Section_Name, $Day, $Start_Time, $End_Time, $Room, $S_ID);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $_SESSION['error'] = "No changes made or section update failed.";
} else {
    $_SESSION['success'] = "Section updated successfully.";
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>