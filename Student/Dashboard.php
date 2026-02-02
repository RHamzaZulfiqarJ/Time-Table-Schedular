<?php

    include "../db.php";
    session_start();

    if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Student') {
        header("Location: ../Auth/login.php");
        exit;
    }

    $student_id = $_SESSION['U_ID'];

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
        <div class="teacher-badge"><?php echo $_SESSION['Name']; ?></div>
        <button onclick="window.location.href='../Auth/logout.php'" class="logout-btn">Logout</button>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>Available Sections for Enrollment</h2>
        </div>
        
        <div class="courses">
            <?php while($row = $available_sections->fetch_assoc()): ?>
                <div class="course-card enrollment-card">
                    <div class="card-glow"></div>
                    
                    <div class="card-top">
                        <span class="course-code"><?= $row['C_Code'] ?></span>
                        <span class="section-tag">Section <?= $row['Section_Name'] ?></span>
                    </div>

                    <div class="card-main">
                        <h3><?= $row['Course_Title'] ?></h3>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <small>Day</small>
                                <span><?= $row['Day'] ?></span>
                            </div>
                            <div class="info-item">
                                <small>Room</small>
                                <span><?= $row['Room'] ?></span>
                            </div>
                            <div class="info-item">
                                <small>Schedule</small>
                                <span><?= $row['Start_Time'] ?> — <?= $row['End_Time'] ?></span>
                            </div>
                            <div class="info-item">
                                <small>Schedule</small>
                                <span><?= $row['Start_Time'] ?> — <?= $row['End_Time'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-bottom">
                        <div class="seats-badge <?= ($row['Seats_Remaining'] < 5) ? 'low-seats' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span><?= $row['Seats_Remaining'] ?> Seats Left</span>
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

    <div class="section">
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
                    <th>Teacher</th>
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
                    <td><?= $row['Teacher_Name'] ?? 'Not Assigned' ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

</div>

<!-- Make-up Class Modal -->
<div class="modal" id="enroll_course">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add a Teacher</h3>
            <p>Create a new teacher profile for the academic semester.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert error-alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?php echo $error; ?></span>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="alert success-alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <form action="createTeacher.php" method="POST" class="styled-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" placeholder="e.g. John Doe" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="number" name="phone" placeholder="e.g. 1234567890" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" name="email" placeholder="e.g. john.doe@example.com" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter a secure password" required>
            </div>

            <button type="submit" class="submit-btn">Register Teacher</button>
        </form>
    </div>
</div>

<script>
    const modalButtons = document.querySelectorAll('.enroll_course');
    const modal = document.getElementById('enroll_course');

    modalButtons.forEach(btn => {
        btn.addEventListener('click', () => modal.classList.add('active'));
    });

    modal.addEventListener('click', e => {
        if(e.target === modal) modal.classList.remove('active');
    });
</script>

</body>
</html>
