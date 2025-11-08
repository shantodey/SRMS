<?php
/**
 * Get subjects that have class test results for a student
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$studentId = $_GET['student_id'] ?? null;
$semester = $_GET['semester'] ?? null;
$examType = $_GET['exam_type'] ?? 'ClassTest';

if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

try {
    // Get subjects with results for this student and exam type
    $sql = "SELECT DISTINCT
                s.id AS subject_id,
                s.subject_code,
                s.subject_name,
                COUNT(DISTINCT e.id) AS test_count
            FROM results r
            INNER JOIN exams e ON r.exam_id = e.id
            INNER JOIN subjects s ON r.subject_id = s.id
            WHERE r.student_id = ?
              AND e.exam_type = ?";

    $params = [$studentId, $examType];
    $types = 'is';

    if ($semester) {
        $sql .= " AND e.semester = ?";
        $params[] = $semester;
        $types .= 'i';
    }

    $sql .= " GROUP BY s.id, s.subject_code, s.subject_name
              ORDER BY s.subject_code";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching subjects: ' . $e->getMessage()
    ]);
}
