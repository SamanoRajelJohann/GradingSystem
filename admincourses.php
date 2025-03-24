<?php
// Include the database connection file
require_once("db.php");

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Add new course logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $course_id_number = mysqli_real_escape_string($mysqli, $_POST['course_id_number']);
    $course_name = mysqli_real_escape_string($mysqli, $_POST['course_name']);
    $course_description = mysqli_real_escape_string($mysqli, $_POST['course_description']);
    $faculty_id = (int)$_POST['faculty_id'];

    // Insert query
    $sql = "INSERT INTO course (CourseIDnumber, CourseName, CourseDescription, FacultyID) 
            VALUES ('$course_id_number', '$course_name', '$course_description', $faculty_id)";

    if (mysqli_query($mysqli, $sql)) {
        echo "<script>alert('New course added successfully!');</script>";
        echo "<script>window.location.href='admincourses.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($mysqli) . "');</script>";
    }
}

// Edit course logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $course_id = $_POST['course_id'];
    $course_id_number = $_POST['course_id_number'];
    $course_name = $_POST['course_name'];
    $course_description = $_POST['course_description'];
    $faculty_id = $_POST['faculty_id'];

    // First, get the current values
    $check_query = "SELECT CourseIDnumber, CourseName, CourseDescription, FacultyID FROM course WHERE CourseID = ?";
    $check_stmt = $mysqli->prepare($check_query);
    $check_stmt->bind_param("i", $course_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $current_values = $check_result->fetch_assoc();
    $check_stmt->close();

    // Check if any values have changed
    $has_changes = false;
    if ($current_values['CourseIDnumber'] != $course_id_number ||
        $current_values['CourseName'] != $course_name ||
        $current_values['CourseDescription'] != $course_description ||
        $current_values['FacultyID'] != $faculty_id) {
        $has_changes = true;
    }

    if ($has_changes) {
        // Update the course
        $update_query = "UPDATE course SET CourseIDnumber = ?, CourseName = ?, CourseDescription = ?, FacultyID = ? WHERE CourseID = ?";
        $update_stmt = $mysqli->prepare($update_query);
        $update_stmt->bind_param("issii", $course_id_number, $course_name, $course_description, $faculty_id, $course_id);
        
        if ($update_stmt->execute()) {
            echo "<script>
                alert('Course updated successfully!');
                window.location.href = 'admincourses.php';
            </script>";
        } else {
            echo "<script>
                alert('Error updating course: " . $update_stmt->error . "');
            </script>";
        }
        $update_stmt->close();
    }
    // If no changes were made, don't show any message
}

// Delete course logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['course_id'])) {
        $course_id = intval($_POST['course_id']);
        
        // First, insert into deleted_course table
        $insert_sql = "INSERT INTO deleted_course (CourseID, CourseIDnumber, CourseName, CourseDescription, FacultyID)
                      SELECT CourseID, CourseIDnumber, CourseName, CourseDescription, FacultyID
                      FROM course WHERE CourseID = ?";
        
        $stmt = $mysqli->prepare($insert_sql);
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            // Then delete from course table
            $delete_sql = "DELETE FROM course WHERE CourseID = ?";
            $stmt = $mysqli->prepare($delete_sql);
            $stmt->bind_param("i", $course_id);
            
            if ($stmt->execute()) {
                echo "<script>alert('Course deleted successfully!');</script>";
                echo "<script>window.location.href='admincourses.php';</script>";
            } else {
                echo "<script>alert('Error deleting course: " . $mysqli->error . "');</script>";
            }
        } else {
            echo "<script>alert('Error moving to deleted records: " . $mysqli->error . "');</script>";
        }
        
        $stmt->close();
    }
}

