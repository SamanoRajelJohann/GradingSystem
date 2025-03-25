<?php
// Include the database connection file
require_once "db.php";

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Add deadline constants at the top of the file
const PRELIM_DEADLINE = '2025-02-12';
const MIDTERM_DEADLINE = '2025-03-30';
const FINAL_DEADLINE = '2025-06-10';

// Function to check if a grade can be modified based on date
function canModifyGrade($gradeType) {
    return true; // Always allow modification if not recorded
}

// Function to get grade status
function getGradeStatus($gradeType) {
    $current_date = date('Y-m-d');
    $deadline = match($gradeType) {
        'prelim' => PRELIM_DEADLINE,
        'midterm' => MIDTERM_DEADLINE,
        'final' => FINAL_DEADLINE,
        default => null
    };
    
    if ($deadline === null) {
        return 'closed';
    }
    
    // Convert dates to timestamps for comparison
    $current_timestamp = strtotime($current_date);
    $deadline_timestamp = strtotime($deadline);
    
    // Return 'open' if current date is before or equal to deadline
    return $current_timestamp <= $deadline_timestamp ? 'open' : 'closed';
}

// Add deadline update logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_deadlines') {
    $prelim_deadline = $_POST['prelim_deadline'];
    $midterm_deadline = $_POST['midterm_deadline'];
    $final_deadline = $_POST['final_deadline'];
    
    // Check if file is writable
    if (!is_writable(__FILE__)) {
        echo "<script>alert('Error: File is not writable. Please check file permissions.');</script>";
        exit();
    }
    
    // Read the current file content
    $file_content = file_get_contents(__FILE__);
    
    // Create the new constant definitions with the actual values from the form
    $new_prelim = "const PRELIM_DEADLINE = '$prelim_deadline';";
    $new_midterm = "const MIDTERM_DEADLINE = '$midterm_deadline';";
    $new_final = "const FINAL_DEADLINE = '$final_deadline';";
    
    // Replace the old constant definitions with new ones using a more flexible regex
    $file_content = preg_replace(
        "/const PRELIM_DEADLINE = '[0-9]{4}-[0-9]{2}-[0-9]{2}';/",
        $new_prelim,
        $file_content
    );
    $file_content = preg_replace(
        "/const MIDTERM_DEADLINE = '[0-9]{4}-[0-9]{2}-[0-9]{2}';/",
        $new_midterm,
        $file_content
    );
    $file_content = preg_replace(
        "/const FINAL_DEADLINE = '[0-9]{4}-[0-9]{2}-[0-9]{2}';/",
        $new_final,
        $file_content
    );
    
    // Write the updated content back to the file
    if (file_put_contents(__FILE__, $file_content) !== false) {
        echo "<script>alert('Deadlines updated successfully!');</script>";
        echo "<script>window.location.href='admingrades.php';</script>";
    } else {
        echo "<script>alert('Error updating deadlines. Please check file permissions.');</script>";
    }
    exit();
}

