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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USSG - User Grades</title>
    <!-- Icon -->
    <link rel="icon" type="image/png" href="img/USSG.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #2E2E2E;
            color: #E0E0E0;
        }
        .navbar {
            background-color: #880606 !important;
        }
        .navbar-section {
            display: flex;
            align-items: center;
            font-size: 1.9rem;
            font-family: 'Teko', sans-serif;
            text-decoration: none;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            font-size: 1.9rem;
            font-family: 'Teko', sans-serif;
        }
        .navbar-brand span {
            padding-top: 1px;
        }
        .nav-link {
            color: white !important;
            font-family: 'Teko', sans-serif;
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
            background-color: #4B1D1D;
            color: white;
        }
        th {
            background-color: #660505;
            color: white;
        }
        .dataTables_wrapper .dataTables_filter input {
            width: 500px;
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_length select {
            margin-bottom: 1rem;
        }
        .btn {
            width: auto;
            background-color: #880606;
            color: white;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn:hover {
            background-color: #AA0D0D;
        }
        .container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 1400px;
            margin: 20px auto;
            font-size: 16px;
            line-height: 1.6;
            color: black;
        }
        .modal-content {
            background-color: #f8f9fa;
        }
        .modal-header {
            background-color: #880606;
            color: white;
        }
        .modal-footer {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-dark border-bottom border-body" data-bs-theme="dark">
        <div class="container-fluid d-flex align-items-center">
            <!-- Logo and USSG Text -->
            <a class="navbar-brand d-flex align-items-center me-3" href="userhome.php">
                <img src="img/USSG.png" alt="Logo" width="50" height="50">
                <span class="ms-2">USSG</span>
            </a>

            <!-- Navigation Sections -->
            <div class="d-flex align-items-center">
                <a class="navbar-section d-flex align-items-center me-3" href="usergrades.php">
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

    <!-- Grades Table -->
    <div class="container mt-4">
        <?php
        // SQL Query to Fetch Grade Data with Student and Course Information
        $query = "SELECT DISTINCT s.StudentID, s.StudentIDNumber, s.LName, s.FName
                  FROM student s
                  JOIN grades g ON s.StudentID = g.StudentID
                  ORDER BY s.LName, s.FName";

        $stmt = $mysqli->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<table id='gradesTable' class='display table table-striped' style='width:100%'>
                    <thead>
                      <tr>
                        <th>Student</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['StudentIDNumber'] . " - " . $row['LName'] . ", " . $row['FName']) . "</td>
                        <td>
                            <button class='btn btn-info btn-sm' onclick='displayGrades(" . $row['StudentID'] . ")'>Display Grades</button>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "No grade records found.";
        }
        $stmt->close();
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
        });

        function displayGrades(studentId) {
            // Fetch grades for the specific student
            fetch('get_student_grades.php?student_id=' + studentId)
                .then(response => response.json())
                .then(data => {
                    let tableHtml = `
                        <table class='table table-striped'>
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Prelim</th>
                                    <th>Midterm</th>
                                    <th>Final</th>
                                    <th>GWA</th>
                                    <th>EQ</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    
                    data.forEach(grade => {
                        tableHtml += `
                            <tr>
                                <td>${grade.CourseIDnumber} - ${grade.CourseName}</td>
                                <td>${grade.Prelim}</td>
                                <td>${grade.Midterm}</td>
                                <td>${grade.Final}</td>
                                <td>${grade.GWA}</td>
                                <td>${grade.EQ}</td>
                                <td>${grade.Remarks}</td>
                            </tr>`;
                    });
                    
                    tableHtml += '</tbody></table>';
                    document.getElementById('studentGradesTable').innerHTML = tableHtml;
                    
                    // Show the modal
                    var displayModal = new bootstrap.Modal(document.getElementById('displayGradesModal'));
                    displayModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching grades data');
                });
        }

        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "index.php";
            }
        }
    </script>
</body>
</html>
