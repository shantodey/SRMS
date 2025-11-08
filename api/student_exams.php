<?php
/**
 * API Endpoint: Get Student Exams
 *
 * GET /api/student_exams.php?student_id=1&exam_type=ClassTest
 *
 * Returns student info and available exams for that student
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/ExamManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Validate student_id
    if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'student_id is required'
        ]);
        exit;
    }

    $studentId = (int)$_GET['student_id'];
    $examType = $_GET['exam_type'] ?? null;

    // Get student info
    $sql = "SELECT
                s.*,
                d.name as department_name,
                d.code as department_code,
                b.name as batch_name,
                b.year as batch_year
            FROM students s
            INNER JOIN departments d ON s.department_id = d.id
            INNER JOIN batches b ON s.batch_id = b.id
            WHERE s.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
        exit;
    }

    $student = $result->fetch_assoc();

    // Get exams for this student
    $examManager = new ExamManager($conn);
    $exams = $examManager->getExamsForStudent($studentId, $examType);

    // For each exam, check if student has results
    foreach ($exams as &$exam) {
        $resultSql = "SELECT COUNT(*) as result_count
                      FROM results
                      WHERE student_id = ? AND exam_id = ?";
        $stmt = $conn->prepare($resultSql);
        $stmt->bind_param("ii", $studentId, $exam['id']);
        $stmt->execute();
        $resultRow = $stmt->get_result()->fetch_assoc();
        $exam['has_results'] = $resultRow['result_count'] > 0;
    }

    echo json_encode([
        'success' => true,
        'student' => $student,
        'exams' => $exams
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