// Add new grade logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $student_id = (int)$_POST['student_id'];
    $course_id = (int)$_POST['course_id'];
    $prelim = floatval($_POST['prelim']);
    $midterm = floatval($_POST['midterm']);
    $final = floatval($_POST['final']);
    $current_date = date('Y-m-d');
    
    // Calculate school year automatically
    $current_month = (int)date('n'); // Get current month (1-12)
    $current_year = (int)date('Y'); // Get current year
    
    // If current month is before June (6), use previous year as start year
    // If current month is June or later, use current year as start year
    $start_year = $current_month < 6 ? $current_year - 1 : $current_year;
    $school_year = $start_year . '-' . ($start_year + 1);
    
    // Check if grade record exists
    $check_sql = "SELECT * FROM grades WHERE StudentID = ? AND CourseID = ? AND SchoolYear = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("iis", $student_id, $course_id, $school_year);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE grades SET 
                      Prelim = ?, 
                      Midterm = ?, 
                      Final = ?,
                      PrelimDate = CASE WHEN ? > 0 THEN COALESCE(PrelimDate, ?) ELSE PrelimDate END,
                      MidtermDate = CASE WHEN ? > 0 THEN COALESCE(MidtermDate, ?) ELSE MidtermDate END,
                      FinalDate = CASE WHEN ? > 0 THEN COALESCE(FinalDate, ?) ELSE FinalDate END
                      WHERE StudentID = ? AND CourseID = ? AND SchoolYear = ?";
        
        $update_stmt = $mysqli->prepare($update_sql);
        $update_stmt->bind_param("ddddddddiis", 
            $prelim, $midterm, $final,
            $prelim, $current_date,
            $midterm, $current_date,
            $final, $current_date,
            $student_id, $course_id, $school_year);
            
        if ($update_stmt->execute()) {
            echo "<script>alert('Grades updated successfully!');</script>";
            echo "<script>window.location.href='admingrades.php';</script>";
        } else {
            echo "<script>alert('Error updating grades: " . $update_stmt->error . "');</script>";
        }
        $update_stmt->close();
    } else {
        // Insert new record
        $sql = "INSERT INTO grades (StudentID, CourseID, Prelim, Midterm, Final, GWA, EQ, Remarks,
                                  PrelimDate, MidtermDate, FinalDate, SchoolYear) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Calculate GWA and other values
        $gwa = $prelim * 0.3 + $midterm * 0.3 + $final * 0.4;
        
        if ($gwa < 71.50) {
            $gwa = 70.00;
            $remarks = "Dropped";
        } else if ($gwa < 74.50) {
            $gwa = 74.00;
            $remarks = "Failed";
        } else {
            $remarks = "Passed";
        }
        
        // Calculate EQ
        if ($gwa >= 97.50) $eq = "1.00";
        else if ($gwa >= 94.50) $eq = "1.25";
        else if ($gwa >= 91.50) $eq = "1.50";
        else if ($gwa >= 88.50) $eq = "1.75";
        else if ($gwa >= 85.50) $eq = "2.00";
        else if ($gwa >= 82.50) $eq = "2.25";
        else if ($gwa >= 79.50) $eq = "2.50";
        else if ($gwa >= 76.50) $eq = "2.75";
        else if ($gwa >= 74.50) $eq = "3.00";
        else $eq = "5.00";
        
        $stmt = $mysqli->prepare($sql);
        
        // Prepare the date values
        $prelim_date = $prelim > 0 ? $current_date : null;
        $midterm_date = $midterm > 0 ? $current_date : null;
        $final_date = $final > 0 ? $current_date : null;
        
        $stmt->bind_param("iidddsssssss", 
            $student_id, $course_id,
            $prelim, $midterm, $final,
            $gwa, $eq, $remarks,
            $prelim_date, $midterm_date, $final_date,
            $school_year);
            
        if ($stmt->execute()) {
            echo "<script>alert('New grade added successfully!');</script>";
            echo "<script>window.location.href='admingrades.php';</script>";
        } else {
            echo "<script>alert('Error: " . $mysqli->error . "');</script>";
        }
        $stmt->close();
    }
}

// Initialize grade_dates array with null values
$grade_dates = [
    'PrelimDate' => null,
    'MidtermDate' => null,
    'FinalDate' => null
];

