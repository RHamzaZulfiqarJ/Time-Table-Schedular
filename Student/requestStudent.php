<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Student') {
    header("Location: ../Auth/login.php");
    exit;
}

$studentId = $_SESSION['U_ID'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request.";
    header("Location: Dashboard.php");
    exit;
}

$S_ID = $_POST['S_ID'] ?? null;

if (!$S_ID || !is_numeric($S_ID)) {
    $_SESSION['error'] = "Invalid section selected.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$S_ID = (int) $S_ID;

// 1️⃣ Check if section exists
$stmt = $connection->prepare("SELECT * FROM course_schedule WHERE S_ID = ?");
$stmt->bind_param("i", $S_ID);
$stmt->execute();
$section = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$section) {
    $_SESSION['error'] = "Section not found.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

// 2️⃣ Check if a pending request already exists
$stmt = $connection->prepare("
    SELECT * FROM requests 
    WHERE U_ID = ? AND S_ID = ? AND Role='Student' AND Status='Pending'
");
$stmt->bind_param("ii", $studentId, $S_ID);
$stmt->execute();
$existingRequest = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($existingRequest) {
    $_SESSION['error'] = "You already have a pending request for this section.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

// 3️⃣ Insert new drop request
$stmt = $connection->prepare("
    INSERT INTO requests (U_ID, Role, S_ID, Request_Type, Request_Data)
    VALUES (?, 'Student', ?, 'Drop_Section', NULL)
");

if (!$stmt) {
    $_SESSION['error'] = "Failed to prepare request: " . $connection->error;
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$stmt->bind_param("ii", $studentId, $S_ID);

if ($stmt->execute()) {
    $_SESSION['success'] = "Drop request submitted successfully.";
} else {
    $_SESSION['error'] = "Failed to submit request: " . $stmt->error;
}

$stmt->close();
$connection->close();
header("Location: Dashboard.php");
exit;
?>