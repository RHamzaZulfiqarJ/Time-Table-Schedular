<?php
include "../db.php";
session_start();

    $error = $_SESSION['error'] ?? '';
    $success = $_SESSION['success'] ?? '';
    unset($_SESSION['error'], $_SESSION['success']);

if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Student') {
    header("Location: ../Auth/login.php");
    exit;
}

$student_id = $_SESSION['U_ID'];

// Available sections for enrollment
$sql = "
    SELECT cs.S_ID, cs.C_Code, c.Title AS Course_Title, cs.Section_Name,
           cs.Day, cs.Start_Time, cs.End_Time, cs.Room, c.Student_Limit,
           COUNT(e.E_ID) AS Current_Enrollment,
           (c.Student_Limit - COUNT(e.E_ID)) AS Seats_Remaining
    FROM course_schedule cs
    JOIN courses c ON cs.C_Code = c.C_Code
    LEFT JOIN enrollments e ON cs.S_ID = e.S_ID
    WHERE cs.S_ID NOT IN (
        SELECT S_ID 
        FROM enrollments 
        WHERE U_ID = ?
    )
    GROUP BY cs.S_ID
    HAVING Seats_Remaining > 0
    ORDER BY cs.C_Code, cs.Section_Name
";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$available_sections = $stmt->get_result();
$stmt->close();

// Enrolled sections
$sql = "
    SELECT cs.S_ID, cs.C_Code, c.Title AS Course_Title, cs.Section_Name, 
           cs.Day, cs.Start_Time, cs.End_Time, cs.Room,
           u.Name AS Teacher_Name
    FROM enrollments e
    JOIN course_schedule cs ON e.S_ID = cs.S_ID
    JOIN courses c ON cs.C_Code = c.C_Code
    LEFT JOIN user u ON cs.Teacher_ID = u.U_ID
    WHERE e.U_ID = ?
    ORDER BY cs.Day, cs.Start_Time
";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrolled_sections = $stmt->get_result();
$stmt->close();

// Student requests (ongoing requests)
$sql2 = "
    SELECT r.R_ID, r.S_ID, r.Request_Type, r.Request_Data, r.Status, cs.C_Code, cs.Section_Name
    FROM requests r
    JOIN course_schedule cs ON r.S_ID = cs.S_ID
    WHERE r.U_ID = ? AND r.Role = 'Student'
    ORDER BY r.R_ID DESC
";
$stmt = $connection->prepare($sql2);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_requests = $stmt->get_result();
$stmt->close();

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard | Timetable Scheduler</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Dashboard.css">
</head>
<body>

<div class="main">

    <div class="topbar">
        <h1>Student Dashboard</h1>
        <div class="teacher-badge"><?= $_SESSION['Name'] ?></div>
        <button onclick="window.location.href='../Auth/logout.php'" class="logout-btn">Logout</button>
    </div>

    <div id="toast-container" class="toast-container"></div>

    <?php if (!empty($error)): ?>
        <script>
            window.onload = () => {
                showToast("<?= addslashes($error) ?>", "error");
            };
        </script>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <script>
            window.onload = () => {
                showToast("<?= addslashes($success) ?>", "success");
            };
        </script>
    <?php endif; ?>

    <!-- Available Sections -->
    <div class="section">
        <div class="section-header">
            <h2>Available Sections for Enrollment</h2>
        </div>
        <div class="courses">
            <?php while($row = $available_sections->fetch_assoc()): ?>
            <div class="course-card enrollment-card">
                <div class="card-top">
                    <span class="course-code"><?= htmlspecialchars($row['C_Code']) ?></span>
                    <span class="section-tag">Section <?= htmlspecialchars($row['Section_Name']) ?></span>
                </div>
                <div class="card-main">
                    <h3><?= htmlspecialchars($row['Course_Title']) ?></h3>
                    <div class="info-grid">
                        <div class="info-item"><small>Day</small><span><?= $row['Day'] ?></span></div>
                        <div class="info-item"><small>Room</small><span><?= $row['Room'] ?></span></div>
                        <div class="info-item"><small>Time</small><span><?= $row['Start_Time'] ?> — <?= $row['End_Time'] ?></span></div>
                    </div>
                </div>
                <div class="card-bottom">
                    <div class="seats-badge <?= ($row['Seats_Remaining'] < 5) ? 'low-seats' : '' ?>">
                        <?= $row['Seats_Remaining'] ?> Seats Left
                    </div>
                    <form method="POST" action="enrollSection.php">
                        <input type="hidden" name="S_ID" value="<?= $row['S_ID'] ?>">
                        <button type="submit" class="enroll-action-btn">Enroll Now</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Enrolled Sections -->
    <div class="section">
        <div class="section-header"><h2>My Enrolled Sections</h2></div>
        <div class="timetable">
            <table>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Section</th>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Room</th>
                    <th>Teacher</th>
                    <th>Actions</th>
                </tr>
                <?php while($row = $enrolled_sections->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['C_Code']) ?></td>
                    <td><?= htmlspecialchars($row['Course_Title']) ?></td>
                    <td><?= htmlspecialchars($row['Section_Name']) ?></td>
                    <td><?= htmlspecialchars($row['Day']) ?></td>
                    <td><?= htmlspecialchars($row['Start_Time']) ?></td>
                    <td><?= htmlspecialchars($row['End_Time']) ?></td>
                    <td><?= htmlspecialchars($row['Room']) ?></td>
                    <td><?= htmlspecialchars($row['Teacher_Name'] ?? 'Not Assigned') ?></td>
                    <td>
                        <button
                            class="modal-btn"
                            data-modal="dropRequestModal"
                            data-sid="<?= $row['S_ID'] ?>"
                        >Drop Course</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- Student Requests -->
    <div class="section">
        <div class="section-header"><h2>My Requests</h2></div>
        <div class="timetable">
            <table>
                <tr>
                    <th>Course</th>
                    <th>Section</th>
                    <th>Request Type</th>
                    <th>Status</th>
                </tr>
                <?php if ($student_requests->num_rows > 0): ?>
                    <?php while($req = $student_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['C_Code']) ?></td>
                        <td><?= htmlspecialchars($req['Section_Name']) ?></td>
                        <td><?= str_replace('_',' ',$req['Request_Type']) ?></td>
                        <td>
                            <span class="status <?= strtolower($req['Status']) ?>">
                                <?= htmlspecialchars($req['Status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No requests submitted yet.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

<!-- Drop Request Modal -->
<div class="modal" id="dropRequestModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Drop Section</h3>
            <p>Are you sure you want to submit a drop request for this section?</p>
        </div>
        <form action="requestStudent.php" method="POST" class="styled-form">
            <input type="hidden" name="S_ID" id="requestSectionId">
            <button type="submit" class="submit-btn">Submit Request</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("click", e => {
        const btn = e.target.closest(".modal-btn");
        const activeModal = document.querySelector(".modal.active");

        if(btn) {
            const modal = document.getElementById(btn.dataset.modal);
            modal.classList.add("active");
            document.getElementById("requestSectionId").value = btn.dataset.sid;
            return;
        }

        if(activeModal && !activeModal.querySelector(".modal-content").contains(e.target)) {
            activeModal.classList.remove("active");
        }
    });

    document.addEventListener("keydown", e => {
        if(e.key === "Escape") {
            const modal = document.querySelector(".modal.active");
            if(modal) modal.classList.remove("active");
        }
    });

    function showToast(message, type = "success") {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        // Choose icon based on type
        const icon = type === "success" ? "✅" : "❌";
        
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        `;
        
        container.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('active'), 100);
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            toast.classList.remove('active');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
</script>

</body>
</html>