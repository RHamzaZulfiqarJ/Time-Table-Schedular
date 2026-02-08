<?php
    include "../db.php";
    session_start();

    $error = $_SESSION['error'] ?? '';
    $success = $_SESSION['success'] ?? '';
    unset($_SESSION['error'], $_SESSION['success']);

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

    $sql = "
        SELECT 
            r.R_ID,
            u.Name AS Username,
            r.Role,
            r.Request_Type,
            r.Request_Data,
            r.Status,
            cs.C_Code,
            cs.Section_Name
        FROM requests r
        JOIN user u ON r.U_ID = u.U_ID
        JOIN course_schedule cs ON r.S_ID = cs.S_ID
        ORDER BY r.Created_At DESC
    ";

    $requests = $connection->query($sql);

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

    <div id="courseSection" class="section">
        <div class="section-header">
            <h2>Courses</h2>
            <div>
                <button class="modal-btn" data-modal="createCourse">Add Course</button>
                <button class="modal-btn" data-modal="createSection">Add Section</button>
                <button class="modal-btn" data-modal="createTeacher">Add Teacher</button>
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

    <div id="sectionSection" class="section">
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
                    <th>Actions</th>
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
                            <td>
                                <button class="edit-btn" data-id="<?php echo $row['S_ID']; ?>" data-modal="editSection">Edit</button>
                                <button class="delete-btn" data-id="<?php echo $row['S_ID']; ?>" data-modal="deleteSection">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div id="teacherSection" class="section">
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
                    <th>Actions</th>
                </tr>
                <?php if ($teachers->num_rows > 0): ?>
                    <?php while($row = $teachers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo htmlspecialchars($row['Role']); ?></td>
                            <td><?php echo htmlspecialchars($row['Password']); ?></td>
                            <td>
                                <button class="edit-btn" data-id="<?php echo $row['U_ID']; ?>" data-modal="editTeacher">Edit</button>
                                <button class="delete-btn" data-id="<?php echo $row['U_ID']; ?>" data-modal="deleteTeacher">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div id="studentSection" class="section">
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
                    <th>Actions</th>
                </tr>
                <?php if ($students->num_rows > 0): ?>
                    <?php while($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo htmlspecialchars($row['Role']); ?></td>
                            <td><?php echo htmlspecialchars($row['Password']); ?></td>
                            <td>
                                <button class="edit-btn" data-id="<?php echo $row['U_ID']; ?>" data-modal="editStudent">Edit</button>
                                <button class="delete-btn" data-id="<?php echo $row['U_ID']; ?>" data-modal="deleteStudent">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div id="requestSection" class="section">
        <div class="section-header">
            <h2>All Requests</h2>
        </div>
        <div class="timetable">
            <table>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Course</th>
                    <th>Section</th>
                    <th>Request Type</th>
                    <th>Request Data</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php if ($requests->num_rows > 0): ?>
                    <?php while($row = $requests->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Username']) ?></td>
                            <td><?= $row['Role'] ?></td>
                            <td><?= $row['C_Code'] ?></td>
                            <td><?= $row['Section_Name'] ?></td>
                            <td>
                                <?php 
                                    if ($row['Request_Type'] == "Change_Section_Time") {
                                        echo "Change Section Time";
                                    } else if ($row['Request_Type'] == "Drop_Section") {
                                        echo "Drop Section";
                                    } else if ($row['Request_Type'] == "Add_Section") {
                                        echo "Add Section";
                                    }
                                ?>
                            </td>
                            <td>
                                <?php
                                    if ($row['Request_Type'] == "Drop_Section") {
                                        echo "—";
                                    } else {
                                        if ($row['Request_Data']) {
                                            $data = json_decode($row['Request_Data'], true);

                                            if (isset($data['Day'])) {
                                                echo htmlspecialchars($data['Day']) . " | ";
                                            }
                                            if (isset($data['Start'], $data['End'])) {
                                                echo htmlspecialchars($data['Start']) . " - " . htmlspecialchars($data['End']);
                                            }
                                        } else {
                                            echo "—";
                                        }
                                    }
                                ?>
                            </td>
                            <td>
                                <span class="status <?= strtolower($row['Status']) ?>">
                                    <?= $row['Status'] ?>
                                </span>
                            </td> 
                            <td>
                                <?php if ($row['Status'] === 'Pending'): ?>
                                    <form action="handleRequest.php" method="POST" style="display:inline">
                                        <input type="hidden" name="R_ID" value="<?= $row['R_ID'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button>Approve</button>
                                    </form>

                                    <form action="handleRequest.php" method="POST" style="display:inline">
                                        <input type="hidden" name="R_ID" value="<?= $row['R_ID'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button>Reject</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
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

<div class="modal" id="editSection">
    <div class="modal-content">
        <h3>Edit Section</h3>
        <p>Update the details of the section.</p>

        <form method="POST" action="editSection.php" class="styled-form">

            <input type="hidden" name="S_ID" id="editS_ID">

            <div class="form-group">
                <label>Section Name</label>
                <input type="text" name="Section_Name" id="editSectionName" required>
            </div>

            <div class="form-group">
                <label>Day</label>
                <select name="Day" id="editDay" required>
                    <option>Monday</option>
                    <option>Tuesday</option>
                    <option>Wednesday</option>
                    <option>Thursday</option>
                    <option>Friday</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="Start_Time" id="editStart" required>
                </div>

                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="End_Time" id="editEnd" required>
                </div>
            </div>

            <div class="form-group">
                <label>Room</label>
                <input type="text" name="Room" id="editRoom" required>
            </div>

            <button type="submit" class="submit-btn">Update Section</button>
        </form>
    </div>
</div>

<div class="modal" id="deleteSection">
    <div class="modal-content">
        <h3>Delete Section</h3>
        <p>Are you sure you want to delete this section?</p>
        
        <form method="POST" action="deleteSection.php">
            <input type="hidden" name="S_ID" id="deleteSectionId">
            <button type="submit" class="submit-btn">Confirm Delete</button>
        </form>
    </div>
</div>

<div class="modal" id="createTeacher">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add a Teacher</h3>
            <p>Create a new teacher profile for the academic semester.</p>
        </div>

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

<div class="modal" id="editTeacher">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Teacher</h3>
            <p>Update the details of the teacher.</p>
        </div>

        <form action="editTeacher.php" method="POST" class="styled-form">
            <input type="hidden" name="U_ID" id="editU_ID">
            <div class="form-row">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" id="editName" name="name" placeholder="e.g. John Doe" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="number" id="editPhone" name="phone" placeholder="e.g. 1234567890" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" id="editEmail" name="email" placeholder="e.g. john.doe@example.com" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" id="editPassword" name="password" placeholder="Enter a secure password" required>
            </div>

            <button type="submit" class="submit-btn">Update Teacher</button>
        </form>
    </div>
</div>

<div class="modal" id="deleteTeacher">
    <div class="modal-content">
        <h3>Delete Teacher</h3>
        <p>Are you sure you want to delete this teacher?</p>
        
        <form method="POST" action="deleteTeacher.php">
            <input type="hidden" name="U_ID" id="deleteTeacherId">
            <button type="submit" class="submit-btn">Confirm Delete</button>
        </form>
    </div>
</div>

<div class="modal" id="editStudent">
    <div class="modal-content">
        <h3>Edit Student</h3>
        <p>Update student details.</p>
        <form action="editStudent.php" method="POST" class="styled-form">
            <input type="hidden" name="U_ID" id="editStudentId">
            <div class="form-row">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="editStudentName" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="number" name="phone" id="editStudentPhone" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="editStudentEmail" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="editStudentPassword" required>
            </div>
            <button type="submit" class="submit-btn">Update Student</button>
        </form>
    </div>
</div>

<div class="modal" id="deleteStudent">
    <div class="modal-content">
        <h3>Delete Student</h3>
        <p>Are you sure you want to delete this student?</p>
        <form method="POST" action="deleteStudent.php">
            <input type="hidden" name="U_ID" id="deleteStudentId">
            <button type="submit" class="submit-btn">Confirm Delete</button>
        </form>
    </div>
</div>

<script src="Dashboard.js"></script>

</body>
</html>