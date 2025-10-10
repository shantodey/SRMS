<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'departments' => [], 'message' => ''];

try {
    $sql = "SELECT * FROM departments ORDER BY name ASC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response['departments'][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'code' => $row['code'],
                'created_at' => $row['created_at']
            ];
        }
        $response['success'] = true;
    } else {
        $response['success'] = true;
        $response['message'] = 'No departments found';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
