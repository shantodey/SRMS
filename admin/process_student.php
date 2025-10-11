<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    // Get form data
    $index_no = $_POST['index_no'];
    $board_roll = $_POST['board_roll'];
    $roll_no = $_POST['roll_no'];
    $student_name = $_POST['student_name'];
    $department_id = $_POST['department_id'];
    $batch_id = $_POST['batch_id'];
    $semester = $_POST['semester'];

    // Validate data
    if (empty($index_no) || empty($board_roll) || empty($student_name)) {
        $response['message'] = 'Required fields cannot be empty';
        echo json_encode($response);
        exit;
    }

    // Check if student already exists
    $check_sql = "SELECT id FROM students WHERE index_no = ? OR board_roll = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $index_no, $board_roll);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $response['message'] = 'Student with this Index No or Board Roll already exists';
        echo json_encode($response);
        exit;
    }

    // Insert student data
    $sql = "INSERT INTO students (index_no, board_roll, roll_no, student_name, department_id, batch_id, semester) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiii", $index_no, $board_roll, $roll_no, $student_name, $department_id, $batch_id, $semester);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Student added successfully';
    } else {
        $response['message'] = 'Error adding student: ' . $stmt->error;
    }

    echo json_encode($response);
    exit;
}
?>
