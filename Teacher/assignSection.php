<?php
include "../db.php";
session_start();

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Teacher') {
    header("Location: ../Auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_SESSION['U_ID'];
    $section_id = $_POST['S_ID'];

    // Update the course_schedule to assign this teacher
    $stmt = $connection->prepare("UPDATE course_schedule SET Teacher_ID = ? WHERE S_ID = ?");
    $stmt->bind_param("ii", $teacher_id, $section_id);
    $stmt->execute();
    $stmt->close();
    $connection->close();

    // Redirect back to dashboard
    header("Location: Dashboard.php");
    exit;
}
?>
