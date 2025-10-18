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

    if ($teacher_id <= 0) {
        $response['message'] = 'Invalid teacher ID';
        echo json_encode($response);
        exit();
    }

    try {
        // Get teacher's profile picture before deleting
        $stmt = $conn->prepare("SELECT profile_picture FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $teacher = $result->fetch_assoc();
            $profile_picture = $teacher['profile_picture'];

            // Delete teacher from database
            $delete_stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
            $delete_stmt->bind_param("i", $teacher_id);

            if ($delete_stmt->execute()) {
                // Delete profile picture file if exists
                if ($profile_picture && file_exists("../uploads/teacher_profiles/" . $profile_picture)) {
                    unlink("../uploads/teacher_profiles/" . $profile_picture);
                }

                $response['success'] = true;
                $response['message'] = 'Teacher account deleted successfully';
            } else {
                $response['message'] = 'Failed to delete teacher account';
            }

            $delete_stmt->close();
        } else {
            $response['message'] = 'Teacher not found';
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
