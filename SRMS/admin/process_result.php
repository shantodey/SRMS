<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    // Get form data
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $marks_obtained = $_POST['marks_obtained'];
    $semester = $_POST['semester'];
    $exam_date = $_POST['exam_date'];

    // Calculate grade based on marks
    $grade = '';
    $sql = "SELECT grade FROM grade_scale WHERE ? >= min_percentage ORDER BY min_percentage DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $percentage = ($marks_obtained / 100) * 100; // Assuming total marks is 100
    $stmt->bind_param("d", $percentage);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $grade = $row['grade'];
    }

    // Insert result data
    $sql = "INSERT INTO results (student_id, subject_id, marks_obtained, grade, semester, exam_date) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iidsss", $student_id, $subject_id, $marks_obtained, $grade, $semester, $exam_date);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Result added successfully';
    } else {
        $response['message'] = 'Error adding result: ' . $stmt->error;
    }

    echo json_encode($response);
    exit;
}
?>
