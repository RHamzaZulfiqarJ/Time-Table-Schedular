<?php
include "../db.php";
session_start();

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Teacher') {
    header("Location: ../Auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: Dashboard.php");
    exit;
}

$teacher_id = $_SESSION['U_ID'];
$section_id = $_POST['S_ID'] ?? null;

if (!$section_id || !is_numeric($section_id)) {
    $_SESSION['error'] = "Invalid section selected.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$section_id = (int)$section_id;

// 1️⃣ Verify section exists
$stmt = $connection->prepare("SELECT * FROM course_schedule WHERE S_ID = ?");
$stmt->bind_param("i", $section_id);
$stmt->execute();
$section = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$section) {
    $_SESSION['error'] = "Section not found.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

// 2️⃣ Assign teacher
$stmt = $connection->prepare("UPDATE course_schedule SET Teacher_ID = ? WHERE S_ID = ?");

if (!$stmt) {
    $_SESSION['error'] = "Failed to prepare statement: " . $connection->error;
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$stmt->bind_param("ii", $teacher_id, $section_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Teacher assigned to section successfully.";
} else {
    $_SESSION['error'] = "Failed to assign teacher: " . $stmt->error;
}

$stmt->close();
$connection->close();
header("Location: Dashboard.php");
exit;
?>