// Edit grade logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (isset($_POST['grades_id'])) {
        $grades_id = intval($_POST['grades_id']);
        
        // Fetch existing grade dates
        $fetch_sql = "SELECT PrelimDate, MidtermDate, FinalDate FROM grades WHERE GradesID = ?";
        $fetch_stmt = $mysqli->prepare($fetch_sql);
        $fetch_stmt->bind_param("i", $grades_id);
        $fetch_stmt->execute();
        $grade_dates = $fetch_stmt->get_result()->fetch_assoc();
        $fetch_stmt->close();
        
        $prelim = floatval($_POST['prelim']);
        $midterm = floatval($_POST['midterm']);
        $final = floatval($_POST['final']);
        $current_date = date('Y-m-d');
        
        // Check each grade independently
        if ($prelim > 0 && !canModifyGrade('prelim')) {
            echo "<script>alert('Prelim grade entry is closed.');
            window.location.href='admingrades.php';</script>";
            exit();
        }
        if ($midterm > 0 && !canModifyGrade('midterm')) {
            echo "<script>alert('Midterm grade entry is closed.');
            window.location.href='admingrades.php';</script>";
            exit();
        }
        if ($final > 0 && !canModifyGrade('final')) {
            echo "<script>alert('Final grade entry is closed.');
            window.location.href='admingrades.php';</script>";
            exit();
        }
        
        // Calculate new values
        $gwa = $prelim * 0.3 + $midterm * 0.3 + $final * 0.4;
        
        if ($gwa < 71.50) {
            $gwa = 70.00;
            $remarks = "Dropped";
        } else if ($gwa < 74.50) {
            $gwa = 74.00;
            $remarks = "Failed";
        } else {
            $remarks = "Passed";
        }
        
        // Calculate EQ
        if ($gwa >= 97.50) $eq = "1.00";
        else if ($gwa >= 94.50) $eq = "1.25";
        else if ($gwa >= 91.50) $eq = "1.50";
        else if ($gwa >= 88.50) $eq = "1.75";
        else if ($gwa >= 85.50) $eq = "2.00";
        else if ($gwa >= 82.50) $eq = "2.25";
        else if ($gwa >= 79.50) $eq = "2.50";
        else if ($gwa >= 76.50) $eq = "2.75";
        else if ($gwa >= 74.50) $eq = "3.00";
        else $eq = "5.00";
        
        // Update the grades
        $update_query = "UPDATE grades SET 
                        Prelim = ?, 
                        Midterm = ?, 
                        Final = ?, 
                        GWA = ?, 
                        EQ = ?, 
                        Remarks = ?,
                        PrelimDate = CASE WHEN ? > 0 THEN COALESCE(PrelimDate, ?) ELSE PrelimDate END,
                        MidtermDate = CASE WHEN ? > 0 THEN COALESCE(MidtermDate, ?) ELSE MidtermDate END,
                        FinalDate = CASE WHEN ? > 0 THEN COALESCE(FinalDate, ?) ELSE FinalDate END
                        WHERE GradesID = ?";
        
        $update_stmt = $mysqli->prepare($update_query);
        $update_stmt->bind_param("ddddssddddddi", 
            $prelim, $midterm, $final, $gwa, $eq, $remarks,
            $prelim, $current_date,
            $midterm, $current_date,
            $final, $current_date,
            $grades_id);
        
        if ($update_stmt->execute()) {
            echo "<script>
                alert('Grade updated successfully!');
                window.location.href = 'admingrades.php';
            </script>";
        } else {
            echo "<script>
                alert('Error updating grade: " . $update_stmt->error . "');
                window.location.href = 'admingrades.php';   
            </script>";
        }
        $update_stmt->close();
    }
}

// Delete grade logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['grades_id'])) {
        $grades_id = intval($_POST['grades_id']);
            
        // First, insert into deleted_grades table
        $insert_sql = "INSERT INTO deleted_grades (GradesID, StudentID, CourseID, Prelim, Midterm, Final, GWA, EQ, Remarks)
                      SELECT GradesID, StudentID, CourseID, Prelim, Midterm, Final, GWA, EQ, Remarks
                      FROM grades WHERE GradesID = ?";
        
        $stmt = $mysqli->prepare($insert_sql);
        $stmt->bind_param("i", $grades_id);
        
        if ($stmt->execute()) {
            // Then delete from grades table
            $delete_sql = "DELETE FROM grades WHERE GradesID = ?";
            $stmt = $mysqli->prepare($delete_sql);
            $stmt->bind_param("i", $grades_id);
            
            if ($stmt->execute()) {
                echo "<script>alert('Grade deleted successfully!');</script>";
                echo "<script>window.location.href='admingrades.php';</script>";
            } else {
                echo "<script>alert('Error deleting grade: " . $mysqli->error . "');</script>";
            }
        } else {
            echo "<script>alert('Error moving to deleted records: " . $mysqli->error . "');</script>";
        }
        
        $stmt->close();
    }
}

// Function to get student list for dropdown
function getStudentList($mysqli) {
    $student_list = array();
    $query = "SELECT StudentID, StudentIDNumber, LName, FName FROM student ORDER BY LName, FName";
    $result = $mysqli->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $student_list[] = $row;
        }
    }
    return $student_list;
}

// Function to get course list for dropdown
function getCourseList($mysqli) {
    $course_list = array();
    $query = "SELECT CourseID, CourseIDnumber, CourseName FROM course ORDER BY CourseName";
    $result = $mysqli->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $course_list[] = $row;
        }
    }
    return $course_list;
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
    max-width: 1400px;  /* Increased from 800px to 1400px */
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
        <span class="user-info"> Welcome: <?php echo htmlspecialchars($_SESSION['name']); ?> | Role: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
        <button onclick="confirmLogout()" class="logout-btn btn btn-link ms-3">Logout</button>
      </div>
    </div>
