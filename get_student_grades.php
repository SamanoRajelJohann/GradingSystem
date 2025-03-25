<?php
require_once("db.php");

if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    
    $query = "SELECT g.Prelim, g.Midterm, g.Final, g.GWA, g.EQ, g.Remarks,
                     c.CourseIDnumber, c.CourseName, g.SchoolYear
              FROM grades g
              JOIN course c ON g.CourseID = c.CourseID
              WHERE g.StudentID = ?
              ORDER BY c.CourseName";
              
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grades = array();
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($grades);
    
    $stmt->close();
    $mysqli->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Student ID not provided']);
}
?> 