<?php
/**
 * API Endpoint: List Exams
 *
 * GET /admin/api/exam_list.php?exam_type=ClassTest&semester=6&department_id=1&subject_id=5
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Build filters from query params
    $filters = [];

    if (!empty($_GET['exam_type'])) {
        $filters['exam_type'] = $_GET['exam_type'];
    }

    if (!empty($_GET['semester'])) {
        $filters['semester'] = (int)$_GET['semester'];
    }

    if (!empty($_GET['department_id'])) {
        $filters['department_id'] = (int)$_GET['department_id'];
    }

    if (isset($_GET['subject_id'])) {
        if ($_GET['subject_id'] === 'null' || $_GET['subject_id'] === '') {
            $filters['subject_id'] = null;
        } else {
            $filters['subject_id'] = (int)$_GET['subject_id'];
        }
    }

    // Build options
    $options = [];

    if (!empty($_GET['limit'])) {
        $options['limit'] = (int)$_GET['limit'];
    }

    if (!empty($_GET['offset'])) {
        $options['offset'] = (int)$_GET['offset'];
    }

    if (!empty($_GET['order_by'])) {
        $options['order_by'] = $_GET['order_by'];
    }

    // Fetch exams
    $examManager = new ExamManager($conn);
    $exams = $examManager->getExams($filters, $options);

    echo json_encode([
        'success' => true,
        'exams' => $exams,
        'count' => count($exams)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
