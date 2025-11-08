<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'deleted_count' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadId = $_POST['upload_id'] ?? '';

    if (empty($uploadId)) {
        $response['message'] = 'Upload ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Get upload info
        $sql = "SELECT * FROM upload_history WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $response['message'] = 'Upload not found';
            echo json_encode($response);
            exit;
        }

        $upload = $result->fetch_assoc();
        $stmt->close();

        // Delete results associated with this upload
        $sql = "DELETE FROM results WHERE upload_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $uploadId);
        $stmt->execute();
        $deletedCount = $stmt->affected_rows;
        $stmt->close();

        // Mark upload as undone
        $sql = "UPDATE upload_history SET status = 'undone', updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $uploadId);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();

        $response['success'] = true;
        $response['message'] = 'Upload undone successfully!';
        $response['deleted_count'] = $deletedCount;

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
