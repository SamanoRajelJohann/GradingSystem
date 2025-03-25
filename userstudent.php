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
  <title>USSG</title>
  <!-- Icon -->
  <link rel="icon" type="image/png" href="img/USSG.png">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Teko:wght@300;400;500;700&display=swap" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
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
    .container {
    background-color: #3A3A3A; /* Slightly lighter than background */
    padding: 20px;
    border-radius: 10px;
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
</style>
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
        <a class="navbar-section d-flex align-items-center me-3" href="userstudent.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
          </svg>
          <span class="ms-2">Students</span>
        </a>
        <a class="navbar-section d-flex align-items-center me-3" href="userfaculty.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-bank2" viewBox="0 0 16 16">
            <path d="M8.277.084a.5.5 0 0 0-.554 0l-7.5 5A.5.5 0 0 0 .5 6h1.875v7H1.5a.5.5 0 0 0 0 1h13a.5.5 0 1 0 0-1h-.875V6H15.5a.5.5 0 0 0 .277-.916zM12.375 6v7h-1.25V6zm-2.5 0v7h-1.25V6zm-2.5 0v7h-1.25V6zm-2.5 0v7h-1.25V6zM8 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2M.5 15a.5.5 0 0 0 0 1h15a.5.5 0 1 0 0-1z"/>
        </svg>
        <span class="ms-2">Faculty</span>
        </a>
        <a class="navbar-section d-flex align-items-center me-3" href="usercourses.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-book-fill" viewBox="0 0 16 16">
            <path d="M8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
          </svg>
        <span class="ms-2">Courses</span>
        </a>
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
        <span class="user-info">Welcome: <?php echo htmlspecialchars($_SESSION['name']); ?> | Role: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
        <button onclick="confirmLogout()" class="logout-btn btn btn-link ms-3">Logout</button>
      </div>
    </div>
</nav>

    
<div class="container mt-4">
    <?php
    // Include database connection
    require_once 'db.php';

    // SQL Query to Fetch Student Data
    $query = "SELECT StudentIDNumber, LName, FName, MI FROM student";

    if ($result = $mysqli->query($query)) {
        if ($result->num_rows > 0) {
            echo "<table id='studentsTable' class='display table table-striped' style='width:100%'>
                    <thead>
                      <tr>
                        <th>Student ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Initial</th>
                      </tr>
                    </thead>
                    <tbody>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['StudentIDNumber']) . "</td>
                        <td>" . htmlspecialchars($row['LName']) . "</td>
                        <td>" . htmlspecialchars($row['FName']) . "</td>
                        <td>" . htmlspecialchars($row['MI']) . "</td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "No student records found.";
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
    $('#studentsTable').DataTable(); // Correct ID
});

function confirmLogout() {
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "index.php";
    }
  }
  </script>
</body>
</html>
