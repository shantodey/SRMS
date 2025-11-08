<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

    // Validation
    if ($teacher_id <= 0) {
        $response['message'] = 'Invalid teacher ID';
        echo json_encode($response);
        exit();
    }

    if (empty($new_password)) {
        $response['message'] = 'Password is required';
        echo json_encode($response);
        exit();
    }

    if (strlen($new_password) < 6) {
        $response['message'] = 'Password must be at least 6 characters long';
        echo json_encode($response);
        exit();
    }

    try {
        // Check if teacher exists
        $check_stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM teachers WHERE id = ?");
        $check_stmt->bind_param("i", $teacher_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            $response['message'] = 'Teacher not found';
            echo json_encode($response);
            exit();
        }

        $teacher = $result->fetch_assoc();

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password
        $update_stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $teacher_id);

        if ($update_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Password reset successfully for ' . $teacher['first_name'] . ' ' . $teacher['last_name'];
            $response['teacher_email'] = $teacher['email'];
        } else {
            $response['message'] = 'Failed to reset password';
        }

        $update_stmt->close();
        $check_stmt->close();

    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
