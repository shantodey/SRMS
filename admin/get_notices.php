<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'notices' => [], 'message' => ''];

try {
    $sql = "SELECT n.*, a.email as created_by_email
            FROM notices n
            LEFT JOIN admin a ON n.created_by = a.id
            ORDER BY n.publish_date DESC, n.created_at DESC";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response['notices'][] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'content' => $row['content'],
                'status' => $row['status'],
                'publish_date' => $row['publish_date'],
                'created_by_email' => $row['created_by_email'],
                'created_at' => $row['created_at']
            ];
        }
        $response['success'] = true;
    } else {
        $response['success'] = true;
        $response['message'] = 'No notices found';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
