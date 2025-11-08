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

    // Sanitize content to prevent XSS
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

    try {
        // Get user name based on user type
        $publisher_name = 'Administrator';
        if ($user_type === 'teacher') {
            $teacher_query = $conn->prepare("SELECT first_name, last_name FROM teachers WHERE id = ?");
            $teacher_query->bind_param("i", $user_id);
            $teacher_query->execute();
            $teacher_result = $teacher_query->get_result();
            if ($teacher_row = $teacher_result->fetch_assoc()) {
                $publisher_name = trim($teacher_row['first_name'] . ' ' . $teacher_row['last_name']);
            }
            $teacher_query->close();
        } else {
            $admin_query = $conn->prepare("SELECT email FROM admin WHERE id = ?");
            $admin_query->bind_param("i", $user_id);
            $admin_query->execute();
            $admin_result = $admin_query->get_result();
            if ($admin_row = $admin_result->fetch_assoc()) {
                // Use email prefix or just "Administrator"
                $publisher_name = 'Administrator';
            }
            $admin_query->close();
        }

        // Check if publisher columns exist
        $check_columns = $conn->query("SHOW COLUMNS FROM notices LIKE 'publisher_type'");

        if ($check_columns && $check_columns->num_rows > 0) {
            // New structure with publisher fields
            $sql = "INSERT INTO notices (title, content, status, publish_date, publisher_type, publisher_id, publisher_name)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssiis", $title, $content, $status, $publish_date, $user_type, $user_id, $publisher_name);
        } else {
            // Fallback: check for creator_type columns
            $check_creator = $conn->query("SHOW COLUMNS FROM notices LIKE 'creator_type'");

            if ($check_creator && $check_creator->num_rows > 0) {
                $sql = "INSERT INTO notices (title, content, status, publish_date, creator_type, creator_id)
                        VALUES (?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $title, $content, $status, $publish_date, $user_type, $user_id);
            } else {
                // Old structure with created_by (admin only)
                $sql = "INSERT INTO notices (title, content, status, publish_date, created_by)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $title, $content, $status, $publish_date, $user_id);
            }
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