</nav>
    
  <!-- Button to trigger modal -->
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" style="width:175px;" data-bs-target="#addGradeModal">
        Add New Grade
      </button>
      <button type="button" class="btn btn-warning" data-bs-toggle="modal" style="width:175px;" data-bs-target="#deadlineModal">
        Manage Deadlines
      </button>
    </div>

    <!--ADD Modal -->
<div class="modal fade" id="addGradeModal" tabindex="-1" aria-labelledby="addGradeLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addGradeLabel">Add New Grade</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="mb-3">
            <label for="student_id" class="form-label">Student</label>
            <select class="form-control" id="student_id" name="student_id" required>
              <option value="">Select Student</option>
              <?php
              $student_list = getStudentList($mysqli);
              foreach ($student_list as $student) {
                echo "<option value='" . $student['StudentID'] . "'>" . 
                     htmlspecialchars($student['StudentIDNumber'] . " - " . $student['LName'] . ", " . $student['FName']) . 
                     "</option>";
              }
              ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="course_id" class="form-label">Course</label>
            <select class="form-control" id="course_id" name="course_id" required>
              <option value="">Select Course</option>
              <?php
              $course_list = getCourseList($mysqli);
              foreach ($course_list as $course) {
                echo "<option value='" . $course['CourseID'] . "'>" . 
                     htmlspecialchars($course['CourseIDnumber'] . " - " . $course['CourseName']) . 
                     "</option>";
              }
              ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="prelim" class="form-label">Prelim Grade</label>
            <input type="number" step="0.01" class="form-control" id="prelim" name="prelim" required>
          </div>
          <div class="mb-3">
            <label for="midterm" class="form-label">Midterm Grade</label>
            <input type="number" step="0.01" class="form-control" id="midterm" name="midterm" required>
          </div>
          <div class="mb-3">
            <label for="final" class="form-label">Final Grade</label>
            <input type="number" step="0.01" class="form-control" id="final" name="final" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Calculated GWA</label>
            <input type="text" class="form-control" id="add_gwa" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Grade Point (EQ)</label>
            <input type="text" class="form-control" id="add_eq" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Remarks</label>
            <input type="text" class="form-control" id="add_remarks" readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Add Grade</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Deadline Management Modal -->
<div class="modal fade" id="deadlineModal" tabindex="-1" aria-labelledby="deadlineModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deadlineModalLabel">Manage Grade Deadlines</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="update_deadlines">
        <div class="modal-body">
          <div class="mb-3">
            <label for="prelim_deadline" class="form-label">Prelim Grade Deadline</label>
            <input type="date" class="form-control" id="prelim_deadline" name="prelim_deadline" value="<?php echo PRELIM_DEADLINE; ?>" required>
          </div>
          <div class="mb-3">
            <label for="midterm_deadline" class="form-label">Midterm Grade Deadline</label>
            <input type="date" class="form-control" id="midterm_deadline" name="midterm_deadline" value="<?php echo MIDTERM_DEADLINE; ?>" required>
          </div>
          <div class="mb-3">
            <label for="final_deadline" class="form-label">Final Grade Deadline</label>
            <input type="date" class="form-control" id="final_deadline" name="final_deadline" value="<?php echo FINAL_DEADLINE; ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Save Deadlines</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Grade Modal -->
