<?php
/**
 * API Endpoint: Upload Preview
 *
 * POST /admin/api/upload_preview.php
 *
 * Parses uploaded Excel file and returns validation results
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
    // Validate file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = isset($_FILES['file']) ? $_FILES['file']['error'] : 'No file uploaded';
        echo json_encode([
            'success' => false,
            'message' => 'File upload error: ' . $error
        ]);
        exit;
    }

    // Validate exam_id
    if (!isset($_POST['exam_id']) || empty($_POST['exam_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'exam_id is required'
        ]);
        exit;
    }

    $examId = (int)$_POST['exam_id'];

    // Get uploader info
    if (isset($_SESSION['admin_logged_in'])) {
        $uploaderId = $_SESSION['admin_id'] ?? null;
        $uploaderType = 'admin';
    } else {
        $uploaderId = $_SESSION['teacher_id'] ?? null;
        $uploaderType = 'teacher';
    }

    // Validate file type
    $allowedExtensions = ['xlsx', 'xls'];
    $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only .xlsx and .xls files are allowed.'
        ]);
        exit;
    }

    // Initialize uploader
    $uploader = new ResultUploader($conn, $examId, $uploaderId, $uploaderType);

    // Parse and validate
    $result = $uploader->parseAndValidate($_FILES['file']['tmp_name']);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
