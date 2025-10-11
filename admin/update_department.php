<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_id = $_POST['dept_id'] ?? '';
    $dept_name = trim($_POST['dept_name'] ?? '');
    $dept_code = strtoupper(trim($_POST['dept_code'] ?? ''));

    if (empty($dept_id) || empty($dept_name) || empty($dept_code)) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if department with same code already exists (excluding current department)
        $sql = "SELECT id FROM departments WHERE code = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $dept_code, $dept_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = 'A department with this code already exists';
            $stmt->close();
            echo json_encode($response);
            exit;
        }
        $stmt->close();

        // Update department
        $sql = "UPDATE departments SET name = ?, code = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $dept_name, $dept_code, $dept_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Department updated successfully';
        } else {
            $response['message'] = 'Error updating department: ' . $stmt->error;
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