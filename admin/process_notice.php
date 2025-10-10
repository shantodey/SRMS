<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $publish_date = $_POST['publish_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'draft';

    // Validate data
    if (empty($title) || empty($content)) {
        $response['message'] = 'Title and content are required';
        echo json_encode($response);
        exit;
    }

    // For now, use admin ID 1 (you should use the logged-in admin's ID from session)
    $admin_id = $_SESSION['admin_id'] ?? 1;

    try {
        $sql = "INSERT INTO notices (title, content, status, publish_date, created_by)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $title, $content, $status, $publish_date, $admin_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Notice saved successfully';
            $response['notice_id'] = $stmt->insert_id;
        } else {
            $response['message'] = 'Error saving notice: ' . $stmt->error;
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
