<?php
include "../db.php";
session_start();

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Student') {
    header("Location: ../Auth/login.php");
    exit;
}

$student_id = $_SESSION['U_ID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_id = $_POST['S_ID'];

    // 1️⃣ Fetch section details and student limit
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
        $stmt->close();
        $connection->close();
        die("Section not found.");
    }

    $section = $result->fetch_assoc();
    $stmt->close();

    // 2️⃣ Check if section limit is reached
    $stmt = $connection->prepare("SELECT COUNT(*) AS enrolled FROM enrollments WHERE S_ID = ?");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $count_res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($count_res['enrolled'] >= $section['Student_Limit']) {
        $connection->close();
        die("This section is full. Cannot enroll.");
    }

    // 3️⃣ Check for time conflict with student's current enrollments
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
            // Compare times
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
        $connection->close();
        die("Time conflict with another section you are enrolled in.");
    }

    // 4️⃣ Enroll the student
    $stmt = $connection->prepare("INSERT INTO enrollments (U_ID, S_ID) VALUES (?, ?)");
    $stmt->bind_param("ii", $student_id, $section_id);
    $stmt->execute();
    $stmt->close();
    $connection->close();

    header("Location: Dashboard.php");
    exit;
}
?>
