<?php

    include "../db.php";
    session_start();

    $error = $_SESSION['error'] ?? '';
    $success = $_SESSION['success'] ?? '';
    unset($_SESSION['error'], $_SESSION['success']);

    if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Teacher') {
        header("Location: ../Auth/login.php");
        exit;
    }

    $teacher_id = $_SESSION['U_ID'];

    $sql = "
        SELECT cs.S_ID, cs.C_Code, c.Title AS Course_Title, cs.Section_Name, 
            cs.Day, cs.Start_Time, cs.End_Time, cs.Room
        FROM course_schedule cs
        JOIN courses c ON cs.C_Code = c.C_Code
        WHERE cs.Teacher_ID IS NULL
        ORDER BY cs.C_Code, cs.Section_Name
    ";

    $available_sections = $connection->query($sql);

    $sql2 = "
        SELECT cs.S_ID, cs.C_Code, c.Title AS Course_Title, cs.Section_Name, 
            cs.Day, cs.Start_Time, cs.End_Time, cs.Room
        FROM course_schedule cs
        JOIN courses c ON cs.C_Code = c.C_Code
        WHERE cs.Teacher_ID = ?
        ORDER BY cs.C_Code, cs.Section_Name
    ";
    $stmt = $connection->prepare($sql2);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $enrolled_sections = $stmt->get_result();
    $stmt->close();

    $sql3 = "
        SELECT 
            r.Request_Type,
            r.Request_Data,
            r.Status,
            cs.C_Code,
            cs.Section_Name
        FROM requests r
        JOIN course_schedule cs ON r.S_ID = cs.S_ID
        WHERE r.U_ID = ?
        ORDER BY r.Created_At DESC
    ";

    $stmt = $connection->prepare($sql3);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $teacher_requests = $stmt->get_result();
    $stmt->close();

    $connection->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | Timetable Scheduler</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Dashboard.css">

</head>
<body>