<div class="modal fade" id="editGradeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="grades_id" id="edit-grades-id">
                <div class="modal-header">
                    <h5>Edit Grade</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">School Year</label>
                        <input type="text" class="form-control" id="edit_school_year" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_prelim" class="form-label">Prelim Grade</label>
                        <input type="number" step="0.01" class="form-control" id="edit_prelim" name="prelim">
                        <small class="text-danger" id="prelim_status"></small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_midterm" class="form-label">Midterm Grade</label>
                        <input type="number" step="0.01" class="form-control" id="edit_midterm" name="midterm">
                        <small class="text-danger" id="midterm_status"></small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_final" class="form-label">Final Grade</label>
                        <input type="number" step="0.01" class="form-control" id="edit_final" name="final">
                        <small class="text-danger" id="final_status"></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Calculated GWA</label>
                        <input type="text" class="form-control" id="edit_gwa" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grade Point (EQ)</label>
                        <input type="text" class="form-control" id="edit_eq" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" class="form-control" id="edit_remarks" readonly>
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

    // SQL Query to Fetch Grade Data with Student and Course Information
    $query = "SELECT g.GradesID, g.Prelim, g.Midterm, g.Final, g.GWA, g.EQ, g.Remarks,
                     g.PrelimDate, g.MidtermDate, g.FinalDate, g.SchoolYear,
                     s.StudentID, s.StudentIDNumber, s.LName, s.FName,
                     c.CourseID, c.CourseIDnumber, c.CourseName,
                     f.FacultyID, f.LName as FacultyLName, f.FName as FacultyFName
              FROM grades g
              JOIN student s ON g.StudentID = s.StudentID
              JOIN course c ON g.CourseID = c.CourseID
              JOIN faculty f ON c.FacultyID = f.FacultyID
              ORDER BY s.LName, s.FName, c.CourseName";

    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        echo "<table id='gradesTable' class='display table table-striped' style='width:100%'>
                <thead>
                  <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Faculty</th>
                    <th>School Year</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['StudentIDNumber'] . " - " . $row['LName'] . ", " . $row['FName']) . "</td>
                    <td>" . htmlspecialchars($row['CourseIDnumber'] . " - " . $row['CourseName']) . "</td>
                    <td>" . htmlspecialchars($row['FacultyLName'] . " " . $row['FacultyFName']) . "</td>
                    <td>" . htmlspecialchars($row['SchoolYear']) . "</td>
                    <td>
                        <button class='btn btn-primary btn-sm' onclick='editGrade(" . $row['GradesID'] . ", " . 
                         $row['Prelim'] . ", " . $row['Midterm'] . ", " . $row['Final'] . ", " . 
                         $row['GWA'] . ", \"" . htmlspecialchars($row['EQ']) . "\", \"" . 
                         htmlspecialchars($row['Remarks']) . "\", \"" . $row['PrelimDate'] . "\", \"" . 
                         $row['MidtermDate'] . "\", \"" . $row['FinalDate'] . "\", \"" . 
                         htmlspecialchars($row['SchoolYear']) . "\")'>Edit</button>
                        <form method='POST' style='display: inline;'>
                            <input type='hidden' name='action' value='delete'>
                            <input type='hidden' name='grades_id' value='" . $row['GradesID'] . "'>
                            <button type='submit' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this grade?\")'>Delete</button>
                        </form>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "No grade records found.";
    }

    $mysqli->close();
    ?>
</div>

