<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notice_id = $_POST['notice_id'] ?? '';

    if (empty($notice_id)) {
        $response['message'] = 'Notice ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        // Delete notice
        $sql = "DELETE FROM notices WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $notice_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Notice deleted successfully';
        } else {
            $response['message'] = 'Error deleting notice: ' . $stmt->error;
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
