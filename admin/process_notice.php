<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isLoggedIn()) {
    $response['message'] = 'Not authenticated';
    echo json_encode($response);
    exit;
}

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

    // Get user info from session
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'] ?? 'admin';

    try {
        // Check if creator_type and creator_id columns exist
        $check_columns = $conn->query("SHOW COLUMNS FROM notices LIKE 'creator_type'");

        if ($check_columns && $check_columns->num_rows > 0) {
            // New structure with creator_type and creator_id
            $sql = "INSERT INTO notices (title, content, status, publish_date, creator_type, creator_id)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $title, $content, $status, $publish_date, $user_type, $user_id);
        } else {
            // Old structure with created_by (admin only)
            $admin_id = $user_id;
            $sql = "INSERT INTO notices (title, content, status, publish_date, created_by)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $title, $content, $status, $publish_date, $admin_id);
        }

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
