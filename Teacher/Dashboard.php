<?php

    include "../db.php";
    session_start();

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

    <div class="section">
        <div class="section-header">
            <h2>Enrolled Courses</h2>
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
