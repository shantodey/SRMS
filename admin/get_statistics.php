<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'stats' => [], 'message' => ''];

try {
    // Get total students count
    $sql = "SELECT COUNT(*) as total_students FROM students";
    $result = $conn->query($sql);
    $response['stats']['total_students'] = $result->fetch_assoc()['total_students'];

    // Get total published results
    $sql = "SELECT COUNT(DISTINCT student_id, semester) as total_results FROM results";
    $result = $conn->query($sql);
    $response['stats']['published_results'] = $result->fetch_assoc()['total_results'];

    // Get total departments
    $sql = "SELECT COUNT(*) as total_departments FROM departments";
    $result = $conn->query($sql);
    $response['stats']['total_departments'] = $result->fetch_assoc()['total_departments'];

    // Get total active notices
    $sql = "SELECT COUNT(*) as total_notices FROM notices WHERE status = 'published'";
    $result = $conn->query($sql);
    $response['stats']['active_notices'] = $result->fetch_assoc()['total_notices'];

    $response['success'] = true;
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
