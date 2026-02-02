<?php
    include "../db.php";
    session_start();

    if (!isset($_SESSION['U_ID']) || $_SESSION['Role'] !== 'Admin') {
        header("Location: ../Auth/login.php");
        exit;
    }

    $sql = "SELECT * FROM courses";
    $courses = $connection->query($sql);

    $query = "
        SELECT cs.S_ID, cs.C_Code, cs.Section_Name, cs.Day, cs.Start_Time, cs.End_Time, cs.Room,
            u.Name AS Teacher_Name, COUNT(e.E_ID) AS Student_Count
        FROM course_schedule cs
        LEFT JOIN user u ON cs.Teacher_ID = u.U_ID
        LEFT JOIN enrollments e ON cs.S_ID = e.S_ID
        GROUP BY cs.S_ID
    ";
    $timetable = $connection->query($query);

    $sql = "SELECT * FROM user WHERE Role = 'Teacher'";
    $teachers = $connection->query($sql);

    $sql = "SELECT * FROM user WHERE Role = 'Student'";
    $students = $connection->query($sql);

    $connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Timetable Scheduler</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Dashboard.css">

</head>
<body>

<div class="main">

    <div class="topbar">
        <h1>Admin Dashboard</h1>
        <div class="admin-badge">Administrator</div>
        <button onclick="window.location.href='../Auth/logout.php'" class="logout-btn">Logout</button>
    </div>

    <div class="stats">
        <div class="stat-card">
            <h3>Total Sections</h3>
            <span><?php echo $timetable->num_rows; ?></span>
        </div>
        <div class="stat-card">
            <h3>Total Teachers</h3>
            <span><?php echo $teachers->num_rows; ?></span>
        </div>
        <div class="stat-card">
            <h3>Total Students</h3>
            <span><?php echo $students->num_rows; ?></span>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>Courses</h2>
            <div>
                <button class="teacher-btn">Add Teacher +</button>
                <button class="course-btn">Create Course +</button>
                <button class="section-btn">Create Section +</button>
            </div>
        </div>
        <div class="courses">
            <?php if ($courses->num_rows > 0): ?>
                <?php while($row = $courses->fetch_assoc()): ?>
                    <div class="course-card">
                        <div class="card-top">
                            <span class="code-badge"><?php echo htmlspecialchars($row['C_Code']); ?></span>
                        </div>
                        
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($row['Title']); ?></h3>
                            <p class="description"><?php echo htmlspecialchars($row['Description']); ?></p>
                        </div>

                        <div class="card-footer">
                            <div class="limit-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                <span><?php echo htmlspecialchars($row['Student_Limit']); ?> Students Max</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>            
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>All Sections</h2>
        </div>
        <div class="timetable">
            <table>
                <tr>
                    <th>Course Code</th>
                    <th>Section</th>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Room</th>
                    <th>Enrolled Students</th>
                    <th>Assigned Teacher</th>
                </tr>
                <?php if ($timetable->num_rows > 0): ?>
                    <?php while($row = $timetable->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['C_Code']); ?></td>
                            <td><?php echo htmlspecialchars($row['Section_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Day']); ?></td>
                            <td><?php echo htmlspecialchars($row['Start_Time']); ?></td>
                            <td><?php echo htmlspecialchars($row['End_Time']); ?></td>
                            <td><?php echo htmlspecialchars($row['Room']); ?></td>
                            <td><?php echo htmlspecialchars($row['Student_Count']); ?></td>
                            <td><?php echo htmlspecialchars($row['Teacher_Name'] ?? 'Not Assigned'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>All Teachers</h2>
        </div>
        <div class="timetable">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Password</th>
                </tr>
                <?php if ($teachers->num_rows > 0): ?>
                    <?php while($row = $teachers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo htmlspecialchars($row['Role']); ?></td>
                            <td><?php echo htmlspecialchars($row['Password']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>All Students</h2>
        </div>
        <div class="timetable">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Password</th>
                </tr>
                <?php if ($students->num_rows > 0): ?>
                    <?php while($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo htmlspecialchars($row['Role']); ?></td>
                            <td><?php echo htmlspecialchars($row['Password']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

<div class="modal" id="createCourse">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Course</h3>
            <p>Define a new subject for the academic semester.</p>
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

        <form action="createCourse.php" method="POST" class="styled-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="C_Code" placeholder="e.g. CS-101" required>
                </div>
                <div class="form-group">
                    <label>Student Limit</label>
                    <input type="number" name="Student_Limit" placeholder="e.g. 50" required>
                </div>
            </div>

            <div class="form-group">
                <label>Course Title</label>
                <input type="text" name="Title" placeholder="e.g. Data Structures & Algorithms" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="Description" rows="3" placeholder="Briefly describe the course objectives..."></textarea>
            </div>

            <button type="submit" class="submit-btn">Register Course</button>
        </form>
    </div>
</div>

<div class="modal" id="createSection">
    <div class="modal-content">
        <h3>Create New Section</h3>
        <p>Assign a course to a specific time and room.</p>
        
        <form method="POST" action="createSection.php" class="styled-form">
            <div class="form-group">
                <label>Course Selection</label>
                <select name="C_Code" required>
                    <option value="" disabled selected>Choose a course...</option>
                    <?php
                        include "../db.php";
                        $courseSql = "SELECT C_Code, Title FROM courses";
                        $courseResult = $connection->query($courseSql);
                        if ($courseResult->num_rows > 0):
                            while($course = $courseResult->fetch_assoc()):
                    ?>
                        <option value="<?php echo htmlspecialchars($course['C_Code']); ?>">
                            <?php echo htmlspecialchars($course['C_Code'] . " - " . $course['Title']); ?>
                        </option>
                    <?php
                            endwhile;
                        endif;
                        $connection->close();
                    ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Section Name</label>
                    <input type="text" name="Section_Name" placeholder="e.g. A" required>
                </div>
                <div class="form-group">
                    <label>Day</label>
                    <select name="Day" required>
                        <option>Monday</option>
                        <option>Tuesday</option>
                        <option>Wednesday</option>
                        <option>Thursday</option>
                        <option>Friday</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="Start_Time" required>
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="End_Time" required>
                </div>
            </div>

            <div class="form-group">
                <label>Room / Location</label>
                <input type="text" name="Room" placeholder="e.g. Lab 01" required>
            </div>

            <button type="submit" class="submit-btn">Confirm & Create Section</button>
        </form>
    </div>
</div>

<div class="modal" id="createTeacher">
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
    const courseModalButton = document.querySelectorAll('.section-header .course-btn');
    const courseModal = document.getElementById('createCourse');

    const sectionModalButton = document.querySelectorAll('.section-header .section-btn');
    const sectionModal = document.getElementById('createSection');

    const teacherModalButton = document.querySelectorAll('.section-header .teacher-btn');
    const teacherModal = document.getElementById('createTeacher');

    courseModalButton.forEach(btn => {
        btn.addEventListener('click', () => courseModal.classList.add('active'));
    });

    courseModal.addEventListener('click', e => {
        if(e.target === courseModal) courseModal.classList.remove('active');
    });

    sectionModalButton.forEach(btn => {
        btn.addEventListener('click', () => sectionModal.classList.add('active'));
    });

    sectionModal.addEventListener('click', e => {
        if(e.target === sectionModal) sectionModal.classList.remove('active');
    });

    teacherModalButton.forEach(btn => {
        btn.addEventListener('click', () => teacherModal.classList.add('active'));
    });

    teacherModal.addEventListener('click', e => {
        if(e.target === teacherModal) teacherModal.classList.remove('active');
    });
</script>

</body>
</html>