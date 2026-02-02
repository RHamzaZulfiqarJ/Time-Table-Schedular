<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: ../Auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$code = trim($_POST['C_Code']);
$section = trim($_POST['Section_Name']);
$day = $_POST['Day'];
$start = $_POST['Start_Time'];
$end = $_POST['End_Time'];
$room = trim($_POST['Room']);

if ($code === "" || $section === "" || $start === "" || $end === "" || $room === "") {
    die("All fields are required");
}

if ($start >= $end) {
    die("Invalid time range");
}

$stmt = $connection->prepare(
    "INSERT INTO course_schedule 
    (C_Code, Section_Name, Day, Start_Time, End_Time, Room)
    VALUES (?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param("ssssss", $code, $section, $day, $start, $end, $room);

if (!$stmt->execute()) {
    die("Failed to create section");
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>