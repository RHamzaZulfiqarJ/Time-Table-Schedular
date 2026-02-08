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
$title = trim($_POST['Title'] ?? '');
$description = trim($_POST['Description'] ?? '');
$limit = (int) ($_POST['Student_Limit'] ?? 0);

if ($code === "" || $title === "" || $limit <= 0) {
    $_SESSION['error'] = "Required fields missing or invalid.";
    header("Location: Dashboard.php");
    exit;
}

$checkSql = "SELECT C_Code FROM courses WHERE C_Code = ?";
$stmt = $connection->prepare($checkSql);
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Course code already exists.";
    $stmt->close();
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}
$stmt->close();

$insertSql = "INSERT INTO courses (C_Code, Title, Description, Student_Limit) VALUES (?, ?, ?, ?)";
$stmt = $connection->prepare($insertSql);
$stmt->bind_param("sssi", $code, $title, $description, $limit);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $_SESSION['error'] = "Course could not be created.";
} else {
    $_SESSION['success'] = "Course created successfully.";
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>