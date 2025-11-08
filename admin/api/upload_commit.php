<?php
/**
 * API Endpoint: Upload Commit
 *
 * POST /admin/api/upload_commit.php
 *
 * Commits validated results to database
 *
 * Request body (JSON):
 * {
 *   "exam_id": 1,
 *   "valid_rows": [...],  // from preview response
 *   "conflict_policy": "overwrite|skip",
 *   "filename": "results.xlsx"
 * }
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../includes/ResultUploader.php';

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

    // Validate required fields
    if (empty($input['exam_id']) || empty($input['valid_rows'])) {
        echo json_encode([
            'success' => false,
            'message' => 'exam_id and valid_rows are required'
        ]);
        exit;
    }

    $examId = (int)$input['exam_id'];
    $validRows = $input['valid_rows'];
    $conflictPolicy = $input['conflict_policy'] ?? 'overwrite';
    $filename = $input['filename'] ?? 'upload.xlsx';

    // Validate conflict policy
    if (!in_array($conflictPolicy, ['overwrite', 'skip'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid conflict_policy. Must be "overwrite" or "skip"'
        ]);
        exit;
    }

    // Get uploader info
    if (isset($_SESSION['admin_logged_in'])) {
        $uploaderId = $_SESSION['admin_id'] ?? null;
        $uploaderType = 'admin';
    } else {
        $uploaderId = $_SESSION['teacher_id'] ?? null;
        $uploaderType = 'teacher';
    }

    // Initialize uploader
    $uploader = new ResultUploader($conn, $examId, $uploaderId, $uploaderType);

    // Commit results
    $result = $uploader->commit($validRows, $conflictPolicy, $filename);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
