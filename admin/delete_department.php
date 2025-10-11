<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = $_POST['department_id'] ?? '';

    if (empty($department_id)) {
        $response['message'] = 'Department ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if department is being used by students
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE department_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $department_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            $response['message'] = 'Cannot delete department. ' . $row['count'] . ' student(s) are enrolled in this department.';
            echo json_encode($response);
            exit;
        }

        // Check if department has subjects
        $check_subjects_sql = "SELECT COUNT(*) as count FROM subjects WHERE department_id = ?";
        $check_subjects_stmt = $conn->prepare($check_subjects_sql);
        $check_subjects_stmt->bind_param("i", $department_id);
        $check_subjects_stmt->execute();
        $subjects_result = $check_subjects_stmt->get_result();
        $subjects_row = $subjects_result->fetch_assoc();

        if ($subjects_row['count'] > 0) {
            $response['message'] = 'Cannot delete department. ' . $subjects_row['count'] . ' subject(s) are associated with this department.';
            echo json_encode($response);
            exit;
        }

        // Delete department
        $sql = "DELETE FROM departments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $department_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Department deleted successfully';
        } else {
            $response['message'] = 'Error deleting department: ' . $stmt->error;
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
