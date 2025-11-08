<?php
/**
 * Helper Endpoint: Get Subjects by Department and Semester
 *
 * GET /get_subjects.php?department_id=1&semester=6
 */

header('Content-Type: application/json');

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([]);
    exit;
}

try {
    $departmentId = $_GET['department_id'] ?? null;
    $semester = $_GET['semester'] ?? null;

    if (!$departmentId || !$semester) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT id, subject_code, subject_name, total_marks
            FROM subjects
            WHERE department_id = ? AND semester = ?
            ORDER BY subject_code";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $departmentId, $semester);
    $stmt->execute();
    $result = $stmt->get_result();

    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    echo json_encode($subjects);

} catch (Exception $e) {
    echo json_encode([]);
}
