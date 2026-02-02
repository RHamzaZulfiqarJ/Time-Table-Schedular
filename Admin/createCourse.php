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
$title = trim($_POST['Title']);
$description = trim($_POST['Description']);
$limit = trim($_POST['Student_Limit']);

if ($code === "" || $title === "" || $limit === "") {
    die("Required fields missing");
}

$stmt = $connection->prepare(
    "INSERT INTO courses (C_Code, Title, Description, Student_Limit) VALUES (?, ?, ?, ?)"
);

$stmt->bind_param("sssi", $code, $title, $description, $limit);

if (!$stmt->execute()) {
    if ($connection->errno == 1062) {
        die("Course code already exists");
    } else {
        die("Error creating course");
    }
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>