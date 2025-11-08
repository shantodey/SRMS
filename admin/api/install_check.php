<?php
/**
 * Check if exam layer is already installed
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

try {
    // Check if exams table exists
    $result = $conn->query("SHOW TABLES LIKE 'exams'");
    $examsTableExists = $result->num_rows > 0;

    // Check if exam_id column exists in results
    $result = $conn->query("SHOW COLUMNS FROM results LIKE 'exam_id'");
    $examIdColumnExists = $result->num_rows > 0;

    $alreadyInstalled = $examsTableExists && $examIdColumnExists;

    echo json_encode([
        'success' => true,
        'already_installed' => $alreadyInstalled,
        'exams_table_exists' => $examsTableExists,
        'exam_id_column_exists' => $examIdColumnExists
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
