<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_name = $_POST['dept_name'] ?? '';
    $dept_code = $_POST['dept_code'] ?? '';

    // Validate data
    if (empty($dept_name) || empty($dept_code)) {
        $response['message'] = 'Department name and code are required';
        echo json_encode($response);
        exit;
    }

    // Validate code format (alphanumeric, 2-10 characters)
    if (!preg_match('/^[A-Z0-9]{2,10}$/i', $dept_code)) {
        $response['message'] = 'Department code should be 2-10 alphanumeric characters';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if department code already exists
        $check_sql = "SELECT id FROM departments WHERE code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $dept_code);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = 'Department with this code already exists';
            echo json_encode($response);
            exit;
        }

        // Insert department
        $sql = "INSERT INTO departments (name, code) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $dept_name, $dept_code);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Department added successfully';
            $response['department_id'] = $stmt->insert_id;
        } else {
            $response['message'] = 'Error adding department: ' . $stmt->error;
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
