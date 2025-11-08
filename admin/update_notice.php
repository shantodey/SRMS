<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notice_id = $_POST['notice_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $publish_date = $_POST['publish_date'] ?? '';
    $status = $_POST['status'] ?? 'draft';

    if (empty($notice_id) || empty($title) || empty($content)) {
        $response['message'] = 'Notice ID, title, and content are required';
        echo json_encode($response);
        exit;
    }

    if (empty($publish_date)) {
        $publish_date = date('Y-m-d');
    }

    try {
        // Update notice
        $sql = "UPDATE notices SET title = ?, content = ?, publish_date = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $title, $content, $publish_date, $status, $notice_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Notice updated successfully';
        } else {
            $response['message'] = 'Error updating notice: ' . $stmt->error;
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