<!-- Display Grades Modal -->
<div class="modal fade" id="displayGradesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Student Grades</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="studentGradesTable"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery and DataTables -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        $('#gradesTable').DataTable();
        
        // Add event listeners for the add grade form
        document.getElementById('prelim').addEventListener('input', calculateAddGrades);
        document.getElementById('midterm').addEventListener('input', calculateAddGrades);
        document.getElementById('final').addEventListener('input', calculateAddGrades);

        // Handle modal close events for edit modal
        $('#editGradeModal').on('hide.bs.modal', function (e) {
            // Check if the close was triggered by the form submission
            if (e.target && $(e.target).find('form').data('submitted')) {
                return;
            }
            // For any other close action, just redirect
            window.location.href = 'admingrades.php';
        });

        // Mark the form as submitted when the submit button is clicked
        $('#editGradeModal form').on('submit', function() {
            $(this).data('submitted', true);
        });

        // Handle close button clicks
        $('#editGradeModal .btn-close, #editGradeModal .btn-secondary').on('click', function(e) {
            e.preventDefault();
            window.location.href = 'admingrades.php';
        });
    });

    function calculateAddGrades() {
        const prelim = parseFloat(document.getElementById('prelim').value) || 0;
        const midterm = parseFloat(document.getElementById('midterm').value) || 0;
        const final = parseFloat(document.getElementById('final').value) || 0;
        
        // Calculate GWA
        let gwa = prelim * 0.3 + midterm * 0.3 + final * 0.4;
        
        // Adjust GWA based on cutoff rules
        if (gwa < 71.50) {
            gwa = 70.00; // Dropped
        } else if (gwa < 74.50) {
            gwa = 74.00; // Failed
        }
        
        // Calculate EQ
        let eq;
        if (gwa >= 97.50) {
            eq = "1.00";
        } else if (gwa >= 94.50) {
            eq = "1.25";
        } else if (gwa >= 91.50) {
            eq = "1.50";
        } else if (gwa >= 88.50) {
            eq = "1.75";
        } else if (gwa >= 85.50) {
            eq = "2.00";
        } else if (gwa >= 82.50) {
            eq = "2.25";
        } else if (gwa >= 79.50) {
            eq = "2.50";
        } else if (gwa >= 76.50) {
            eq = "2.75";
        } else if (gwa >= 74.50) {
            eq = "3.00";
        } else {
            eq = "5.00";
        }
        
        // Determine remarks
        let remarks;
        if (gwa < 71.50) {
            remarks = "Dropped";
        } else if (gwa < 74.50) {
            remarks = "Failed";
        } else {
            remarks = "Passed";
        }
        
        // Update the display fields
        document.getElementById('add_gwa').value = gwa.toFixed(2);
        document.getElementById('add_eq').value = eq;
        document.getElementById('add_remarks').value = remarks;
    }

    function calculateGrades() {
        const prelim = parseFloat(document.getElementById('edit_prelim').value) || 0;
        const midterm = parseFloat(document.getElementById('edit_midterm').value) || 0;
        const final = parseFloat(document.getElementById('edit_final').value) || 0;
        
        // Calculate GWA
        let gwa = prelim * 0.3 + midterm * 0.3 + final * 0.4;
        
        // Adjust GWA based on cutoff rules
        if (gwa < 71.50) {
            gwa = 70.00; // Dropped
        } else if (gwa < 74.50) {
            gwa = 74.00; // Failed
        }
        
        // Calculate EQ
        let eq;
        if (gwa >= 97.50) {
            eq = "1.00";
        } else if (gwa >= 94.50) {
            eq = "1.25";
        } else if (gwa >= 91.50) {
            eq = "1.50";
        } else if (gwa >= 88.50) {
            eq = "1.75";
        } else if (gwa >= 85.50) {
            eq = "2.00";
        } else if (gwa >= 82.50) {
            eq = "2.25";
        } else if (gwa >= 79.50) {
            eq = "2.50";
        } else if (gwa >= 76.50) {
            eq = "2.75";
        } else if (gwa >= 74.50) {
            eq = "3.00";
        } else {
            eq = "5.00";
        }
        
        // Determine remarks
        let remarks;
        if (gwa < 71.50) {
            remarks = "Dropped";
        } else if (gwa < 74.50) {
            remarks = "Failed";
        } else {
            remarks = "Passed";
        }
        
        // Update the display fields
        document.getElementById('edit_gwa').value = gwa.toFixed(2);
        document.getElementById('edit_eq').value = eq;
        document.getElementById('edit_remarks').value = remarks;
    }

    function editGrade(gradesId, prelim, midterm, final, gwa, eq, remarks, prelimDate, midtermDate, finalDate, schoolYear) {
        // Set the form values
        document.getElementById('edit-grades-id').value = gradesId;
        document.getElementById('edit_prelim').value = prelim;
        document.getElementById('edit_midterm').value = midterm;
        document.getElementById('edit_final').value = final;
        document.getElementById('edit_school_year').value = schoolYear;
        
        // Get current date in Y-m-d format
        const currentDate = new Date().toISOString().split('T')[0];
        
        // Set field states based on deadlines only
        document.getElementById('edit_prelim').readOnly = currentDate > '<?php echo PRELIM_DEADLINE; ?>';
        document.getElementById('edit_midterm').readOnly = currentDate > '<?php echo MIDTERM_DEADLINE; ?>';
        document.getElementById('edit_final').readOnly = currentDate > '<?php echo FINAL_DEADLINE; ?>';
        
        // Set status messages based on deadlines only
        document.getElementById('prelim_status').textContent = 
            currentDate > '<?php echo PRELIM_DEADLINE; ?>' ? 'Prelim grade entry is closed' : '';
            
        document.getElementById('midterm_status').textContent = 
            currentDate > '<?php echo MIDTERM_DEADLINE; ?>' ? 'Midterm grade entry is closed' : '';
            
        document.getElementById('final_status').textContent = 
            currentDate > '<?php echo FINAL_DEADLINE; ?>' ? 'Final grade entry is closed' : '';
        
        // Add event listeners for grade calculations
        document.getElementById('edit_prelim').addEventListener('input', calculateGrades);
        document.getElementById('edit_midterm').addEventListener('input', calculateGrades);
        document.getElementById('edit_final').addEventListener('input', calculateGrades);
        
        // Calculate initial values
        calculateGrades();
        
        // Show the modal
        var editModal = new bootstrap.Modal(document.getElementById('editGradeModal'));
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