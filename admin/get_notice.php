<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $notice_id = $_GET['id'] ?? '';

    if (empty($notice_id)) {
        $response['message'] = 'Notice ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        $sql = "SELECT * FROM notices WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $notice_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['success'] = true;
            $response['notice'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Notice not found';
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
