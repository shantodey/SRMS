<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = $_POST['batch_id'] ?? '';
    $batch_name = trim($_POST['batch_name'] ?? '');
    $batch_year = $_POST['batch_year'] ?? '';

    if (empty($batch_id) || empty($batch_name) || empty($batch_year)) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if batch with same name already exists (excluding current batch)
        $sql = "SELECT id FROM batches WHERE name = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $batch_name, $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = 'A batch with this name already exists';
            $stmt->close();
            echo json_encode($response);
            exit;
        }
        $stmt->close();

        // Update batch
        $sql = "UPDATE batches SET name = ?, year = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $batch_name, $batch_year, $batch_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Batch updated successfully';
        } else {
            $response['message'] = 'Error updating batch: ' . $stmt->error;
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