<div class="main">

    <div class="topbar">
        <h1>Teacher Dashboard</h1>
        <div class="teacher-badge"><?php echo $_SESSION['Name']; ?></div>
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

    <div class="section" id="availableCourses">
        <div class="section-header">
            <h2>Available Courses</h2>
        </div>
        <div class="courses">
            <?php while ($course = $available_sections->fetch_assoc()): ?>
                <div class="course-card enrolled">
                    <div class="card-glow"></div>
                    <div class="card-header">
                        <span class="course-code">[<?php echo htmlspecialchars($course['C_Code']); ?>] <?php echo htmlspecialchars($course['Course_Title']); ?></span>
                        <span class="section-tag">Sec <?php echo htmlspecialchars($course['Section_Name']); ?></span>
                    </div>
                    
                    <div class="card-details">
                        <div class="detail-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span><?php echo htmlspecialchars($course['Day']); ?></span>
                        </div>
                        <div class="detail-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span><?php echo htmlspecialchars($course['Start_Time'] . ' - ' . $course['End_Time']); ?></span>
                        </div>
                        <div class="detail-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10h-6a4 4 0 0 0-8 0H3"></path><path d="M3 10a4 4 0 0 1 8 0h6a4 4 0 0 1 8 0"></path><path d="M3 10v10a4 4 0 0 0 4 4h10a4 4 0 0 0 4-4V10"></path></svg>
                            <span><?php echo htmlspecialchars($course['Room']); ?></span>
                        </div>
                    </div>

                    <form action="assignSection.php" method="POST">
                        <input type="hidden" name="S_ID" value="<?php echo htmlspecialchars($course['S_ID']); ?>">
                        <button class="makeup_class_btn">
                            <span>Enroll In This Section</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="section" id="weeklyTimetable">
        <div class="section-header">
            <h2>Weekly Timetable</h2>
        </div>
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
                    <th>Request Change</th>
                </tr>
                <?php while($row = $enrolled_sections->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['C_Code'] ?></td>
                    <td><?= $row['Course_Title'] ?></td>
                    <td><?= $row['Section_Name'] ?></td>
                    <td><?= $row['Day'] ?></td>
                    <td><?= $row['Start_Time'] ?></td>
                    <td><?= $row['End_Time'] ?></td>
                    <td><?= $row['Room'] ?></td>
                    <td>
                        <button 
                            class="modal-btn"
                            data-modal="teacherRequest"
                            data-sid="<?= $row['S_ID'] ?>"
                            data-day="<?= $row['Day'] ?>"
                            data-start="<?= $row['Start_Time'] ?>"
                            data-end="<?= $row['End_Time'] ?>">
                            Request Change
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <div class="section" id="requests">
        <div class="section-header">
            <h2>My Requests</h2>
        </div>

        <div class="timetable">
            <table>
                <tr>
                    <th>Course</th>
                    <th>Section</th>
                    <th>Request Type</th>
                    <th>Request Data</th>
                    <th>Status</th>
                </tr>

                <?php if ($teacher_requests->num_rows > 0): ?>
                    <?php while ($req = $teacher_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['C_Code']) ?></td>
                        <td><?= htmlspecialchars($req['Section_Name']) ?></td>
                        <td><?= str_replace('_', ' ', $req['Request_Type']) ?></td>
                        <td>
                            <?php
                            if ($req['Request_Data']) {
                                $data = json_decode($req['Request_Data'], true);

                                if (isset($data['Day'])) {
                                    echo htmlspecialchars($data['Day']) . " | ";
                                }
                                if (isset($data['Start'], $data['End'])) {
                                    echo htmlspecialchars($data['Start']) . " - " . htmlspecialchars($data['End']);
                                }
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>
                        <td>
                            <span class="status <?= strtolower($req['Status']) ?>">
                                <?= $req['Status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No requests submitted yet.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

<!-- Make-up Class Modal -->
<div class="modal" id="teacherRequest">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Submit a Request</h3>
            <p>Request changes to a section you are assigned to.</p>
        </div>

        <form action="requestTeacher.php" method="POST" class="styled-form">

            <input type="hidden" name="S_ID" id="requestSectionId">

            <div class="form-group">
                <label>Request Type</label>
                <select name="request_type" id="teacherRequestType" required>
                    <option value="" disabled selected>Select type...</option>
                    <option value="Drop_Section">Drop Section</option>
                    <option value="Change_Section_Time">Change Time</option>
                    <option value="Change_Section_Day">Change Day & Time</option>
                </select>
            </div>

            <div class="form-row" id="teacherTimeChange" style="display:none;">
                <div id="teacherDay" class="form-group">
                    <label>New Day</label>
                    <select name="new_day" id="newDay">
                        <option>Monday</option>
                        <option>Tuesday</option>
                        <option>Wednesday</option>
                        <option>Thursday</option>
                        <option>Friday</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>New Start Time</label>
                    <input type="time" name="new_start" id="newStart">
                </div>
                <div class="form-group">
                    <label>New End Time</label>
                    <input type="time" name="new_end" id="newEnd">
                </div>
            </div>

            <button type="submit" class="submit-btn">Submit Request</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("click", e => {

        const activeModal = document.querySelector(".modal.active");

        const btn = e.target.closest(".modal-btn");

        if (btn) {
            const modal = document.getElementById(btn.dataset.modal);
            modal.classList.add("active");

            document.getElementById("requestSectionId").value = btn.dataset.sid;
            document.getElementById("newDay").value = btn.dataset.day;
            document.getElementById("newStart").value = btn.dataset.start;
            document.getElementById("newEnd").value = btn.dataset.end;

            return;
        }

        if (
            activeModal &&
            !activeModal.querySelector(".modal-content").contains(e.target)
        ) {
            activeModal.classList.remove("active");
        }
    });

    document.getElementById("teacherRequestType")
        .addEventListener("change", e => {

            const box = document.getElementById("teacherTimeChange");
            const day = document.getElementById("teacherDay");

            if (
                e.target.value === "Change_Section_Time" ||
                e.target.value === "Change_Section_Day"
            ) {
                box.style.display = "grid";
            } else {
                box.style.display = "none";
            }

            day.style.display =
                e.target.value === "Change_Section_Day" ? "flex" : "none";
        });

    document.addEventListener("keydown", e => {
        if (e.key === "Escape") {
            const modal = document.querySelector(".modal.active");
            if (modal) modal.classList.remove("active");
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
