<?php
/**
 * API Endpoint: Get Exam Results
 *
 * GET /api/exam_results.php?exam_id=1&student_id=1
 *
 * Returns exam info and student's results for that exam
 */

header('Content-Type: application/json');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Validate parameters
    if (!isset($_GET['exam_id']) || empty($_GET['exam_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'exam_id is required'
        ]);
        exit;
    }

    if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'student_id is required'
        ]);
        exit;
    }

    $examId = (int)$_GET['exam_id'];
    $studentId = (int)$_GET['student_id'];

    // Get exam info
    $examSql = "SELECT
                    e.*,
                    d.name as department_name,
                    d.code as department_code,
                    s.subject_name,
                    s.subject_code
                FROM exams e
                INNER JOIN departments d ON e.department_id = d.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE e.id = ?";

    $stmt = $conn->prepare($examSql);
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Exam not found'
        ]);
        exit;
    }

    $exam = $result->fetch_assoc();

    // Get results for this student and exam
    $resultsSql = "SELECT
                        r.*,
                        s.subject_name,
                        s.subject_code
                    FROM results r
                    INNER JOIN subjects s ON r.subject_id = s.id
                    WHERE r.exam_id = ? AND r.student_id = ?
                    ORDER BY s.subject_code";

    $stmt = $conn->prepare($resultsSql);
    $stmt->bind_param("ii", $examId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    echo json_encode([
        'success' => true,
        'exam' => $exam,
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
