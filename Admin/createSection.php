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

$code = trim($_POST['C_Code'] ?? '');
$section = trim($_POST['Section_Name'] ?? '');
$day = $_POST['Day'] ?? '';
$start = $_POST['Start_Time'] ?? '';
$end = $_POST['End_Time'] ?? '';
$room = trim($_POST['Room'] ?? '');

if ($code === "" || $section === "" || $day === "" || $start === "" || $end === "" || $room === "") {
    $_SESSION['error'] = "All fields are required.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

if ($start >= $end) {
    $_SESSION['error'] = "Invalid time range.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$sql = "SELECT 1 FROM course_schedule WHERE C_Code = ? AND Section_Name = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("ss", $code, $section);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Section already exists for this course.";
    $stmt->close();
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}
$stmt->close();

$sql = "SELECT 1 FROM course_schedule WHERE Day = ? AND Room = ? AND NOT (End_Time <= ? OR Start_Time >= ?)";
$stmt = $connection->prepare($sql);
$stmt->bind_param("ssss", $day, $room, $start, $end);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Room already booked for this time slot.";
    $stmt->close();
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}
$stmt->close();

$sql = "INSERT INTO course_schedule (C_Code, Section_Name, Day, Start_Time, End_Time, Room) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $connection->prepare($sql);
$stmt->bind_param("ssssss", $code, $section, $day, $start, $end, $room);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $_SESSION['error'] = "Section could not be created.";
} else {
    $_SESSION['success'] = "Section created successfully.";
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>