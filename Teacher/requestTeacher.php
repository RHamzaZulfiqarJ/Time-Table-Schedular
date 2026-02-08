<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Teacher') {
    header("Location: ../Auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: Dashboard.php");
    exit;
}

$teacherId = $_SESSION['U_ID'];
$S_ID = $_POST['S_ID'] ?? null;
$requestType = $_POST['request_type'] ?? null;

if (!$S_ID || !is_numeric($S_ID) || !$requestType) {
    $_SESSION['error'] = "All fields are required.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$S_ID = (int)$S_ID;

// Optional request data for time/day change
$requestData = (isset($_POST['new_day'], $_POST['new_start'], $_POST['new_end']) &&
                !empty($_POST['new_day']) &&
                !empty($_POST['new_start']) &&
                !empty($_POST['new_end']))
               ? json_encode([
                   'Day' => $_POST['new_day'],
                   'Start' => $_POST['new_start'],
                   'End' => $_POST['new_end']
                 ])
               : null;

// Check if a similar pending request already exists
$stmt = $connection->prepare("
    SELECT * FROM requests
    WHERE U_ID = ? AND S_ID = ? AND Role = 'Teacher' AND Status = 'Pending'
");
$stmt->bind_param("ii", $teacherId, $S_ID);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "You already have a pending request for this section.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

// Insert request
$stmt = $connection->prepare("
    INSERT INTO requests (U_ID, Role, S_ID, Request_Type, Request_Data)
    VALUES (?, 'Teacher', ?, ?, ?)
");

if (!$stmt) {
    $_SESSION['error'] = "Failed to prepare request: " . $connection->error;
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$stmt->bind_param("iiss", $teacherId, $S_ID, $requestType, $requestData);

if ($stmt->execute()) {
    $_SESSION['success'] = "Request submitted successfully.";
} else {
    $_SESSION['error'] = "Failed to submit request: " . $stmt->error;
}

$stmt->close();
$connection->close();

header("Location: Dashboard.php");
exit;
?>