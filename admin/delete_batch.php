<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = $_POST['batch_id'] ?? '';

    if (empty($batch_id)) {
        $response['message'] = 'Batch ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if batch is being used by students
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE batch_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $batch_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            $response['message'] = 'Cannot delete batch. ' . $row['count'] . ' student(s) are enrolled in this batch.';
            echo json_encode($response);
            exit;
        }

        // Delete batch
        $sql = "DELETE FROM batches WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $batch_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Batch deleted successfully';
        } else {
            $response['message'] = 'Error deleting batch: ' . $stmt->error;
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
