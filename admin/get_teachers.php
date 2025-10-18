<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');
$response = ['success' => false, 'teachers' => [], 'message' => '', 'total' => 0];

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit();
}

try {
    // Get all teachers
    $sql = "SELECT id, first_name, last_name, email, profile_picture, status, created_at
            FROM teachers
            ORDER BY created_at DESC";

    $result = $conn->query($sql);

    $serial_no = 1;
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response['teachers'][] = [
                's_no' => $serial_no++,
                'id' => $row['id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'full_name' => $row['first_name'] . ' ' . $row['last_name'],
                'email' => $row['email'],
                'profile_picture' => $row['profile_picture'],
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        }
        $response['success'] = true;
        $response['total'] = $result->num_rows;
    } else {
        $response['success'] = true;
        $response['message'] = 'No teachers found';
    }

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