// Function to get faculty list for dropdown
function getFacultyList($mysqli) {
    $faculty_list = array();
    $query = "SELECT FacultyID, FacultyIDNumber, LName, FName FROM faculty ORDER BY LName, FName";
    $result = $mysqli->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $faculty_list[] = $row;
        }
    }
    return $faculty_list;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>USSG</title>
  <!-- Icon -->
  <link rel="icon" type="image/png" href="img/USSG.png">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Teko:wght@300;400;500;700&display=swap" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{
    background-color: #2E2E2E; /* Dark gray to complement red */
    color: #E0E0E0; /* Light gray text for readability */
    }   
    .navbar {
    background-color: #880606 !important;
    }
    .navbar-section{
    display: flex;
    align-items: center;  
    font-size: 1.9rem; /* Adjusts the text size */
    font-family: 'Teko', sans-serif; /* Applies the Teko font */   
    text-decoration: none; 
    }
    .navbar-brand {
    display: flex;
    align-items: center;  
    font-size: 1.9rem; /* Adjusts the text size */
    font-family: 'Teko', sans-serif; /* Applies the Teko font */
    }
    .navbar-brand span {
    padding-top: 1px; /* Adjusts only the text's vertical position */
    }
    .nav-link {
    color: white !important;
    font-family: 'Teko', sans-serif; /* Applies the Teko font to nav links */
    font-size: 1.5rem;
    }
    .user-info {
    color: #E0E0E0;
    font-family: 'Teko', sans-serif;
    font-size: 1.2rem;
    margin-right: 20px;
    }
    .logout-btn {
    color: #E0E0E0;
    background: none;
    border: none;
    font-family: 'Teko', sans-serif;
    font-size: 1.2rem;
    cursor: pointer;
    text-decoration: none;
    }
    table {
    background-color: #4B1D1D; /* Dark red to blend with theme */
    color: white;
    }
    th {
    background-color: #660505; /* Darker shade of red */
    color: white;
    }
    .dataTables_wrapper .dataTables_filter input {
    width: 500px;
    margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_length select {
    margin-bottom: 1rem;
    }
    a, button {
    width: 145px;
    background-color: #880606;
    color: white;
    border-radius: 5px;
    transition: 0.3s;
    }
    a:hover, button:hover {
    background-color: #AA0D0D;
    }
    .dataTables_length select {
    background-color: white !important;
    color: black !important;
    border: 1px solid #880606; /* Optional: Add a border matching your theme */
    padding: 5px;
    border-radius: 5px;
    }
    .dataTables_filter input {
    background-color: white !important;
    color: black !important;
    border: 1px solid #880606; /* Optional: Add a border for consistency */
    padding: 5px;
    border-radius: 5px;
    }
    .dataTables_filter input::placeholder {
    color: #555; /* Slightly darker placeholder */
    opacity: 1;
    }
    .containerUSSG {
    background-color:rgb(133, 3, 3); /* Primary blue */
    color: white;
    font-size: 24px;
    font-weight: bold;
    text-align: center;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
    width: 450px;
    margin-top: 20px;
    margin-left: 535px;
    }
    .container {
    background-color: #f8f9fa; /* Light gray background */
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
    max-width: 1400px;
    margin: 20px auto;
    font-size: 16px;
    line-height: 1.6;
    color: black;
    }
    @media (max-width: 768px) {
    .containerUSSG {
        font-size: 20px;
        padding: 10px;
    }
    .container {
        padding: 15px;
        font-size: 14px;
    }
    }
  </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-dark border-bottom border-body" data-bs-theme="dark">
    <div class="container-fluid d-flex align-items-center">
      <!-- Logo and USSG Text -->
      <a class="navbar-brand d-flex align-items-center me-3" href="adminhome.php">
        <img src="img/USSG.png" alt="Logo" width="50" height="50">
        <span class="ms-2">USSG</span>
      </a>

      <!-- Navigation Sections -->
      <div class="d-flex align-items-center">
        <a class="navbar-section d-flex align-items-center me-3" href="adminstudent.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
          </svg>
          <span class="ms-2">Students</span>
        </a>
        <a class="navbar-section d-flex align-items-center me-3" href="adminfaculty.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-bank2" viewBox="0 0 16 16">
            <path d="M8.277.084a.5.5 0 0 0-.554 0l-7.5 5A.5.5 0 0 0 .5 6h1.875v7H1.5a.5.5 0 0 0 0 1h13a.5.5 0 1 0 0-1h-.875V6H15.5a.5.5 0 0 0 .277-.916zM12.375 6v7h-1.25V6zm-2.5 0v7h-1.25V6zm-2.5 0v7h-1.25V6zm-2.5 0v7h-1.25V6zM8 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2M.5 15a.5.5 0 0 0 0 1h15a.5.5 0 1 0 0-1z"/>
        </svg>
        <span class="ms-2">Faculty</span>
        </a>
        <a class="navbar-section d-flex align-items-center me-3" href="admincourses.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-book-fill" viewBox="0 0 16 16">
            <path d="M8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
          </svg>
        <span class="ms-2">Courses</span>
        </a>
        <a class="navbar-section d-flex align-items-center me-3" href="admingrades.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-mortarboard-fill" viewBox="0 0 16 16">
            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917z"/>
            <path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466z"/>
        </svg>
        <span class="ms-2">Grades</span>
        </a>
      </div>

      <!-- Right Side (User Info + Logout) -->
      <div class="d-flex align-items-center ms-auto">
        <span class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> | Role: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
        <button onclick="confirmLogout()" class="logout-btn btn btn-link ms-3">Logout</button>
      </div>
    </div>
</nav>
    
  <!-- Button to trigger modal -->
  <div class="container mt-4">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" style="width:175px;" data-bs-target="#addCourseModal">
      Add New Course
    </button>

    <!--ADD Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCourseLabel">Add New Course</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="mb-3">
            <label for="course_id_number" class="form-label">Course ID Number</label>
            <input type="number" class="form-control" id="course_id_number" name="course_id_number" required>
          </div>
          <div class="mb-3">
            <label for="course_name" class="form-label">Course Name</label>
            <input type="text" class="form-control" id="course_name" name="course_name" required>
          </div>
          <div class="mb-3">
            <label for="course_description" class="form-label">Course Description</label>
            <textarea class="form-control" id="course_description" name="course_description" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label for="faculty_id" class="form-label">Faculty</label>
            <select class="form-control" id="faculty_id" name="faculty_id" required>
              <option value="">Select Faculty</option>
              <?php
              $faculty_list = getFacultyList($mysqli);
              foreach ($faculty_list as $faculty) {
                echo "<option value='" . $faculty['FacultyID'] . "'>" . 
                     htmlspecialchars($faculty['FacultyIDNumber'] . " - " . $faculty['LName'] . ", " . $faculty['FName']) . 
                     "</option>";
              }
              ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Add Course</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="course_id" id="edit-course-id">
                <div class="modal-header">
                    <h5>Edit Course</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_course_id_number" class="form-label">Course ID Number</label>
                        <input type="number" class="form-control" id="edit_course_id_number" name="course_id_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="edit_course_name" name="course_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_description" class="form-label">Course Description</label>
                        <textarea class="form-control" id="edit_course_description" name="course_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_faculty_id" class="form-label">Faculty</label>
                        <select class="form-control" id="edit_faculty_id" name="faculty_id" required>
                          <option value="">Select Faculty</option>
                          <?php
                          foreach ($faculty_list as $faculty) {
                            echo "<option value='" . $faculty['FacultyID'] . "'>" . 
                                 htmlspecialchars($faculty['FacultyIDNumber'] . " - " . $faculty['LName'] . ", " . $faculty['FName']) . 
                                 "</option>";
                          }
                          ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

  <!-- DataTable -->
  <div class="container mt-4">
    <?php
    // Include database connection
    require_once 'db.php';

    // SQL Query to Fetch Course Data with Faculty Names
    $query = "SELECT c.CourseID, c.CourseIDnumber, c.CourseName, c.CourseDescription, 
                     f.FacultyID, f.FacultyIDNumber, f.LName, f.FName
              FROM course c
              LEFT JOIN faculty f ON c.FacultyID = f.FacultyID
              ORDER BY c.CourseName";

    if ($result = $mysqli->query($query)) {
        if ($result->num_rows > 0) {
            echo "<table id='coursesTable' class='display table table-striped' style='width:100%'>
                    <thead>
                      <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Description</th>
                        <th>Faculty</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['CourseIDnumber']) . "</td>
                        <td>" . htmlspecialchars($row['CourseName']) . "</td>
                        <td>" . htmlspecialchars($row['CourseDescription']) . "</td>
                        <td>" . htmlspecialchars($row['FacultyIDNumber'] . " - " . $row['LName'] . ", " . $row['FName']) . "</td>
                        <td>
                            <button class='btn btn-primary btn-sm' onclick='editCourse(" . $row['CourseID'] . ", \"" . htmlspecialchars($row['CourseIDnumber']) . "\", \"" . htmlspecialchars($row['CourseName']) . "\", \"" . htmlspecialchars($row['CourseDescription']) . "\", " . $row['FacultyID'] . ")'>Edit</button>
                            <form method='POST' style='display: inline;'>
                                <input type='hidden' name='action' value='delete'>
                                <input type='hidden' name='course_id' value='" . $row['CourseID'] . "'>
                                <button type='submit' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this course?\")'>Delete</button>
                            </form>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "No course records found.";
        }
        $result->free();
    } else {
        echo "Error: " . $mysqli->error;
    }

    $mysqli->close();
    ?>
</div>

  <!-- jQuery and DataTables -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function () {
        $('#coursesTable').DataTable();
    });

    function editCourse(courseId, courseIdNumber, courseName, courseDescription, facultyId) {
        document.getElementById('edit-course-id').value = courseId;
        document.getElementById('edit_course_id_number').value = courseIdNumber;
        document.getElementById('edit_course_name').value = courseName;
        document.getElementById('edit_course_description').value = courseDescription;
        document.getElementById('edit_faculty_id').value = facultyId;
        
        var editModal = new bootstrap.Modal(document.getElementById('editCourseModal'));
        editModal.show();
    }

    function confirmLogout() {
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "index.php";
        }
    }
  </script>
</body>
</html> 