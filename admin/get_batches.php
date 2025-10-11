<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'batches' => [], 'message' => ''];

try {
    $sql = "SELECT * FROM batches ORDER BY year DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response['batches'][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'year' => $row['year'],
                'created_at' => $row['created_at']
            ];
        }
        $response['success'] = true;
    } else {
        $response['success'] = true;
        $response['message'] = 'No batches found';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
