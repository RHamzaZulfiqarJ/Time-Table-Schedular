<?php
include "../db.php";
session_start();

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Student') {
    header("Location: ../Auth/login.php");
    exit;
}

$student_id = $_SESSION['U_ID'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request.";
    header("Location: Dashboard.php");
    exit;
}

$section_id = $_POST['S_ID'] ?? null;

if (!$section_id || !is_numeric($section_id)) {
    $_SESSION['error'] = "Invalid section selected.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$section_id = (int) $section_id;

// 1️⃣ Check if section exists
$stmt = $connection->prepare("
    SELECT cs.C_Code, cs.Section_Name, cs.Day, cs.Start_Time, cs.End_Time, c.Student_Limit
    FROM course_schedule cs
    JOIN courses c ON cs.C_Code = c.C_Code
    WHERE cs.S_ID = ?
");
$stmt->bind_param("i", $section_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Section not found.";
    $stmt->close();
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$section = $result->fetch_assoc();
$stmt->close();

// 2️⃣ Check if student already enrolled
$stmt = $connection->prepare("SELECT * FROM enrollments WHERE U_ID = ? AND S_ID = ?");
$stmt->bind_param("ii", $student_id, $section_id);
$stmt->execute();
$already_enrolled = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($already_enrolled) {
    $_SESSION['error'] = "You are already enrolled in this section.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

// 3️⃣ Check if section limit is reached
$stmt = $connection->prepare("SELECT COUNT(*) AS enrolled FROM enrollments WHERE S_ID = ?");
$stmt->bind_param("i", $section_id);
$stmt->execute();
$count_res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($count_res['enrolled'] >= $section['Student_Limit']) {
    $_SESSION['error'] = "This section is full. Cannot enroll.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

// 4️⃣ Check for time conflicts with existing enrollments
$stmt = $connection->prepare("
    SELECT cs.Day, cs.Start_Time, cs.End_Time
    FROM enrollments e
    JOIN course_schedule cs ON e.S_ID = cs.S_ID
    WHERE e.U_ID = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$current_enrollments = $stmt->get_result();
$stmt->close();

$conflict = false;
while ($row = $current_enrollments->fetch_assoc()) {
    if ($row['Day'] === $section['Day']) {
        $new_start = strtotime($section['Start_Time']);
        $new_end = strtotime($section['End_Time']);
        $existing_start = strtotime($row['Start_Time']);
        $existing_end = strtotime($row['End_Time']);

        if (($new_start < $existing_end) && ($new_end > $existing_start)) {
            $conflict = true;
            break;
        }
    }
}

if ($conflict) {
    $_SESSION['error'] = "Time conflict with another section you are enrolled in.";
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

// 5️⃣ Enroll the student
$stmt = $connection->prepare("INSERT INTO enrollments (U_ID, S_ID) VALUES (?, ?)");
if (!$stmt) {
    $_SESSION['error'] = "Failed to prepare enrollment: " . $connection->error;
    $connection->close();
    header("Location: Dashboard.php");
    exit;
}

$stmt->bind_param("ii", $student_id, $section_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Enrolled in {$section['C_Code']} - Section {$section['Section_Name']} successfully.";
} else {
    $_SESSION['error'] = "Failed to enroll: " . $stmt->error;
}

$stmt->close();
$connection->close();
header("Location: Dashboard.php");
exit;
?>