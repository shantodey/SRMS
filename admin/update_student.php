<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $student_name = trim($_POST['student_name'] ?? '');
    $roll_no = trim($_POST['roll_no'] ?? '');
    $index_no = trim($_POST['index_no'] ?? '');
    $board_roll = trim($_POST['board_roll'] ?? '');
    $batch_id = $_POST['batch_id'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $semester = $_POST['semester'] ?? '';

    if (empty($student_id) || empty($student_name) || empty($index_no)) {
        $response['message'] = 'Student ID, name, and index number are required';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if index number already exists (excluding current student)
        $sql = "SELECT id FROM students WHERE index_no = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $index_no, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = 'A student with this index number already exists';
            $stmt->close();
            echo json_encode($response);
            exit;
        }
        $stmt->close();

        // Update student
        $sql = "UPDATE students SET student_name = ?, roll_no = ?, index_no = ?, board_roll = ?, batch_id = ?, department_id = ?, semester = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiiii", $student_name, $roll_no, $index_no, $board_roll, $batch_id, $department_id, $semester, $student_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Student updated successfully';
        } else {
            $response['message'] = 'Error updating student: ' . $stmt->error;
        }

        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>