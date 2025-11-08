<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_name = $_POST['batch_name'] ?? '';
    $batch_year = $_POST['batch_year'] ?? '';

    // Validate data
    if (empty($batch_name) || empty($batch_year)) {
        $response['message'] = 'Batch name and year are required';
        echo json_encode($response);
        exit;
    }

    // Validate year is a number
    if (!is_numeric($batch_year) || $batch_year < 2000 || $batch_year > 2100) {
        $response['message'] = 'Please enter a valid year';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if batch already exists
        $check_sql = "SELECT id FROM batches WHERE name = ? OR year = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $batch_name, $batch_year);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = 'Batch with this name or year already exists';
            echo json_encode($response);
            exit;
        }

        // Insert batch
        $sql = "INSERT INTO batches (name, year) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $batch_name, $batch_year);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Batch added successfully';
            $response['batch_id'] = $stmt->insert_id;
        } else {
            $response['message'] = 'Error adding batch: ' . $stmt->error;
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
