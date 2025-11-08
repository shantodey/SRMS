<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $resultId = $_GET['id'] ?? '';

    if (empty($resultId)) {
        $response['message'] = 'Result ID is required';
        echo json_encode($response);
        exit;
    }

    try {
        $sql = "SELECT r.*,
                s.student_name, s.index_no, s.board_roll,
                e.exam_type, e.title as exam_title,
                subj.subject_code, subj.subject_name
                FROM results r
                INNER JOIN students s ON r.student_id = s.id
                INNER JOIN exams e ON r.exam_id = e.id
                INNER JOIN subjects subj ON r.subject_id = subj.id
                WHERE r.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $resultId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['success'] = true;
            $response['result'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Result not found';
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
