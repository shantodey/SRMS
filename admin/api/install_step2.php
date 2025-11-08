<?php
/**
 * Installation Step 2: Migrate Data
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn->begin_transaction();

    // Update migration status
    $conn->query("INSERT INTO `migration_status` (`migration_name`, `status`, `started_at`)
                  VALUES ('exam_layer_step2', 'running', CURRENT_TIMESTAMP)
                  ON DUPLICATE KEY UPDATE status = 'running', started_at = CURRENT_TIMESTAMP");

    // Create exams from existing results
    $sql = "INSERT INTO `exams` (
        `exam_type`,
        `exam_number`,
        `title`,
        `semester`,
        `department_id`,
        `subject_id`,
        `total_marks`,
        `exam_date`,
        `created_by`,
        `created_at`
    )
    SELECT DISTINCT
        COALESCE(r.exam_type, 'Final') AS exam_type,
        1 AS exam_number,
        CONCAT(
            COALESCE(r.exam_type, 'Final'),
            ' - ',
            COALESCE(sub.subject_name, CONCAT('Semester ', r.semester)),
            ' - Sem ',
            r.semester,
            IFNULL(CONCAT(' - ', DATE_FORMAT(r.exam_date, '%b %Y')), '')
        ) AS title,
        r.semester,
        s.department_id,
        r.subject_id,
        COALESCE(r.total_marks, sub.total_marks, 100) AS total_marks,
        r.exam_date,
        NULL AS created_by,
        MIN(r.created_at) AS created_at
    FROM `results` r
    INNER JOIN `students` s ON r.student_id = s.id
    LEFT JOIN `subjects` sub ON r.subject_id = sub.id
    WHERE r.exam_id IS NULL
    GROUP BY
        COALESCE(r.exam_type, 'Final'),
        r.semester,
        s.department_id,
        r.subject_id,
        r.exam_date,
        COALESCE(r.total_marks, sub.total_marks, 100)
    ORDER BY r.semester, r.exam_date, COALESCE(r.exam_type, 'Final')";

    $conn->query($sql);
    $examsCreated = $conn->affected_rows;

    // Link results to exams
    $sql = "UPDATE `results` r
    INNER JOIN `students` s ON r.student_id = s.id
    LEFT JOIN `subjects` sub ON r.subject_id = sub.id
    INNER JOIN `exams` e ON
        e.exam_type = COALESCE(r.exam_type, 'Final')
        AND e.semester = r.semester
        AND e.department_id = s.department_id
        AND (
            (e.subject_id = r.subject_id) OR
            (e.subject_id IS NULL AND r.subject_id IS NULL)
        )
        AND (
            (e.exam_date = r.exam_date) OR
            (e.exam_date IS NULL AND r.exam_date IS NULL)
        )
        AND e.total_marks = COALESCE(r.total_marks, sub.total_marks, 100)
    SET r.exam_id = e.id
    WHERE r.exam_id IS NULL";

    $conn->query($sql);
    $resultsMapped = $conn->affected_rows;

    // Count unmapped results
    $result = $conn->query("SELECT COUNT(*) as count FROM results WHERE exam_id IS NULL");
    $unmappedResults = $result->fetch_assoc()['count'];

    if ($unmappedResults > 0) {
        throw new Exception("$unmappedResults results could not be mapped to exams");
    }

    // Update migration status
    $conn->query("UPDATE `migration_status`
                  SET status = 'completed', completed_at = CURRENT_TIMESTAMP
                  WHERE migration_name = 'exam_layer_step2'");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Data migrated successfully',
        'exams_created' => $examsCreated,
        'results_mapped' => $resultsMapped,
        'unmapped_results' => $unmappedResults
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
