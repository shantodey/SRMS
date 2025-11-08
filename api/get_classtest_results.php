<?php
/**
 * Get class test results for a specific subject and student
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$studentId = $_GET['student_id'] ?? null;
$subjectId = $_GET['subject_id'] ?? null;
$examType = $_GET['exam_type'] ?? 'ClassTest';

if (!$studentId || !$subjectId) {
    echo json_encode(['success' => false, 'message' => 'Student ID and Subject ID are required']);
    exit;
}

try {
    // Get all results for this subject and exam type
    $sql = "SELECT
                r.id,
                r.marks_obtained,
                r.grade,
                s.subject_code,
                s.subject_name,
                e.exam_number,
                e.title AS exam_title,
                e.total_marks,
                e.exam_date,
                ROUND((r.marks_obtained / e.total_marks) * 100, 2) AS percentage
            FROM results r
            INNER JOIN exams e ON r.exam_id = e.id
            INNER JOIN subjects s ON r.subject_id = s.id
            WHERE r.student_id = ?
              AND r.subject_id = ?
              AND e.exam_type = ?
            ORDER BY e.exam_number ASC, e.exam_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $studentId, $subjectId, $examType);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching class test results: ' . $e->getMessage()
    ]);
}
