<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $student_id = $_GET['id'] ?? '';

    if (empty($student_id)) {
        $response['message'] = 'Student ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        $sql = "SELECT s.*, b.name as batch_name, b.year as batch_year, d.name as department_name, d.code as department_code
                FROM students s
                LEFT JOIN batches b ON s.batch_id = b.id
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE s.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();

            // Get results count
            $sql = "SELECT COUNT(*) as result_count FROM results WHERE student_id = ?";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("i", $student_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $student['result_count'] = $result2->fetch_assoc()['result_count'];
            $stmt2->close();

            $response['success'] = true;
            $response['student'] = $student;
        } else {
            $response['message'] = 'Student not found';
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
