<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultId = $_POST['result_id'] ?? '';

    if (empty($resultId)) {
        $response['message'] = 'Result ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        $sql = "DELETE FROM results WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $resultId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Result deleted successfully!';
            } else {
                $response['message'] = 'Result not found or already deleted';
            }
        } else {
            $response['message'] = 'Failed to delete result';
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
