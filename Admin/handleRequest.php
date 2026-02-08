<?php
session_start();
include "../db.php";

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: ../Auth/login.php");
    exit;
}

$R_ID = (int)$_POST['R_ID'];
$action = $_POST['action']; // approve or decline

$stmt = $connection->prepare("SELECT * FROM requests WHERE R_ID = ?");
$stmt->bind_param("i", $R_ID);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    $_SESSION['error'] = "Request not found.";
    header("Location: Dashboard.php");
    exit;
}

if ($action === 'approve') {
    if ($request['Request_Type'] === 'Drop_Section') {
        if ($request['Role'] === 'Student') {
            $connection->query("DELETE FROM enrollments WHERE U_ID={$request['U_ID']} AND S_ID={$request['S_ID']}");
        } else { // Teacher
            $connection->query("UPDATE course_schedule SET Teacher_ID=NULL WHERE S_ID={$request['S_ID']}");
        }
    }

    if ($request['Request_Type'] === 'Change_Section_Time' || $request['Request_Type'] === 'Change_Section_Day') {
        $data = json_decode($request['Request_Data'], true);
        $connection->query("UPDATE course_schedule SET Day='{$data['Day']}', Start_Time='{$data['Start']}', End_Time='{$data['End']}' WHERE S_ID={$request['S_ID']}");
    }

    $connection->query("UPDATE requests SET Status='Approved' WHERE R_ID=$R_ID");
    $_SESSION['success'] = "Request approved and applied successfully.";

} else {
    $connection->query("UPDATE requests SET Status='Declined' WHERE R_ID=$R_ID");
    $_SESSION['success'] = "Request declined.";
}

header("Location: Dashboard.php");
exit;
?>