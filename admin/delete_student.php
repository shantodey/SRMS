<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';

    if (empty($student_id)) {
        $response['message'] = 'Student ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // First, delete related results
        $sql = "DELETE FROM results WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        // Then delete the student
        $sql = "DELETE FROM students WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);

        if ($stmt->execute()) {
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Student and related results deleted successfully';
        } else {
            $conn->rollback();
            $response['message'] = 'Error deleting student: ' . $stmt->error;
        }

        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
