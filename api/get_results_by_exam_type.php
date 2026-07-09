<?php
/**
 * Get results filtered by exam type (Final, Midterm, etc.)
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$studentId = $_GET['student_id'] ?? null;
$examType = $_GET['exam_type'] ?? null;

if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

if (!$examType) {
    echo json_encode(['success' => false, 'message' => 'Exam type is required']);
    exit;
}

try {
    // Get results for this exam type
    $sql = "SELECT
                r.id,
                r.marks_obtained,
                r.grade,
                s.subject_code,
                s.subject_name,
                e.total_marks,
                e.exam_type,
                e.title AS exam_title,
                e.exam_date,
                ROUND((r.marks_obtained / e.total_marks) * 100, 2) AS percentage
            FROM results r
            INNER JOIN exams e ON r.exam_id = e.id
            INNER JOIN subjects s ON r.subject_id = s.id
            WHERE r.student_id = ?
              AND e.exam_type = ?
            ORDER BY s.subject_code ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $studentId, $examType);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    echo json_encode([
        'success' => true,
        'results' => $results,
        'exam_type' => $examType,
        'count' => count($results)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching results: ' . $e->getMessage()
    ]);
}
