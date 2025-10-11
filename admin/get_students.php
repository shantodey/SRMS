<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'students' => [], 'message' => ''];

try {
    $sql = "SELECT s.*, d.department_code 
            FROM students s 
            LEFT JOIN departments d ON s.department_id = d.id 
            ORDER BY s.id DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response['students'][] = [
                'id' => $row['id'],
                'index_no' => $row['index_no'],
                'student_name' => $row['student_name'],
                'roll_no' => $row['roll_no'],
                'department_code' => $row['department_code'],
                'batch_year' => $row['batch_year']
            ];
        }
        $response['success'] = true;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
