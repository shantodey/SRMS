<?php
/**
 * API Endpoint: Create New Exam
 *
 * POST /admin/api/exam_create.php
 *
 * Request body (JSON):
 * {
 *   "exam_type": "Final|Midterm|ClassTest|Assignment|Quiz",
 *   "exam_number": 1,  // optional, auto-increment for ClassTest
 *   "title": "CT-3 - Data Structures - Sem 6",  // optional, auto-generated
 *   "semester": 6,
 *   "department_id": 1,
 *   "subject_id": 5,  // null for semester-wide exams
 *   "total_marks": 100,  // optional
 *   "exam_date": "2025-10-24",  // optional
 *   "created_by": 1  // teacher/admin ID
 * }
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../includes/ExamManager.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['teacher_logged_in'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }

    // Set created_by from session
    if (isset($_SESSION['admin_logged_in'])) {
        $input['created_by'] = $_SESSION['admin_id'] ?? null;
    } elseif (isset($_SESSION['teacher_logged_in'])) {
        $input['created_by'] = $_SESSION['teacher_id'] ?? null;
    }

    // Auto-generate title if not provided
    if (empty($input['title'])) {
        $examManager = new ExamManager($conn);

        // Fetch subject name if subject_id provided
        if (!empty($input['subject_id'])) {
            $subjectSql = "SELECT subject_name FROM subjects WHERE id = ?";
            $stmt = $conn->prepare($subjectSql);
            $stmt->bind_param("i", $input['subject_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $input['subject_name'] = $row['subject_name'];
            }
        }

        $input['title'] = $examManager->generateTitle($input);
    }

    // Create exam
    $examManager = new ExamManager($conn);
    $result = $examManager->createExam($input);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
