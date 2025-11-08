<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;

    if ($teacher_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
        exit();
    }

    // Check if teacher exists
    $check_stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM teachers WHERE id = ?");
    $check_stmt->bind_param("i", $teacher_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
        exit();
    }

    $teacher = $result->fetch_assoc();

    // Generate a secure random password (8 characters: letters and numbers)
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $temp_password = '';
    $length = 10;

    for ($i = 0; $i < $length; $i++) {
        $temp_password .= $characters[random_int(0, strlen($characters) - 1)];
    }

    // Hash the password
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

    // Update teacher's password in database
    $update_stmt = $conn->prepare("UPDATE teachers SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $teacher_id);

    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Temporary password generated successfully',
            'teacher_name' => $teacher['first_name'] . ' ' . $teacher['last_name'],
            'teacher_email' => $teacher['email'],
            'temp_password' => $temp_password
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
